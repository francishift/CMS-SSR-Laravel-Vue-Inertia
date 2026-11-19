/**
 * Utilidades para normalización y validación de colores hexadecimales e imágenes de fondo.
 */
export function normalizarColor(val, fallback = '#10b981') {
    if (!val || typeof val !== 'string') return fallback;
    const clean = val.trim();
    if (clean.startsWith('#')) return clean;
    if (/^[0-9a-fA-F]{3,6}$/.test(clean)) {
        return '#' + clean;
    }
    return clean || fallback;
}

export function normalizarColorPicker(val, fallback = '#10b981') {
    const norm = normalizarColor(val, fallback);
    if (/^#[0-9a-fA-F]{6}$/i.test(norm)) return norm;
    return fallback;
}

export function normalizarBgImage(val, fallback = 'none') {
    if (!val || typeof val !== 'string') return fallback;
    let clean = val.trim();
    // Eliminar punto y coma residual que se suele copiar al pegar código CSS
    clean = clean.replace(/;+\s*$/, '').trim();
    if (!clean || clean === 'none') return 'none';
    if (clean.includes('radial-gradient(50% 50%,')) {
        clean = clean.replace('radial-gradient(50% 50%,', 'radial-gradient(50% 50% at 50% 50%,');
    }
    return clean;
}
