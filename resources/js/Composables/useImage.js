/**
 * Composable reutilizable para tratamiento de imágenes en Vue 3 / SSR.
 *
 * Proporciona helpers para generar URLs optimizadas con dimensiones exactas,
 * recortes inteligentes y cadenas srcset responsivas para etiquetas <img> y <picture>.
 */

/**
 * Genera la URL dinámica optimizada para una imagen dada.
 *
 * @param {string} src Ruta o URL de la imagen
 * @param {Object} options Opciones: { w, h, fit: 'cover'|'contain'|'scale', q: 85, f: 'webp' }
 * @returns {string} URL formateada para /media/render
 */
export function imageUrl(src, options = {}) {
    if (!src || typeof src !== 'string') return '';

    // Normalizar ruta si viene sin barra inicial (ej: "storage/media/...")
    let normalizedSrc = src;
    if (!normalizedSrc.startsWith('http://') && !normalizedSrc.startsWith('https://') && !normalizedSrc.startsWith('/') && !normalizedSrc.startsWith('data:') && !normalizedSrc.startsWith('blob:')) {
        if (normalizedSrc.startsWith('storage/') || normalizedSrc.startsWith('media/')) {
            normalizedSrc = '/' + normalizedSrc;
        }
    }

    const cleanPath = normalizedSrc.split('?')[0].split('#')[0].toLowerCase();

    // Si es SVG o GIF animado o URL externa de data URI, devolver intacto normalizado
    if (cleanPath.endsWith('.svg') || cleanPath.endsWith('.gif') || normalizedSrc.startsWith('data:') || normalizedSrc.startsWith('blob:')) {
        return normalizedSrc;
    }

    // Extraer la ruta relativa de storage si viene como URL absoluta o relativa
    let relativePath = src;
    try {
        if (src.startsWith('http://') || src.startsWith('https://')) {
            const urlObj = new URL(src);
            // Si es una URL externa que no pertenece a nuestro almacenamiento /storage/, devolver original
            if (!urlObj.pathname.includes('/storage/')) {
                return src;
            }
            relativePath = urlObj.pathname.replace(/^\/?storage\//, '');
        } else {
            relativePath = src.replace(/^\/?storage\//, '');
        }
    } catch {
        relativePath = src.replace(/^\/?storage\//, '');
    }

    relativePath = relativePath.replace(/^\/+/, '');

    const params = new URLSearchParams();
    params.set('src', relativePath);

    if (options.w) params.set('w', options.w);
    if (options.h) params.set('h', options.h);
    if (options.fit) params.set('fit', options.fit);
    if (options.q) params.set('q', options.q);
    if (options.f) params.set('f', options.f);

    return `/media/render?${params.toString()}`;
}

/**
 * Genera una cadena `srcset` responsiva para distintas anchuras de pantalla.
 *
 * @param {string} src Ruta o URL de la imagen
 * @param {Array<number>} widths Lista de anchuras en px [360, 640, 960, 1280, 1600]
 * @param {Object} options Opciones adicionales (h, fit, q, f)
 * @returns {string} Cadena formateada para el atributo srcset
 */
export function imageSrcset(src, widths = [360, 640, 960, 1280, 1600], options = {}) {
    if (!src || typeof src !== 'string') return '';

    if (src.endsWith('.svg') || src.endsWith('.gif')) {
        return '';
    }

    return widths
        .map(w => {
            const opts = { ...options, w };
            // Si hay alto proporcional
            if (options.h && options.w) {
                opts.h = Math.round((options.h / options.w) * w);
            }
            return `${imageUrl(src, opts)} ${w}w`;
        })
        .join(', ');
}

export function useImage() {
    return {
        url: imageUrl,
        srcset: imageSrcset,
    };
}
