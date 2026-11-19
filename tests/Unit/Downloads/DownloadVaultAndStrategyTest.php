<?php

declare(strict_types=1);

namespace Tests\Unit\Downloads;

use App\Models\User;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Modules\Downloads\Services\DownloadVaultService;
use App\Modules\Downloads\Strategies\StandardDownloadAccessStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

/**
 * Pruebas unitarias de seguridad de la bóveda privada (`DownloadVaultService`)
 * y de la estrategia de autorización (`StandardDownloadAccessStrategy`).
 */
class DownloadVaultAndStrategyTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $vaultDisk = (string) config('downloads.vault.disk', 'downloads_vault');
        Storage::fake($vaultDisk);
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
    public function almacena_paquete_zip_valido_con_nombre_ofuscado_y_sha256(): void
    {
        $zipPath = $this->crearZipTemporal([
            'yootheme-timeline/plugin.json' => '{"name":"FV Timeline"}',
            'yootheme-timeline/element.php' => '<?php // Elemento YOOtheme Pro',
        ]);

        $uploaded = new UploadedFile($zipPath, 'FV Timeline YOOtheme v1.0.zip', 'application/zip', null, true);
        $vault = app(DownloadVaultService::class);

        $meta = $vault->storeUploadedPackage($uploaded);

        $this->assertSame('FV-Timeline-YOOtheme-v1.0.zip', $meta['original_filename']);
        $this->assertSame(hash_file('sha256', $zipPath), $meta['sha256_hash']);
        $this->assertStringEndsWith('.bin', $meta['private_file_path']);
        $this->assertStringNotContainsString('timeline', $meta['private_file_path']);

        Storage::disk($meta['vault_disk'])->assertExists($meta['private_file_path']);
    }

    #[Test]
    public function rechaza_archivo_falsificado_que_no_tiene_magic_bytes_zip_reales(): void
    {
        $fakePath = tempnam(sys_get_temp_dir(), 'fake_zip_');
        file_put_contents($fakePath, '<?php system($_GET["cmd"]); ?>');
        $this->tempFiles[] = $fakePath;

        $uploaded = new UploadedFile($fakePath, 'malicioso.zip', 'application/zip', null, true);
        $vault = app(DownloadVaultService::class);

        $this->expectException(ValidationException::class);
        $vault->storeUploadedPackage($uploaded);
    }

    #[Test]
    public function rechaza_paquete_zip_con_ataque_zip_slip(): void
    {
        $zipPath = $this->crearZipTemporal([
            '../../public/shell.php' => 'contenido-peligroso',
        ]);

        $uploaded = new UploadedFile($zipPath, 'zip-slip.zip', 'application/zip', null, true);
        $vault = app(DownloadVaultService::class);

        $this->expectException(ValidationException::class);
        $vault->storeUploadedPackage($uploaded);
    }

    #[Test]
    public function estrategia_respeta_visibilidad_solo_backend_y_modalidades_free_registered_paid(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create(['active' => true]);
        $admin->assignRole('admin');
        $normalUser = User::factory()->create(['active' => true]);

        $item = DownloadItem::create([
            'title'             => 'Plugin YOOtheme Grid',
            'slug'              => 'plugin-yootheme-grid',
            'vault_disk'        => 'downloads_vault',
            'private_file_path' => '2026/11/test.bin',
            'file_size_bytes'   => 2048,
            'access_type'       => DownloadItem::ACCESS_FREE,
            'status'            => DownloadItem::STATUS_PUBLISHED,
            'published_at'      => now()->subMinute(),
        ]);

        $strategy = new StandardDownloadAccessStrategy();
        $request = Request::create('/download-vault/plugin-yootheme-grid/request', 'GET');

        // 1. Por defecto el módulo nace en 'admin_only': deniega anónimos con 404 pero permite al admin
        $this->assertFalse($strategy->evaluate($item, null, $request)->allowed);
        $this->assertSame(404, $strategy->evaluate($item, null, $request)->httpStatus);
        $this->assertTrue($strategy->evaluate($item, $admin, $request)->allowed);

        // 2. Al activar visibilidad pública: permite acceso gratuito a todos
        DownloadSetting::cached()->update(['public_visibility' => DownloadSetting::VISIBILITY_PUBLIC]);
        DownloadSetting::clearCache();

        $this->assertTrue($strategy->evaluate($item, null, $request)->allowed);

        // 3. Modalidad 'registered': exige usuario autenticado
        $item->update(['access_type' => DownloadItem::ACCESS_REGISTERED]);
        $this->assertSame(401, $strategy->evaluate($item, null, $request)->httpStatus);
        $this->assertTrue($strategy->evaluate($item, $normalUser, $request)->allowed);

        // 4. Modalidad 'paid' (preparada para cobros futuros): bloquea con 402 salvo al admin
        $item->update(['access_type' => DownloadItem::ACCESS_PAID, 'price_cents' => 2900]);
        $paidDecision = $strategy->evaluate($item, $normalUser, $request);
        $this->assertFalse($paidDecision->allowed);
        $this->assertSame(402, $paidDecision->httpStatus);
        $this->assertTrue($strategy->evaluate($item, $admin, $request)->allowed);
    }

    #[Test]
    public function configuracion_cacheada_almacena_array_primitivo_evitando_php_incomplete_class(): void
    {
        $setting = DownloadSetting::cached();
        $setting->update(['public_visibility' => DownloadSetting::VISIBILITY_PUBLIC]);
        DownloadSetting::clearCache();

        $resolved = DownloadSetting::cached();
        $this->assertTrue($resolved->isStrictlyPublic());
        $this->assertIsArray(\Illuminate\Support\Facades\Cache::get(DownloadSetting::CACHE_KEY));
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function crearZipTemporal(array $entries): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dl_test_zip_') . '.zip';
        $this->tempFiles[] = $tmp;

        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $tmp;
    }
}
