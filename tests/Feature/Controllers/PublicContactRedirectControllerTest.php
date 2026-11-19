<?php

namespace Tests\Feature\Controllers;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración para las rutas de contacto seguro y anti-scraping
 * (PublicContactRedirectController).
 */
class PublicContactRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function redireccion_email_devuelve_pagina_puente_con_mailto_y_cabeceras_anti_bot(): void
    {
        SiteSetting::create([
            'site_name' => 'Test CMS',
            'contact_email' => 'contacto@testcms.es',
        ]);

        $response = $this->get('/email');

        $response->assertStatus(200);
        $response->assertSee('mailto:contacto@testcms.es', false);
        $response->assertSee('window.location.replace', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function redireccion_email_sin_configuracion_redirige_a_inicio_con_aviso(): void
    {
        SiteSetting::create([
            'site_name' => 'Test CMS',
            'contact_email' => null,
        ]);

        $response = $this->get('/email');

        $response->assertRedirect('/');
        $response->assertSessionHas('info', 'El correo de contacto no está configurado.');
    }

    #[Test]
    public function redireccion_telefono_devuelve_pagina_puente_con_tel(): void
    {
        SiteSetting::create([
            'site_name' => 'Test CMS',
            'contact_phone' => '+34 600 123 456',
        ]);

        $response = $this->get('/llamar');

        $response->assertStatus(200);
        $response->assertSee('tel:+34600123456', false);
        $response->assertSee('window.location.replace', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[Test]
    public function redireccion_whatsapp_en_escritorio_abre_whatsapp_web(): void
    {
        SiteSetting::create([
            'site_name' => 'Test CMS',
            'whatsapp_phone' => '600123456',
            'whatsapp_message' => 'Hola consulta',
        ]);

        $desktopUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        $response = $this->withHeader('User-Agent', $desktopUA)->get('/whatsapp');

        $response->assertStatus(200);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $response->assertSee('https://wa.me/34600123456?text=Hola%20consulta', false);
        $response->assertSee('window.location.replace', false);
    }

    #[Test]
    public function redireccion_whatsapp_en_dispositivos_moviles_abre_url_segura(): void
    {
        SiteSetting::create([
            'site_name' => 'Test CMS',
            'whatsapp_phone' => '600123456',
            'whatsapp_message' => 'Hola móvil',
        ]);

        $iosUA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

        $response = $this->withHeader('User-Agent', $iosUA)->get('/whatsapp');

        $response->assertStatus(200);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('https://wa.me/34600123456', false);
    }

    #[Test]
    public function redireccion_whatsapp_en_android_abre_url_segura(): void
    {
        SiteSetting::create([
            'site_name' => 'Test CMS',
            'whatsapp_phone' => '600123456',
            'whatsapp_message' => 'Hola android',
        ]);

        $androidUA = 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.43 Mobile Safari/537.36';

        $response = $this->withHeader('User-Agent', $androidUA)->get('/whatsapp');

        $response->assertStatus(200);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('https://wa.me/34600123456', false);
    }
}

