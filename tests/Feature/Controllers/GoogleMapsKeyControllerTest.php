<?php

namespace Tests\Feature\Controllers;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas para el endpoint seguro de obtención de la API key de Google Maps.
 */
class GoogleMapsKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function devuelve_la_api_key_cuando_esta_configurada(): void
    {
        SiteSetting::create([
            'site_name'           => 'Test CMS',
            'google_maps_api_key' => 'AIzaSyA_TestKeyParaMaps123',
        ]);

        $response = $this->getJson('/api/cms/maps-key');

        $response->assertStatus(200);
        $response->assertJson(['key' => 'AIzaSyA_TestKeyParaMaps123']);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function devuelve_null_cuando_no_hay_api_key_configurada(): void
    {
        SiteSetting::create([
            'site_name'           => 'Test CMS',
            'google_maps_api_key' => null,
        ]);

        $response = $this->getJson('/api/cms/maps-key');

        $response->assertStatus(200);
        $response->assertJson(['key' => null]);
    }

    #[Test]
    public function la_key_no_aparece_en_site_settings_de_paginas_publicas(): void
    {
        SiteSetting::create([
            'site_name'           => 'Test CMS',
            'google_maps_api_key' => 'AIzaSyA_SecretKeyNoDebeAparecer',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // La key NO debe estar en el HTML renderizado de la página pública
        $response->assertDontSee('AIzaSyA_SecretKeyNoDebeAparecer', false);
    }
}
