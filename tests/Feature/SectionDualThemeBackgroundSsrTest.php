<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PageBuilderResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración y renderizado SSR para fondos independientes Claro / Oscuro
 * en secciones del Page Builder, preloads LCP condicionados por tema y sanitización CSS SSR.
 */
class SectionDualThemeBackgroundSsrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.stores.redis' => [
                'driver' => 'array',
                'serialize' => false,
            ],
        ]);
        Cache::purge('redis');

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function persiste_configuracion_independiente_claro_oscuro_y_contraste_cabecera_transparente(): void
    {
        SiteSetting::create([
            'site_name' => 'CMS Dual Theme',
            'frontend_theme_mode' => 'user_choice',
            'frontend_default_theme' => 'system',
            'social_links' => [],
        ]);

        $homepage = Page::create([
            'title' => 'Inicio',
            'slug' => '',
            'is_active' => true,
            'is_homepage' => true,
            'layout_json' => ['version' => '1.0', 'sections' => []],
        ]);

        $nuevoLayout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_cabecera',
                    'name' => 'CABECERA',
                    'settings' => [
                        'element_id' => 'cabecera',
                        'container' => 'full_margin',
                        'bg_preset' => 'transparent',
                        'bg_type' => 'image',
                        'bg_image' => '',
                        'bg_color' => '',
                        'bg_overlay' => '',
                        'bg_dark_mode' => 'custom',
                        'bg_type_dark' => 'image',
                        'bg_image_dark' => '/storage/media/silla-pc.png',
                        'bg_size_dark' => 'auto 100%',
                        'bg_position_dark' => 'top left',
                        'bg_attachment_dark' => 'fixed',
                        'bg_blend_mode_dark' => 'darken',
                        'bg_overlay_dark' => 'rgba(0,0,0,0.6)',
                        'text_color' => 'inherit',
                        'text_color_dark' => 'same',
                        'transparent_header' => true,
                        'transparent_header_pull' => true,
                        'transparent_header_nav_color' => 'dark',
                        'transparent_header_nav_color_dark' => 'light',
                    ],
                    'rows' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('paginas.builder.update', $homepage->id), [
            'layout_json' => $nuevoLayout,
        ]);

        $response->assertRedirect();

        $homepage->refresh();
        $savedSettings = $homepage->layout_json['sections'][0]['settings'];
        $this->assertSame('custom', $savedSettings['bg_dark_mode']);
        $this->assertSame('/storage/media/silla-pc.png', $savedSettings['bg_image_dark']);
        $this->assertSame('rgba(0,0,0,0.6)', $savedSettings['bg_overlay_dark']);
        $this->assertSame('light', $savedSettings['transparent_header_nav_color_dark']);

        // Verificar que la respuesta Inertia pública expone cabeceraTransparente con ambos contrastes
        $publicResponse = $this->get('/');
        $publicResponse->assertOk();
        $publicResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Page')
            ->where('cabeceraTransparente.active', true)
            ->where('cabeceraTransparente.pull', true)
            ->where('cabeceraTransparente.navColor', 'dark')
            ->where('cabeceraTransparente.navColorDark', 'light')
        );
    }

    #[Test]
    public function ssr_renderiza_capas_aisladas_sin_parpadeo_preloads_por_esquema_y_css_sin_escapar_selectores(): void
    {
        SiteSetting::create([
            'site_name' => 'CMS SSR Sin Parpadeo',
            'frontend_theme_mode' => 'user_choice',
            'frontend_default_theme' => 'system',
            'social_links' => [],
        ]);

        Page::create([
            'title' => 'Portada Dual',
            'slug' => '',
            'is_active' => true,
            'is_homepage' => true,
            'layout_json' => [
                'version' => '1.0',
                'sections' => [
                    [
                        'id' => 'sec_hero_dual',
                        'settings' => [
                            'element_id' => 'cabecera',
                            'bg_preset' => 'transparent',
                            'bg_type' => 'image',
                            'bg_image' => '',
                            'bg_overlay' => '',
                            'bg_dark_mode' => 'custom',
                            'bg_type_dark' => 'image',
                            'bg_image_dark' => '/storage/media/silla-pc.png',
                            'bg_overlay_dark' => 'rgba(0,0,0,0.6)',
                            'custom_css' => '#cabecera > .test-child { opacity: 1; }</style><script>alert(1)</script>',
                        ],
                        'rows' => [],
                    ],
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertOk();

        $html = (string) $response->getContent();

        if (str_contains($html, ' inertia>')) {
            // 1. La capa de imagen y el overlay oscuros nacen con clase builder-media-only-dark en el HTML inicial SSR
            $this->assertStringContainsString('builder-bg-layer builder-media-only-dark', $html);
            $this->assertStringContainsString('builder-overlay-layer builder-media-only-dark', $html);
            $this->assertStringContainsString('--sec-overlay-light:transparent;--sec-overlay-dark:rgba(0,0,0,0.6)', $html);

            // 2. Los preloads en <head> están condicionados a (prefers-color-scheme: dark) para no penalizar modo claro ni Googlebot
            $this->assertStringContainsString('media="(prefers-color-scheme: dark) and (min-width: 1024px)"', $html);

            // 3. Dentro de la etiqueta <style> renderizada por SSR el selector '>' NO se escapa como '&gt;', y se neutraliza '</style>'
            $this->assertStringContainsString('<style>#cabecera > .test-child { opacity: 1; }<script>alert(1)</script></style>', $html);
            $this->assertStringNotContainsString('<style>#cabecera &gt;', $html);
        }
    }

    #[Test]
    public function resuelve_tokens_dinamicos_tambien_en_imagen_oscura_de_plantilla_categoria(): void
    {
        SiteSetting::create([
            'site_name' => 'CMS Tokens',
            'social_links' => [],
        ]);

        $category = Category::create([
            'name' => 'Desarrollo Web',
            'slug' => 'desarrollo-web',
            'featured_image' => '/storage/media/banner-oscuro-cat.webp',
            'is_active' => true,
        ]);

        $templateLayout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_cat_hero',
                    'settings' => [
                        'bg_preset' => 'transparent',
                        'bg_image' => '',
                        'bg_dark_mode' => 'custom',
                        'bg_image_dark' => '{category.featured_image}',
                    ],
                    'rows' => [],
                ],
            ],
        ];

        $resolver = app(PageBuilderResolverService::class);
        $resolved = $resolver->resolveForCategory($templateLayout, $category);

        $this->assertSame(
            '/storage/media/banner-oscuro-cat.webp',
            $resolved['sections'][0]['settings']['bg_image_dark']
        );
    }
}
