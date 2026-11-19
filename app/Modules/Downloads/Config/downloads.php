<?php

declare(strict_types=1);

/**
 * Configuración autónoma y portable del Módulo de Descargas.
 *
 * Ningún valor de infraestructura, ruta base, límite de seguridad o moneda
 * está hardcodeado en la lógica de negocio; todo se lee desde este archivo
 * para facilitar la exportación directa del módulo a otros portales CMS.
 */
return [
    // Prefijo de rutas del panel de administración (ej. /panel/descargas)
    'admin_prefix' => env('DOWNLOADS_ADMIN_PREFIX', 'panel/descargas'),

    // Rol requerido para administrar el módulo y previsualizar en modo privado
    'admin_role' => env('DOWNLOADS_ADMIN_ROLE', 'admin'),

    // Prefijo técnico para los endpoints de entrega binaria firmada (excluido de Inertia SPA y caché Redis)
    'delivery_prefix' => env('DOWNLOADS_DELIVERY_PREFIX', 'download-vault'),

    // Valores iniciales por defecto al instalar el módulo en un portal nuevo
    'defaults' => [
        'page_title'                 => env('DOWNLOADS_DEFAULT_TITLE', 'Descargas'),
        'slug'                       => env('DOWNLOADS_DEFAULT_SLUG', 'descargas'),
        'description'                => env('DOWNLOADS_DEFAULT_DESCRIPTION', 'Repositorio oficial de plugins, elementos y recursos descargables.'),
        'icon'                       => env('DOWNLOADS_DEFAULT_ICON', 'pi pi-download'),
        // Por defecto solo visible en backend / administradores hasta que se publique explícitamente
        'public_visibility'          => env('DOWNLOADS_DEFAULT_VISIBILITY', 'admin_only'),
        'default_view_mode'          => 'grid',
        'allow_view_toggle'          => true,
        'cols_desktop'               => 3,
        'cols_tablet'                => 2,
        'cols_mobile'                => 1,
        'gap_size'                   => 'md',
        'header_align'               => 'left',
        'per_page'                   => 12,
        'show_quick_download'        => true,
        'show_downloads_count_public'=> false,
        'currency'                   => env('DOWNLOADS_DEFAULT_CURRENCY', 'EUR'),
    ],

    // Configuración de la bóveda privada de almacenamiento (siempre fuera de public/)
    'vault' => [
        'disk'              => env('DOWNLOADS_VAULT_DISK', 'downloads_vault'),
        'root'              => env('DOWNLOADS_VAULT_ROOT', storage_path('app/private/downloads')),
        'max_upload_kb'     => (int) env('DOWNLOADS_MAX_UPLOAD_KB', 102400), // 100 MB
        'allowed_extensions'=> ['zip'],
        'allowed_mimes'     => [
            'application/zip',
            'application/x-zip-compressed',
            'multipart/x-zip',
        ],
    ],

    // Límites de inspección interna de paquetes ZIP (defensa contra Zip Slip y Zip Bombs)
    'zip_security' => [
        'max_entries'            => (int) env('DOWNLOADS_ZIP_MAX_ENTRIES', 3000),
        'max_uncompressed_bytes' => (int) env('DOWNLOADS_ZIP_MAX_UNCOMPRESSED', 524288000), // 500 MB
        'max_compression_ratio'  => (int) env('DOWNLOADS_ZIP_MAX_RATIO', 100),
    ],

    // Seguridad de entrega, anti-hotlinking, rendimiento binario y retención RGPD
    'delivery' => [
        'signed_url_ttl_minutes'   => (int) env('DOWNLOADS_SIGNED_TTL_MINUTES', 5),
        'rate_limit_per_minute'    => (int) env('DOWNLOADS_RATE_LIMIT_PER_MIN', 15),
        'hotlink_protection'       => (bool) env('DOWNLOADS_HOTLINK_PROTECTION', true),
        'bind_signature_to_client' => (bool) env('DOWNLOADS_BIND_SIGNATURE_CLIENT', true),
        'x_accel_redirect_prefix'  => env('DOWNLOADS_X_ACCEL_PREFIX', null),
        'log_retention_days'       => (int) env('DOWNLOADS_LOG_RETENTION_DAYS', 180),
    ],

    // Nombres de tablas de base de datos (parametrizables para evitar colisiones en otros portales)
    'tables' => [
        'settings' => env('DOWNLOADS_TABLE_SETTINGS', 'download_settings'),
        'items'    => env('DOWNLOADS_TABLE_ITEMS', 'download_items'),
        'logs'     => env('DOWNLOADS_TABLE_LOGS', 'download_logs'),
    ],

    // Slugs reservados del sistema con los que el catálogo público no puede colisionar
    'reserved_slugs' => [
        'panel', 'acceso', 'login', 'logout', 'register', 'forgot-password',
        'reset-password', 'confirm-password', 'password', 'verify-email',
        'auth', 'oauth', 'perfil', 'media', 'api', 'storage', 'up', 'sanctum',
        'buscar', 'pagina', 'articulos', 'formularios', 'whatsapp', 'llamar',
        'telefono', 'email', 'mail', 'download-vault',
    ],
];
