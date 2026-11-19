/**
 * Constantes y utilidades unificadas para Escalas Visuales de Títulos y Encabezados
 * utilizadas a lo largo de todos los Inspectores y Elementos del Page Builder.
 */

export const OPCIONES_TAMANO_VISUAL = [
    { label: 'Por defecto (Según semántica / Tema)', value: 'default' },
    { label: 'Hero 2XL (8XL Gigante / ~96px)',     value: 'hero_2xl' },
    { label: 'Hero XL (6XL Portada / ~72px)',       value: 'hero_xl' },
    { label: 'H1 Grande (5XL / ~48px)',             value: 'h1' },
    { label: 'H2 Mediano (4XL / ~36px)',            value: 'h2' },
    { label: 'H3 Estándar (3XL / ~30px)',           value: 'h3' },
    { label: 'H4 Pequeño (XL / ~20px)',             value: 'h4' },
    { label: 'H5 Mini (LG / ~18px)',                value: 'h5' },
    { label: 'H6 Subtítulo (SM / ~14px)',           value: 'h6' },
    { label: 'Texto Base (16px)',                   value: 'base' },
    { label: 'Texto Pequeño (14px)',                value: 'sm' },
];

export const OPCIONES_TAMANO_VISUAL_RESPONSIVE = [
    { label: 'Heredar de Escritorio', value: '' },
    ...OPCIONES_TAMANO_VISUAL,
];

/**
 * Obtener clases CSS responsive unificadas según tamaño visual seleccionado.
 */
export function getHeadingClasses(sizeDesk = 'default', sizeTab = '', sizeMob = '', vistaSimulacion = '', fallbackTag = 'h2') {
    const mobMap = {
        default: '',
        hero_2xl: '!text-4xl',
        hero_xl: '!text-3xl',
        h1: '!text-3xl',
        h2: '!text-2xl',
        h3: '!text-xl',
        h4: '!text-lg',
        h5: '!text-base',
        h6: '!text-xs',
        base: '!text-base',
        sm: '!text-sm',
    };

    const tabMap = {
        default: '',
        hero_2xl: 'md:!text-6xl',
        hero_xl: 'md:!text-5xl',
        h1: 'md:!text-4xl',
        h2: 'md:!text-3xl',
        h3: 'md:!text-2xl',
        h4: 'md:!text-xl',
        h5: 'md:!text-base',
        h6: 'md:!text-sm',
        base: 'md:!text-base',
        sm: 'md:!text-sm',
    };

    const deskMap = {
        default: '',
        hero_2xl: 'lg:!text-8xl',
        hero_xl: 'lg:!text-6xl',
        h1: 'lg:!text-5xl',
        h2: 'lg:!text-4xl',
        h3: 'lg:!text-3xl',
        h4: 'lg:!text-xl',
        h5: 'lg:!text-lg',
        h6: 'lg:!text-sm',
        base: 'lg:!text-base',
        sm: 'lg:!text-sm',
    };

    // Si estamos en simulación dentro del editor visual:
    if (vistaSimulacion === 'mobile') {
        const val = sizeMob || sizeTab || sizeDesk;
        if (val === 'default') {
            return mobMap[fallbackTag] || mobMap['h2'];
        }
        return mobMap[val] || '';
    }

    if (vistaSimulacion === 'tablet') {
        const val = sizeTab || sizeDesk;
        if (val === 'default') {
            return tabMap[fallbackTag] || tabMap['h2'];
        }
        return tabMap[val] || mobMap[val] || '';
    }

    if (vistaSimulacion === 'desktop') {
        if (sizeDesk === 'default') return '';
        return deskMap[sizeDesk] || '';
    }

    // Modo público frontend (sin simulación manual):
    if (!sizeTab && !sizeMob) {
        if (sizeDesk === 'default') return '';
        return {
            hero_2xl: '!text-4xl sm:!text-6xl md:!text-7xl lg:!text-8xl',
            hero_xl: '!text-3xl sm:!text-5xl md:!text-6xl lg:!text-7xl',
            h1: '!text-3xl sm:!text-4xl md:!text-5xl',
            h2: '!text-2xl sm:!text-3xl md:!text-4xl',
            h3: '!text-xl sm:!text-2xl md:!text-3xl',
            h4: '!text-lg sm:!text-xl',
            h5: '!text-base sm:!text-lg',
            h6: '!text-xs sm:!text-sm',
            base: '!text-base',
            sm: '!text-sm',
        }[sizeDesk] || '';
    }

    const mClass = sizeMob ? mobMap[sizeMob] : (sizeTab ? mobMap[sizeTab] : mobMap[sizeDesk]);
    const tClass = sizeTab ? tabMap[sizeTab] : (sizeMob ? tabMap[sizeDesk] : null);
    const dClass = deskMap[sizeDesk];

    return [mClass, tClass, dClass].filter(Boolean).join(' ');
}

/**
 * Obtener objeto de estilo inline dinámico vinculado a las variables CSS tipográficas del sitio.
 */
export function getHeadingStyle(sizeDesk = 'default', fallbackTag = 'h2') {
    const key = (sizeDesk && sizeDesk !== 'default') ? sizeDesk : fallbackTag;
    if (['hero_2xl', 'hero_xl', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(key)) {
        return {
            fontFamily: "var(--heading-font), system-ui, sans-serif",
            fontSize: `var(--${key}-size)`,
            fontWeight: `var(--${key}-font-weight)`,
            letterSpacing: `var(--${key}-letter-spacing)`,
            lineHeight: `var(--${key}-line-height)`,
        };
    }
    return {
        fontFamily: "var(--heading-font), system-ui, sans-serif",
    };
}
