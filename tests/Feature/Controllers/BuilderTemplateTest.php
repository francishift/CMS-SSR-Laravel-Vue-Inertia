<?php

namespace Tests\Feature\Controllers;

use App\Models\Article;
use App\Models\BuilderTemplate;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin_template@test.com',
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_list_templates(): void
    {
        BuilderTemplate::create([
            'name'        => 'Plantilla Noticia',
            'type'        => 'single_article',
            'layout_json' => BuilderTemplate::plantillaInicialPorTipo('single_article'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('plantillas.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_template(): void
    {
        $response = $this->actingAs($this->admin)->post(route('plantillas.store'), [
            'name'        => 'Plantilla Blog Tech',
            'type'        => 'single_article',
            'description' => 'Plantilla para artículos de tecnología',
        ]);

        $template = BuilderTemplate::where('name', 'Plantilla Blog Tech')->first();
        $this->assertNotNull($template);
        $response->assertRedirect(route('plantillas.builder', $template->id));
    }

    public function test_admin_can_update_template_and_layout(): void
    {
        $template = BuilderTemplate::create([
            'name'        => 'Plantilla Inicial',
            'type'        => 'category_archive',
            'layout_json' => ['sections' => []],
        ]);

        $customLayout = [
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
                                            'id'      => 'el_1',
                                            'type'    => 'heading',
                                            'content' => ['text' => '{category.name}'],
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
            'layout_json' => $customLayout,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('builder_templates', [
            'id' => $template->id,
        ]);

        $template->refresh();
        $this->assertEquals('{category.name}', $template->layout_json['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content']['text']);
    }

    public function test_admin_can_duplicate_template(): void
    {
        $template = BuilderTemplate::create([
            'name'        => 'Plantilla Original',
            'type'        => 'single_article',
            'layout_json' => BuilderTemplate::plantillaInicialPorTipo('single_article'),
        ]);

        $response = $this->actingAs($this->admin)->post(route('plantillas.duplicate', $template->id));
        $response->assertStatus(302);

        $this->assertDatabaseHas('builder_templates', [
            'name' => 'Plantilla Original (Copia)',
        ]);
    }

    public function test_public_category_with_template_resolves_dynamic_tags(): void
    {
        $template = BuilderTemplate::create([
            'name'        => 'Plantilla Categoria Custom',
            'type'        => 'category_archive',
            'layout_json' => [
                'sections' => [
                    [
                        'id'   => 'sec_cat',
                        'rows' => [
                            [
                                'id'      => 'row_cat',
                                'columns' => [
                                    [
                                        'id'       => 'col_cat',
                                        'elements' => [
                                            [
                                                'id'      => 'el_title',
                                                'type'    => 'heading',
                                                'content' => ['text' => 'Categoria: {category.name}'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'is_active'   => true,
        ]);

        $category = Category::create([
            'name'                         => 'Tecnología e IA',
            'slug'                         => 'tecnologia-ia',
            'description'                  => 'Noticias sobre innovación tecnológica',
            'category_builder_template_id' => $template->id,
            'is_active'                    => true,
        ]);

        $response = $this->get('/' . $category->slug);
        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];
        $this->assertNotNull($props['categoria']['layout_json']);
        $this->assertEquals(
            'Categoria: Tecnología e IA',
            $props['categoria']['layout_json']['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content']['text']
        );
    }

    public function test_public_article_with_template_resolves_dynamic_tags(): void
    {
        $template = BuilderTemplate::create([
            'name'        => 'Plantilla Articulo Custom',
            'type'        => 'single_article',
            'layout_json' => [
                'sections' => [
                    [
                        'id'   => 'sec_art',
                        'rows' => [
                            [
                                'id'      => 'row_art',
                                'columns' => [
                                    [
                                        'id'       => 'col_art',
                                        'elements' => [
                                            [
                                                'id'      => 'el_art_title',
                                                'type'    => 'heading',
                                                'content' => ['text' => '{article.title}'],
                                            ],
                                            [
                                                'id'      => 'el_art_author',
                                                'type'    => 'text',
                                                'content' => ['html' => 'Por {article.author.name}'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'is_active'   => true,
        ]);

        $category = Category::create([
            'name'                        => 'General',
            'slug'                        => 'general',
            'article_builder_template_id' => $template->id, // Asignada a nivel categoría
            'is_active'                   => true,
        ]);

        $article = Article::create([
            'title'        => 'Lanzamiento Revolucionario de Inteligencia Artificial',
            'slug'         => 'lanzamiento-revolucionario-ia',
            'body'         => '<p>Contenido del artículo de prueba.</p>',
            'category_id'  => $category->id,
            'author_id'    => $this->admin->id,
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/{$category->slug}/{$article->slug}");
        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];
        $this->assertNotNull($props['articulo']['layout_json']);
        $this->assertEquals(
            'Lanzamiento Revolucionario de Inteligencia Artificial',
            $props['articulo']['layout_json']['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content']['text']
        );
        $this->assertEquals(
            'Por ' . $this->admin->name,
            $props['articulo']['layout_json']['sections'][0]['rows'][0]['columns'][0]['elements'][1]['content']['html']
        );
    }

    public function test_category_and_article_without_template_render_classic_view(): void
    {
        $category = Category::create([
            'name'      => 'Sin Plantilla',
            'slug'      => 'sin-plantilla',
            'is_active' => true,
        ]);

        $article = Article::create([
            'title'        => 'Articulo Clasico',
            'slug'         => 'articulo-clasico',
            'body'         => '<p>Vista clásica.</p>',
            'category_id'  => $category->id,
            'author_id'    => $this->admin->id,
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        // 1. Categoria sin plantilla -> layout_json es null
        $respCat = $this->get('/' . $category->slug);
        $respCat->assertStatus(200);
        $propsCat = $respCat->original->getData()['page']['props'];
        $this->assertNull($propsCat['categoria']['layout_json']);

        // 2. Articulo sin plantilla -> layout_json es null
        $respArt = $this->get("/{$category->slug}/{$article->slug}");
        $respArt->assertStatus(200);
        $propsArt = $respArt->original->getData()['page']['props'];
        $this->assertNull($propsArt['articulo']['layout_json']);
    }

    public function test_admin_can_open_template_builder(): void
    {
        $template = BuilderTemplate::create([
            'name'        => 'Plantilla Test Builder',
            'type'        => 'category_archive',
            'layout_json' => BuilderTemplate::plantillaInicialPorTipo('category_archive'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('plantillas.builder', $template->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Paginas/Builder')
            ->has('pagina')
            ->has('paginasList')
            ->has('categorias')
        );
    }

    public function test_admin_can_open_page_builder(): void
    {
        $page = \App\Models\Page::create([
            'title'       => 'Página Test',
            'slug'        => 'pagina-test',
            'layout_json' => ['sections' => []],
        ]);

        $response = $this->actingAs($this->admin)->get(route('paginas.builder', $page->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($p) => $p
            ->component('Paginas/Builder')
            ->has('pagina')
            ->has('paginasList')
            ->has('categorias')
        );
    }

    public function test_template_builder_picks_sample_article_from_assigned_category(): void
    {
        $template = BuilderTemplate::create([
            'name'        => 'Plantilla Servicios',
            'type'        => 'single_article',
            'layout_json' => BuilderTemplate::plantillaInicialPorTipo('single_article'),
        ]);

        $catServicios = Category::create([
            'name' => 'Servicios',
            'slug' => 'servicios-test',
            'order' => 1,
            'article_builder_template_id' => $template->id,
        ]);

        $catProyectos = Category::create([
            'name' => 'Proyectos',
            'slug' => 'proyectos-test',
            'order' => 2,
        ]);

        // Articulo en proyectos creado despues (mas reciente)
        Article::create([
            'title'        => 'Proyecto Reciente',
            'slug'         => 'proyecto-reciente',
            'category_id'  => $catProyectos->id,
            'author_id'    => $this->admin->id,
            'status'       => 'published',
            'published_at' => now(),
        ]);

        // Articulo en servicios creado antes
        $artServicio = Article::create([
            'title'        => 'Servicio Antiguo',
            'slug'         => 'servicio-antiguo',
            'category_id'  => $catServicios->id,
            'author_id'    => $this->admin->id,
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('plantillas.builder', $template->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($p) => $p
            ->component('Paginas/Builder')
            ->where('articuloMuestra.title', 'Servicio Antiguo')
            ->where('categoriaMuestra.name', 'Servicios')
        );
    }

    public function test_page_builder_resolver_service_resolves_extra_fields_url(): void
    {
        $category = Category::create([
            'name'                => 'Proyectos',
            'slug'                => 'proyectos-extra',
            'order'               => 1,
            'extra_fields_schema' => [
                ['key' => 'Url', 'label' => 'Sitio Web', 'type' => 'url'],
                ['key' => 'vacio', 'label' => 'Campo Vacio', 'type' => 'url'],
            ],
        ]);

        $article = Article::create([
            'title'             => 'Proyecto Alpha',
            'slug'              => 'proyecto-alpha',
            'category_id'       => $category->id,
            'author_id'         => $this->admin->id,
            'status'            => 'published',
            'published_at'      => now(),
            'extra_fields_data' => [
                'Url' => 'https://alpha.example.com',
            ],
        ]);

        $layout = [
            'sections' => [
                [
                    'rows' => [
                        [
                            'columns' => [
                                [
                                    'elements' => [
                                        [
                                            'type' => 'icon',
                                            'content' => [
                                                'icon' => 'pi pi-link',
                                                'url'  => '{article.extra_fields.Url}',
                                            ],
                                        ],
                                        [
                                            'type' => 'icon',
                                            'content' => [
                                                'icon' => 'pi pi-star',
                                                'url'  => '{article.extra_fields.vacio}',
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

        /** @var \App\Services\PageBuilderResolverService $resolver */
        $resolver = app(\App\Services\PageBuilderResolverService::class);
        $resolved = $resolver->resolveForArticle($layout, $article);

        $elements = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'];

        // Primer elemento: URL debe resolverse al valor configurado
        $this->assertSame('https://alpha.example.com', $elements[0]['content']['url']);

        // Segundo elemento: Campo vacío del esquema debe resolverse a cadena vacía
        $this->assertSame('', $elements[1]['content']['url']);
    }
}
