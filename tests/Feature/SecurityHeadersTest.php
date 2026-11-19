<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\CspInlineScripts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración para el middleware global SecurityHeaders y validación estricta
 * de Content-Security-Policy (CSP) compatible con Mozilla HTTP Observatory (Nota A / A+).
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function respuestas_publicas_incluyen_todas_las_cabeceras_de_seguridad_y_csp_sin_unsafe_inline_en_scripts(): void
    {
        SiteSetting::create([
            'site_name' => 'CMS Seguro',
            'frontend_theme_mode' => 'dark',
        ]);

        Page::create([
            'title' => 'Inicio',
            'slug' => 'inicio',
            'status' => 'published',
            'is_home' => true,
        ]);

        $response = $this->get('https://cms.test/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
        // Por defecto HSTS lo gestiona Nginx para no duplicar la cabecera RFC 6797
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertNotEmpty($csp);

        // Directivas exigidas por MDN HTTP Observatory
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString('upgrade-insecure-requests', $csp);

        // Verificar que script-src NO contiene 'unsafe-inline', 'unsafe-eval' ni comodines amplios
        $scriptSrc = $this->extractCspDirective($csp, 'script-src');
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrc);
        $this->assertStringNotContainsString("'unsafe-eval'", $scriptSrc);
        $this->assertDoesNotMatchRegularExpression('/(?:^|\s)https:(?:\s|$)/', $scriptSrc);

        // Verificar que todos los scripts ejecutables inline del HTML están autorizados en script-src
        $this->assertAllExecutableInlineScriptsMatchCsp($response->getContent(), $scriptSrc);
    }

    #[Test]
    public function todos_los_modos_de_tema_del_frontend_generan_scripts_inline_autorizados_por_su_hash_csp(): void
    {
        Page::create([
            'title' => 'Inicio',
            'slug' => 'inicio',
            'status' => 'published',
            'is_home' => true,
        ]);

        $combinaciones = [
            ['mode' => 'light', 'default' => 'light', 'expected' => CspInlineScripts::PUBLIC_THEME_LIGHT],
            ['mode' => 'dark', 'default' => 'light', 'expected' => CspInlineScripts::PUBLIC_THEME_DARK],
            ['mode' => 'user_choice', 'default' => 'light', 'expected' => CspInlineScripts::PUBLIC_THEME_USER_CHOICE_LIGHT],
            ['mode' => 'user_choice', 'default' => 'dark', 'expected' => CspInlineScripts::PUBLIC_THEME_USER_CHOICE_DARK],
            ['mode' => 'user_choice', 'default' => 'system', 'expected' => CspInlineScripts::PUBLIC_THEME_USER_CHOICE_SYSTEM],
        ];

        foreach ($combinaciones as $combo) {
            SiteSetting::updateOrCreate(
                ['id' => 1],
                [
                    'site_name' => 'CMS Seguro',
                    'frontend_theme_mode' => $combo['mode'],
                    'frontend_default_theme' => $combo['default'],
                ]
            );
            Cache::flush();

            $response = $this->get('/');
            $response->assertStatus(200);

            $csp = (string) $response->headers->get('Content-Security-Policy');
            $scriptSrc = $this->extractCspDirective($csp, 'script-src');

            $this->assertStringContainsString($combo['expected'], $response->getContent());
            $this->assertAllExecutableInlineScriptsMatchCsp($response->getContent(), $scriptSrc);
        }
    }

    #[Test]
    public function pagina_puente_de_whatsapp_cumple_estrictamente_con_el_hash_sha256_de_csp(): void
    {
        SiteSetting::create([
            'site_name' => 'CMS Seguro',
            'whatsapp_phone' => '600123456',
            'whatsapp_message' => 'Hola',
        ]);

        $response = $this->get('/whatsapp');
        $response->assertStatus(200);

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $scriptSrc = $this->extractCspDirective($csp, 'script-src');

        $this->assertStringContainsString(CspInlineScripts::hash(CspInlineScripts::CONTACT_BRIDGE), $scriptSrc);
        $this->assertAllExecutableInlineScriptsMatchCsp($response->getContent(), $scriptSrc);
    }

    #[Test]
    public function rutas_de_autenticacion_autorizan_ziggy_routes_mediante_nonce_criptografico_por_peticion(): void
    {
        SiteSetting::create(['site_name' => 'CMS Seguro']);

        $response1 = $this->get('/acceso');
        $response2 = $this->get('/acceso');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $scriptSrc1 = $this->extractCspDirective((string) $response1->headers->get('Content-Security-Policy'), 'script-src');
        $scriptSrc2 = $this->extractCspDirective((string) $response2->headers->get('Content-Security-Policy'), 'script-src');

        // El nonce debe rotar en cada petición
        $this->assertNotSame($scriptSrc1, $scriptSrc2);
        $this->assertMatchesRegularExpression("/'nonce-[A-Za-z0-9+\/=]+'/", $scriptSrc1);

        // Todos los scripts inline de /login (Ziggy @routes + tema admin) deben estar autorizados
        $this->assertAllExecutableInlineScriptsMatchCsp($response1->getContent(), $scriptSrc1);
    }

    #[Test]
    public function paginas_de_error_404_tambien_incluyen_todas_las_cabeceras_de_seguridad(): void
    {
        SiteSetting::create(['site_name' => 'CMS Seguro']);

        $response = $this->get('/ruta-que-no-existe-jamas-404');

        $response->assertStatus(404);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function csp_omite_origenes_de_terceros_no_configurados_y_los_habilita_dinamicamente_cuando_se_activan(): void
    {
        config([
            'services.turnstile.site_key' => null,
            'services.turnstile.secret_key' => null,
        ]);

        $settings = SiteSetting::create([
            'site_name' => 'CMS Minimalista',
            'analytics_enabled' => false,
            'analytics_measurement_id' => null,
            'google_maps_api_key' => null,
        ]);

        Page::create([
            'title' => 'Inicio',
            'slug' => 'inicio',
            'status' => 'published',
            'is_home' => true,
        ]);

        // 1. Sin integraciones configuradas: ni Cloudflare, ni GA4, ni Google Maps API en script-src
        $resMinimal = $this->get('/');
        $cspMinimal = (string) $resMinimal->headers->get('Content-Security-Policy');
        $scriptMinimal = $this->extractCspDirective($cspMinimal, 'script-src');

        $this->assertStringNotContainsString('challenges.cloudflare.com', $cspMinimal);
        $this->assertStringNotContainsString('googletagmanager.com', $scriptMinimal);
        $this->assertStringNotContainsString('maps.googleapis.com', $scriptMinimal);

        // 2. Aunque existan claves en .env, si ningún formulario activo usa captcha, Cloudflare sigue omitido
        config([
            'services.turnstile.site_key' => '0x4AAAAAA_test_site_key',
            'services.turnstile.secret_key' => '0x4AAAAAA_test_secret_key',
        ]);
        Cache::flush();

        $resNoFormCaptcha = $this->get('/');
        $this->assertStringNotContainsString(
            'challenges.cloudflare.com',
            (string) $resNoFormCaptcha->headers->get('Content-Security-Policy')
        );

        // 3. Al activar un formulario con captcha, Analytics y Google Maps, se habilitan automáticamente
        Form::create([
            'name' => 'Contacto Seguro',
            'slug' => 'contacto-seguro',
            'fields_schema' => [],
            'success_message' => 'Enviado',
            'is_active' => true,
            'requires_captcha' => true,
        ]);
        $settings->update([
            'analytics_enabled' => true,
            'analytics_measurement_id' => 'G-TEST123456',
            'google_maps_api_key' => 'TEST_MAPS_API_KEY_PLACEHOLDER',
        ]);
        Cache::flush();

        $resFull = $this->get('/');
        $cspFull = (string) $resFull->headers->get('Content-Security-Policy');
        $scriptFull = $this->extractCspDirective($cspFull, 'script-src');

        $this->assertStringContainsString('https://challenges.cloudflare.com', $scriptFull);
        $this->assertStringContainsString('https://www.googletagmanager.com', $scriptFull);
        $this->assertStringContainsString('https://maps.googleapis.com', $scriptFull);
    }

    /**
     * Extrae el contenido de una directiva específica dentro de una cabecera CSP.
     */
    private function extractCspDirective(string $csp, string $directiveName): string
    {
        foreach (explode(';', $csp) as $part) {
            $trimmed = trim($part);
            if (str_starts_with($trimmed, $directiveName . ' ')) {
                return $trimmed;
            }
        }

        $this->fail("No se encontró la directiva [{$directiveName}] en la cabecera CSP: {$csp}");
    }

    /**
     * Comprueba que cada bloque <script> ejecutable inline presente en el HTML esté autorizado
     * bien por su atributo nonce='...' o bien por su hash exacto 'sha256-...' en script-src.
     */
    private function assertAllExecutableInlineScriptsMatchCsp(string $html, string $scriptSrc): void
    {
        preg_match_all('#<script\b([^>]*)>(.*?)</script>#is', $html, $matches, PREG_SET_ORDER);

        $inlineChecked = 0;

        foreach ($matches as $match) {
            $attrs = $match[1];
            $body = $match[2];

            // Ignorar scripts externos con src="..."
            if (preg_match('#\bsrc\s*=#i', $attrs)) {
                continue;
            }

            // Ignorar bloques de datos no ejecutables como application/ld+json
            if (preg_match('#\btype\s*=\s*["\']([^"\']+)["\']#i', $attrs, $typeMatch)) {
                $mime = strtolower(trim($typeMatch[1]));
                if (!in_array($mime, ['text/javascript', 'application/javascript', 'module'], true)) {
                    continue;
                }
            }

            $inlineChecked++;

            // Comprobar si tiene atributo nonce válido en script-src
            if (preg_match('#\bnonce\s*=\s*["\']([^"\']+)["\']#i', $attrs, $nonceMatch)) {
                $nonceToken = "'nonce-{$nonceMatch[1]}'";
                $this->assertStringContainsString(
                    $nonceToken,
                    $scriptSrc,
                    "El nonce [{$nonceToken}] del <script> inline no figura en script-src."
                );
                continue;
            }

            // Si no tiene nonce, su hash SHA-256 exacto debe figurar en script-src
            $expectedHash = CspInlineScripts::hash($body);
            $this->assertStringContainsString(
                $expectedHash,
                $scriptSrc,
                "El script inline [{$body}] con hash [{$expectedHash}] no está autorizado en script-src: {$scriptSrc}"
            );
        }

        $this->assertGreaterThan(0, $inlineChecked, 'Se esperaba validar al menos un <script> inline en el documento HTML.');
    }
}
