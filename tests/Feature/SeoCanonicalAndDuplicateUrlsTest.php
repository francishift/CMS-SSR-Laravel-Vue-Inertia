<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Suite de pruebas para garantizar la ausencia total de URLs duplicadas
 * y la correcta emisión de redirecciones 301 y etiquetas canónicas SEO.
 */
class SeoCanonicalAndDuplicateUrlsTest extends TestCase
{
    use RefreshDatabase;

    private User $autor;
    private Category $categoria;
    private Article $articulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->autor = User::factory()->create();

        $this->categoria = Category::create([
            'name'      => 'Tecnología',
            'slug'      => 'tecnologia',
            'is_active' => true,
            'per_page'  => 2,
        ]);

        $this->articulo = Article::create([
            'title'        => 'Inteligencia Artificial en Laravel',
            'slug'         => 'inteligencia-artificial-en-laravel',
            'category_id'  => $this->categoria->id,
            'author_id'    => $this->autor->id,
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    #[Test]
    public function home_responde_con_200_y_canonical_limpio(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertEquals(url('/'), SEOMeta::getCanonical());
    }

    #[Test]
    public function home_pagina_1_redirige_301_a_la_raiz_canonica(): void
    {
        $response = $this->get('/pagina/1');
        $response->assertStatus(301);
        $response->assertRedirect(url('/'));

        $responseQuery = $this->get('/?page=1');
        $responseQuery->assertStatus(301);
        $responseQuery->assertRedirect(url('/'));
    }

    #[Test]
    public function home_parametro_query_page_redirige_301_a_url_limpia(): void
    {
        $response = $this->get('/?page=2');
        $response->assertStatus(301);
        $response->assertRedirect(url('/pagina/2'));
    }

    #[Test]
    public function home_paginada_limpia_establece_canonical_correcto(): void
    {
        $response = $this->get('/pagina/2');
        $response->assertStatus(200);
        $this->assertEquals(url('/pagina/2'), SEOMeta::getCanonical());
    }

    #[Test]
    public function categoria_pagina_1_redirige_301_a_su_url_canonica(): void
    {
        $response = $this->get('/tecnologia/pagina/1');
        $response->assertStatus(301);
        $response->assertRedirect(url('/tecnologia'));

        $responseQuery = $this->get('/tecnologia?page=1');
        $responseQuery->assertStatus(301);
        $responseQuery->assertRedirect(url('/tecnologia'));
    }

    #[Test]
    public function categoria_parametro_query_page_redirige_301_a_url_limpia(): void
    {
        $response = $this->get('/tecnologia?page=2');
        $response->assertStatus(301);
        $response->assertRedirect(url('/tecnologia/pagina/2'));
    }

    #[Test]
    public function categoria_paginada_limpia_establece_canonical_correcto(): void
    {
        // Crear artículos suficientes para tener página 2
        for ($i = 1; $i <= 3; $i++) {
            Article::create([
                'title'        => "Artículo extra $i",
                'slug'         => "articulo-extra-$i",
                'category_id'  => $this->categoria->id,
                'author_id'    => $this->autor->id,
                'status'       => 'published',
                'published_at' => now()->subHours($i),
            ]);
        }

        $response = $this->get('/tecnologia/pagina/2');
        $response->assertStatus(200);
        $this->assertEquals(url('/tecnologia/pagina/2'), SEOMeta::getCanonical());
    }

    #[Test]
    public function categoria_pagina_fuera_de_rango_devuelve_404_para_evitar_urls_vacias_duplicadas(): void
    {
        $response = $this->get('/tecnologia/pagina/999');
        $response->assertStatus(404);
    }

    #[Test]
    public function articulo_responde_con_su_url_canonica_completa(): void
    {
        $response = $this->get('/tecnologia/inteligencia-artificial-en-laravel');
        $response->assertStatus(200);
        $this->assertEquals(
            url('/tecnologia/inteligencia-artificial-en-laravel'),
            SEOMeta::getCanonical()
        );
    }

    #[Test]
    public function articulo_accedido_por_ruta_generica_articulos_redirige_301_a_url_canonica(): void
    {
        $response = $this->get('/articulos/inteligencia-artificial-en-laravel');
        $response->assertStatus(301);
        $response->assertRedirect(url('/tecnologia/inteligencia-artificial-en-laravel'));
    }

    #[Test]
    public function articulo_accedido_con_categoria_erronea_redirige_301_a_url_canonica(): void
    {
        $response = $this->get('/categoria-inexistente/inteligencia-artificial-en-laravel');
        $response->assertStatus(301);
        $response->assertRedirect(url('/tecnologia/inteligencia-artificial-en-laravel'));
    }

    #[Test]
    public function pagina_estatica_con_query_page_redirige_301_a_url_limpia(): void
    {
        $pagina = Page::create([
            'title'     => 'Sobre Nosotros',
            'slug'      => 'sobre-nosotros',
            'is_active' => true,
        ]);

        $responseP1 = $this->get('/sobre-nosotros?page=1');
        $responseP1->assertStatus(301);
        $responseP1->assertRedirect(url('/sobre-nosotros'));

        $responsePag1 = $this->get('/sobre-nosotros/pagina/1');
        $responsePag1->assertStatus(301);
        $responsePag1->assertRedirect(url('/sobre-nosotros'));

        $responseP2 = $this->get('/sobre-nosotros?page=2');
        $responseP2->assertStatus(301);
        $responseP2->assertRedirect(url('/sobre-nosotros/pagina/2'));
    }

    #[Test]
    public function pagina_de_busqueda_tiene_canonical_estricto_y_meta_robots_noindex(): void
    {
        $response = $this->get('/buscar?q=laravel');
        $response->assertStatus(200);

        // El canonical debe ser siempre la raíz del buscador sin parámetros
        $this->assertEquals(url('/buscar'), SEOMeta::getCanonical());

        // Debe emitir noindex, follow para que los motores no indexen búsquedas como contenido duplicado
        $this->assertEquals('noindex, follow', SEOMeta::getRobots());
    }
}
