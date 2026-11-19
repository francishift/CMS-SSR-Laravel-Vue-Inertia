# 📋 Módulo de Formularios Dinámicos — Documentación Técnica

El módulo de formularios dinámicos es una solución nativa integrada en el CMS (Laravel + Vue 3 + Inertia con SSR), diseñada específicamente para alto rendimiento, arquitectura limpia y máxima seguridad en aplicaciones con miles de registros y peticiones.

---

## 1. Arquitectura General y Estado del Módulo

El desarrollo está organizado en 6 fases estratégicas:

| Fase | Descripción | Estado |
|:---|:---|:---:|
| **Fase 1: Cimientos y Backend Seguro** | Migraciones, modelos Eloquent, generador dinámico de reglas Laravel, servicio de procesamiento sanitizado, API Resources, FormRequest admin y endpoint público. | ✅ **COMPLETA** |
| **Fase 2: Constructor Visual y Panel Admin** | Constructor drag-and-drop modular (`Edit.vue` y 8 parciales) con 23 tipos de campo, inspector lateral, vista previa interactiva con modo simulación y real, editor WYSIWYG de emails, bandeja de envíos (`Submissions.vue`) y exportación CSV. | ✅ **COMPLETA** |
| **Fase 3: Renderizado Público y Page Builder** | Componente `ElementForm.vue` para el frontend público con SSR, inspector `ElementFormInspector.vue` en el maquetador visual, endpoint `schema` filtrado, envío AJAX con feedback animado y grid de 12 columnas responsivo. | ✅ **COMPLETA** |
| **Fase 4: Blindaje de Seguridad Avanzado** | Integración del widget de Cloudflare Turnstile opcional (`TurnstileService` + orígenes CSP condicionales), honeypot y rate-limiting por IP. | ✅ **COMPLETA** |
| **Fase 5: Notificaciones y Emails Dinámicos** | Despacho síncrono inmediato vía SMTP de `FormNotificationMail` (notificación al admin y autorespuesta al usuario), reemplazo de variables dinámicas, tabla `{all_fields}` con inline CSS, personalización visual (color de cabecera y logotipo por formulario) y detección automática de emails/teléfonos como enlaces clicables. | ✅ **COMPLETA** |
| **Fase 6: Motor de Scripts en Navegador** | Ejecución en frontend de `scripts_before`, `scripts_on_submit` y `scripts_after` para píxeles de conversión y analítica. | ✅ **COMPLETA** |

---

## 2. Componentes Backend (Fase 1)

### 2.1. Modelos de Base de Datos
* **`App\Models\Form`**:
  * Representa la definición del formulario.
  * Casts automáticos a array/json para `fields_schema` y `settings`.
  * Scopes de consulta: `active()`.
  * Relación `hasMany` con `FormSubmission`.
* **`App\Models\FormSubmission`**:
  * Registra cada envío recibido.
  * Cast a json para `data` (respuestas del usuario).
  * Scopes: `unread()`, `read()`, `spam()`, `notSpam()`.
  * Métodos de utilidad: `markAsRead()`, `markAsSpam()`.

### 2.2. Capa de Servicios y Validación Dinámica
* **`App\Services\FormValidationRuleBuilder`**:
  * Transforma el `fields_schema` JSON en un array de reglas nativas de validación de Laravel en tiempo de ejecución.
  * **Seguridad Reforzada**:
    * Validación wildcard para elementos individuales de arrays (`multiselect` y `checkbox`: `fields.servicios.* => in:...`).
    * Límites de tamaño en campos `hidden` (`max:1000`) para evitar *payload bombing*.
    * Whitelist estricta de reglas de validación permitidas para administradores, bloqueando inyecciones de reglas peligrosas como `exists`, `unique` o `current_password`.
* **`App\Services\FormSubmissionService`**:
  * Orquesta la recepción, validación, sanitización anti-XSS y almacenamiento.
  * Verificación de Honeypot anti-spam.
  * Verificación de rate-limit por IP (`max_submissions_per_ip`).
  * Sanitización recursiva con `HtmlSanitizerService` de todos los campos de texto.

### 2.3. Controladores y FormRequests
* **`App\Http\Requests\StoreFormRequest`**:
  * Valida la estructura de creación/edición de formularios en el panel admin.
  * Exige identificadores (`name`) en snake_case (`^[a-z0-9_]+$`).
  * Valida los 23 tipos de campo permitidos, límites de scripts y reglas condicionales.
* **`App\Http\Controllers\FormController`**:
  * CRUD completo en Inertia: `index`, `create`, `store`, `edit`, `update`, `destroy`.
  * Operaciones auxiliares: `duplicate` (duplicación instantánea con nuevo slug) y `toggleActive`.
  * Gestión de bandeja de envíos: `submissions`, `showSubmission`, `markSpam` (bidireccional: marcar/desmarcar), `markRead` (silencioso vía AJAX), `destroySubmission` y `exportCsv` (con BOM UTF-8 para compatibilidad nativa con Microsoft Excel).
  * Endpoint JSON ligero para el Page Builder: `index` con `_builder_list=1` devuelve lista de formularios activos sin Inertia.
* **`App\Http\Controllers\PublicFormController`**:
  * Endpoint público `POST /formularios/{slug}/enviar` para recibir envíos.
  * Endpoint público `GET /api/formularios/{slug}` para obtener el esquema de renderizado (con filtrado de claves internas).
  * Anti-enumeración por slug, devuelve JSON para peticiones asíncronas desde el frontend.

### 2.4. API Resources
* **`App\Http\Resources\FormResource`**: Formatea y protege metadatos del formulario, evitando la exposición de modelos Eloquent crudos en Inertia.
* **`App\Http\Resources\FormSubmissionResource`**: Formatea los envíos para la bandeja de administración.

---

## 3. Interfaces de Administración y Constructor Visual (Fase 2)

### 3.1. Listado de Formularios (`resources/js/Pages/Formularios/Index.vue`)
* Buscador en tiempo real por nombre o slug.
* Métricas visibles: número de campos y contador de envíos con indicador pulsante para mensajes no leídos.
* Badges de seguridad activada (Honeypot, Turnstile, notificaciones admin, autorespuesta).
* Toggle reactivo de activación/desactivación sin recargar la página.
* Menú de acciones: bandeja de envíos, duplicar, editar y eliminar con diálogo modal de confirmación `useConfirm`.

### 3.2. Constructor Visual Drag & Drop y Arquitectura Modular (`Edit.vue`)
Siguiendo los principios de **Clean Code**, **SOLID** y alta cohesión, el constructor de formularios se ha dividido en submódulos especializados dentro de `resources/js/Pages/Formularios/Partials/`:

* **`FieldPalette.vue`**: Catálogo categorizado de los 23 tipos de campo (Texto, Selección, Fecha/Hora, Especiales, Estructura).
* **`FieldCanvas.vue`**: Lienzo central con `vuedraggable` para reordenar, duplicar o eliminar campos.
* **`FieldInspector.vue`**: Panel lateral reactivo para propiedades de campo (label, name en snake_case, placeholder, requerido, ancho de 33%/50%/100%, opciones con importador en bloque y lógica condicional).
  * **Configuración de Enlace Legal / Términos (`checkbox` y `toggle`)**: Permite vincular una URL (ej: `/politica-de-privacidad`) para convertir todo el label en un enlace interactivo sin ensuciar el texto con código HTML manual.
  * **Modo de Apertura**: Selector desambiguado PrimeVue 4 entre *Ventana modal emergente* (predeterminada) y *Nueva pestaña (`_blank`)*.
  * **Autolimpieza y Detección Inteligente**: Si el usuario introduce etiquetas `<a href="...">` en el campo label, el inspector extrae automáticamente la URL y limpia el texto para evitar que se renderice código HTML en crudo.
* **`TabGeneralSettings.vue`**: Ajustes generales (nombre, slug con auto-generación, mensajes de éxito, redirección, estilo y alineación del botón).
* **`TabEmailSettings.vue`**: Notificaciones a administradores y auto-respuestas con `WysiwygEditor.vue` e inserción de variables dinámicas en un solo clic. Los correos se entregan de forma directa y síncrona mediante el transporte SMTP, garantizando recepción inmediata sin requerir un worker de colas en segundo plano.
* **`TabSecuritySettings.vue`**: Rate limiting por IP, Honeypot antispam invisible y Cloudflare Turnstile.
* **`TabScriptsSettings.vue`**: Scripts personalizados de JavaScript en cliente (`before`, `on_submit`, `after`).
* **`TabLivePreview.vue`**: Vista previa interactiva completa con dos modalidades de prueba:
  * **Modo Simulación**: Valida campos obligatorios y muestra confirmación en tiempo real sin ensuciar la base de datos.
  * **Modo Envío Real a BD**: Envía el payload real vía AJAX al endpoint público (`POST /formularios/{slug}/enviar`) y almacena el registro en la base de datos para auditarlo en la bandeja de envíos.
  * **Inspector JSON**: Visualización en vivo del payload reactivo `{ fields: ... }`.
  * **Lógica condicional interactiva**: Muestra y oculta campos en vivo según los valores introducidos.
* **Cumplimiento Estricto PrimeVue 4**: Modelos computados desambiguados con valor `'none'` para selectores anulables, evitando pantallas en blanco.

### 3.3. Bandeja de Envíos (`resources/js/Pages/Formularios/Submissions.vue` y `SubmissionDetail.vue`)
* **Doble Modalidad (Global y por Formulario)**:
  * **Bandeja Global (`/panel/formularios/envios` — `panel.formularios.submissions.all`)**: Agrupa los mensajes y leads de todos los formularios con precarga de relaciones `with(['form:id,name,slug'])` para evitar el problema N+1. Incluye selector desplegable PrimeVue 4 para filtrar por formulario específico (con valor `'todos'` por defecto según las reglas del proyecto).
  * **Bandeja Específica (`/panel/formularios/{form}/envios`)**: Muestra únicamente los envíos del formulario seleccionado.
* **Buscador de Envíos**: Filtrado instantáneo por texto en el cuerpo de los datos (nombre, email, teléfono, mensaje) o por dirección IP.
* **Filtros Rápidos por Estado**: *Todos los envíos*, *No leídos* (con contador animado en rojo si hay pendientes), *Leídos* y *Spam*.
* **Modal de Inspección Completa**: Desglose de formulario de origen, fecha y hora formateada, IP, referer y lista estructurada de campos.
* **Acciones Directas y Silenciosas**:
  * Marcado como leído silencioso vía AJAX (`axios.patch`) al abrir el modal o mediante endpoints directos `/panel/formularios/envios/{id}/leer`.
  * Toggle bidireccional de spam (`/panel/formularios/envios/{id}/spam`).
  * Eliminación permanente con confirmación (`/panel/formularios/envios/{id}`).
* **Exportación CSV Unificada**:
  * Por formulario específico: exporta las columnas exactas del schema.
  * Global (`/panel/formularios/envios/exportar-csv`): analiza dinámicamente los campos heterogéneos de todos los formularios, generando un CSV unificado con codificación UTF-8 BOM para compatibilidad total con Microsoft Excel.

### 3.4. Integración en el Panel de Control (Dashboard)
* **`App\Http\Controllers\DashboardController`**:
  * Consulta optimizada e indexada que extrae: total de envíos recibidos, conteo de envíos sin leer (`is_read = false`) y fecha del último envío.
  * Pasa `estadisticasEnvios` a la vista `Dashboard.vue`.
* **Tarjeta (Card) de "Bandeja de Envíos"**:
  * Acceso directo a la bandeja global de envíos.
  * **Aviso visual de nuevo envío recibido**:
    * Si hay envíos no leídos (`no_leidos > 0`), se activa un badge vibrante con animación pulsante (`animate-ping`), borde destacado en tonos rosas/ámbar, etiqueta destacada *«¡Nuevo envío recibido!»* y contador visible.
    * Si no hay mensajes pendientes, muestra estado neutro *«Al día (0 pendientes)»*.

---

## 4. Renderizado Público y Page Builder (Fase 3)

### 4.1. Integración en el Page Builder

El elemento **Formulario** está registrado en el catálogo del Page Builder como tipo `form`, disponible en cualquier página o plantilla:

* **`resources/js/Components/Builder/Inspectors/ElementFormInspector.vue`**: Inspector del maquetador que permite al administrador:
  * Seleccionar un formulario activo por slug (carga asíncrona desde `FormController@index` con `_builder_list=1`).
  * Configurar el tema visual: *Por defecto*, *Transparente*, *Formulario con fondo*, *Glassmorphism*.
  * Ajustar esquinas (6 niveles), sombras (5 niveles), etiquetas visibles y modo compacto.
  * Personalización avanzada (ID, clases CSS, atributos HTML y CSS inline).

* **Registrado en**:
  * `usePageBuilder.js` — catálogo, iconos, labels y `obtenerNombreElemento`.
  * `BuilderInspector.vue` — renderizado condicional del inspector.
  * `BuilderColumn.vue` — mapa de componentes públicos.
  * `ElementSublayout.vue` — soporte dentro de sublayouts.

### 4.2. Componente Público (`resources/js/Frontend/Builder/Elements/ElementForm.vue`)

Renderiza el formulario dinámico en la web pública con las siguientes características:

* **15+ tipos de campo interactivos**: text, email, url, tel, password, textarea, number, select, radio, checkbox/toggle, date, datetime, time, color, hidden, slider, rating.
* **Gestión de Enlaces Legales y Usabilidad en Checkbox/Toggle**:
  * **Desacople de Eventos**: El control interactivo (casilla o interruptor) se encuentra físicamente separado de la etiqueta de texto. Al hacer clic sobre el enlace del documento legal, **no se conmuta involuntariamente el estado del toggle/checkbox**.
  * **Estilo Limpio sin Subrayado**: El enlace utiliza el estilo tipográfico nativo del formulario con efecto hover esmeralda (`hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer`), prescindiendo de subrayados intrusivos (`no-underline`).
  * **Ventana Modal Flotante Integrada**: Cuando el enlace está configurado en modo `modal`, solicita de forma asíncrona el contenido a `GET /api/paginas/{slug}` y despliega un modal centrado con fondo translúcido (`backdrop-blur-sm`), cabecera con botón `✕`, tipografía legal optimizada (`prose`), botón inferior «Entendido», bloqueo de scroll en el body y cierre con tecla `Escape`. **Los datos que el usuario haya escrito en el formulario permanecen intactos**.
  * **Modo Nueva Pestaña**: Si se configura `blank`, el enlace se abre de forma segura con `target="_blank"` y `rel="noopener noreferrer"`.
* **3 tipos decorativos**: heading, paragraph, separator.
* **Grid responsivo de 12 columnas**: Respeta los anchos configurados (full=12, half=6, third=4), idéntico al preview del admin.
* **Lógica condicional**: Campos que aparecen/desaparecen según el valor de otros campos (operadores: equals, not_equals, contains, not_empty, empty).
* **Validación en cliente**: UX mejorada con validación en blur + validación completa al enviar. No sustituye la validación del servidor.
* **Envío AJAX seguro**: POST a `/formularios/{slug}/enviar` con CSRF automático (Axios).
* **Feedback visual premium y experiencia móvil**:
  * **Prevención de Zoom en iOS Safari**: Todos los campos interactivos utilizan fuente mínima de 16px (`text-base sm:text-sm`) combinada con una regla CSS global en `app.css` (`@media screen and (max-width: 768px)`), impidiendo que los iPhones fuercen zoom automático al tocar los inputs.
  * **Ancla Inteligente (Auto-scroll Suave)**: Al completarse el envío, la pantalla realiza un scroll suave (`scrollIntoView({ behavior: 'smooth', block: 'center' })`) hacia el contenedor del formulario para evitar que el usuario quede desorientado en pantallas móviles.
  * **Notificación Toast Flotante (Glassmorphism)**: Teletransportada directamente al `body` (`<Teleport to="body">`) con `z-[999999]`, diseño translúcido con desenfoque de fondo (*glassmorphism*), icono animado con efecto *ping*, barra temporizada de 6 segundos y botón de cierre `×`.
  * **Modos de Feedback Configurables (`success_feedback`)**:
    * `both` (Ambos): Mensaje en el contenedor con auto-scroll + Notificación Toast flotante *(por defecto)*.
    * `toast` (Solo Toast Flotante): Limpia los campos del formulario y avisa arriba en pantalla.
    * `inline` (Solo en el Formulario): Muestra la pantalla completa de agradecimiento con auto-scroll.
  * Check animado SVG con stroke-dasharray al enviar con éxito.
  * Icono de éxito con animación scale + cubic-bezier.
  * Spinner de envío en el botón durante la petición.
  * Fade animado para errores con scroll suave al primer campo con error.
  * Redirección configurable post-éxito (si está configurada, redirige tras 2 segundos).
  * Botón "Enviar otro mensaje" para resetear el formulario.
* **Dark mode completo**: Todos los campos y estados soportan tema oscuro.

### 4.3. Endpoint `schema` — Superficie de Datos Mínima

`GET /api/formularios/{slug}` (`PublicFormController@schema`) devuelve **solo** los datos necesarios para renderizar:

**Claves del `fields_schema` expuestas (whitelist)**:
`id`, `type`, `name`, `label`, `placeholder`, `required`, `options`, `width`, `conditional`, `default_value`, `min`, `max`, `min_length`, `max_length`, `min_date`, `max_date`, `max_rating`, `content`, `link_url`, `link_target`.

**Nunca se exponen**: `validation_rules`, `css_class`, `allowed_extensions`, `max_file_size`, `max_selections`, ni ningún otro dato interno del admin.

**Datos globales expuestos**: `name`, `slug`, `settings` (texto, color, alineación del botón y modo `success_feedback`), `requires_captcha`, `turnstile_site_key`, `success_message`, `honeypot_field`.

**Nunca se exponen a nivel de formulario**: `email_admin_template`, `email_user_template`, `scripts_before`, `scripts_on_submit`, `scripts_after`, `email_admin_to`, `email_admin_subject`, `email_user_subject`, `email_user_field`, `max_submissions_per_ip`.

### 4.4. Accesibilidad Universal y Estándares WCAG 2.1 / WAI-ARIA

El componente `ElementForm.vue` ha sido auditado y optimizado para satisfacer los criterios de conformidad WCAG 2.1 Nivel AA y superar las pruebas de navegación agéntica de IA y lectores de pantalla (Lighthouse / axe-core):

1. **Garantía de Nombres Accesibles en Controles (`Form elements must have labels`)**:
   - **`checkbox` y `toggle`**: El elemento `<label>` que compone el interruptor visual incluye siempre `<span class="sr-only">{{ obtenerTextoLabel(campo) }}</span>`, eliminando etiquetas huérfanas o vacías de texto.
   - **Atributos ARIA en inputs**: Cada campo dispone de `:aria-label="obtenerTextoLabel(campo) || campo.label || campo.name"`.
   - **Semántica de Interruptores**: Los campos `toggle` declaran `role="switch"` y `:aria-checked="valores[campo.name] ? 'true' : 'false'"`.
   - **Campos Obligatorios**: Se comunican semánticamente mediante `:aria-required="campo.required ? 'true' : 'false'"` y el asterisco visual lleva `aria-hidden="true"` para no pronunciar ruidos innecesarios.
2. **Preservación de Etiquetas con Ocultación Visual (`show_labels: false`)**:
   - Cuando el usuario desactiva visualmente las etiquetas en el maquetador, la etiqueta `<label>` no se elimina del DOM, sino que adquiere la clase utilitaria `sr-only`, manteniendo la asociación accesible para lectores de pantalla.
3. **Controles Especiales**:
   - `color`, `date`, `slider` y `rating` incorporan etiquetas de accesibilidad explícitas (`aria-label`, `aria-valuemin`, `aria-valuemax`, `aria-valuenow`, `role="radiogroup"`, `role="group"`).
4. **Desacoplamiento Legal Accesible**:
   - El texto que contiene enlaces a la política de privacidad o términos se ubica en elementos adyacentes (`<a>` o `<button>`) sin anidar elementos interactivos dentro del `<label>`, respetando la especificación formal de HTML5.

---

## 5. Seguridad — 9 Capas de Protección

| # | Capa | Mecanismo | Ubicación |
|---|---|---|---|
| 1 | **CSRF** | Token `X-XSRF-TOKEN` automático de Axios desde cookie de Laravel | Middleware `web` (VerifyCsrfToken) |
| 2 | **Rate Limiting HTTP** | `throttle:5,1` (5 envíos/min/IP) en submit, `throttle:30,1` en schema | `routes/web.php` |
| 3 | **Rate Limiting por IP acumulado** | `max_submissions_per_ip` configurable por formulario (límite total) | `FormSubmissionService` |
| 4 | **Honeypot anti-bot** | Campo invisible: si un bot lo rellena → `is_spam=true` → email NO se envía | `FormSubmissionService` + `ElementForm.vue` |
| 5 | **Validación servidor** | Reglas dinámicas por tipo (email:rfc,dns en producción, regex tel, whitelist select/radio, etc.) | `FormValidationRuleBuilder` |
| 6 | **Sanitización XSS** | strip_tags + eliminación de eventos inline + neutralización javascript:/data: | `HtmlSanitizerService` |
| 7 | **Anti-enumeración** | Acceso público por slug, nunca por ID numérico | `PublicFormController` |
| 8 | **Whitelist de reglas admin** | Filtro de reglas personalizadas: solo reglas seguras (bloquea `exists`, `unique`, etc.) | `FormValidationRuleBuilder::filterSafeRules()` |
| 9 | **Superficie de datos mínima** | `fields_schema` filtrado con `collect()->only()`, sin datos internos ni templates de email | `PublicFormController::schema()` |

---

## 5b. Notificaciones por Email y Personalización Visual (Fase 5)

### 5b.1. Arquitectura de Envío
* **`FormNotificationMail`** (Mailable): Entrega síncrona vía SMTP. Recibe `Form`, `FormSubmission`, asunto, body HTML y flag `isAutoReply`.
* **`FormSubmissionService::dispatchEmails()`**: Orquesta la resolución de variables, generación de la tabla `{all_fields}` y envío a los destinatarios configurados.
* **Plantilla Blade**: `resources/views/emails/form-notification.blade.php` con estructura de tablas e **inline CSS** para compatibilidad máxima con clientes de correo (Gmail, Outlook, Apple Mail, móviles).

### 5b.2. Personalización Visual por Formulario
Cada formulario almacena en su campo JSON `settings`:

| Campo | Tipo | Descripción |
|---|---|---|
| `email_header_color` | string (hex sin #) | Color de fondo de la cabecera del email (por defecto: `059669`) |
| `email_logo_url` | string (URL) | URL absoluta del logotipo mostrado en la cabecera. Si no se configura, usa el logo global del sitio (`logo_path` de `SiteSetting`). Debe ser PNG o JPG (los emails no soportan SVG). |

La interfaz en `TabEmailSettings.vue` ofrece:
* **ColorPicker** de PrimeVue con previsualización en tiempo real.
* **Botón "Seleccionar Logo"** que abre la **Biblioteca de Medios** (`MediaPickerModal`) para elegir o subir una imagen directamente.

### 5b.3. Tabla de Campos con Inline CSS
La tabla `{all_fields}` se genera con estilos inline en cada celda `<td>` para garantizar el renderizado correcto en todos los clientes:
* Etiquetas en columna izquierda con fondo `#fafafa` y texto gris oscuro.
* Valores en columna derecha con texto negro.
* **Detección automática**: los valores de tipo `email` se renderizan como `<a href="mailto:...">` y los teléfonos como `<a href="tel:...">`, ambos clicables directamente desde el email.

---

## 6. Matriz de Rutas del Sistema

```
// Panel Admin (middleware: auth, role:admin)
GET|HEAD   /panel/formularios ...................... panel.formularios.index
GET|HEAD   /panel/formularios/crear ................ panel.formularios.create
POST       /panel/formularios ...................... panel.formularios.store
GET|HEAD   /panel/formularios/{form}/editar ........ panel.formularios.edit
PUT        /panel/formularios/{form} ............... panel.formularios.update
DELETE     /panel/formularios/{form} ............... panel.formularios.destroy
POST       /panel/formularios/{form}/duplicar ...... panel.formularios.duplicate
PATCH      /panel/formularios/{form}/activar ....... panel.formularios.toggle-active
GET|HEAD   /panel/formularios/{form}/envios ........ panel.formularios.submissions
GET|HEAD   /panel/formularios/{form}/envios/{sub} .. panel.formularios.submissions.show
PATCH      /panel/formularios/{form}/envios/{sub}/leer  panel.formularios.submissions.mark-read
PATCH      /panel/formularios/{form}/envios/{sub}/spam  panel.formularios.submissions.spam
DELETE     /panel/formularios/{form}/envios/{sub} .. panel.formularios.submissions.destroy
GET|HEAD   /panel/formularios/{form}/exportar-csv .. panel.formularios.export-csv

// Endpoints Públicos (sin autenticación)
POST       /formularios/{slug}/enviar .............. public.form.submit     (throttle:30,1 + límite por IP)
GET|HEAD   /api/formularios/{slug} ................. public.form.schema     (throttle:30,1)
GET|HEAD   /api/paginas/{slug} ..................... public.page.content    (throttle:60,1)
```

---

## 7. Suite de Pruebas Automatizadas

El módulo cuenta con una batería de **51 pruebas automatizadas específicas** (integradas dentro de las **248 pruebas** globales del CMS que pasan al 100%):

* `tests/Feature/Services/FormValidationRuleBuilderTest.php` (12 tests): Generación de reglas, validación individual de arrays, protección anti-payload bombing y whitelist de seguridad.
* `tests/Feature/Services/FormSubmissionServiceTest.php` (15 tests): Sanitización anti-XSS, honeypot, rate limiting por IP, almacenamiento y validaciones.
* `tests/Feature/Controllers/PublicFormControllerTest.php` (11 tests): Endpoint público HTTP, respuestas 422, control de formularios inactivos, whitelist de esquema con `link_url`/`link_target`, contenido limpio de páginas para modal y protección contra robots.
* `tests/Feature/Controllers/FormControllerTest.php` (10 tests): Autenticación admin, CRUD completo, duplicación de formularios, toggle activo, bandeja de envíos y descarga CSV.
* `tests/Feature/Mail/FormNotificationTest.php` (3 tests): Despacho de notificación al administrador, autorespuesta al usuario con variables dinámicas y bloqueo de emails en envíos detectados como spam.

---

## 8. Protección Anti-Spam con Cloudflare Turnstile y CSP Condicional

- Cuando `TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET_KEY` están configuradas en `.env` y el formulario activa `requires_captcha`, `ElementForm.vue` inyecta bajo demanda `preconnect` y el widget de Cloudflare Turnstile, validando el token en servidor mediante `TurnstileService`.
- El middleware `SecurityHeaders` autoriza `https://challenges.cloudflare.com` en la cabecera `Content-Security-Policy` únicamente cuando existen formularios activos con captcha habilitado (`Form::hasAnyCaptchaEnabled()`).
