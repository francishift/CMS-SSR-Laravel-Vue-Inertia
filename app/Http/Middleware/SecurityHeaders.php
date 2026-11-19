<?php

namespace App\Http\Middleware;

use App\Models\Form;
use App\Models\SiteSetting;
use App\Support\CspInlineScripts;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware global de cabeceras de seguridad HTTP y Content Security Policy (CSP).
 *
 * Se ejecuta al inicio de la pila HTTP (por delante de PublicResponseCache) para garantizar
 * que tanto las respuestas dinámicas SSR como las respuestas ultrarrápidas servidas desde
 * la memoria RAM de Redis incluyan todas las protecciones contra XSS, Clickjacking,
 * MIME-sniffing y filtrado de cabeceras Referer.
 */
class SecurityHeaders
{
    /**
     * Patrones de rutas privadas/autenticación que inyectan rutas dinámicas (@routes de Ziggy)
     * y nunca se almacenan en la caché pública de Redis, permitiendo el uso de nonce por petición.
     */
    private const DYNAMIC_NONCE_PATTERNS = [
        'panel*',
        'acceso*',
        'login*',
        'register*',
        'forgot-password*',
        'reset-password*',
        'confirm-password*',
        'password*',
        'logout*',
        'verify-email*',
        'auth/google*',
        'oauth/google*',
        '*builder/preview-frame*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('security.enabled', true)) {
            return $next($request);
        }

        // Generar nonce criptográfico por petición únicamente en rutas no cacheadas que usan @routes (Ziggy)
        if ($this->requiereNonceDinamico($request)) {
            $request->attributes->set('csp_nonce', base64_encode(random_bytes(16)));
        }

        /** @var Response $response */
        $response = $next($request);

        return self::applyToResponse($request, $response);
    }

    /**
     * Aplica las cabeceras de seguridad y la directiva CSP sobre cualquier instancia Response
     * (incluyendo respuestas de caché en RAM y páginas de excepción HTTP 403/404/500).
     */
    public static function applyToResponse(Request $request, Response $response): Response
    {
        if (!config('security.enabled', true)) {
            return $response;
        }

        // Ocultar versión exacta de PHP si el entorno SAPI lo permite
        if (function_exists('header_remove') && !headers_sent()) {
            @header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');

        // 1. Cabeceras defensivas universales
        self::setHeaderIfMissing($response, 'X-Content-Type-Options', (string) config('security.x_content_type_options', 'nosniff'));
        self::setHeaderIfMissing($response, 'X-Frame-Options', (string) config('security.x_frame_options', 'SAMEORIGIN'));
        self::setHeaderIfMissing($response, 'Referrer-Policy', (string) config('security.referrer_policy', 'strict-origin-when-cross-origin'));
        self::setHeaderIfMissing($response, 'Cross-Origin-Opener-Policy', (string) config('security.cross_origin_opener_policy', 'same-origin-allow-popups'));
        self::setHeaderIfMissing($response, 'X-Permitted-Cross-Domain-Policies', (string) config('security.x_permitted_cross_domain_policies', 'none'));

        $permissionsPolicy = (string) config('security.permissions_policy', '');
        if ($permissionsPolicy !== '') {
            self::setHeaderIfMissing($response, 'Permissions-Policy', $permissionsPolicy);
        }

        // HSTS opcional desde PHP (desactivado por defecto cuando Nginx/Plesk ya emite Strict-Transport-Security)
        if (config('security.hsts_enabled', false) && $request->isSecure() && !$response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', (string) config('security.hsts_value', 'max-age=31536000; includeSubDomains'));
        }

        // 2. Content Security Policy (CSP)
        // Solo es necesaria en documentos HTML (omitir en imágenes /media/render, JSON APIs o binarios para 0 ms de sobrecarga)
        if (!config('security.csp.enabled', true) || is_file(public_path('hot')) || !self::esRespuestaDocumentoHtml($response)) {
            return $response;
        }

        $headerName = config('security.csp.report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        if (!$response->headers->has($headerName)) {
            $response->headers->set($headerName, self::buildCspHeader($request));
        }

        return $response;
    }

    /**
     * Construye el valor completo de la cabecera Content-Security-Policy adaptado al contexto de la ruta
     * y únicamente con las integraciones de terceros que estén activas en la instancia del CMS.
     */
    public static function buildCspHeader(Request $request): string
    {
        $nonce = $request->attributes->get('csp_nonce');
        $isDynamicRoute = is_string($nonce) && $nonce !== '';

        $sources = [
            'script_src' => (array) config('security.csp.script_src', ["'self'"]),
            'style_src' => (array) config('security.csp.style_src', ["'self'", "'unsafe-inline'"]),
            'img_src' => (array) config('security.csp.img_src', ["'self'", 'data:', 'blob:', 'https:']),
            'font_src' => (array) config('security.csp.font_src', ["'self'", 'data:']),
            'connect_src' => (array) config('security.csp.connect_src', ["'self'"]),
            'frame_src' => (array) config('security.csp.frame_src', ["'self'"]),
            'media_src' => (array) config('security.csp.media_src', ["'self'", 'blob:', 'https:']),
            'worker_src' => (array) config('security.csp.worker_src', ["'self'", 'blob:']),
        ];

        // Añadir únicamente las integraciones externas configuradas/activas en esta instalación
        $integrations = (array) config('security.csp.integrations', []);
        foreach (self::resolveActiveIntegrations($isDynamicRoute) as $integrationKey) {
            if (!isset($integrations[$integrationKey]) || !is_array($integrations[$integrationKey])) {
                continue;
            }
            foreach ($integrations[$integrationKey] as $directiveKey => $origins) {
                if (isset($sources[$directiveKey]) && is_array($origins)) {
                    $sources[$directiveKey] = array_merge($sources[$directiveKey], $origins);
                }
            }
        }

        if ($isDynamicRoute) {
            $sources['script_src'][] = "'nonce-{$nonce}'";
            $sources['script_src'] = array_merge($sources['script_src'], CspInlineScripts::adminHashes());
        } else {
            $sources['script_src'] = array_merge($sources['script_src'], CspInlineScripts::publicHashes());
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            'script-src ' . implode(' ', array_unique($sources['script_src'])),
            'style-src ' . implode(' ', array_unique($sources['style_src'])),
            'img-src ' . implode(' ', array_unique($sources['img_src'])),
            'font-src ' . implode(' ', array_unique($sources['font_src'])),
            'connect-src ' . implode(' ', array_unique($sources['connect_src'])),
            'frame-src ' . implode(' ', array_unique($sources['frame_src'])),
            'media-src ' . implode(' ', array_unique($sources['media_src'])),
            'worker-src ' . implode(' ', array_unique($sources['worker_src'])),
            "manifest-src 'self'",
        ];

        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    /**
     * Determina qué integraciones opcionales de terceros están activas en la instancia actual del CMS.
     *
     * @return list<string>
     */
    private static function resolveActiveIntegrations(bool $isDynamicAdminRoute): array
    {
        if ($isDynamicAdminRoute) {
            $active = ['google_maps'];
            if (!empty(config('services.turnstile.site_key')) && !empty(config('services.turnstile.secret_key'))) {
                $active[] = 'turnstile';
            }
            try {
                $settings = SiteSetting::cached();
                if (!empty($settings->analytics_enabled) && !empty($settings->analytics_measurement_id)) {
                    $active[] = 'google_analytics';
                }
            } catch (\Throwable) {
            }

            return $active;
        }

        // En rutas públicas: 1 sola lectura plana en caché (sin deserializar modelos Eloquent en cada petición)
        try {
            $cachedFlags = Cache::remember('cms_csp_active_integrations', 86400, static function (): array {
                $settings = SiteSetting::cached();

                return [
                    'captcha_form' => Form::hasAnyCaptchaEnabled(),
                    'analytics' => !empty($settings->analytics_enabled) && !empty($settings->analytics_measurement_id),
                    'maps' => SiteSetting::hasGoogleMapsKeyConfigured(),
                ];
            });

            $active = [];
            if (
                !empty($cachedFlags['captcha_form'])
                && !empty(config('services.turnstile.site_key'))
                && !empty(config('services.turnstile.secret_key'))
            ) {
                $active[] = 'turnstile';
            }
            if (!empty($cachedFlags['analytics'])) {
                $active[] = 'google_analytics';
            }
            if (!empty($cachedFlags['maps'])) {
                $active[] = 'google_maps';
            }

            return $active;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Comprueba si la respuesta corresponde a un documento HTML que deba portar directiva CSP completa.
     */
    private static function esRespuestaDocumentoHtml(Response $response): bool
    {
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        return $contentType === '' || str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml+xml');
    }

    /**
     * Determina si la ruta actual corresponde al panel privado, autenticación o vista previa del constructor.
     */
    private function requiereNonceDinamico(Request $request): bool
    {
        return $request->is(...self::DYNAMIC_NONCE_PATTERNS);
    }

    /**
     * Establece una cabecera solo si no ha sido definida previamente por el controlador.
     */
    private static function setHeaderIfMissing(Response $response, string $header, string $value): void
    {
        if ($value !== '' && !$response->headers->has($header)) {
            $response->headers->set($header, $value);
        }
    }
}
