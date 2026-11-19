# Configuración de SEO, SSR y Rendimiento

Este documento detalla la arquitectura de Server-Side Rendering (SSR), optimización de payloads y SEO del CMS.

## 1. Server-Side Rendering (SSR con Node.js + Inertia v2)

### Entrada SSR (`resources/js/ssr.js`)
- Inicializa la aplicación Vue en servidor usando `createSSRApp` y `renderToString`.
- **Compatibilidad `@inertiajs/vue3` v2 + `@vue/server-renderer >= 3.5.40`**: A partir de Vue `3.5.40`, `@vue/server-renderer` convierte `props.innerHTML` mediante `String(...)` de forma síncrona. Como `@inertiajs/vue3` v2 asigna `innerHTML: render(vueApp)` sin `await`, `ssr.js` pre-resuelve `await renderToString(ssrApp)` dentro de `async setup()` usando un `WeakMap` efímero (liberado al instante tras su lectura). Esto evita que se emita `[object Promise]` en la primera carga, garantiza cero huella de memoria bajo alta concurrencia y asegura que el HTML completo y todas las etiquetas `<Head>` (`@inertiaHead`) lleguen íntegras a los motores de búsqueda.
- Configura `ZiggyVue`, `PrimeVue`, `ToastService` y `ConfirmationService` para prevenir errores de hidratación durante la renderización en servidor.

### Servicio Nativo de Monitorización SSR (`systemd: inertia-ssr.service`)
- En entornos de producción (Linux / Plesk), el servidor Node SSR se administra como un servicio nativo de `systemd` (`/etc/systemd/system/inertia-ssr.service`).
- **Resiliencia 24/7**: Si el servidor se reinicia o el proceso cae por cualquier motivo, `systemd` lo relanza de forma automática e inmediata (`Restart=always`, `RestartSec=1`).
- **Logs centralizados**: La salida estándar y errores se canalizan a `storage/logs/ssr.log` y `storage/logs/ssr-error.log`.

### Pipeline de Despliegue y Compilación Atómica (`package.json`)
El script de compilación `npm run build` asegura un despliegue sin desincronización de versiones ni caídas de servicio:
```bash
vite build && vite build --ssr && php artisan inertia:stop-ssr && php artisan cache:clear && php artisan view:clear
```
1. **Compilación Frontend & SSR**: Vite compila los nuevos chunks con sus respectivos hashes y el bundle de Node SSR en `bootstrap/ssr/ssr.js`.
2. **Reinicio Atómico del SSR**: `php artisan inertia:stop-ssr` detiene el proceso de Node antiguo en memoria. `systemd` detecta el cierre y lo arranca de nuevo en 1 segundo con el código fresco.
3. **Invalidación de Caché Redis**: `php artisan cache:clear` purga la caché de respuestas HTML completas (`PublicResponseCache`), evitando que los navegadores reciban HTML con referencias a archivos CSS/JS obsoletos (errores 404).
4. **Limpieza de Vistas**: `php artisan view:clear` purga la caché de plantillas Blade compiladas.

## 2. Optimización de Payloads en `data-page` y Bundle Público

- **Ocultación de Rutas Privadas**: `HandleInertiaRequests` inyecta las rutas `ziggy` únicamente si la URL pertenece al panel privado (`/panel*`). La web pública recibe `ziggy: null`.
- **Protección de Datos de Usuario y Anti-Scraping de Contacto**: `auth.user` se entrega a `null` en rutas públicas y `siteSettings` omite los valores crudos `whatsapp_phone`, `whatsapp_message`, `contact_email`, `contact_phone` y `google_maps_api_key`, exponiendo únicamente indicadores booleanos (`has_whatsapp`, `has_contact_email`, `has_contact_phone`, `has_google_maps_key`).
- **Eliminación de Payloads Duplicados en Plantillas del Builder**: En categorías y artículos renderizados mediante una plantilla activa del Page Builder (`PublicPageController`), se evita serializar por duplicado la colección `articulos` de la vista clásica o el `body` HTML completo cuando la plantilla ya resuelve sus propios bloques, reduciendo a la mitad el tamaño de `data-page` en listados extensos.
- **Resource de Artículos y Layout Livianos**: Los listados públicos (`PageBuilderResolverService` y `ArticleResource`) excluyen `body` en la consulta SQL, omiten `category.extra_fields_schema` repetido en cada tarjeta y descartan metadatos exclusivos de edición (`admin_label` y campos de código vacíos) en `layout_json`.
- **Separación de Bundle JS Público vs Administración (`resources/js/app.js`)**: En rutas públicas (`Public/*` y `Error`), Vue se hidrata directamente sin descargar ni ejecutar `PrimeVue`, el preset `@primevue/themes/aura` ni `ZiggyVue` (~190 KB menos de JS en el arranque), cargándolos dinámicamente solo en `/panel*`, `/acceso` y vistas previas del maquetador.

## 3. SEO, Metadatos y Auditorías Lighthouse

- **Generación Limpia en HTML Root (`resources/views/app.blade.php`)**:
  - `SEOMeta::generate()`, `OpenGraph::generate()` y `JsonLd::generate()` se renderizan directamente en la cabecera HTML inicial sin minificación agresiva en 1 sola línea, asegurando que `<title>`, `<meta name="description" content="...">`, `<link rel="canonical">`, OpenGraph y Schema.org estén siempre presentes en el DOM para herramientas de auditoría como Lighthouse y rastreadores de motores de búsqueda.
- **Evitado de Duplicidad de Títulos**:
  - `SEOMeta::setTitle($title, false)` desactiva la adición automática de sufijos duplicados del nombre del sitio en backend.
- **Inyección en Componentes Vue (`<Head>`)**:
  - `Public/Page.vue`, `Public/Category.vue` y `Public/Article.vue` incluyen etiquetas `<meta name="description" :content="...">` dentro de `<Head>` para mantener sincronizados los metadatos durante la navegación SPA del cliente.
- **Fallbacks en Cascada (`app/Services/SeoService.php`)**:
  - Si un artículo, categoría o página no especifica metadescripción manual, se extrae un resumen del texto principal y, en última instancia, se recurre a `seo_default_description` o `site_description` de la Configuración Global.
- **Transición SPA sin Saltos**:
  - Transición `.smooth-fade` en `PublicLayout.vue` combinada con `min-h-[calc(100vh-10rem)]` para obtener una puntuación CLS de 0.0 en Google Core Web Vitals.

## 4. Core Web Vitals y Optimización LCP (Largest Contentful Paint)

### 4.1. Descubrimiento de Solicitudes LCP (`lcp-discovery`) y `fetchpriority="high"`
Google Lighthouse evalúa el descubrimiento de la imagen principal (*Hero Image* o fondo de sección) mediante la auditoría `lcp-discovery`:
1. **Visibilidad en el Documento Inicial**: El recurso debe figurar en el HTML inicial entregado por SSR antes de ejecutar JavaScript.
2. **Sin Carga en Diferido**: Los recursos LCP nunca deben llevar `loading="lazy"`.
3. **Prioridad Alta de Red (`fetchpriority="high"`)**: La solicitud debe marcarse con máxima prioridad para que el navegador la descargue sin competir con hojas de estilo o scripts secundarios.

### 4.2. Sincronización de Preload con Media Queries CSS
Cuando el elemento LCP es un fondo CSS (`.builder-bg-layer`), el navegador no puede predecir qué variante descargará la regla CSS si existe discrepancia entre el cálculo de DPR del preload y los breakpoints de pantalla.
- **Solución Implementada**: En `BuilderSection.vue`, se emiten 3 etiquetas `<link rel="preload">` condicionales con atributo `media` idéntico a las reglas `@media` de `app.css`:
  ```html
  <link rel="preload" as="image" href="/media/render?...w=768..." media="(max-width: 767px)" fetchpriority="high">
  <link rel="preload" as="image" href="/media/render?...w=1280..." media="(min-width: 768px) and (max-width: 1023px)" fetchpriority="high">
  <link rel="preload" as="image" href="/media/render?...w=1920..." media="(min-width: 1024px)" fetchpriority="high">
  ```
- **Resultado en Lighthouse y Navegadores**:
  - En smartphones móviles (`< 768px`), el preload descarga la variante de `w=768` con prioridad `High`. Cuando el motor CSS aplica `var(--bg-url-mob)`, la imagen ya está en caché con prioridad alta.
  - Se eliminan las dobles descargas y se superan las tres comprobaciones de la auditoría `lcp-discovery`.

### 4.3. Caché Inmutable de Variantes WebP (`ImageController`)
- Endpoint `/media/render` con cabecera `Cache-Control: public, max-age=31536000, immutable` y `X-Image-Cache: HIT (Disk Cache)`.
- Compresión WebP al 85% de calidad (82% en móvil), reduciendo el peso de la imagen de fondo entre un 91% y un 98% sin pérdida perceptible de calidad visual.
### 4.4. Estrategia de `preconnect` / `dns-prefetch` Condicional

Las directivas `preconnect` (DNS + TCP + TLS) y `dns-prefetch` (solo DNS) aceleran la carga de recursos de terceros al preparar la conexión antes de que el navegador la necesite. Sin embargo, **Lighthouse penaliza los `preconnect` no utilizados** con la auditoría *"Avoid unnecessary preconnect hints"*, por lo que solo se inyectan cuando realmente se van a usar.

#### Estrategia implementada

| Servicio | Directiva | Dónde se inyecta | Condición |
|---|---|---|---|
| **Google Tag Manager** (GA4/GTM) | `preconnect` + `dns-prefetch` | `app.blade.php` (estático) | Solo si `analytics_enabled` y `analytics_measurement_id` están configurados en `SiteSetting` |
| **Google Maps API** | `preconnect` dinámico | `ElementMap.vue` (JS) | Solo cuando la página contiene un bloque de mapa del Page Builder |
| **Cloudflare Turnstile** | `preconnect` dinámico | `ElementForm.vue` (JS) | Solo cuando la página contiene un formulario con captcha habilitado |

- **GTM** es el único servicio que se inyecta estáticamente en la plantilla Blade porque es el más probable en todas las páginas públicas (se activa tras la aceptación de cookies analíticas por el visitante).
- **Maps y Turnstile** se inyectan dinámicamente desde sus componentes Vue justo antes de cargar el script externo, evitando conexiones desperdiciadas en páginas que no los usan.
- **Nunca se hace `preconnect` al propio dominio** (`config('app.url')`), ya que el navegador ya tiene la conexión establecida al dominio desde el que cargó la página.

---


## 5. Canales de Contacto Directo Seguro y Escudo Anti-Scraping (`PublicContactRedirectController`)

Para proteger los números de teléfono y direcciones de correo electrónico de spambots, scrapers y recolectores automáticos de datos sin perjudicar la usabilidad ni el SEO:

### 5.1. Rutas de Escudo Internas
- **WhatsApp**: `/whatsapp` detecta de forma inteligente el dispositivo del usuario:
  - En **móviles**: lanza directamente la aplicación nativa (`whatsapp://` en iOS o `intent://` en Android) manteniendo `target="_self"`, de modo que no abre pestañas en blanco ni pantallas intermedias vacías. Al volver de WhatsApp, el usuario sigue en la página web intacta.
  - En **escritorio**: redirige hacia `https://api.whatsapp.com/send?phone=...&text=...` en nueva pestaña (`_blank`), permitiendo al navegador detectar si WhatsApp Desktop está instalado para abrirlo, o continuar en WhatsApp Web.
- **Llamadas Telefónicas**: `/llamar` y `/telefono` redirigen de forma instantánea al esquema `tel:...` sin exponer el número en el HTML estático.
- **Correo Electrónico**: `/email` y `/mail` redirigen directamente al cliente de correo predeterminado del usuario (`mailto:...`) sin pantallas intermedias redundantes ni exponer el correo en texto plano en el HTML del sitio.

### 5.2. Blindaje para Buscadores y Caché
- Todas las rutas de contacto emiten cabeceras estrictas:
  ```http
  X-Robots-Tag: noindex, nofollow
  Cache-Control: no-store, no-cache, must-revalidate
  ```
- Impide que los rastreadores indexen estas páginas o sigan los enlaces externos de contacto.

---

## 6. Integraciones de Terceros y Seguridad de Credenciales (Google Maps API)

Para garantizar la seguridad de las claves de servicios externos y evitar su dispersión en elementos de maquetación:

### 6.1. Centralización en la Base de Datos
- Las credenciales de terceros no se introducen en cada elemento individual del maquetador, sino en la sección dedicada **Configuración Global > Integraciones** (`/panel/configuracion` > pestaña *Integraciones*).
- Se almacenan en la tabla `site_settings` en la columna `google_maps_api_key`.

### 6.2. Cifrado Criptográfico en Reposo (AES-256)
- En el modelo Eloquent `SiteSetting.php`, el atributo `google_maps_api_key` utiliza el cast nativo `'encrypted'`:
  ```php
  protected function casts(): array
  {
      return [
          // ...
          'google_maps_api_key' => 'encrypted',
      ];
  }
  ```
- La clave se almacena cifrada en la base de datos MySQL con la clave maestra de la aplicación (`APP_KEY`). Un volcado o fuga de base de datos no expone la clave en texto plano.

### 6.3. Validación y Saneamiento
- `UpdateSiteSettingRequest.php` valida la clave con reglas estrictas (máximo 255 caracteres, formato alfanumérico seguro para API Keys).
- Al guardar, se ejecuta `SiteSetting::clearCache()` para invalidar la caché de Redis y refrescar los datos compartidos.

### 6.4. Entrega Bajo Demanda sin Exposición en HTML Inicial
- En rutas públicas, `HandleInertiaRequests.php` nunca incluye `google_maps_api_key` dentro de `$page.props.siteSettings` (solo expone el booleano `has_google_maps_key`), evitando filtrar la credencial en el HTML cacheado de todas las páginas.
- Cuando una página contiene efectivamente un bloque `ElementMap.vue` configurado con Google Maps, el componente solicita la clave bajo demanda al endpoint protegido por *rate-limit* `GET /api/cms/maps-key` (`GoogleMapsKeyController`).

---

## 7. Canonicidad de Dominio y Redirecciones 301 Permanentes

Para evitar la fragmentación de PageRank y el contenido duplicado indexado por los motores de búsqueda:

### 7.1. Versión Canónica Única
El sitio web consolida todo el tráfico bajo una única URL canónica oficial (configurable vía `APP_URL`):
`https://www.tudominio.com`

Cualquier variante alternativa debe resolverse con código de estado HTTP `301 Moved Permanently`:
* `http://tudominio.com` ➔ 301 directo a ➔ `https://www.tudominio.com/`
* `http://www.tudominio.com` ➔ 301 directo a ➔ `https://www.tudominio.com/`
* `https://tudominio.com` ➔ 301 directo a ➔ `https://www.tudominio.com/`

### 7.2. Redirección en un Solo Salto (Single-Hop Redirect)
* Se evita cualquier cadena de redirecciones intermedias (por ejemplo: `http://` ➔ `https://` ➔ `https://www.`), ya que penalizan el TTFB (*Time to First Byte*) y consumen *Crawl Budget*.
* La redirección se ejecuta directamente a nivel de servidor web (Plesk / Nginx / Apache), respondiendo en menos de 5 ms sin arrancar PHP ni Laravel.

---

## 8. Control Fino de Indexabilidad y Enlaces No-Follow

### 8.1. Directiva `meta_robots` por Recurso
Los modelos `Page`, `Article` y `Category` cuentan con el campo `meta_robots` configurable desde sus respectivos formularios de administración:
* `index, follow` (Predeterminado para contenido público relevante).
* `noindex, follow` (Para páginas de agradecimiento, listados filtrados o avisos legales).
* `noindex, nofollow` (Para páginas privadas, utilidades internas o borradores).

El servicio `SeoService.php` inyecta la etiqueta correspondiente en el `<head>` del HTML renderizado por SSR.

### 8.2. Atributo `is_nofollow` en Menús
El modelo `MenuItem` permite marcar individualmente enlaces de navegación con la bandera `is_nofollow`:
* Cuando `is_nofollow = true`, el componente de navegación renderiza el atributo `rel="nofollow"`.
* Permite enlazar a servicios externos, redes sociales o recursos sin transferir autoridad SEO no deseada.

---

## 9. Cookie Consent y Google Analytics 4 (Consent Mode v2)

### 9.1. Arquitectura General
El sistema integra `vanilla-cookieconsent` v3.1 (Orest Bida) con Google Analytics 4, cumpliendo con la normativa GDPR/RGPD y Google Consent Mode v2. Toda la configuración es administrable desde el panel de administración (`Configuración > Integraciones`).

**Flujo de datos:**
```
SiteSetting (DB) → HandleInertiaRequests (props) → PublicLayout.vue (onMounted) → useCookieConsent.js (client-only)
```

### 9.2. Configuración en Base de Datos (`site_settings`)
Campos disponibles:

| Campo | Tipo | Descripción |
|---|---|---|
| `cookie_consent_enabled` | boolean | Activa/desactiva el banner de cookies |
| `cookie_consent_layout` | string | Layout del modal: `box`, `bar`, `cloud` |
| `cookie_consent_position` | string | Posición: `bottom right`, `bottom left`, etc. |
| `cookie_consent_theme` | string | Tema visual: `dark`, `light` |
| `cookie_consent_revision` | integer | Versión de la política (fuerza re-aceptación) |
| `cookie_consent_privacy_url` | string | URL a la política de privacidad |
| `cookie_consent_cookie_url` | string | URL a la política de cookies |
| `cookie_consent_title` | string | Título personalizado del modal |
| `cookie_consent_description` | string | Descripción personalizada del modal |
| `analytics_enabled` | boolean | Activa/desactiva Google Analytics 4 |
| `analytics_provider` | string | Proveedor: `ga4` |
| `analytics_measurement_id` | string | ID de medición GA4 (ej. `G-XXXXXXXXXX`) |
| `analytics_anonymize_ip` | boolean | Anonimizar IP de visitantes |

### 9.3. Composable `useCookieConsent.js` — 100% SSR-Safe
El composable está diseñado para **cero impacto en SSR**:

- **Sin imports estáticos de navegador**: ni `vanilla-cookieconsent` ni `@inertiajs/vue3` se importan a nivel de módulo.
- **Early return en SSR**: `useCookieConsent()` devuelve funciones vacías inmediatamente si `window` no existe.
- **Import dinámico**: la librería y su CSS se cargan bajo demanda solo en el cliente vía `import()`.
- **Estado en `window.__cc*`**: evita variables de módulo compartidas entre peticiones SSR concurrentes.
- **Exclusión del backend**: no se inicializa si la ruta empieza por `/panel/` o si estamos dentro de un iframe (preview del Page Builder).

### 9.4. Google Consent Mode v2
1. **Estado inicial**: todos los tipos de almacenamiento (`analytics_storage`, `ad_storage`, etc.) arrancan como `denied`.
2. **Al aceptar cookies analíticas**: se actualiza a `granted` y se inyecta la etiqueta `gtag.js` dinámicamente.
3. **Al rechazar**: se revoca el consentimiento y se marca `ga-disable-{ID}` como `true`.
4. **Navegación SPA**: cada transición Inertia reporta `page_view` automáticamente vía `router.on('finish')`.

### 9.5. Tema Visual Dinámico
Los botones del banner de cookies se adaptan automáticamente a los colores del tema configurado en el backend:

| Variable CookieConsent | Mapeo al tema del sitio |
|---|---|
| `--cc-btn-primary-bg` | `button_primary_bg` |
| `--cc-btn-primary-color` | `button_primary_text` |
| `--cc-btn-secondary-bg` | `button_secondary_bg` |
| `--cc-btn-border-radius` | `button_border_radius` |
| `--cc-font-family` | `body_font` |
| `--cc-toggle-on-bg` | `primary_color` |

Se inyecta un bloque `<style>` dinámico que soporta modo claro y oscuro con `#cc-main.cc--darkmode`.

### 9.6. Shortcode `{{cookies_button}}`
Para permitir a los editores insertar un botón de "Preferencias de cookies" en cualquier lugar del contenido:

- Escribir `{{cookies_button}}` en cualquier **Elemento de Texto** o **Elemento HTML** del Page Builder.
- El sistema lo reemplaza automáticamente por un botón interactivo con icono 🛡️ que abre el modal de preferencias.
- Se re-procesa tras cada navegación SPA para contenido cargado dinámicamente.

### 9.7. Tests
4 tests de integración en `SiteSettingAnalyticsAndCookieConsentTest.php`:
- Almacenamiento y recuperación de la configuración completa.
- Activación/desactivación parcial de analytics y cookies.
- Validaciones de campos requeridos y formatos.
- Flujo completo de habilitación y posterior desactivación.

---

## 10. Variables CSS Críticas en SSR, Anti-FOUC y Aislamiento del Lienzo de Previsualización (`app.blade.php`)

Para garantizar que la web pública y el lienzo del constructor carguen sin parpadeos visuales (FOUC) y con fidelidad idéntica:

### 10.1. Inyección de Variables Críticas en `:root`
- En `resources/views/app.blade.php`, se inyecta en el `<head>` un bloque `<style id="cms-ssr-critical-vars">` con las variables de color, tipografía y fondos del sitio (`--site-bg-light`, `--site-bg-dark`, `--site-text-light`, `--site-text-dark`, `--bg-image-light`, `--bg-image-dark`).
- **Saneamiento Defensivo SSR y Cliente**: Se aplica `rtrim(..., '; ')` en Blade y `.replace(/;+\s*$/, '').trim()` en `colorUtils.js` (`normalizarBgImage`), garantizando que cualquier punto y coma o espacio sobrante introducido accidentalmente en base de datos no invalide la sintaxis CSS ni provoque que `element.style.setProperty` rechace la regla en el navegador.

### 10.2. Diferenciación de Rutas en Blade
- Las peticiones bajo `/panel*` que corresponden al iframe del Page Builder (`*builder/preview-frame*`) se diferencian explícitamente mediante:
  ```php
  $isBuilderPreview = request()->is('*builder/preview-frame*');
  $isAdminPanel = request()->is('panel*') && !$isBuilderPreview;
  ```
- **Lienzo de Previsualización Fiel**:
  - Recibe el bloque de variables CSS críticas públicas (`--bg-image-dark`, `--site-bg-dark`, etc.) y el script síncrono del tema frontend (`$siteSettingsData->frontend_theme_mode`).
  - Mantiene intacta la seguridad y utilidades internas (`@routes` de Ziggy y `<meta name="robots" content="noindex, nofollow">`).
  - Previene que el tema del panel de administración interfiera en la renderización de la página en edición.

---

## 11. Arquitectura SEO de Paginación Isomórfica y Enrutamiento Canónico

Para maximizar el rastreo de los motores de búsqueda (Googlebot, Bingbot) y prevenir penalizaciones por contenido duplicado o URLs huérfanas:

### 11.1. Estructura de URLs Limpias y Amigables
- **Archivos de Categoría**:
  - Página 1: `https://dominio.com/{categoria}` (URL canónica raíz de la categoría).
  - Páginas sucesivas: `https://dominio.com/{categoria}/pagina/{N}` (donde `N >= 2`).
- **Páginas de Inicio / Catálogo Principal**:
  - Página 1: `https://dominio.com/` o `https://dominio.com/{pagina}`.
  - Páginas sucesivas: `https://dominio.com/pagina/{N}`.

### 11.2. Redirecciones Canónicas 301 Anti-Duplicados
- Si un usuario o rastreador accede con parámetro de query `?page=1` o a la sub-ruta `/pagina/1`, el controlador emite una **redirección permanente HTTP 301** hacia la URL raíz sin número de página.
- Si se solicita con parámetro de query `?page=X` (con `X >= 2`), se redirige con **HTTP 301** hacia la ruta limpia `/pagina/X` o `/{categoria}/pagina/X`.

### 11.3. Metadatos Canónicos y Directivas de Paginación (`SeoService.php`)
- **`<link rel="canonical">`**: Apunta siempre a la URL limpia de la página actual (`/pagina/X` si $X \ge 2$, o la raíz si $X = 1$).
- **Etiquetas de Paginación**: En páginas sucesivas se inyectan automáticamente las relaciones semánticas correspondientes para los rastreadores:
  - `<link rel="prev" href="...">`
  - `<link rel="next" href="...">`
- **Mantenimiento en Navegación SPA**: Al navegar entre páginas con Inertia, el componente `<Head>` sincroniza dinámicamente el canonical y las metaetiquetas sin recargar el navegador.

### 11.4. Desambiguación Estricta: Archivo vs Serie de Artículos
- En un artículo individual (`single_article`), el elemento modular `ElementPagination` distingue contextualmente si se encuentra en una serie de artículos (`is_article_series: true`).
- Cada botón numérico (`1, 2, 3...`) apunta al slug canónico del artículo hermano respectivo (`/{categoria}/{slug-hermano}`).
- Se previene activamente la generación de URLs híbridas o erróneas como `/{categoria}/{articulo}/pagina/2`, preservando la integridad del árbol de indexación SEO.

---

## 12. Prevención Integral de URLs Duplicadas y Redirecciones Canónicas 301

Para cumplir al 100% con las directrices de calidad de Google Search Essentials y prevenir la fragmentación de autoridad o canibalización de palabras clave:

### 12.1. Resolución y Canonicidad de Artículos
- **URL Canónica Oficial**: `https://dominio.com/{categoria}/{articulo}`.
- **Ruta de Acceso Corto `/articulos/{slug}`**:
  - Si un usuario o enlace externo accede mediante `/articulos/{slug}`, el método `PublicPageController::articleBySlug` emite una **redirección permanente HTTP 301** hacia su URL canónica oficial con su categoría correspondiente.
- **Categoría Incorrecta o Desactualizada**:
  - Si se solicita `/{otra-categoria}/{articulo}` donde la categoría no coincide con la del artículo publicado, el controlador `PublicPageController::article` detecta la discrepancia y redirige inmediatamente con **HTTP 301** hacia la URL canónica correcta `/{categoria-real}/{articulo}`.
- **Meta Canonical y Open Graph**: Siempre auto-referenciados a la URL canónica completa de dos niveles.

### 12.2. Blindaje de Paginación y Páginas Vacías
- **Primera Página Canónica**: Cualquier variación tipo `/?page=1`, `/pagina/1`, `/{slug}?page=1` o `/{slug}/pagina/1` se redirige con **HTTP 301** a la URL canónica raíz (`/` o `/{slug}`).
- **Parámetros Query en Páginas Posteriores**: Parámetros tipo `?page=2` se redirigen con **HTTP 301** a `/pagina/2` o `/{slug}/pagina/2`.
- **Límite de Paginación y Error 404 Defensivo**:
  - Si un rastreador solicita una página que supera el total real (`page > lastPage`), el controlador aborta con **código 404** en lugar de servir una página vacía o con contenido residual que pueda indexarse como duplicado.

### 12.3. Exclusividad de la Portada (Homepage)
- El modelo `Page` fuerza automáticamente `slug = ''` para cualquier página configurada con `is_homepage = true`.
- La portada solo se sirve en `/`, evitando la existencia simultánea de URLs duplicadas como `/inicio` y `/`.
- En la generación del `sitemap.xml`, se excluye automáticamente de la lista de páginas estáticas mediante `where('is_homepage', false)`.

### 12.4. Protección del Buscador Interno (`/buscar`)
- **Canonical Estricto**: La metaetiqueta canónica en `/buscar` siempre apunta a `url('/buscar')` fija, sin concatenar parámetros de búsqueda (`?q=...`), evitando que consultas ilimitadas de usuarios generen miles de páginas indexables duplicadas.
- **Directiva Robots**: Emite automáticamente `noindex, follow` en las páginas de resultados de búsqueda para cumplir con las directrices de rastreo eficiente de Google.

---

## 13. Schema.org Estructurado Unificado con `@graph` y Twitter Cards

Para optimizar los Rich Snippets y la comprensión semántica del sitio por parte de Google y redes sociales:

### 13.1. Estructura Unificada en un Solo Bloque `<script type="application/ld+json">`
En lugar de múltiples bloques JSON-LD fragmentados, el CMS genera una única estructura `@graph` consolidada:
- **`Organization`**: Nodo `@id: {dominio}/#organization` con nombre del sitio, logotipo, descripción, enlaces a redes sociales y puntos de contacto.
- **`WebSite`**: Nodo `@id: {dominio}/#website` con nombre, idioma, vinculación a la organización y acción de búsqueda (`SearchAction` con plantilla `/buscar?q={search_term_string}`).
- **`WebPage` / `Article` / `CollectionPage` / `SearchResultsPage`**: Nodo específico según el tipo de página renderizada, vinculado jerárquicamente a `WebSite` (`isPartOf: {dominio}/#website`).
- **`BreadcrumbList`**: Lista correlativa de migas de pan con `@type: ListItem` y posición ordinal (`position: 1, 2, 3...`), enlazada al nodo principal.

### 13.2. Ciclo de Vida y Persistencia en `app.blade.php`
- `SeoService` está registrado como **Singleton** en el contenedor de dependencias (`AppServiceProvider.php`).
- Durante la ejecución del controlador, los métodos `configurarHome()`, `configurarPagina()`, `configurarCategoria()` o `configurarArticulo()` van acumulando los nodos en memoria.
- En la plantilla raíz `resources/views/app.blade.php`, `{!! app(SeoService::class)->renderSchemaJsonLd() !!}` emite el JSON limpio en el `<head>` inicial del SSR.

### 13.3. Twitter Cards Integradas
- Se emiten automáticamente metaetiquetas `twitter:card` (`summary_large_image`), `twitter:title`, `twitter:description`, `twitter:image` y `twitter:site` (extrayendo limpiamente el handle desde las redes sociales configuradas).

---

## 14. Regeneración Reactiva del Sitemap XML (`CmsCacheObserver`) e Imágenes

Para garantizar que los motores de búsqueda descubran instantáneamente nuevos contenidos y actualizaciones sin esperar al cron diario:

### 14.1. Observer Reactivo en Tiempo Real
El observador `app/Observers/CmsCacheObserver.php` escucha los eventos del ciclo de vida de los modelos:
- **Eventos escuchados**: `saved`, `deleted`, `restored`, `forceDeleted`.
- **Modelos monitorizados**: `Article`, `Category` y `Page`.
- **Ejecución Post-Transacción (`DB::afterCommit`)**: La regeneración del archivo `public/sitemap.xml` se ejecuta exclusivamente tras la confirmación atómica en la base de datos MySQL, evitando estados inconsistentes.

### 14.2. Filtrado Inteligente de Contenido y Directivas `noindex`
- Excluye automáticamente cualquier página, categoría o artículo que tenga asignada la directiva `noindex` en su campo `meta_robots`.
- Excluye borradores, contenidos con fecha de publicación futura y artículos expirados.

### 14.3. Soporte para Google Imágenes y Google Discover
- Cada `<url>` generada incluye su nodo `<image:image>` con la URL absoluta y el título del recurso para potenciar la indexación multimedia en Google Imágenes y Discover.

---

## 15. Inyección Crítica de Variables de Diseño y Tipografía SSR (`app.blade.php`)

Para evitar parpadeos visuales (FOUC - *Flash of Unstyled Content*) y saltos de maquetación (CLS) durante la carga inicial en servidor:

### 15.1. Variables CSS Críticas en `:root`
- En `resources/views/app.blade.php`, dentro del bloque `<style id="cms-ssr-critical-vars">:root { ... }</style>`, se inyectan sincrónicamente desde la primera respuesta HTML del servidor:
  - Colores corporativos (`--primary-color`, `--secondary-color`, `--accent-color`, etc.).
  - Tipografía base y fuentes (`--heading-font`, `--body-font`).
  - Escala de cuerpo (`--body-font-size`, `--body-font-weight`, `--body-line-height`, `--body-letter-spacing`).
  - Escalas de encabezados para los 8 niveles (`--{level}-size`, `--{level}-font-weight`, `--{level}-letter-spacing`, `--{level}-line-height` para `hero_2xl`, `hero_xl`, `h1`–`h6`).

### 15.2. Sincronización Backend-Frontend mediante API Resources y Middleware
- **`SiteSettingResource`**: Expone fielmente todas las propiedades de tipografía de cuerpo y encabezados hacia el formulario de `/panel/configuracion`, asegurando la persistencia bidireccional tras guardar y recargar la página.
- **`HandleInertiaRequests`**: Comparte la configuración global en `siteSettings` para que el frontend público y el iframe del constructor visual (`BuilderPreviewFrame`) reactiven las variables en tiempo real sin requerir recargas de página.




