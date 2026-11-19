/**
 * Catálogo de Presets de Cuadrícula y Utilidades de Distribución de Columnas.
 * Permite selección visual de 24 layouts y cálculo responsivo granular por breakpoint.
 */

export const GRID_PRESETS = [
    // 1 Columna
    {
        id: '1',
        name: 'Whole',
        label: '1 Columna (100%)',
        cols: 1,
        fractions: ['1/1'],
        rectangles: [1],
    },
    // 2 Columnas
    {
        id: '1/2+1/2',
        name: 'Halves',
        label: '2 Cols Iguales (1/2 + 1/2)',
        cols: 2,
        fractions: ['1/2', '1/2'],
        rectangles: [1, 1],
    },
    {
        id: '2/3+1/3',
        name: 'Thirds 2-1',
        label: '2 Cols (2/3 + 1/3)',
        cols: 2,
        fractions: ['2/3', '1/3'],
        rectangles: [2, 1],
    },
    {
        id: '1/3+2/3',
        name: 'Thirds 1-2',
        label: '2 Cols (1/3 + 2/3)',
        cols: 2,
        fractions: ['1/3', '2/3'],
        rectangles: [1, 2],
    },
    {
        id: '3/4+1/4',
        name: 'Quarters 3-1',
        label: '2 Cols (3/4 + 1/4)',
        cols: 2,
        fractions: ['3/4', '1/4'],
        rectangles: [3, 1],
    },
    {
        id: '1/4+3/4',
        name: 'Quarters 1-3',
        label: '2 Cols (1/4 + 3/4)',
        cols: 2,
        fractions: ['1/4', '3/4'],
        rectangles: [1, 3],
    },
    {
        id: '2/5+3/5',
        name: 'Fifths 2-3',
        label: '2 Cols (2/5 + 3/5)',
        cols: 2,
        fractions: ['2/5', '3/5'],
        rectangles: [2, 3],
    },
    {
        id: '3/5+2/5',
        name: 'Fifths 3-2',
        label: '2 Cols (3/5 + 2/5)',
        cols: 2,
        fractions: ['3/5', '2/5'],
        rectangles: [3, 2],
    },
    {
        id: '4/5+1/5',
        name: 'Fifths 4-1',
        label: '2 Cols (4/5 + 1/5)',
        cols: 2,
        fractions: ['4/5', '1/5'],
        rectangles: [4, 1],
    },
    {
        id: '1/5+4/5',
        name: 'Fifths 1-4',
        label: '2 Cols (1/5 + 4/5)',
        cols: 2,
        fractions: ['1/5', '4/5'],
        rectangles: [1, 4],
    },
    {
        id: '5/6+1/6',
        name: 'Sixths 5-1',
        label: '2 Cols (5/6 + 1/6)',
        cols: 2,
        fractions: ['5/6', '1/6'],
        rectangles: [5, 1],
    },
    {
        id: '1/6+5/6',
        name: 'Sixths 1-5',
        label: '2 Cols (1/6 + 5/6)',
        cols: 2,
        fractions: ['1/6', '5/6'],
        rectangles: [1, 5],
    },

    // 3 Columnas
    {
        id: '1/3+1/3+1/3',
        name: 'Thirds',
        label: '3 Cols Iguales (1/3 x 3)',
        cols: 3,
        fractions: ['1/3', '1/3', '1/3'],
        rectangles: [1, 1, 1],
    },
    {
        id: '2/4+1/4+1/4',
        name: 'Quarters 2-1-1',
        label: '3 Cols (2/4 + 1/4 + 1/4)',
        cols: 3,
        fractions: ['2/4', '1/4', '1/4'],
        rectangles: [2, 1, 1],
    },
    {
        id: '1/4+1/4+2/4',
        name: 'Quarters 1-1-2',
        label: '3 Cols (1/4 + 1/4 + 2/4)',
        cols: 3,
        fractions: ['1/4', '1/4', '2/4'],
        rectangles: [1, 1, 2],
    },
    {
        id: '1/4+2/4+1/4',
        name: 'Quarters 1-2-1',
        label: '3 Cols (1/4 + 2/4 + 1/4)',
        cols: 3,
        fractions: ['1/4', '2/4', '1/4'],
        rectangles: [1, 2, 1],
    },
    {
        id: '3/5+1/5+1/5',
        name: 'Fifths 3-1-1',
        label: '3 Cols (3/5 + 1/5 + 1/5)',
        cols: 3,
        fractions: ['3/5', '1/5', '1/5'],
        rectangles: [3, 1, 1],
    },
    {
        id: '1/5+1/5+3/5',
        name: 'Fifths 1-1-3',
        label: '3 Cols (1/5 + 1/5 + 3/5)',
        cols: 3,
        fractions: ['1/5', '1/5', '3/5'],
        rectangles: [1, 1, 3],
    },
    {
        id: '1/5+3/5+1/5',
        name: 'Fifths 1-3-1',
        label: '3 Cols (1/5 + 3/5 + 1/5)',
        cols: 3,
        fractions: ['1/5', '3/5', '1/5'],
        rectangles: [1, 3, 1],
    },

    // 4 Columnas
    {
        id: '1/4+1/4+1/4+1/4',
        name: 'Quarters',
        label: '4 Cols Iguales (1/4 x 4)',
        cols: 4,
        fractions: ['1/4', '1/4', '1/4', '1/4'],
        rectangles: [1, 1, 1, 1],
    },
    {
        id: '2/5+1/5+1/5+1/5',
        name: 'Fifths 2-1-1-1',
        label: '4 Cols (2/5 + 1/5 + 1/5 + 1/5)',
        cols: 4,
        fractions: ['2/5', '1/5', '1/5', '1/5'],
        rectangles: [2, 1, 1, 1],
    },
    {
        id: '1/5+1/5+1/5+2/5',
        name: 'Fifths 1-1-1-2',
        label: '4 Cols (1/5 + 1/5 + 1/5 + 2/5)',
        cols: 4,
        fractions: ['1/5', '1/5', '1/5', '2/5'],
        rectangles: [1, 1, 1, 2],
    },

    // 5 Columnas
    {
        id: '1/5*5',
        name: 'Fifths',
        label: '5 Cols Iguales (1/5 x 5)',
        cols: 5,
        fractions: ['1/5', '1/5', '1/5', '1/5', '1/5'],
        rectangles: [1, 1, 1, 1, 1],
    },

    // 6 Columnas
    {
        id: '1/6*6',
        name: 'Sixths',
        label: '6 Cols Iguales (1/6 x 6)',
        cols: 6,
        fractions: ['1/6', '1/6', '1/6', '1/6', '1/6', '1/6'],
        rectangles: [1, 1, 1, 1, 1, 1],
    },
];

/**
 * Grupos de opciones de tamaño para Select / Dropdown de PrimeVue 4.
 */
export const COLUMN_WIDTH_GROUPS = [
    {
        label: 'Fraction Layouts (Fracciones)',
        items: [
            { label: '1/1 (100%)', value: '1/1' },
            { label: '1/2 (50%)', value: '1/2' },
            { label: '1/3 (33.33%)', value: '1/3' },
            { label: '2/3 (66.66%)', value: '2/3' },
            { label: '1/4 (25%)', value: '1/4' },
            { label: '3/4 (75%)', value: '3/4' },
            { label: '1/5 (20%)', value: '1/5' },
            { label: '2/5 (40%)', value: '2/5' },
            { label: '3/5 (60%)', value: '3/5' },
            { label: '4/5 (80%)', value: '4/5' },
            { label: '1/6 (16.66%)', value: '1/6' },
            { label: '5/6 (83.33%)', value: '5/6' },
        ],
    },
    {
        label: 'Fixed / Dynamic Layouts (Fijo / Dinámico)',
        items: [
            { label: 'Expandir (Espacio libre)', value: 'expand' },
            { label: 'Auto (Ajuste a contenido)', value: 'auto' },
            { label: 'Pequeño (192px)', value: 'sm' },
            { label: 'Medio (256px)', value: 'md' },
            { label: 'Grande (320px)', value: 'lg' },
            { label: 'Extragrande (384px)', value: 'xl' },
            { label: '2X-Grande (448px)', value: '2xl' },
        ],
    },
];

/**
 * Lista plana de opciones con opción de herencia para breakpoints específicos.
 */
export const getColumnWidthOptionsWithInherit = () => [
    {
        label: 'General',
        items: [
            { label: '— Heredar del breakpoint anterior / preset —', value: 'inherit' },
        ],
    },
    ...COLUMN_WIDTH_GROUPS,
];

/**
 * Opciones de orden visual para PrimeVue 4 Selects.
 */
export const COLUMN_ORDER_OPTIONS = [
    { label: 'Ninguno / Por defecto', value: 'none' },
    { label: 'Primero (order-first)', value: 'first' },
    { label: 'Último (order-last)', value: 'last' },
    { label: 'Posición 1', value: '1' },
    { label: 'Posición 2', value: '2' },
    { label: 'Posición 3', value: '3' },
    { label: 'Posición 4', value: '4' },
    { label: 'Posición 5', value: '5' },
    { label: 'Posición 6', value: '6' },
];

/**
 * Mapeo de valores de ancho a clases Tailwind puras (para modo aislado o simulación directa).
 */
const WIDTH_CLASS_MAP = {
    '1/1': 'w-full flex-none',
    '1/2': 'w-1/2 flex-none',
    '1/3': 'w-1/3 flex-none',
    '2/3': 'w-2/3 flex-none',
    '1/4': 'w-1/4 flex-none',
    '2/4': 'w-1/2 flex-none',
    '3/4': 'w-3/4 flex-none',
    '1/5': 'w-1/5 flex-none',
    '2/5': 'w-2/5 flex-none',
    '3/5': 'w-3/5 flex-none',
    '4/5': 'w-4/5 flex-none',
    '1/6': 'w-1/6 flex-none',
    '5/6': 'w-5/6 flex-none',
    'expand': 'w-auto flex-1 min-w-0',
    'auto': 'w-auto flex-none',
    'sm': 'w-48 flex-none',
    'md': 'w-64 flex-none',
    'lg': 'w-80 flex-none',
    'xl': 'w-96 flex-none',
    '2xl': 'w-[28rem] flex-none',
};

/**
 * Mapeo de clases responsivas de ancho según el breakpoint.
 * Se incluyen explícitamente [bp]:flex-none o [bp]:flex-1 y [bp]:w-auto para evitar conflictos entre breakpoints.
 */
const RESPONSIVE_WIDTH_CLASS_MAP = {
    xs: {
        '1/1': 'w-full flex-none',
        '1/2': 'w-1/2 flex-none',
        '1/3': 'w-1/3 flex-none',
        '2/3': 'w-2/3 flex-none',
        '1/4': 'w-1/4 flex-none',
        '2/4': 'w-1/2 flex-none',
        '3/4': 'w-3/4 flex-none',
        '1/5': 'w-1/5 flex-none',
        '2/5': 'w-2/5 flex-none',
        '3/5': 'w-3/5 flex-none',
        '4/5': 'w-4/5 flex-none',
        '1/6': 'w-1/6 flex-none',
        '5/6': 'w-5/6 flex-none',
        'expand': 'w-auto flex-1 min-w-0',
        'auto': 'w-auto flex-none',
        'sm': 'w-48 flex-none',
        'md': 'w-64 flex-none',
        'lg': 'w-80 flex-none',
        'xl': 'w-96 flex-none',
        '2xl': 'w-[28rem] flex-none',
    },
    sm: {
        '1/1': 'sm:w-full sm:flex-none',
        '1/2': 'sm:w-1/2 sm:flex-none',
        '1/3': 'sm:w-1/3 sm:flex-none',
        '2/3': 'sm:w-2/3 sm:flex-none',
        '1/4': 'sm:w-1/4 sm:flex-none',
        '2/4': 'sm:w-1/2 sm:flex-none',
        '3/4': 'sm:w-3/4 sm:flex-none',
        '1/5': 'sm:w-1/5 sm:flex-none',
        '2/5': 'sm:w-2/5 sm:flex-none',
        '3/5': 'sm:w-3/5 sm:flex-none',
        '4/5': 'sm:w-4/5 sm:flex-none',
        '1/6': 'sm:w-1/6 sm:flex-none',
        '5/6': 'sm:w-5/6 sm:flex-none',
        'expand': 'sm:w-auto sm:flex-1 sm:min-w-0',
        'auto': 'sm:w-auto sm:flex-none',
        'sm': 'sm:w-48 sm:flex-none',
        'md': 'sm:w-64 sm:flex-none',
        'lg': 'sm:w-80 sm:flex-none',
        'xl': 'sm:w-96 sm:flex-none',
        '2xl': 'sm:w-[28rem] sm:flex-none',
    },
    md: {
        '1/1': 'md:w-full md:flex-none',
        '1/2': 'md:w-1/2 md:flex-none',
        '1/3': 'md:w-1/3 md:flex-none',
        '2/3': 'md:w-2/3 md:flex-none',
        '1/4': 'md:w-1/4 md:flex-none',
        '2/4': 'md:w-1/2 md:flex-none',
        '3/4': 'md:w-3/4 md:flex-none',
        '1/5': 'md:w-1/5 md:flex-none',
        '2/5': 'md:w-2/5 md:flex-none',
        '3/5': 'md:w-3/5 md:flex-none',
        '4/5': 'md:w-4/5 md:flex-none',
        '1/6': 'md:w-1/6 md:flex-none',
        '5/6': 'md:w-5/6 md:flex-none',
        'expand': 'md:w-auto md:flex-1 md:min-w-0',
        'auto': 'md:w-auto md:flex-none',
        'sm': 'md:w-48 md:flex-none',
        'md': 'md:w-64 md:flex-none',
        'lg': 'md:w-80 md:flex-none',
        'xl': 'md:w-96 md:flex-none',
        '2xl': 'md:w-[28rem] md:flex-none',
    },
    lg: {
        '1/1': 'lg:w-full lg:flex-none',
        '1/2': 'lg:w-1/2 lg:flex-none',
        '1/3': 'lg:w-1/3 lg:flex-none',
        '2/3': 'lg:w-2/3 lg:flex-none',
        '1/4': 'lg:w-1/4 lg:flex-none',
        '2/4': 'lg:w-1/2 lg:flex-none',
        '3/4': 'lg:w-3/4 lg:flex-none',
        '1/5': 'lg:w-1/5 lg:flex-none',
        '2/5': 'lg:w-2/5 lg:flex-none',
        '3/5': 'lg:w-3/5 lg:flex-none',
        '4/5': 'lg:w-4/5 lg:flex-none',
        '1/6': 'lg:w-1/6 lg:flex-none',
        '5/6': 'lg:w-5/6 lg:flex-none',
        'expand': 'lg:w-auto lg:flex-1 lg:min-w-0',
        'auto': 'lg:w-auto lg:flex-none',
        'sm': 'lg:w-48 lg:flex-none',
        'md': 'lg:w-64 lg:flex-none',
        'lg': 'lg:w-80 lg:flex-none',
        'xl': 'lg:w-96 lg:flex-none',
        '2xl': 'lg:w-[28rem] lg:flex-none',
    },
    xl: {
        '1/1': 'xl:w-full xl:flex-none',
        '1/2': 'xl:w-1/2 xl:flex-none',
        '1/3': 'xl:w-1/3 xl:flex-none',
        '2/3': 'xl:w-2/3 xl:flex-none',
        '1/4': 'xl:w-1/4 xl:flex-none',
        '2/4': 'xl:w-1/2 xl:flex-none',
        '3/4': 'xl:w-3/4 xl:flex-none',
        '1/5': 'xl:w-1/5 xl:flex-none',
        '2/5': 'xl:w-2/5 xl:flex-none',
        '3/5': 'xl:w-3/5 xl:flex-none',
        '4/5': 'xl:w-4/5 xl:flex-none',
        '1/6': 'xl:w-1/6 xl:flex-none',
        '5/6': 'xl:w-5/6 xl:flex-none',
        'expand': 'xl:w-auto xl:flex-1 xl:min-w-0',
        'auto': 'xl:w-auto xl:flex-none',
        'sm': 'xl:w-48 xl:flex-none',
        'md': 'xl:w-64 xl:flex-none',
        'lg': 'xl:w-80 xl:flex-none',
        'xl': 'xl:w-96 xl:flex-none',
        '2xl': 'xl:w-[28rem] xl:flex-none',
    },
};

/**
 * Normaliza y extrae la configuración de ancho de una columna de forma segura.
 */
function extraerConfigAncho(column, defaultDesktopFraction = '1/2') {
    const raw = column?.width || column?.settings?.width;
    if (typeof raw === 'object' && raw !== null) {
        return {
            xs: raw.xs && raw.xs !== 'inherit' ? raw.xs : '1/1',
            sm: raw.sm && raw.sm !== 'inherit' ? raw.sm : null,
            md: raw.md && raw.md !== 'inherit' ? raw.md : null,
            lg: raw.lg && raw.lg !== 'inherit' ? raw.lg : defaultDesktopFraction,
            xl: raw.xl && raw.xl !== 'inherit' ? raw.xl : null,
        };
    }
    if (typeof raw === 'string' && raw.trim()) {
        return {
            xs: '1/1',
            sm: null,
            md: null,
            lg: raw,
            xl: null,
        };
    }
    return {
        xs: '1/1',
        sm: null,
        md: null,
        lg: defaultDesktopFraction,
        xl: null,
    };
}

/**
 * Calcula las clases Tailwind completas de ancho y orden para una columna.
 *
 * @param {Object} column Objeto de la columna.
 * @param {number} colIndex Índice de la columna (0, 1, 2...).
 * @param {string} rowLayout ID del preset de layout de la fila padre (ej. '1/2+1/2', '2/3+1/3').
 * @param {string} vistaDispositivo 'desktop' | 'tablet' | 'mobile' (simulación en editor).
 * @returns {string} Clases CSS concatenadas.
 */
export function calculateColumnLayoutClasses(column, colIndex = 0, rowLayout = '1', vistaDispositivo = 'desktop') {
    const preset = GRID_PRESETS.find(p => p.id === rowLayout) || GRID_PRESETS[0];
    const defaultDesktopFraction = preset.fractions?.[colIndex] || '1/1';

    const widthConfig = extraerConfigAncho(column, defaultDesktopFraction);
    // Si el layout efectivo de la fila es '1' (columna única o columnas hermanas colapsadas), expandir al 100%
    if (rowLayout === '1') {
        widthConfig.xs = '1/1';
        widthConfig.sm = '1/1';
        widthConfig.md = '1/1';
        widthConfig.lg = '1/1';
        widthConfig.xl = '1/1';
    }
    const orderConfig = column?.order || column?.settings?.order || {};

    // 1. Simulación en Modo Móvil (< 640px)
    if (vistaDispositivo === 'mobile') {
        const val = widthConfig.xs || '1/1';
        const wClass = WIDTH_CLASS_MAP[val] || 'w-full flex-none';
        const oClass = getOrderClassDirect(orderConfig.xs || orderConfig.default);
        return [wClass, oClass].filter(Boolean).join(' ');
    }

    // 2. Simulación en Modo Tablet (640px - 1024px)
    if (vistaDispositivo === 'tablet') {
        let val = widthConfig.md || widthConfig.sm;
        if (!val) {
            val = preset.cols >= 3 ? '1/2' : defaultDesktopFraction;
        }
        const wClass = WIDTH_CLASS_MAP[val] || 'w-1/2 flex-none';
        const oClass = getOrderClassDirect(orderConfig.md || orderConfig.sm || orderConfig.default);
        return [wClass, oClass].filter(Boolean).join(' ');
    }

    // 3. Modo Escritorio y Renderizado SSR Web Pública Real (Media Queries Completas)
    const classes = [];

    // Breakpoint XS (Móvil vertical)
    classes.push(RESPONSIVE_WIDTH_CLASS_MAP.xs[widthConfig.xs] || 'w-full flex-none');
    if (orderConfig.xs && orderConfig.xs !== 'none') {
        classes.push(getOrderClassForBreakpoint('xs', orderConfig.xs));
    }

    // Breakpoint SM (Móvil horizontal)
    if (widthConfig.sm) {
        classes.push(RESPONSIVE_WIDTH_CLASS_MAP.sm[widthConfig.sm]);
    }
    if (orderConfig.sm && orderConfig.sm !== 'none') {
        classes.push(getOrderClassForBreakpoint('sm', orderConfig.sm));
    }

    // Breakpoint MD (Tablet)
    if (widthConfig.md) {
        classes.push(RESPONSIVE_WIDTH_CLASS_MAP.md[widthConfig.md]);
    } else {
        if (preset.cols >= 3 && widthConfig.xs === '1/1') {
            classes.push('md:w-1/2 md:flex-none');
        }
    }
    if (orderConfig.md && orderConfig.md !== 'none') {
        classes.push(getOrderClassForBreakpoint('md', orderConfig.md));
    }

    // Breakpoint LG (Escritorio)
    classes.push(RESPONSIVE_WIDTH_CLASS_MAP.lg[widthConfig.lg] || `lg:${WIDTH_CLASS_MAP[defaultDesktopFraction] || 'w-1/2 flex-none'}`);
    if (orderConfig.lg && orderConfig.lg !== 'none') {
        classes.push(getOrderClassForBreakpoint('lg', orderConfig.lg));
    }

    // Breakpoint XL (Pantallas grandes)
    if (widthConfig.xl) {
        classes.push(RESPONSIVE_WIDTH_CLASS_MAP.xl[widthConfig.xl]);
    }
    if (orderConfig.xl && orderConfig.xl !== 'none') {
        classes.push(getOrderClassForBreakpoint('xl', orderConfig.xl));
    }

    return classes.filter(Boolean).join(' ');
}

function getOrderClassDirect(order) {
    if (!order || order === 'none') return '';
    if (order === 'first') return 'order-first';
    if (order === 'last') return 'order-last';
    return `order-${order}`;
}

function getOrderClassForBreakpoint(bp, order) {
    if (!order || order === 'none') return '';
    const prefix = bp === 'xs' ? '' : `${bp}:`;
    if (order === 'first') return `${prefix}order-first`;
    if (order === 'last') return `${prefix}order-last`;
    return `${prefix}order-${order}`;
}
