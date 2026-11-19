# Módulo de Artículos y Publicaciones

El módulo de artículos administra las publicaciones del sitio, su ciclo de vida, opciones de maquetación en grids, editor WYSIWYG, reordenamiento Drag & Drop, gestor de medios, campos dinámicos extra y control de expiración/programación en tiempo real.

## 1. Ciclo de Vida y Estados de Publicación

El sistema gestiona el estado y la visibilidad pública de los artículos de forma dinámica en tiempo real:

- **`draft` (Despublicado)**: En preparación o retirado de la web pública (badge gris `secondary` / `zinc`). No es accesible en el sitio público bajo ninguna circunstancia.
- **`scheduled` (Programado)**: Artículos con estado activo cuya fecha de inicio (`published_at`) es futura. Se muestran con badge **ámbar** en el panel de control y **se publican de forma 100% automática en la web pública en cuanto el reloj alcanza dicha fecha y hora**, sin requerir tareas manuales.
- **`published` (Publicado)**: Visible públicamente en el frontend (badge verde `emerald`) siempre que se encuentre dentro de su ventana de publicación (`published_at <= now()` y `published_until` no haya expirado).
- **`expired` (Expirado / Finalizado)**: Estado calculado dinámicamente cuando un artículo tiene fijada una fecha de fin (`published_until`) y dicha fecha ya ha pasado. Deja de ser visible en la web pública (devuelve 404) y en el panel de control se resalta con badge **rojo/rosa**.
- **Papelera (Soft Deletes)**: Los artículos eliminados se trasladan a la papelera (`deleted_at != null`), desde donde pueden restaurarse o destruirse definitivamente.

---

## 2. Programación, Fecha de Finalización y Publicación Indefinida

Cada artículo cuenta con controles precisos de calendario y ventana de visibilidad:

1. **Fecha de Publicación (`published_at`)**:
   - Fecha y hora exacta a partir de la cual el artículo es visible. Por defecto, se asigna la fecha y hora actual al crear o republicar.
2. **Fecha de Finalización (`published_until`)**:
   - **Modalidad Indefinida** (`published_until = null`): El artículo permanecerá publicado por tiempo indefinido sin caducidad.
   - **Modalidad con Fecha Límite**: El artículo dejará de ser visible y se despublicará automáticamente en el segundo exacto indicado.
3. **Sincronización de Huso Horario de Base de Datos**:
   - La conexión MySQL/MariaDB (`config/database.php`) sincroniza dinámicamente el offset de zona horaria con la configuración de la aplicación (`APP_TIMEZONE = 'Europe/Madrid'`), garantizando sincronización al segundo entre el navegador, Laravel y las consultas en base de datos.
4. **Consulta Pública Optimizada (`Article::scopePublicados`)**:
   - Evalúa de forma atómica: `status IN ('published', 'scheduled') AND published_at <= NOW() AND (published_until IS NULL OR published_until >= NOW())`.

---

## 3. Editor HTML y Gestión de Imágenes y Medios

1. **Editor Enriquecido WYSIWYG Opcional (`WysiwygEditor.vue`)**:
   - El cuerpo del artículo (`body`) es **100% opcional** (`nullable` en base de datos y validaciones). Cuando se utiliza, se redacta mediante un editor enriquecido visual basado en Quill con soporte para encabezados, negrita, listas, enlaces e inyección directa de imágenes. Si el artículo prescinde de cuerpo (por ejemplo, en artículos diseñados exclusivamente mediante plantillas del Page Builder o basados en campos extra), el frontend no renderiza contenedores vacíos.
2. **Subida y Conversión a WebP (`featured_image`)**:
   - Selector integrado con el nuevo Gestor de Medios (`MediaPickerModal.vue`) y subida directa con `MediaController` y `ImageOptimizerService`. Las imágenes rasterizadas (`.jpg`, `.jpeg`, `.png`) se optimizan y convierten automáticamente a formato `.webp` de alta eficiencia. Soporte completo para SVGs vectoriales.

---

## 4. Listado, Ordenación Visual y Reordenamiento Drag & Drop

En el listado de administración (`resources/js/Pages/Articulos/Index.vue`):

### 4.1. Listado Completo y Paginación
- Al acceder al listado sin filtros, se muestran **todos los artículos de todas las categorías**, ordenados por su posición estructural y paginados limpiamente mediante `ArticleResource`.
- La paginación utiliza los controles de PrimeVue sincronizados con la URL mediante Inertia, mostrando los controles únicamente cuando hay más de una página activa.

### 4.2. Ordenación Visual por Encabezados de Tabla
- Permite hacer clic en los encabezados de las columnas (**Título**, **Categoría**, **Estado**, **Autor** y **Fecha de Publicación**) para ordenar la vista de forma ascendente o descendente.
- **Sin impacto en el frontend ni en la base de datos**: Esta ordenación es estrictamente administrativa/visual en el panel y no modifica la columna `order` ni afecta al orden de presentación público en el frontend.

### 4.3. Blindaje del Drag & Drop Estructural
- El reordenamiento manual por arrastre mediante el tirador `<i class="pi pi-bars" />` está **blindado**:
  - **Solo se activa cuando hay una categoría filtrada** y **no hay ninguna ordenación visual por columna activa**.
  - Si el usuario está viendo todas las categorías o ha hecho clic en un encabezado para ordenar por título o fecha, el arrastre se desactiva visualmente con un tooltip explicativo.
  - Al soltar la fila dentro de una categoría específica, se envía la petición `POST /panel/articulos/reorder` con el array de IDs, actualizando la columna `order` indexada en base de datos.

> **Importante**: Para que el orden manual surta efecto en el frontend público (tanto en el grid de la categoría como en la navegación «Anterior / Siguiente»), el campo `articles_order` de la categoría debe estar configurado como `manual`. Si está en `published_at_desc` (u otro criterio), el Drag & Drop modifica el campo `order` en la base de datos pero el frontend respeta el criterio seleccionado en la categoría.

### 4.4. Preservación de Filtros y Navegación Contextual de Retorno
Para garantizar una experiencia de usuario ergonómica y no invasiva:
- **Flujo de Retorno Contextual**:
  - Al acceder a la edición o creación de un artículo desde un listado filtrado, los parámetros activos (búsqueda, categoría, estado, orden y página) se transmiten en la petición.
  - Al pulsar la flecha de retroceso, cualquiera de los botones *"Cancelar"* (superior o inferior) o al completar *"Guardar Cambios"*, el sistema devuelve al usuario exactamente al listado con los filtros y la página donde estaba trabajando.
- **Navegación Directa Limpia**:
  - Si el usuario hace clic en *"Artículos"* en el menú principal o navega a `/panel/articulos`, accede a la vista limpia por defecto sin parámetros forzados en la URL ni redirecciones automáticas.
- **Botón "Limpiar Filtros"**:
  - Cuando hay filtros activos en el listado, la barra muestra un botón *"Limpiar filtros"* (`pi pi-filter-slash`) para restablecer todos los selectores y la búsqueda a su estado inicial en un solo clic.

---

## 5. Navegación entre Artículos: Secuencial y Paginación Numérica de Serie

En la vista pública del detalle de artículo (`resources/js/Pages/Public/Article.vue`) o mediante el elemento modular del Page Builder `ElementPagination.vue`:

### 5.1. Navegación Secuencial Anterior / Siguiente (`article_nav`)
- Se calculan dinámicamente las tarjetas de navegación al pie del cuerpo del artículo:
  - **« Artículo Anterior** (si existe)
  - **Artículo Siguiente »** (si existe)
- El cálculo se realiza en `ArticleService::obtenerNavegacionAnteriorYSiguiente()` de forma optimizada $O(1)$ y respeta **estrictamente el criterio de ordenación configurado en la categoría** (Fecha, Fecha inversa, Alfabético A-Z, Alfabético Z-A u Orden Manual).
- Dispone de 5 estilos visuales configurables desde el constructor: Tarjetas (`cards`), Minimalista (`minimal`), Cápsulas (`pills`), Hero (`hero`) y Barra Compacta (`compact`).

### 5.2. Paginación Numérica de Artículos Hermanos (`archive_numeric`)
- Cuando se utiliza el elemento `ElementPagination` con modo `archive_numeric` dentro de una plantilla de artículo:
  - En lugar de paginar un listado, el elemento representa la **serie de artículos de la misma categoría** como enlaces numéricos `1, 2, 3... N`.
  - El artículo actual en visualización se marca como activo (`active: true`), permitiendo al usuario saltar de forma directa a cualquier entrega de la serie.
  - **Enlaces Canónicos Limpios**: Cada número enlaza a la URL propia del artículo hermano (`/{categoria}/{slug-hermano}`), asegurando que nunca se generen URLs no deseadas del tipo `.../pagina/X` en un artículo singular.
  - Al igual que la navegación secuencial, respeta el `articles_order` de la categoría.

### 5.3. Inyección Automática en Plantillas (`PageBuilderResolverService`)
- Al resolver una plantilla de artículo (`resolveForArticle`), el servicio inyecta de forma recursiva en cada `ElementPagination` tanto el nodo `resolved_data.navegacion` como el nodo `resolved_data.paginacion` (con `is_article_series => true`).
- De este modo, el diseñador puede alternar entre navegación secuencial o paginación numérica desde el inspector visual sin necesidad de reiniciar o re-resolver la plantilla.

---

## 6. Opciones Configurables de Tarjeta (Grids de Categorías)

Cada artículo permite personalizar su tarjeta visual en los listados y categorías mediante las siguientes opciones:

- **`card_full_link`** (boolean, defecto `true`): Permite decidir si toda la tarjeta del artículo es un enlace interactivo al detalle o si solo lo son el título/botón.
- **`show_read_more`** (boolean, defecto `true`): Alterna la visibilidad del botón/enlace "Leer más" al pie de la tarjeta.
- **`read_more_text`** (string, defecto `'Leer más'`): Permite personalizar el texto del botón de acción (ej: *"Descubrir"*, *"Ver noticia"*, *"Leer artículo"*).

---

## 7. Esquema de Campos Extra Dinámicos (`extra_fields_schema` / `extra_fields_data`)

Los artículos admiten una amplia variedad de tipos de campos profesionales definidos en el esquema de su categoría:

1. **Tipos de campos soportados**:
   - `text`: Texto corto estándar.
   - `textarea`: Área de texto multilinea.
   - `wysiwyg`: Editor enriquecido HTML completo.
   - `number`: Valor numérico con paso y límites.
   - `select`: Desplegable con opciones personalizadas (`key:label`).
   - `boolean`: Interruptor Sí / No (`ToggleSwitch`).
   - `date`: Selector de fecha (`DD/MM/YYYY`).
   - `datetime`: Selector de fecha y hora (`DD/MM/YYYY, HH:mm`).
   - `time`: Selector de hora.
   - `color`: Selector de color hexadecimal con vista previa.
   - `icon`: Selector visual de iconos de PrimeIcons.
   - `image`: Campo de imagen conectado con el selector de la Biblioteca de Medios.
   - `video`: Campo de vídeo conectado con el selector modal y la Biblioteca de Medios (`VideoUploader.vue`), con previsualización del primer fotograma y optimización faststart.
   - `url`: Enlace web validado.
   - `email`: Correo electrónico.

2. **Internacionalización (i18n)**:
   - Configuración global del calendario PrimeVue en castellano (`resources/js/Config/locale.js`) para meses, días de la semana y nombres abreviados.

3. **Sincronización y Limpieza Automática de Campos Eliminados**:
   - En `ArticleResource`, el método `obtenerExtraFieldsDataFiltrados()` limpia automáticamente de la respuesta cualquier clave que haya sido eliminada del esquema de la categoría, previniendo datos huérfanos o incongruencias en el frontend público.

---

## 8. Usabilidad y Diseño de Formularios

Para maximizar el confort visual y optimizar el flujo de trabajo:
- **Diseño a Ancho Completo (`w-full`)**: Los formularios de creación y edición prescinden de contenedores con anchos estrechos o rígidos (`max-w-7xl`), aprovechando con fluidez toda la resolución disponible del monitor.
- **Barra Superior Fija de Acciones**: Botones principales **"Guardar Cambios"** y **"Cancelar"** colocados tanto en la cabecera superior junto al título como al pie del formulario para evitar scroll innecesario.
- **Maquetación Proporcional en 2 Columnas**:
  - **Columna Principal (Izquierda, `flex-1`)**: Título del artículo, editor WYSIWYG, configuración de tarjetas y extracto.
  - **Columna Lateral (Derecha, `w-80` a `w-96` / `xl:w-[420px]`)**: Opciones secundarias ordenadas lógicamente: Publicación y fechas, Categoría y Autor, Imagen Destacada, y el bloque de Campos Extra Dinámicos.


---

## 9. Características Técnicas Adicionales

1. **Tiempo de Lectura Automático**:
   - Se calcula automáticamente en `ArticleService` a razón de ~200 palabras por minuto.
2. **Contador de Visitas**:
   - Incrementado atómicamente mediante `increment('views_count')` al visitar la vista pública.
3. **Duplicación de Artículos (`duplicar`)**:
   - Clonación con todo su contenido, campos extra y esquema SEO guardados automáticamente en estado **`draft` (Despublicado)**.

---

## 10. Buscador Público de Contenidos (`/buscar`)

El CMS dispone de un motor de búsqueda público en tiempo real accesible desde la ruta `GET /buscar` (y conectado desde el buscador integrado en la página de error 404):

1. **Ruta y Controlador (`PublicPageController@search`)**:
   - Captura el parámetro `q` enviado por la petición HTTP (`/buscar?q=termino`).
2. **Consultas y Coincidencias**:
   - Filtra únicamente artículos publicados activos (`Article::publicados()`) buscando coincidencias por palabras clave en **Título**, **Extracto** y **Cuerpo**.
3. **Respuesta Paginada y SPA**:
   - Retorna una estructura paginada (12 elementos por página con `$articulos->withQueryString()`) formateada mediante `ArticleResource`.
4. **Vista de Resultados (`resources/js/Pages/Public/Search.vue`)**:
   - Renderiza la cabecera de resultados, un campo de búsqueda interactivo, la cuadrícula de tarjetas (`ArticleCard.vue`), el paginador público y un estado de resultados vacíos con sugerencias de búsqueda.
