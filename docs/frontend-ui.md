# Guía de Diseño y UI Frontend

## 1. Sistema de Diseño e Identidad Visual

La interfaz del CMS ha sido diseñada con un estilo elegante, moderno y de alto rendimiento:

- **Paleta Primaria**: Emerald de Tailwind CSS (`#10b981` / `emerald-600` como tono principal, graduado desde `emerald-50` hasta `emerald-950`). Personalizable además dinámicamente mediante la configuración del sitio (`primary_color`, `secondary_color`).
- **Paleta Neutral y Superficies**: Zinc de Tailwind CSS (`zinc-50` a `zinc-950` para tarjetas, fondos, tablas y bordes).
- **Componentes UI**: PrimeVue v4 personalizado con el preset `Aura`. Todos los componentes (`DataTable`, `Dialog`, `Button`, `InputText`, `Select`, `Tag`, `Paginator`) adoptan los colores semánticos del sistema.
- **Ancho Estándar de Contenedor**: `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8` aplicado uniformemente en cabecera, pie de página, portada, categorías, páginas estáticas y artículos públicos.

---

## 2. Paginación SPA Cero Parpadeos y Micro-Animaciones Grid

Para lograr una experiencia de usuario fluida e imperceptible:
- **Estabilización de Cabecera**: En las vistas de categoría (`resources/js/Pages/Public/Category.vue`), la miga de pan, el título y la descripción se mantienen **100% inmóviles** al navegar entre páginas.
- **Animación `card-pop` Escalonada**: Al cambiar de página, las nuevas tarjetas entran con un suave zoom y elevación (`translateY(10px) -> translateY(0)`) encadenadas con un retraso progresivo de `30ms` por elemento.
- **Botones de Paginación Interactivos**: Resaltado activo con un **anillo de luz esmeralda (`ring-2 ring-emerald-500/40`)**, efecto hover fluido y feedback táctil (`active:scale-95`).

---

## 3. Optimización de Usabilidad en Formularios de Administración

Para ofrecer la mejor experiencia en pantallas de alta y baja resolución:
- **Barra de Acciones Duplicada (Top Action Bar)**: Botones principales **"Guardar Cambios"** y **"Cancelar"** presentes tanto en la cabecera superior como en la parte inferior de los formularios de Artículos, Categorías y Páginas Estáticas.
- **Maquetación en 2 Columnas**:
  - **Área Principal (Izquierda)**: Campos extensos (Título, Editor WYSIWYG, Contenido HTML).
  - **Sidebar de Ajustes (Derecha)**: Opciones de estado, taxonomía, SEO, maquetación de grid e imagen destacada.
  - Minimiza drásticamente el desplazamiento vertical (scroll).

### 3.1. Escala Visual del Título en Páginas Estáticas Básicas (`title_size`)
Las páginas estáticas que no emplean secciones del Page Builder (por ejemplo textos legales como *Política de privacidad* o *Política de cookies*) incorporan en `Paginas/Create.vue` y `Paginas/Edit.vue` el selector **Tamaño Visual del Título** (`title_size`, reutilizando `OPCIONES_TAMANO_VISUAL` de `resources/js/Utils/headingScale.js`):
- **Semántica SEO Intacta**: `resources/js/Pages/Public/Page.vue` conserva siempre la etiqueta `<h1>` real para buscadores, pero aplica dinámicamente `getHeadingClasses(title_size, ..., 'h1')` y `getHeadingStyle(title_size, 'h1')`.
- **Escalas Responsivas**: Permite mantener el `H1` global del tema (`default`) o escoger escalas contenidas y adaptables a móvil (`h2`, `h3`, `h4`, etc.) página por página sin afectar al resto de titulares `H1` del sitio.

---

## 4. Gestión de Logotipo, Tamaño Dinámico, Filtros y Modo Oscuro

- **Subida y Dualidad Claro / Oscuro (`logo_path` y `logo_dark_path`)**: Soporte para PNG, JPG, WebP y **SVG vectoriales** con logotipo independiente opcional para modo oscuro y vista previa dual en tiempo real en `IdentidadSection.vue`.
- **Control de Altura en Escritorio y Móvil (`logo_height` y `logo_height_mobile`)**:
  - Dos deslizadores independientes en **Configuración → Identidad** (`16–100 px` en escritorio, por defecto `36 px`; `16–64 px` en móvil y cajón lateral `.site-logo-drawer`, por defecto `32 px`) manteniendo `width: auto` (`object-contain`) para no deformar ninguna proporción.
  - **Cero CLS en SSR**: Las variables `--logo-height` y `--logo-height-mobile` se inyectan directamente en `<style id="cms-ssr-critical-vars">` (`app.blade.php`) y se sincronizan en caliente desde `PublicLayout.vue`.
  - **Cabecera Elástica y Modo Shrink**: `PublicHeader.vue` emplea alturas mínimas (`min-h-*`) en lugar de alturas cerradas para que logotipos de mayor formato expandan suavemente la barra sin recortarse, aplicando además una reducción proporcional automática (`calc(... * 0.82)`) al hacer scroll cuando está activo `shrink_on_scroll`.
- **Optimización Automática WebP**: `ImageOptimizerService` procesa las imágenes subidas reduciendo su peso manteniendo alta fidelidad visual.
- **Filtros de Inversión y Brillo (`logo_invert`, `logo_brightness`, `logo_dark_invert`, `logo_dark_brightness`)**: Conversión monocromática y ajuste porcentual de luminancia independientes por tema.
- **Modo Claro y Oscuro Anti-FOUC (`frontend_theme_mode` y `frontend_default_theme`)**: `CspInlineScripts::publicThemeScript()` inyecta en `<head>` el inicializador exacto según el modo global (`light`, `dark` o `user_choice`) y el tema inicial para primeras visitas (`light`, `dark` o `system`), evitando cualquier destello de tema incorrecto antes de hidratar Vue.

### 4.1. Cabecera y Modos de Disposición (Cápsula Flotante y Anchos Adaptables)
La cabecera pública (`PublicHeader.vue`) y su configuración global (`CabeceraSection.vue`) permiten seleccionar estilos visuales clásicos, integrados o de **cápsula flotante** (*Floating Capsule*), con una escala coherente de anchos de contenedor:
- **100% Pantalla** (`full`): Ocupa toda la anchura del viewport (`w-full`).
- **Ultra Amplio** (`wide_1600`): Limita el marco a 1600px (`max-w-[1600px] mx-auto`).
- **Expandido Grande** (`expand`): Limita el marco a 1536px (`max-w-screen-2xl mx-auto`), en perfecta sintonía con las secciones y filas `expand` del Page Builder.
- **Panorámico** (`wide_1440`): Limita el marco a 1440px (`max-w-[1440px] mx-auto`).
- **Ancho General / Boxed** (`contained`): Limita el marco a 1280px (`max-w-7xl mx-auto`), idéntico al estándar general del sitio.
- **Compacto y Estrecho**: 1152px (`max-w-6xl`), 1024px (`max-w-5xl`), 896px (`max-w-4xl`) y 768px (`max-w-3xl` / mini).
- **Ajustado al Contenido** (`fit`): Ocupa estrictamente el ancho del menú y logotipo (`w-fit mx-auto`).

---

## 5. Constructor Visual de Páginas (Page Builder) y Elementos Modulares

El constructor visual de páginas (`resources/js/Pages/Paginas/Builder.vue`) permite maquetar secciones, filas y elementos modulares en tiempo real:

- **Elemento Acordeón**: Gestiona desplegables con estructura limpia de **Título** y **Descripción**. Soporta renderizado de contenido formateado mediante `v-html` sanitizado con DOMPurify en `resources/js/Frontend/Builder/Elements/ElementAccordion.vue` y panel interactivo con reordenado drag-and-drop en la barra lateral.
- **Elemento Texto Párrafo**: Interfaz limpia en el árbol de estructura con asa de arrastre unificada (`elem-drag-handle`) sin redundancia visual de iconos. HTML sanitizado contra XSS via `useSanitize` antes de renderizar con `v-html`.
- **Elemento Icono Modular**: Renderizado rápido y ligero de iconos individuales con escala de tamaños (`xs` a `4xl` o personalizado en px), paletas cromáticas y estilos de contenedor (sin fondo, suave, sólido, circular, etc.) en `resources/js/Frontend/Builder/Elements/ElementIcon.vue`.
- **Elemento Paginación / Navegación**: Navegación entre artículos anterior/siguiente (5 variantes de maquetación: Cards, Minimal, Pills, Hero, Compact) y paginación numérica de archivos en `resources/js/Frontend/Builder/Elements/ElementPagination.vue`.
- **Distribución Visual de Cuadrículas y Control Granular de Columnas**: Galería modal de 24 layouts tipo wireframe y control granular por 5 breakpoints (fracciones, `Expandir`, `Auto`, tamaños fijos y orden visual). Detalle completo en `docs/page-builder.md`.
- **Control Coherente de Ancho de Filas**: Capacidad de modular el ancho del contenedor de cada fila (`inherit`, `boxed`, `small`, `expand`, `full_margin`, `full_bleed`) dentro de secciones amplias o full-width, con gestión inteligente de padding lateral para evitar sangrías dobles en móvil. Detalle completo en `docs/page-builder.md`.
- **Fondos Multimedia en Secciones y Columnas (Imagen / Vídeo HTML5 y Modos Claro / Oscuro Independientes)**: Soporte completo para fondos tanto de imagen como de vídeo HTML5 silenciado y en bucle continuo en Secciones y Columnas, incluyendo en Secciones configuración independiente para **Modo Claro** y **Modo Oscuro** (`bg_dark_mode: 'inherit' | 'custom'`) resuelta 100 % en CSS SSR sin parpadeos (`builder-media-only-light` / `builder-media-only-dark`, `--sec-bg-light/dark`, `--sec-overlay-light/dark`) y preloads LCP condicionados por `prefers-color-scheme`. Incorpora optimización con `IntersectionObserver` (pausa automática fuera de pantalla y en tema inactivo para ahorro de ciclos de GPU y batería), fallback a imagen póster WebP en móviles (`bg_video_mobile`), ajuste de encuadre focal (`object-position`), modulación de velocidad de reproducción (`0.5x` a `1.25x`) y recorte exacto con esquinas redondeadas (`overflow-hidden` con `border_radius`).
- **Optimización de Imágenes (CLS & Lighthouse)**: Todos los componentes de imágenes (`resources/js/Frontend/Components/PublicHeader.vue`, `ElementGridArticles.vue`, `ResponsiveImage.vue`) incorporan dimensiones explícitas `width` y `height` para evitar saltos de maquetación y garantizar máxima puntuación en auditorías de Google.

---

## 6. Editor WYSIWYG Profesional

El editor de texto enriquecido (`resources/js/Components/Admin/WysiwygEditor.vue`) basado en PrimeVue Editor (Quill) incluye funcionalidades profesionales:

- **Desacoplamiento de Encabezados Admin y Públicos**: Las reglas CSS dinámicas de tamaño y espaciado de `--h1-size` a `--h6-size` están acopladas exclusivamente a la plantilla pública (`.public-site`, `.prose`, `.public-page`). El panel de administración (`.admin-layout`, `.admin-panel`) cuenta con un reset CSS limpio que respeta rigurosamente las clases de utilidad Tailwind (`text-xl`, `text-2xl`, etc.), garantizando que los encabezados del backend permanezcan perfectamente proporcionados y compactos.
- **Sanitización al Pegar y Escribir**: Intercepta el evento `paste` y detecta contenido de Word/Google Docs (markers: `urn:schemas-microsoft-com`, `docs-internal-guid`, `mso-`). Limpia automáticamente estilos inline, clases de Office, atributos `data-*`, comentarios condicionales y tags XML de Office.
- **Normalización Automática de Espacios (`&nbsp;` y `\u00A0`)**: Durante la edición y al emitir eventos `v-model`, se normalizan automáticamente las entidades `&nbsp;` y caracteres no rompibles a espacios estándar ASCII (` `), garantizando un salto de línea (*word-wrapping*) perfecto en pantallas móviles y evitando cadenas de texto continuas sin cortes en base de datos.
- **Vista Código Fuente HTML**: Botón toggle (`pi-code`) en la barra de herramientas que alterna entre el editor visual (Quill) y un textarea con fondo oscuro tipo IDE para edición directa del HTML. Sincroniza el HTML con `v-model` al volver al modo visual.
- **Subida de Imágenes**: Upload directo a servidor vía AJAX (`/panel/media/upload`) con inserción en la posición del cursor y sincronización con `v-model`.
- **Toolbar en Español**: Selector de encabezados (H1–H4 + Párrafo Normal), formato de texto, listas, citas, código, enlaces e imágenes.

---

## 7. Tipografía Global Configurable (Cuerpo y Encabezados Hero 2XL a H6)

El sistema de tipografía permite configurar de forma granular fuentes, tamaño, grosor (weight), interlineado (line-height) y espaciado entre letras (letter-spacing) tanto para el cuerpo de texto como para todos los niveles de encabezados:

- **Almacenamiento en Base de Datos**:
  - `body_font`, `body_font_size`, `body_font_weight`, `body_line_height`, `body_letter_spacing` en la tabla `site_settings`.
  - `heading_font` y columna JSON `heading_styles` con estructura para 8 niveles: `hero_2xl`, `hero_xl`, `h1`, `h2`, `h3`, `h4`, `h5`, `h6`, cada uno con `{ size, font_weight, letter_spacing, line_height }`.
- **Inyección Crítica SSR (Cero FOUC / Cero Parpadeo)**:
  - En `resources/views/app.blade.php`, dentro del bloque `<style id="cms-ssr-critical-vars">:root { ... }</style>`, se inyectan en servidor las variables `--body-font-size`, `--body-font-weight`, `--body-line-height`, `--body-letter-spacing` y `--{level}-{size|font-weight|letter-spacing|line-height}` para renderizado inmediato en el HTML inicial.
- **Sincronización Reactiva en Cliente**:
  - `resources/js/Frontend/Layouts/PublicLayout.vue` inyecta las variables en `:root` mediante `aplicarVariablesCssEnRoot()` y las mantiene actualizadas en vivo ante mutaciones de tema o cambios en la configuración.
- **Reglas CSS Globales y Cascada Unificada (`resources/css/app.css`)**:
  - `body`, `.public-site`, `.public-site p`, `.prose p`, `.builder-text-element p`, `article p` y `.wysiwyg-content p` adoptan las variables `--body-*` por defecto, evitando que presets rígidos de Tailwind Typography sobreescriban los valores elegidos por el usuario.
  - `.public-site h1` a `h6`, `.prose h1` a `h6`, `article h1` a `h6`, `.public-page h1` a `h6` y clases del constructor `.builder-h1` a `.builder-h6`, `.builder-hero_2xl`, `.builder-hero_xl` aplican `--{level}-size`, `--{level}-font-weight`, `--{level}-letter-spacing` y `--{level}-line-height`.
- **Prevalencia de Overrides por Elemento en el Maquetador**:
  - Si un bloque de encabezado (`ElementHeading.vue`) o de texto (`ElementText.vue`) se configura con tamaño, peso o preset explícito en el inspector del Page Builder, sus clases con especificidad forzada (`!text-*`, `!font-*`) tienen máxima prioridad sobre la configuración global.
  - Cuando se mantienen en "Por defecto", heredan con total fidelidad la escala tipográfica global configurada en el panel.

---

## 8. Configuración Global y Cabeceras — Responsive Mobile

1. **Cabeceras y Pestañas Adaptativas en el Panel (`AuthenticatedLayout.vue`)**:
   - **Desktop (md+)**: La barra superior (`header`) aloja el título del módulo, las acciones principales y las pestañas conmutadoras (ej. Activos vs Papelera) en una línea horizontal compacta y equilibrada.
   - **Móvil (< md)**: Para evitar solapamientos y desbordamientos en pantallas reducidas, el título de la Topbar se mantiene limpio y truncado con los botones de acción como elementos fijos (`shrink-0`), mientras que las pestañas de navegación se trasladan automáticamente al cuerpo principal de la vista como un selector táctil de ancho completo (`w-full`).
   - **Formularios de Creación y Edición**: Eliminación de subtítulos redundantes en dispositivos móviles para maximizar el área de trabajo útil.

2. **Configuración Global Adaptativa (`resources/js/Pages/Configuracion/Index.vue`)**:
   - **Desktop (lg+)**: Menú lateral fijo (`sticky top-6`) con 7 secciones temáticas.
   - **Móvil**: Barra horizontal scrolleable (`overflow-x-auto`) con botones tipo píldora (`rounded-full`) y barra de desplazamiento oculta (`scrollbar-hide`).

---

## 9. Fallback Tipográfico y Estabilidad SSR (H1–H6)

Para garantizar un renderizado en servidor (SSR) libre de excepciones y un fallback coherente:
- Los diccionarios por defecto (`headingDefaults`) en `resources/js/Frontend/Layouts/PublicLayout.vue` y `resources/js/Pages/Paginas/Builder.vue` definen rigurosamente los 6 niveles tipográficos (`h1` a `h6`) con tamaño (`size`), interlineado (`line_height`) y espaciado de letras (`letter_spacing`).
- Previene errores de tipo `TypeError: Cannot read properties of undefined (reading 'size')` durante la compilación e hidratación de páginas sin configuración tipográfica previa.

---

## 10. Página de Error Pública y 404 Personalizada (`resources/js/Pages/Error.vue`)

La vista de errores del sistema (`404`, `403`, `500`, `503`) está integrada dentro de `PublicLayout` preservando la cabecera y el pie de página para una navegación ininterrumpida:
- **Estética Glassmorphism Premium**: Tarjeta central translúcida con desenfoque de fondo (`backdrop-blur-xl`), halo de luces flotantes (`glow orbs`) y código de estado con degradado dinámico.
- **Iconografía Semántica**: Badge central con iconos de PrimeIcons adaptados a cada código (`pi-compass` para 404, `pi-lock` para 403, `pi-server` para 500, `pi-wrench` para 503).
- **Buscador Directo Integrado (404)**: Permite a los usuarios buscar contenidos directamente desde la pantalla de error redirigiendo fluidamente a `/buscar?q=...`.
- **Acciones Rápidas**: Botón principal a la portada y botón de retroceso inteligente en el historial del navegador (`window.history.back()`).

---

## 11. Optimización de Rendimiento Público, Core Web Vitals e Higiene de Payloads

Para mantener la máxima puntuación en auditorías de rendimiento y SEO en cualquier proyecto construido sobre este CMS:

- **Carga Diferida de PrimeVue + Tema Aura + Ziggy solo en Administración (`resources/js/app.js` y `resources/js/ssr.js`)**:
  - Las vistas públicas (`Public/*` y `Error`) no emplean componentes de PrimeVue. `app.js` detecta el contexto en `setup()` y monta Vue inmediatamente con una directiva ligera accesible `v-tooltip`, evitando descargar y ejecutar ~190 KB de JavaScript administrativo (`PrimeVue`, `@primevue/themes/aura`, `ZiggyVue`, `ToastService` y `ConfirmationService`) ni inyectar variables CSS del tema de administración en el cliente público.
  - En `/panel/*`, `/acceso` y el visor del Page Builder se carga mediante `Promise.all([import(...)])` el stack administrativo completo sin pérdida funcional.
- **Google Analytics 4 y Gestor de Cookies sin Bloqueo de LCP (`useCookieConsent.js` y `PublicLayout.vue`)**:
  - La inicialización de `vanilla-cookieconsent` se programa en `requestIdleCallback` para no competir con la hidratación del Hero.
  - Cuando el visitante tiene aceptadas las cookies analíticas, `activarGoogleAnalytics()` registra de inmediato `consent`, `config` y el evento `page_view` en `window.dataLayer` (cero pérdida de visitas), pero pospone la descarga del script externo `googletagmanager.com/gtag/js` hasta `requestIdleCallback` tras `window.load` o la primera interacción del usuario (`scroll`, `touchstart`, `pointerdown`, `keydown`).
- **Shimmer Skeleton Compuesto en GPU e Hidratación SSR de SVGs (`ResponsiveImage.vue`)**:
  - La animación `.ri-shimmer::after` utiliza exclusivamente `transform: translate3d(...)` con `will-change: transform`, ejecutándose en el compositor GPU sin provocar repintados (*non-composited animations*) ni alteraciones de *layout* (`CLS`).
  - Soporta la prop `shimmer` (`true` por defecto), permitiendo desactivar el fondo sólido del esqueleto cuando se renderizan logotipos transparentes (`contain`) sobre fondos degradados en carruseles planos (`plain`).
  - En `onMounted()`, detecta recursos descargados antes de la hidratación SSR tanto mediante `el.naturalWidth > 0` como mediante `el.decode()` para archivos vectoriales `.svg` definidos únicamente con `viewBox` (sin atributos `width`/`height` explícitos en el nodo raíz `<svg>`).
- **Higiene y Privacidad de Payloads Públicos Inertia (`HandleInertiaRequests`, `PublicPageController`, `PageBuilderResolverService`, `ArticleResource`)**:
  - **Anti-Scraping de Contacto**: Los valores crudos `whatsapp_phone`, `whatsapp_message`, `contact_email` y `contact_phone` se omiten de `siteSettings` en rutas públicas (exponiendo únicamente indicadores booleanos `has_whatsapp`, `has_contact_email`, `has_contact_phone` hacia los endpoints puente `/whatsapp`, `/email` y `/llamar`).
  - **Eliminación de Datos Duplicados**: En categorías y artículos renderizados mediante plantillas del Page Builder, no se serializan por duplicado colecciones de tarjetas ni el `body` HTML completo si la plantilla ya resuelve sus propios bloques, excluyendo además columnas pesadas (`body`, `extra_fields_schema`, `admin_label`) en listados públicos.

---

## 12. Composables Reutilizables

| Composable | Ubicación | Función |
|---|---|---|
| `useSanitize` | `Composables/useSanitize.js` | Sanitización XSS con DOMPurify (isomorphic), normalización declarativa de eventos inline (`onclick` a `data-cms-open-url`) compatible con CSP estricta y sanitización SSR de etiquetas `<style>` (`sanitizeInlineCss`) sin escapar selectores CSS |
| `useCookieConsent` | `Composables/useCookieConsent.js` | Gestión SSR-safe de consentimiento RGPD (Orest Bida v3) y Google Consent Mode v2 / GA4 diferido en reposo |
| `useUrlHelpers` | `Composables/useUrlHelpers.js` | Discriminación centralizada entre navegación SPA Inertia (`<Link>`) y enlaces nativos (`<a>`, canales de contacto, descargas) |
| `useAutoExpire` | `Composables/useAutoExpire.js` | Detección reactiva en tiempo real (Zero Polling) de caducidad en artículos (`useSingleItemExpire`) y auto-recarga suave de cuadrículas (`useListAutoExpire`) |
| `usePageBuilder` | `Composables/usePageBuilder.js` | Estado reactivo del Page Builder, mutaciones, autoguardado y previsualización |
| `useElementAnimation` | `Composables/useElementAnimation.js` | Cálculo y composición de retardo jerárquico de animaciones en Secciones, Filas, Columnas y Elementos |
| `useScrollAnimation` | `Composables/useScrollAnimation.js` | Motor de animaciones al scroll de alto rendimiento mediante IntersectionObserver compartido y repetición en vivo |
| `useRoles` | `Composables/useRoles.js` | Gestión de roles y permisos del usuario autenticado |
