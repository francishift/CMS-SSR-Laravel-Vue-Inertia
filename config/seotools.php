<?php
/**
 * @see https://github.com/artesaos/seotools
 */

return [
    'inertia' => env('SEO_TOOLS_INERTIA', false),
    'meta' => [
        /*
         * Configuraciones por defecto para la etiqueta meta.
         */
        'defaults'       => [
            'title'        => false,
            'titleBefore'  => false,
            'description'  => false,
            'separator'    => ' — ',
            'keywords'     => [],
            'canonical'    => false,
            'robots'       => 'index, follow',
        ],
        'webmaster_tags' => [
            'google'    => null,
            'bing'      => null,
            'alexa'     => null,
            'pinterest' => null,
            'yandex'    => null,
            'norton'    => null,
        ],

        'add_notranslate_class' => false,
    ],
    'opengraph' => [
        'defaults' => [
            'title'       => env('APP_NAME', 'CMS SSR'),
            'description' => env('APP_NAME', 'CMS SSR'),
            'url'         => null,
            'type'        => 'website',
            'site_name'   => env('APP_NAME', 'CMS SSR'),
            'images'      => [],
        ],
    ],
    'twitter' => [
        'defaults' => [
            'card' => 'summary_large_image',
        ],
    ],
    'json-ld' => [
        'defaults' => [
            'title'       => env('APP_NAME', 'CMS SSR'),
            'description' => env('APP_NAME', 'CMS SSR'),
            'type'        => 'WebSite',
            'images'      => [],
        ],
    ],
];
