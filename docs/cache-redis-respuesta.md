# Sistema de Caché de Respuesta Redis e Invalidación Instantánea

Este documento detalla la arquitectura, funcionamiento e invalidación del sistema de caché de respuestas en memoria RAM mediante **Redis** para la web pública del CMS SSR.

---

## 1. Objetivo y Arquitectura

Para permitir que el CMS atienda **más de 1.000 peticiones simultáneas por segundo (RPS)** sin saturar la base de datos MySQL ni el servidor monohilo SSR de Node.js, se ha implementado un sistema de **Full Response Cache en Redis**.

```
[ Petición HTTP Pública GET ]
             │
             ▼
 ┌──────────────────────┐
 │ PublicResponseCache  │ ──(¿Está autenticado o es /panel?)──> [ No Caché / Ejecutar Controller ]
 └──────────────────────┘
             │
             ▼ (Invitado / Ruta Pública)
 ┌──────────────────────┐
 │  Consulta Redis RAM  │ ──(HIT en Redis: < 0.5ms)───────────> [ Retornar HTML / JSON Directo ]
 └──────────────────────┘
             │
             ▼ (MISS en Redis)
 ┌──────────────────────┐
 │  Ejecutar Controller │ ──> [ Guardar Respuesta en Redis ] ──> [ Retornar al Cliente ]
 └──────────────────────┘
```

---

## 2. Componentes del Sistema

### 2.1 Middleware `App\Http\Middleware\PublicResponseCache`

Ubicado en `app/Http/Middleware/PublicResponseCache.php` y registrado al inicio del stack web en `bootstrap/app.php`:

* **Rutas incluidas**: Únicamente peticiones `GET` y `HEAD` públicas anónimas (`/`, `/{categoria}`, `/{articulo}`, etc.).
* **Exclusiones estrictas**: 
  * Rutas que comienzan por `panel`, `acceso`, `login`, `logout`, `register`, `password`, `perfil`, `media`, `up`, `sanctum` y canales puente de contacto (`whatsapp`, `llamar`, `telefono`, `email`, `mail`).
  * Usuarios autenticados (administradores y redactores) y peticiones parciales diferidas (`X-Inertia-Partial-Data`).
* **Normalización Anti-Fragmentación para Campañas (`normalizarUrlParaCache`)**:
  * Al calcular la clave `md5()`, descarta automáticamente parámetros efímeros de seguimiento publicitario y analítico (`utm_*`, `hsa_*`, `fbclid`, `gclid`, `dclid`, `gbraid`, `wbraid`, `msclkid`, `twclid`, `ttclid`, `li_fat_id`, `mc_cid`, `mc_eid`, `yclid`, `igshid`, `mkt_tok`, `srsltid`, `_gl`, `_ga`, `_gac`, `_hsenc`, `_hsmi`, `ref_src`, `ref_url`), mientras **preserva y ordena alfabéticamente** los parámetros funcionales reales (`?q=...`, `?orden=...`, etc.).
  * En caso de fallo de caché (`MISS`), `limpiarQueryTrackingEnRequest()` limpia dichos parámetros efímeros del `Request` antes de renderizar Inertia, garantizando que el HTML compartido en Redis almacene siempre un `initialPage.url` canónico libre de identificadores de clic ajenos.
  * En el navegador (`resources/js/app.js`), al hidratar rutas públicas se restaura `window.location.search` sobre `initialPage.url`, permitiendo que cientos de visitantes simultáneos procedentes de LinkedIn, Meta o Google Ads compartan un único `HIT (Redis RAM)` sin perder la atribución analítica en GA4.
* **Clave de Caché en Redis**: Generada con el hash `md5()` del esquema, URL canónica normalizada y tipo de petición (`X-Inertia` JSON vs HTML SSR).
* **Etiqueta Redis (`Tags`)**: Guardada bajo la etiqueta `public_pages` de Redis (`REDIS_CACHE_DB`).
* **Duración (`TTL`) Inteligente**: Por defecto 24 horas (86.400 segundos), ajustada dinámicamente según fechas programadas (ver Sección 3).

### 2.2 Servicio de Invalidación `App\Services\CacheInvalidationService`

Ubicado en `app/Services/CacheInvalidationService.php`:

* Ejecuta `Cache::store('redis')->tags(['public_pages'])->flush()` para vaciar las respuestas cacheadas en Redis en 1 sola orden atómica.
* Vacía también la caché de configuración global de `SiteSetting`.

### 2.3 Observador de Modelos `App\Observers\CmsCacheObserver`

Ubicado en `app/Observers/CmsCacheObserver.php` y registrado en `AppServiceProvider.php` para los modelos:

* `App\Models\Article`
* `App\Models\Category`
* `App\Models\Page`
* `App\Models\Menu`
* `App\Models\MenuItem`
* `App\Models\SiteSetting`
* `App\Models\Form`

**Eventos escuchados**: `saved`, `deleted`, `restored`, `forceDeleted`.

### 2.4 Invalidación en Despliegues y Compilaciones (`npm run build`)

Al ejecutar `npm run build`, el pipeline incluye automáticamente `php artisan cache:clear`. Esto asegura que todas las páginas HTML cacheadas en Redis con rutas a archivos estáticos (hashes de CSS/JS de Vite) se purguen inmediatamente, evitando errores 404 en los navegadores de los usuarios tras un nuevo despliegue.

---

## 3. Caché Sensible al Tiempo y TTL Dinámico (Time-Aware Caching)

Para soportar de forma nativa la **publicación programada (`published_at`)** y la **despublicación por fecha de caducidad (`published_until`)** sin retrasos y sin requerir procesos cron en bucle cada minuto:

### 3.1 Cálculo Dinámico del TTL (`calcularTtl()`)

En `PublicResponseCache.php`, antes de persistir la respuesta en Redis, el middleware consulta de forma atómica el instante del evento futuro más cercano:

1. **Próxima publicación programada**: `Article::where('status', 'scheduled')->where('published_at', '>', now())->min('published_at')`.
2. **Próxima expiración de artículo**: `Article::whereNotNull('published_until')->where('published_until', '>', now())->min('published_until')`.
3. Si existe algún evento programado en las próximas 24 horas, el TTL de la clave de Redis se reduce al **número exacto de segundos que faltan para dicho instante**:
   $$\text{TTL} = \max\left(1, \min\left(86400, \text{segundos hasta el próximo cambio}\right)\right)$$

### 3.2 Ciclo de Expiración Atómico en Redis

```
┌─────────────────────────────────────────────────────────────┐
│ 12:19:30  Visitante entra a la web                         │
│           - Artículo programado para caducar a las 12:21:00 │
│           - Faltan 90 segundos -> Redis guarda TTL = 90s   │
├─────────────────────────────────────────────────────────────┤
│ 12:20:15  Nuevas visitas reciben la copia en RAM (< 0.5ms)  │
├─────────────────────────────────────────────────────────────┤
│ 12:21:00  Redis expira automáticamente la clave en memoria  │
├─────────────────────────────────────────────────────────────┤
│ 12:21:01  Siguiente visita genera MISS, consulta MySQL,    │
│           aplica scopePublicados (excluye artículo)         │
│           y guarda la nueva página en Redis.               │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Garantía de Refresco Instantáneo por Edición Manual

### ¿Cómo se garantiza que las ediciones manuales se reflejen de inmediato?

1. Un administrador crea, edita o borra un artículo, página, menú o configuración en el panel privado.
2. Eloquent dispara automáticamente el evento correspondiente (`saved`, `deleted`, etc.).
3. `CmsCacheObserver` captura el evento e invoca a `CacheInvalidationService::limpiarCachePublica()`.
4. Redis vacía en **0.1 milisegundos** las claves de la etiqueta `public_pages`.
5. El siguiente visitante que entra a la web pública recibe la versión **fresca y actualizada**.

---

## 5. Verificación y Diagnóstico

Puedes comprobar el estado de la caché inspeccionando las cabeceras HTTP de respuesta:

```bash
curl -s -D - https://midominio.com/ -o /dev/null
```

### Cabeceras de Respuesta:

* `x-response-cache: HIT (Redis RAM)` $\rightarrow$ La respuesta se entregó desde la memoria RAM de Redis en < 0.5ms.
* `x-response-cache: MISS (Stored in Redis)` $\rightarrow$ La respuesta fue generada por PHP/SSR, entregada al cliente y almacenada en Redis para las siguientes peticiones.

---

## 6. Pruebas de Invalidez en Consola

Puedes simular un cambio desde Tinker para comprobar la invalidación en tiempo real:

```bash
php artisan tinker --execute="\App\Models\Page::first()?->touch();"
```

Al volver a ejecutar `curl`, verás la transición automática: `HIT` $\rightarrow$ `MISS` $\rightarrow$ `HIT`.
