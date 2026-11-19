<?php

/**
 * Configuración centralizada de cabeceras HTTP de seguridad y Content Security Policy (CSP).
 *
 * Permite ampliar orígenes de terceros mediante variables de entorno separadas por comas
 * (CSP_EXTRA_SCRIPT_SRC, CSP_EXTRA_FRAME_SRC, CSP_EXTRA_CONNECT_SRC, CSP_EXTRA_STYLE_SRC)
 * sin modificar el código fuente cuando se integren nuevos widgets externos.
 */
$parseExtraOrigins = static function (?string $raw): array {
    if (empty($raw)) {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw))));
};

return [
    /*
    |--------------------------------------------------------------------------
    | Activación global de cabeceras de seguridad
    |--------------------------------------------------------------------------
    */
    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cabeceras HTTP defensivas estándar
    |--------------------------------------------------------------------------
    | X-Frame-Options se mantiene en SAMEORIGIN para proteger contra Clickjacking
    | sin bloquear los iframes internos de vista previa del Page Builder.
    | HSTS viene desactivado por defecto en PHP porque el proxy inverso Nginx/Plesk
    | ya inyecta Strict-Transport-Security en el borde (evita duplicar la cabecera).
    */
    'hsts_enabled' => env('SECURITY_HSTS_ENABLED', false),
    'hsts_value' => env('SECURITY_HSTS_VALUE', 'max-age=31536000; includeSubDomains'),
    'x_content_type_options' => 'nosniff',
    'x_frame_options' => 'SAMEORIGIN',
    'referrer_policy' => 'strict-origin-when-cross-origin',
    'cross_origin_opener_policy' => 'same-origin-allow-popups',
    'x_permitted_cross_domain_policies' => 'none',
    'permissions_policy' => implode(', ', [
        'accelerometer=()',
        'autoplay=(self)',
        'camera=()',
        'display-capture=()',
        'encrypted-media=(self)',
        'fullscreen=(self)',
        'geolocation=(self)',
        'gyroscope=()',
        'magnetometer=()',
        'microphone=()',
        'midi=()',
        'payment=()',
        'picture-in-picture=(self)',
        'publickey-credentials-get=()',
        'sync-xhr=()',
        'usb=()',
        'xr-spatial-tracking=()',
    ]),

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    | Las directivas base contienen únicamente los orígenes propios del núcleo del CMS.
    | Los servicios opcionales de terceros (Cloudflare Turnstile, Google Analytics 4
    | y Google Maps JavaScript API) se declaran en 'integrations' y SecurityHeaders
    | los habilita automáticamente solo cuando están configurados y activos en cada
    | instalación del CMS.
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),

        // Orígenes base permitidos para ejecución de JavaScript externo (núcleo CMS + Leaflet libre)
        'script_src' => array_merge([
            "'self'",
            'https://unpkg.com/leaflet@1.9.4/',
        ], $parseExtraOrigins(env('CSP_EXTRA_SCRIPT_SRC'))),

        // Orígenes base permitidos para hojas de estilo (PrimeVue 4 y variables CSS requieren 'unsafe-inline')
        'style_src' => array_merge([
            "'self'",
            "'unsafe-inline'",
            'https://unpkg.com/leaflet@1.9.4/',
        ], $parseExtraOrigins(env('CSP_EXTRA_STYLE_SRC'))),

        // Imágenes: tiles de mapas, avatares OAuth, media local, data URIs y SVGs
        'img_src' => [
            "'self'",
            'data:',
            'blob:',
            'https:',
        ],

        // Tipografías locales auto-hospedadas (@fontsource / PrimeIcons)
        'font_src' => [
            "'self'",
            'data:',
        ],

        // Conexiones XHR / Fetch base (Inertia SPA y geocodificación libre OpenStreetMap)
        'connect_src' => array_merge([
            "'self'",
            'https://nominatim.openstreetmap.org',
            'https://*.tile.openstreetmap.org',
            'https://*.basemaps.cartocdn.com',
        ], $parseExtraOrigins(env('CSP_EXTRA_CONNECT_SRC'))),

        // Iframes embebidos estándar permitidos (vista previa interna, mapas iframe gratuitos y vídeos)
        'frame_src' => array_merge([
            "'self'",
            'https://www.google.com',
            'https://maps.google.com',
            'https://www.openstreetmap.org',
            'https://www.youtube.com',
            'https://www.youtube-nocookie.com',
            'https://player.vimeo.com',
        ], $parseExtraOrigins(env('CSP_EXTRA_FRAME_SRC'))),

        // Vídeo y audio HTML5 nativos (ElementVideo MP4 streaming local y orígenes HTTPS)
        'media_src' => [
            "'self'",
            'blob:',
            'https:',
        ],

        // Web Workers utilizados por motores de mapas vectoriales (WebGL)
        'worker_src' => [
            "'self'",
            'blob:',
        ],

        // Integraciones opcionales activadas dinámicamente según la configuración de la instancia CMS
        'integrations' => [
            'turnstile' => [
                'script_src' => ['https://challenges.cloudflare.com'],
                'connect_src' => ['https://challenges.cloudflare.com'],
                'frame_src' => ['https://challenges.cloudflare.com'],
            ],
            'google_analytics' => [
                'script_src' => ['https://www.googletagmanager.com'],
                'connect_src' => [
                    'https://www.google-analytics.com',
                    'https://*.google-analytics.com',
                    'https://*.analytics.google.com',
                    'https://www.googletagmanager.com',
                ],
            ],
            'google_maps' => [
                'script_src' => [
                    'https://maps.googleapis.com',
                    'https://maps.gstatic.com',
                ],
                'style_src' => ['https://fonts.googleapis.com'],
                'font_src' => ['https://fonts.gstatic.com'],
                'connect_src' => [
                    'https://maps.googleapis.com',
                    'https://maps.gstatic.com',
                ],
            ],
        ],
    ],
];
