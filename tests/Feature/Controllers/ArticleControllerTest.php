<?php

namespace Tests\Feature\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests de integración del ArticleController.
 */
class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['active' => true]);
        $this->admin->assignRole('admin');

        $this->category = Category::create([
            'name' => 'Noticias',
            'slug' => 'noticias',
            'order' => 0,
        ]);
    }

    #[Test]
    public function puede_crear_articulo_sin_cuerpo(): void
    {
        $response = $this->actingAs($this->admin)->post(route('articulos.store'), [
            'title'       => 'Artículo sin cuerpo',
            'slug'        => 'articulo-sin-cuerpo',
            'category_id' => $this->category->id,
            'status'      => 'published',
            'body'        => null,
        ]);

        $response->assertRedirect(route('articulos.index'));
        $this->assertDatabaseHas('articles', [
            'title' => 'Artículo sin cuerpo',
            'slug'  => 'articulo-sin-cuerpo',
            'body'  => null,
        ]);
    }

    #[Test]
    public function puede_crear_articulo_con_cuerpo(): void
    {
        $response = $this->actingAs($this->admin)->post(route('articulos.store'), [
            'title'       => 'Artículo con cuerpo',
            'slug'        => 'articulo-con-cuerpo',
            'category_id' => $this->category->id,
            'status'      => 'published',
            'body'        => '<p>Contenido del artículo de prueba</p>',
        ]);

        $response->assertRedirect(route('articulos.index'));
        $this->assertDatabaseHas('articles', [
            'title' => 'Artículo con cuerpo',
            'body'  => '<p>Contenido del artículo de prueba</p>',
        ]);
    }

    #[Test]
    public function puede_actualizar_articulo_dejando_cuerpo_vacio(): void
    {
        $article = Article::create([
            'title'       => 'Artículo original',
            'slug'        => 'articulo-original',
            'category_id' => $this->category->id,
            'author_id'   => $this->admin->id,
            'status'      => 'published',
            'body'        => '<p>Texto inicial</p>',
        ]);

        $response = $this->actingAs($this->admin)->put(route('articulos.update', $article), [
            'title'       => 'Artículo actualizado sin cuerpo',
            'category_id' => $this->category->id,
            'status'      => 'published',
            'body'        => null,
        ]);

        $response->assertRedirect(route('articulos.index'));
        $this->assertDatabaseHas('articles', [
            'id'    => $article->id,
            'title' => 'Artículo actualizado sin cuerpo',
            'body'  => null,
        ]);
    }

    #[Test]
    public function index_retorna_articulos_paginados_con_estructura_resource(): void
    {
        // Crear 15 artículos para asegurar que hay más de 1 página (12 por página)
        for ($i = 1; $i <= 15; $i++) {
            Article::create([
                'title'        => "Artículo {$i}",
                'slug'         => "articulo-{$i}",
                'category_id'  => $this->category->id,
                'author_id'    => $this->admin->id,
                'status'       => 'published',
                'published_at' => now()->subMinutes(15 - $i),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('articulos.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Articulos/Index')
            ->has('articulos.data', 12)
            ->has('articulos.meta', fn ($meta) => $meta
                ->where('current_page', 1)
                ->where('last_page', 2)
                ->where('total', 15)
                ->etc()
            )
        );
    }

    #[Test]
    public function index_permite_ordenar_visualmente_por_columna_sin_alterar_la_base_de_datos(): void
    {
        $artZeta = Article::create([
            'title'        => 'Zeta Artículo',
            'slug'         => 'zeta-articulo',
            'category_id'  => $this->category->id,
            'author_id'    => $this->admin->id,
            'status'       => 'published',
            'order'        => 0,
            'published_at' => now(),
        ]);

        $artAlfa = Article::create([
            'title'        => 'Alfa Artículo',
            'slug'         => 'alfa-articulo',
            'category_id'  => $this->category->id,
            'author_id'    => $this->admin->id,
            'status'       => 'draft',
            'order'        => 1,
            'published_at' => now()->subDay(),
        ]);

        // Ordenar por título ascendente
        $response = $this->actingAs($this->admin)->get(route('articulos.index', [
            'sort_by'  => 'title',
            'sort_dir' => 'asc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Articulos/Index')
            ->where('articulos.data.0.id', $artAlfa->id)
            ->where('articulos.data.1.id', $artZeta->id)
            ->where('filtros.sort_by', 'title')
            ->where('filtros.sort_dir', 'asc')
        );

        // Verificar que los campos 'order' en la base de datos no se alteraron
        $this->assertEquals(0, $artZeta->fresh()->order);
        $this->assertEquals(1, $artAlfa->fresh()->order);
    }

    #[Test]
    public function reorder_actualiza_correctamente_el_campo_order_en_la_categoria(): void
    {
        $art1 = Article::create([
            'title'       => 'Primero',
            'slug'        => 'primero',
            'category_id' => $this->category->id,
            'author_id'   => $this->admin->id,
            'order'       => 0,
        ]);

        $art2 = Article::create([
            'title'       => 'Segundo',
            'slug'        => 'segundo',
            'category_id' => $this->category->id,
            'author_id'   => $this->admin->id,
            'order'       => 1,
        ]);

        $response = $this->actingAs($this->admin)->post(route('articulos.reorder'), [
            'category_id' => $this->category->id,
            'ids'         => [$art2->id, $art1->id],
            'page'        => 1,
            'per_page'    => 12,
        ]);

        $response->assertRedirect();
        $this->assertEquals(0, $art2->fresh()->order);
        $this->assertEquals(1, $art1->fresh()->order);
    }

    #[Test]
    public function index_permite_ordenar_por_autor_sin_error_500(): void
    {
        $response = $this->actingAs($this->admin)->get(route('articulos.index', [
            'category_id' => $this->category->id,
            'sort_by'     => 'author',
            'sort_dir'    => 'asc',
            'status'      => 'published',
        ]));

        $response->assertStatus(200);
    }

    #[Test]
    public function index_sin_parametros_no_fuerza_redirecciones_y_carga_limpiamente(): void
    {
        $response = $this->actingAs($this->admin)->get(route('articulos.index'));
        $response->assertStatus(200);
    }

    #[Test]
    public function update_redirige_al_listado_preservando_los_filtros_de_retorno(): void
    {
        $article = Article::create([
            'title'       => 'Artículo existente',
            'slug'        => 'articulo-existente',
            'category_id' => $this->category->id,
            'author_id'   => $this->admin->id,
            'status'      => 'published',
        ]);

        $filtrosPrevios = [
            'category_id' => (string)$this->category->id,
            'status'      => 'published',
            'page'        => 2,
        ];

        $response = $this->actingAs($this->admin)->put(route('articulos.update', $article), [
            'title'            => 'Artículo existente modificado',
            'category_id'      => $this->category->id,
            'status'           => 'published',
            '_filtros_retorno' => $filtrosPrevios,
        ]);

        $response->assertRedirect(route('articulos.index', $filtrosPrevios));
    }
}

