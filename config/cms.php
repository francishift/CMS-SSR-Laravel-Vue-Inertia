<?php

/**
 * Configuración general del CMS.
 *
 * Valores por defecto que se pueden sobreescribir desde
 * la tabla site_settings o el archivo .env.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Plantillas de Categoría Disponibles
    |--------------------------------------------------------------------------
    |
    | Cada plantilla corresponde a un componente Vue en
    | resources/js/Components/Templates/CategoryTemplate{Nombre}.vue
    |
    */
    'plantillas' => [
        'grid' => [
            'nombre' => 'Cuadrícula',
            'descripcion' => 'Tarjetas en columnas responsive (2-4 por fila)',
            'componente' => 'CategoryTemplateGrid',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paginación por defecto
    |--------------------------------------------------------------------------
    */
    'paginacion' => [
        'articulos_por_pagina' => 12,
        'categorias_por_pagina' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caché
    |--------------------------------------------------------------------------
    |
    | TTL en segundos para las distintas cachés del CMS.
    | Se usa Redis con tags para invalidación selectiva.
    |
    */
    'cache' => [
        'site_settings_ttl' => 86400,   // 24 horas
        'menus_ttl'         => 3600,    // 1 hora
        'categorias_ttl'    => 3600,    // 1 hora
        'articulos_ttl'     => 1800,    // 30 minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de campos extra permitidos en categorías
    |--------------------------------------------------------------------------
    |
    | Tipos de campo que el admin puede definir en el esquema
    | de campos extra de una categoría. Cada tipo se renderiza
    | con su componente correspondiente en el formulario del artículo.
    |
    */
    'tipos_campo_extra' => [
        'text'      => 'Texto corto',
        'textarea'  => 'Texto largo',
        'wysiwyg'   => 'Editor enriquecido (HTML)',
        'number'    => 'Número',
        'select'    => 'Selector (opciones)',
        'boolean'   => 'Sí / No (Interruptor)',
        'date'      => 'Fecha',
        'datetime'  => 'Fecha y Hora',
        'time'      => 'Hora',
        'image'     => 'Imagen (Biblioteca de Medios)',
        'video'     => 'Vídeo (Biblioteca de Medios)',
        'url'       => 'Enlace URL',
        'email'     => 'Correo electrónico',
        'color'     => 'Color (Hexadecimal)',
        'icon'      => 'Icono (PrimeIcons)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Estados de Artículo
    |--------------------------------------------------------------------------
    */
    'estados_articulo' => [
        'draft'     => 'Despublicado',
        'published' => 'Publicado',
        'scheduled' => 'Programado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fuentes Locales Disponibles
    |--------------------------------------------------------------------------
    |
    | Fuentes instaladas localmente via npm (@fontsource/*).
    | Se pueden usar tanto para encabezados como para texto normal.
    | Para añadir una nueva fuente:
    |   1. npm install @fontsource/nombre-fuente
    |   2. Importarla en resources/css/app.css
    |   3. Añadirla a esta lista
    |
    */
    'fuentes_disponibles' => [
        'Hanken Grotesk' => [
            'nombre' => 'Hanken Grotesk',
            'familia' => "'Hanken Grotesk', system-ui, sans-serif",
            'paquete' => '@fontsource/hanken-grotesk',
            'estilo' => 'Sans-Serif Moderna'
        ],
        'Inter' => [
            'nombre' => 'Inter',
            'familia' => "'Inter', system-ui, sans-serif",
            'paquete' => '@fontsource/inter',
            'estilo' => 'Sans-Serif de Interfaz'
        ],
        'Lato' => [
            'nombre' => 'Lato',
            'familia' => "'Lato', sans-serif",
            'paquete' => '@fontsource/lato',
            'estilo' => 'Sans-Serif Elegante'
        ],
        'Plus Jakarta Sans' => [
            'nombre' => 'Plus Jakarta Sans',
            'familia' => "'Plus Jakarta Sans', sans-serif",
            'paquete' => '@fontsource/plus-jakarta-sans',
            'estilo' => 'Sans-Serif Contemporánea'
        ],
        'Outfit' => [
            'nombre' => 'Outfit',
            'familia' => "'Outfit', sans-serif",
            'paquete' => '@fontsource/outfit',
            'estilo' => 'Geométrica para Encabezados'
        ],
        'Roboto' => [
            'nombre' => 'Roboto',
            'familia' => "'Roboto', sans-serif",
            'paquete' => '@fontsource/roboto',
            'estilo' => 'Sans-Serif Neogótica'
        ],
        'Lora' => [
            'nombre' => 'Lora',
            'familia' => "'Lora', serif",
            'paquete' => '@fontsource/lora',
            'estilo' => 'Serifa Editorial'
        ],
        'Playfair Display' => [
            'nombre' => 'Playfair Display',
            'familia' => "'Playfair Display', serif",
            'paquete' => '@fontsource/playfair-display',
            'estilo' => 'Serifa de Alta Gama'
        ],
        'Open Sans' => [
            'nombre' => 'Open Sans',
            'familia' => "'Open Sans', sans-serif",
            'paquete' => '@fontsource/open-sans',
            'estilo' => 'Sans-Serif Neutral Humanista'
        ],
    ],
];
