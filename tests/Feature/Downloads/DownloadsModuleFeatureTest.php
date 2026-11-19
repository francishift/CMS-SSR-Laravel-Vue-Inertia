<?php

declare(strict_types=1);

namespace Tests\Feature\Downloads;

use App\Models\Menu;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Services\SeoService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

/**
 * Pruebas de integración extremo a extremo del Módulo Autónomo de Descargas:
 * Visibilidad solo backend por defecto, CRUD admin, reordenación Drag & Drop,
 * seguridad de URLs firmadas, integración con Menús y SEO Schema.org + Sitemap.
 */
class DownloadsModuleFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        $vaultDisk = (string) config('downloads.vault.disk', 'downloads_vault');
        Storage::fake($vaultDisk);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['active' => true]);
        $this->admin->assignRole('admin');

        SiteSetting::create([
            'id'            => 1,
            'site_name'     => 'Portal Test YOOtheme',
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
    public function por_defecto_tiene_visibilidad_solo_en_backend_ocultando_al_publico_y_permitiendo_admin(): void
    {
        $zipFile = $this->crearUploadedZip('elemento-accordion.zip');

        // El admin sube y publica la ficha en el panel
        $this->actingAs($this->admin)->post(route('downloads.admin.store'), [
            'title'             => 'Elemento Accordion Pro',
            'short_description' => 'Acordeón avanzado para YOOtheme Pro',
            'body'              => '<p>Instrucciones</p><script>alert(1)</script>',
            'version'           => '1.1.0',
            'compatibility'     => 'YOOtheme Pro 4.5+',
            'access_type'       => 'free',
            'status'            => 'published',
            'package_file'      => $zipFile,
        ])->assertRedirect(route('downloads.admin.index'));

        $item = DownloadItem::firstOrFail();

        // Sanitización XSS verificada en base de datos
        $this->assertStringNotContainsString('<script>', (string) $item->body);

        // Cerrar sesión administrativa para simular a un visitante público anónimo real
        auth()->guard('web')->logout();

        // Visitante anónimo recibe 404 en el catálogo, en la ficha y en el endpoint de descarga
        $this->get('/descargas')->assertNotFound();
        $this->get("/descargas/{$item->slug}")->assertNotFound();
        $this->get("/download-vault/{$item->slug}/request")->assertNotFound();

        // El administrador autenticado SÍ puede acceder al panel, previsualizar y descargar el binario firmado
        $this->actingAs($this->admin)->get(route('downloads.admin.index'))->assertOk();
        $this->actingAs($this->admin)->get('/descargas')->assertOk();
        $this->actingAs($this->admin)->get("/descargas/{$item->slug}")->assertOk();

        $redirect = $this->actingAs($this->admin)->get("/download-vault/{$item->slug}/request");
        $redirect->assertRedirect();
        $signedUrl = (string) $redirect->headers->get('Location');

        $stream = $this->actingAs($this->admin)->get($signedUrl);
        $stream->assertOk();
        $stream->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(1, $item->fresh()->downloads_count);
    }

    #[Test]
    public function al_activar_visibilidad_publica_sirve_catalogo_ficha_schema_jsonld_y_bloquea_firmas_manipuladas(): void
    {
        $zipFile = $this->crearUploadedZip('fv-slider-yootheme.zip');

        $this->actingAs($this->admin)->post(route('downloads.admin.store'), [
            'title'             => 'FV Slider YOOtheme',
            'short_description' => 'Slider ligero para YOOtheme Pro',
            'version'           => '2.0.1',
            'compatibility'     => 'YOOtheme Pro 4.x',
            'access_type'       => 'free',
            'status'            => 'published',
            'package_file'      => $zipFile,
        ]);

        $item = DownloadItem::firstOrFail();

        // Cambiar configuración: título, descripción, icono, slug personalizado y visibilidad pública
        $this->actingAs($this->admin)->put(route('downloads.admin.settings.update'), [
            'page_title'                  => 'Plugins YOOtheme Pro',
            'slug'                        => 'plugins-yootheme',
            'description'                 => 'Colección oficial de elementos para YOOtheme.',
            'icon'                        => 'pi pi-box',
            'public_visibility'           => 'public',
            'default_view_mode'           => 'grid',
            'allow_view_toggle'           => true,
            'cols_desktop'                => 3,
            'cols_tablet'                 => 2,
            'cols_mobile'                 => 1,
            'gap_size'                    => 'md',
            'header_align'                => 'center',
            'per_page'                    => 12,
            'show_quick_download'         => true,
            'show_downloads_count_public' => false,
        ])->assertSessionHasNoErrors();

        // Cerrar sesión administrativa para comprobar acceso de un visitante anónimo real
        auth()->guard('web')->logout();

        // Ahora el visitante anónimo accede al nuevo slug dinámico `/plugins-yootheme` y su ficha
        $this->get('/plugins-yootheme')->assertOk();
        $this->get("/plugins-yootheme/{$item->slug}")->assertOk();

        // Verificar que SeoService generó el nodo Schema.org `SoftwareApplication` con `Offer` 0.00 EUR
        $jsonLdHtml = app(SeoService::class)->renderSchemaJsonLd();
        $this->assertStringContainsString('"SoftwareApplication"', $jsonLdHtml);
        $this->assertStringContainsString('"softwareVersion":"2.0.1"', $jsonLdHtml);
        $this->assertStringContainsString('"price":"0.00"', $jsonLdHtml);

        // Descarga pública mediante URL firmada efímera
        $reqResponse = $this->get("/download-vault/{$item->slug}/request");
        $reqResponse->assertRedirect();
        $validSignedUrl = (string) $reqResponse->headers->get('Location');

        // Si alguien manipula la firma criptográfica, recibe 403 Forbidden
        $tamperedUrl = $validSignedUrl . 'tampered';
        $this->get($tamperedUrl)->assertForbidden();

        // Con la firma intacta se transmite el archivo y se registra auditoría en `download_logs`
        $this->get($validSignedUrl)->assertOk();
        $this->assertSame(1, $item->fresh()->downloads_count);
        $this->assertDatabaseHas('download_logs', [
            'download_item_id'   => $item->id,
            'version_downloaded' => '2.0.1',
        ]);
    }

    #[Test]
    public function permite_reordenar_drag_and_drop_papelera_y_purgado_de_boveda_en_borrado_definitivo(): void
    {
        $vaultDisk = (string) config('downloads.vault.disk', 'downloads_vault');

        $this->actingAs($this->admin)->post(route('downloads.admin.store'), [
            'title'        => 'Elemento A',
            'access_type'  => 'free',
            'status'       => 'published',
            'package_file' => $this->crearUploadedZip('a.zip'),
        ]);

        $this->actingAs($this->admin)->post(route('downloads.admin.store'), [
            'title'        => 'Elemento B',
            'access_type'  => 'free',
            'status'       => 'published',
            'package_file' => $this->crearUploadedZip('b.zip'),
        ]);

        $itemA = DownloadItem::where('title', 'Elemento A')->firstOrFail();
        $itemB = DownloadItem::where('title', 'Elemento B')->firstOrFail();

        // Reordenar invirtiendo B primero y A después
        $this->actingAs($this->admin)->post(route('downloads.admin.reorder'), [
            'ids'      => [$itemB->id, $itemA->id],
            'page'     => 1,
            'per_page' => 20,
        ])->assertRedirect();

        $this->assertSame(0, $itemB->fresh()->order);
        $this->assertSame(1, $itemA->fresh()->order);

        // Verificar que `DownloadItemPublicResource` expone `order` para la ordenación reactiva en `ElementGridArticles`
        $resolvedPublicB = (new \App\Modules\Downloads\Http\Resources\DownloadItemPublicResource($itemB->fresh()))->resolve();
        $resolvedPublicA = (new \App\Modules\Downloads\Http\Resources\DownloadItemPublicResource($itemA->fresh()))->resolve();
        $this->assertSame(0, $resolvedPublicB['order']);
        $this->assertSame(1, $resolvedPublicA['order']);

        // Enviar a papelera conserva el archivo privado
        $privatePathA = (string) $itemA->private_file_path;
        $this->actingAs($this->admin)->delete(route('downloads.admin.destroy', $itemA->id))->assertRedirect();
        $this->assertSoftDeleted('download_items', ['id' => $itemA->id]);
        Storage::disk($vaultDisk)->assertExists($privatePathA);

        // Borrado definitivo elimina el registro y purga físicamente el archivo de la bóveda
        $this->actingAs($this->admin)->delete(route('downloads.admin.force-delete', $itemA->id))->assertRedirect();
        $this->assertDatabaseMissing('download_items', ['id' => $itemA->id]);
        Storage::disk($vaultDisk)->assertMissing($privatePathA);
    }

    #[Test]
    public function integra_elemento_de_menu_descargas_y_valida_colisiones_de_slug(): void
    {
        Page::create([
            'title'     => 'Contacto',
            'slug'      => 'contacto',
            'is_active' => true,
        ]);

        // Intentar asignar al catálogo el slug de una página existente o ruta reservada falla validación
        $this->actingAs($this->admin)->put(route('downloads.admin.settings.update'), [
            'page_title'        => 'Descargas',
            'slug'              => 'contacto',
            'public_visibility' => 'admin_only',
            'default_view_mode' => 'grid',
            'cols_desktop'      => 3,
            'cols_tablet'       => 2,
            'cols_mobile'       => 1,
            'gap_size'          => 'md',
            'header_align'      => 'left',
            'per_page'          => 12,
        ])->assertSessionHasErrors('slug');

        // Crear un elemento de menú apuntando al módulo de Descargas
        $menu = Menu::create([
            'name'      => 'Menú Cabecera',
            'slug'      => 'menu-cabecera',
            'location'  => 'header',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(route('menus.items.store', $menu->id), [
            'title'      => 'Descargas',
            'route_name' => 'public.downloads.index',
            'icon'       => 'pi pi-download',
            'target'     => '_self',
            'is_active'  => true,
        ])->assertRedirect();

        $menuItem = $menu->items()->firstOrFail();
        $this->assertSame(url('/descargas'), $menuItem->resolved_url);

        // Mientras el módulo está en 'admin_only', el item es invisible para un visitante anónimo, pero visible para el admin
        $this->assertFalse($menuItem->isPubliclyVisibleFor(null));
        $this->assertTrue($menuItem->isPubliclyVisibleFor($this->admin));
    }

    #[Test]
    public function permite_configurar_tamanos_granulares_de_items_y_maquetar_catalogo_con_page_builder(): void
    {
        $this->actingAs($this->admin)->post(route('downloads.admin.store'), [
            'title'             => 'FV Hero Slider',
            'subtitle'          => 'Cabecera con Fondo Fijo y Slider de Textos SEO',
            'short_description' => 'Hero slider nativo para YOOtheme Pro',
            'version'           => '1.0.0',
            'compatibility'     => 'YOOtheme Pro 4.5+',
            'access_type'       => 'free',
            'status'            => 'published',
            'package_file'      => $this->crearUploadedZip('fv-hero-slider.zip'),
        ]);

        $createdItem = DownloadItem::where('title', 'FV Hero Slider')->firstOrFail();
        $this->assertSame('Cabecera con Fondo Fijo y Slider de Textos SEO', $createdItem->subtitle);

        // 1. Actualizar controles granulares de cabecera y tamaños de cada tarjeta
        $this->actingAs($this->admin)->put(route('downloads.admin.settings.update'), [
            'page_title'            => 'Extensiones YOOtheme',
            'slug'                  => 'descargas',
            'description'           => 'Plugins optimizados para YOOtheme Pro.',
            'icon'                  => 'pi pi-download',
            'public_visibility'     => 'public',
            'use_builder_layout'    => false,
            'container_width'       => 'expand',
            'show_breadcrumb'       => true,
            'show_header_icon'      => true,
            'show_description'      => true,
            'header_title_size'     => 'hero_xl',
            'header_desc_size'      => 'lg',
            'default_view_mode'     => 'grid',
            'allow_view_toggle'     => true,
            'cols_desktop'          => 3,
            'cols_tablet'           => 2,
            'cols_mobile'           => 1,
            'gap_size'              => 'lg',
            'header_align'          => 'center',
            'per_page'              => 12,
            'card_style'            => 'bordered',
            'card_text_align'       => 'center',
            'item_title_tag'        => 'h3',
            'item_title_size'       => 'h4',
            'item_desc_size'        => 'sm',
            'item_desc_lines'       => '3',
            'show_item_image'       => true,
            'show_item_description' => true,
            'show_item_badges'      => true,
            'show_item_meta'        => true,
            'item_image_ratio'      => '16:9',
            'item_svg_size'         => 'lg',
            'item_image_position'   => 'flush',
            'item_button_style'     => 'button_primary',
            'item_button_size'      => 'md',
            'show_quick_download'   => true,
        ])->assertSessionHasNoErrors();

        $settings = DownloadSetting::cached();
        $this->assertSame('expand', $settings->container_width);
        $this->assertSame('hero_xl', $settings->header_title_size);
        $this->assertSame('h4', $settings->item_title_size);
        $this->assertSame('lg', $settings->item_svg_size);

        // 2. Acceder al Dashboard principal (/panel) y verificar métricas del widget de Descargas
        $this->actingAs($this->admin)->get('/panel')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('estadisticasDescargas.disponible', true)
                ->where('estadisticasDescargas.published_items', 1)
                ->where('estadisticasDescargas.visibilidad', 'public')
            );

        // 3. Acceder al Maquetador Visual de Descargas y su marco de vista previa
        $this->actingAs($this->admin)->get(route('downloads.admin.builder'))->assertOk();
        $this->actingAs($this->admin)->get(route('downloads.admin.builder.preview-frame'))->assertOk();

        // 4. Guardar un layout personalizado del Page Builder con origen dinámico `downloads`
        $builderLayout = [
            'sections' => [
                [
                    'id'      => 'sec_dl_test',
                    'type'    => 'section',
                    'padding' => 'py-12',
                    'rows'    => [
                        [
                            'id'      => 'row_dl_test',
                            'columns' => [
                                [
                                    'id'       => 'col_dl_test',
                                    'width'    => 'w-full',
                                    'elements' => [
                                        [
                                            'id'              => 'el_grid_dl_test',
                                            'type'            => 'grid_articles',
                                            'dynamic_binding' => [
                                                'enabled' => true,
                                                'source'  => 'downloads',
                                                'limit'   => 12,
                                                'sort_by' => 'order',
                                                'mapping' => [
                                                    'title'       => 'title',
                                                    'excerpt'     => 'short_description',
                                                    'meta'        => 'version',
                                                    'category'    => 'compatibility',
                                                    'button_text' => 'Ver ficha',
                                                ],
                                            ],
                                            'content'         => [
                                                'cols_desktop' => 3,
                                                'card_style'   => 'bordered',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($this->admin)->put(route('downloads.admin.builder.update'), [
            'layout_json' => $builderLayout,
        ])->assertSessionHasNoErrors();

        $updatedSettings = DownloadSetting::cached();
        $this->assertTrue($updatedSettings->use_builder_layout);

        // 4b. Verificar Plantilla Visual de Ficha Individual (`downloads.admin.item-builder`) y resolución `{download.*}`
        $this->actingAs($this->admin)->get(route('downloads.admin.item-builder', ['preview_download_id' => $createdItem->id]))->assertOk();
        $this->actingAs($this->admin)->get(route('downloads.admin.item-builder.preview-frame', ['preview_download_id' => $createdItem->id]))->assertOk();

        $itemTemplateLayout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_item_tpl',
                    'rows' => [
                        [
                            'id'      => 'row_item_tpl',
                            'layout'  => '1',
                            'columns' => [
                                [
                                    'id'       => 'col_item_tpl',
                                    'width'    => ['xs' => '1/1', 'lg' => '1/1'],
                                    'elements' => [
                                        [
                                            'id'      => 'el_item_h1',
                                            'type'    => 'heading',
                                            'content' => [
                                                'text'     => 'Plugin Oficial: {download.title}',
                                                'subtitle' => '{download.subtitle}',
                                                'tag'      => 'h1',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($this->admin)->put(route('downloads.admin.item-builder.update'), [
            'layout_json' => $itemTemplateLayout,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(DownloadSetting::cached()->use_item_builder_layout);

        // 5. Visitante público recibe tanto el catálogo como la ficha individual renderizados con sus layouts de Builder resueltos
        auth()->guard('web')->logout();
        $response = $this->get('/descargas');
        $response->assertOk();
        $response->assertSee('FV Hero Slider');

        $itemResponse = $this->get("/descargas/{$createdItem->slug}");
        $itemResponse->assertOk();
        $itemResponse->assertSee('Plugin Oficial: FV Hero Slider');
        $itemResponse->assertSee('Cabecera con Fondo Fijo y Slider de Textos SEO');
    }

    #[Test]
    public function aplica_proteccion_antihotlinking_firma_ligada_a_ip_preserva_updated_at_y_purga_logs_antiguos(): void
    {
        $zipFile = $this->crearUploadedZip('fv-security-pack.zip');

        $this->actingAs($this->admin)->post(route('downloads.admin.store'), [
            'title'             => 'FV Security Pack',
            'short_description' => 'Paquete de prueba de blindaje',
            'version'           => '1.5.0',
            'compatibility'     => 'Joomla 5.x / YOOtheme Pro 5.x',
            'license_type'      => 'GPL-2.0-or-later',
            'changelog'         => "v1.5.0\n- Blindaje criptográfico activo.",
            'access_type'       => 'free',
            'status'            => 'published',
            'package_file'      => $zipFile,
        ])->assertRedirect(route('downloads.admin.index'));

        $this->actingAs($this->admin)->put(route('downloads.admin.settings.update'), [
            'page_title'                  => 'Descargas Oficiales',
            'slug'                        => 'descargas',
            'description'                 => 'Repositorio oficial.',
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

        auth()->guard('web')->logout();

        $item = DownloadItem::firstOrFail();
        $frozenDate = now()->subDays(10)->startOfSecond();
        \Illuminate\Support\Facades\DB::table($item->getTable())
            ->where('id', $item->id)
            ->update(['updated_at' => $frozenDate]);

        // 1. Verificar enriquecimiento Schema.org SoftwareApplication (author, license canónica GPL, softwareRequirements, releaseNotes)
        $this->get("/descargas/{$item->slug}")->assertOk();
        $jsonLd = app(SeoService::class)->renderSchemaJsonLd();
        $this->assertStringContainsString('"license":"https://www.gnu.org/licenses/old-licenses/gpl-2.0.html"', $jsonLd);
        $this->assertStringContainsString('"softwareRequirements":"Joomla 5.x / YOOtheme Pro 5.x"', $jsonLd);
        $this->assertStringContainsString('Blindaje criptogr', $jsonLd);

        // 2. Anti-hotlinking: petición desde dominio externo redirige a la ficha SEO en vez de entregar URL firmada
        $hotlinkRes = $this->withHeaders([
            'Referer'        => 'https://foro-pirata-externo.example/hilo-descarga',
            'Sec-Fetch-Site' => 'cross-site',
        ])->get("/download-vault/{$item->slug}/request");

        $hotlinkRes->assertRedirect(DownloadSetting::cached()->publicItemUrl($item->slug));
        $this->flushHeaders();

        // 3. Petición legítima desde una IP genera firma ligada a esa IP; otra IP distinta recibe 403 Forbidden
        $legitReq = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->get("/download-vault/{$item->slug}/request");
        $legitReq->assertRedirect();
        $signedUrl = (string) $legitReq->headers->get('Location');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->get($signedUrl)
            ->assertForbidden();

        // 4. La misma IP legítima descarga correctamente y NO altera `updated_at`
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->get($signedUrl)
            ->assertOk();

        $freshItem = $item->fresh();
        $this->assertSame(1, $freshItem->downloads_count);
        $this->assertSame($frozenDate->timestamp, $freshItem->updated_at->timestamp);

        // 5. Purgado masivo `model:prune` elimina logs antiguos conservando los recientes y `downloads_count`
        \App\Modules\Downloads\Models\DownloadLog::create([
            'download_item_id'     => $item->id,
            'ip_hash'              => str_repeat('a', 64),
            'version_downloaded'   => '1.0.0',
            'access_type_snapshot' => 'free',
            'created_at'           => now()->subDays(200),
        ]);
        $this->assertSame(2, \App\Modules\Downloads\Models\DownloadLog::count());

        $this->artisan('model:prune', [
            '--model' => [\App\Modules\Downloads\Models\DownloadLog::class],
        ])->assertSuccessful();

        $this->assertSame(1, \App\Modules\Downloads\Models\DownloadLog::count());
        $this->assertSame(1, $item->fresh()->downloads_count);
    }

    private function crearUploadedZip(string $clientName): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dl_feat_zip_') . '.zip';
        $this->tempFiles[] = $tmp;

        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('plugin/manifest.json', json_encode(['package' => $clientName]));
        $zip->close();

        return new UploadedFile($tmp, $clientName, 'application/zip', null, true);
    }
}
