# Centro de Actividad, Telemetría IP de Descargas y Auditoría del CMS (`spatie/laravel-activitylog`)

El **Centro de Actividad** (`/panel/actividad`) centraliza en una única interfaz privada de administración —accesible exclusivamente para el rol **`admin`** (`middleware(['auth', 'role:admin'])`)— dos pilares de control operativo:

1. **Telemetría de Descargas y Direcciones IP (`download_logs` + históricos perpetuos)**.
2. **Auditoría Administrativa y de Seguridad del CMS (`spatie/laravel-activitylog` v5.1)**.

---

## 1. Telemetría de Descargas, IPs y Ranking Perpetuo

### 1.1. Captura Asíncrona post-respuesta (`app()->terminating()`)
En `DownloadDeliveryService::recordDownloadTelemetry()`, cada transmisión autorizada de un paquete desde la bóveda privada programa una rutina de terminación tras cerrar el envío HTTP al navegador:
- Almacena en `download_logs`:
  - `ip_address` (`VARCHAR(45)` indexado, compatible con IPv4 e IPv6, visible únicamente para `admin` en el backend; `bootstrap/app.php` confía en `127.0.0.1`/`::1` para obtener la IP real tras Nginx y marca con insignia `VPN / Interna` las conexiones desde túneles privados como WireGuard).
  - `ip_hash` (huella HMAC-SHA256 con sal rotativa diaria).
  - `user_agent` y resumen ligero de **Navegador + Sistema Operativo** (`DownloadLogResource::resumirAgenteUsuario()`).
  - `referer_url` (dominio/ruta desde donde se originó la descarga).
  - `version_downloaded`, `access_type_snapshot` y `user_id` (si estaba autenticado).
- Incrementa de forma atómica (`DB::table(...)->increment(...)`, sin modificar `updated_at`):
  1. `download_items.downloads_count` (**histórico perpetuo individual** de cada descarga).
  2. `download_settings.total_downloads_all_time` (**histórico perpetuo global** del portal).

### 1.2. Funciones en el Panel (`/panel/actividad?tab=descargas`)
- **KPIs en Tiempo Real**: Histórico Total Global (inmune a limpiezas), descargas de los últimos 30 y 7 días, IPs únicas recientes y volumen actual de registros detallados en `download_logs`.
- **Ranking Interactivo por Recurso**: Tarjetas con barra de progreso y porcentaje sobre el total; al hacer clic en cualquier descarga filtra inmediatamente todas sus IPs en la tabla inferior.
- **Inspección Rápida por IP**: Clic directo sobre cualquier insignia de IP para listar todas las descargas realizadas desde esa dirección IP, más botón de copia rápida al portapapeles.

### 1.3. Purgado Inteligente sin Pérdida de Históricos
- **Manual desde el Panel (`DELETE /panel/actividad/descargas-logs`)**: Permite eliminar con confirmación (`ConfirmDialog`) registros con antigüedad `> 90 días`, `> 30 días`, `> 7 días` o **vaciar el 100 % de `download_logs`**.
- **Automático Diario (`routes/console.php` a las `03:30`)**: Ejecuta `model:prune` sobre `DownloadLog` (`MassPrunable`) respetando `DOWNLOADS_LOG_RETENTION_DAYS` (180 días por defecto).
- **Garantía de Integridad Histórica**: Ninguna operación de purgado (ni siquiera vaciar la tabla completa o eliminar definitivamente un ítem antiguo) altera `total_downloads_all_time` ni `downloads_count`.

---

## 2. Auditoría del CMS con `spatie/laravel-activitylog` v5.1

### 2.1. Modelos Auditados y Exclusión de Secretos / Payloads Pesados
Mediante el trait reutilizable `App\Models\Concerns\RecordsCmsActivity` y la configuración global `config/activitylog.php`:
- Se registran automáticamente eventos `created`, `updated`, `deleted` y `restored` (`logOnlyDirty()` + `dontLogEmptyChanges()`) sobre:
  - **Contenido**: `Article`, `Page`, `Category`, `Menu`, `Form`.
  - **Descargas**: `DownloadItem`, `DownloadSetting`.
  - **Maquetador Visual**: `BuilderTemplate` y guardados de diseño en `AdminDownloadBuilderController`.
  - **Usuarios y Configuración**: `User`, `SiteSetting`.
- **Blindaje de Seguridad y Tamaño de Base de Datos**:
  - Se excluyen por defecto `password`, `remember_token` y `google_maps_api_key` (cero exposición de credenciales en logs).
  - Se excluyen árboles JSON masivos (`layout_json`, `item_layout_json`, `fields_schema`, `extra_fields_schema`) y cuerpos HTML extensos (`body`) del diff de `attribute_changes`, manteniendo cada fila de `activity_log` por debajo de `1 KB`.
  - `include_soft_deleted_subjects = true` permite identificar recursos enviados a la papelera.

### 2.2. Eventos de Seguridad y Enriquecimiento de IP (`AppServiceProvider`)
- Un hook global `Activity::saving()` inyecta automáticamente la `ip` y `user_agent` del autor en `properties`.
- Se escuchan los eventos nativos de autenticación de Laravel en el canal `seguridad`:
  - `Illuminate\Auth\Events\Login` (`event = 'login'`): registra accesos exitosos al panel con usuario e IP.
  - `Illuminate\Auth\Events\Failed` (`event = 'login_failed'`): registra intentos fallidos de acceso indicando el correo intentado y la IP de origen.
  - Operaciones de limpieza manual de logs (`event = 'purged'`).

### 2.3. Comparador Antes vs Después y Retención (`/panel/actividad?tab=auditoria`)
- Todas las respuestas viajan formateadas a través de `App\Http\Resources\ActivityLogResource` (cero modelos Eloquent crudos).
- Incluye filtros por módulo (`contenido`, `descargas`, `maquetador`, `seguridad`, `usuarios`, `configuracion`), tipo de evento y búsqueda libre.
- Cada edición dispone del botón **«Ver cambios»**, abriendo un modal comparativo campo por campo (**Valor Anterior** vs **Valor Nuevo**).
- Limpieza manual selectiva (`DELETE /panel/actividad/auditoria-logs`) y limpieza automática diaria a las `03:45` mediante `activitylog:clean --force` (`ACTIVITYLOG_CLEAN_DAYS`, 180 días por defecto).
