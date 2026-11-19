/**
 * Composable de sanitización HTML para el frontend público.
 *
 * Utiliza DOMPurify (isomorphic) para prevenir XSS en todo contenido
 * renderizado con v-html. Compatible con SSR (Node.js) y cliente (navegador).
 *
 * Uso:
 *   import { useSanitize } from '@/Composables/useSanitize';
 *   const { sanitize } = useSanitize();
 *   const htmlSeguro = sanitize(htmlPeligroso);
 */
import DOMPurify from 'dompurify';

export const COOKIES_BUTTON_HTML = '<button type="button" class="cc-shortcode-btn" data-cc-shortcode="true"><i class="pi pi-shield"></i> Preferencias de cookies</button>';

/**
 * Reemplaza los shortcodes conocidos como {{cookies_button}} por sus elementos HTML interactivos.
 *
 * @param {string} html
 * @returns {string}
 */
export function resolverShortcodes(html) {
    if (!html || typeof html !== 'string') return html;
    return html.replace(/\{\{\s*cookies_button\s*\}\}/gi, COOKIES_BUTTON_HTML);
}

/**
 * Valida que una URL extraída de un manejador editorial inline sea segura para navegación
 * (bloqueando esquemas ejecutables como javascript:, data: o vbscript:).
 *
 * @param {string} url
 * @returns {boolean}
 */
function esUrlSeguraNavegacion(url) {
    if (!url || typeof url !== 'string') return false;
    const limpia = url.trim();
    if (!limpia || limpia.startsWith('//')) return false;
    return /^(https?:\/\/|\/|#|mailto:|tel:)/i.test(limpia);
}

/**
 * Escapa caracteres especiales para su inserción segura dentro de un atributo HTML entre comillas dobles.
 *
 * @param {string} valor
 * @returns {string}
 */
function escaparAtributoHtml(valor) {
    return String(valor)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/**
 * Transforma manejadores de navegación inline habituales en contenido editorial
 * (ej. onclick="window.open('https://...', '_blank')" o onclick="location.href='/ruta'")
 * en atributos declarativos compatibles con Content Security Policy estricta (data-cms-open-url),
 * y elimina cualquier otro atributo de evento inline (on*) tanto en SSR como en cliente.
 *
 * @param {string} html
 * @returns {string}
 */
export function normalizarEventosInlineHtml(html) {
    if (!html || typeof html !== 'string' || !/\bon[a-z]+\s*=/i.test(html)) {
        return html || '';
    }

    return html.replace(/<([a-zA-Z][a-zA-Z0-9:-]*)(\s[^<>]*?)?(\/?)>/g, (fullTag, tagName, rawAttrs = '', selfClosing = '') => {
        if (!rawAttrs || !/\bon[a-z]+\s*=/i.test(rawAttrs)) {
            return fullTag;
        }

        const attrsLimpios = rawAttrs.replace(
            /\s+(on[a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'=<>`]+))/gi,
            (_attrMatch, attrName, doubleQuoted, singleQuoted, unquoted) => {
                if (String(attrName).toLowerCase() !== 'onclick') {
                    return '';
                }

                const expr = (doubleQuoted ?? singleQuoted ?? unquoted ?? '').trim();
                if (!expr) return '';

                // Patrón 1: window.open('URL', '_blank', ...) o open("URL")
                const matchOpen = expr.match(
                    /^(?:return\s+)?(?:window\.)?open\(\s*(['"])([^'"]+)\1(?:\s*,\s*(['"])([^'"]*)\3)?[^)]*\)\s*;?(?:\s*return\s+false\s*;?)?$/i
                );
                if (matchOpen) {
                    const url = matchOpen[2].trim();
                    const target = (matchOpen[4] || '_blank').trim() || '_blank';
                    if (esUrlSeguraNavegacion(url)) {
                        return ` data-cms-open-url="${escaparAtributoHtml(url)}" data-cms-open-target="${escaparAtributoHtml(target)}"`;
                    }
                    return '';
                }

                // Patrón 2: location.href = 'URL' / window.location = 'URL' / location.assign('URL')
                const matchLocationAssign = expr.match(
                    /^(?:window\.|document\.)?location(?:\.href)?\s*=\s*(['"])([^'"]+)\1\s*;?$/i
                ) || expr.match(
                    /^(?:window\.)?location\.(?:assign|replace)\(\s*(['"])([^'"]+)\1\s*\)\s*;?$/i
                );
                if (matchLocationAssign) {
                    const url = matchLocationAssign[2].trim();
                    if (esUrlSeguraNavegacion(url)) {
                        return ` data-cms-open-url="${escaparAtributoHtml(url)}" data-cms-open-target="_self"`;
                    }
                }

                return '';
            }
        );

        return `<${tagName}${attrsLimpios}${selfClosing}>`;
    });
}

/**
 * Configuración global de DOMPurify.
 * Permite tags y atributos seguros para contenido CMS rico.
 */
const PURIFY_CONFIG = {
    // Tags HTML permitidos (cobertura completa para contenido editorial)
    ALLOWED_TAGS: [
        // Estructura
        'p', 'br', 'hr', 'div', 'span', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav',
        // Encabezados
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        // Formato inline
        'b', 'i', 'u', 's', 'em', 'strong', 'small', 'sub', 'sup', 'mark', 'del', 'ins', 'abbr', 'code', 'kbd', 'var',
        // Listas
        'ul', 'ol', 'li',
        // Tablas
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        // Media
        'img', 'figure', 'figcaption', 'picture', 'source', 'video', 'audio',
        // Otros
        'a', 'blockquote', 'pre', 'cite', 'q', 'dl', 'dt', 'dd', 'details', 'summary',
        // Botones interactivos (shortcodes y acciones declarativas)
        'button',
        // Embeds seguros (iframes se filtran con ALLOWED_URI_REGEXP)
        'iframe',
    ],
    // Atributos permitidos
    ALLOWED_ATTR: [
        'href', 'src', 'alt', 'title', 'class', 'id', 'name',
        'width', 'height', 'style',
        'target', 'rel',
        'role', 'aria-label', 'aria-hidden', 'aria-expanded', 'aria-controls',
        'colspan', 'rowspan', 'scope', 'headers',
        'loading', 'decoding', 'srcset', 'sizes',
        'controls', 'autoplay', 'loop', 'muted', 'poster', 'preload',
        'type', 'media',
        'frameborder', 'allowfullscreen', 'allow',
        'open', // para <details>
        'start', 'reversed', // para <ol>
        'data-align', 'data-size', 'data-cc-shortcode', 'data-builder-navigate-url',
        'data-cms-open-url', 'data-cms-open-target',
    ],
    // Permitir iframes y botones de confianza
    ADD_TAGS: ['iframe', 'button'],
    // Forzar target="_blank" en enlaces externos y atributos data seguros
    ADD_ATTR: ['target', 'data-cc-shortcode', 'data-cms-open-url', 'data-cms-open-target'],
};

/**
 * Composable que expone la función de sanitización.
 */
export function useSanitize() {
    /**
     * Sanitiza HTML para renderizado seguro con v-html, resolviendo shortcodes integrados
     * y normalizando eventos inline para cumplir estrictamente con CSP en SSR y cliente.
     *
     * @param {string} html - HTML potencialmente peligroso
     * @returns {string} HTML sanitizado y seguro
     */
    function sanitize(html) {
        if (!html || typeof html !== 'string') return '';
        const procesado = normalizarEventosInlineHtml(resolverShortcodes(html));
        if (typeof window === 'undefined') return procesado;
        return DOMPurify.sanitize(procesado, PURIFY_CONFIG);
    }

    return { sanitize, resolverShortcodes, normalizarEventosInlineHtml };
}
