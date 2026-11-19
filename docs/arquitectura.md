# Arquitectura del Sistema

## 1. Patrones de Diseño y Principios SOLID

La aplicación **CMS Base de Usuarios** está diseñada siguiendo una arquitectura limpia (Clean Architecture), altamente cohesiva y de bajo acoplamiento:

### 1.1 Single Responsibility Principle (SRP)
- **Controllers Delgados (`Thin Controllers`)**: Los controladores en `app/Http/Controllers/` se limitan a orquestar las peticiones HTTP y retornar respuestas Inertia. Ninguna lógica de persistencia ni reglas de negocio reside en los controladores.
- **Servicios Dedicados (`Services`)**: La lógica de negocio pesada y compleja reside en clases de servicio dedicadas dentro de `app/Services/` (ej. `UserService`, `ArticleService`, `SeoService`, `MediaImageService`, `MediaCleanupService`, `CacheInvalidationService`).
- **Form Requests (`Http/Requests`)**: Las validaciones de datos entrantes están 100% aisladas en clases `StoreUserRequest`, `UpdateUserRequest`, `StoreCategoryRequest`, `UpdateCategoryRequest`, `UpdateSiteSettingRequest`, `LoginRequest`, etc.
- **Transformación de Datos (`API Resources / DTOs`)**: Las respuestas hacia el frontend se transforman a través de `UserResource`, `ArticleResource`, `CategoryResource`, `PageResource`, `MediaFileResource`, garantizando que nunca se expongan modelos Eloquent crudos ni columnas sensibles (`password`, `remember_token`).

### 1.2 Open/Closed Principle (OCP)
- **Sistema de Roles Extensible**: La gestión de roles mediante `spatie/laravel-permission` y el composable `useRoles.js` permite añadir nuevos roles o permisos sin alterar el código existente de los controladores ni de las vistas.

### 1.3 Dependency Inversion Principle (DIP) y Extensiones Modulares Autónomas
- Inyección de dependencias en constructores de controladores y servicios (ej. `public function __construct(protected readonly UserService $userService)`).
- **Módulos Autocontenidos (`app/Modules/`)**: Las extensiones desacopladas como el [Módulo de Descargas](modulo-descargas.md) (`App\Modules\Downloads`) encapsulan sus propias migraciones, rutas, configuración, modelos, API Resources y servicios, conectándose al núcleo exclusivamente mediante interfaces (`DownloadSlugHandlerInterface`, `DownloadAccessStrategyInterface`) registradas en su propio `ServiceProvider`.

### 1.4 Componentes Atómicos y Composables Vue (Page Builder & Media)
- **Extracción de Estado en Composables (`usePageBuilder.js`)**: Toda la reactividad, mutaciones del árbol JSON, peticiones Axios diferidas de previsualización y side-effects de autoguardado se aíslan en composables reutilizables.
- **Inspectores Atómicos Reutilizables (`Inspectors/`)**: Descomposición de la interfaz del maquetador en subcomponentes de inspección dedicados (`SectionInspector`, `RowInspector`, `ColumnLayoutInspector`, `ColumnInspector`, `ElementHeadingInspector`, `ElementTextInspector`, `ElementImageInspector`, `ElementVideoInspector`, `ElementButtonInspector`, `ElementIconInspector`, `ElementPaginationInspector`, `ElementGridInspector`, `ElementAccordionInspector`, `ElementMenuInspector`, `ElementSocialInspector`, `ElementHtmlInspector`), garantizando que la vista principal (`resources/js/Pages/Paginas/Builder.vue`) actúe únicamente como un orquestador limpio y desacoplado de menos de 150 líneas.
- **Gestor de Medios Integrado (`MediaPickerModal.vue`)**: Componente modal reutilizable para selección y subida de archivos en caliente desde cualquier formulario.

---

## 2. Aislamiento de Almacenamiento y Servidores (Redis & DB)

### 2.1 Aislamiento de Redis
En servidores compartidos (ej. Plesk / cPanel), donde múltiples aplicaciones hacen uso del mismo servidor de Redis en `127.0.0.1:6379`, se ha implementado un esquema de aislamiento riguroso en `.env` y `config/database.php`:
- **Instancia Base de Datos Principal**: `REDIS_DB=4`
- **Instancia Base de Datos de Caché**: `REDIS_CACHE_DB=5`
- **Prefijo de Claves Global (`REDIS_PREFIX`)**: `cms_ssr_`
- **Prefijo de Caché (`CACHE_PREFIX`)**: `cms_ssr_`

Esto evita la contaminación cruzada de claves de caché, sesiones y colas entre distintos sitios web alojados en el mismo servidor.

### 2.2 Optimización de Base de Datos MySQL y Sincronización Horaria
- **Eager Loading Anti N+1**: Las consultas de listados cargan explícitamente sus relaciones necesarias (ej. `User::with('roles')`, `Article::with(['category', 'author'])`).
- **Índices de Alto Rendimiento**: Migraciones con índices simples y compuestos en los campos `email`, `active`, `category_id`, `status`, `mime_type`, `folder` y el índice compuesto temporal `['status', 'published_at', 'published_until']` para realizar búsquedas instantáneas entre miles de registros.
- **Sincronización Dinámica de Zona Horaria**: En `config/database.php`, las conexiones `mysql` y `mariadb` inyectan el offset horario calculado (`env('DB_TIMEZONE', (new DateTime('now', new DateTimeZone(env('APP_TIMEZONE', 'Europe/Madrid'))))->format('P'))`), garantizando que `NOW()` en el motor SQL opere en el mismo huso horario que la aplicación PHP y el frontend, evitando desfases en publicaciones programadas o con fecha de fin.
- **Paginación Server-Side**: Uso obligatorio de `paginate(20)` para evitar desbordamientos de memoria al cargar grandes volúmenes de datos.

---

## 3. Infraestructura y Seguridad en Producción

### 3.1 Proxy Inverso (Nginx + Apache en Plesk)
En entornos donde Nginx gestiona las conexiones SSL (puerto 443) y proxifica internamente hacia Apache/PHP en HTTP, se han aplicado los siguientes mecanismos de seguridad:
- **HTTPS Forzado**: En `AppServiceProvider::boot()`, se evalúa el entorno y la URL de aplicación para forzar el esquema `https://` vía `URL::forceScheme('https')`. Esto garantiza que los enlaces, archivos estáticos de Vite y payloads de Inertia se sirvan con protocolo seguro `https://`, eliminando errores de contenido mixto (Mixed Content).
- **Manejo de Handler de Errores para Plesk**: En `AppServiceProvider::register()`, se intercepta el error handler para suprimir advertencias de `open_basedir` generadas por herramientas del entorno sin afectar al resto de excepciones de Laravel.

### 3.2 Cabeceras HTTP de Seguridad y Content Security Policy (CSP)
- **Middleware Global (`SecurityHeaders`)**: Ejecutado en `prepend` global (por delante de `PublicResponseCache`) y en el manejador de excepciones HTTP, inyecta `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Cross-Origin-Opener-Policy: same-origin-allow-popups`, `X-Permitted-Cross-Domain-Policies: none` y `Permissions-Policy`, eliminando además `X-Powered-By`.
- **CSP Híbrida sin `'unsafe-inline'` en JavaScript**: Combina hashes criptográficos estáticos `'sha256-...'` (`CspInlineScripts`) para páginas públicas servidas desde la RAM de Redis y `nonces` dinámicos por petición para rutas privadas que inyectan `@routes` de Ziggy. Ver detalle completo en [docs/cabeceras-seguridad-csp.md](cabeceras-seguridad-csp.md).

---

## 4. Arquitectura de Sincronización de Tema (Modo Claro / Oscuro)

Para evitar desincronizaciones entre Tailwind CSS y PrimeVue:
- **Tailwind CSS v4**: En `resources/css/app.css`, se define la variante personalizada `@custom-variant dark (&:where(.dark, .dark *));`. Esto vincula todas las utilidades `dark:` de Tailwind directamente con la presencia de la clase `.dark` en la etiqueta `<html>`.
- **PrimeVue 4**: Configurado con `darkModeSelector: '.dark'`.
- **Scripts de Inicialización Síncrona Desacoplados (Admin vs Frontend)**: En `<head>` de `resources/views/app.blade.php` y `resources/views/errors/404.blade.php`, `CspInlineScripts` inyecta de forma independiente el arranque del panel (`ADMIN_THEME` vía `admin_modo_oscuro`) y el del frontend público (`CspInlineScripts::publicThemeScript($mode, $defaultTheme)` vía `public_theme_mode`), respetando desde el milisegundo cero tanto temas forzados (`light` / `dark`) como la elección del visitante (`user_choice`) con su tema predeterminado inicial (`light`, `dark` o `system`) antes de pintar el DOM y con hashes `'sha256-...'` validados por CSP.

---

## 5. Sistema de Caché de Respuesta HTML en Redis e Invalidación Instantánea

Para garantizar un rendimiento extremo capaz de absorber más de 1.000 peticiones simultáneas por segundo (RPS) en la web pública:

### 5.1 Middleware de Caché de Respuestas (`PublicResponseCache`)
- **Filtro de Peticiones Anónimas**: Intercepta peticiones `GET`/`HEAD` públicas anónimas (`auth()->guest()`) en rutas públicas (`/`, `/{categoria}`, `/{articulo}`).
- **Respuesta desde Redis RAM (< 0.5ms)**: Almacena y sirve directamente la respuesta completa (HTML renderizado por SSR o JSON de Inertia) desde las etiquetas `public_pages` de Redis, omitiendo consultas a MySQL, ejecuciones de Eloquent y renderizados del motor SSR Node.js.
- **Normalización de Parámetros de Campaña**: Ignora parámetros efímeros de marketing (`utm_*`, `fbclid`, `gclid`, `srsltid`, etc.) al generar la clave de caché y limpia el `Request` antes de almacenar un `MISS`, absorbiendo oleadas simultáneas de campañas publicitarias con una única entrada en RAM mientras el cliente (`app.js`) preserva la query real en el navegador para analítica.
- **TTL Dinámico Sensible al Tiempo (Time-Aware Caching)**: Si existen contenidos programados (`published_at`) o con fecha de fin (`published_until`), el middleware ajusta automáticamente el TTL de la clave de Redis al número exacto de segundos que faltan para el próximo cambio de estado, permitiendo que la caché caduque por sí misma en el segundo preciso sin desfase.
- **Cabeceras de Diagnóstico**: Añade la cabecera `X-Response-Cache: HIT (Redis RAM)` en aciertos de caché y `X-Response-Cache: MISS (Stored in Redis)` en fallos.
- **Exclusión de Rutas Privadas y Binarias**: Las rutas `/panel*`, `/acceso*`, `/login*`, `/logout*`, endpoints de entrega firmada de archivos (`/download-vault*`), canales nativos de contacto y peticiones autenticadas nunca se almacenan en caché.

### 5.2 Observadores Eloquent e Invalidación Instantánea (`CmsCacheObserver` & `DownloadCacheObserver`)
- **Sincronización Inmediata en el Panel**: Se registran observadores Eloquent (`Article`, `Category`, `Page`, `Menu`, `MenuItem`, `SiteSetting`, `Form` en `AppServiceProvider::boot()`, y `DownloadItem` + `DownloadSetting` en `DownloadsServiceProvider::boot()`).
- **Invalidación en 1 Comando**: Ante cualquier evento `saved`, `deleted`, `restored` o `forceDeleted` realizado por un administrador, `CacheInvalidationService::limpiarCachePublica()` ejecuta `Cache::store('redis')->tags(['public_pages'])->flush()`.
- **Refresco Garantizado**: Garantiza que los visitantes vean inmediatamente cualquier cambio guardado en el panel de administración en el siguiente milisegundo.
