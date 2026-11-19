/**
 * Helper y utilidades para determinar si un elemento o columna del Page Builder está vacío
 * y gestionar el colapso condicional de columnas con expansión del espacio restante.
 */

/**
 * Determina si un elemento modular está vacío (sin contenido real o variable dinámica sin resolver).
 *
 * @param {Object} elem Objeto elemento del Page Builder.
 * @returns {boolean}
 */
export function esElementoVacio(elem) {
    if (!elem) return true;
    const type = elem.type;
    const content = elem.content || {};

    // 1. Elemento Vídeo
    if (type === 'video') {
        const src = (content.src || '').trim();
        // Si no hay src o sigue siendo una etiqueta dinámica sin resolver ej: {article.extra_fields.video}
        return !src || (src.startsWith('{') && src.endsWith('}'));
    }

    // 2. Elemento Imagen
    if (type === 'image') {
        const src = (content.src || content.url || '').trim();
        return !src || (src.startsWith('{') && src.endsWith('}'));
    }

    // 3. Elemento Texto
    if (type === 'text') {
        const raw = (content.html || content.text || '')
            .replace(/<[^>]*>/g, '')
            .replace(/&nbsp;/g, ' ')
            .trim();
        return !raw || (raw.startsWith('{') && raw.endsWith('}'));
    }

    // 4. Elemento Encabezado (Heading)
    if (type === 'heading') {
        const text = (content.text || '').trim();
        const subtitle = (content.subtitle || '').trim();
        const hasText = Boolean(text && !(text.startsWith('{') && text.endsWith('}')));
        const hasSub = Boolean(subtitle && !(subtitle.startsWith('{') && subtitle.endsWith('}')));
        return !hasText && !hasSub;
    }

    // 5. Elemento Botón
    if (type === 'button') {
        const text = (content.text || content.label || '').trim();
        const url = (content.url || '').trim();
        return !text || !url || (text.startsWith('{') && text.endsWith('}'));
    }

    // 6. Elemento Icono
    if (type === 'icon') {
        const icon = (content.icon || '').trim();
        return !icon || (icon.startsWith('{') && icon.endsWith('}'));
    }

    // 7. Elemento Galería
    if (type === 'gallery') {
        const items = elem.resolved_data || content.items || [];
        return !Array.isArray(items) || items.length === 0;
    }

    // 8. Elemento Grid de Artículos
    if (type === 'grid_articles' || type === 'grid') {
        if (Array.isArray(elem.resolved_data)) {
            return elem.resolved_data.length === 0;
        }
        return false;
    }

    // 9. Elemento HTML Personalizado
    if (type === 'html') {
        const html = (content.html || '').trim();
        return !html;
    }

    // 10. Elemento Sublayout (recursivo)
    if (type === 'sublayout') {
        const rows = content.rows || [];
        if (!Array.isArray(rows) || rows.length === 0) return true;
        return rows.every(row => {
            const cols = row.columns || [];
            return cols.every(col => esColumnaVacia(col));
        });
    }

    return false;
}

/**
 * Determina si una columna está completamente vacía (todos sus elementos están vacíos o no tiene ninguno).
 *
 * @param {Object} column Objeto de columna.
 * @returns {boolean}
 */
export function esColumnaVacia(column) {
    if (!column) return true;
    const elements = column.elements || [];
    if (!Array.isArray(elements) || elements.length === 0) {
        return true;
    }
    return elements.every(elem => esElementoVacio(elem));
}

/**
 * Obtiene las columnas visibles de una fila aplicando la regla de auto-colapso si están vacías.
 *
 * @param {Array} columns Lista de columnas.
 * @param {boolean} rowAutoCollapse Si la fila tiene activo el colapso automático general.
 * @returns {Array} Lista de columnas visibles.
 */
export function filtrarColumnasVisibles(columns, rowAutoCollapse = false) {
    if (!Array.isArray(columns)) return [];
    return columns.filter(col => {
        const colapsar = Boolean(col.collapse_if_empty || col.settings?.collapse_if_empty || rowAutoCollapse);
        if (colapsar && esColumnaVacia(col)) {
            return false;
        }
        return true;
    });
}

/**
 * Determina el layout responsivo efectivo tras el posible colapso de columnas hermanas.
 * Si había 2 columnas y colapsa 1 -> '1' (100% ancho).
 * Si había 3 columnas y colapsa 1 -> '1/2+1/2'.
 *
 * @param {string} originalLayout Layout original (ej: '1/2+1/2', '2/3+1/3').
 * @param {number} totalOriginal Cantidad original de columnas.
 * @param {number} totalVisibles Cantidad de columnas visibles tras colapso.
 * @returns {string} Layout efectivo adaptado.
 */
export function calcularLayoutEfectivo(originalLayout, totalOriginal, totalVisibles) {
    if (totalVisibles === 1 && totalOriginal > 1) {
        return '1';
    }
    if (totalVisibles === 2 && totalOriginal > 2) {
        return '1/2+1/2';
    }
    if (totalVisibles === 3 && totalOriginal > 3) {
        return '1/3+1/3+1/3';
    }
    return originalLayout || '1/2+1/2';
}
