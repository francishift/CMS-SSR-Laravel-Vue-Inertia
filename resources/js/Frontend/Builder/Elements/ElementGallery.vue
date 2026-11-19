<script setup>
/**
 * Elemento Galería de Imágenes / Vídeos para el Page Builder.
 *
 * Características:
 *  - Grid responsivo con columnas configurables por breakpoint (escritorio, tablet, móvil).
 *  - Layout Masonry con CSS columns (gap-free, sin JS, SSR compatible).
 *  - Integrado con ResponsiveImage (WebP, srcset, shimmer, cache 0ms).
 *  - Soporte de vídeo inline optimizado (lazy, IntersectionObserver, pausa auto).
 *  - Hover effects: zoom, brightness, blur, grayscale.
 *  - Overlays: hover_dark, hover_gradient, always_dark, always_gradient.
 *  - Captions: debajo de la imagen o superpuesto (overlay).
 *  - Filtro por tags con barra de navegación animada.
 *  - Lightbox propio moderno con navegación completa.
 *  - Soporte completo de personalización avanzada (ID, clases, CSS, atributos, animaciones).
 */
import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';
import GalleryLightbox from '@/Frontend/Components/GalleryLightbox.vue';
import { computed, inject, ref, onMounted, onUnmounted } from 'vue';
import { sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
    resolvedData: { type: [Array, Object], default: () => null },
});

const vistaDispositivo = inject('vistaDispositivo', ref(null));

// --- Animación: soporte cascada (por cada imagen) y bloque (todo a la vez) ---
const parentAnimationDelay = inject('parentAnimationDelay', ref(0));

const animationName = computed(() => {
    const a = props.content?.animation;
    return (a && a !== 'none') ? a : 'none';
});

const animationMode = computed(() => {
    return props.content?.animation_mode || 'cascade';
});

const ownDelay = computed(() => {
    const d = props.content?.animation_delay;
    return (d !== undefined && d !== null && !isNaN(Number(d))) ? Number(d) : 0;
});

const totalBaseDelay = computed(() => {
    if (animationName.value === 'none') return 0;
    return (parentAnimationDelay.value || 0) + ownDelay.value;
});

const staggerInterval = computed(() => {
    const s = props.content?.animation_stagger;
    if (s !== undefined && s !== null && !isNaN(Number(s)) && Number(s) >= 0) {
        return Number(s);
    }
    return 100;
});

// Atributos y estilos para el contenedor principal (SOLO si mode === 'block')
const blockAnimAttrs = computed(() => {
    if (animationName.value === 'none' || animationMode.value !== 'block') return {};
    return {
        'data-cms-animate': animationName.value,
        'data-cms-delay': String(totalBaseDelay.value),
    };
});

const blockAnimStyle = computed(() => {
    if (animationName.value === 'none' || animationMode.value !== 'block') return {};
    return { '--cms-anim-delay': `${totalBaseDelay.value}ms` };
});

// Atributos y estilos para CADA IMAGEN INDIVIDUAL (cuando mode === 'cascade')
const getItemAnimAttrs = (idx) => {
    if (animationName.value === 'none' || animationMode.value === 'block') return {};
    const itemDelay = totalBaseDelay.value + (idx * staggerInterval.value);
    return {
        'data-cms-animate': animationName.value,
        'data-cms-delay': String(itemDelay),
    };
};

const getItemAnimStyle = (idx) => {
    if (animationName.value === 'none' || animationMode.value === 'block') return {};
    const itemDelay = totalBaseDelay.value + (idx * staggerInterval.value);
    return { '--cms-anim-delay': `${itemDelay}ms` };
};

// --- Personalización avanzada ---
const customId = computed(() => props.content?.element_id || undefined);
const customClasses = computed(() => props.content?.custom_classes || '');
const customCss = computed(() => sanitizeInlineCss(props.content?.custom_css || ''));

const parsedCustomAttributes = computed(() => {
    const raw = props.content?.custom_attributes;
    if (!raw || typeof raw !== 'string' || !raw.trim()) return {};
    const attrs = {};
    const regex = /([a-zA-Z0-9_-]+)(?:=(?:"([^"]*)"|'([^']*)'|([^\s"']+)))?/g;
    let match;
    while ((match = regex.exec(raw)) !== null) {
        const key = match[1];
        const val = match[2] ?? match[3] ?? match[4] ?? '';
        attrs[key] = val;
    }
    return attrs;
});

// --- Items de la galería ---
const items = computed(() => {
    // Modo dinámico: usar resolved_data inyectado por el backend o la preview
    if (props.content?.mode === 'dynamic' && Array.isArray(props.resolvedData) && props.resolvedData.length > 0) {
        return props.resolvedData.filter(item => item.src);
    }
    // Modo manual: usar items del content
    const raw = props.content?.items;
    if (!Array.isArray(raw)) return [];
    return raw.filter(item => item.src);
});

// --- Tags únicos para filtro ---
const uniqueTags = computed(() => {
    if (!props.content?.filter_tags) return [];
    const tagSet = new Set();
    items.value.forEach(item => {
        if (item.tags && typeof item.tags === 'string') {
            item.tags.split(',').forEach(t => {
                const trimmed = t.trim();
                if (trimmed) tagSet.add(trimmed);
            });
        }
    });
    return Array.from(tagSet).sort();
});

// --- Estado del filtro activo ---
const activeFilter = ref('all');

const filteredItems = computed(() => {
    if (activeFilter.value === 'all' || !props.content?.filter_tags) {
        return items.value;
    }
    return items.value.filter(item => {
        if (!item.tags) return false;
        const itemTags = item.tags.split(',').map(t => t.trim());
        return itemTags.includes(activeFilter.value);
    });
});

// --- Layout ---
const layoutMode = computed(() => props.content?.layout || 'grid');

// --- Márgenes ---
const marginTopClass = computed(() => {
    const mt = props.content?.margin_top || 'none';
    return { none: 'mt-0', xs: 'mt-2', sm: 'mt-4', md: 'mt-6', lg: 'mt-8', xl: 'mt-12', '2xl': 'mt-16' }[mt] || 'mt-0';
});

const marginBottomClass = computed(() => {
    const mb = props.content?.margin_bottom || 'none';
    return { none: 'mb-0', xs: 'mb-2', sm: 'mb-4', md: 'mb-6', lg: 'mb-8', xl: 'mb-12', '2xl': 'mb-16' }[mb] || 'mb-0';
});

// --- Valores de Gap Responsivo ---
const gapDesk = computed(() => props.content?.gap || 'md');
const gapTab = computed(() => {
    const t = props.content?.gap_tablet;
    return (t && t !== 'auto') ? t : gapDesk.value;
});
const gapMob = computed(() => {
    const m = props.content?.gap_mobile;
    return (m && m !== 'auto') ? m : gapTab.value;
});

const tailwindGapClasses = {
    base: {
        none: 'gap-0', sm: 'gap-2', md: 'gap-4', lg: 'gap-6', xl: 'gap-8',
        '2xl': 'gap-10', '3xl': 'gap-12', '4xl': 'gap-14', '5xl': 'gap-16',
        '6xl': 'gap-[72px]', '7xl': 'gap-20', '8xl': 'gap-[90px]', '9xl': 'gap-[100px]',
        '40px': 'gap-10', '48px': 'gap-12', '56px': 'gap-14', '64px': 'gap-16',
        '72px': 'gap-[72px]', '80px': 'gap-20', '90px': 'gap-[90px]', '100px': 'gap-[100px]',
    },
    sm: {
        none: 'sm:gap-0', sm: 'sm:gap-2', md: 'sm:gap-4', lg: 'sm:gap-6', xl: 'sm:gap-8',
        '2xl': 'sm:gap-10', '3xl': 'sm:gap-12', '4xl': 'sm:gap-14', '5xl': 'sm:gap-16',
        '6xl': 'sm:gap-[72px]', '7xl': 'sm:gap-20', '8xl': 'sm:gap-[90px]', '9xl': 'sm:gap-[100px]',
        '40px': 'sm:gap-10', '48px': 'sm:gap-12', '56px': 'sm:gap-14', '64px': 'sm:gap-16',
        '72px': 'sm:gap-[72px]', '80px': 'sm:gap-20', '90px': 'sm:gap-[90px]', '100px': 'sm:gap-[100px]',
    },
    lg: {
        none: 'lg:gap-0', sm: 'lg:gap-2', md: 'lg:gap-4', lg: 'lg:gap-6', xl: 'lg:gap-8',
        '2xl': 'lg:gap-10', '3xl': 'lg:gap-12', '4xl': 'lg:gap-14', '5xl': 'lg:gap-16',
        '6xl': 'lg:gap-[72px]', '7xl': 'lg:gap-20', '8xl': 'lg:gap-[90px]', '9xl': 'lg:gap-[100px]',
        '40px': 'lg:gap-10', '48px': 'lg:gap-12', '56px': 'lg:gap-14', '64px': 'lg:gap-16',
        '72px': 'lg:gap-[72px]', '80px': 'lg:gap-20', '90px': 'lg:gap-[90px]', '100px': 'lg:gap-[100px]',
    },
};

const gapPxMap = {
    none: 0, sm: 8, md: 16, lg: 24, xl: 32,
    '2xl': 40, '3xl': 48, '4xl': 56, '5xl': 64,
    '6xl': 72, '7xl': 80, '8xl': 90, '9xl': 100,
    '40px': 40, '48px': 48, '56px': 56, '64px': 64,
    '72px': 72, '80px': 80, '90px': 90, '100px': 100,
};

function resolverGapPx(val) {
    if (typeof val === 'number') return val;
    if (typeof val === 'string' && val.endsWith('px')) {
        const parsed = parseInt(val, 10);
        if (!isNaN(parsed)) return parsed;
    }
    return gapPxMap[val] ?? 16;
}

const gapPxDesk = computed(() => resolverGapPx(gapDesk.value));
const gapPxTab = computed(() => resolverGapPx(gapTab.value));
const gapPxMob = computed(() => resolverGapPx(gapMob.value));

// gapPx activo (sensible a la vista de dispositivo en builder)
const gapPx = computed(() => {
    if (vistaDispositivo.value === 'mobile') return gapPxMob.value;
    if (vistaDispositivo.value === 'tablet') return gapPxTab.value;
    return gapPxDesk.value;
});

// Clases de Gap para Grid tradicional responsivo
const gapClass = computed(() => {
    if (vistaDispositivo.value === 'mobile') {
        return tailwindGapClasses.base[gapMob.value] || 'gap-4';
    }
    if (vistaDispositivo.value === 'tablet') {
        return tailwindGapClasses.base[gapTab.value] || 'gap-4';
    }
    const mob = tailwindGapClasses.base[gapMob.value] || 'gap-4';
    const tab = tailwindGapClasses.sm[gapTab.value] || 'sm:gap-4';
    const desk = tailwindGapClasses.lg[gapDesk.value] || 'lg:gap-4';
    return [mob, tab, desk].join(' ');
});

// --- Columnas Grid Responsivo ---
const gridColumnsClass = computed(() => {
    const colsDesk = Number(props.content?.columns_desktop || 3);
    const customTab = String(props.content?.columns_tablet || 'auto');
    const customMob = String(props.content?.columns_mobile || '1');

    const mobMap = { '1': 'grid-cols-1', '2': 'grid-cols-2', '3': 'grid-cols-3', '4': 'grid-cols-4', '5': 'grid-cols-5', '6': 'grid-cols-6' };
    const tabMap = { '1': 'grid-cols-1', '2': 'grid-cols-2', '3': 'grid-cols-3', '4': 'grid-cols-4', '5': 'grid-cols-5', '6': 'grid-cols-6' };
    const tabResponsiveMap = { '1': 'sm:grid-cols-1', '2': 'sm:grid-cols-2', '3': 'sm:grid-cols-3', '4': 'sm:grid-cols-4', '5': 'sm:grid-cols-5', '6': 'sm:grid-cols-6' };
    const deskMap = { 1: 'lg:grid-cols-1', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4', 5: 'lg:grid-cols-5', 6: 'lg:grid-cols-6' };

    // Vista de dispositivo en el builder
    if (vistaDispositivo.value === 'mobile') {
        return mobMap[customMob] || 'grid-cols-1';
    }
    if (vistaDispositivo.value === 'tablet') {
        return tabMap[customTab] || (colsDesk >= 3 ? 'grid-cols-2' : `grid-cols-${Math.min(colsDesk, 2)}`);
    }

    // Modo público responsive
    const mobClass = mobMap[customMob] || 'grid-cols-1';
    const tabClass = tabResponsiveMap[customTab] || (colsDesk >= 3 ? 'sm:grid-cols-2' : `sm:grid-cols-${Math.min(colsDesk, 2)}`);
    const deskClass = deskMap[colsDesk] || 'lg:grid-cols-3';

    return [mobClass, tabClass, deskClass].join(' ');
});

// --- Columnas Masonry (CSS columnas) ---
const masonryStyle = computed(() => {
    const colsDesk = Number(props.content?.columns_desktop || 3);
    let cols = colsDesk;
    let gap = gapPxDesk.value;

    if (vistaDispositivo.value === 'mobile') {
        const m = Number(props.content?.columns_mobile || 1);
        cols = m > 0 ? m : 1;
        gap = gapPxMob.value;
    } else if (vistaDispositivo.value === 'tablet') {
        const customTab = String(props.content?.columns_tablet || 'auto');
        cols = customTab !== 'auto' ? Number(customTab) : (colsDesk >= 3 ? 2 : colsDesk);
        gap = gapPxTab.value;
    }

    return {
        columnCount: cols,
        columnGap: `${gap}px`,
    };
});

// --- Opciones y estado para Carrusel Infinito ---
const carouselRows = computed(() => Number(props.content?.carousel_rows || 1));
const carouselSpeed = computed(() => Number(props.content?.carousel_speed || 60));
const carouselDirection = computed(() => props.content?.carousel_direction || 'left');
const carouselPauseOnHover = computed(() => props.content?.carousel_pause_on_hover !== false);
const carouselFadeEdges = computed(() => props.content?.carousel_fade_edges !== false);
const carouselCardStyle = computed(() => props.content?.carousel_card_style || 'plain');
const carouselItemSize = computed(() => props.content?.carousel_item_size || 'md');

const carruselContenedorRef = ref(null);
const carruselVisible = ref(true);
const carruselContainerPx = ref(null);
let carruselObserver = null;
let carruselResizeObserver = null;

onMounted(() => {
    if (typeof window !== 'undefined' && 'IntersectionObserver' in window) {
        carruselObserver = new IntersectionObserver(
            ([entry]) => {
                carruselVisible.value = entry.isIntersecting;
            },
            { rootMargin: '150px 0px 150px 0px' }
        );
        if (carruselContenedorRef.value) {
            carruselObserver.observe(carruselContenedorRef.value);
        }
    }

    if (typeof window !== 'undefined' && 'ResizeObserver' in window && carruselContenedorRef.value) {
        carruselResizeObserver = new ResizeObserver(([entry]) => {
            if (entry.contentRect && entry.contentRect.width > 0) {
                carruselContainerPx.value = Math.round(entry.contentRect.width);
            }
        });
        carruselResizeObserver.observe(carruselContenedorRef.value);
    }
});

onUnmounted(() => {
    if (carruselObserver) {
        carruselObserver.disconnect();
        carruselObserver = null;
    }
    if (carruselResizeObserver) {
        carruselResizeObserver.disconnect();
        carruselResizeObserver = null;
    }
});

// Clases de estilo para tarjetas de insignia
const carruselCardClass = computed(() => {
    if (carouselCardStyle.value !== 'badge') return '';
    return 'border border-zinc-200/80 dark:border-zinc-800/80 bg-white/70 dark:bg-zinc-900/70 shadow-2xs hover:border-zinc-300 dark:hover:border-zinc-700 hover:scale-105 transition-all duration-300 flex items-center justify-center p-3 sm:p-4';
});

const carruselItemContainerStyle = computed(() => {
    if (hasGlobalDimensions.value) {
        return globalImageContainerStyle.value;
    }
    return {};
});

// Duplicación matemática para bucle continuo del 50% sin saltos perceptibles
function prepararCarrilInfinito(listaBase) {
    if (!listaBase || listaBase.length === 0) return [];
    let base = [...listaBase];
    const minItems = Math.max(12, flexColsDesk.value * 2);
    while (base.length < minItems) {
        base = [...base, ...listaBase];
    }
    return [...base, ...base];
}

const carruselFila1Items = computed(() => {
    const list = filteredItems.value;
    if (list.length === 0) return [];
    if (carouselRows.value === 2 && list.length >= 6) {
        const fila = list.filter((_, i) => i % 2 === 0).map((it, idx) => ({ ...it, _origIdx: idx * 2 }));
        return prepararCarrilInfinito(fila);
    }
    const fila = list.map((it, idx) => ({ ...it, _origIdx: idx }));
    return prepararCarrilInfinito(fila);
});

const carruselFila2Items = computed(() => {
    if (carouselRows.value !== 2) return [];
    const list = filteredItems.value;
    if (list.length === 0) return [];
    if (list.length >= 6) {
        const fila = list.filter((_, i) => i % 2 === 1).map((it, idx) => ({ ...it, _origIdx: (idx * 2) + 1 }));
        return prepararCarrilInfinito(fila);
    }
    const mitad = Math.floor(list.length / 2);
    const fila = [...list.slice(mitad), ...list.slice(0, mitad)].map((it, k) => {
        const origIdx = (mitad + k) % list.length;
        return { ...it, _origIdx: origIdx };
    });
    return prepararCarrilInfinito(fila);
});

// --- Ratio de imagen ---
const imageAspectClass = computed(() => {
    const ratio = props.content?.image_ratio || 'auto';
    // Si se definen dimensiones globales para logos (contain con max_height/max_width),
    // no estrangular la anchura con aspect-video salvo que el usuario haya fijado explícitamente un ratio no por defecto
    if (hasGlobalDimensions.value && props.content?.image_fit === 'contain' && (ratio === '16:9' || ratio === 'auto')) {
        return '';
    }
    return {
        '16:9': 'aspect-video',
        '4:3': 'aspect-[4/3]',
        '3:2': 'aspect-[3/2]',
        '2:3': 'aspect-[2/3]',
        '1:1': 'aspect-square',
        'auto': '',
    }[ratio] || '';
});

// --- Ajuste de imagen (Opción A: transparente para PNGs y SVGs) ---
const imageFitClass = computed(() => {
    const fit = props.content?.image_fit || 'cover';
    return fit === 'contain' ? 'object-contain' : 'object-cover';
});

// --- Dimensiones globales de imágenes ---
const hasGlobalDimensions = computed(() => {
    const mh = props.content?.max_height_px || props.content?.max_height;
    const mw = props.content?.max_width_px || props.content?.max_width;
    return Boolean((mh && !isNaN(Number(mh))) || (mw && !isNaN(Number(mw))));
});

const globalImageContainerStyle = computed(() => {
    const styles = {};
    const mh = props.content?.max_height_px || props.content?.max_height;
    const mw = props.content?.max_width_px || props.content?.max_width;

    if (mh && !isNaN(Number(mh))) {
        styles.maxHeight = `${Number(mh)}px`;
    }
    if (mw && !isNaN(Number(mw))) {
        styles.maxWidth = `${Number(mw)}px`;
    }
    return styles;
});

const imgDimensionsClass = computed(() => {
    if (hasGlobalDimensions.value) {
        return props.content?.image_fit === 'cover' ? 'w-full h-full' : 'max-w-full max-h-full mx-auto block';
    }
    return centerColumns.value ? 'w-full h-full mx-auto block' : 'w-full h-full';
});

// --- Alineación y Distribución del Grid ---
const centerColumns = computed(() => Boolean(props.content?.center_columns));
const centerRows = computed(() => Boolean(props.content?.center_rows));
const showDividers = computed(() => Boolean(props.content?.show_dividers));

// Columnas numéricas para el cálculo de anchos en flex centrado
const flexColsDesk = computed(() => Number(props.content?.columns_desktop || 3));
const flexColsTab = computed(() => {
    const customTab = String(props.content?.columns_tablet || 'auto');
    if (customTab !== 'auto') return Number(customTab);
    return flexColsDesk.value >= 3 ? 2 : Math.min(flexColsDesk.value, 2);
});
const flexColsMob = computed(() => Number(props.content?.columns_mobile || 1));

const centeredGridContainerStyle = computed(() => {
    if (vistaDispositivo.value === 'mobile') {
        return {
            '--cols': flexColsMob.value,
            '--gap': `${gapPxMob.value}px`,
        };
    }
    if (vistaDispositivo.value === 'tablet') {
        return {
            '--cols': flexColsTab.value,
            '--gap': `${gapPxTab.value}px`,
        };
    }
    return {
        '--cols-mob': flexColsMob.value,
        '--cols-tab': flexColsTab.value,
        '--cols-desk': flexColsDesk.value,
        '--gap-mob': `${gapPxMob.value}px`,
        '--gap-tab': `${gapPxTab.value}px`,
        '--gap-desk': `${gapPxDesk.value}px`,
    };
});

const gridAlignmentClass = computed(() => {
    const classes = [];
    if (centerColumns.value) {
        classes.push('justify-center');
    }
    if (centerRows.value) {
        classes.push('items-center');
    }
    return classes.join(' ');
});

const figureAlignmentClass = computed(() => {
    const classes = [];
    if (centerColumns.value) {
        classes.push('text-center flex flex-col items-center justify-center justify-self-center mx-auto');
    }
    if (centerRows.value) {
        classes.push('self-center');
    }
    return classes.join(' ');
});

const dividerItemClass = computed(() => {
    if (!showDividers.value) return '';
    return 'border border-zinc-200/80 dark:border-zinc-800/80 p-4 sm:p-5 bg-white/40 dark:bg-zinc-900/40 transition-colors duration-200 hover:border-zinc-300 dark:hover:border-zinc-700';
});

// --- Filtros visuales (Blanco/Logo y Colorimetría con herencia y sobrescritura por ítem) ---
function getItemFilterClass(item) {
    // 1. Ajuste individual del ítem si está explícitamente configurado
    if (item?.is_white === true) {
        return 'brightness-0 invert';
    }
    if (item?.color_filter && item.color_filter !== 'inherit') {
        switch (item.color_filter) {
            case 'white':
                return 'brightness-0 invert';
            case 'dark_white':
                return 'dark:brightness-0 dark:invert';
            case 'black':
                return 'brightness-0';
            case 'grayscale':
                return 'grayscale';
            case 'grayscale_hover':
                return 'grayscale hover:grayscale-0 transition-all duration-300';
            case 'none':
                return ''; // Sobrescribe y anula cualquier filtro global
        }
    }

    // 2. Si el ítem no sobrescribe, heredar de la configuración global de la galería
    if (props.content?.is_white || props.content?.force_white) {
        return 'brightness-0 invert';
    }
    const globalFilter = props.content?.color_filter;
    if (globalFilter === 'white') return 'brightness-0 invert';
    if (globalFilter === 'dark_white') return 'dark:brightness-0 dark:invert';
    if (globalFilter === 'black') return 'brightness-0';
    if (globalFilter === 'grayscale') return 'grayscale';
    if (globalFilter === 'grayscale_hover') return 'grayscale hover:grayscale-0 transition-all duration-300';

    return '';
}

// --- Bordes redondeados ---
const roundedClass = computed(() => {
    const r = props.content?.rounded || 'md';
    return {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
        '2xl': 'rounded-2xl',
        full: 'rounded-full',
    }[r] || 'rounded-md';
});

// --- Hover effect ---
const hoverEffectClass = computed(() => {
    const effect = props.content?.hover_effect || 'zoom';
    return {
        none: '',
        zoom: 'group-hover/item:scale-105',
        brightness: 'group-hover/item:brightness-110',
        blur: 'group-hover/item:blur-[2px]',
        grayscale: 'grayscale group-hover/item:grayscale-0',
    }[effect] || '';
});

// --- Overlay ---
const overlayMode = computed(() => props.content?.overlay_mode || 'none');

const overlayClasses = computed(() => {
    const mode = overlayMode.value;
    if (mode === 'none') return '';
    const baseTransition = 'transition-all duration-300 ease-out';
    if (mode === 'hover_dark') return `${baseTransition} opacity-0 group-hover/item:opacity-100 bg-black/50`;
    if (mode === 'hover_gradient') return `${baseTransition} opacity-0 group-hover/item:opacity-100 bg-gradient-to-t from-black/70 via-black/20 to-transparent`;
    if (mode === 'always_dark') return `${baseTransition} bg-black/40`;
    if (mode === 'always_gradient') return `${baseTransition} bg-gradient-to-t from-black/60 via-black/15 to-transparent`;
    return '';
});

const showCaption = computed(() => Boolean(props.content?.show_caption));
const captionPosition = computed(() => props.content?.caption_position || 'below');

// Padding dinámico para Carrusel Infinito: espacio suficiente para los tooltips sin cortes
const carruselContenedorPaddingClass = computed(() => {
    if (!showCaption.value) return 'py-3';
    return carouselRows.value === 2 ? 'pt-20 pb-20' : 'pt-20 pb-4';
});

// --- Sizes para srcset responsivo ---
const responsiveSizes = computed(() => {
    const cols = Number(props.content?.columns_desktop || 3);
    if (cols <= 1) return '(max-width: 768px) 100vw, 800px';
    if (cols === 2) return '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 600px';
    if (cols === 3) return '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 400px';
    return '(max-width: 768px) 50vw, (max-width: 1024px) 33vw, 300px';
});

// --- Lightbox ---
const lightboxEnabled = computed(() => {
    return props.content?.lightbox !== false && props.content?.enable_lightbox !== false;
});
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

function openLightbox(idx) {
    if (!lightboxEnabled.value) return;
    if (!filteredItems.value || filteredItems.value.length === 0) return;
    const safeIdx = Math.max(0, Math.min(Number(idx) || 0, filteredItems.value.length - 1));
    lightboxIndex.value = safeIdx;
    lightboxOpen.value = true;
}

function closeLightbox() {
    lightboxOpen.value = false;
}

// --- Detección de tipo de item (imagen o vídeo) ---
function isVideoItem(item) {
    if (item.type === 'video') return true;
    if (!item.src) return false;
    const ext = item.src.split('?')[0].split('.').pop()?.toLowerCase();
    return ['mp4', 'webm', 'mov'].includes(ext);
}

// --- Normalización de URL de vídeo ---
function normalizeVideoSrc(src) {
    if (!src) return '';
    let normalized = src;
    if (!normalized.startsWith('http://') && !normalized.startsWith('https://') && !normalized.startsWith('/')) {
        if (normalized.startsWith('storage/') || normalized.startsWith('media/')) {
            normalized = '/' + normalized;
        }
    }
    // Añadir fragmento para primer fotograma si no lo tiene
    if (!normalized.includes('#t=')) {
        return `${normalized}#t=0.001`;
    }
    return normalized;
}
</script>

<template>
    <div
        v-if="items.length"
        :id="customId"
        :class="['w-full', marginTopClass, marginBottomClass, customClasses]"
        :style="blockAnimStyle"
        v-bind="{ ...blockAnimAttrs, ...parsedCustomAttributes }"
    >
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <!-- Barra de Filtro por Tags -->
        <nav
            v-if="props.content?.filter_tags && uniqueTags.length > 0"
            class="flex flex-wrap items-center gap-2 mb-6"
            role="navigation"
            aria-label="Filtrar galería por etiquetas"
        >
            <button
                type="button"
                @click="activeFilter = 'all'"
                :class="[
                    'px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer border',
                    activeFilter === 'all'
                        ? 'bg-[var(--primary-color,#10b981)] text-white border-[var(--primary-color,#10b981)] shadow-md shadow-[var(--primary-color,#10b981)]/20'
                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:border-[var(--primary-color,#10b981)]/50 hover:text-[var(--primary-color,#10b981)]'
                ]"
            >
                Todas
            </button>
            <button
                v-for="tag in uniqueTags"
                :key="tag"
                type="button"
                @click="activeFilter = tag"
                :class="[
                    'px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer border',
                    activeFilter === tag
                        ? 'bg-[var(--primary-color,#10b981)] text-white border-[var(--primary-color,#10b981)] shadow-md shadow-[var(--primary-color,#10b981)]/20'
                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:border-[var(--primary-color,#10b981)]/50 hover:text-[var(--primary-color,#10b981)]'
                ]"
            >
                {{ tag }}
            </button>
        </nav>

        <!-- Layout GRID -->
        <div
            v-if="layoutMode === 'grid'"
            :class="[
                centerColumns
                    ? 'gallery-centered-grid flex flex-wrap justify-center w-full'
                    : ['grid w-full', gridColumnsClass],
                gapClass,
                gridAlignmentClass
            ]"
            :style="centerColumns ? centeredGridContainerStyle : {}"
            role="group"
            aria-label="Galería de imágenes"
        >
            <figure
                v-for="(item, idx) in filteredItems"
                :key="item.id || item.src || idx"
                class="group/item relative overflow-hidden m-0"
                :class="[
                    roundedClass,
                    lightboxEnabled ? 'cursor-pointer' : '',
                    figureAlignmentClass,
                    dividerItemClass,
                    centerColumns ? 'gallery-centered-item' : 'w-full'
                ]"
                :style="getItemAnimStyle(idx)"
                v-bind="getItemAnimAttrs(idx)"
                @click="openLightbox(idx)"
            >
                <!-- Imagen -->
                <div
                    v-if="!isVideoItem(item)"
                    :class="[
                        'overflow-hidden relative mx-auto w-full flex items-center justify-center',
                        imageAspectClass,
                        roundedClass
                    ]"
                    :style="globalImageContainerStyle"
                >
                    <ResponsiveImage
                        :src="item.src"
                        :alt="item.alt || item.title || 'Imagen de galería'"
                        :width="600"
                        :height="imageAspectClass ? 338 : undefined"
                        :fit="props.content?.image_fit || 'cover'"
                        :quality="85"
                        :widths="[320, 480, 640]"
                        :sizes="responsiveSizes"
                        :imgClass="[imgDimensionsClass, imageFitClass, hoverEffectClass, getItemFilterClass(item), 'mx-auto block']"
                        containerClass="w-full h-full flex items-center justify-center mx-auto"
                        :style="globalImageContainerStyle"
                    />

                    <!-- Overlay -->
                    <div
                        v-if="overlayMode !== 'none'"
                        :class="['absolute inset-0 z-10 pointer-events-none', overlayClasses, roundedClass]"
                    />

                    <!-- Icono de lupa en hover (solo si lightbox activo) -->
                    <div
                        v-if="lightboxEnabled"
                        class="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition-opacity duration-300 pointer-events-none"
                    >
                        <div class="w-10 h-10 rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-sm flex items-center justify-center shadow-lg transform scale-75 group-hover/item:scale-100 transition-transform duration-300">
                            <i class="pi pi-search-plus text-sm text-zinc-700 dark:text-zinc-200" />
                        </div>
                    </div>

                    <!-- Caption en overlay -->
                    <div
                        v-if="showCaption && captionPosition === 'overlay' && (item.title || item.caption)"
                        class="absolute bottom-0 left-0 right-0 z-20 p-3 bg-gradient-to-t from-black/70 to-transparent"
                    >
                        <p v-if="item.title" class="text-white text-sm font-semibold leading-tight">{{ item.title }}</p>
                        <p v-if="item.caption" class="text-white/80 text-xs mt-0.5 leading-snug">{{ item.caption }}</p>
                    </div>
                </div>

                <!-- Vídeo inline -->
                <div
                    v-else
                    :class="[
                        'overflow-hidden relative mx-auto w-full flex items-center justify-center',
                        imageAspectClass || 'aspect-video',
                        roundedClass
                    ]"
                    :style="globalImageContainerStyle"
                >
                    <video
                        :src="normalizeVideoSrc(item.src)"
                        :poster="item.poster || undefined"
                        :muted="true"
                        :loop="true"
                        playsinline
                        preload="metadata"
                        :class="['w-full h-full mx-auto block', imageFitClass, hoverEffectClass, getItemFilterClass(item)]"
                        :style="[
                            { transition: 'transform 0.8s cubic-bezier(0.25, 0.1, 0.25, 1), scale 0.8s cubic-bezier(0.25, 0.1, 0.25, 1)' },
                            globalImageContainerStyle
                        ]"
                        @mouseenter="$event.target.play()"
                        @mouseleave="$event.target.pause()"
                    />

                    <!-- Icono de vídeo -->
                    <div class="absolute top-3 right-3 z-20 w-7 h-7 rounded-full bg-black/60 backdrop-blur-sm flex items-center justify-center pointer-events-none">
                        <i class="pi pi-play-circle text-white text-xs" />
                    </div>

                    <!-- Overlay sobre vídeo -->
                    <div
                        v-if="overlayMode !== 'none'"
                        :class="['absolute inset-0 z-10 pointer-events-none', overlayClasses, roundedClass]"
                    />
                </div>

                <!-- Caption debajo de la imagen/vídeo -->
                <figcaption
                    v-if="showCaption && captionPosition === 'below' && (item.title || item.caption)"
                    :class="['mt-2 px-0.5 w-full', centerColumns ? 'text-center' : '']"
                >
                    <p v-if="item.title" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-tight">{{ item.title }}</p>
                    <p v-if="item.caption" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-snug">{{ item.caption }}</p>
                </figcaption>
            </figure>
        </div>

        <!-- Layout MASONRY (CSS columns) -->
        <div
            v-else-if="layoutMode === 'masonry'"
            :style="masonryStyle"
            class="w-full"
            role="group"
            aria-label="Galería de imágenes en mosaico"
        >
            <figure
                v-for="(item, idx) in filteredItems"
                :key="item.id || item.src || idx"
                class="group/item relative overflow-hidden m-0 break-inside-avoid w-full"
                :class="[
                    roundedClass,
                    lightboxEnabled ? 'cursor-pointer' : '',
                    figureAlignmentClass,
                    dividerItemClass
                ]"
                :style="[{ marginBottom: `${gapPx}px` }, getItemAnimStyle(idx)]"
                v-bind="getItemAnimAttrs(idx)"
                @click="openLightbox(idx)"
            >
                <!-- Imagen Masonry -->
                <div
                    v-if="!isVideoItem(item)"
                    :class="[
                        'overflow-hidden relative mx-auto w-full flex items-center justify-center',
                        roundedClass
                    ]"
                    :style="globalImageContainerStyle"
                >
                    <ResponsiveImage
                        :src="item.src"
                        :alt="item.alt || item.title || 'Imagen de galería'"
                        :width="600"
                        :fit="props.content?.image_fit || 'cover'"
                        :quality="85"
                        :widths="[320, 480, 640]"
                        :sizes="responsiveSizes"
                        :imgClass="[imgDimensionsClass, imageFitClass, hoverEffectClass, getItemFilterClass(item), 'mx-auto block']"
                        containerClass="w-full flex items-center justify-center mx-auto"
                        :style="globalImageContainerStyle"
                    />

                    <!-- Overlay -->
                    <div
                        v-if="overlayMode !== 'none'"
                        :class="['absolute inset-0 z-10 pointer-events-none', overlayClasses, roundedClass]"
                    />

                    <!-- Icono de lupa en hover -->
                    <div
                        v-if="lightboxEnabled"
                        class="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition-opacity duration-300 pointer-events-none"
                    >
                        <div class="w-10 h-10 rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-sm flex items-center justify-center shadow-lg transform scale-75 group-hover/item:scale-100 transition-transform duration-300">
                            <i class="pi pi-search-plus text-sm text-zinc-700 dark:text-zinc-200" />
                        </div>
                    </div>

                    <!-- Caption en overlay (masonry) -->
                    <div
                        v-if="showCaption && captionPosition === 'overlay' && (item.title || item.caption)"
                        class="absolute bottom-0 left-0 right-0 z-20 p-3 bg-gradient-to-t from-black/70 to-transparent"
                    >
                        <p v-if="item.title" class="text-white text-sm font-semibold leading-tight">{{ item.title }}</p>
                        <p v-if="item.caption" class="text-white/80 text-xs mt-0.5 leading-snug">{{ item.caption }}</p>
                    </div>
                </div>

                <!-- Vídeo Masonry -->
                <div
                    v-else
                    :class="[
                        'overflow-hidden relative',
                        roundedClass,
                        (hasGlobalDimensions || centerColumns || centerRows) ? 'flex items-center justify-center w-full' : ''
                    ]"
                    :style="globalImageContainerStyle"
                >
                    <video
                        :src="normalizeVideoSrc(item.src)"
                        :poster="item.poster || undefined"
                        :muted="true"
                        :loop="true"
                        playsinline
                        preload="metadata"
                        :class="['w-full h-auto', imageFitClass, hoverEffectClass, getItemFilterClass(item)]"
                        :style="[
                            { transition: 'transform 0.8s cubic-bezier(0.25, 0.1, 0.25, 1), scale 0.8s cubic-bezier(0.25, 0.1, 0.25, 1)' },
                            globalImageContainerStyle
                        ]"
                        @mouseenter="$event.target.play()"
                        @mouseleave="$event.target.pause()"
                    />
                    <div class="absolute top-3 right-3 z-20 w-7 h-7 rounded-full bg-black/60 backdrop-blur-sm flex items-center justify-center pointer-events-none">
                        <i class="pi pi-play-circle text-white text-xs" />
                    </div>
                </div>

                <!-- Caption debajo (masonry) -->
                <figcaption
                    v-if="showCaption && captionPosition === 'below' && (item.title || item.caption)"
                    class="mt-2 px-0.5"
                >
                    <p v-if="item.title" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-tight">{{ item.title }}</p>
                    <p v-if="item.caption" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-snug">{{ item.caption }}</p>
                </figcaption>
            </figure>
        </div>

        <!-- Layout CARRUSEL INFINITO (Continuo) -->
        <div
            v-else-if="layoutMode === 'carousel_infinite'"
            ref="carruselContenedorRef"
            :class="[
                'carrusel-infinito-contenedor relative w-full select-none',
                carruselContenedorPaddingClass,
                carouselFadeEdges ? 'carrusel-infinito-mascara' : '',
                carouselPauseOnHover ? 'carrusel-pausar-hover' : ''
            ]"
            :style="{
                '--carrusel-duracion': `${carouselSpeed}s`,
                '--carrusel-gap-desk': `${gapPxDesk}px`,
                '--carrusel-gap-tab': `${gapPxTab}px`,
                '--carrusel-gap-mob': `${gapPxMob}px`,
                '--carrusel-gap': vistaDispositivo ? `${gapPx}px` : undefined,
                '--carrusel-cols-desk': flexColsDesk,
                '--carrusel-cols-tab': flexColsTab,
                '--carrusel-cols-mob': flexColsMob,
                '--carrusel-cols': vistaDispositivo
                    ? (vistaDispositivo === 'mobile' ? flexColsMob : (vistaDispositivo === 'tablet' ? flexColsTab : flexColsDesk))
                    : undefined,
                '--carrusel-container-px': carruselContainerPx ? `${carruselContainerPx}px` : undefined,
            }"
            role="region"
            aria-label="Carrusel continuo de imágenes"
        >
            <!-- Fila 1 -->
            <div
                :class="[
                    'carrusel-infinito-fila flex w-max flex-row items-center',
                    carouselDirection === 'right' ? 'carrusel-anim-der' : 'carrusel-anim-izq',
                    !carruselVisible ? 'carrusel-pausado' : ''
                ]"
            >
                <figure
                    v-for="(item, idx) in carruselFila1Items"
                    :key="`c1_${item.id || item.src}_${idx}`"
                    class="group/item relative overflow-visible m-0 shrink-0 flex items-center justify-center hover:z-30 min-w-0"
                    :class="[
                        roundedClass,
                        lightboxEnabled ? 'cursor-pointer' : '',
                        carouselCardStyle === 'badge' ? carruselCardClass : ''
                    ]"
                    :style="carruselItemContainerStyle"
                    @click="openLightbox(item._origIdx ?? (idx % filteredItems.length))"
                >
                    <!-- Tooltip flotante en hover Fila 1 (hacia arriba) -->
                    <div
                        v-if="showCaption && (item.title || item.caption)"
                        class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 z-40 opacity-0 scale-95 group-hover/item:opacity-100 group-hover/item:scale-100 transition-all duration-200 ease-out min-w-max max-w-[240px] text-center"
                    >
                        <div class="px-3 py-1.5 rounded-lg bg-zinc-900/95 dark:bg-zinc-800/95 text-white shadow-xl border border-zinc-700/60 dark:border-zinc-600/60 backdrop-blur-md">
                            <p v-if="item.title" class="text-xs font-bold leading-tight whitespace-nowrap">{{ item.title }}</p>
                            <p v-if="item.caption" class="text-[10px] text-zinc-300 dark:text-zinc-400 leading-snug mt-0.5 line-clamp-2 max-w-[200px]">{{ item.caption }}</p>
                        </div>
                        <div class="w-0 h-0 border-x-4 border-x-transparent border-t-4 border-t-zinc-900/95 dark:border-t-zinc-800/95 mx-auto" />
                    </div>

                    <!-- Imagen Fila 1 -->
                    <div
                        v-if="!isVideoItem(item)"
                        :class="[
                            'overflow-hidden relative flex items-center justify-center w-full',
                            imageAspectClass || (hasGlobalDimensions ? '' : 'aspect-video'),
                            roundedClass
                        ]"
                        :style="globalImageContainerStyle"
                    >
                        <ResponsiveImage
                            :src="item.src"
                            :alt="item.alt || item.title || 'Imagen de galería'"
                            :width="600"
                            :height="imageAspectClass ? 338 : undefined"
                            :fit="props.content?.image_fit || (carouselCardStyle === 'badge' ? 'contain' : 'cover')"
                            :quality="85"
                            :widths="[320, 480, 640, 960]"
                            :sizes="responsiveSizes"
                            loading="eager"
                            :shimmer="carouselCardStyle === 'badge' || props.content?.image_fit === 'cover'"
                            :imgClass="[
                                props.content?.image_fit === 'cover' ? 'w-full h-full object-cover' : 'max-w-full max-h-full object-contain mx-auto block',
                                hoverEffectClass,
                                getItemFilterClass(item)
                            ]"
                            containerClass="w-full h-full flex items-center justify-center"
                        />

                        <!-- Overlay -->
                        <div
                            v-if="overlayMode !== 'none'"
                            :class="['absolute inset-0 z-10 pointer-events-none', overlayClasses, roundedClass]"
                        />

                        <!-- Icono de lupa en hover -->
                        <div
                            v-if="lightboxEnabled && carouselCardStyle !== 'badge'"
                            class="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition-opacity duration-300 pointer-events-none"
                        >
                            <div class="w-9 h-9 rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-sm flex items-center justify-center shadow-lg transform scale-75 group-hover/item:scale-100 transition-transform duration-300">
                                <i class="pi pi-search-plus text-xs text-zinc-700 dark:text-zinc-200" />
                            </div>
                        </div>
                    </div>

                    <!-- Vídeo Fila 1 -->
                    <div
                        v-else
                        :class="[
                            'overflow-hidden relative flex items-center justify-center w-full',
                            imageAspectClass || (hasGlobalDimensions ? '' : 'aspect-video'),
                            roundedClass
                        ]"
                        :style="globalImageContainerStyle"
                    >
                        <video
                            :src="normalizeVideoSrc(item.src)"
                            :poster="item.poster || undefined"
                            :muted="true"
                            :loop="true"
                            playsinline
                            preload="metadata"
                            :class="['w-full h-full object-cover', hoverEffectClass, getItemFilterClass(item)]"
                            @mouseenter="$event.target.play()"
                            @mouseleave="$event.target.pause()"
                        />
                        <div class="absolute top-2 right-2 z-20 w-6 h-6 rounded-full bg-black/60 backdrop-blur-sm flex items-center justify-center pointer-events-none">
                            <i class="pi pi-play-circle text-white text-[10px]" />
                        </div>
                    </div>
                </figure>
            </div>

            <!-- Fila 2 (opcional, dirección contraria) -->
            <div
                v-if="carouselRows === 2"
                :class="[
                    'carrusel-infinito-fila flex w-max flex-row items-center',
                    showCaption ? 'mt-8 sm:mt-10' : 'mt-3 sm:mt-4',
                    carouselDirection === 'right' ? 'carrusel-anim-izq' : 'carrusel-anim-der',
                    !carruselVisible ? 'carrusel-pausado' : ''
                ]"
            >
                <figure
                    v-for="(item, idx) in carruselFila2Items"
                    :key="`c2_${item.id || item.src}_${idx}`"
                    class="group/item relative overflow-visible m-0 shrink-0 flex items-center justify-center hover:z-30 min-w-0"
                    :class="[
                        roundedClass,
                        lightboxEnabled ? 'cursor-pointer' : '',
                        carouselCardStyle === 'badge' ? carruselCardClass : ''
                    ]"
                    :style="carruselItemContainerStyle"
                    @click="openLightbox(item._origIdx ?? (idx % filteredItems.length))"
                >
                    <!-- Tooltip flotante en hover Fila 2 (hacia abajo para no tapar Fila 1) -->
                    <div
                        v-if="showCaption && (item.title || item.caption)"
                        class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-1.5 z-40 opacity-0 scale-95 group-hover/item:opacity-100 group-hover/item:scale-100 transition-all duration-200 ease-out min-w-max max-w-[240px] text-center"
                    >
                        <div class="w-0 h-0 border-x-4 border-x-transparent border-b-4 border-b-zinc-900/95 dark:border-b-zinc-800/95 mx-auto" />
                        <div class="px-3 py-1.5 rounded-lg bg-zinc-900/95 dark:bg-zinc-800/95 text-white shadow-xl border border-zinc-700/60 dark:border-zinc-600/60 backdrop-blur-md">
                            <p v-if="item.title" class="text-xs font-bold leading-tight whitespace-nowrap">{{ item.title }}</p>
                            <p v-if="item.caption" class="text-[10px] text-zinc-300 dark:text-zinc-400 leading-snug mt-0.5 line-clamp-2 max-w-[200px]">{{ item.caption }}</p>
                        </div>
                    </div>

                    <!-- Imagen Fila 2 -->
                    <div
                        v-if="!isVideoItem(item)"
                        :class="[
                            'overflow-hidden relative flex items-center justify-center w-full',
                            imageAspectClass || (hasGlobalDimensions ? '' : 'aspect-video'),
                            roundedClass
                        ]"
                        :style="globalImageContainerStyle"
                    >
                        <ResponsiveImage
                            :src="item.src"
                            :alt="item.alt || item.title || 'Imagen de galería'"
                            :width="600"
                            :height="imageAspectClass ? 338 : undefined"
                            :fit="props.content?.image_fit || (carouselCardStyle === 'badge' ? 'contain' : 'cover')"
                            :quality="85"
                            :widths="[320, 480, 640, 960]"
                            :sizes="responsiveSizes"
                            loading="eager"
                            :shimmer="carouselCardStyle === 'badge' || props.content?.image_fit === 'cover'"
                            :imgClass="[
                                props.content?.image_fit === 'cover' ? 'w-full h-full object-cover' : 'max-w-full max-h-full object-contain mx-auto block',
                                hoverEffectClass,
                                getItemFilterClass(item)
                            ]"
                            containerClass="w-full h-full flex items-center justify-center"
                        />

                        <!-- Overlay -->
                        <div
                            v-if="overlayMode !== 'none'"
                            :class="['absolute inset-0 z-10 pointer-events-none', overlayClasses, roundedClass]"
                        />

                        <!-- Icono de lupa en hover -->
                        <div
                            v-if="lightboxEnabled && carouselCardStyle !== 'badge'"
                            class="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition-opacity duration-300 pointer-events-none"
                        >
                            <div class="w-9 h-9 rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-sm flex items-center justify-center shadow-lg transform scale-75 group-hover/item:scale-100 transition-transform duration-300">
                                <i class="pi pi-search-plus text-xs text-zinc-700 dark:text-zinc-200" />
                            </div>
                        </div>
                    </div>

                    <!-- Vídeo Fila 2 -->
                    <div
                        v-else
                        :class="[
                            'overflow-hidden relative flex items-center justify-center w-full',
                            imageAspectClass || (hasGlobalDimensions ? '' : 'aspect-video'),
                            roundedClass
                        ]"
                        :style="globalImageContainerStyle"
                    >
                        <video
                            :src="normalizeVideoSrc(item.src)"
                            :poster="item.poster || undefined"
                            :muted="true"
                            :loop="true"
                            playsinline
                            preload="metadata"
                            :class="['w-full h-full object-cover', hoverEffectClass, getItemFilterClass(item)]"
                            @mouseenter="$event.target.play()"
                            @mouseleave="$event.target.pause()"
                        />
                        <div class="absolute top-2 right-2 z-20 w-6 h-6 rounded-full bg-black/60 backdrop-blur-sm flex items-center justify-center pointer-events-none">
                            <i class="pi pi-play-circle text-white text-[10px]" />
                        </div>
                    </div>
                </figure>
            </div>
        </div>

        <!-- Lightbox -->
        <GalleryLightbox
            v-if="lightboxEnabled && lightboxOpen"
            :items="filteredItems"
            :startIndex="lightboxIndex"
            :galleryContent="content"
            @close="closeLightbox"
        />

        <!-- Estado Vacío en el Builder -->
        <div v-if="!items.length" class="py-12 px-4 text-center bg-zinc-50 dark:bg-zinc-900/40 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 space-y-2">
            <i class="pi pi-images text-2xl text-zinc-400 mb-1" />
            <h4 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Galería sin imágenes</h4>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto">
                Añade imágenes o vídeos desde la pestaña de opciones del elemento para crear tu galería.
            </p>
        </div>
    </div>
</template>

<style scoped>
.gallery-centered-grid {
    --cols: var(--cols-mob, 1);
    --gap: var(--gap-mob, 16px);
}

@media (min-width: 640px) {
    .gallery-centered-grid {
        --cols: var(--cols-tab, 2);
        --gap: var(--gap-tab, 16px);
    }
}

@media (min-width: 1024px) {
    .gallery-centered-grid {
        --cols: var(--cols-desk, 3);
        --gap: var(--gap-desk, 16px);
    }
}

.gallery-centered-item {
    flex: 0 0 calc((100% - (var(--cols) - 1) * var(--gap, 16px)) / var(--cols));
    width: calc((100% - (var(--cols) - 1) * var(--gap, 16px)) / var(--cols));
    max-width: calc((100% - (var(--cols) - 1) * var(--gap, 16px)) / var(--cols));
    box-sizing: border-box;
}

/* --- Estilos y Animaciones de Carrusel Infinito --- */
.carrusel-infinito-contenedor {
    position: relative;
    width: 100%;
    overflow: hidden;
    overflow-x: clip;
    overflow-y: visible;
    container-type: inline-size;
    --carrusel-gap: var(--carrusel-gap-desk, 16px);
    --carrusel-cols: var(--carrusel-cols-desk, 4);
}

@media (max-width: 1023px) {
    .carrusel-infinito-contenedor {
        --carrusel-gap: var(--carrusel-gap-tab, var(--carrusel-gap-desk, 16px));
        --carrusel-cols: var(--carrusel-cols-tab, 3);
    }
}

@media (max-width: 639px) {
    .carrusel-infinito-contenedor {
        --carrusel-gap: var(--carrusel-gap-mob, var(--carrusel-gap-tab, var(--carrusel-gap-desk, 16px)));
        --carrusel-cols: var(--carrusel-cols-mob, 2);
    }
}

.carrusel-infinito-mascara {
    mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
    -webkit-mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
}

.carrusel-infinito-fila {
    display: flex;
    width: max-content;
    flex-wrap: nowrap;
    will-change: transform;
    align-items: center;
    gap: var(--carrusel-gap, 16px);
}

/* Ancho por columna en el carrusel continuo: soporta cualquier escala libremente (gigantes o compactas) */
.carrusel-infinito-fila figure {
    width: calc((var(--carrusel-container-px, 100cqw) - (var(--carrusel-cols) - 1) * var(--carrusel-gap)) / var(--carrusel-cols));
    flex: 0 0 calc((var(--carrusel-container-px, 100cqw) - (var(--carrusel-cols) - 1) * var(--carrusel-gap)) / var(--carrusel-cols));
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.carrusel-infinito-fila img,
.carrusel-infinito-fila svg,
.carrusel-infinito-fila video {
    display: block;
}

.carrusel-anim-izq {
    animation: galeria-carrusel-izq var(--carrusel-duracion, 60s) linear infinite;
}

.carrusel-anim-der {
    animation: galeria-carrusel-der var(--carrusel-duracion, 60s) linear infinite;
}

.carrusel-pausado {
    animation-play-state: paused !important;
}

.carrusel-pausar-hover:hover .carrusel-infinito-fila {
    animation-play-state: paused;
}

@keyframes galeria-carrusel-izq {
    0% {
        transform: translate3d(0, 0, 0);
    }
    100% {
        transform: translate3d(-50%, 0, 0);
    }
}

@keyframes galeria-carrusel-der {
    0% {
        transform: translate3d(-50%, 0, 0);
    }
    100% {
        transform: translate3d(0, 0, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .carrusel-infinito-fila {
        animation-play-state: paused !important;
    }
}
</style>
