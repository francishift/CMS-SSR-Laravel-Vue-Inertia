# Historial de Versiones (Changelog)

Todos los cambios notables de **CMS SSR (Laravel + Vue + Inertia)** se documentan en este archivo siguiendo [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

### Nuevas Funcionalidades y Mejoras de UI / UX
- **Fondos, Medios, Overlays y Contrastes Independientes Modo Claro / Modo Oscuro en Secciones (`BuilderSection` & `SectionInspector`)**:
  - Nuevas pestañas **`Claro` / `Oscuro`** en el bloque *Estilos Visuales, Fondos y Capas* del inspector de sección (`bg_dark_mode: 'inherit' | 'custom'`), permitiendo configurar de forma 100% independiente en Modo Oscuro imagen (PC/Tablet/Móvil), vídeo HTML5 + póster, color/gradiente personalizado, capa de superposición (`bg_overlay_dark`) y color de texto forzado (`text_color_dark`), además de contraste dual para menú en cabecera transparente (`transparent_header_nav_color_dark`).
  - **Cero Parpadeo en SSR (`0 ms FOUC`) y Cero Descargas Innecesarias**: resolución puramente declarativa en CSS (`app.css`) anclada a `html:not(.dark)` y `html.dark` (`builder-media-only-light`, `builder-media-only-dark`, `--sec-bg-light/dark`, `--sec-overlay-light/dark`), combinada con `<link rel="preload" fetchpriority="high">` condicionado por `prefers-color-scheme` en la primera sección para proteger el LCP en Googlebot y PageSpeed.
  - **Conmutador Claro / Oscuro en Vivo en el Maquetador**: nuevo botón rápido en `BuilderHeader` y sincronización automática del lienzo `BuilderPreviewFrame` (`CMS_BUILDER_SET_THEME` / `CMS_PREVIEW_THEME_CHANGED`) al alternar pestañas en el inspector, más poda automática de claves oscuras en `PageBuilderResolverService::limpiarLayoutParaPublico()` cuando la sección hereda el modo claro.
- **Control de Tamaño del Logotipo en Escritorio y Móvil (`logo_height` y `logo_height_mobile`)**:
  - Nuevos deslizadores independientes en **Configuración → Identidad** (`16–100 px` escritorio y `16–64 px` móvil/cajón lateral) con previsualización en tiempo real para modo claro y oscuro.
  - Inyección crítica SSR (`--logo-height` y `--logo-height-mobile`) sin saltos de layout (`CLS = 0`), alturas mínimas elásticas (`min-h-*`) en las 4 disposiciones de `PublicHeader` y reducción proporcional automática en modo `shrink_on_scroll`.
- **Selector de Tamaño Visual del Título en Páginas Básicas (`title_size`)**:
  - Permite elegir en `Paginas/Create` y `Paginas/Edit` la escala tipográfica visual (`OPCIONES_TAMANO_VISUAL`) para páginas estáticas sin Page Builder (ej. *Política de privacidad* y *Política de cookies*), conservando el `<h1>` semántico para SEO.

### Correcciones, Rendimiento Multimedia y Seguridad CSP
- **Estabilidad de Vídeo HTML5, Galerías Ligeras y Entrega Stateless de Imágenes (`ElementVideo`, `ElementGallery`, `/media/render`)**:
  - **`ElementVideo.vue`**: acotado el overlay *"Cargando vídeo..."* exclusivamente a la carga previa al primer fotograma (`!hasReadyFrame`), suprimiendo falsos disparos por `stalled`/`seeking` en redes lentas o bucles; añadido `autoplay` cooperativo tras completar las imágenes prioritarias (`loading="eager"`, máx. `550 ms`) y clave `:key` por artículo en `Public/Article.vue` para abortar descargas `206 Range` en vuelo durante navegaciones SPA.
  - **`ElementGallery.vue` y `ResponsiveImage.vue`**: optimizado el `srcset` en carruseles infinitos (`[320, 480, 640, 960]` con `:sizes="responsiveSizes"`, ~60 % menos peso por miniatura) y añadida verificación reactiva de caché al mutar `props.src`.
  - **Ruta `GET /media/render` sin bloqueo de sesión (`withoutMiddleware('web')`) y limpieza de diferidos en `PublicPageController`**: las variantes WebP se sirven de forma *stateless* sin bloqueo `StartSession` ni consultas de usuario, y las vistas de artículo maquetadas con plantillas del Builder omiten llamadas `Inertia::defer` redundantes (`relacionados`, `navegacion`).
  - **Preload LCP Inteligente en `BuilderSection.vue`**: cuando `bg_dark_mode` es `'custom'` y el sitio opera en `'user_choice'`, se omite el `<link rel="preload">` especulativo en `<head>` para evitar advertencias de consola y descargas innecesarias si `localStorage` difiere de `prefers-color-scheme`.
- **Renderizado SSR Íntegro y Seguro de `custom_css` en todo el Page Builder (`sanitizeInlineCss`)**:
  - Sustituido `v-text` por `v-html` sanitizado con `sanitizeInlineCss()` (`useSanitize.js`) en los 14 componentes estructurales y modulares del Builder, evitando que `@vue/server-renderer` escape selectores CSS hijos (`>` -> `&gt;`) o comillas en el HTML SSR inicial y neutralizando cierres `</style>` contra inyección XSS.
- **Sincronización Anti-FOUC de `frontend_default_theme` en `<head>` (`CspInlineScripts`)**:
  - Añadidas las variantes `PUBLIC_THEME_USER_CHOICE_LIGHT`, `PUBLIC_THEME_USER_CHOICE_DARK` y `PUBLIC_THEME_USER_CHOICE_SYSTEM` junto con `CspInlineScripts::publicThemeScript()` y sus hashes `SHA-256` en `publicHashes()`, eliminando cualquier destello de tema en primeras visitas tanto en páginas públicas como en `404.blade.php`.
- **Suite de Pruebas**: ampliada a **248 pruebas automatizadas (1.231 aserciones)**.

---

## [v2.0.0] — 2026-11-17

### Nuevos Módulos y Funcionalidades Principales
- **Módulo Autónomo de Descargas (`App\Modules\Downloads`)**:
  - Arquitectura desacoplada y portable (`DownloadsServiceProvider`, contratos SOLID `DownloadSlugHandlerInterface` y `DownloadAccessStrategyInterface`), con visibilidad privada por defecto (`admin_only`) y preparada para monetización futura (`free`, `registered`, `paid`).
  - **Bóveda Privada Segura (`DownloadVaultService`)**: almacenamiento aislado fuera de `public/` con extensión `.bin`, verificación real de *Magic Bytes* (`finfo`), inspección interna contra *Zip Slip* y *Zip Bombs*, y cálculo de huella `SHA-256`.
  - **Entrega Firmada Anti-Hotlinking y Anti-Bots (`DownloadDeliveryService`)**: URLs firmadas efímeras ligadas a `IP + sesión`, redirección SEO ante enlaces externos (*hotlinking*), bloqueo de rastreadores automatizados en rutas binarias y soporte opcional `X-Accel-Redirect` (Nginx).
  - **Integración Total con Page Builder**: maquetación visual en vivo tanto para la portada del catálogo como para la plantilla de ficha individual (`{download.*}`), bloque lateral fijo (`header + 20px`), secciones inferiores a ancho completo y reordenación Drag & Drop sincronizada con Redis.
  - **SEO `SoftwareApplication` + `CollectionPage`**: grafo Schema.org JSON-LD enriquecido (`Offer`, `license`, `softwareRequirements`, `releaseNotes`), integración con menús y extensor automático de `sitemap.xml`.
- **Centro de Actividad, Telemetría IP y Auditoría (`spatie/laravel-activitylog` v5.1)**:
  - Panel exclusivo para administradores (`/panel/actividad`) combinando telemetría de direcciones IP de descargas (con históricos perpetuos globales y por recurso inmunes a limpiezas) y auditoría administrativa con comparador *Antes vs Después* e inicios/fallos de sesión.
  - Purgado manual selectivo y tareas programadas diarias (`model:prune` y `activitylog:clean`).
- **Módulo de Formularios Dinámicos, Mapas Duales y Autenticación Google OAuth**:
  - Constructor de formularios incrustables en Page Builder con bandeja de envíos, notificaciones de correo y protección anti-spam Cloudflare Turnstile.
  - Elementos geoespaciales con carga diferida para Leaflet (OpenStreetMap) y Google Maps (API Key cifrada `AES-256` en base de datos).
  - Inicio de sesión opcional con Google OAuth (`laravel/socialite`) y copias de seguridad automatizadas hacia Google Drive (`spatie/laravel-backup`).

### Seguridad, Rendimiento y SEO Técnico
- **Actualización de Stack a PHP 8.4+, Laravel 13.x, Vite 8 y Vue 3.5+**:
  - Compatibilidad SSR completa con `@vue/server-renderer >= 3.5.40` e `@inertiajs/vue3` v2 sin fugas de memoria.
  - Separación de bundle JS público (~190 KB menos de JS al cargar `PrimeVue` y `Ziggy` bajo demanda solo en administración).
- **Cabeceras HTTP de Seguridad A+ y Content Security Policy (CSP) Híbrida**:
  - Middleware global `SecurityHeaders` con hashes criptográficos `SHA-256` estáticos para páginas públicas en Redis RAM y `nonces` dinámicos en rutas privadas, eliminando `'unsafe-inline'` en `script-src`.
- **Caché Redis de Respuesta Completa (`PublicResponseCache`) Mejorada**:
  - Normalización automática de parámetros de campañas publicitarias (`utm_*`, `fbclid`, `gclid`, `srsltid`) para máxima tasa de acierto en RAM (`< 0.5 ms`) y TTL dinámico sensible a publicaciones programadas.
- **Blindaje SEO Canónico y Grafo `@graph` Unificado**:
  - Eliminación de URLs duplicadas en paginación y categorías mediante redirecciones `301`, soporte completo de `meta_robots` por entidad, atributos `nofollow` en menús y escudo anti-scraping en canales nativos de contacto (`/whatsapp`, `/llamar`, `/email`).
- **Suite de Pruebas Ampliada**:
  - Cobertura ampliada a **237 pruebas automatizadas (1.118 aserciones)** en PHPUnit 12.

---

## [v1.0.0] — Lanzamiento Inicial

- Arquitectura base **CMS SSR** con Laravel 13, Vue 3, Inertia.js v2, Tailwind CSS v4 y PrimeVue v4.
- Gestión de Usuarios y Roles (`spatie/laravel-permission`), Categorías con campos extra, Artículos programables, Páginas estáticas, Menús, Biblioteca de Medios con optimización WebP y primera versión del Constructor Visual (Page Builder).
