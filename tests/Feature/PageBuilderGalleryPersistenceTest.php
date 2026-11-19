<?php

namespace Tests\Feature;

use App\Models\BuilderTemplate;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración para la persistencia del elemento Galería en el Page Builder.
 */
class PageBuilderGalleryPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function guarda_correctamente_elementos_galeria_manual_con_multiples_imagenes_en_pagina(): void
    {
        $page = Page::create([
            'title'       => 'Página con Galería',
            'slug'        => 'pagina-con-galeria',
            'layout_json' => ['version' => '1.0', 'sections' => []],
            'is_active'   => true,
        ]);

        $galeriaItems = [
            [
                'id'      => 'gal_item_1',
                'src'     => 'https://example.com/foto1.jpg',
                'alt'     => 'Primera foto',
                'title'   => 'Foto 1',
                'caption' => 'Pie de foto 1',
                'tags'    => 'naturaleza, paisaje',
                'link'    => '',
                'type'    => 'image',
                'poster'  => '',
            ],
            [
                'id'      => 'gal_item_2',
                'src'     => 'https://example.com/foto2.jpg',
                'alt'     => 'Segunda foto',
                'title'   => 'Foto 2',
                'caption' => 'Pie de foto 2',
                'tags'    => 'arquitectura',
                'link'    => '',
                'type'    => 'image',
                'poster'  => '',
            ],
            [
                'id'      => 'gal_item_3',
                'src'     => 'https://example.com/foto3.jpg',
                'alt'     => 'Tercera foto',
                'title'   => 'Foto 3',
                'caption' => 'Pie de foto 3',
                'tags'    => 'paisaje',
                'link'    => '',
                'type'    => 'image',
                'poster'  => '',
            ],
        ];

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_gal',
                    'rows' => [
                        [
                            'id'      => 'row_gal',
                            'columns' => [
                                [
                                    'id'       => 'col_gal',
                                    'elements' => [
                                        [
                                            'id'      => 'el_gal_1',
                                            'type'    => 'gallery',
                                            'content' => [
                                                'mode'             => 'manual',
                                                'layout'           => 'grid',
                                                'columns_desktop'  => 3,
                                                'columns_tablet'   => 'auto',
                                                'columns_mobile'   => '1',
                                                'gap'              => 'md',
                                                'image_ratio'      => '16:9',
                                                'image_fit'        => 'cover',
                                                'rounded'          => 'lg',
                                                'hover_effect'     => 'zoom',
                                                'overlay_mode'     => 'none',
                                                'show_caption'     => false,
                                                'caption_position' => 'below',
                                                'enable_lightbox'  => true,
                                                'filter_tags'      => false,
                                                'items'            => $galeriaItems,
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

        $response = $this->actingAs($this->admin)->put(route('paginas.builder.update', $page->id), [
            'layout_json' => $layout,
        ]);

        $response->assertStatus(302);
        $page->refresh();

        $savedContent = $page->layout_json['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content'];
        $this->assertEquals('manual', $savedContent['mode']);
        $this->assertCount(3, $savedContent['items']);
        $this->assertEquals('https://example.com/foto1.jpg', $savedContent['items'][0]['src']);
        $this->assertEquals('https://example.com/foto2.jpg', $savedContent['items'][1]['src']);
        $this->assertEquals('https://example.com/foto3.jpg', $savedContent['items'][2]['src']);
        $this->assertEquals('Primera foto', $savedContent['items'][0]['alt']);
    }

    #[Test]
    public function normaliza_correctamente_galerias_con_content_vacio_al_guardar_pagina(): void
    {
        $page = Page::create([
            'title'       => 'Página Vacía',
            'slug'        => 'pagina-vacia',
            'layout_json' => ['version' => '1.0', 'sections' => []],
            'is_active'   => true,
        ]);

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_1',
                    'rows' => [
                        [
                            'id'      => 'row_1',
                            'columns' => [
                                [
                                    'id'       => 'col_1',
                                    'elements' => [
                                        [
                                            'id'      => 'el_gal_vacia',
                                            'type'    => 'gallery',
                                            'content' => [], // Enviado como array vacío
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('paginas.builder.update', $page->id), [
            'layout_json' => $layout,
        ]);

        $response->assertStatus(302);
        $page->refresh();

        $savedContent = $page->layout_json['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content'];
        $this->assertIsArray($savedContent);
        $this->assertEquals('manual', $savedContent['mode']);
        $this->assertIsArray($savedContent['items']);
        $this->assertEmpty($savedContent['items']);
    }

    #[Test]
    public function guarda_correctamente_galeria_en_plantilla_de_articulos(): void
    {
        $template = BuilderTemplate::create([
            'name'        => 'Plantilla con Galería',
            'type'        => 'single_article',
            'layout_json' => ['version' => '1.0', 'sections' => []],
        ]);

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_tpl',
                    'rows' => [
                        [
                            'id'      => 'row_tpl',
                            'columns' => [
                                [
                                    'id'       => 'col_tpl',
                                    'elements' => [
                                        [
                                            'id'      => 'el_gal_tpl',
                                            'type'    => 'gallery',
                                            'content' => [
                                                'mode'  => 'manual',
                                                'items' => [
                                                    ['id' => '1', 'src' => 'https://example.com/p1.webp'],
                                                    ['id' => '2', 'src' => 'https://example.com/p2.webp'],
                                                ],
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

        $response = $this->actingAs($this->admin)->put(route('plantillas.builder.update', $template->id), [
            'layout_json' => $layout,
        ]);

        $response->assertStatus(302);
        $template->refresh();

        $savedContent = $template->layout_json['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content'];
        $this->assertEquals('manual', $savedContent['mode']);
        $this->assertCount(2, $savedContent['items']);
        $this->assertEquals('https://example.com/p1.webp', $savedContent['items'][0]['src']);
    }

    #[Test]
    public function guarda_correctamente_galeria_con_ajustes_globales_de_tamano_distribucion_y_filtros_individuales(): void
    {
        $page = Page::create([
            'title'       => 'Página con Logos y Filtros',
            'slug'        => 'pagina-logos-filtros',
            'layout_json' => ['version' => '1.0', 'sections' => []],
            'is_active'   => true,
        ]);

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_logos',
                    'rows' => [
                        [
                            'id'      => 'row_logos',
                            'columns' => [
                                [
                                    'id'       => 'col_logos',
                                    'elements' => [
                                        [
                                            'id'      => 'el_gal_logos',
                                            'type'    => 'gallery',
                                            'content' => [
                                                'mode'            => 'manual',
                                                'image_fit'       => 'contain',
                                                'max_height_px'   => 60,
                                                'max_width_px'    => 180,
                                                'center_columns'  => true,
                                                'center_rows'     => true,
                                                'show_dividers'   => true,
                                                'is_white'        => true,
                                                'color_filter'    => 'white',
                                                'items'           => [
                                                    [
                                                        'id'           => 'logo_1',
                                                        'src'          => 'https://example.com/logo1.svg',
                                                        'is_white'     => true,
                                                        'color_filter' => 'white',
                                                    ],
                                                    [
                                                        'id'           => 'logo_2',
                                                        'src'          => 'https://example.com/logo2.png',
                                                        'is_white'     => false,
                                                        'color_filter' => 'none', // Sobrescritura individual para mantener color original
                                                    ],
                                                ],
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

        $response = $this->actingAs($this->admin)->put(route('paginas.builder.update', $page->id), [
            'layout_json' => $layout,
        ]);

        $response->assertStatus(302);
        $page->refresh();

        $savedContent = $page->layout_json['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content'];
        $this->assertEquals('contain', $savedContent['image_fit']);
        $this->assertEquals(60, $savedContent['max_height_px']);
        $this->assertEquals(180, $savedContent['max_width_px']);
        $this->assertTrue($savedContent['center_columns']);
        $this->assertTrue($savedContent['center_rows']);
        $this->assertTrue($savedContent['show_dividers']);
        $this->assertTrue($savedContent['is_white']);
        $this->assertEquals('white', $savedContent['color_filter']);

        $this->assertCount(2, $savedContent['items']);
        $this->assertTrue($savedContent['items'][0]['is_white']);
        $this->assertFalse($savedContent['items'][1]['is_white']);
        $this->assertEquals('none', $savedContent['items'][1]['color_filter']);
    }

    #[Test]
    public function guarda_correctamente_elementos_galeria_con_layout_carrusel_infinito(): void
    {
        $page = Page::create([
            'title'       => 'Página con Carrusel Infinito',
            'slug'        => 'pagina-con-carrusel-infinito',
            'layout_json' => ['version' => '1.0', 'sections' => []],
            'is_active'   => true,
        ]);

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_carrusel',
                    'rows' => [
                        [
                            'id'      => 'row_carrusel',
                            'columns' => [
                                [
                                    'id'       => 'col_carrusel',
                                    'elements' => [
                                        [
                                            'id'      => 'el_carrusel_1',
                                            'type'    => 'gallery',
                                            'content' => [
                                                'mode'                     => 'manual',
                                                'layout'                   => 'carousel_infinite',
                                                'carousel_rows'            => 2,
                                                'carousel_speed'           => 45,
                                                'carousel_direction'       => 'left',
                                                'carousel_pause_on_hover'  => true,
                                                'carousel_fade_edges'      => true,
                                                'carousel_card_style'      => 'badge',
                                                'carousel_item_size'       => 'lg',
                                                'items'                    => [
                                                    ['id' => '1', 'src' => 'https://example.com/logo1.svg'],
                                                    ['id' => '2', 'src' => 'https://example.com/logo2.svg'],
                                                ],
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

        $response = $this->actingAs($this->admin)->put(route('paginas.builder.update', $page->id), [
            'layout_json' => $layout,
        ]);

        $response->assertStatus(302);
        $page->refresh();

        $savedContent = $page->layout_json['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content'];
        $this->assertEquals('carousel_infinite', $savedContent['layout']);
        $this->assertEquals(2, $savedContent['carousel_rows']);
        $this->assertEquals(45, $savedContent['carousel_speed']);
        $this->assertEquals('left', $savedContent['carousel_direction']);
        $this->assertTrue($savedContent['carousel_pause_on_hover']);
        $this->assertTrue($savedContent['carousel_fade_edges']);
        $this->assertEquals('badge', $savedContent['carousel_card_style']);
        $this->assertEquals('lg', $savedContent['carousel_item_size']);
        $this->assertCount(2, $savedContent['items']);
    }
}

