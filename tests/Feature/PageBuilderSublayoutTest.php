<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PageBuilderResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para la resolución de elementos y sublayouts anidados en el Page Builder.
 */
class PageBuilderSublayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function resuelve_correctamente_datos_dinamicos_anidados_dentro_de_un_sublayout(): void
    {
        $admin = User::factory()->create();
        $categoria = Category::create([
            'name' => 'Proyectos Test',
            'slug' => 'proyectos-test',
        ]);

        Article::create([
            'title'        => 'Artículo en Sublayout',
            'slug'         => 'articulo-en-sublayout',
            'category_id'  => $categoria->id,
            'author_id'    => $admin->id,
            'status'       => 'published',
            'published_at' => now(),
        ]);

        $layout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_1',
                    'rows' => [
                        [
                            'id' => 'row_1',
                            'columns' => [
                                [
                                    'id' => 'col_1',
                                    'elements' => [
                                        [
                                            'id' => 'el_sub_1',
                                            'type' => 'sublayout',
                                            'content' => [
                                                'layout' => '1/2+1/2',
                                                'rows' => [
                                                    [
                                                        'id' => 'subrow_1',
                                                        'columns' => [
                                                            [
                                                                'id' => 'subcol_1',
                                                                'elements' => [
                                                                    [
                                                                        'id' => 'el_icon_1',
                                                                        'type' => 'icon',
                                                                        'content' => ['icon' => 'pi pi-phone'],
                                                                    ]
                                                                ]
                                                            ],
                                                            [
                                                                'id' => 'subcol_2',
                                                                'elements' => [
                                                                    [
                                                                        'id' => 'el_grid_1',
                                                                        'type' => 'grid_articles',
                                                                        'content' => [
                                                                            'dynamic_binding' => [
                                                                                'source' => 'articles',
                                                                                'category_id' => $categoria->id,
                                                                                'limit' => 2,
                                                                            ]
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $resolver = new PageBuilderResolverService();
        $resolved = $resolver->resolve($layout);

        $this->assertNotNull($resolved);
        $sublayout = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'][0];
        $this->assertEquals('sublayout', $sublayout['type']);

        $subcol1Elem = $sublayout['content']['rows'][0]['columns'][0]['elements'][0];
        $this->assertEquals('icon', $subcol1Elem['type']);
        $this->assertEquals('pi pi-phone', $subcol1Elem['content']['icon']);

        $subcol2Elem = $sublayout['content']['rows'][0]['columns'][1]['elements'][0];
        $this->assertEquals('grid_articles', $subcol2Elem['type']);
        $this->assertNotEmpty($subcol2Elem['resolved_data']);
        $this->assertEquals('Artículo en Sublayout', $subcol2Elem['resolved_data'][0]['title']);
    }

    #[Test]
    public function resuelve_video_dinamico_desde_campos_extra_del_articulo_en_raiz_y_en_sublayout(): void
    {
        $admin = User::factory()->create();
        $categoria = Category::create([
            'name' => 'Categoría Maquinaria',
            'slug' => 'categoria-maquinaria',
            'extra_fields_schema' => [
                ['key' => 'video_demo', 'label' => 'Vídeo Demostrativo', 'type' => 'video'],
                ['key' => 'poster_demo', 'label' => 'Portada Vídeo', 'type' => 'image'],
            ],
        ]);

        $articulo = Article::create([
            'title'             => 'Plataforma Articulada 20m',
            'slug'              => 'plataforma-articulada-20m',
            'category_id'       => $categoria->id,
            'author_id'         => $admin->id,
            'status'            => 'published',
            'published_at'      => now(),
            'extra_fields_data' => [
                'video_demo'  => 'https://ejemplo.com/videos/plataforma.mp4',
                'poster_demo' => 'https://ejemplo.com/imagenes/poster.webp',
            ],
        ]);

        $layout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_1',
                    'rows' => [
                        [
                            'id' => 'row_1',
                            'columns' => [
                                [
                                    'id' => 'col_1',
                                    'elements' => [
                                        // Elemento vídeo directo en raíz
                                        [
                                            'id' => 'elem_video_root',
                                            'type' => 'video',
                                            'content' => [
                                                'src' => '{article.extra_fields.video_demo}',
                                                'poster' => '{article.extra_fields.poster_demo}',
                                            ],
                                        ],
                                        // Elemento vídeo anidado en sublayout
                                        [
                                            'id' => 'elem_sublayout',
                                            'type' => 'sublayout',
                                            'content' => [
                                                'layout' => '1',
                                                'rows' => [
                                                    [
                                                        'id' => 'subrow_1',
                                                        'columns' => [
                                                            [
                                                                'id' => 'subcol_1',
                                                                'elements' => [
                                                                    [
                                                                        'id' => 'elem_video_sub',
                                                                        'type' => 'video',
                                                                        'content' => [
                                                                            'src' => '{extra.video_demo}',
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
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $resolver = new PageBuilderResolverService();
        $resolved = $resolver->resolveForArticle($layout, $articulo);

        $this->assertNotNull($resolved);
        $videoRoot = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'][0];
        $this->assertEquals('https://ejemplo.com/videos/plataforma.mp4', $videoRoot['content']['src']);
        $this->assertEquals('https://ejemplo.com/imagenes/poster.webp', $videoRoot['content']['poster']);

        $sublayout = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'][1];
        $videoSub = $sublayout['content']['rows'][0]['columns'][0]['elements'][0];
        $this->assertEquals('https://ejemplo.com/videos/plataforma.mp4', $videoSub['content']['src']);
    }
}
