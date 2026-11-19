# Suite de Pruebas Automatizadas y Cobertura de Testing

## 1. Descripción de la Suite de Pruebas

El proyecto **CMS SSR** mantiene una suite automatizada de pruebas unitarias y de integración desarrollada en PHPUnit / Laravel Testing. 

Todas las pruebas están diseñadas para ejecutarse tanto en entornos locales como en pipelines de integración continua (CI/CD), asegurando la calidad del código, la estabilidad de los contratos API y la seguridad en el control de acceso.

---

## 2. Comandos de Ejecución

### Ejecución estándar vía Artisan:
```bash
php artisan test
```

### Ejecución directa vía PHPUnit:
```bash
vendor/bin/phpunit
```

> **Nota de entorno (`phpunit.xml`)**: El archivo `phpunit.xml` define explícitamente `<ini name="memory_limit" value="512M"/>` para garantizar que la ejecución completa de las 223 pruebas en un único proceso no se interrumpa en servidores donde el `php.ini` de CLI tenga el límite por defecto de `128M` (p. ej. Plesk).

---

## 3. Cobertura y Métricas de Calidad

- **Pruebas Totales**: 242
- **Pruebas Pasadas**: 242 (100% Éxito)
- **Aserciones Ejecutadas**: 1.179
- **Tiempo de Ejecución medio**: ~12 segundos

---

## 4. Estructura y Cobertura por Módulo

### 4.1 Autenticación (`Tests\Feature\Auth\AuthenticationTest`)
- Acceso a pantalla de Login.
- Autenticación correcta con credenciales válidas.
- Rechazo de inicio de sesión con contraseña incorrecta.
- Cierre de sesión (`Logout`) e invalidación del token de sesión.
- **Middleware `VerificarUsuarioActivo`**: Verifica que usuarios con `active = false` sean expulsados automáticamente al intentar acceder a cualquier ruta protegida.

### 4.2 Gestión de Usuarios (`Tests\Feature\Controllers\UserControllerTest` & `Tests\Unit\Services\UserServiceTest`)
- **Index**: Renderiza el listado paginado para administradores. Rechaza el acceso a usuarios no administradores (403 Forbidden).
- **Create & Store**: Valida campos obligatorios y formato de email. Permite contraseña personalizada o genera temporal, asigna el rol y envía notificación de bienvenida por email.
- **Edit & Update**: Actualiza datos de usuario, encripta nueva contraseña opcionalmente si es proporcionada y sincroniza roles mediante `syncRoles`.
- **Toggle Active**: Cambia el estado activo/inactivo. Verifica que un administrador NO pueda desactivar su propia cuenta (`RuntimeException`).
- **Destroy (Soft Delete)**: Realiza borrado suave enviando al usuario a la papelera. Verifica que un administrador NO pueda enviar a la papelera su propia cuenta (`RuntimeException`).
- **Trashed (Papelera)**: Renderiza el listado de usuarios eliminados suavemente (`esPapelera = true`).
- **Restore (Restaurar)**: Restaura un usuario previamente en la papelera devolviéndole su acceso.
- **ForceDelete (Borrado Definitivo)**: Elimina de forma permanente al usuario de la base de datos de manera irreversible.

### 4.3 Verificación de Email y Contraseñas
- `EmailVerificationTest`: Valida generación y consumo de rutas firmadas temporales para verificación de correo.
- `PasswordUpdateTest`: Actualización segura de contraseña con verificación de hash actual.
- `PasswordConfirmationTest`: Confirmación de contraseña previa a acciones sensibles.
- `ProfileTest`: Modificación de datos del perfil propio y eliminación de cuenta de usuario con aserción de soft delete.

### 4.4 Gestión de Artículos (`Tests\Feature\Controllers\ArticleControllerTest`)
- **Creación sin cuerpo**: Permite almacenar artículos con `body = null` de forma segura.
- **Creación con cuerpo**: Almacena y procesa contenido HTML enriquecido.
- **Actualización flexible**: Actualiza artículos dejando el cuerpo vacío o modificando sus campos.

### 4.5 Plantillas del Page Builder (`Tests\Feature\Controllers\BuilderTemplateTest`)
- **CRUD de Plantillas**: Listado, creación, actualización de layout y duplicación.
- **Resolución de Muestra por Categoría**: Verifica que el maquetador visual seleccione automáticamente como muestra un artículo de la categoría asignada a la plantilla en lugar de un artículo aleatorio reciente de otra categoría.
- **Renderizado Clásico vs Plantilla**: Verifica la alternancia condicional entre el layout del builder y la vista pública clásica.

### 4.6 Canales de Contacto Directo y Escudo Anti-Bot (`Tests\Feature\Controllers\PublicContactRedirectControllerTest`)
- **Email Directo (`/email`)**: Valida redirección inmediata hacia `mailto:...` con cabeceras `X-Robots-Tag: noindex, nofollow` y `Cache-Control: no-store, no-cache`, sin pantallas intermedias ni exposición de correo en HTML plano.
- **Teléfono Directo (`/llamar`)**: Valida redirección hacia el esquema `tel:...`.
- **WhatsApp Inteligente (`/whatsapp`)**:
  - **Escritorio**: Valida redirección hacia `https://api.whatsapp.com/send?...` para permitir al navegador detectar la aplicación WhatsApp Desktop o continuar en WhatsApp Web.
  - **iOS**: Valida redirección hacia protocolo nativo `whatsapp://send?...`.
  - **Android**: Valida redirección hacia intent nativo `intent://send?...`.

### 4.7 Persistencia e Integridad del Page Builder (`Tests\Feature\PageBuilderGalleryPersistenceTest`)
- **Galería con Múltiples Imágenes**: Valida que elementos galería en modo manual con múltiples ítems persistan correctamente en base de datos (`content.mode = 'manual'`, `content.items` con sus URLs, metadatos y orden).
- **Protección contra Arrays Vacíos**: Valida que elementos recibidos con `content = []` vacío sean normalizados automáticamente a objetos asociativos íntegros sin pérdida de propiedades.
- **Persistencia en Plantillas y Páginas**: Comprueba el guardado tanto en `PageBuilderController` como en `TemplateBuilderController`.

### 4.8 Blindaje de URLs Duplicadas y Redirecciones Canónicas (`Tests\Feature\SeoCanonicalAndDuplicateUrlsTest`)
- **Canonicidad en Artículos**: Valida que `/{categoria}/{articulo}` responda con HTTP 200 y canonical idéntico, y que accesos por `/articulos/{slug}` o con categoría incorrecta emitan HTTP 301 a la URL canónica.
- **Normalización de Paginación**: Valida que `/pagina/1`, `/?page=1`, `/{cat}/pagina/1` y `/{cat}?page=1` redirijan con HTTP 301 a la raíz, y que páginas secundarias redirijan parámetros query hacia URLs amigables (`/pagina/X`).
- **Defensa contra Páginas Vacías**: Comprueba que solicitudes de paginación superiores al total real (`page > lastPage`) devuelvan HTTP 404.
- **Buscador Interno**: Verifica canonical fijo en `/buscar` y directiva `noindex, follow`.

### 4.9 Grafo Schema.org Unificado (`Tests\Feature\SeoUnifiedJsonLdTest`)
- **Estructura `@graph` Unificada**: Valida la renderización de un único bloque `<script type="application/ld+json">` con `@context: https://schema.org` y `@graph`.
- **Interconexión de Nodos**: Verifica la presencia y vinculación de `Organization`, `WebSite`, `WebPage`/`Article`/`CollectionPage`/`SearchResultsPage` y `BreadcrumbList`.

### 4.10 Control de Indexación y Atributos Nofollow (`Tests\Feature\SeoRobotsAndMenuNofollowTest`)
- **Directivas `meta_robots`**: Valida almacenamiento, actualización e inyección en SSR de `meta_robots` en páginas, categorías y artículos.
- **Atributo `is_nofollow` en Menús**: Verifica el renderizado de `rel="nofollow"` en enlaces de navegación configurados.

### 4.11 Sincronización de Colecciones de Medios (`Tests\Feature\MediaCollectionFilterTest`)
- **Actualización Reactiva de Colecciones**: Valida que al quitar la colección al único archivo que la poseía, la respuesta de la API omita inmediatamente dicha colección y el index no la ofrezca.
- **Eliminación Unitaria y Masiva**: Comprueba que al eliminar archivos (vía `destroy` o `bulkDelete`), las colecciones devueltas por el servidor reflejen en tiempo real el estado limpio de la base de datos sin etiquetas huérfanas.

### 4.12 Cabeceras HTTP de Seguridad y CSP Estricta (`Tests\Unit\Support\CspInlineScriptsTest` & `Tests\Feature\SecurityHeadersTest`)
- **Integridad de Hashes SHA-256 y Variantes de Tema Inicial**: Verifica matemáticamente que las 5 variantes de tema público (`light`, `dark` y `user_choice` con tema inicial `light`, `dark` o `system`) resueltas por `CspInlineScripts::publicThemeScript()` coincidan byte a byte con los hashes emitidos en `script-src`.
- **Estrategia Híbrida Hashes vs Nonces**: Valida que las rutas públicas cacheadas en Redis empleen hashes estáticos sin `nonce` (preservando la caché de 24 h) y que `/panel*` y `/acceso*` generen un `nonce` criptográfico único por petición.
- **Orígenes Dinámicos Mínimos**: Comprueba que Cloudflare Turnstile (`challenges.cloudflare.com`), Google Analytics y Google Maps solo se expongan en CSP cuando la integración correspondiente y sus formularios asociados están realmente activos.

### 4.13 Caché Redis Pública y Normalización de Campañas (`Tests\Feature\PublicResponseCacheTest`)
- **Normalización de Parámetros de Marketing**: Valida que URLs con `utm_*`, `fbclid`, `gclid` o `srsltid` compartan exactamente la misma clave canónica en Redis sin alterar parámetros funcionales (`?q=...`, `?orden=...`).
- **Aislamiento de HTML Compartido**: Comprueba que un `MISS` inicial originado desde un enlace con `?fbclid=...` limpie el `Request` antes de almacenar el HTML en Redis (cero filtración de IDs de clic ajenos en `data-page`) y sirva `HIT (Redis RAM)` inmediato a las siguientes visitas con o sin parámetros de campaña.

### 4.14 Módulo Autónomo de Descargas y Seguridad de Bóveda (`Tests\Unit\Downloads\DownloadVaultAndStrategyTest` & `Tests\Feature\Downloads\DownloadsModuleFeatureTest`)
- **Bóveda Privada (`DownloadVaultService`)**: Valida almacenamiento aislado fuera de `public/` con extensión `.bin`, verificación real de *Magic Bytes* (`finfo`), rechazo de ataques *Zip Slip* (`../` traversal) y *Zip Bombs*, y cálculo de huella `SHA-256`.
- **Autorización y Visibilidad (`StandardDownloadAccessStrategy`)**: Comprueba ocultación por defecto (`admin_only` -> HTTP 404 para anónimos, previsualización `noindex` para administradores), modalidades `free` / `registered` / `paid` y CRUD completo con papelera, purgado físico en `forceDelete` y reordenación Drag & Drop.
- **Anti-Hotlinking, Firma Ligada a IP y Telemetría Diferida**: Verifica que peticiones externas (`Sec-Fetch-Site: cross-site` / `Referer` externo) redirijan a la ficha SEO canónica, que una URL firmada temporal consumida desde otra IP reciba `403 Forbidden`, que las descargas no alteren `updated_at` y que `model:prune` limpie `download_logs` antiguos preservando `downloads_count`.
- **Constructor Visual (Portada + Ficha Individual) y Schema.org**: Valida maquetación en Page Builder (`/panel/descargas/builder` y `/panel/descargas/item-builder`), resolución de tokens `{download.*}`, integración con menús (`type = 'downloads'`), `sitemap.xml` y grafo `SoftwareApplication` + `Offer` enriquecido (`license`, `softwareRequirements`, `releaseNotes`).

### 4.15 Renderizado SSR y Metadatos SEO (`Tests\Feature\SsrRenderingSeoTest`)
- Verifica la integridad de metadatos `<title>`, `<meta name="description">`, `<link rel="canonical">` y compatibilidad de renderizado SSR con `@inertiajs/vue3` v2 y `@vue/server-renderer`.

### 4.16 Centro de Actividad, Telemetría IP y Auditoría Spatie (`Tests\Feature\ActivityCenterTest`)
- **Control de Acceso Estricto**: Verifica que solo `role:admin` acceda a `/panel/actividad` y a los endpoints de purgado (`403 Forbidden` para otros roles).
- **Telemetría de IPs e Históricos Perpetuos**: Comprueba que cada descarga almacene la `ip_address` real en `download_logs` e incremente `downloads_count` y `total_downloads_all_time` mediante `app()->terminating()`, y que purgar el 100 % de los logs detallados conserve intactos ambos contadores históricos.
- **Auditoría Limpia con `spatie/laravel-activitylog` v5.1**: Verifica el registro de cambios (`attribute_changes`) con exclusión garantizada de `password`, `google_maps_api_key` y `layout_json`.

### 4.17 Escala Visual de Títulos en Páginas Básicas y Altura Dinámica de Logotipo (`Tests\Feature\PageTitleSizeTest` & `Tests\Feature\SiteSettingLogoHeightTest`)
- **Tamaño Visual del `<h1>` en Páginas Estáticas (`title_size`)**: Valida el valor por defecto (`default`), la actualización y exposición en `PageResource` e Inertia (`Public/Page`) de escalas tipográficas (`h1`..`h6`, `hero_xl`, etc.) y el rechazo de escalas no permitidas.
- **Altura del Logotipo en Escritorio y Móvil (`logo_height` y `logo_height_mobile`)**: Verifica persistencia en `SiteSetting`, validación de rangos (`16–120 px` escritorio y `16–80 px` móvil), exposición en `siteSettings` e inyección crítica SSR de `--logo-height` y `--logo-height-mobile` en `<head>`.



