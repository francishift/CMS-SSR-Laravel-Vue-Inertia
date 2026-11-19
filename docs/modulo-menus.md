# Módulo de Menús y Navegación

El módulo de menús administra la estructura de navegación pública del CMS (Header, Sidebar).

## Arquitectura

### Modelos y Tablas
- **`Menu`** (`menus`): Define un menú (`name`, `slug`, `location`, `is_active`).
  - **Ubicaciones admitidas (`location`)**:
    - `'header'`: Cabecera principal del sitio web.
    - `'sidebar'`: Barra lateral de navegación.
    - `'custom'`: Menú independiente **Sin asignar / Personalizado para Page Builder**. No interfiere con la cabecera ni la barra lateral fija; se invoca modularmente desde cualquier bloque `ElementMenu` del maquetador visual (páginas, pie de página o plantillas).
    - `'footer'`: Pie de página (soporte legado / compatible).
- **`MenuItem`** (`menu_items`): Representa cada enlace (`title`, `url`, `route_name`, `category_id`, `article_id`, `page_id`, `target`, `icon`, `order`, `is_active`, `parent_id`).

### Resolutor Dinámico de URLs
En `MenuItem::resolvedUrl`, la URL se resuelve dinámicamente según el tipo de elemento:
1. **Inicio (`/`)**: Retorna `/`.
2. **Categoría (`category_id`)**: Retorna `/{category_slug}`.
3. **Artículo (`article_id`)**: Retorna `/{category_slug}/{article_slug}`.
4. **Página Estática (`page_id`)**: Retorna `/{page_slug}`.
5. **Catálogo de Descargas (`type = 'downloads'`)**: Retorna dinámicamente el slug activo configurado en `DownloadSetting` (ej. `/descargas`).
6. **URL Personalizada (`url`)**: Retorna la URL personalizada configurada.

## Caché y Rendimiento
- Los menús públicos y la colección completa de menús activos se almacenan en caché de memoria Redis (`menu_header`, `menu_sidebar`, `menus_activos_con_items`).
- Al crear, actualizar o eliminar menús o elementos, `MenuService::invalidarCache()` y el observador `Menu::booted()` limpian automáticamente la caché taggeada (`['menus']`) para reflejar los cambios al instante en menos de 1ms sin consultas a MySQL.

## Interfaz de Usuario (PrimeVue)
- **Desplegables buscables**: `<Select filter>` para seleccionar categorías, artículos y páginas.
- **Edición completa**: Modal `Dialog` para editar cualquier elemento existente.
- **Iconos vectoriales**: Selección mediante iconos nativos PrimeIcons (`pi pi-home`, `pi pi-folder`, etc.).
