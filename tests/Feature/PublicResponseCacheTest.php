<?php

namespace Tests\Feature;

use App\Http\Middleware\PublicResponseCache;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicResponseCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Aislar el store 'redis' en memoria durante los tests para no tocar Redis de producción
        config([
            'cache.stores.redis' => [
                'driver' => 'array',
                'serialize' => false,
            ],
        ]);
        Cache::purge('redis');
    }

    #[Test]
    public function normaliza_urls_ignorando_parametros_de_campanas_y_preservando_funcionales(): void
    {
        $middleware = new PublicResponseCache();

        $cleanHome = $middleware->normalizarUrlParaCache(Request::create('https://ejemplo.com/'));
        $utmHome = $middleware->normalizarUrlParaCache(Request::create('https://ejemplo.com/?utm_source=linkedin&utm_medium=social&utm_campaign=lanzamiento'));
        $fbHome = $middleware->normalizarUrlParaCache(Request::create('https://ejemplo.com/?fbclid=IwAR123456789'));
        $adsHome = $middleware->normalizarUrlParaCache(Request::create('https://ejemplo.com/?gclid=EAIaIQobChMI&srsltid=AfmBOop'));

        $this->assertSame('https://ejemplo.com/', $cleanHome);
        $this->assertSame($cleanHome, $utmHome);
        $this->assertSame($cleanHome, $fbHome);
        $this->assertSame($cleanHome, $adsHome);

        // Los parámetros funcionales (?q=..., ?orden=...) se preservan y se ordenan alfabéticamente
        $searchA = $middleware->normalizarUrlParaCache(Request::create('https://ejemplo.com/buscar?utm_source=boletin&orden=recientes&q=laravel'));
        $searchB = $middleware->normalizarUrlParaCache(Request::create('https://ejemplo.com/buscar?q=laravel&orden=recientes&fbclid=XYZ'));
        $searchDifferent = $middleware->normalizarUrlParaCache(Request::create('https://ejemplo.com/buscar?q=vue&orden=recientes'));

        $this->assertSame('https://ejemplo.com/buscar?orden=recientes&q=laravel', $searchA);
        $this->assertSame($searchA, $searchB);
        $this->assertNotSame($searchA, $searchDifferent);
    }

    #[Test]
    public function visitas_simultaneas_con_distintos_parametros_utm_o_fbclid_reutilizan_la_misma_cache_sin_contaminar_el_html(): void
    {
        SiteSetting::create([
            'site_name' => 'CMS Concurrencia',
        ]);

        Page::create([
            'title' => 'Portada',
            'slug' => 'portada',
            'status' => 'published',
            'is_home' => true,
        ]);

        // 1. Primera visita con parámetros de campaña (MISS): almacena HTML limpio sin fbclid ni utm_source
        $firstResponse = $this->get('/?fbclid=SECRET_CLICK_ID_111&utm_source=facebook');
        $firstResponse->assertOk();
        $firstResponse->assertHeader('X-Response-Cache', 'MISS (Stored in Redis)');
        $this->assertStringNotContainsString('SECRET_CLICK_ID_111', (string) $firstResponse->getContent());

        // 2. Segunda visita desde otra campaña distinta (Google Ads): reutiliza HIT en RAM
        $secondResponse = $this->get('/?gclid=GOOGLE_CLICK_ID_222&utm_source=google&utm_medium=cpc');
        $secondResponse->assertOk();
        $secondResponse->assertHeader('X-Response-Cache', 'HIT (Redis RAM)');

        // 3. Tercera visita directa sin query string: reutiliza exactamente el mismo HIT en RAM
        $thirdResponse = $this->get('/');
        $thirdResponse->assertOk();
        $thirdResponse->assertHeader('X-Response-Cache', 'HIT (Redis RAM)');
    }
}

