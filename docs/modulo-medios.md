# Módulo de Biblioteca de Medios y Gestor de Archivos

El módulo de medios administra las imágenes, documentos y archivos adjuntos del CMS con optimización automática WebP, almacenamiento relacional en base de datos, servicio de limpieza automatizado y selector modal reutilizable.

## 1. Arquitectura y Componentes

- **Modelo `MediaFile` (`app/Models/MediaFile.php`)**:
  - Representa cada archivo subido al sistema.
  - Guarda metadatos como `filename`, `original_filename`, `mime_type`, `extension`, `size_bytes`, `dimensions` (ancho/alto en píxeles), `alt_text`, `title`, `description`, `folder` y la ruta pública accesible `url`.
- **Migración (`database/migrations/2026_08_11_075612_create_media_files_table.php`)**:
  - Tabla `media_files` indexada por `folder`, `mime_type`, `extension` y fecha de creación.
- **Controlador (`MediaController.php`)**:
  - `/panel/media`: Listado paginado de archivos con búsqueda, filtros por tipo (imagen/documento/video) y ordenación.
  - `POST /panel/media/upload`: Subida unitaria o múltiple con optimización automática a `.webp` de alta eficiencia.
  - `PUT /panel/media/{id}`: Edición de texto alternativo (`alt_text`), título y descripción SEO del archivo.
  - `DELETE /panel/media/{id}` y `POST /panel/media/bulk-delete`: Eliminación física del disco y de la base de datos.
- **Selector Modal Reutilizable (`MediaPickerModal.vue`)**:
  - Modal interactivo con subida Drag & Drop en caliente, buscador en tiempo real, pestañas de filtrado, visor de detalles e inyección de la URL seleccionada en cualquier formulario o campo del Page Builder.
- **Servicio de Limpieza (`MediaCleanupService.php` y Comando `media:clean-unused`)**:
  - Comando `php artisan media:clean-unused` para buscar y desvincular imágenes huérfanas en el disco no asociadas a ningún registro activo.

---

## 2. Flujo de Trabajo

1. **Subida de Archivos**:
   - Las imágenes subidas son procesadas por `MediaImageService` e `ImageOptimizerService`.
   - Se generan automáticamente versiones WebP optimizadas conservando las dimensiones originales o ajustándolas para la web.
2. **Selección en Formularios**:
   - Los componentes de administración (`ImageUploader.vue`, `ElementImageInspector.vue`, etc.) abren `MediaPickerModal.vue`, permitiendo elegir una imagen existente de la biblioteca o subir una nueva en el acto.
3. **Subida de Vídeos y Detección de FFmpeg**:
   - Subida optimizada de archivos MP4, WebM y MOV gestionada por `MediaController` y `MediaImageService`.
   - Se realiza detección automática del binario FFmpeg mediante `MediaImageService::hasFFmpeg()`.

---

## 3. Optimización de Vídeos y Detección de FFmpeg

### Detección Dinámica de FFmpeg (`MediaImageService::hasFFmpeg()`)
- El sistema comprueba dinámicamente si el ejecutable `ffmpeg` está disponible en el servidor host mediante `exec('which ffmpeg')`.
- **Estado en el Servidor Actual**: **FFmpeg está INSTALADO Y ACTIVO** (`/usr/bin/ffmpeg`). La comprobación `hasFFmpeg()` devuelve `true` en el entorno PHP del servidor web.
- **Optimización Automática Activa**: Todas las subidas de vídeo MP4/MOV son procesadas automáticamente con `-movflags +faststart` para garantizar streaming instantáneo.

### Optimización `+faststart` (cuando FFmpeg está activo)
- Cuando el binario de FFmpeg está disponible en el servidor, `optimizarVideoFaststart()` ejecuta la instrucción de reordenación de átomos MP4:
  ```bash
  ffmpeg -i input.mp4 -c copy -movflags +faststart output.mp4
  ```
- Esta optimización relocaliza la cabecera `moov` al principio del archivo, lo que permite que los navegadores web comiencen la reproducción instantánea vía peticiones HTTP Range sin tener que descargar el vídeo completo.

---

## 4. Motor de Variantes On-Demand y Precalentamiento de Caché (`MediaImageService`)

### 4.1. Generación y Caché en Disco (`getOrGenerateVariant`)
- Las imágenes maestras originales se almacenan intactas en `storage/app/public/media/`.
- El servicio genera variantes optimizadas bajo demanda (`storage/app/public/cache/media/{hash}_{nombre}.{formato}`):
  - Modos de ajuste soportados: `'scale'` (proporcional), `'cover'` (recorte exacto centrado) y `'contain'`.
  - Formatos: WebP (por defecto), AVIF, PNG y JPG con muestreo de alta calidad (`imagecopyresampled`) y canal alfa transparente preservado.
  - Entrega directa a 0ms en visitas posteriores (Cache HIT).

### 4.2. Precalentamiento Inmediato tras la Subida (`prewarmVariants`)
Al subir una imagen a través de `MediaController@upload`, se pre-generan de forma asíncrona las variantes exactas requeridas por la web pública:
- Grids de Artículos: `w=320`, `w=480`, `w=640` (16:9 cover).
- Detalle de Artículos (`Article.vue`): `w=1600` (scale WebP q:90).
- Fondos de Sección y Columna (`BuilderSection.vue` y `BuilderColumn.vue`):
  - Móvil: `w=768, q=82, f=webp` (scale).
  - Tablet: `w=1280, q=85, f=webp` (scale).
  - Escritorio: `w=1920, q=85, f=webp` (scale).
Esto asegura que el primer visitante de cualquier dispositivo obtenga una respuesta instantánea desde la caché en disco sin tiempos de espera de procesamiento.

---

## 5. Gestión de Colecciones y Carpetas de Medios

El sistema permite clasificar y organizar los archivos en colecciones temáticas lógicas mediante el atributo `collection` de la tabla `media_files`:
- **Filtrado por Colección en la Biblioteca (`Media/Index.vue`)**:
  - Selector de colecciones para filtrar al instante archivos pertenecientes a categorías como `articulos`, `fondos`, `logos`, `banners` o colecciones personalizadas creadas por el usuario.
- **Creación de Colecciones al Vuelo**:
  - Tanto desde la ventana principal de medios (`Media/Index.vue`) como desde el modal selector (`MediaPickerModal.vue`) y el cargador de imágenes (`ImageUploader.vue`), es posible seleccionar una colección existente o crear una nueva en el momento de la subida.
- **Subida con Barra de Progreso y Diálogo Dedicado**:
  - Modal de subida optimizado (`mostrarModalSubida`) con selector/creador de colección, dropzone con animación de carga, porcentaje en tiempo real y detección automática de formatos de imagen y vídeo.

### 5.1. Sincronización Reactiva de Filtros y Resolución de Colecciones Extintas
- **Problema de Usabilidad Resuelto**: Si un usuario filtraba por una colección que solo contenía un archivo y posteriormente le quitaba la colección (o eliminaba el archivo), el selector de PrimeVue mostraba "Todas las colecciones" debido a la ausencia del valor en la lista de opciones, pero internamente el estado reactivo (`filtroColeccion`) seguía apuntando a la colección extinta, provocando una pantalla vacía hasta que el usuario manipulaba manualmente el selector.
- **Arquitectura de Sincronización Implementada**:
  1. **Retorno de Colecciones en Mutaciones (`MediaController.php`)**: Los endpoints de actualización (`update`), eliminación unitaria (`destroy`) y masiva (`bulkDelete`) devuelven siempre la lista actualizada de colecciones existentes (`colecciones`).
  2. **Modelos Computados PrimeVue 4**: Siguiendo la regla de no usar cadenas vacías en `optionValue`, la opción por defecto utiliza `'all'` vinculada mediante computed getters/setters (`coleccionSeleccionada` y `tipoSeleccionado`), garantizando que PrimeVue 4 reconozca el valor sin máscaras ni desincronizaciones.
  3. **Watcher Reactivo y Limpieza Defensiva**:
     - `watch(() => props.colecciones)`: Si la colección activa en el filtro deja de existir en la lista de colecciones del servidor, `filtroColeccion.value` se resetea automáticamente a `''`.
     - Dicho reseteo dispara `aplicarFiltros()`, recargando de inmediato la cuadrícula con todos los archivos multimedia sin requerir intervención del usuario.
     - `onMounted`: Limpia proactivamente el filtro si el usuario navega directamente a una URL con un parámetro `?collection=...` ya inexistente.
     - Tanto `Media/Index.vue` como `MediaPickerModal.vue` implementan esta arquitectura reactiva defensiva.

---

## 6. Selector Unificado de Vídeo (`VideoUploader.vue`) y Mejoras en el Modal de Medios

### 6.1. Componente Unificado `VideoUploader.vue`
Con el objetivo de unificar la experiencia de usuario con `ImageUploader.vue`:
- **Dropzone Único Interactivo**: Se eliminaron los botones redundantes y cajas de texto fijas. Al hacer clic sobre el área de carga se abre de inmediato el selector modal (`MediaPickerModal.vue`).
- **Subida y Selección Integrada**: Desde el propio modal se puede elegir un vídeo existente o subir uno nuevo en el acto con barra de progreso. Todas las subidas pasan por `MediaImageService::optimizarVideoFaststart()` (`ffmpeg -movflags +faststart`) garantizando reproducción por streaming HTTP Range inmediata.
- **Previsualización Reactiva**: El cargador reproduce el primer fotograma real con la etiqueta `#t=0.001` y provee acciones rápidas en hover ("Ver", "Cambiar" y "Quitar").
- **Variables Dinámicas**: Admite la inserción de variables del CMS (ej. `{article.extra_fields.mi_video}`), mostrándolas mediante un badge interactivo y estilizado.

### 6.2. Reconocimiento Visual de Archivos en `MediaPickerModal.vue`
Para resolver la dificultad al identificar vídeos o archivos multimedia en la biblioteca:
- **Nombre de Archivo Visible**: Cada tarjeta incluye en su pie el nombre exacto del archivo (`medio.filename`) en texto semibold con tooltip `:title="medio.filename"` para nombres extensos.
- **Fotograma Real de Vídeo**: En lugar de mostrar un icono genérico o un recuadro gris, los archivos multimedia de vídeo cargan automáticamente su primer fotograma útil (`<video :src="medio.url + '#t=0.001'" preload="metadata" muted playsinline>`) cubierto por un botón de reproducción distintivo.
- **Metadatos Complementarios**: Se muestra el peso formateado (`medio.formatted_size`) y las dimensiones de resolución (`medio.dimensions`).
- **Rejilla Adaptativa**: Cuadrícula optimizada (`grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5`) con formato de tarjeta vertical (`aspect-4/3` superior + pie inferior de información).

---

## 7. Pack Completo de Usabilidad y Diseño de la Biblioteca de Medios

Se ha implementado una arquitectura de interfaz moderna y ergonómica tanto en la vista principal (`resources/js/Pages/Media/Index.vue`) como en el selector modal (`resources/js/Components/Admin/MediaPickerModal.vue`):

### 7.1. Navegación y Filtros Rápidos (Chips / Pills)
- **Barra de Chips de Colecciones**: Tira horizontal con scroll suave justo debajo de la barra de búsqueda que permite alternar entre `Todas` y cada colección (`articulos`, `fondos`, etc.) con un solo clic, sin necesidad de abrir desplegables.
- **Botón de Borrado Rápido (`pi-times-circle`)**: Al escribir en el buscador de archivos, aparece un icono interactivo que permite vaciar la búsqueda en un toque.
- **Botón "Limpiar Filtros" con Contador Reactivo**: Cuando hay algún filtro activo (búsqueda, tipo o colección), se muestra el botón `Limpiar (N)` con icono `pi-filter-slash` para restablecer la vista completa en un solo clic.
- **Estadísticas en Cabecera**: El encabezado muestra badges elegantes con el total de archivos del repositorio y el desglose de imágenes y vídeos.

### 7.2. Visualización y Densidad de Cuadrícula
- **Selector de Densidad (Compacta / Normal / Detalle)**:
  - *Detalle (Grande)*: Cuadrícula de 3 a 5 columnas con miniaturas amplias para inspección visual minuciosa de fotografías y gráficos.
  - *Normal (Equilibrada)*: Cuadrícula estándar de 5 a 8 columnas.
  - *Compacta (Densa)*: Cuadrícula de 8 a 12 columnas con miniaturas cuadradas ajustadas para escaneo rápido de grandes volúmenes de archivos.
  - La preferencia se persiste en el navegador del usuario vía `localStorage('cms_media_grid_density')`.
- **Badges de Colección en Miniaturas**: Si un archivo tiene una colección asignada, la tarjeta muestra una etiqueta flotante con icono de carpeta en la esquina superior izquierda.

### 7.3. Inspector Lateral y Prevención de Errores (Smart Collection Selector)
- **Selector Inteligente de Colección**: Se sustituyó el campo de texto libre por un selector asistido que lista las colecciones existentes (`props.colecciones`) más la opción `+ Nueva colección...`. Esto erradica el 100% de los errores ortográficos o duplicidades involuntarias (ej. `portadas` vs `portada`).
- **Navegación Secuencial Anterior / Siguiente**: En la cabecera del inspector se añadieron botones de flecha `<` y `>`, contador de posición `(X/Y)` y atajos de teclado (`ArrowLeft` / `ArrowRight`) para examinar archivos consecutivamente sin tocar el ratón.
- **Microinteracción al Copiar URL**: Al hacer clic en copiar URL, el botón cambia de forma reactiva su icono a un check verde (`pi-check text-emerald-500`) y tooltip `"¡Copiado!"` durante 2 segundos.

### 7.4. Selección Múltiple y Barra Flotante (Glassmorphism)
- **Barra de Acciones Flotante**: Al seleccionar elementos en modo múltiple, emerge una barra flotante en la parte inferior de la pantalla con fondo oscuro translúcido (`backdrop-blur-md`), contador de seleccionados, botones de "Seleccionar todos", "Deseleccionar" y "Eliminar selección".
- **Atajos de Teclado**:
  - `Escape`: Cierra el inspector de detalles o deselecciona los archivos seleccionados.
  - `Ctrl+A` / `Cmd+A`: Selecciona todos los archivos de la página cuando el modo de selección está activo.




