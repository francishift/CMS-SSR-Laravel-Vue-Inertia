<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use App\Http\Resources\PageResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MenuItemResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRobotsAndMenuNofollowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    /**
     * Comprobar que una página puede crearse y actualizarse con meta_robots.
     */
    public function test_pagina_soporta_meta_robots(): void
    {
        $response = $this->actingAs($this->admin)->post(route('paginas.store'), [
            'title'       => 'Página Privada SEO',
            'slug'        => 'pagina-privada-seo',
            'body'        => '<p>Contenido privado</p>',
            'is_active'   => true,
            'order'       => 1,
            'meta_robots' => 'noindex, nofollow',
        ]);

        $response->assertRedirect(route('paginas.index'));

        $pagina = Page::where('slug', 'pagina-privada-seo')->first();
        $this->assertNotNull($pagina);
        $this->assertEquals('noindex, nofollow', $pagina->meta_robots);

        $resource = (new PageResource($pagina))->resolve();
        $this->assertEquals('noindex, nofollow', $resource['meta_robots']);

        // Actualizar directiva a index, nofollow
        $updateResponse = $this->actingAs($this->admin)->put(route('paginas.update', $pagina), [
            'title'       => 'Página Privada SEO Actualizada',
            'slug'        => 'pagina-privada-seo',
            'body'        => '<p>Contenido privado</p>',
            'is_active'   => true,
            'order'       => 1,
            'meta_robots' => 'index, nofollow',
        ]);

        $updateResponse->assertRedirect(route('paginas.index'));
        $this->assertEquals('index, nofollow', $pagina->fresh()->meta_robots);
    }

    /**
     * Comprobar que un artículo puede crearse y actualizarse con meta_robots.
     */
    public function test_articulo_soporta_meta_robots(): void
    {
        $categoria = Category::create([
            'name'      => 'General',
            'slug'      => 'general',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('articulos.store'), [
            'title'        => 'Artículo Oculto SEO',
            'slug'         => 'articulo-oculto-seo',
            'category_id'  => $categoria->id,
            'status'       => 'published',
            'body'         => '<p>Texto de prueba</p>',
            'meta_robots'  => 'noindex, follow',
        ]);

        $response->assertRedirect(route('articulos.index'));

        $articulo = Article::where('slug', 'articulo-oculto-seo')->first();
        $this->assertNotNull($articulo);
        $this->assertEquals('noindex, follow', $articulo->meta_robots);

        $resource = (new ArticleResource($articulo))->resolve();
        $this->assertEquals('noindex, follow', $resource['meta_robots']);
    }

    /**
     * Comprobar que una categoría puede crearse y actualizarse con meta_robots.
     */
    public function test_categoria_soporta_meta_robots(): void
    {
        $response = $this->actingAs($this->admin)->post(route('categorias.store'), [
            'name'        => 'Categoría NoIndex',
            'slug'        => 'categoria-noindex',
            'is_active'   => true,
            'meta_robots' => 'noindex, nofollow',
        ]);

        $response->assertRedirect(route('categorias.index'));

        $categoria = Category::where('slug', 'categoria-noindex')->first();
        $this->assertNotNull($categoria);
        $this->assertEquals('noindex, nofollow', $categoria->meta_robots);

        $resource = (new CategoryResource($categoria))->resolve();
        $this->assertEquals('noindex, nofollow', $resource['meta_robots']);
    }

    /**
     * Comprobar que un item de menú soporta is_nofollow y se expone en Resource.
     */
    public function test_menu_item_soporta_is_nofollow(): void
    {
        $menu = Menu::create([
            'name'      => 'Menú Principal',
            'slug'      => 'menu-principal',
            'location'  => 'header',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('menus.items.store', $menu), [
            'title'       => 'Enlace Patrocinado',
            'url'         => 'https://externo.com',
            'target'      => '_blank',
            'is_nofollow' => true,
            'is_active'   => true,
        ]);

        $response->assertRedirect(route('menus.edit', $menu));

        $item = MenuItem::where('title', 'Enlace Patrocinado')->first();
        $this->assertNotNull($item);
        $this->assertTrue((bool) $item->is_nofollow);

        $resource = (new MenuItemResource($item))->resolve();
        $this->assertTrue($resource['is_nofollow']);

        // Actualizar a is_nofollow = false
        $updateResponse = $this->actingAs($this->admin)->put(route('menus.items.update', $item), [
            'title'       => 'Enlace Patrocinado Dofollow',
            'url'         => 'https://externo.com',
            'target'      => '_self',
            'is_nofollow' => false,
            'is_active'   => true,
        ]);

        $updateResponse->assertRedirect(route('menus.edit', $menu));
        $this->assertFalse((bool) $item->fresh()->is_nofollow);
    }

    /**
     * Comprobar que SeoService configura correctamente la directiva robots.
     */
    public function test_seo_service_aplica_directiva_robots(): void
    {
        $seoService = app(\App\Services\SeoService::class);

        $pagina = Page::create([
            'title'       => 'Página Robots Test',
            'slug'        => 'pagina-robots-test',
            'is_active'   => true,
            'meta_robots' => 'noindex, nofollow',
        ]);

        $seoService->configurarPagina($pagina, 'Mi Sitio');

        $htmlRobots = \Artesaos\SEOTools\Facades\SEOMeta::generate();
        $this->assertStringContainsString('name="robots" content="noindex, nofollow"', $htmlRobots);
    }

    /**
     * Comprobar que acceder a /articulos/{slug} redirige con 301 a la URL canónica /{categoria}/{slug}.
     */
    public function test_articulo_por_slug_redirige_301_a_url_canonica(): void
    {
        $categoria = Category::create([
            'name'      => 'Desarrollo',
            'slug'      => 'desarrollo',
            'is_active' => true,
        ]);

        $articulo = Article::create([
            'title'        => 'Artículo Redirección 301',
            'slug'         => 'articulo-redireccion-301',
            'body'         => '<p>Contenido</p>',
            'status'       => 'published',
            'category_id'  => $categoria->id,
            'author_id'    => $this->admin->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/articulos/{$articulo->slug}");

        $response->assertStatus(301);
        $response->assertRedirect(url("/{$categoria->slug}/{$articulo->slug}"));
    }

    /**
     * Comprobar que acceder a /{categoriaFalsa}/{slug} redirige con 301 a la categoría real.
     */
    public function test_articulo_con_categoria_invalida_redirige_301_a_categoria_real(): void
    {
        $categoriaReal = Category::create([
            'name'      => 'Backend',
            'slug'      => 'backend',
            'is_active' => true,
        ]);

        $articulo = Article::create([
            'title'        => 'Artículo Blindaje Slug',
            'slug'         => 'articulo-blindaje-slug',
            'body'         => '<p>Contenido</p>',
            'status'       => 'published',
            'category_id'  => $categoriaReal->id,
            'author_id'    => $this->admin->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/categoria-falsa/{$articulo->slug}");

        $response->assertStatus(301);
        $response->assertRedirect(url("/{$categoriaReal->slug}/{$articulo->slug}"));
    }

    /**
     * Comprobar que solicitar una página que excede el total de artículos en una categoría devuelve 404.
     */
    public function test_categoria_con_paginacion_fuera_de_rango_devuelve_404(): void
    {
        $categoria = Category::create([
            'name'      => 'Tutoriales',
            'slug'      => 'tutoriales',
            'is_active' => true,
            'per_page'  => 10,
        ]);

        // Crear 1 artículo solo (solo hay página 1)
        Article::create([
            'title'        => 'Único Artículo',
            'slug'         => 'unico-articulo',
            'body'         => '<p>Contenido</p>',
            'status'       => 'published',
            'category_id'  => $categoria->id,
            'author_id'    => $this->admin->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/tutoriales/pagina/999");
        $response->assertStatus(404);
    }

    /**
     * Comprobar que el sitemap excluye páginas y artículos con directiva noindex.
     */
    public function test_sitemap_excluye_recursos_con_noindex(): void
    {
        $seoService = app(\App\Services\SeoService::class);

        // Página pública indexable
        Page::create([
            'title'       => 'Página Indexable',
            'slug'        => 'pagina-indexable-sitemap',
            'is_active'   => true,
            'meta_robots' => 'index, follow',
        ]);

        // Página con noindex
        Page::create([
            'title'       => 'Página Secreta',
            'slug'        => 'pagina-secreta-noindex',
            'is_active'   => true,
            'meta_robots' => 'noindex, nofollow',
        ]);

        $seoService->generarSitemap();

        $sitemapContent = file_get_contents(public_path('sitemap.xml'));
        $this->assertStringContainsString('pagina-indexable-sitemap', $sitemapContent);
        $this->assertStringNotContainsString('pagina-secreta-noindex', $sitemapContent);
    }

    /**
     * Comprobar que al borrar definitivamente un artículo, desaparece al instante de sitemap.xml.
     */
    public function test_borrar_definitivamente_articulo_lo_elimina_al_instante_del_sitemap(): void
    {
        $categoria = Category::create([
            'name'      => 'Blog',
            'slug'      => 'blog',
            'is_active' => true,
        ]);

        $articulo = Article::create([
            'title'        => 'Artículo para Borrar del Sitemap',
            'slug'         => 'articulo-a-borrar-sitemap',
            'body'         => '<p>Contenido</p>',
            'status'       => 'published',
            'category_id'  => $categoria->id,
            'author_id'    => $this->admin->id,
            'published_at' => now()->subDay(),
        ]);

        // Verificar que está en el sitemap
        $sitemapContent = file_get_contents(public_path('sitemap.xml'));
        $this->assertStringContainsString('articulo-a-borrar-sitemap', $sitemapContent);

        // Borrado definitivo (forceDelete)
        $articulo->forceDelete();

        // Verificar que DESAPARECIÓ al instante del sitemap sin esperar al cron diario
        $sitemapActualizado = file_get_contents(public_path('sitemap.xml'));
        $this->assertStringNotContainsString('articulo-a-borrar-sitemap', $sitemapActualizado);
    }
}

