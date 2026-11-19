# Módulo de Mapas Interactivos y Geolocalización

El módulo de mapas (`ElementMap.vue`, `ElementMapInspector.vue` y `LocationPickerModal.vue`) permite integrar mapas interactivos de alto rendimiento en cualquier página o plantilla del CMS, con soporte para múltiples marcadores, geocodificación automática, colorimetría avanzada y compatibilidad tanto con **Google Maps JavaScript API** como con **OpenStreetMap (Leaflet)**.

---

## 1. Soporte Dual de Motores

### 1.1. Google Maps JavaScript API
- **Requisitos**: Clave de API configurada de forma centralizada en el CMS (*Panel > Configuración Global > Integraciones*).
- **Tipos de Capa Base**:
  - `roadmap`: Hoja de ruta (calles y avenidas estándar).
  - `satellite`: Vista satelital de alta resolución.
  - `hybrid`: Vista satelital con etiquetas de calles y fronteras superpuestas.
  - `terrain`: Mapa de relieve y topografía.
- **Stylers Nativos de Google Maps**: Colorimetría calculada vectorialmente en el motor de Google.
- **Controles Nativos**: Selector de capa base, zoom (+/-), vista de pantalla completa y Street View.

### 1.2. OpenStreetMap (Leaflet)
- **Ventajas**: 100% gratuito, cero consumo de cuota, sin necesidad de API Key, y estricto cumplimiento del RGPD (sin rastreadores de terceros).
- **Tipos de Capa Base**:
  - `osm_standard`: Tiles oficiales de OpenStreetMap.
  - `carto_light`: CartoDB Positron (diseño minimalista claro de alto contraste).
  - `carto_dark`: CartoDB Dark Matter (modo noche elegante para temas oscuros).
- **Colorimetría CSS**: Filtros de matriz CSS calculados dinámicamente (`saturate`, `brightness`, `grayscale`, `invert`).

---

## 2. Selector Visual Interactivo de Punto Exacto (`LocationPickerModal.vue`)

Permite a los administradores ajustar coordenadas sin necesidad de copiar y pegar números decimales desde fuentes externas:

1. **Pin Arrastrable (Draggable Marker)**: Marcador interactivo que se puede mover con el cursor directamente sobre el portal, calle o edificio deseado para lograr precisión al metro.
2. **Clic Directo**: Al hacer clic en cualquier punto del mapa, el pin salta inmediatamente a esa posición.
3. **Buscador de Direcciones (Nominatim)**: Campo de búsqueda predictivo con geocodificación gratuita. Al escribir una dirección (ej: *Plaza Pedro Santos Gómez 19, Sevilla*), el mapa vuela suavemente al destino con animación de zoom.
4. **Geolocalización GPS del Dispositivo**: Botón "Mi Ubicación GPS" para centrar el punto en la posición física actual del navegador.
5. **Lectura de Coordenadas en Vivo**: Visualización continua de Latitud y Longitud con 6 decimales.
6. **Sincronización Bidireccional Automática**: Si el mapa contiene 1 marcador, la selección del punto actualiza simultáneamente el marcador y el centro del mapa, garantizando que tanto en el lienzo del maquetador como en el frontend público la vista coincida con la ubicación elegida.

---

## 3. Colorimetría y Personalización Visual Avanzada

El inspector ofrece controles milimétricos de estilo en la pestaña **Color & Estilo**:

- **Presets Rápidos**:
  - *Estándar*: Colores originales del mapa.
  - *Escala de Grises*: Mapa monocromático en blanco y negro (saturación -100%).
  - *Modo Noche / Oscuro*: Paleta nocturna con contraste optimizado.
  - *Personalizado*: Sliders de ajuste continuo.
- **Saturación (-100 a +100)**: De desaturado total a colores vívidos e intensos.
- **Tono Corporativo (Hue Tint)**: ColorPicker hexadecimal para teñir el mapa con el color de marca del sitio.
- **Luminosidad / Brillo (-100 a +100)**: Regulación de brillo para adaptar el mapa a fondos claros u oscuros.
- **Invertir Luminosidad**: Invierte la matriz lumínica para crear temas oscuros de alto impacto.

---

## 4. Origen de Datos: Modo Manual vs Dinámico

### 4.1. Modo Manual
- Lista de marcadores creada directamente en el inspector con reordenación, clonación y eliminación.
- Cada marcador soporta:
  - Título y descripción.
  - Coordenadas de latitud y longitud (`step="any"`).
  - Geocodificación asistida desde el campo de dirección de texto.
  - Icono personalizado (subida de imagen o selección de medios).
  - Enlace CTA en popup con texto y URL personalizables.
  - Apertura automática de popup al cargar (`open_popup: true`).

### 4.2. Modo Dinámico (`PageBuilderResolverService.php`)
- **Artículo Actual (`current_article`)**: Para plantillas de detalle de artículos (inmuebles, eventos, sedes, sucursales). Extrae automáticamente las coordenadas desde los campos extra (`extra_fields_data.coordenadas` o claves similares).
- **Colección de Artículos (`articles`)**: Genera múltiples marcadores mapeando los artículos de una categoría específica, con límite configurable y enlace directo a cada artículo.
- **Eager Loading y Cero N+1**: Resuelto en una sola pasada en servidor durante el ciclo SSR.

---

## 5. Rendimiento y Seguridad

1. **Lazy Loading con IntersectionObserver**:
   - Los scripts de los motores (Google Maps o Leaflet) no se descargan hasta que el mapa entra en el viewport del usuario (`rootMargin: 250px`).
   - Muestra un esqueleto de carga elegante mientras se inicializa el motor.
2. **Resiliencia SSR**:
   - `ElementMap.vue` comprueba `typeof window !== 'undefined'` y `isMounted` para prevenir fallos durante el renderizado en Node.js.
3. **Cifrado de Credenciales**:
   - La Google Maps API Key se almacena cifrada en la base de datos con AES-256 (`'encrypted'` cast en `SiteSetting.php`).
   - Se valida en `UpdateSiteSettingRequest.php`; `HandleInertiaRequests.php` únicamente expone en rutas públicas el indicador booleano `has_google_maps_key`, mientras que `ElementMap.vue` obtiene la clave bajo demanda a través del endpoint protegido `GET /api/cms/maps-key` (`GoogleMapsKeyController`).
4. **Optimización de InfoWindow de Google Maps**:
   - Eliminación del espacio superior fantasma (`.gm-style-iw-chr`) que Google Maps reserva por defecto para la cabecera, reubicando el botón de cierre en posición flotante y aplicando esquinas redondeadas y tipografía moderna integrada con el sistema de diseño.

---

## 6. Semántica SEO y Aspecto Visual en Títulos de Popups

Para evitar errores de auditoría SEO (rotura de la jerarquía de encabezados por etiquetas `<h4>` fijas en ventanas modales o popups) y otorgar control total de diseño, el módulo desacopla la **etiqueta HTML semántica** del **aspecto visual tipográfico**:

### 6.1. Tipo de Etiqueta (Semántica HTML / SEO)
* Permite elegir: `div`, `h1`, `h2`, `h3`, `h4`, `h5`, `h6`, `p`, `span`.
* **Valor predeterminado**: `div`. De este modo, los títulos de las ubicaciones en el mapa no interfieren con el esquema de títulos de la página pública ni generan avisos en rastreadores de motores de búsqueda.

### 6.2. Aspecto Visual (Escala Tipográfica)
* Independientemente de la etiqueta seleccionada, se puede asignar el tamaño y peso visual deseado:
  * `h1`: 24px/30px bold (`text-2xl font-bold tracking-tight text-zinc-900`)
  * `h2`: 20px/24px bold (`text-xl font-bold text-zinc-900`)
  * `h3`: 18px/20px bold (`text-lg font-bold text-zinc-900`)
  * `h4`: 16px bold (`text-base font-bold text-zinc-900`, aspecto estándar recomendado para popups)
  * `h5`: 14px semibold (`text-sm font-semibold text-zinc-900`)
  * `h6`: 12px semibold mayúsculas (`text-xs font-semibold uppercase tracking-wider text-zinc-900`)
  * `base`: 16px normal (`text-base font-medium text-zinc-900`)
  * `sm`: 14px normal (`text-sm font-medium text-zinc-800`)
  * `xs`: 12px normal (`text-xs font-medium text-zinc-800`)

### 6.3. Configuración Dual (Global y por Marcador)
* **Nivel Global del Mapa**: Se define en la pestaña *Marcadores* del inspector (`marker_title_tag` y `marker_title_aspect`) para regir todos los popups del mapa.
* **Nivel Individual por Marcador**: Cada punto de interés en la lista permite seleccionar `Heredar del mapa` (`'inherit'`) o sobrescribir específicamente su etiqueta (`title_tag`) o su aspecto (`title_aspect`).
* **Soporte Unificado**: Aplicado con idénticas clases tanto en los `InfoWindow` de Google Maps como en los popups de OpenStreetMap / Leaflet.
