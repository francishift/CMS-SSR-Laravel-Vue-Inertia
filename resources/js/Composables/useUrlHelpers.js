/**
 * Composable de utilidades para URLs en la web pública.
 *
 * Centraliza las funciones de detección de enlaces internos vs externos
 * y decide si un enlace debe procesarse mediante el router SPA de Inertia
 * o como navegación nativa del navegador (<a>).
 */

/**
 * Patrones de rutas del servidor y archivos que no son páginas Inertia
 * (redirecciones nativas de contacto, descargas directas, recursos de almacenamiento).
 */
const RUTAS_NO_INERTIA = [
    /^\/(whatsapp|llamar|telefono|email|mail)(\/|\?|$)/i,
    /^\/download-vault(\/|\?|$)/i,
    /^\/media\/render(\/|\?|$)/i,
    /^\/storage\//i,
    /\.(pdf|zip|rar|csv|xlsx?|docx?|jpe?g|png|webp|svg|mp4)$/i,
];

/**
 * Comprobar si una URL pertenece al mismo dominio de la aplicación.
 *
 * @param {string|null} url - URL a evaluar.
 * @returns {boolean}
 */
export function esEnlaceInterno(url) {
    if (!url || typeof url !== 'string') return false;
    const clean = url.trim();
    if (!clean) return false;

    // Esquemas de protocolo directo o anclas que no pertenecen al enrutamiento de páginas web
    if (/^(tel:|mailto:|whatsapp:|sms:|callto:|tg:|viber:|javascript:|#)/i.test(clean)) {
        return false;
    }

    // Rutas relativas son siempre internas de nuestro propio dominio
    if (clean.startsWith('/')) {
        return true;
    }

    // URLs absolutas: comprobar si el hostname coincide con el del entorno actual
    try {
        const parsed = new URL(clean);
        return typeof window !== 'undefined' && parsed.hostname === window.location.hostname;
    } catch {
        return false;
    }
}

/**
 * Determina si un enlace debe procesarse mediante el router SPA de Inertia (<Link>)
 * o mediante navegación nativa del navegador (<a>).
 *
 * @param {string|null} url - URL a evaluar.
 * @param {string} [target='_self'] - Atributo target del enlace.
 * @returns {boolean}
 */
export function esNavegacionInertia(url, target = '_self') {
    if (!url || typeof url !== 'string') return false;
    if (target && target !== '_self') return false;

    const clean = url.trim();
    if (!clean || clean.startsWith('#')) return false;

    // Solo enlaces internos de nuestro dominio pueden ser páginas Inertia
    if (!esEnlaceInterno(clean)) return false;

    // Extraer ruta limpia sin parámetros de búsqueda ni anclas
    const pathname = clean.startsWith('/')
        ? clean.split('?')[0].split('#')[0]
        : (() => {
            try { return new URL(clean).pathname; } catch { return clean; }
        })();

    // Comprobar si es un archivo estático o un endpoint de acción directa del backend
    for (const pattern of RUTAS_NO_INERTIA) {
        if (pattern.test(pathname)) {
            return false;
        }
    }

    return true;
}

/**
 * Extraer la ruta relativa de una URL absoluta para usarla en <Link :href="...">.
 *
 * @param {string|null} url - URL a convertir.
 * @returns {string}
 */
export function getRutaInterna(url) {
    if (!url) return '/';
    if (url.startsWith('/')) return url;
    try {
        const parsed = new URL(url);
        return parsed.pathname + parsed.search + parsed.hash;
    } catch {
        return url;
    }
}
