<?php

namespace Tests\Unit\Services;

use App\Services\PageBuilderResolverService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias para la poda y preservación de ajustes independientes de fondo
 * Modo Claro / Modo Oscuro en secciones del Page Builder.
 */
class PageBuilderSectionDarkThemeCleanTest extends TestCase
{
    #[Test]
    public function poda_propiedades_oscuras_cuando_la_seccion_hereda_el_fondo_claro(): void
    {
        $layout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_1',
                    'name' => 'Sección Heredada',
                    'admin_label' => 'Cabecera',
                    'settings' => [
                        'bg_preset' => 'white',
                        'bg_image' => '/storage/media/claro.webp',
                        'bg_overlay' => 'rgba(255,255,255,0.4)',
                        'bg_dark_mode' => 'inherit',
                        'bg_image_dark' => '/storage/media/antiguo-oscuro.webp',
                        'bg_overlay_dark' => 'rgba(0,0,0,0.7)',
                        'bg_color_dark' => '#09090b',
                        'text_color_dark' => 'light',
                        'custom_css' => '',
                    ],
                    'rows' => [],
                ],
            ],
        ];

        $limpio = PageBuilderResolverService::limpiarLayoutParaPublico($layout);
        $settings = $limpio['sections'][0]['settings'];

        $this->assertSame('/storage/media/claro.webp', $settings['bg_image']);
        $this->assertSame('rgba(255,255,255,0.4)', $settings['bg_overlay']);
        $this->assertArrayNotHasKey('bg_dark_mode', $settings);
        $this->assertArrayNotHasKey('bg_image_dark', $settings);
        $this->assertArrayNotHasKey('bg_overlay_dark', $settings);
        $this->assertArrayNotHasKey('bg_color_dark', $settings);
        $this->assertArrayNotHasKey('text_color_dark', $settings);
        $this->assertArrayNotHasKey('custom_css', $settings);
    }

    #[Test]
    public function preserva_propiedades_oscuras_cuando_bg_dark_mode_es_custom(): void
    {
        $layout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_hero',
                    'settings' => [
                        'element_id' => 'cabecera',
                        'bg_preset' => 'transparent',
                        'bg_image' => '',
                        'bg_overlay' => '',
                        'bg_dark_mode' => 'custom',
                        'bg_type_dark' => 'image',
                        'bg_image_dark' => '/storage/media/hero-dark.webp',
                        'bg_size_dark' => 'auto 100%',
                        'bg_position_dark' => 'top left',
                        'bg_attachment_dark' => 'fixed',
                        'bg_blend_mode_dark' => 'darken',
                        'bg_overlay_dark' => 'rgba(0,0,0,0.6)',
                        'text_color_dark' => 'same',
                    ],
                    'rows' => [],
                ],
            ],
        ];

        $limpio = PageBuilderResolverService::limpiarLayoutParaPublico($layout);
        $settings = $limpio['sections'][0]['settings'];

        $this->assertSame('custom', $settings['bg_dark_mode']);
        $this->assertSame('/storage/media/hero-dark.webp', $settings['bg_image_dark']);
        $this->assertSame('auto 100%', $settings['bg_size_dark']);
        $this->assertSame('fixed', $settings['bg_attachment_dark']);
        $this->assertSame('darken', $settings['bg_blend_mode_dark']);
        $this->assertSame('rgba(0,0,0,0.6)', $settings['bg_overlay_dark']);
        $this->assertSame('cabecera', $settings['element_id']);
    }
}
