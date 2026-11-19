<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadLog;
use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

/**
 * Pruebas de integración del Centro de Actividad, Telemetría IP de Descargas y Auditoría Spatie.
 */
class ActivityCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $empleado;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        Storage::fake((string) config('downloads.vault.disk', 'downloads_vault'));

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['active' => true]);
        $this->admin->assignRole('admin');

        $this->empleado = User::factory()->create(['active' => true]);
        $this->empleado->assignRole('empleado');

        SiteSetting::create([
            'id'            => 1,
            'site_name'     => 'Portal Test Actividad',
            'primary_color' => '#10b981',
            'heading_font'  => 'Inter',
            'body_font'     => 'Inter',
        ]);
        SiteSetting::clearCache();
        DownloadSetting::clearCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function bloquea_acceso_a_no_administradores_en_centro_de_actividad_y_purgado(): void
    {
        $this->actingAs($this->empleado)->get(route('actividad.index'))->assertForbidden();
        $this->actingAs($this->empleado)->delete(route('actividad.purge-downloads'), ['mode' => 'all'])->assertForbidden();
        $this->actingAs($this->empleado)->delete(route('actividad.purge-activity'), ['mode' => 'all'])->assertForbidden();
    }

    #[Test]
    public function guarda_ip_real_en_descargas_registra_auditoria_spatie_sin_secretos_y_purga_manteniendo_historicos_perpetuos(): void
    {
        $zip = $this->crearZip('fv-plugin-ip.zip');

        $this->actingAs($this->admin)->post(route('downloads.admin.store'), [
            'title'             => 'FV Plugin Telemetría',
            'short_description' => 'Plugin con control de IPs',
            'version'           => '2.1.0',
            'access_type'       => 'free',
            'status'            => 'published',
            'package_file'      => $zip,
        ])->assertRedirect(route('downloads.admin.index'));

        $this->actingAs($this->admin)->put(route('downloads.admin.settings.update'), [
            'page_title'                  => 'Descargas',
            'slug'                        => 'descargas',
            'description'                 => 'Catálogo público',
            'icon'                        => 'pi pi-download',
            'public_visibility'           => 'public',
            'default_view_mode'           => 'grid',
            'allow_view_toggle'           => true,
            'cols_desktop'                => 3,
            'cols_tablet'                 => 2,
            'cols_mobile'                 => 1,
            'gap_size'                    => 'md',
            'header_align'                => 'left',
            'per_page'                    => 12,
            'show_quick_download'         => true,
            'show_downloads_count_public' => false,
        ])->assertSessionHasNoErrors();

        // Crear y editar una Página para verificar auditoría Spatie sin layout_json pesado
        $pagina = Page::create([
            'title'       => 'Sobre Nosotros',
            'slug'        => 'sobre-nosotros',
            'is_active'   => true,
            'layout_json' => ['sections' => [['id' => 'sec_heavy']]],
        ]);
        $this->actingAs($this->admin)->withServerVariables(['REMOTE_ADDR' => '198.51.100.77']);
        $pagina->update(['title' => 'Quiénes Somos']);

        $pageUpdateActivity = Activity::query()
            ->where('subject_type', Page::class)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($pageUpdateActivity);
        $this->assertStringContainsString('Actualizó página «Quiénes Somos»', $pageUpdateActivity->description);
        $changes = $pageUpdateActivity->attribute_changes?->toArray() ?? [];
        $this->assertSame('Quiénes Somos', $changes['attributes']['title'] ?? null);
        $this->assertArrayNotHasKey('layout_json', $changes['attributes'] ?? []);

        // Simular que un bot de IA (ClaudeBot) intenta descargar: debe recibir 403 y NO sumar descarga
        auth()->guard('web')->logout();
        $item = DownloadItem::firstOrFail();

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 AppleWebKit/537.36 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
        ])->get("/download-vault/{$item->slug}/request")->assertForbidden();
        $this->flushHeaders();

        // Simular 2 descargas públicas desde dos IPs reales distintas

        foreach (['203.0.113.45', '192.0.2.88'] as $clientIp) {
            $req = $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
                ->get("/download-vault/{$item->slug}/request");
            $req->assertRedirect();
            $signedUrl = (string) $req->headers->get('Location');

            $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
                ->get($signedUrl)
                ->assertOk();
        }

        // Verificar que las IPs reales están almacenadas en download_logs y los históricos suman 2
        $this->assertDatabaseHas('download_logs', ['ip_address' => '203.0.113.45']);
        $this->assertDatabaseHas('download_logs', ['ip_address' => '192.0.2.88']);
        $this->assertSame(2, $item->fresh()->downloads_count);
        $this->assertSame(2, (int) DownloadSetting::query()->value('total_downloads_all_time'));

        // El administrador consulta el Centro de Actividad y filtra por IP
        $this->actingAs($this->admin)
            ->get(route('actividad.index', ['tab' => 'descargas', 'dl_search' => '203.0.113.45']))
            ->assertOk()
            ->assertSee('203.0.113.45');

        $this->actingAs($this->admin)
            ->get(route('actividad.index', ['tab' => 'auditoria']))
            ->assertOk();

        // Purgar TODOS los logs detallados de IPs y verificar que los históricos general y del ítem permanecen en 2
        $this->actingAs($this->admin)
            ->delete(route('actividad.purge-downloads'), ['mode' => 'all'])
            ->assertRedirect();

        $this->assertSame(0, DownloadLog::count());
        $this->assertSame(2, $item->fresh()->downloads_count);
        $this->assertSame(2, (int) DownloadSetting::query()->value('total_downloads_all_time'));
    }

    private function crearZip(string $name): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'act_zip_') . '.zip';
        $this->tempFiles[] = $tmp;

        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('plugin.xml', '<extension/>');
        $zip->close();

        return new UploadedFile($tmp, $name, 'application/zip', null, true);
    }
}
