<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas automatizadas para la unificación y exhaustividad
 * de los datos estructurados Schema.org (@graph) en JSON-LD.
 */
class SeoUnifiedJsonLdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::create([
            'id'                       => 1,
            'site_name'                => 'Mi Portal de Pruebas',
            'site_description'         => 'Descripción global del portal de pruebas',
            'logo_path'                => '/storage/media/logo-test.svg',
            'social_links'             => [
                'twitter'  => 'https://x.com/portalpruebas',
                'linkedin' => 'https://linkedin.com/in/portalpruebas',
            ],
            'contact_email'            => 'soporte@portalpruebas.com',
            'contact_phone'            => '+34 900 123 456',
            'primary_color'            => '#10b981',
            'heading_font'             => 'Inter',
            'body_font'                => 'Inter',
        ]);
        SiteSetting::clearCache();
    }

    public function test_esquema_home_incluye_organization_y_website_con_todas_las_variables(): void
    {
        $seoService = app(SeoService::class);
        $settings = SiteSetting::cached();

        $seoService->configurarHome($settings->site_name, $settings->site_description, '/storage/media/og-home.jpg');
        $scriptTag = $seoService->renderSchemaJsonLd();

        $this->assertStringStartsWith('<script type="application/ld+json">', $scriptTag);
        $this->assertStringEndsWith('</script>', $scriptTag);

        $jsonStr = trim(str_replace(['<script type="application/ld+json">', '</script>'], '', $scriptTag));
        $data = json_decode($jsonStr, true);

        $this->assertIsArray($data);
        $this->assertEquals('https://schema.org', $data['@context']);
        $this->assertArrayHasKey('@graph', $data);

        $nodes = collect($data['@graph'])->keyBy('@type');

        // 1. Organization
        $this->assertTrue($nodes->has('Organization'));
        $org = $nodes->get('Organization');
        $this->assertEquals('Mi Portal de Pruebas', $org['name']);
        $this->assertEquals(url('/'), $org['url']);
        $this->assertEquals(url('/storage/media/logo-test.svg'), $org['logo']['url']);
        $this->assertContains('https://x.com/portalpruebas', $org['sameAs']);
        $this->assertContains('https://linkedin.com/in/portalpruebas', $org['sameAs']);
        $this->assertEquals('+34 900 123 456', $org['contactPoint'][0]['telephone']);
        $this->assertEquals('soporte@portalpruebas.com', $org['contactPoint'][0]['email']);

        // 2. WebSite
        $this->assertTrue($nodes->has('WebSite'));
        $website = $nodes->get('WebSite');
        $this->assertEquals('Mi Portal de Pruebas', $website['name']);
        $this->assertEquals(url('/#organization'), $website['publisher']['@id']);
        $this->assertEquals('SearchAction', $website['potentialAction']['@type']);
    }

    public function test_esquema_articulo_incluye_todas_las_variables_y_breadcrumb(): void
    {
        $autor = User::factory()->create([
            'name' => 'Redactor Estrella',
        ]);

        $categoria = Category::create([
            'name'      => 'Tecnología',
            'slug'      => 'tecnologia',
            'is_active' => true,
        ]);

        $articulo = Article::create([
            'title'           => 'Nueva Revolución en Inteligencia Artificial',
            'slug'            => 'nueva-revolucion-en-ia',
            'excerpt'         => 'Breve resumen sobre el impacto de la IA en 2026.',
            'body'            => '<p>' . str_repeat('palabra ', 300) . '</p>',
            'status'          => 'published',
            'published_at'    => now()->subDays(2),
            'reading_time'    => 2,
            'category_id'     => $categoria->id,
            'author_id'       => $autor->id,
            'featured_image'  => '/storage/articulos/ia.jpg',
            'seo_title'       => 'Revolución IA 2026 | Análisis Completo',
            'seo_description' => 'Meta descripción especializada para el artículo de IA.',
        ]);

        $breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => 'Tecnología', 'url' => url('/tecnologia')],
            ['name' => $articulo->title, 'url' => url('/tecnologia/' . $articulo->slug)],
        ];

        $seoService = app(SeoService::class);
        $settings = SiteSetting::cached();
        $seoService->configurarArticulo($articulo, $settings->site_name, $breadcrumb);

        $scriptTag = $seoService->renderSchemaJsonLd();
        $jsonStr = trim(str_replace(['<script type="application/ld+json">', '</script>'], '', $scriptTag));
        $data = json_decode($jsonStr, true);

        $nodes = collect($data['@graph'])->keyBy('@type');

        // Article
        $this->assertTrue($nodes->has('Article'));
        $artNode = $nodes->get('Article');
        $this->assertEquals('Revolución IA 2026 | Análisis Completo', $artNode['headline']);
        $this->assertEquals('Meta descripción especializada para el artículo de IA.', $artNode['description']);
        $this->assertEquals(url('/tecnologia/nueva-revolucion-en-ia'), $artNode['url']);
        $this->assertEquals(url('/tecnologia/nueva-revolucion-en-ia'), $artNode['mainEntityOfPage']['@id']);
        $this->assertEquals('Redactor Estrella', $artNode['author']['name']);
        $this->assertEquals('Tecnología', $artNode['articleSection']);
        $this->assertEquals(300, $artNode['wordCount']);
        $this->assertEquals('PT2M', $artNode['timeRequired']);
        $this->assertEquals(url('/tecnologia/nueva-revolucion-en-ia#breadcrumb'), $artNode['breadcrumb']['@id']);

        // BreadcrumbList
        $this->assertTrue($nodes->has('BreadcrumbList'));
        $breadNode = $nodes->get('BreadcrumbList');
        $this->assertCount(3, $breadNode['itemListElement']);
        $this->assertEquals('Tecnología', $breadNode['itemListElement'][1]['name']);
    }

    public function test_esquema_pagina_estatica_incluye_todas_las_variables(): void
    {
        $pagina = Page::create([
            'title'           => 'Quiénes Somos',
            'slug'            => 'quienes-somos',
            'body'            => '<p>' . str_repeat('contenido ', 150) . '</p>',
            'is_active'       => true,
            'is_homepage'     => false,
            'seo_title'       => 'Conoce a Nuestro Equipo',
            'seo_description' => 'Historia y trayectoria de nuestra compañía.',
            'seo_og_image'    => '/storage/paginas/equipo.jpg',
        ]);

        $breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => 'Quiénes Somos', 'url' => url('/quienes-somos')],
        ];

        $seoService = app(SeoService::class);
        $settings = SiteSetting::cached();
        $seoService->configurarPagina($pagina, $settings->site_name, 1, $breadcrumb);

        $scriptTag = $seoService->renderSchemaJsonLd();
        $jsonStr = trim(str_replace(['<script type="application/ld+json">', '</script>'], '', $scriptTag));
        $data = json_decode($jsonStr, true);

        $nodes = collect($data['@graph'])->keyBy('@type');

        // WebPage
        $this->assertTrue($nodes->has('WebPage'));
        $pageNode = $nodes->get('WebPage');
        $this->assertEquals('Conoce a Nuestro Equipo', $pageNode['name']);
        $this->assertEquals('Historia y trayectoria de nuestra compañía.', $pageNode['description']);
        $this->assertEquals(url('/quienes-somos'), $pageNode['url']);
        $this->assertEquals(150, $pageNode['wordCount']);
        $this->assertEquals(url('/quienes-somos#breadcrumb'), $pageNode['breadcrumb']['@id']);
    }

    public function test_esquema_categoria_incluye_todas_las_variables(): void
    {
        $categoria = Category::create([
            'name'            => 'Diseño Web',
            'slug'            => 'diseno-web',
            'description'     => 'Artículos y tutoriales de diseño web moderno.',
            'is_active'       => true,
            'seo_title'       => 'Especialistas en Diseño Web',
            'seo_description' => 'Descubre tendencias y buenas prácticas de UI/UX.',
            'featured_image'  => '/storage/categorias/diseno.jpg',
        ]);

        $breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => 'Diseño Web', 'url' => url('/diseno-web')],
        ];

        $seoService = app(SeoService::class);
        $settings = SiteSetting::cached();
        $seoService->configurarCategoria($categoria, $settings->site_name, 1, $breadcrumb);

        $scriptTag = $seoService->renderSchemaJsonLd();
        $jsonStr = trim(str_replace(['<script type="application/ld+json">', '</script>'], '', $scriptTag));
        $data = json_decode($jsonStr, true);

        $nodes = collect($data['@graph'])->keyBy('@type');

        // CollectionPage
        $this->assertTrue($nodes->has('CollectionPage'));
        $catNode = $nodes->get('CollectionPage');
        $this->assertEquals('Especialistas en Diseño Web', $catNode['name']);
        $this->assertEquals('Descubre tendencias y buenas prácticas de UI/UX.', $catNode['description']);
        $this->assertEquals(url('/diseno-web'), $catNode['url']);
        $this->assertEquals(url('/diseno-web#breadcrumb'), $catNode['breadcrumb']['@id']);
    }

    public function test_respuesta_http_publica_contiene_un_solo_script_ld_json_y_sin_atributos_innerhtml(): void
    {
        $autor = User::factory()->create();
        $categoria = Category::create([
            'name'      => 'Tutoriales',
            'slug'      => 'tutoriales',
            'is_active' => true,
        ]);

        $articulo = Article::create([
            'title'        => 'Cómo programar con Vue',
            'slug'         => 'como-programar-con-vue',
            'excerpt'      => 'Tutorial paso a paso.',
            'body'         => '<p>Contenido completo del tutorial de Vue.</p>',
            'status'       => 'published',
            'published_at' => now()->subDay(),
            'category_id'  => $categoria->id,
            'author_id'    => $autor->id,
        ]);

        $response = $this->get('/tutoriales/como-programar-con-vue');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Debe haber exactamente una etiqueta <script type="application/ld+json">
        $count = substr_count($html, '<script type="application/ld+json">');
        $this->assertEquals(1, $count, "Debe existir exactamente 1 bloque de script ld+json en el HTML");

        // 2. No debe existir el artefacto 'innerHTML="{&quot;' proveniente del bug de Vue Head
        $this->assertStringNotContainsString('innerHTML="{&quot;', $html);
        $this->assertStringNotContainsString('innerHTML="{\&quot;', $html);

        // 3. El script debe contener los datos Schema.org válidos
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
    }
}
