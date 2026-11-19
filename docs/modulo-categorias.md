# Módulo de Categorías y Disposición de Grids

El módulo de categorías permite organizar el contenido del sitio en taxonomías jerárquicas con personalización visual completa de sus grids de artículos, paginación optimizada, ordenación configurable y esquemas de campos extra.

## 1. Disposición de Grid Configurable (por Categoría)

Cada categoría permite ajustar dinámicamente la cuadrícula de artículos para diferentes dispositivos en sus formularios (`resources/js/Pages/Categorias/Create.vue` y `resources/js/Pages/Categorias/Edit.vue`):

- **Columnas en Móvil (`cols_mobile`)**: 1 o 2 columnas (`grid-cols-1`, `grid-cols-2`).
- **Columnas en Tablet (`cols_tablet`)**: 1 a 4 columnas (`sm:grid-cols-1` ... `sm:grid-cols-4`).
- **Columnas en Escritorio (`cols_desktop`)**: 1 a 6 columnas (`lg:grid-cols-1` ... `lg:grid-cols-6`).
- **Separación de Cuadrícula (`gap_size`)**:
  - `sm`: 12px (`gap-3`)
  - `md`: 24px (`gap-6`) - Estándar
  - `lg`: 32px (`gap-8`)
  - `xl`: 48px (`gap-12`)
- **Alineación Horizontal del Grid (`align_grid`)**:
  - `start`: Alineado al inicio / izquierda
  - `center`: Centrado horizontal de items
  - `end`: Alineado a la derecha

---

## 2. Criterio de Ordenación de Artículos (`articles_order`)

Configurable individualmente por categoría en la base de datos y administrable en el panel:
- **`published_at_desc`**: Fecha de Publicación (más reciente primero - predeterminado).
- **`published_at_asc`**: Fecha de Publicación Inversa (más antiguo primero).
- **`title_asc`**: Alfabético (A - Z).
- **`title_desc`**: Alfabético Inverso (Z - A).
- **`manual`**: Orden Manual (posicionamiento mediante Drag & Drop en el panel de administración).

---

## 3. Etiqueta HTML de Título en Tarjeta (`title_tag`)

Permite elegir qué etiqueta HTML (`<h1>`, `<h2>`, `<h3>` o `<h4>`) se utiliza para envolver el título de las tarjetas de artículos en la categoría.
- Facilita una estructura semántica SEO impecable en cada página.
- Previene tener múltiples `<h1>` en la categoría cuando el título principal ya es `<h1>`.

---

## 4. Jerarquía y Regla Anti-Bucle en Selector Padre

- El selector de **Categoría Padre** excluye dinámicamente a la propia categoría en el formulario de edición (`app/Http/Controllers/CategoryController.php`).
- Previene errores fatales de jerarquía circular o auto-referencia (`parent_id = id`).

---

## 5. Paginación Optimizada y Escalable

- **Artículos por Página (`per_page`)**: Configurable por categoría (de 1 a 100 elementos por página).
- **Rendimiento y Escalabilidad**: Realizada en el servidor mediante Eloquent `$query->paginate($perPage)` con payloads livianos de `ArticleResource`, garantizando respuesta instantánea incluso con millones de artículos.

---

## 6. Esquema de Campos Extra (`extra_fields_schema`)

Permite asociar atributos personalizados que los artículos de esta categoría deberán cumplimentar:
- **Tipos de datos configurables**: Texto, Área de texto, WYSIWYG, Número, Selector/Desplegable, Booleano (Sí/No), Fecha, Fecha/Hora, Hora, Color, Icono (PrimeIcons), Imagen y **Vídeo (Biblioteca de Medios)**.
- **Validación robusta en Backend**: Las solicitudes `StoreCategoryRequest` y `UpdateCategoryRequest` validan de forma estricta cada definición del esquema, asegurando claves únicas, etiquetas descriptivas y tipos de datos válidos.

---

## 7. Usabilidad y Diseño de Formularios

- **Barra Superior de Acciones**: Botones **"Guardar Cambios"** y **"Cancelar"** disponibles tanto en el header superior como al final del formulario.
- **Layout en 2 Columnas**: Separación clara entre datos principales de categoría y propiedades avanzadas (Grid, SEO, Campos extra) para una edición ágil.

---

## 8. Opciones de Visualización y Metadatos (por Categoría)

Permite activar o desactivar mediante interruptores (`ToggleSwitch`) qué elementos visuales globales y metadatos se muestran públicamente en la categoría y en sus artículos asociados:

- **Ruta del Sitio (`show_breadcrumb`)**: Muestra u oculta la navegación por migas de pan (`Inicio / Categoría / Artículo`) tanto en la vista pública de la categoría como en el detalle del artículo.
- **Paginación (`show_pagination`)**: Muestra u oculta los controles de navegación entre páginas en el listado de la categoría.
- **Autor (`show_author`)**: Muestra u oculta el nombre del autor en las tarjetas de la categoría y en el encabezado del artículo.
- **Fecha (`show_date`)**: Muestra u oculta la fecha de publicación en las tarjetas de la categoría y en el encabezado del artículo.
- **Tiempo de Lectura (`show_reading_time`)**: Muestra u oculta los minutos estimados de lectura (ej. *3 min de lectura*) en las tarjetas de la categoría y en el encabezado del artículo.
- **Insignia / Enlace a la Categoría (`show_category_link`)**: Muestra u oculta la etiqueta/enlace con el nombre de la categoría en el encabezado de la vista de detalle del artículo.
