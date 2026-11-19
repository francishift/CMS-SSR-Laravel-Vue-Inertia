<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use App\Services\PageBuilderResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests de integración para la paginación numérica del Page Builder.
 */
class PageBuilderPaginationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function resuelve_correctamente_paginacion_para_categoria_con_rutas_completas(): void
    {
        $admin = User::factory()->create();
        $categoria = Category::create([
            'name'     => 'Proyectos Test',
            'slug'     => 'proyectos-test',
            'per_page' => 2,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Article::create([
                'title'        => "Proyecto $i",
                'slug'         => "proyecto-$i",
                'category_id'  => $categoria->id,
                'author_id'    => $admin->id,
                'status'       => 'published',
                'published_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $layout = [
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
                                            'id' => 'grid_1',
                                            'type' => 'grid_articles',
                                            'content' => [
                                                'mode' => 'dynamic',
                                                'dynamic_binding' => [
                                                    'source' => 'current_category',
                                                    'category_id' => 'current',
                                                ],
                                            ],
                                        ],
                                        [
                                            'id' => 'pag_1',
                                            'type' => 'pagination',
                                            'content' => [
                                                'mode' => 'archive_numeric',
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

        $resolver = app(PageBuilderResolverService::class);
        $resolved = $resolver->resolveForCategory($layout, $categoria);

        $elements = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'];
        $grid = $elements[0];
        $pagination = $elements[1];

        $this->assertArrayHasKey('resolved_data', $grid);
        $this->assertNotEmpty($grid['resolved_data']);
        $this->assertCount(2, $grid['resolved_data']);

        $this->assertNotEmpty($pagination['resolved_data']['paginacion']);
        $pagData = $pagination['resolved_data']['paginacion'];

        $this->assertEquals(1, $pagData['current_page']);
        $this->assertEquals(3, $pagData['last_page']);
        $this->assertEquals(5, $pagData['total']);

        // Comprobar que la URL contiene la ruta amigable SEO limpia /pagina/2
        $page2Link = collect($pagData['links'])->firstWhere('label', '2');
        $this->assertNotNull($page2Link);
        $this->assertStringContainsString('proyectos-test/pagina/2', $page2Link['url']);
        $this->assertStringNotContainsString('page=2', $page2Link['url']);
    }

    #[Test]
    public function resuelve_correctamente_paginacion_general_en_paginas_estaticas(): void
    {
        $admin = User::factory()->create();
        $categoria = Category::create([
            'name'     => 'Noticias',
            'slug'     => 'noticias',
            'per_page' => 12,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Article::create([
                'title'        => "Noticia $i",
                'slug'         => "noticia-$i",
                'category_id'  => $categoria->id,
                'author_id'    => $admin->id,
                'status'       => 'published',
                'published_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $layout = [
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
                                            'id' => 'grid_1',
                                            'type' => 'grid_articles',
                                            'content' => [
                                                'mode' => 'dynamic',
                                                'dynamic_binding' => [
                                                    'category_id' => $categoria->id,
                                                    'limit'       => 2,
                                                ],
                                            ],
                                        ],
                                        [
                                            'id' => 'pag_1',
                                            'type' => 'pagination',
                                            'content' => [
                                                'mode' => 'archive_numeric',
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

        $resolver = app(PageBuilderResolverService::class);
        $resolved = $resolver->resolve($layout);

        $elements = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'];
        $pagination = $elements[1];

        $this->assertNotEmpty($pagination['resolved_data']['paginacion']);
        $pagData = $pagination['resolved_data']['paginacion'];

        $this->assertEquals(1, $pagData['current_page']);
        $this->assertEquals(2, $pagData['last_page']);
        $this->assertEquals(3, $pagData['total']);
    }

    #[Test]
    public function redirige_con_301_los_parametros_query_de_pagina_a_urls_limpias_seo(): void
    {
        $categoria = Category::create([
            'name'     => 'Blog',
            'slug'     => 'blog',
            'per_page' => 5,
        ]);

        $response = $this->get('/blog?page=2');
        $response->assertStatus(301);
        $response->assertRedirect(url('/blog/pagina/2'));

        $responseP1 = $this->get('/blog?page=1');
        $responseP1->assertStatus(301);
        $responseP1->assertRedirect(url('/blog'));

        $responsePagina1 = $this->get('/blog/pagina/1');
        $responsePagina1->assertStatus(301);
        $responsePagina1->assertRedirect(url('/blog'));
    }

    #[Test]
    public function carga_correctamente_categoria_en_ruta_paginada_limpia(): void
    {
        $admin = User::factory()->create();
        $categoria = Category::create([
            'name'     => 'Tutoriales',
            'slug'     => 'tutoriales',
            'per_page' => 2,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Article::create([
                'title'        => "Tutorial $i",
                'slug'         => "tutorial-$i",
                'category_id'  => $categoria->id,
                'author_id'    => $admin->id,
                'status'       => 'published',
                'published_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $response = $this->get('/tutoriales/pagina/2');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Category')
            ->where('articulos.meta.current_page', 2)
            ->where('articulos.meta.last_page', 2)
        );
    }

    #[Test]
    public function resuelve_correctamente_plantilla_de_articulo_sin_sobreescribir_con_paginacion_numerica(): void
    {
        $admin = User::factory()->create();
        $categoria = Category::create([
            'name'     => 'Servicios Test',
            'slug'     => 'servicios-test',
            'per_page' => 12,
        ]);

        $art1 = Article::create([
            'title'        => 'Servicio 1',
            'slug'         => 'servicio-1',
            'category_id'  => $categoria->id,
            'author_id'    => $admin->id,
            'status'       => 'published',
            'published_at' => now()->subMinutes(10),
            'order'        => 1,
        ]);

        $art2 = Article::create([
            'title'        => 'Servicio 2',
            'slug'         => 'servicio-2',
            'category_id'  => $categoria->id,
            'author_id'    => $admin->id,
            'status'       => 'published',
            'published_at' => now()->subMinutes(5),
            'order'        => 2,
        ]);

        $layout = [
            'sections' => [
                [
                    'id' => 'sec_art',
                    'rows' => [
                        [
                            'id' => 'row_art',
                            'columns' => [
                                [
                                    'id' => 'col_art',
                                    'elements' => [
                                        [
                                            'id' => 'pag_nav',
                                            'type' => 'pagination',
                                            'content' => [
                                                'mode' => 'article_nav',
                                                'layout_variant' => 'cards',
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

        $resolver = app(PageBuilderResolverService::class);
        $resolved = $resolver->resolveForArticle($layout, $art1);

        $elements = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'];
        $pagination = $elements[0];

        // Debe contener tanto navegación secuencial como paginación numérica de serie de artículos
        $this->assertArrayHasKey('resolved_data', $pagination);
        $this->assertArrayHasKey('navegacion', $pagination['resolved_data']);
        $this->assertArrayHasKey('paginacion', $pagination['resolved_data']);
        $this->assertTrue($pagination['resolved_data']['paginacion']['is_article_series']);

        // Para art1 (mas antiguo), el anterior cronologico en orden desc es Servicio 2
        $this->assertEquals('Servicio 2', $pagination['resolved_data']['navegacion']['anterior']['title']);
        $this->assertStringContainsString('servicios-test/servicio-2', $pagination['resolved_data']['navegacion']['anterior']['url']);
        $this->assertStringNotContainsString('pagina/2', $pagination['resolved_data']['navegacion']['anterior']['url']);
    }

    #[Test]
    public function resuelve_correctamente_plantilla_de_articulo_con_paginacion_numerica_enlazando_a_articulos_hermanos(): void
    {
        $admin = User::factory()->create();
        $categoria = Category::create([
            'name'     => 'Servicios Num',
            'slug'     => 'servicios-num',
            'per_page' => 12,
            'articles_order' => 'published_at_desc',
        ]);

        $art1 = Article::create([
            'title'        => 'Desarrollo Web Laravel & Vue',
            'slug'         => 'desarrollo-web-laravel-vue',
            'category_id'  => $categoria->id,
            'author_id'    => $admin->id,
            'status'       => 'published',
            'published_at' => now()->subMinutes(10),
            'order'        => 1,
        ]);

        $art2 = Article::create([
            'title'        => 'Desarrollos Web en Joomla',
            'slug'         => 'desarrollos-web-en-joomla',
            'category_id'  => $categoria->id,
            'author_id'    => $admin->id,
            'status'       => 'published',
            'published_at' => now()->subMinutes(5),
            'order'        => 2,
        ]);

        $layout = [
            'sections' => [
                [
                    'id' => 'sec_num',
                    'rows' => [
                        [
                            'id' => 'row_num',
                            'columns' => [
                                [
                                    'id' => 'col_num',
                                    'elements' => [
                                        [
                                            'id' => 'pag_elem',
                                            'type' => 'pagination',
                                            'content' => [
                                                'mode' => 'archive_numeric',
                                                'numeric_variant' => 'numbers_boxed',
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

        $resolver = app(PageBuilderResolverService::class);
        $resolved = $resolver->resolveForArticle($layout, $art1);

        $pagination = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'][0];
        $pagData = $pagination['resolved_data']['paginacion'];

        $this->assertTrue($pagData['is_article_series']);
        $this->assertEquals(2, $pagData['total']);
        $this->assertEquals(2, $pagData['last_page']);

        // Cada enlace numérico debe apuntar directamente al slug de su respectivo artículo hermano
        $links = $pagData['links'];
        $link1 = collect($links)->firstWhere('label', '1');
        $link2 = collect($links)->firstWhere('label', '2');

        $this->assertNotNull($link1);
        $this->assertNotNull($link2);

        // Ningún enlace debe contener /pagina/2 o slug/slug/pagina
        $this->assertStringNotContainsString('pagina/2', $link1['url']);
        $this->assertStringNotContainsString('pagina/2', $link2['url']);
        $this->assertStringContainsString('servicios-num/desarrollo-web-laravel-vue', $link2['url']); // En orden desc art2 es más reciente (pos 1), art1 (pos 2)
    }
}
