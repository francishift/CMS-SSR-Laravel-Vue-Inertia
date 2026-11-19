# Documentación Técnica: Constructor Visual de Páginas (Page Builder)

El **Constructor Visual de Páginas** (`resources/js/Pages/Paginas/Builder.vue`) es el motor de maquetación del CMS. Ha sido desarrollado bajo los **Principios SOLID**, **Clean Architecture** y patrones de **Componentes Atómicos Desacoplados**, dividiendo la lógica de negocio en Composables reutilizables e Inspectores específicos para garantizar alta cohesión, bajo acoplamiento y máxima escalabilidad.

- **Arquitectura de Iframe Aislado Reactivo (`BuilderPreviewFrame.vue`)**: El lienzo de previsualización se ejecuta en un iframe HTML/DOM independiente. Garantiza un scroll 100% nativo del navegador, aislamiento total de estilos CSS del panel de administración, soporte nativo de breakpoints de Tailwind (`sm:`, `md:`, `lg:`, `xl:`) al redimensionar y sincronización en memoria continua (< 1ms) mediante `postMessage`.
- **Maquetador Global del Pie de Página (Footer Builder)**: Accesible desde la Configuración Global en `/panel/configuracion/footer/builder`. Permite diseñar visualmente el pie de página de todo el sitio utilizando la misma interfaz del Page Builder. El layout se almacena en `site_settings.footer_layout_json` y se renderiza públicamente con `PublicFooter.vue` y `PageBuilderRenderer.vue`.
- **Árbol de Estructura (`BuilderTree.vue`)**: Representación visual de Secciones, Filas, Columnas y Elementos Modulares con reordenación drag-and-drop.
- **Motor de Animaciones al Scroll (`useScrollAnimation.js` y `useElementAnimation.js`)**: Animaciones 3D aceleradas por GPU (`translate3d`), cálculo acumulativo de retardo jerárquico (sección + columna + elemento) y botón de prueba/replay en tiempo real.
- **Inspectores Modulares Adicionales**:
  - **Elemento Icono (`ElementIconInspector.vue` y `ElementIcon.vue`)**: Selector visual PrimeIcons y variables dinámicas (`{article.icon}`, `{category.icon}`), 8 tamaños predefinidos y personalizado en px, colores de icono y fondo, 5 estilos de contenedor (sin fondo, suave, sólido, circular), y **texto opcional junto al icono** alineado verticalmente a la derecha (`content.text`, `text_size`, `text_weight`, `text_color`, `text_gap`) que forma parte integral del enlace cuando hay una URL asignada.
  - **Elemento Paginación (`ElementPaginationInspector.vue`)**: Navegación entre artículos anterior/siguiente (5 variantes: Cards, Minimal, Pills, Hero, Compact) y paginación numérica de archivos.
  - **Menú de Navegación (`ElementMenuInspector.vue` y `ElementMenu.vue`)**: Renderiza menús de navegación activos (Cabecera, Barra Lateral o Menús Independientes/Personalizados creados en `/panel/menus` con ubicación *"Sin asignar / Page Builder"*) con orientación (horizontal/vertical), alineación, escala de fuentes, colores, prefetch automático y soporte para `rel="nofollow"` por enlace.
  - **Redes Sociales (`ElementSocialInspector.vue`)**: Renderiza los iconos de redes sociales activas con presets visuales (cuadrados, redondos, sin fondo, sólidos), escalas de tamaño y paletas de color.
  - **Elemento Vídeo HTML5 (`ElementVideo.vue` e `ElementVideoInspector.vue`)**: Reproductor HTML5 de rendimiento extremo con preloader de doble anillo giratorio, resplandor pulsante, botón flotante blanco glassmorphism (SVG gigante al 75%), pausado al salir del viewport (`pause_on_scroll`), fragmentos `#t=0.001` para el primer fotograma sin poster, y atenuación de peticiones por `IntersectionObserver`.
  - **Elemento Mapa Interactivo (`ElementMap.vue`, `ElementMapInspector.vue` y `LocationPickerModal.vue`)**: Renderiza mapas interactivos con soporte dual para Google Maps (JavaScript API con API Key cifrada del CMS y stylers nativos) y OpenStreetMap (Leaflet, privado, sin coste ni clave). Incluye selector visual de coordenadas con **pin arrastrable al milímetro**, clic directo en el mapa, geocodificación de direcciones vía Nominatim, botón GPS de ubicación física, sincronización bidireccional automática centro-marcador, colorimetría avanzada (saturación -100 a +100, tono/hue corporativo, luminosidad e invertir modo noche), soporte de marcadores dinámicos desde artículos o categorías, encuadre automático (auto-fit bounds), scroll cooperativo, altura adaptativa y corrección de espacio superior de InfoWindow.
  - **Galería de Imágenes/Vídeos (`ElementGallery.vue` e `ElementGalleryInspector.vue`)**: Grid/Masonry responsivo, lightbox con navegación circular, hover zoom suave (propiedad CSS `scale` nativa de Tailwind v4), overlays, captions, filtro por tags, y animación en cascada escalonada (stagger) por cada imagen. Soporta **modo dinámico** con dos orígenes: *Campos del artículo actual* (multi-select de campos `extra_fields_data` de tipo imagen) y *Artículos del CMS* (categoría + mapeo de campos, como el Grid).
  - **Elemento Formulario (`ElementForm.vue` e `ElementFormInspector.vue`)**: Renderiza formularios dinámicos del CMS en la web pública con 15+ tipos de campo, grid responsivo de 12 columnas (full/half/third), lógica condicional, validación en cliente, enlaces legales interactivos en casillas/interruptores con ventana modal flotante desacoplada de eventos, envío AJAX seguro con CSRF, honeypot anti-bot invisible, 4 temas visuales (default, transparente, fondo, glassmorphism), feedback animado (check SVG de éxito, spinner, shake en error) y dark mode completo. El endpoint `schema` filtra las claves internas con whitelist para no exponer reglas de validación ni datos de admin.

---

## 1. Arquitectura de Componentes y Composables

El maquetador visual sigue una arquitectura modular en `resources/js/`:

```
resources/js/
├── Config/
│   └── gridPresets.js                        # Catálogo de 24 layouts de cuadrícula, generador de clases responsivas de ancho y orden
├── Composables/
│   ├── usePageBuilder.js                     # Gestión del estado del builder, selecciones, mutaciones y peticiones API
│   ├── useElementAnimation.js                # Cálculo de retardo jerárquico e inyección de atributos de animación
│   └── useScrollAnimation.js                 # Motor compartido de IntersectionObserver y repetición en vivo
├── Components/
│   └── Builder/
│       ├── BuilderHeader.vue                 # Barra superior (dispositivos, guardar, previsualizar)
│       ├── BuilderTree.vue                   # Árbol de estructura drag-and-drop
│       ├── BuilderInspector.vue              # Orquestador del panel de inspección (Pestaña Ajustes)
│       ├── GridPresetModal.vue               # Modal de selección visual de 24 layouts de cuadrícula (Wireframes)
│       ├── DynamicTagSelector.vue            # Selector de variables dinámicas de artículo, categoría y sitio
│       │   └── Inspectors/
│       │       ├── SectionInspector.vue      # Inspector atómico de Sección
│       │       ├── RowInspector.vue          # Inspector atómico de Fila (Presets, tabs Layout/Columnas y ancho de contenedor)
│       │       ├── ColumnLayoutInspector.vue # Inspector granular de ancho por breakpoint y orden de columnas
│       │       ├── ColumnInspector.vue       # Inspector atómico de Columna (Disposición, Ancho & Grid, Fondo Imagen/Vídeo, CSS)
│       │       ├── ElementHeadingInspector.vue # Inspector de Encabezado
│       │       ├── ElementTextInspector.vue    # Inspector de Texto Párrafo
│       │       ├── ElementImageInspector.vue   # Inspector de Imagen
│       │       ├── ElementVideoInspector.vue   # Inspector de Vídeo HTML5
│       │       ├── ElementButtonInspector.vue  # Inspector de Botón CTA
│       │       ├── ElementIconInspector.vue    # Inspector de Icono Modular y Variables Dinámicas ({article.icon}, etc.)
│       │       ├── ElementPaginationInspector.vue # Inspector de Paginación y Navegación entre Artículos
│       │       ├── ElementGridInspector.vue    # Inspector de Grid Dinámico/Manual
│       │       ├── ElementGalleryInspector.vue  # Inspector de Galería (Manual + Dinámico con 2 orígenes)
│       │       ├── ElementMapInspector.vue      # Inspector de Mapa (Google Maps / OpenStreetMap, Marcadores, Colorimetría)
│       │       ├── ElementFormInspector.vue     # Inspector de Formulario Dinámico (selección por slug, tema visual, esquinas, sombras)
│       │       ├── ElementAccordionInspector.vue # Inspector de Acordeón FAQ
│       │       ├── ElementMenuInspector.vue    # Inspector de Menú de Navegación
│       │       ├── ElementSocialInspector.vue  # Inspector de Redes Sociales
│       │       ├── ElementSublayoutInspector.vue # Inspector de Subdiseño / Sublayout (Cuadrículas anidadas y columnas auto)
│       │       ├── AnimationBlock.vue          # Bloque reutilizable de animación y retardo
│       │       └── ElementHtmlInspector.vue    # Inspector de Código HTML Libre
│       └── Modals/
│           ├── AddElementModal.vue           # Modal catálogo de elementos modulares
│           ├── LocationPickerModal.vue       # Modal selector visual interactivo de coordenadas con pin arrastrable
│           └── SaveToLibraryModal.vue        # Modal de guardado de presets en biblioteca
└── Pages/
    └── Paginas/
        ├── Builder.vue                       # Orquestador principal del panel del maquetador
        └── BuilderPreviewFrame.vue           # Lienzo de previsualización en iframe aislado
```

---

## 2. Reglas Técnicas de Persistencia y Modelos Computados (PrimeVue 4)

### 2.1. Desambiguación de Componentes PrimeVue 4 `<Select>`
En PrimeVue 4, los componentes `<Select optionValue="value" />` interpretan la cadena vacía (`value: ''`) como ausencia de valor/estado nulo, dejando la caja del desplegable completamente en blanco.
- **Solución Estándar del Proyecto**: Se asignan cadenas explícitas no vacías (`'transparent'`, `'inherit'`, `'none'`, `'cover'`, `'center center'`, etc.) a las opciones por defecto.
- **Modelos Computados Reactivos**: En inspectores como `SectionInspector.vue`, se utilizan getters y setters computados (`bgPresetModel`, `textColorModel`, `stickyModel`, `bgSizeModel`, `bgPositionModel`, etc.) para devolver siempre un valor de coincidencia válido.

### 2.2. Re-vinculación del Árbol de Objetos tras Guardar (`guardarBuilder`)
Al guardar la estructura mediante Inertia (`form.put`), el servidor responde actualizando `props.pagina.layout_json`. Al clonar reactivamente el nuevo árbol (`layout.value = JSON.parse(JSON.stringify(newLayout))`), la referencia previamente seleccionada (`elementoSeleccionado.value`) quedaría huérfana en memoria si no se re-vincula.
- **Reconexión Recursiva por ID**: Tanto en `guardarBuilder()` como en el observador `watch(() => props.pagina?.layout_json)`, se ejecuta `buscarElementoPorId(currentSelectedId)` para volver a conectar `elementoSeleccionado.value` con el nodo vivo del árbol activo, permitiendo guardados ilimitados consecutivos sin perder la reactividad.

---

## 3. Estilos Visuales y Ajustes Avanzados de Sección (`BuilderSection.vue`)

### 3.1. Gestión Unificada de Fondos y Especificidad CSS
- **Precedencia de Presets (`bg_preset`)**: Los presets visuales predefinidos (*Blanco Puro*, *Gris Suave*, *Zinc Oscuro*, *Esmeralda Suave*, *Gradiente Oscuro*, *Gradiente Esmeralda*) se aplican vía clases CSS sin ser anulados por estilos inline.
- **Color Personalizado (`bg_color`)**: Aplica como `backgroundColor` inline únicamente cuando no hay preset o el preset es `'transparent'`. Incluye un botón de limpieza rápida en la interfaz para restablecer el color.

### 3.2. Panel Avanzado de Imagen de Fondo Condicional (`v-if="bg_image"`)
Para no saturar la interfaz, el subpanel de opciones avanzadas de imagen se muestra de forma condicional únicamente cuando la sección dispone de una ruta de imagen asignada:
- **Capa Única Dedicada (`bgImageLayerStyle`)**: Renderiza una capa `<div class="absolute inset-0 z-0 builder-bg-layer" />` optimizada, desacoplada del flujo flex para admitir transparencias y capas de superposición z-index sin bloquear eventos de puntero (`pointer-events-none`).
- **Control Responsivo por Dispositivo [PC | Tablet | Móvil]**: Conmutador de 3 pestañas para definir comportamientos independientes por tamaño de pantalla:
  - **Escritorio (PC)**: Tamaño base (`cover`, `contain`, `auto`, `100% auto`, `auto 100%`), posición focal en 9 cuadrantes, repetición mosaico, modos de fusión (`blend-mode`) y filtros visuales (`filter`).
  - **Tableta (768px - 1024px)**: Ajustes exclusivos de escalado (`bg_size_tablet`) y posición (`bg_position_tablet`) con opción de *"Heredar de Escritorio"*.
  - **Móvil (< 768px)**: Ajustes exclusivos de escalado (`bg_size_mobile`), posición (`bg_position_mobile`), imagen de fondo alternativa vertical (`bg_image_mobile`) y visibilidad (`bg_hide_mobile` para `hidden md:block`). Cuando se define `bg_image_mobile`, se optimiza automáticamente a 768px WebP en smartphones.
- **Soporte de Variables Dinámicas en Ajustes de Sección**: Tanto en el cliente (`resolveDynamicTokensClient`) como en el servidor (`PageBuilderResolverService`), las etiquetas dinámicas como `{article.featured_image}`, `{article.image}` o `{category.image}` se sustituyen automáticamente en los `settings` de las secciones (incluyendo `bg_image`), permitiendo que las plantillas de artículos y categorías muestren sus imágenes destacadas como fondos de sección sin configuración manual.
- **Arquitectura de Variables CSS Nativas**:
  - En lugar de ejecutar listeners de Javascript en el cliente, la sección inyecta variables CSS (`--bg-sz-desk`, `--bg-pos-desk`, `--bg-sz-tab`, `--bg-pos-tab`, `--bg-sz-mob`, `--bg-pos-mob`, `--bg-url-desk`, `--bg-url-tab`, `--bg-url-mob`).
  - Las reglas `@media (min-width: 768px)` y `@media (min-width: 1024px)` en `app.css` aplican los estilos en tiempo cero en el navegador sin recálculos del hilo principal.

### 3.3. Optimización SEO, Core Web Vitals (LCP) y Compresión WebP en Fondos
- **Conversión Automática a WebP**: Las imágenes de fondo locales se enrutan mediante `useImage.js` hacia el endpoint `/media/render` del motor `MediaImageService`, entregando variantes WebP con compresión de alta fidelidad:
  - Móvil (`< 768px`): `w=768, q=82, f=webp` (~14.7 KB frente a 777 KB del PNG maestro, -98.1% de peso).
  - Tablet (`768px - 1024px`): `w=1280, q=85, f=webp` (~60 KB).
  - Escritorio (`> 1024px`): `w=1920, q=85, f=webp` (~65.6 KB, -91.6% de peso).
- **Preload LCP con Coincidencia Exacta de Media Queries**:
  En la primera sección (`isFirstSection`), el componente inyecta etiquetas `<link rel="preload">` en el `<head>` del documento HTML mediante Inertia SSR con atributo `fetchpriority="high"` y `media="..."` coincidente con el CSS:
  ```html
  <link rel="preload" as="image" href="/media/render?...w=768..." media="(max-width: 767px)" fetchpriority="high">
  <link rel="preload" as="image" href="/media/render?...w=1280..." media="(min-width: 768px) and (max-width: 1023px)" fetchpriority="high">
  <link rel="preload" as="image" href="/media/render?...w=1920..." media="(min-width: 1024px)" fetchpriority="high">
  ```
  Esto garantiza que tanto en pruebas de Google Lighthouse / PageSpeed móvil como en dispositivos de escritorio reales, la solicitud pre-cargada coincida exactamente con la URL requerida por el CSS, satisfaciendo la auditoría de LCP Request Discovery al 100%.

### 3.4. Cabecera Transparente Detrás de la Primera Sección (`transparent_header`)
Permite situar la primera sección del diseño en `top: 0` (`y = 0`) para que la imagen o gradiente de fondo cubra todo el viewport, pasando por detrás del menú de navegación:
- **`transparent_header: true`**: Activa la cabecera transparente flotante en la primera sección.
- **`transparent_header_pull: true`**: Permite que el contenido interno de la sección comience desde arriba del todo. Si se desactiva (`false`), se aplica un padding compensatorio automático (`pt-16 sm:pt-20 lg:pt-24`) para que el contenido no quede tapado por los enlaces del menú mientras el fondo sí sube hasta el borde superior.
- **Inyección Isomórfica Servidor / Cliente**: Configuración propagada vía middleware `HandleInertiaRequests` e inyectada jerárquicamente en `PublicLayout.vue` y `PublicHeader.vue` con soporte reactivo en tiempo real en el iframe del editor mediante `provide/inject`.

### 3.5. Forzado Profundo de Color de Texto (`textColorClass`)
Dado que las clases directas de Tailwind en componentes hijos (como `<h2 class="text-zinc-900">`) sobrescriben la herencia de fuente del elemento padre `<section>`, se han creado clases de anulación profunda en `resources/css/app.css`:
- **`.section-text-light`**: Aplica `color: #ffffff !important` sobre todos los encabezados (`h1`-`h6`), párrafos (`p`), listas (`li`), etiquetas y divisores pertenecientes a la sección cuando se activa *"Claro (Forzar Texto Blanco)"*.
- **`.section-text-dark`**: Aplica `color: #18181b !important` sobre todos los textos hijos cuando se activa *"Oscuro (Forzar Texto Negro)"*.

### 3.6. Vídeo de Fondo en Secciones (`bg_type: 'video'`)
Cualquier sección del Page Builder permite alternar entre una imagen panorámica y un vídeo de fondo HTML5 en bucle continuo, conservando el 100% de la configuración de estilos, overlays y colores:
- **Conmutador Visual de Modo (`bg_type`)**: Pestañas en el inspector de sección `[ Imagen | Vídeo ]` que permiten alternar sin destruir las configuraciones previas de ninguno de los dos medios.
- **Reproducción Fluida y Silenciada (Autoplay Policy)**: El tag `<video>` incorpora los atributos estándar `autoplay`, `muted`, `loop`, `playsinline` y `webkit-playsinline`. En el cliente, el gancho `onMounted()` asegura la propiedad DOM `muted = true` y lanza `.play().catch(...)` de forma segura, garantizando la reproducción instantánea en Chrome, Safari (macOS/iOS), Edge y Firefox.
- **Optimización de GPU y Batería (`IntersectionObserver`)**: Para evitar el consumo innecesario de ciclos de GPU al decodificar vídeo fuera de pantalla, un observador de intersección con margen de `150px` pausa el vídeo (`video.pause()`) cuando el usuario hace scroll y la sección abandona el viewport, reanudándolo inmediatamente al reingresar.
- **Ahorro de Datos en Smartphones (`bg_video_mobile`)**:
  - `fallback` *(Recomendado por defecto)*: En pantallas móviles (`< 768px`) o en la simulación móvil del lienzo, el navegador no descarga ni instancia el tag `<video>`, sirviendo directamente la imagen póster optimizada en WebP. Ahorra entre 5MB y 30MB de datos móviles por visita.
  - `play`: Permite al usuario forzar el intento de reproducción de vídeo también en smartphones si la política energética del dispositivo lo autoriza.
- **Core Web Vitals y Precarga LCP**:
  - Si la sección con vídeo es la primera de la página (`isFirstSection`), se inyecta en el `<head>` mediante Inertia SSR un enlace `<link rel="preload" as="image" :href="posterUrl" fetchpriority="high">`. La imagen póster se pinta en menos de 0.5s satisfaciendo el Largest Contentful Paint (LCP) al 100%, mientras el vídeo se inicializa en segundo plano con `preload="metadata"`.
  - Si no se define una imagen póster explícita (`bg_video_poster`), el sistema utiliza automáticamente como respaldo la imagen de fondo ya configurada en `bg_image`.
- **Encuadre Focal y Velocidad de Reproducción**:
  - `bg_video_position`: Permite calibrar el `object-position` en 5 posiciones clave (`center center`, `top center`, `bottom center`, `left center`, `right center`) para asegurar que el sujeto principal no quede recortado en pantallas ultrapanorámicas (21:9) o verticales.
  - `bg_video_playback_rate`: Modula la velocidad de reproducción (`0.5x` cámara lenta, `0.75x` cinemático suave, `1.0x` estándar, `1.25x` dinámico).
- **Accesibilidad (`prefers-reduced-motion`)**: Consulta `window.matchMedia('(prefers-reduced-motion: reduce)')`. Si el visitante tiene configurada la reducción de movimiento en su sistema operativo, el vídeo no se reproduce en bucle y se muestra la imagen estática.
- **Limpieza de Recursos en Navegación SPA**: En el gancho `onBeforeUnmount()`, se desconecta el observador, se pausa la reproducción, se retira el atributo `src` y se invoca `video.load()`, evitando fugas de memoria y manteniendo libres los decodificadores de hardware durante la navegación entre páginas con Inertia.

### 3.7. Vídeo de Fondo en Columnas (`BuilderColumn.vue` / `ColumnInspector.vue`)
El soporte de vídeo multimedia de fondo se extiende con total paridad arquitectónica a cada Columna individual del constructor:
- **Pestaña Fondo en ColumnInspector**: Dispone del selector conmutador `[ Imagen | Vídeo ]` (`bg_type: 'image' | 'video'`), permitiendo a los editores alternar entre una imagen estática y un clip de vídeo de fondo sin perder configuraciones preexistentes.
- **Recorte Exacto con Esquinas Redondeadas (`border_radius`)**: A diferencia de las secciones, las columnas suelen estructurarse como tarjetas con bordes redondeados (`rounded-xl`, `rounded-2xl`, etc.). La capa `.builder-column-video-layer` hereda `overflow-hidden` del marco de la columna, garantizando un recorte milimétrico del vídeo sin sangrías ni desbordamientos visuales.
- **Soporte Concurrente y Gestión Eficiente de GPU**: Al poder coexistir múltiples columnas con vídeo en una misma fila, el `IntersectionObserver` independiente por columna asegura que sólo se reproduzcan los vídeos actualmente visibles en el viewport. Al hacer scroll fuera de pantalla, cada vídeo se pausa inmediatamente liberando decodificadores de hardware.
- **Fallback Automático y Ahorro en Móviles**:
  - `bg_video_mobile: 'fallback'`: Sirve la imagen póster (`bg_video_poster` o `bg_image`) en pantallas táctiles móviles (`< 768px`) y en la simulación móvil del editor, previniendo descargas pesadas de datos móviles.
  - `bg_video_mobile: 'play'`: Fuerza la reproducción si el diseño o el cliente lo requiere.
- **Parámetros de Reproducción Avanzados**: Configuración de `bg_video_position` (encuadre focal en 5 posiciones) y `bg_video_playback_rate` (de `0.5x` a `1.25x`), junto con la capa de superposición (`bg_overlay`) y color de fondo base (`bg_color`).
- **Limpieza SPA Completa**: En `onBeforeUnmount()`, el observador se desconecta y el `<video>` se pausa y vacía con `.load()`, garantizando cero fugas de memoria en navegación reactiva Inertia.

---

## 4. Controles del Pie de Página (Footer Builder)

El pie de página se administra como una estructura JSON de Page Builder almacenada en `site_settings.footer_layout_json`:
1. En `/panel/configuracion`, al pulsar **"Maquetar Footer con Builder"**, se abre el maquetador visual pre-cargando la estructura del pie de página.
2. Al guardar, los cambios actualizan la base de datos e invalidan la caché pública en Redis (`CacheInvalidationService::limpiarCachePublica()`).
3. En la web pública, `PublicFooter.vue` comprueba la presencia de `footer_layout_json` y renderiza el layout mediante `PageBuilderRenderer.vue`.

---

## 5. Arquitectura de Iframe Aislado y Sincronización en Memoria

Para resolver colisiones de estilos CSS, fallos en fondos fijos (`bg-fixed`), alturas (`100vh`) y cortes de scroll en cabeceras flotantes/cápsula:
- **Lienzo Aislado (`BuilderPreviewFrame.vue`)**: El canvas de previsualización se monta dentro de un `<iframe>` embebido.
- **Scroll Nativo y Breakpoints Nativos**: Al cambiar el ancho del iframe a 768px (Tablet) o 390px (Móvil), las *media queries* `@media` de Tailwind se disparan de forma nativa sin necesidad de emulación por CSS container queries.
- **Canal de Comunicación Bidireccional (`postMessage`)**:
  - `CMS_BUILDER_UPDATE_LAYOUT`: Transfiere reactivamente en memoria el `layout_json` actualizado (< 1ms).
  - `CMS_BUILDER_SCROLL_TO`: Desplaza la vista previa hacia el elemento seleccionado en el árbol.
  - `CMS_BUILDER_REPLAY_ANIMATION`: Reproduce la animación del nodo activo.
  - `CMS_BUILDER_SELECT_ELEMENT`: Permite hacer clic directamente en cualquier elemento del lienzo para seleccionarlo en el árbol de administración.
- **Delegación de Eventos Interactivos en el Lienzo**: El escuchador de clics del iframe delega la selección del elemento hacia el editor padre pero permite que los componentes interactivos internos (como las miniaturas con lightbox de la galería, botones de acordeón FAQ y filtros de etiquetas) ejecuten su evento de clic con normalidad.
- **Preservación del Árbol de Componentes**: El lienzo compara la estructura JSON recibida (`lastLayoutJson`) antes de forzar un re-render con `renderVersion++`, garantizando que la selección de un nodo no destruya el estado interno del componente ni cierre el visor interactivo.

---

## 6. Motor de Animaciones al Scroll y Cascada Escalonada (Stagger)

El sistema incorpora un motor de animaciones al scroll de alto rendimiento (`useScrollAnimation.js`, `useElementAnimation.js` y `AnimationBlock.vue`):
- **Aceleración por GPU (3D Transforms)**: Utiliza `transform: translate3d(...)` y transiciones optimizadas de 0.75s con aceleración por hardware y 0% de sobrecarga en CPU.
- **Retardo Acumulativo Jerárquico**: Los retardos (`delay`) configurados en Secciones, Columnas y Elementos se suman automáticamente (`delayTotal = delaySeccion + delayColumna + delayElemento`), garantizando que los elementos hijos esperen a sus contenedores padres.
- **Animaciones en Cascada (Staggered)**:
  - **En Cuadrículas y Grids (`ElementGridArticles.vue`)**: En modo cascada, cada tarjeta individual calcula su retardo de forma progresiva:
    $$\text{Retardo Tarjeta } i = \text{Retardo Base} + \text{Offset Cabecera} + (i \times \text{Paso Cascada})$$
    Permitiendo que las tarjetas entren en una ola sucesiva fluida (ej. $+100\text{ms}$ por tarjeta) en lugar de mover todo el bloque rígido.
  - **En Secciones (`BuilderSection.vue` y `BuilderColumn.vue`)**: La sección propaga la configuración de cascada a sus columnas, asignando a cada una su tiempo incremental (`colIndex * stagger`) mientras el fondo de la página permanece estático.
  - **Modo en Bloque**: Opcional desde `AnimationBlock.vue` para animar todo el contenedor simultáneamente.
- **Amplitudes Visuales Profesionales**:
  - *Desvanecer*: Opacidad pura (`opacity: 0 -> 1`).
  - *Escala (Zoom)*: `scale(0.85)` / `scale(1.15)`.
  - *Deslizamientos (Arriba, Abajo, Izquierda, Derecha)*:
    - *Corto*: `30px`.
    - *Mediano*: `70px`.
    - *Completo*: `100%` del ancho/alto del contenedor.
- **Previsualización en Vivo**: El botón **"Probar"** en el inspector envía `CMS_BUILDER_REPLAY_ANIMATION` al iframe, ejecutando la animación escalonada en vivo con exactitud milimétrica.

### 6.1. Animación en Cascada de Galerías (`ElementGallery.vue`)
La galería implementa el mismo sistema de cascada que `ElementGridArticles.vue`, permitiendo que cada imagen entre con un retardo progresivo:
$$\text{Retardo Imagen } i = \text{Retardo Base} + (i \times \text{Paso Cascada})$$
Configurable desde `AnimationBlock` con `allowCascade`. Modo **Bloque** (todo a la vez) o **Cascada** (escalonado por imagen). Los `data-cms-animate` y `--cms-anim-delay` se aplican a cada `<figure>` individual tanto en layout Grid como Masonry.

### 6.2. Hover Zoom Suave y Compatibilidad con Tailwind CSS v4
En Tailwind CSS v4, las utilidades de escala (`scale-105`, `group-hover:scale-105`) generan la **propiedad CSS nativa `scale`** en lugar de `transform: scale()`. La transición de la imagen (`.ri-visible` en `ResponsiveImage.vue`) debe incluir explícitamente `scale` además de `transform` y `opacity`:
```css
transition: opacity 0.35s ease-out, transform 0.8s cubic-bezier(0.25, 0.1, 0.25, 1), scale 0.8s cubic-bezier(0.25, 0.1, 0.25, 1);
```
Sin esta inclusión, el hover zoom se aplica instantáneamente sin animación.

### 6.3. Lightbox con Navegación Circular (`GalleryLightbox.vue`)
El visor ampliado implementa navegación circular (loop infinito): al llegar a la última imagen, "Siguiente" vuelve a la primera; al estar en la primera, "Anterior" salta a la última. Los botones de flechas se muestran siempre (ocultos solo si hay una sola imagen). Soporta teclas ← →, swipe táctil y clic en overlay para cerrar.

- **Operatividad Isomórfica (Web Pública y Canvas del Builder)**: Mediante `<Teleport to="body">`, el lightbox se monta en el `document.body` del entorno activo. En el lienzo del maquetador, queda fuera de los contenedores estructurales con `[data-builder-id]`, lo que permite abrirlo, probar la navegación en alta resolución y cerrarlo con la `X` o la tecla `ESC` directamente desde el lienzo del constructor sin interrumpir la sesión de edición.

### 6.4. Galería Dinámica y Manual: Persistencia, Orígenes de Datos y Resolución
La galería soporta dos modos de contenido seleccionables en el inspector:

1. **Manual**: Gestión tradicional de ítems con Acordeón + Drag & Drop. Cada ítem almacena `id`, `src`, `alt`, `title`, `caption`, `tags`, `link`, `type` (`image` o `video`) y `poster`.
   - **Garantía de Persistencia e Integridad (`content: {}`)**: En JavaScript, `content` debe ser siempre una instancia de `Object` (`{}`), nunca un `Array` (`[]`). Si un elemento llega deserializado desde la base de datos con `content: []` vacío, el composable `usePageBuilder.js` (`normalizarArbolLayout`) y el inspector `ElementGalleryInspector.vue` (`ensureItems` y watcher reactivo) lo normalizan de inmediato a objeto asociativo `{ mode: 'manual', items: [] }`. Esto previene que `JSON.stringify()` ignore propiedades asignadas a arrays y garantiza que las imágenes seleccionadas persistan siempre al guardar.
2. **Dinámico**: Las imágenes se obtienen automáticamente del CMS, con dos orígenes:

#### Origen: Campos del Artículo Actual (`source: 'current_article'`)
Ideal para galerías dentro de **plantillas de artículo** (`single_article`). El usuario selecciona mediante checkboxes qué campos de tipo `image` del `extra_fields_schema` de la categoría quiere incluir. Cada campo con valor se convierte en un ítem de galería; los vacíos se omiten.

| Componente | Responsabilidad |
|---|---|
| `ElementGalleryInspector.vue` | Multi-select de campos imagen con botones "Todos/Ninguno". Almacena `content.dynamic_binding.selected_image_fields: ['gal-1', 'gal-2', ...]` |
| `PageBuilderResolverService::resolveForArticle()` | Detecta galerías con `source: 'current_article'`, extrae los valores de `extra_fields_data` del artículo via `extraerItemsGaleriaDeArticulo()` e inyecta el array de ítems como `resolved_data` |
| `BuilderPreviewFrame.vue` | En la preview del builder, extrae los campos del `articuloMuestra` y los inyecta como `resolved_data` |
| `ElementGallery.vue` | Cuando `mode === 'dynamic'`, usa `props.resolvedData` como fuente de ítems en lugar de `content.items` |

#### Origen: Artículos del CMS (`source: 'articles'`)
Ideal para galerías en **páginas estáticas o plantillas de categoría**. Funciona igual que el Grid: selecciona una categoría (específica, activa o todas), y cada artículo contribuye una imagen según el campo mapeado (`featured_image` o cualquier `extra_fields_data.*`).

| Configuración | Almacenamiento en `content` |
|---|---|
| Categoría | `dynamic_binding.category_id` (`'all'`, `'current'`, o ID numérico) |
| Campo imagen | `dynamic_mapping.src_field` (ej. `'featured_image'` o `'extra_fields_data.gal-1'`) |
| Título/Caption/Alt | `dynamic_mapping.title_field`, `.caption_field`, `.alt_field` |
| Límite | `dynamic_binding.limit` (default 12) |
| Orden | `dynamic_binding.order_by` (default `'published_at_desc'`). Valores: `'published_at_desc'`, `'published_at_asc'`, `'title_asc'`, `'title_desc'`, `'manual'`/`'order_asc'` (Drag & Drop), `'default'`/`'backend'` (respeta el orden que envía el backend sin re-ordenar localmente en JavaScript) |

#### Regla de Fallback en Grid Dinámico (Campos Extra)
Cuando el Grid de artículos (`ElementGridArticles.vue`) mapea un campo de imagen extra (`extra_fields_data.gal-1`) y un artículo **no tiene** ese campo rellenado, **no se hace fallback** a la imagen destacada: la imagen queda vacía. El fallback a `featured_image` solo aplica cuando el campo mapeado es el propio `featured_image`.

#### Dependencia Crítica: `extra_fields_schema` y `articles_order` en Eager Loading
Para que los campos extra de artículos se expongan correctamente a través de `ArticleResource::obtenerExtraFieldsDataFiltrados()` y para que la ordenación y navegación anterior/siguiente respeten el criterio de la categoría, **todas** las queries que cargan artículos con relación `category` deben incluir `extra_fields_schema` y `articles_order` en el eager loading:
```php
->with(['category:id,name,slug,extra_fields_schema,articles_order,...', 'author:id,name'])
```
Esto aplica a: `TemplateBuilderController`, `PageBuilderController`, `SiteSettingController`, `PageBuilderResolverService`.

#### 6.4.1. Dimensiones, Alineación, Divisores y Filtros Visuales / Logos en Galería
El elemento Galería (`ElementGallery.vue` y su inspector `ElementGalleryInspector.vue`) cuenta con controles especializados para grids de marcas, logos de clientes, patrocinadores y portfolios:

1. **Ajuste de Imagen Transparente (Opción A)**: El modo `image_fit: 'contain'` no aplica ningún fondo por defecto (eliminando fondos grises no deseados), preservando la transparencia natural de archivos PNG y SVG.
2. **Dimensionamiento Global de Imágenes**:
   - `content.max_height_px`: Fija una altura máxima uniforme en píxeles (ideal para nivelar logos con alturas o proporciones dispares).
   - `content.max_width_px`: Fija una anchura máxima en píxeles.
   - En combinación con `contain`, las imágenes escalan proporcionalmente sin deformación y quedan centradas en su contenedor.
3. **Distribución y Alineación de Grid**:
   - `content.show_dividers` ("Mostrar Divisores"): Enmarca cada celda con un borde elegante y sutil (`border-zinc-200/80 dark:border-zinc-800/80`), padding simétrico y efecto hover reactivo.
   - `content.center_columns` ("Centrar columnas"): Centra horizontalmente los elementos dentro del grid (`justify-items-center justify-center text-center`).
   - `content.center_rows` ("Centrar Filas"): Centra verticalmente las imágenes entre sí dentro de cada fila (`items-center self-center`), logrando una línea visual equilibrada entre logos altos y anchos.
4. **Filtros Monocromo y de Color (Efecto Logo)**:
   - **Nivel General**: `content.is_white` (aplica `brightness-0 invert` a toda la galería) o `content.color_filter` (`none`, `white`, `dark_white`, `black`, `grayscale`, `grayscale_hover`).
   - **Nivel de Ítem Individual**: Cada ítem en el acordeón permite sobrescribir el filtro global (`item.is_white` o `item.color_filter: 'inherit' | 'none' | 'white' | 'dark_white' | 'black' | 'grayscale' | 'grayscale_hover'`). Esto permite, por ejemplo, convertir todos los logos a blanco excepto uno específico que deba conservar sus colores originales.

#### 6.4.2. Modo Carrusel Infinito (Logos y Marcas en Movimiento Continuo)
Al seleccionar `layout: 'carousel_infinite'` en el inspector de galería:
- **Animación Continua y Fluida**: Cinta horizontal infinita con duplicación técnica del conjunto de imágenes (`prepararCarrilInfinito`) para lograr un bucle del 50% sin saltos perceptibles (*marquee continuo* acelerado en GPU con `translate3d`).
- **Carga Inmediata sin Bloques Grises en los Clones (`loading="eager"`)**: Dado que las copias duplicadas nacen fuera del recorte horizontal (`overflow-x: clip`) y entran en pantalla impulsadas por el compositor GPU sin eventos de scroll, `ElementGallery.vue` fuerza `loading="eager"` en `ResponsiveImage` (el navegador reutiliza en memoria las mismas URLs sin peticiones de red extra) y desactiva el esqueleto `.ri-shimmer` en modo plano (`plain`) con ajuste `contain`, evitando destellos o rectángulos grises sobre fondos oscuros/transparentes.
- **Control de Separación (Gap) Real**: Ajuste responsivo de espaciado (`gap`, `gap_tablet`, `gap_mobile`) aplicado mediante variables CSS sin deformar ni recortar las dimensiones naturales de las imágenes o logotipos vectoriales (SVG).
- **Opciones Avanzadas del Carrusel**:
  - `carousel_rows`: Configuración en 1 o 2 filas sincronizadas en sentidos opuestos.
  - `carousel_speed`: Velocidad en segundos para recorrer un ciclo completo (de ultrarrápido a paso suave).
  - `carousel_direction`: Sentido de desplazamiento (`left` o `right`).
  - `carousel_pause_on_hover`: Pausa la animación CSS cuando el visitante sitúa el ratón encima.
  - `carousel_fade_edges`: Gradiente de máscara lineal (`mask-image`) en los extremos para un desvanecimiento suave contra el fondo de la sección.
  - `carousel_card_style`: Plano limpio (`plain`) o tarjeta de insignia con fondo y borde (`badge`).

---

### 6.5. Biblioteca de Presets y Guardado de Secciones Completas
El sistema de Biblioteca (`SaveToLibraryModal.vue` y `usePageBuilder.js`) permite guardar y reutilizar componentes:
1. **Guardado de Secciones Enteras**: A diferencia de los bloques aislados, una sección completa almacena toda su jerarquía: fondos avanzados, configuraciones de filas, distribución de columnas y todos los elementos hijos preconfigurados.
2. **Inserción Contextual**: Mediante `abrirModalBibliotecaSecciones(index)`, el usuario puede insertar una sección guardada en cualquier punto específico del diseño (`layout.sections.splice(targetIndex, 0, preset)`), facilitando la composición modular de páginas complejas en segundos.

---

### 6.6. Selector de Variables Dinámicas y Campos Extra (`DynamicTagSelector.vue`)
El selector emergente de variables dinámicas (`DynamicTagSelector.vue`) centraliza la inserción de tokens en cualquier entrada de texto, enlace o imagen del constructor visual, garantizando una arquitectura 100% desacoplada y libre de código espagueti en los inspectores atómicos:

- **Filtrado Estricto por Tipo (`tipo="link" | "url" | "image" | "icon" | "text"`)**:
  - `tipo="link"` o `tipo="url"`: Muestra únicamente enlaces (`{article.url}`, `{article.category.url}`, `{category.url}`, `{site.url}`) y campos extra del artículo catalogados como URL/enlace.
  - `tipo="image"`: Muestra únicamente imágenes (`{article.featured_image}`, `{category.featured_image}`) y campos extra de tipo imagen.
  - `tipo="icon"`: Muestra iconos estándar y campos extra de tipo icono.
  - `tipo="text"`: Muestra variables textuales y campos extra de texto/números/selectores.
- **Detección Automática de Esquemas de Campos Extra**:
  - Inyecta `articuloMuestra`, `categoriaMuestra` y `categoriasLista` provistos reactivamente por `Builder.vue`.
  - Agrupa los campos personalizados bajo la categoría **`Campos Extra (Artículo)`**, generando etiquetas `{article.extra_fields.campo_key}`.
---

### 6.7. Elemento Icono Modular con Texto Integrado y Control SEO No-Follow
El elemento Icono (`ElementIcon.vue` y `ElementIconInspector.vue`) combina la representación gráfica PrimeIcons / tokens dinámicos con capacidades de texto y enlazado profesional:
- **Texto Opcional Integrado**:
  - Parámetros: `content.text` (soporta variables dinámicas), `content.text_size` (`xs` a `2xl` o personalizado en px), `content.text_weight`, `content.text_color` e interletraje/espaciado `content.text_gap`.
  - El texto se alinea verticalmente al centro (`items-center`) junto al icono.
  - Cuando se define una URL de enlace, tanto el icono como el texto se envuelven en un **único hipervínculo clicable** (`<a>` o `<Link>`), garantizando una experiencia de usuario coherente y accesible.
- **Efecto Hover Progresivo**:
  - Control `content.hover_effect` con opciones `none`, `zoom_subtle`, `zoom_smooth` y `lift`.
  - La opción `zoom_smooth` aplica una transición CSS refinada `transform 400ms cubic-bezier(0.16, 1, 0.3, 1)` que evita brincos bruscos en el cursor.
- **Control SEO y Seguridad de Enlaces**:
  - `content.target`: Alterna entre abrir en la misma pestaña (`_self`) o en pestaña nueva (`_blank`).
  - `content.rel_nofollow`: Interruptor que añade `rel="nofollow"` para evitar el traspaso de autoridad/PageRank en enlaces comerciales o hacia dominios externos de terceros.
  - Generación dinámica de `rel`: En enlaces externos o con `target="_blank"`, combina automáticamente `noopener noreferrer` por seguridad contra ataques de *tabnabbing*, resultando en `rel="nofollow noopener noreferrer"`.

---

### 6.8. Elemento Paginación Modular y Navegación entre Artículos (`ElementPagination.vue` y `ElementPaginationInspector.vue`)

El elemento de paginación (`ElementPagination.vue`) gestiona la paginación y navegación secuencial en páginas, plantillas de catálogo y plantillas de detalle de artículos:

#### 1. Modos de Funcionamiento (`content.mode`)
- **`article_nav` (Navegación entre Artículos - Anterior / Siguiente)**:
  - Diseñado para vistas de detalle (`single_article`).
  - Ofrece 5 variantes visuales de maquetación:
    1. `cards`: Tarjetas lado a lado con títulos, flechas configurables y enlaces.
    2. `minimal`: Enlaces tipográficos sutiles con flechas.
    3. `pills`: Botones cápsula / píldoras estilizadas.
    4. `hero`: Tarjetas destacadas de gran formato con degradados y categoría.
    5. `compact`: Barra compacta unificada horizontal.
  - Opciones de personalización: etiquetas personalizadas (`custom_prev_label`, `custom_next_label`), visibilidad de títulos (`show_titles`), visibilidad y tipo de flechas (`show_arrows`, `arrow_type`: arrow, chevron, angle, circle), y botón central de retorno opcional (`show_home_button`, `home_button_label`, `home_button_url`, `home_button_icon`).
- **`archive_numeric` (Paginación Numérica 1, 2, 3...)**:
  - Diseñado para listados de archivo (`category_archive`), páginas estáticas y colecciones de artículos hermanos.
  - Ofrece 4 variantes visuales de maquetación:
    1. `numbers_boxed`: Botones cuadrados suaves estándar con estado activo de alto contraste.
    2. `pills`: Píldoras circulares con resplandor.
    3. `simple`: Botones Anterior / Siguiente con contador central (`Página X de Y`).
    4. `minimal`: Enlaces numéricos tipográficos con subrayado en la página activa.

#### 2. Comportamiento Contextual Isomórfico (Catálogo vs Serie de Artículos)
- **En Vistas de Catálogo (`category_archive`) o Páginas**:
  - Los números enlazan a las páginas correspondientes (`/{categoria}`, `/{categoria}/pagina/2`, `/{categoria}/pagina/3`...).
  - Las URLs son limpias y 100% amigables para SEO, integrando redirecciones 301 desde parámetros de query `?page=X` hacia las rutas canónicas.
- **En Vistas de Detalle de Artículo (`single_article`)**:
  - Cuando se configura el modo `archive_numeric` en una plantilla de artículo, cada número (1, 2, 3... N) enlaza directamente al slug canónico del artículo hermano dentro de la misma categoría (`/{categoria}/{slug-articulo}`).
  - El artículo actual se marca con `active: true` y se renderiza con el estilo activo.
  - **Prevención de URLs Erróneas**: Se garantiza que bajo ningún concepto se generen rutas inválidas ni duplicadas del tipo `/{categoria}/{slug-articulo}/pagina/2`.
  - Respeta estrictamente el criterio de ordenación definido en la categoría (`articles_order`: `published_at_desc`, `published_at_asc`, `title_asc`, `title_desc`, `manual`).

#### 3. Resolución en Servidor (`PageBuilderResolverService`)
- En `resolveForArticle()`, el servicio inyecta de forma recursiva (tanto en columnas principales como dentro de `sublayouts`) un objeto `resolved_data` que contiene simultáneamente:
  - `navegacion`: Datos del artículo anterior y siguiente optimizados en $O(1)$.
  - `paginacion`: Array completo de enlaces numéricos a los artículos hermanos con `is_article_series: true`, `current_page`, `last_page` y `total`.
- Al disponer de ambos nodos resueltos, el componente frontend responde de inmediato a cualquier conmutación de modo realizada en el inspector sin requerir recargas ni llamadas adicionales.

#### 4. Previsualización e Interactividad en el Canvas del Builder (`BuilderPreviewFrame.vue`)
- El iframe del maquetador recibe `tipoPlantilla` y `esPlantilla` mediante `provide()`.
- En modo `archive_numeric` sobre plantillas de artículo, la previsualización genera los botones interactivos correspondientes al conjunto de artículos de muestra de la categoría.
- Al hacer clic en un número dentro del iframe, se despacha el evento `cms-builder-pagination-change`, actualizando el artículo activo de muestra y re-renderizando dinámicamente los tokens en tiempo real sin recargar la página.

---

## 7. Navegación Sincronizada entre Páginas en el Maquetador

El maquetador permite navegar libremente entre páginas del CMS directamente desde la previsualización:
- **Detección de Clics en Enlaces**: Al pulsar sobre cualquier enlace (`<a>`) en menús de cabecera, pie de página o botones dentro del iframe, `BuilderPreviewFrame.vue` intercepta el clic y despacha `CMS_BUILDER_NAVIGATE_TO_URL`.
- **Resolución y Confirmación de Cambios**: El editor padre busca la URL en `paginasList`. Si hay cambios sin guardar, solicita confirmación mediante `PrimeVue ConfirmDialog`; si se acepta, navega a la página destino mediante Inertia.
- **Selector Rápido de Páginas en Cabecera (`BuilderHeader.vue`)**: Desplegable compacto en la barra superior para alternar de página al instante.

---

## 8. Experiencia y Adaptabilidad 100% Móvil del Page Builder

Para permitir la edición y maquetación fluida desde teléfonos móviles y tablets:
- **Conmutador «Editor ↔ Lienzo» (`BuilderHeader.vue`)**: En pantallas `< lg`, la cabecera ofrece un conmutador segmentado para alternar entre el panel de herramientas al 100% de ancho y la vista previa a pantalla completa.
- **Árbol de Estructura Auto-Adaptable (`BuilderTree.vue`)**: Las filas con 3 o 4 columnas emplean `grid-template-columns: repeat(auto-fit, minmax(130px, 1fr))` para que las cajas de columnas se organicen en grupos táctiles y legibles en pantallas estrechas.
- **Inspectores Responsivos**: Los campos y selectores contiguos utilizan `grid-cols-1 sm:grid-cols-3` para que los controles sean amplios y cómodos de pulsar con el dedo.

---

## 9. Sistema de Plantillas Dinámicas del Maquetador Visual (Theme Templates)

El CMS incorpora un sistema profesional de **Plantillas Dinámicas** (`app/Models/BuilderTemplate.php` y `resources/js/Pages/Plantillas/Index.vue`) que permite diseñar visualmente con el Page Builder las vistas públicas de categorías y artículos respetando estrictamente el SEO, las directivas de rendimiento y la arquitectura del sistema:

### 9.1. Tipos de Plantilla
1. **Plantilla de Vista de Categoría (`category_archive`)**:
   - Maqueta visualmente la página de listado `/{categorySlug}`.
   - Resuelve etiquetas dinámicas de categoría (`{category.name}`, `{category.description}`, `{category.featured_image}`).
   - Admite cuadrículas dinámicas de artículos de la categoría actual activa (`dynamic_binding: 'current_category'`).
2. **Plantilla de Detalle de Artículo (`single_article`)**:
   - Maqueta visualmente la página individual `/{categorySlug}/{articleSlug}`.
   - Resuelve dinámicamente campos del artículo (`{article.title}`, `{article.excerpt}`, `{article.body}`, `{article.author.name}`, `{article.published_at}`, `{article.reading_time}`, `{article.featured_image}`, `{article.extra_fields.*}`).
   - Soporta galerías dinámicas con `source: 'current_article'` que expanden automáticamente los campos de imagen extra del artículo en ítems de galería (ver §6.4).

### 9.2. Jerarquía de Resolución y 100% Opcionalidad
El sistema es **100% opcional y no intrusivo**:
- **Categorías**: Si la categoría tiene asignada una plantilla activa en `category_builder_template_id`, se resuelve su diseño con `app/Services/PageBuilderResolverService.php` y se renderiza con `<PageBuilderRenderer />`. Si no tiene plantilla asignada (`null`), se renderiza de forma idéntica la vista clásica estándar (`resources/js/Pages/Public/Category.vue`).
- **Artículos**: La jerarquía comprueba en primer lugar la sobrescritura individual del artículo (`article.article_builder_template_id`). Si no está definida, comprueba la plantilla asignada a nivel de categoría (`category.article_builder_template_id`). Si ninguna está configurada, se muestra la vista clásica estándar de lectura (`resources/js/Pages/Public/Article.vue`).

### 9.3. Preservación Estricta de SEO y SSR
- Las etiquetas de metadatos (`SEOMeta`), tarjetas sociales (`OpenGraph`), esquemas enriquecidos (`JsonLd` `CollectionPage` y `Article`), breadcrumbs y canonical URLs se calculan y generan en el servidor antes de entregar el HTML inicial de SSR.
- Los inspectores del Builder disponen del componente `resources/js/Components/Builder/DynamicTagSelector.vue` para insertar variables dinámicas en encabezados, textos, botones e imágenes en un solo clic.

### 9.4. Resolución Inteligente de Muestra en el Visualizador (`TemplateBuilderController`)
Al maquetar una plantilla en el visualizador, el backend resuelve la entidad de muestra en memoria siguiendo una jerarquía estricta:
1. **Para plantillas de artículo (`single_article`)**:
   - Artículo explícito seleccionado en la URL / previsualización (`preview_article_id`).
   - Artículos con asignación directa a la plantilla.
   - **Artículos de la(s) categoría(s) que tienen asignada esta plantilla** (ej. si la plantilla se asigna a *Servicios*, carga automáticamente un artículo de *Servicios* sin requerir configuración manual).
   - Artículos de la categoría de previsualización configurada (`preview_category_id`).
   - Fallback ordenado al primer artículo publicado general.
2. **Para plantillas de archivo de categoría (`category_archive`)**:
   - Categoría explícita seleccionada (`preview_category_id`).
   - Categoría(s) que tienen asignada esta plantilla como archivo.
   - Fallback a la primera categoría activa.

### 9.5. Selector de Muestra en Tiempo Real y Duplicación Limpia
- **Selector en Cabecera (`BuilderHeader.vue`)**: Al editar una plantilla, la barra superior muestra un desplegable interactivo («Muestra: [Artículo/Categoría]») que permite cambiar de artículo o categoría en un clic y comprobar al instante cómo luce el diseño con diferentes contenidos.
- **Duplicación de Plantillas (`BuilderTemplateController@duplicate`)**: Al clonar plantillas, se resetean automáticamente los identificadores de muestra (`preview_category_id = null`, `preview_article_id = null`) para que la copia quede limpia y adopte automáticamente el contexto de la nueva categoría a la que se asigne.

### 9.6. Enlace Contextual a la Web Pública («Ver Web Pública»)
Para evitar enlaces rotos o intentos de acceder a rutas inexistentes (como el slug interno `/plantilla-{id}` que devolvía error 404), la cabecera del maquetador (`BuilderHeader.vue`) calcula de forma reactiva y contextual la URL real de destino:
- **Páginas Estáticas (`Page`)**: Enlaza directamente a la portada (`/`) si `is_homepage` está activo, o a la ruta canónica de la página (`/{pagina.slug}`).
- **Plantillas de Categoría (`category_archive`)**: Resuelve reactivamente la URL pública de la categoría de muestra seleccionada en la cabecera (`/{categoria.slug}`). El botón se titula dinámicamente *«Ver Categoría Pública»* y actualiza su enlace en tiempo real al conmutar entre categorías de muestra.
- **Plantillas de Artículo (`single_article`)**: Resuelve la URL canónica del artículo de muestra activo (`/{categoria.slug}/{articulo.slug}` o `/articulos/{articulo.slug}`). El botón se titula dinámicamente *«Ver Artículo Público»*.
- **Control de estados vacíos**: En caso de no existir ninguna categoría o artículo de muestra en el sistema, el botón pasa a estado visualmente deshabilitado (`cursor-not-allowed`) con un tooltip informativo, previniendo navegaciones fallidas.

---

## 10. Sistema de Distribución Visual de Cuadrículas y Diseño Granular de Columnas

El Page Builder incorpora un sistema avanzado de cuadrículas responsivas que combina una galería visual de 24 layouts predefinidos con un control granular de dimensionamiento por tamaño de pantalla:

### 10.1. Catálogo Visual de 24 Diseños de Cuadrícula (`gridPresets.js` y `GridPresetModal.vue`)
Desde el inspector de Fila (`RowInspector.vue`), al pulsar sobre la tarjeta de diseño o el botón *"Cambiar"*, se abre un modal con **24 tarjetas vectoriales tipo *wireframe***:
- **1 Columna**: *Whole (1/1)*.
- **2 Columnas**: *Halves (1/2 + 1/2)*, *Thirds 2-1 (2/3 + 1/3)*, *Thirds 1-2 (1/3 + 2/3)*, *Quarters 3-1 (3/4 + 1/4)*, *Quarters 1-3 (1/4 + 3/4)*, *Fifths 2-3 (2/5 + 3/5)*, *Fifths 3-2 (3/5 + 2/5)*, *Fifths 4-1 (4/5 + 1/5)*, *Fifths 1-4 (1/5 + 4/5)*, *Sixths 5-1 (5/6 + 1/6)*, *Sixths 1-5 (1/6 + 5/6)*.
- **3 Columnas**: *Thirds (1/3 x 3)*, *Quarters 2-1-1 (2/4 + 1/4 + 1/4)*, *Quarters 1-1-2 (1/4 + 1/4 + 2/4)*, *Quarters 1-2-1 (1/4 + 2/4 + 1/4)*, *Fifths 3-1-1 (3/5 + 1/5 + 1/5)*, *Fifths 1-1-3 (1/5 + 1/5 + 3/5)*, *Fifths 1-3-1 (1/5 + 3/5 + 1/5)*.
- **4 Columnas**: *Quarters (1/4 x 4)*, *Fifths 2-1-1-1 (2/5 + 1/5 x 3)*, *Fifths 1-1-1-2 (1/5 x 3 + 2/5)*.
- **5 Columnas**: *Fifths (1/5 x 5)*.
- **6 Columnas**: *Sixths (1/6 x 6)*.

Al seleccionar un diseño, el composable `cambiarLayoutFila()` ajusta el número de columnas y redistribuye los elementos modulares contenidos sin pérdida de datos.

### 10.2. Control Granular de Ancho y Orden por Breakpoint (`ColumnLayoutInspector.vue`)
Permite personalizar el comportamiento responsivo exacto de cada columna individual para 5 tamaños de pantalla:
1. **Móvil en Vertical** (`xs`: `< 640px`)
2. **Móvil en Horizontal** (`sm`: `≥ 640px`)
3. **Tableta en Horizontal** (`md`: `≥ 768px`)
4. **Escritorio** (`lg`: `≥ 1024px`)
5. **Pantalla grande** (`xl`: `≥ 1280px`)

#### Opciones de Dimensionamiento Soportadas:
- **Fracciones**: `1/1` (100%), `1/2` (50%), `1/3` (33.3%), `2/3` (66.6%), `1/4` (25%), `3/4` (75%), `1/5` (20%), `2/5` (40%), `3/5` (60%), `4/5` (80%), `1/6` (16.6%), `5/6` (83.3%).
- **Anchos Dinámicos y Fijos**:
  - **`Expandir` (`expand`)**: Aplica `[bp]:w-auto [bp]:flex-1 [bp]:min-w-0`, absorbiendo todo el espacio sobrante disponible en la fila.
  - **`Auto` (`auto`)**: Aplica `[bp]:w-auto [bp]:flex-none`, ajustando el ancho exactamente al contenido interno.
  - **Anchos Fijos en Píxeles**: `Pequeño` (192px / `w-48`), `Medio` (256px / `w-64`), `Grande` (320px / `w-80`), `Extragrande` (384px / `w-96`) y `2X-Grande` (448px / `w-[28rem]`).
- **Reordenación Visual Flexbox**: Selector independiente de `order` (`none`, `first`, `last`, `1` a `6`) para:
  - **Móvil** (`< 768px`)
  - **Tableta** (`≥ 768px`)
  - **Escritorio** (`≥ 1024px`)
  - Permite alterar el orden de lectura visual según el dispositivo sin modificar la jerarquía semántica del DOM. Todas las clases (`order-none`, `order-first`, `order-last`, `order-1`..`6`, y variantes `sm:`, `md:`, `lg:`, `xl:`) se compilan explícitamente en `app.css` mediante `RESPONSIVE_ORDER_CLASS_MAP` en `gridPresets.js` para blindarlas frente a purgas estáticas de Tailwind JIT.

### 10.3. Renderizado Fluido y Desacoplamiento Estructural (`BuilderRow.vue` y `BuilderColumn.vue`)
- **Sistema de Compensación de Márgenes (*Gutters*)**: La fila utiliza un contenedor `flex flex-wrap` con márgenes negativos (`-mx-*`, `-my-*`) proporcionales al espaciado horizontal (`gap_x`) y vertical (`gap_y`), mientras cada columna externa aplica el relleno de compensación (`px-*`, `py-*`).
- **Aislamiento de Responsabilidades**: El envoltorio exterior de la columna gestiona exclusivamente el dimensionamiento, *gutters* y orden flexbox; el contenedor interno gestiona los fondos, bordes redondeados, padding de contenido, superposiciones y animaciones.
- **Cero Sobrecarga en Tiempo de Ejecución**: La distribución se resuelve enteramente mediante clases nativas y compiladas de Tailwind CSS, garantizando 0 KB de JavaScript adicional y 0 CLS (Cumulative Layout Shift) en el renderizado SSR.

### 10.4. Control Coherente de Ancho de Contenedor en Filas (`container`)
El maquetador permite que cada fila defina su propio ancho de contenedor de forma coherente con el sistema de secciones:
- **Jerarquía y Límite Superior**: La sección actúa como el contenedor delimitador máximo. Si una sección está configurada a pantalla completa (`full_bleed` o `full_margin`, como en banners hero o secciones con fondos panorámicos), cada fila interna puede modular su propio ancho de forma independiente.
- **Opciones Unificadas de Ancho (`opcionesContenedorFila`)**:
  - **`Heredar de la Sección` (`inherit`)**: Opción por defecto. La fila ocupa el 100% del contenedor disponible definido por la sección padre (`w-full`).
  - **`Boxed (Centrado 1280px)` (`boxed`)**: Limita el ancho a 1280px (`max-w-7xl mx-auto`).
  - **`Pequeño (Centrado 768px)` (`small`)**: Limita el ancho a 768px (`max-w-3xl mx-auto`).
  - **`Expandido Grande (1536px)` (`expand`)**: Limita el ancho a 1536px (`max-w-screen-2xl mx-auto`).
  - **`Full Width con Margen` (`full_margin`)**: Ocupa el 100% de la anchura manteniendo los márgenes laterales estándar.
  - **`Full Bleed Total` (`full_bleed`)**: Ocupa el 100% de extremo a extremo sin márgenes laterales (`px-0`).
- **Gestión Inteligente de Padding Horizontal (Cero Sangría Doble en Móvil)**:
  - Mediante `provide('sectionHasHPadding', ...)` en `BuilderSection.vue` e `inject('sectionHasHPadding', ...)` en `BuilderRow.vue`, la fila detecta si la sección padre ya aporta relleno lateral (`px-4 sm:px-6 lg:px-8`).
  - **En Secciones Full Bleed (sin relleno lateral)**: La fila con ancho `boxed`, `small`, `expand` o `full_margin` añade automáticamente el padding protector responsivo (`px-4 sm:px-6 lg:px-8`) para evitar que las columnas peguen a los bordes de la pantalla en móviles y tablets.
  - **En Secciones con Padding (ej. Boxed)**: Si una fila se reduce a `small`, no duplica el padding lateral (`px-0`), previniendo una sangría doble indeseada en pantallas móviles.

### 10.5. Dimensionamiento del Visualizador Responsivo en el Constructor (`usePageBuilder.js`)
Para garantizar que las reglas CSS `media queries` nativas de Tailwind se activen con total fidelidad dentro del iframe del constructor:
- **Escritorio**: `w-full` (aprovecha el ancho total del área de trabajo).
- **Tableta**: `w-full max-w-[770px] mx-auto` con borde exterior de 2px (`border border-zinc-300`). Al descontar los 2px del borde bajo `box-sizing: border-box`, el ancho interior útil del iframe resulta en **768px exactos**, activando con precisión matemática `@media (min-width: 768px)` (`md:`).
- **Móvil**: `w-full max-w-[375px] mx-auto` (ancho de referencia estándar para smartphones).

---

## 11. Elemento Modular "Subdiseño / Sublayout" (Cuadrículas Internas Anidadas)

El elemento **Subdiseño / Sublayout** (`sublayout`) permite crear una cuadrícula interna de filas y columnas dentro de cualquier columna existente del maquetador visual. Resuelve el caso de uso donde se requiere posicionar varios elementos modulares lado a lado en horizontal (por ejemplo, dos o más iconos de contacto, botones secundarios o campos de información) debajo de elementos que ocupan el ancho completo de la columna padre.

### 11.1. Arquitectura y Componentes
- **Renderizado Frontend y SSR (`ElementSublayout.vue`)**: Renderiza recursivamente las subfilas (`content.rows`) y subcolumnas internas con soporte completo para hidratación en servidor e inyección de datos dinámicos.
- **Inspector Visual Dedicado (`ElementSublayoutInspector.vue`)**: Permite seleccionar layouts de columnas predefinidos, cambiar a modo dinámico auto, graduar separaciones (`gap`), alineaciones y gestionar subcolumnas.
- **Árbol de Estructura (`BuilderTree.vue`)**: Identifica el subdiseño con un contenedor diferenciado en tono violeta/índigo, botón de colapsar/desplegar con chevron, visualización en miniatura de subcolumnas y `vuedraggable` unificado (`:group="{ name: 'elements', pull: true, put: true }"`) para arrastrar elementos entre columnas principales y subcolumnas libremente.

### 11.2. Modos de Distribución y Columnas Auto Dinámicas
1. **Layouts Fijos Proporcionales**:
   - `1 Columna` (100%), `2 Columnas` (1/2 + 1/2), `3 Columnas` (1/3 x 3), `4 Columnas` (1/4 x 4).
   - Variantes asimétricas: `2 Cols (1/3 + 2/3)`, `2 Cols (2/3 + 1/3)`, `2 Cols (1/4 + 3/4)`, `2 Cols (3/4 + 1/4)`.
2. **Modo Auto (Contenido) con N Subcolumnas Flexibles**:
   - Cada subcolumna aplica la clase responsiva `w-auto flex-initial`, ciñéndose exactamente al tamaño de su contenido interior sin expandirse artificialmente.
   - El inspector incluye un panel de control interactivo para seleccionar directamente `2 cols`, `3 cols`, `4 cols`, `5 cols`, `6 cols` o añadir más con el botón `+ Añadir Columna`.
   - Permite maquetar cualquier número de elementos en horizontal alineados según la justificación seleccionada (`justify-start`, `justify-center`, `justify-end`, `justify-between`).

### 11.3. Preservación Inteligente de Elementos
- Si el usuario reduce el número de columnas (por ejemplo, de 4 columnas a 2), los elementos contenidos en las columnas eliminadas **no se destruyen**: se migran automáticamente a la última subcolumna activa.
- En la lista de subcolumnas del inspector, cada tarjeta cuenta con un botón de eliminación individual (`🗑️`) que transfiere sus elementos huérfanos a la columna anterior antes de removerla.

### 11.4. Resolución Dinámica Recursiva sin N+1 (`PageBuilderResolverService.php`)
- Los métodos `collectDynamicSourcesFromSubrows()` e `injectResolvedDataIntoSubrows()` analizan de forma recursiva cualquier elemento anidado dentro de las subcolumnas (como grids de artículos o campos dinámicos).
- Recolectan los IDs requeridos y ejecutan una única consulta por lote para toda la página, previniendo cuellos de botella y problemas de rendimiento N+1.

---

## 12. Elemento Vídeo Dinámico y Soporte de Variables del CMS (`ElementVideo.vue`)

El elemento Vídeo HTML5 permite tanto la reproducción de archivos estáticos como la vinculación dinámica con los datos del artículo activo:

### 12.1. Selector de Etiquetas Dinámicas (`DynamicTagSelector.vue`)
- En el inspector de vídeo (`ElementVideoInspector.vue`), tanto la URL del vídeo como la imagen de portada (`poster`) cuentan con un selector desplegable de variables dinámicas:
  - Soporta `{article.extra_fields.clave_video}`, `{extra.clave_video}` y `{article.clave_video}`.
  - El selector detecta automáticamente los campos de tipo `video` e `image` definidos en el esquema de la categoría del artículo.

### 12.2. Resolución y Comportamiento en Frontend y Constructor
- **En el Constructor / Iframe**: Si un vídeo dinámico no tiene aún valor en el artículo de muestra, `ElementVideo.vue` muestra una tarjeta estilizada indicando la variable vinculada para permitir su inspección sin romper la vista previa.
- **En la Web Pública**: Si el artículo visitado no dispone de vídeo en su campo dinámico, el elemento no genera markup vacío ni cajas negras, permaneciendo oculto de forma transparente.

---

## 13. Colapso Inteligente de Columnas Vacías y Expansión al 100%

En diseños de múltiples columnas (por ejemplo, una fila con 3/5 de texto y 2/5 de vídeo), si un artículo no cuenta con vídeo dinámico o los elementos de una columna están vacíos, el sistema previene la aparición de huecos blancos indeseados.

### 13.1. Arquitectura del Composable `useColumnCollapse.js`
- **`esElementoVacio(el)`**: Comprueba de forma atómica si un elemento carece de contenido (texto vacío, imagen sin URL, o tokens dinámicos no resueltos como `{article.extra_fields...}`).
- **`esColumnaVacia(col)`**: Verifica si todos los elementos contenidos en la columna están vacíos.
- **`filtrarColumnasVisibles(columnas, rowSettings)`**: Filtra la lista de columnas evaluando si la fila tiene activo el colapso automático (`row.collapse_empty_columns`) o si la columna individual tiene marcado el colapso (`col.collapse_if_empty`).
- **`calcularLayoutEfectivo(layoutOriginal, visibleCount, totalCount)`**: Recalcula dinámicamente la disposición de la fila: si tras el filtrado solo queda 1 columna visible, transforma el layout efectivo a `'1'`, ordenando que la columna activa se expanda al 100% (`w-full flex-1`).

### 13.2. Integración en `BuilderRow.vue`, `ElementSublayout.vue` y `gridPresets.js`
- **Renderizado Público y SSR**: `BuilderRow.vue` y `ElementSublayout.vue` calculan las columnas visibles en tiempo de ejecución. Al pasar el layout efectivo a `'1'`, `gridPresets.js` inyecta las clases de ancho completo en todos los breakpoints (`base`, `md`, `lg`), asegurando que la columna con contenido ocupe todo el ancho sin alterar la definición original en el panel de administración.
- **Inspectores de Control**:
  - **Fila y Sublayout**: Switch global *"Auto-colapsar columnas vacías"* (`collapse_empty_columns`) en `RowInspector.vue` y `ElementSublayoutInspector.vue`.
---

## 14. Espacio Máximo de Trabajo y Menú Lateral Colapsable (`AuthenticatedLayout.vue`)

Para optimizar la ergonomía visual y aprovechar al máximo las pantallas de escritorio al diseñar páginas, la vista del constructor cuenta con un sistema avanzado de **espacio máximo de trabajo (Zen Workspace)** que permite ocultar o mostrar el menú lateral de administración (`w-64`, 256 px).

### 14.1. Arquitectura y Persistencia Desacoplada
- **Detección Reactiva de Contexto (`esRutaBuilder`)**: `AuthenticatedLayout.vue` evalúa si la ruta activa corresponde al constructor (`paginas.builder` o `plantillas.builder`).
- **Persistencia Aislada en `localStorage`**:
  - En la vista del maquetador: almacena la clave `builder_sidebar_colapsado`.
  - En el resto del panel administrativo: utiliza la clave `admin_sidebar_colapsado`.
  - **Comportamiento Inteligente**: Si el usuario entra al constructor por primera vez, el sidebar se colapsa automáticamente (`true`), garantizando la mayor superficie de lienzo posible. Al volver a listados (Artículos, Páginas, etc.), el sidebar se restaura a su estado habitual sin interferir en la navegación general.
- **Transición Suave Anti-Reflujo**:
  - El elemento `<aside>` aplica `transition-[width,transform,opacity,border-color] duration-300 ease-in-out` (`w-64` a `w-0`).
  - Su contenido interno está encapsulado dentro de un contenedor rígido `w-64 min-w-[16rem]` con `overflow-hidden`. De este modo, los textos, enlaces y avatares no sufren deformaciones ni saltos de línea desagradables durante la animación.

### 14.2. Puntos de Control y Usabilidad
1. **Botón en Barra Superior (Topbar)**: Icono SVG moderno de panel de control con chevron interactivo que cambia de orientación y color según el estado (`Ocultar / Mostrar menú lateral`).
2. **Botón en Cabecera del Sidebar**: Botón directo ubicado junto al logotipo principal (`pi-angle-left`) para retraer el menú con un clic.
3. **Pestaña/Tirador Flotante (Glassmorphism)**: Cuando el sidebar se encuentra colapsado en pantallas de escritorio, se renderiza un tirador sutil en el borde izquierdo de la pantalla (`fixed left-0 top-1/2`). Al pasar el cursor, el tirador se expande suavemente de `w-4` a `w-6` con borde esmeralda translúcido, permitiendo reabrir el menú instantáneamente.
4. **Atajo de Teclado Universal (`Ctrl + B` / `Cmd + B`)**: Permite alternar la visibilidad del menú lateral en cualquier momento. Detecta de forma inteligente si el usuario está editando un campo de texto (`<input>`, `<textarea>`, `isContentEditable`) para no interferir con la escritura.
5. **Ajuste de Altura Perfecta (`h-full`)**: El contenedor raíz de `Builder.vue` utiliza `h-full` y el contenedor `<main>` del layout aplica `overflow-hidden` condicional en rutas de constructor, eliminando barras de desplazamiento dobles y encajando al 100% en el alto del viewport.

---

## 15. Personalización Avanzada de Código y Reglas por Modo Claro / Oscuro (`custom_css`)

Todas las Secciones, Filas, Columnas y Elementos incorporan el bloque **Personalización Avanzada y Código** (`AdvancedCustomizationBlock.vue`), permitiendo definir `element_id` (`id` HTML único), `custom_classes`, `custom_attributes` y `custom_css` (inyectado mediante etiqueta `<style>` autorizada por `style-src` en CSP).

### 15.1. Reglas Diferenciadas según Tema Global (`html.dark` vs `html:not(.dark)`)
- El tema global del sitio público alterna la clase `.dark` sobre el nodo raíz `<html>`.
- **Importante (`html.dark` frente a `.dark`):** Dado que `BuilderSection.vue` añade localmente la clase `.dark` a secciones con presets oscuros o texto claro forzado (`text_color: 'light'`), para aplicar estilos condicionales exclusivamente cuando **todo el sitio** está en modo oscuro o claro se debe acotar siempre el selector a `html.dark` y `html:not(.dark)` junto al `#id` del bloque:

```css
/* Modo Claro */
html:not(.dark) #logos-clientes img,
html:not(.dark) #logos-clientes svg {
    filter: brightness(0) !important;
}

/* Modo Oscuro */
html.dark #logos-clientes img,
html.dark #logos-clientes svg {
    filter: brightness(0) invert(1) !important;
}
```

- Cuando se sobreescriben colores o fondos definidos mediante selectores visuales del inspector (que generan atributos `style="..."` inline), debe añadirse `!important` o dejar vacío el color inline del inspector para delegar ambos modos limpiamente al CSS personalizado.
