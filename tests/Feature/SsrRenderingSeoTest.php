<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsrRenderingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Aislar caché Redis en memoria durante las pruebas
        config([
            'cache.stores.redis' => [
                'driver' => 'array',
                'serialize' => false,
            ],
        ]);
        Cache::purge('redis');
    }

    #[Test]
    public function ssr_entrega_html_completo_para_seo_sin_object_promise_ni_twitter_site_vacio(): void
    {
        SiteSetting::create([
            'site_name' => 'CMS Prueba SEO SSR',
            'site_description' => 'Descripción global optimizada para motores de búsqueda.',
            'social_links' => [],
        ]);

        Page::create([
            'title' => 'Encabezado Principal Renderizado en Servidor',
            'slug' => '',
            'body' => '<p>Texto indexable directamente en el primer byte HTML.</p>',
            'is_active' => true,
            'is_homepage' => true,
            'seo_title' => 'Portada SEO SSR',
            'seo_description' => 'Meta descripción específica de portada.',
        ]);

        $response = $this->get('/');
        $response->assertOk();

        $html = (string) $response->getContent();

        // 1. Nunca debe serializarse una Promise sin resolver de @vue/server-renderer
        $this->assertStringNotContainsString('[object Promise]', $html);

        // 2. Si el daemon Node SSR está activo, verificar que el cuerpo HTML renderizó el DOM real fuera de data-page
        if (str_contains($html, ' inertia>')) {
            $this->assertMatchesRegularExpression(
                '/<h1[^>]*>\s*Encabezado Principal Renderizado en Servidor\s*<\/h1>/is',
                $html
            );
            $this->assertStringContainsString('<meta name="description" content="Meta descripción específica de portada." inertia>', $html);
            $this->assertMatchesRegularExpression('/<link rel="canonical" href="[^"]+" inertia>/', $html);
        }

        // 3. Sin cuenta de Twitter configurada no debe emitirse twitter:site vacío
        $this->assertStringNotContainsString('name="twitter:site" content=""', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
    }
}

