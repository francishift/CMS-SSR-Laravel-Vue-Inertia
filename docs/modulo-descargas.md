# Módulo Autónomo de Descargas y Distribución de Software (`App\Modules\Downloads`)

El módulo de **Descargas** proporciona un repositorio completo de distribución de paquetes instalables, plugins, extensiones y recursos digitales integrado con **Inertia SSR**, el **Constructor Visual (Page Builder)**, la **caché Redis de página completa** y **Schema.org**, manteniendo una arquitectura **100 % desacoplada, portable a otros portales CMS y preparada para monetización futura**.

---

## 1. Arquitectura Modular y Principios SOLID

Todo el dominio reside de forma autocontenida bajo `app/Modules/Downloads/` y se conecta al núcleo del CMS mediante contratos explícitos (Inversión de Dependencias):

```text
app/Modules/Downloads/
├── Config/downloads.php                 # Parámetros de rutas, bóveda, límites ZIP, anti-hotlink y tablas
├── Contracts/
│   ├── DownloadAccessDecision.php       # Value Object inmutable de autorización
│   ├── DownloadAccessStrategyInterface.php
│   └── DownloadSlugHandlerInterface.php
├── Database/Migrations/                 # Migraciones autónomas cargadas desde el ServiceProvider
├── Http/
│   ├── Controllers/
│   │   ├── AdminDownloadController.php
│   │   ├── AdminDownloadBuilderController.php
│   │   ├── DownloadFileDeliveryController.php
│   │   └── PublicDownloadSlugHandler.php
│   ├── Requests/                        # Validaciones estrictas (Store/Update Item y Settings)
│   └── Resources/                       # API Resources (Admin, Public y Settings; cero modelos crudos)
├── Models/                              # DownloadSetting, DownloadItem, DownloadLog
├── Observers/DownloadCacheObserver.php  # Invalidación instantánea de Redis y regeneración de sitemap.xml
├── Providers/DownloadsServiceProvider.php
├── Routes/web.php
├── Services/
│   ├── DownloadCatalogService.php       # CRUD, búsqueda FULLTEXT, Drag & Drop atómico y papelera
│   ├── DownloadDeliveryService.php      # Anti-hotlink, URLs firmadas ligadas a IP/sesión y streaming
│   ├── DownloadSeoBridgeService.php     # OpenGraph, Schema.org SoftwareApplication y sitemap.xml
│   └── DownloadVaultService.php         # Bóveda privada fuera de public/, anti-ZipSlip/ZipBomb y SHA-256
└── Strategies/
    └── StandardDownloadAccessStrategy.php
```

### Portabilidad a otros portales
- Ningún dominio, ruta base, rol ni nombre de tabla está hardcodeado: todo se parametriza desde `app/Modules/Downloads/Config/downloads.php` (sobreescribible por `.env`).
- Por defecto, una instalación limpia nace en modo **`public_visibility = 'admin_only'`** (visible y comprobable únicamente por administradores en backend con `noindex, nofollow` y respuesta `404` para visitantes anónimos) hasta que se activa explícitamente la visibilidad pública.

---

## 2. Modelo de Datos e Índices de Alto Rendimiento

### 2.1. `download_settings` (Configuración global del catálogo)
Almacena el título del catálogo (`page_title`), `slug` dinámico de primer nivel (ej. `/descargas`), visibilidad (`admin_only` / `public`), opciones de rejilla/listado, visibilidad del contador público (`show_downloads_count_public`), metadatos SEO y las dos estructuras del Page Builder:
- `use_builder_layout` + `layout_json`: maquetación visual de la portada del catálogo.
- `use_item_builder_layout` + `item_layout_json`: plantilla visual para las fichas individuales.

Se sirve cacheada en memoria (`DownloadSetting::cached()`) e invalida automáticamente su caché y los menús públicos al guardarse.

### 2.2. `download_items` (Recursos descargables)
- **Identidad y editorial**: `title`, `subtitle` (subtítulo `<small>` opcional), `slug` único, `short_description`, `body` (HTML sanitizado anti-XSS), `featured_image` (raster WebP o ilustración vectorial `.svg`) e `icon` (`PrimeIcons`).
- **Metadatos técnicos**: `version`, `compatibility`, `changelog`, `demo_url`, `documentation_url` y `license_type`.
- **Bóveda privada**: `vault_disk`, `private_file_path`, `original_filename`, `mime_type`, `file_size_bytes` y `sha256_hash`.
- **Acceso y monetización**: `access_type` (`free`, `registered`, `paid`), `price_cents`, `compare_at_price_cents`, `currency` y `external_product_id`.
- **Índices optimizados para miles de registros**:
  - `idx_dl_items_status_del_order` (`status`, `deleted_at`, `order`): resuelve listados públicos y administrativos ordenados manualmente sin `Using filesort`.
  - `idx_dl_items_public_catalog` (`status`, `published_at`, `order`) e `idx_dl_items_access_status` (`access_type`, `status`).
  - `ft_dl_items_search`: índice **`FULLTEXT(title, subtitle, short_description)`** en MySQL/MariaDB utilizado automáticamente en `DownloadCatalogService::listarAdmin()` mediante `MATCH(...) AGAINST(... IN BOOLEAN MODE)`.

### 2.3. `download_logs` (Telemetría de IPs para Administración y Purgado Inteligente)
- Registra `download_item_id`, `user_id` (opcional), **`ip_address`** (IP real IPv4/IPv6 de origen, accesible exclusivamente por `role:admin` en el backend), `ip_hash` (HMAC-SHA256 rotativo diario), `user_agent`, `referer_url`, `version_downloaded`, `access_type_snapshot` y `created_at`.
- **Históricos Perpetuos Inmunes a Limpiezas**:
  - **Por cada ítem**: `download_items.downloads_count` conserva el total histórico de cada recurso.
  - **Global del portal**: `download_settings.total_downloads_all_time` conserva el acumulado histórico global incluso si se purgan todos los logs detallados o se elimina definitivamente algún recurso antiguo.
- **Centro de Actividad y Telemetría (`/panel/actividad`)**:
  - Combina en una sola interfaz privada para administradores el **Registro de Descargas e IPs** (con KPIs, barras de ranking histórico por plugin, clic directo sobre una IP para filtrar su actividad, resumen ligero de navegador/SO y selector de purgado manual `> 90d`, `> 30d`, `> 7d` o total) junto con el **Registro de Auditoría del CMS (`spatie/laravel-activitylog` v5.1)** con comparador *Antes vs Después* de campos modificados e inicios de sesión.
- **Doble Purgado Automático (`routes/console.php`)**:
  - `03:30`: `model:prune` sobre `DownloadLog` (`MassPrunable`, retención por defecto 180 días).
  - `03:45`: `activitylog:clean --force` sobre `activity_log` (`ACTIVITYLOG_CLEAN_DAYS`, 180 días por defecto).

---

## 3. Bóveda Privada y Seguridad de Paquetes (`DownloadVaultService`)

1. **Aislamiento total fuera de `public/`**: Los archivos subidos se almacenan en `storage/app/private/downloads/YYYY/MM/{uuid-v4}.bin` con permisos privados. Ninguna petición directa al servidor web puede acceder al binario.
2. **Validación multicapa en subida**:
   - Límite estricto de peso (`DOWNLOADS_MAX_UPLOAD_KB`, por defecto 100 MB) y lista blanca de extensiones.
   - Verificación real de **Magic Bytes** mediante `finfo(FILEINFO_MIME_TYPE)` contra suplantación de cabeceras HTTP.
   - **Inspección profunda de archivos `.zip`**:
     - Bloquea **Zip Slip** rechazando entradas con `..`, rutas absolutas o bytes nulos (`\0`).
     - Bloquea **Zip Bombs** controlando número máximo de entradas (`3.000`), tamaño descomprimido acumulado (`500 MB`) y ratio anómalo de compresión (`> 100x`).
3. **Huella de integridad `SHA-256`**: Calcula `hash_file('sha256')` en streaming al almacenar cada paquete. En la ficha pública se muestra en una píldora compacta de 1 línea con copia en un clic y se emite también en la cabecera HTTP `X-Checksum-Sha256`, permitiendo verificar que el `.zip` descargado es íntegro y auténtico.
4. **Ciclo de vida limpio**: Reemplazar el `.zip` de una descarga o ejecutar un borrado definitivo (`forceDelete` / vaciar papelera) purga automáticamente el binario antiguo del disco privado; enviarla a la papelera (`SoftDeletes`) conserva el archivo por si se restaura.

---

## 4. Entrega Segura, Anti-Hotlinking y Escalabilidad (`DownloadDeliveryService`)

La descarga nunca apunta a un archivo estático, sino a un flujo de dos fases excluido de Inertia SPA (`RUTAS_NO_INERTIA` en `useUrlHelpers.js`) y de la caché Redis (`PublicResponseCache`):

1. **Fase 1 — Solicitud, Bloqueo Anti-Bots y Escudo Anti-Hotlinking (`GET /download-vault/{slug}/request`)**:
   - **Escudo Anti-Bots / Rastreadores IA (`isAutomatedBotCrawler`)**: Bloquea con `403 Forbidden` y `X-Robots-Tag: noindex, nofollow` a rastreadores automatizados (`ClaudeBot`, `GPTBot`, `meta-externalagent`, `Googlebot`, `AhrefsBot`, `SemrushBot`, etc.) para que indexen libremente las fichas SEO pero jamás descarguen paquetes `.zip` ni distorsionen contadores.
   - Evalúa la estrategia de autorización (`StandardDownloadAccessStrategy`): visibilidad del catálogo, estado de publicación y modalidad (`free`, `registered` con redirección a login, o `paid` preparado para licencias).
   - **Protección Anti-Hotlinking hacia SEO**: Si detecta que el clic proviene de un dominio externo (`Sec-Fetch-Site: cross-site` o `Referer` ajeno al host actual), no entrega el binario directo: redirige (`302`) a la ficha pública canónica `/{catalogo}/{slug}`, transformando el robo de ancho de banda externo en visitas reales hacia la página SEO.
   - Genera una URL temporal firmada con HMAC (`URL::temporarySignedRoute`, validez por defecto de **5 minutos**) e incrusta el parámetro firmado **`ctx`** (huella HMAC corta de `IP + usuario`).

2. **Fase 2 — Transmisión Binaria Blindada (`GET /download-vault/stream/{id}`)**:
   - Verifica firma criptográfica no expirada (`hasValidSignature()`), coincidencia exacta del contexto cliente `IP + sesión` (`hasValidClientContext()`, devolviendo `403 Forbidden` si el enlace temporal se comparte hacia otra IP) y revalida permisos vigentes.
   - Limita ráfagas abusivas mediante `throttle:15,1` configurable (`DOWNLOADS_RATE_LIMIT_PER_MIN`).
   - Emite cabeceras endurecidas: `Content-Disposition: attachment`, `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store, no-cache, must-revalidate` y `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`.
   - **Delegación opcional `X-Accel-Redirect` (Nginx)**: Si se define `DOWNLOADS_X_ACCEL_PREFIX` en `.env`, PHP valida permisos en `< 1 ms` y delega a Nginx la transferencia binaria en C con consumo cero de workers `PHP-FPM`.

3. **Telemetría Diferida sin Contención ni Alteración de `updated_at`**:
   - Tanto el incremento de `downloads_count` como el `INSERT` en `download_logs` (y el incremento de `views_count` en vistas públicas) se ejecutan mediante `dispatch(...)->afterResponse()` tras cerrar el envío HTTP al navegador (`fastcgi_finish_request`) y mediante `DB::table(...)->increment(...)` directo.
   - Esto garantiza **0 ms de espera SQL** al iniciar la descarga y preserva intacta la columna `updated_at` (evitando falsear la fecha de «Última actualización» o el `<lastmod>` del `sitemap.xml`).

---

## 5. Integración Completa con el Constructor Visual (Page Builder)

El módulo ofrece maquetación visual en vivo reutilizando el motor del Page Builder sin duplicar componentes:

### 5.1. Portada del Catálogo (`/panel/descargas/builder`)
- Incluye el origen dinámico **`Catálogo de Descargas`** (`source: 'downloads'`) en el elemento **Cuadrícula Dinámica (`ElementGridArticles`)**, renderizando tarjetas `<DownloadCard>` con soporte de ordenación (`order_asc`, fecha o título) sincronizada con el **Drag & Drop** del panel administrativo (`/panel/descargas`).
- En dispositivos móviles (`< 640px`), el conmutador público Cuadrícula/Listado se oculta automáticamente cuando ambas vistas convergen a una sola columna vertical.

### 5.2. Plantilla de Ficha Individual (`/panel/descargas/item-builder`)
- **Arquitectura híbrida 8/12 + Ancho Completo**:
  - **Sección 1**: Se renderiza en la columna principal (`8/12`) junto al bloque lateral fijo **`DownloadDetailAside.vue`** (`4/12`), el cual calcula dinámicamente la altura real del `<header>` mediante `ResizeObserver` para mantenerse siempre fijo a **`header + 20px`** en scroll.
  - **Secciones 2..N**: Cualquier sección adicional añadida debajo en el árbol de *1. Estructura* (como cuadrículas de *Otros recursos relacionados*, formularios o llamadas a la acción) se renderiza a **ancho completo** debajo del bloque de 2 columnas, tanto en el lienzo del Builder como en la vista pública SSR (`Show.vue`), sin bloques fijos ocultos fuera del editor.
  - Los elementos `Grid de Descargas` insertados dentro de la plantilla de ficha excluyen automáticamente el recurso actual (`excludeDownloadId`).
- **Selector de Muestra y Edición Rápida**: La cabecera del Builder incorpora el selector `Muestra:` para alternar en tiempo real entre cualquier descarga publicada/borrador y acceso directo a `Editar textos / ZIP`, permitiendo además navegar de forma fluida haciendo clic en enlaces del menú o tarjetas dentro del iframe.
- **Variables Dinámicas `{download.*}`** (`DynamicTagSelector.vue` + `PageBuilderResolverService::resolveForDownload()`):
  - `{download.title}`, `{download.subtitle}`, `{download.short_description}`, `{download.body}`, `{download.changelog}`, `{download.featured_image}` (con miniatura real resuelta en el inspector de imagen y soporte nativo para `.svg` con filtro modo oscuro), `{download.icon}`, `{download.version}`, `{download.compatibility}`, `{download.price}`, `{download.file_size}`, `{download.license}`, `{download.demo_url}`, `{download.documentation_url}`, `{download.url}`, `{download.download_url}`, `{download.catalog_title}`, `{download.catalog_url}` y `{download.breadcrumb}` (ruta padre limpia `Inicio / Catálogo` sin repetir el título del ítem).

---

## 6. SEO Técnico, Grafo Schema.org, Caché Redis y Menús

1. **Grafo JSON-LD Unificado (`DownloadSeoBridgeService`)**:
   - **Portada**: Inyecta nodo `CollectionPage` + `ItemList` enlazado a `#website`, `#organization` y `#breadcrumb`.
   - **Ficha Individual**: Inyecta nodo **`SoftwareApplication`** con `Offer` (`0.00 EUR` en recursos gratuitos o importe real en recursos de pago), `softwareVersion`, `fileSize`, `softwareRequirements`, `author` (`#organization`), `license` (normalizada a su URL oficial canónica para GPL-2.0, GPL-3.0 o MIT), `releaseNotes`, `datePublished`, `dateModified` y `BreadcrumbList` completo de 3 niveles para Google Search Console.
   - **Redes Sociales (`OpenGraph` / `X Cards`)**: Si `featured_image` es un icono `.svg`, el puente SEO recurre automáticamente a `seo_og_image` rasterizada para garantizar previsualizaciones válidas en WhatsApp, LinkedIn y X.
2. **Sitemap XML y `robots.txt`**:
   - Extensor registrado mediante `SeoService::registerSitemapExtender()` que añade las URLs públicas solo cuando `public_visibility === 'public'` y sin `noindex`.
   - `public/robots.txt` incluye `Disallow: /download-vault/` para preservar presupuesto de rastreo (*crawl budget*).
3. **Caché Redis (`PublicResponseCache` + `DownloadCacheObserver`)**:
   - Las páginas públicas del catálogo y fichas se sirven desde la RAM de Redis en `< 0.5 ms` y se invalidan al instante al crear, editar, reordenar o borrar cualquier descarga o ajuste.
   - El prefijo `download-vault` (y cualquier valor personalizado en `config('downloads.delivery_prefix')`) está excluido explícitamente de `PublicResponseCache`.
4. **Menús y Dashboard**:
   - El editor de menús (`/panel/menus`) incorpora el tipo nativo `Catálogo de Descargas` (`type = 'downloads'`), que resuelve dinámicamente la URL vigente aunque cambie el `slug` en ajustes.
   - El panel principal (`/panel`) muestra una tarjeta resumen con total de recursos publicados, descargas acumuladas y acceso rápido a `+ Nueva Descarga`.

---

## 7. Evolución hacia Descargas de Pago (Preparado sin Refactorización)

Para activar la venta comercial de plugins o recursos en el futuro:
1. Cambiar `access_type` del ítem a `paid` e indicar `price_cents` y `currency` en el formulario administrativo (ya operativos en BD, validaciones, tarjetas y Schema.org `Offer`).
2. Implementar una clase que cumpla `DownloadAccessStrategyInterface` comprobando la licencia o pedido pagado del usuario (ej. Stripe / LemonSqueezy / Woo-bridge) y enlazarla en `DownloadsServiceProvider::register()` sustituyendo `StandardDownloadAccessStrategy` sin modificar controladores, vistas ni la bóveda de archivos.
