<script setup>
/**
 * Elemento de Cuadrícula Modular (Grid) para el Page Builder.
 * Soporta Modo Dinámico (Artículos del CMS por categoría o recientes),
 * Modo Manual (Tarjetas personalizadas creadas libremente por el usuario) y Modo Mixto.
 * Incluye opción de imagen a sangre superior sin relleno (flush/full bleed) o con relleno interno (boxed),
 * presets visuales, proporciones de imagen, gap dinámico y mapeo de campos estándar y extra.
 */
import { Link, router } from '@inertiajs/vue3';
import { computed, inject, ref, onMounted, onUnmounted, useId } from 'vue';
import { useSanitize } from '@/Composables/useSanitize';
import { useListAutoExpire } from '@/Composables/useAutoExpire';
import { useElementAnimation } from '@/Composables/useElementAnimation';
import { esNavegacionInertia, getRutaInterna } from '@/Composables/useUrlHelpers';
import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';
import DownloadCard from '@/Frontend/Components/DownloadCard.vue';

const { sanitize } = useSanitize();

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    resolvedData: { type: Array, default: () => [] },
});

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
const gridBlockAnimAttrs = computed(() => {
    if (animationName.value === 'none' || animationMode.value !== 'block') return {};
    return {
        'data-cms-animate': animationName.value,
        'data-cms-delay': String(totalBaseDelay.value),
    };
});

const gridBlockAnimStyle = computed(() => {
    if (animationName.value === 'none' || animationMode.value !== 'block') return {};
    return { '--cms-anim-delay': `${totalBaseDelay.value}ms` };
});

// Atributos y estilos para el encabezado del grid (si existe)
const gridHeaderAnimAttrs = computed(() => {
    if (animationName.value === 'none') return {};
    return {
        'data-cms-animate': animationName.value,
        'data-cms-delay': String(totalBaseDelay.value),
    };
});

const gridHeaderAnimStyle = computed(() => {
    if (animationName.value === 'none') return {};
    return { '--cms-anim-delay': `${totalBaseDelay.value}ms` };
});

// Atributos y estilos para CADA TARJETA INDIVIDUAL (cuando mode === 'cascade')
const getItemAnimAttrs = (idx) => {
    if (animationName.value === 'none' || animationMode.value === 'block') return {};
    const headerOffset = props.content?.grid_header_title ? staggerInterval.value : 0;
    const cardDelay = totalBaseDelay.value + headerOffset + (idx * staggerInterval.value);
    return {
        'data-cms-animate': animationName.value,
        'data-cms-delay': String(cardDelay),
    };
};

const getItemAnimStyle = (idx) => {
    if (animationName.value === 'none' || animationMode.value === 'block') return {};
    const headerOffset = props.content?.grid_header_title ? staggerInterval.value : 0;
    const cardDelay = totalBaseDelay.value + headerOffset + (idx * staggerInterval.value);
    return { '--cms-anim-delay': `${cardDelay}ms` };
};

// Auto-recarga reactiva cuando caduca o se publica el artículo más próximo en la cuadrícula
useListAutoExpire(
    () => props.resolvedData || [],
    () => {
        if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/panel')) {
            router.reload({ preserveScroll: true });
        }
    }
);

const vistaDispositivo = inject('vistaDispositivo', ref(null));
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1200);

if (typeof window !== 'undefined') {
    onMounted(() => {
        const onResize = () => {
            windowWidth.value = window.innerWidth;
        };
        window.addEventListener('resize', onResize);
        onUnmounted(() => window.removeEventListener('resize', onResize));
    });
}

// Personalización avanzada
const customId = computed(() => props.content?.element_id || undefined);
const customClasses = computed(() => props.content?.custom_classes || '');
const customCss = computed(() => props.content?.custom_css || '');

// Identificador único para scoping de CSS de fondo de tarjeta
const gridUid = `grid-bg-${useId()}`;

// CSS generado dinámicamente para gradientes de fondo personalizados de tarjeta (Modo Claro / Oscuro)
const cardBgCss = computed(() => {
    const lightGradient = (props.content?.card_bg_gradient_light || '').trim();
    const darkGradient = (props.content?.card_bg_gradient_dark || '').trim();

    if (!lightGradient && !darkGradient) return '';

    const selector = `[data-grid-bg="${gridUid}"]`;
    let css = '';

    if (lightGradient) {
        css += `${selector} .grid-card-bg { background-image: ${lightGradient}; }\n`;
    }
    if (darkGradient) {
        css += `.dark ${selector} .grid-card-bg { background-image: ${darkGradient}; }\n`;
    }

    return css;
});

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

const mode = computed(() => props.content?.mode || 'dynamic');

// Margen superior e inferior del bloque general del grid
const marginTopClass = computed(() => {
    const mt = props.content?.margin_top || 'none';
    return {
        none: 'mt-0',
        xs: 'mt-2',
        sm: 'mt-4',
        md: 'mt-6',
        lg: 'mt-8',
        xl: 'mt-12',
        '2xl': 'mt-16',
    }[mt] || 'mt-0';
});

const marginBottomClass = computed(() => {
    const mb = props.content?.margin_bottom || 'none';
    return {
        none: 'mb-0',
        xs: 'mb-2',
        sm: 'mb-4',
        md: 'mb-6',
        lg: 'mb-8',
        xl: 'mb-12',
        '2xl': 'mb-16',
    }[mb] || 'mb-0';
});

// Separación entre columnas (gap)
const gapClass = computed(() => {
    const g = props.content?.gap || 'md';
    return {
        sm: 'gap-4',
        md: 'gap-6',
        lg: 'gap-8',
        xl: 'gap-12',
    }[g] || 'gap-6';
});

// Clases responsivas de columnas y alineación de cuadrícula (flex-wrap cuando se activa alineación centrada o derecha)
const columnsClass = computed(() => {
    const gridAlign = props.content?.grid_align || 'left';
    
    if (gridAlign === 'center' || gridAlign === 'right') {
        const justify = gridAlign === 'center' ? 'justify-center' : 'justify-end';
        return `builder-element-grid flex flex-wrap ${justify} ${gapClass.value}`;
    }

    const customMob = String(props.content?.columns_mobile || '1');
    const customTab = String(props.content?.columns_tablet || 'auto');
    const colsDesk = Number(props.content?.columns_desktop || props.content?.columns || 3);

    // Si el usuario está previsualizando en un modo de dispositivo específico en el builder:
    if (vistaDispositivo.value === 'mobile') {
        return customMob === '2' 
            ? `builder-element-grid grid grid-cols-2 ${gapClass.value}` 
            : `builder-element-grid grid grid-cols-1 ${gapClass.value}`;
    }

    if (vistaDispositivo.value === 'tablet') {
        const tabColsMap = {
            '1': 'grid-cols-1',
            '2': 'grid-cols-2',
            '3': 'grid-cols-3',
            '4': 'grid-cols-4',
        };
        const tabCol = tabColsMap[customTab] || (colsDesk >= 3 ? 'grid-cols-2' : (colsDesk === 1 ? 'grid-cols-1' : 'grid-cols-2'));
        return `builder-element-grid grid ${tabCol} ${gapClass.value}`;
    }

    // Modo Escritorio / Responsivo completo
    const mobClass = customMob === '2' ? 'grid-cols-2' : 'grid-cols-1';

    const tabMap = {
        '1': 'sm:grid-cols-1',
        '2': 'sm:grid-cols-2',
        '3': 'sm:grid-cols-3',
        '4': 'sm:grid-cols-4',
    };
    const tabClass = tabMap[customTab] || (colsDesk >= 3 ? 'sm:grid-cols-2' : (colsDesk === 1 ? 'sm:grid-cols-1' : 'sm:grid-cols-2'));

    const deskMap = {
        1: 'lg:grid-cols-1',
        2: 'lg:grid-cols-2',
        3: 'lg:grid-cols-3',
        4: 'lg:grid-cols-4',
        5: 'lg:grid-cols-5',
        6: 'lg:grid-cols-6',
    };
    const deskClass = deskMap[colsDesk] || 'lg:grid-cols-3';

    return ['builder-element-grid grid', gapClass.value, mobClass, tabClass, deskClass].join(' ');
});

// Estilos dinámicos de tarjetas cuando el grid está centrado (para centrar elementos sueltos en filas incompletas)
const itemStyle = computed(() => {
    const gridAlign = props.content?.grid_align || 'left';
    if (gridAlign !== 'center' && gridAlign !== 'right') {
        return {};
    }

    const colsDesk = Number(props.content?.columns_desktop || props.content?.columns || 3);
    const colsMob = String(props.content?.columns_mobile || '1') === '2' ? 2 : 1;
    const colsTab = String(props.content?.columns_tablet || 'auto') !== 'auto'
        ? Number(props.content?.columns_tablet)
        : (colsDesk >= 3 ? 2 : colsDesk);

    const gap = props.content?.gap || 'md';
    const gapPx = { sm: 16, md: 24, lg: 32, xl: 48 }[gap] || 24;

    let cols = colsDesk;
    
    // Si estamos en el editor con preview de dispositivo seleccionado explícitamente:
    if (vistaDispositivo.value === 'mobile') {
        cols = colsMob;
    } else if (vistaDispositivo.value === 'tablet') {
        cols = colsTab;
    } else if (vistaDispositivo.value === 'desktop') {
        cols = colsDesk;
    } else {
        // En el frontend público real, guiarse por el ancho de pantalla del navegador
        if (windowWidth.value < 640) {
            cols = colsMob;
        } else if (windowWidth.value < 1024) {
            cols = colsTab;
        } else {
            cols = colsDesk;
        }
    }

    const widthPct = (100 / cols).toFixed(3);
    const gapDeduction = (((cols - 1) * gapPx) / cols).toFixed(2);

    return {
        flex: `0 0 calc(${widthPct}% - ${gapDeduction}px)`,
        maxWidth: `calc(${widthPct}% - ${gapDeduction}px)`,
        width: `calc(${widthPct}% - ${gapDeduction}px)`,
    };
});

// Preset visual de tarjeta (Estándar, Sombra Elevada, Plana, Borde Primario Accent)
const cardStylePreset = computed(() => props.content?.card_style || 'default');

const cardContainerClasses = computed(() => {
    const base = 'group flex flex-col justify-between transition-all duration-300 relative h-full';
    if (cardStylePreset.value === 'shadow') {
        return `${base} bg-white dark:bg-zinc-900 rounded-2xl p-6 shadow-md hover:shadow-2xl hover:-translate-y-1 border-0`;
    }
    if (cardStylePreset.value === 'flat') {
        return `${base} bg-transparent p-0 border-0 shadow-none`;
    }
    if (cardStylePreset.value === 'accent') {
        return `${base} bg-white dark:bg-zinc-900 rounded-2xl border-2 border-[var(--primary-color,#10b981)]/80 p-6 shadow-lg shadow-[var(--primary-color,#10b981)]/10 hover:shadow-xl hover:-translate-y-1`;
    }
    return `${base} bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 hover:shadow-xl hover:-translate-y-1 hover:border-[var(--primary-color,#10b981)]/50`;
});

// Ratio de imagen (16:9, 4:3, 1:1, auto)
const imageAspectClass = computed(() => {
    const ratio = props.content?.image_ratio || '16:9';
    return {
        '16:9': 'aspect-video',
        '4:3': 'aspect-[4/3]',
        '1:1': 'aspect-square',
        'auto': 'h-auto',
    }[ratio] || 'aspect-video';
});

// Ajuste de imagen (Recortar/Cover vs Sin recortar/Contain)
const imageFitClass = computed(() => {
    const fit = props.content?.image_fit || 'cover';
    return fit === 'contain' ? 'object-contain bg-zinc-950/10 dark:bg-zinc-950/40' : 'object-cover';
});

// Estilo del botón en tarjetas (enlace, botón primario, botón secundario, botón contorno)
const buttonStyleClass = computed(() => {
    const st = props.content?.button_style || 'link';
    if (st === 'button_secondary') {
        return 'inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[var(--btn-secondary-bg,var(--secondary-color,#6366f1))] text-[var(--btn-secondary-text,#ffffff)] hover:opacity-90 text-xs font-bold transition-all shadow-xs';
    }
    if (st === 'button_primary') {
        return 'inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[var(--btn-primary-bg,var(--primary-color,#10b981))] text-[var(--btn-primary-text,#ffffff)] hover:opacity-90 text-xs font-bold transition-all shadow-xs';
    }
    if (st === 'button_outline') {
        return 'inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border-2 border-[var(--btn-primary-bg,var(--primary-color,#10b981))] text-[var(--btn-primary-bg,var(--primary-color,#10b981))] hover:bg-[var(--btn-primary-bg,var(--primary-color,#10b981))] hover:text-[var(--btn-primary-text,#ffffff)] text-xs font-bold transition-all';
    }
    return 'inline-flex items-center gap-1.5 text-xs font-bold text-[var(--btn-primary-bg,var(--primary-color,#10b981))] hover:opacity-80 transition-colors';
});

// Etiqueta semántica HTML del título de tarjeta
const titleTag = computed(() => {
    const raw = props.content?.title_tag || 'h3';
    return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'].includes(raw) ? raw : 'h3';
});

import { getHeadingClasses, getHeadingStyle } from '@/Utils/headingScale';

// Tamaño visual del título de tarjeta adaptado al dispositivo activo (Móvil -> Tablet -> Escritorio)
const titleSizeClass = computed(() => {
    const szDesk = props.content?.title_size || 'default';
    const szTab = props.content?.title_size_tablet || '';
    const szMob = props.content?.title_size_mobile || '';
    return getHeadingClasses(szDesk, szTab, szMob, vistaDispositivo.value, titleTag.value);
});

const titleStyle = computed(() => {
    const szDesk = props.content?.title_size || 'default';
    return getHeadingStyle(szDesk, titleTag.value);
});

const gridHeaderStyle = computed(() => {
    const szDesk = props.content?.grid_header_size || 'default';
    return getHeadingStyle(szDesk, gridHeaderTag.value);
});



// Tamaño del texto de descripción de tarjeta
const textSizeClass = computed(() => {
    const sz = props.content?.text_size || 'xs';
    return {
        xs: 'text-xs',
        sm: 'text-sm',
        base: 'text-base',
        lg: 'text-lg',
        xl: 'text-xl',
    }[sz] || 'text-xs';
});

const textSizeCss = computed(() => {
    const sz = props.content?.text_size || 'xs';
    return {
        xs: '0.75rem',    // 12px
        sm: '0.875rem',   // 14px
        base: '1rem',     // 16px
        lg: '1.125rem',   // 18px
        xl: '1.25rem',    // 20px
    }[sz] || '0.75rem';
});

// Alineación del texto interno de la tarjeta (Izquierda, Centrado, Derecha)
const textAlignClass = computed(() => {
    const align = props.content?.text_align || 'left';
    return {
        center: 'text-center items-center',
        right: 'text-right items-end',
        left: 'text-left items-start',
    }[align] || 'text-left items-start';
});

// Utilidad para extraer valores anidados o de campos personalizados
function resolveDynamicField(art, keyPath, fallbackKey) {
    if (keyPath === 'none') {
        return null;
    }

    if (!keyPath || keyPath === 'default') {
        if (fallbackKey === 'title') return art.title;
        if (fallbackKey === 'description') return art.short_description || art.excerpt || null;
        if (fallbackKey === 'badge') {
            if (art._source === 'downloads') {
                return art.version ? `${art.formatted_price || 'Gratis'} · v${art.version}` : (art.formatted_price || null);
            }
            return art.category?.name || null;
        }
        if (fallbackKey === 'image') return art.featured_image || null;
        return null;
    }

    if (keyPath === 'title') return art.title;
    if (keyPath === 'excerpt' || keyPath === 'short_description') return art.short_description || art.excerpt || null;
    if (keyPath === 'compatibility') return art.compatibility || null;
    if (keyPath === 'version') return art.version ? `v${art.version}` : null;
    if (keyPath === 'formatted_price') return art.formatted_price || null;
    if (keyPath === 'formatted_file_size') return art.formatted_file_size || null;
    if (keyPath === 'version_price') {
        return art.version ? `${art.formatted_price || 'Gratis'} · v${art.version}` : (art.formatted_price || null);
    }
    if (keyPath === 'category.name') return art.category?.name || art.compatibility || null;
    if (keyPath === 'published_at') return art.published_at || null;
    if (keyPath === 'featured_image') return art.featured_image || null;

    if (keyPath.startsWith('extra_fields_data.')) {
        const fieldKey = keyPath.replace('extra_fields_data.', '');
        const val = art.extra_fields_data?.[fieldKey];
        if (val !== undefined && val !== null && val !== '') {
            return String(val);
        }
        return null;
    }
    return null;
}

// Estilos y disposición del Icono
const iconPosition = computed(() => props.content?.icon_position || 'top_card');

const iconSizePx = computed(() => {
    const raw = props.content?.icon_size || 'md';
    if (typeof raw === 'number' || (typeof raw === 'string' && /^\d+$/.test(raw))) {
        return Number(raw);
    }
    if (raw === 'custom') {
        const customPx = Number(props.content?.icon_size_px);
        if (customPx > 0) return customPx;
        return 48;
    }

    const presetMap = {
        xs: 16,
        sm: 24,
        md: 32,
        lg: 48,
        xl: 64,
        '2xl': 80,
        '3xl': 96,
        '4xl': 120,
    };
    return presetMap[raw] || 32;
});

const iconStyle = computed(() => {
    const px = iconSizePx.value;
    const styleMode = props.content?.icon_style_mode || (iconPosition.value === 'top_card' ? 'soft_bg' : 'none');
    const hasBg = styleMode && styleMode !== 'none';

    if (hasBg) {
        const containerPx = Math.round(px * 1.75);
        return {
            width: `${containerPx}px`,
            height: `${containerPx}px`,
            fontSize: `${px}px`,
        };
    }

    return {
        fontSize: `${px}px`,
    };
});

const iconTextColorClass = computed(() => {
    const color = props.content?.icon_color || 'primary';
    return {
        primary: 'text-[var(--primary-color,#10b981)]',
        dark: 'text-zinc-900 dark:text-zinc-100',
        light: 'text-white',
        zinc: 'text-zinc-500 dark:text-zinc-400',
        emerald: 'text-emerald-500 dark:text-emerald-400',
        indigo: 'text-indigo-500 dark:text-indigo-400',
        blue: 'text-blue-500 dark:text-blue-400',
        amber: 'text-amber-500 dark:text-amber-400',
        rose: 'text-rose-500 dark:text-rose-400',
    }[color] || 'text-[var(--primary-color,#10b981)]';
});

const iconBgStyleClass = computed(() => {
    const styleMode = props.content?.icon_style_mode || (iconPosition.value === 'top_card' ? 'soft_bg' : 'none');
    if (styleMode === 'none') {
        return 'bg-transparent shadow-none';
    }

    const rounded = (styleMode === 'circle_soft' || styleMode === 'circle_solid') ? 'rounded-full' : 'rounded-xl';
    const bgColor = props.content?.icon_bg_color || 'primary_soft';

    const bgClasses = {
        primary_soft: 'bg-[var(--primary-color,#10b981)]/10',
        primary_solid: 'bg-[var(--primary-color,#10b981)] !text-white',
        zinc_soft: 'bg-zinc-100 dark:bg-zinc-800/80',
        zinc_dark: 'bg-zinc-900 dark:bg-zinc-100 !text-white dark:!text-zinc-900',
        emerald_soft: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400',
        emerald_solid: 'bg-emerald-500 !text-white',
        indigo_soft: 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400',
        indigo_solid: 'bg-indigo-500 !text-white',
        blue_soft: 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400',
        amber_soft: 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400',
        rose_soft: 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400',
    }[bgColor] || 'bg-[var(--primary-color,#10b981)]/10';

    return `${rounded} ${bgClasses}`;
});

const iconVerticalAlignClass = computed(() => {
    const align = props.content?.icon_vertical_align || 'items-center';
    return {
        'items-start': 'items-start',
        'items-center': 'items-center',
        'items-end': 'items-end',
    }[align] || 'items-center';
});

const iconGapClass = computed(() => {
    const m = props.content?.icon_margin || 'sm';
    return {
        none: 'gap-0',
        xs: 'gap-1.5',
        sm: 'gap-2.5',
        md: 'gap-4',
        lg: 'gap-6',
    }[m] || 'gap-2.5';
});

const iconMarginBottomClass = computed(() => {
    const m = props.content?.icon_margin || 'sm';
    return {
        none: 'mb-0',
        xs: 'mb-1.5',
        sm: 'mb-2.5',
        md: 'mb-4',
        lg: 'mb-6',
    }[m] || 'mb-2.5';
});

const iconMarginTopClass = computed(() => {
    const m = props.content?.icon_margin || 'sm';
    return {
        none: 'mt-0',
        xs: 'mt-1.5',
        sm: 'mt-2.5',
        md: 'mt-4',
        lg: 'mt-6',
    }[m] || 'mt-2.5';
});

// Ordenación reactiva de artículos dinámicos (Orden del Backend, Orden Manual Drag&Drop, Alfabético, Fecha de Publicación)
const sortedDynamicArticles = computed(() => {
    const rawData = Array.isArray(props.resolvedData) ? [...props.resolvedData] : [];
    const orderBy = props.content?.dynamic_binding?.order_by || props.content?.order_by || 'published_at_desc';

    // 'default' / 'backend': respetar el orden que el backend ya aplicó sin re-ordenar localmente
    if (orderBy === 'default' || orderBy === 'backend') {
        return rawData;
    }
    if (orderBy === 'title_asc') {
        return rawData.sort((a, b) => (a.title || '').localeCompare(b.title || '', undefined, { numeric: true, sensitivity: 'base' }));
    }
    if (orderBy === 'title_desc') {
        return rawData.sort((a, b) => (b.title || '').localeCompare(a.title || '', undefined, { numeric: true, sensitivity: 'base' }));
    }
    if (orderBy === 'order_asc' || orderBy === 'manual') {
        return rawData.sort((a, b) => ((a.order ?? 0) - (b.order ?? 0)) || ((b.id ?? 0) - (a.id ?? 0)));
    }
    if (orderBy === 'published_at_asc') {
        return rawData.sort((a, b) => new Date(a.published_at_iso || a.published_at || 0) - new Date(b.published_at_iso || b.published_at || 0));
    }
    // Default: published_at_desc
    return rawData.sort((a, b) => new Date(b.published_at_iso || b.published_at || 0) - new Date(a.published_at_iso || a.published_at || 0));
});

// Normalización unificada de tarjetas (dinámicas del CMS, manuales o mixtas)
const itemsToDisplay = computed(() => {
    const manualItems = Array.isArray(props.content?.items) ? props.content.items : [];
    const dynamicArticles = sortedDynamicArticles.value;
    const mapping = props.content?.dynamic_mapping || {};

    const mapDynamic = (art) => {
        const catSlug = art.category?.slug || 'articulos';
        const titleVal = resolveDynamicField(art, mapping.title_field || 'title', 'title') ?? art.title;
        const descVal = resolveDynamicField(art, mapping.description_field || 'excerpt', 'description') ?? art.excerpt;
        const badgeVal = resolveDynamicField(art, mapping.badge_field || 'category.name', 'badge');
        const imageField = mapping.image_field || 'featured_image';
        // Solo hacer fallback a featured_image si el campo mapeado es el propio featured_image.
        // Si el usuario eligió un campo extra y el artículo no lo tiene, la imagen queda vacía.
        const imageVal = imageField === 'featured_image'
            ? (resolveDynamicField(art, imageField, 'image') ?? art.featured_image)
            : resolveDynamicField(art, imageField, 'image');
        const rawIcon = resolveDynamicField(art, mapping.icon_field || 'icon', 'icon') ?? art.icon;
        const btnText = props.content?.dynamic_mapping?.button_text_override || props.content?.button_text || (art._source === 'downloads' ? 'Ver ficha' : 'Leer más');

        let resolvedUrl = null;
        if (art._source === 'downloads') {
            const linkMode = mapping.download_link_mode || 'detail';
            const rawTarget = (linkMode === 'direct_download' && art.download_request_url && art.is_free)
                ? art.download_request_url
                : art.url;
            resolvedUrl = rawTarget ? getRutaInterna(rawTarget) : null;
        } else {
            resolvedUrl = art.slug ? (art.category?.slug ? `/${art.category.slug}/${art.slug}` : `/articulos/${art.slug}`) : null;
        }

        const useSpaLink = resolvedUrl ? esNavegacionInertia(resolvedUrl, '_self') : false;
        const isSvgImg = typeof imageVal === 'string' && imageVal.split('?')[0].split('#')[0].toLowerCase().endsWith('.svg');

        return {
            id: art.id,
            title: titleVal,
            subtitle: art.subtitle || null,
            description: descVal,
            badge: badgeVal,
            image: imageVal,
            isSvgImage: isSvgImg,
            icon: (rawIcon && rawIcon !== 'none') ? rawIcon : null,
            url: resolvedUrl,
            useSpaLink,
            button_text: btnText,
            _isDynamic: true,
            _rawDownload: art._source === 'downloads' ? {
                ...art,
                title: titleVal || art.title,
                short_description: descVal !== undefined ? descVal : art.short_description,
                featured_image: imageVal !== undefined ? imageVal : art.featured_image,
            } : null,
        };
    };

    if (mode.value === 'manual') {
        return manualItems.map(item => ({ ...item, _isManual: true }));
    }

    if (mode.value === 'mixed') {
        return [
            ...manualItems.map(item => ({ ...item, _isManual: true })),
            ...dynamicArticles.map(mapDynamic)
        ];
    }

    // Default / modo dinámico
    if (dynamicArticles.length > 0) {
        return dynamicArticles.map(mapDynamic);
    }

    if (manualItems.length > 0) {
        return manualItems.map(item => ({ ...item, _isManual: true }));
    }

    return [];
});

// Modo Tarjeta Rica nativa para fuentes de Descargas (con peso ZIP, compatibilidad, subtítulo y descarga rápida)
const usarTarjetaDescargaRica = computed(() => {
    const isDownloadsSource = props.content?.dynamic_binding?.source === 'downloads';
    const cardMode = props.content?.download_card_mode || 'download_card';
    return isDownloadsSource && cardMode === 'download_card';
});

const downloadCardConfig = computed(() => {
    const customBtnOverride = props.content?.dynamic_mapping?.button_text_override;
    return {
        card_style: props.content?.card_style === 'accent' ? 'bordered' : (props.content?.card_style || 'default'),
        card_text_align: props.content?.text_align || 'left',
        item_title_tag: props.content?.title_tag || 'h2',
        item_title_size: props.content?.title_size || 'h4',
        item_title_size_tablet: props.content?.title_size_tablet || '',
        item_title_size_mobile: props.content?.title_size_mobile || '',
        item_desc_size: props.content?.text_size || 'sm',
        item_desc_lines: String(props.content?.desc_lines || '3'),
        show_item_title: props.content?.show_title !== false,
        show_item_image: props.content?.show_image !== false,
        show_item_description: props.content?.show_description !== false,
        show_item_badges: props.content?.show_badge !== false,
        show_item_meta: props.content?.show_meta !== false,
        show_item_button: props.content?.show_button !== false,
        item_image_ratio: props.content?.image_ratio || '16:9',
        item_svg_size: props.content?.svg_size || 'lg',
        item_image_position: props.content?.image_position === 'boxed' ? 'boxed' : 'flush',
        item_button_style: props.content?.button_style || 'button_primary',
        item_button_size: props.content?.button_size || 'md',
        show_quick_download: props.content?.show_quick_download !== false,
        custom_button_text: (customBtnOverride && customBtnOverride !== 'Leer más') ? customBtnOverride : '',
    };
});
</script>

<template>
    <div
        :style="gridBlockAnimStyle"
        class="builder-element-grid-container w-full scroll-mt-24 sm:scroll-mt-28"
        :data-grid-bg="(props.content?.card_bg_gradient_light || props.content?.card_bg_gradient_dark) ? gridUid : undefined"
        v-bind="gridBlockAnimAttrs"
    >
        <!-- Inyección de CSS de gradientes de fondo de tarjeta -->
        <component :is="'style'" v-if="cardBgCss" v-text="cardBgCss" />
        <component :is="'style'" v-if="customCss" v-text="customCss" />
        <!-- Encabezado opcional de la Sección Grid -->
        <div
            v-if="props.content?.grid_header_title"
            :class="['mb-6 w-full flex flex-col', textAlignClass]"
            v-bind="gridHeaderAnimAttrs"
            :style="gridHeaderAnimStyle"
        >
            <component :is="gridHeaderTag" :class="[gridHeaderSizeClass, 'text-zinc-900 dark:text-zinc-100']" :style="gridHeaderStyle" v-html="sanitize(props.content.grid_header_title)" />
            <p v-if="props.content?.grid_header_subtitle" class="text-xs sm:text-sm text-zinc-500 mt-1" v-html="sanitize(props.content.grid_header_subtitle)" />
        </div>

        <div
            v-if="itemsToDisplay.length"
            :class="['w-full py-3 -my-3 px-1 -mx-1', columnsClass]"
        >
            <div
                v-for="(item, idx) in itemsToDisplay"
                :key="item.id || item.title || idx"
                :style="[itemStyle, getItemAnimStyle(idx)]"
                v-bind="getItemAnimAttrs(idx)"
                class="w-full"
            >

                <!-- Tarjeta Rica Nativa de Descarga (cuando la fuente es Catálogo de Descargas) -->
                <DownloadCard
                    v-if="usarTarjetaDescargaRica && item._rawDownload"
                    :item="item._rawDownload"
                    view-mode="grid"
                    :title-tag="titleTag"
                    :card-config="downloadCardConfig"
                    :extra-card-class="(props.content?.card_bg_gradient_light || props.content?.card_bg_gradient_dark) ? 'grid-card-bg' : ''"
                />

                <!-- Tarjeta Renderizada con el Diseño Configurado en el Builder (Soporta Tarjeta Clicable Completa) -->
                <component
                    v-else
                    :is="(item.url && (props.content?.card_link_mode === 'card' || props.content?.card_link_mode === 'both')) ? (item.useSpaLink ? Link : 'a') : 'div'"
                    :href="(item.url && (props.content?.card_link_mode === 'card' || props.content?.card_link_mode === 'both')) ? item.url : undefined"
                    :rel="(item.url && !item.useSpaLink && (props.content?.card_link_mode === 'card' || props.content?.card_link_mode === 'both')) ? 'nofollow' : undefined"
                    :data-builder-navigate-url="item.url || undefined"
                    :class="[
                        cardContainerClasses,
                        textAlignClass,
                        (item.url && (props.content?.card_link_mode === 'card' || props.content?.card_link_mode === 'both')) ? 'cursor-pointer block no-underline text-inherit' : '',
                        (props.content?.card_bg_gradient_light || props.content?.card_bg_gradient_dark) ? 'grid-card-bg' : ''
                    ]"
                >
                    <!-- Distintivo / Badge superior (si la imagen NO es sin margen de relleno) -->
                    <div v-if="item.badge && props.content?.show_badge !== false && props.content?.image_position !== 'flush'" :class="['mb-3 flex w-full', (props.content?.text_align === 'center' ? 'justify-center' : (props.content?.text_align === 'right' ? 'justify-end' : 'justify-start'))]">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[var(--primary-color,#10b981)]/10 text-[var(--primary-color,#10b981)]">
                            {{ item.badge }}
                        </span>
                    </div>

                    <!-- Icono o Imagen (Soporta modo Sin Margen de Relleno / Flush superior) -->
                    <div
                        v-if="item.image && props.content?.show_image !== false"
                        :class="[
                            'overflow-hidden bg-zinc-100 dark:bg-zinc-800 relative',
                            imageAspectClass,
                            (props.content?.image_position === 'flush' && cardStylePreset !== 'flat')
                                ? '-mx-6 -mt-6 mb-4 w-[calc(100%+3rem)] max-w-[calc(100%+3rem)] rounded-t-2xl'
                                : 'w-full mb-4 rounded-xl'
                        ]"
                    >
                        <div
                            v-if="item.isSvgImage && props.content?.image_fit === 'contain'"
                            class="w-full h-full flex items-center justify-center p-6 bg-gradient-to-br from-zinc-50 via-zinc-100/80 to-zinc-200/50 dark:from-zinc-900 dark:via-zinc-800/80 dark:to-zinc-900"
                        >
                            <img
                                :src="item.image"
                                :alt="item.title || 'Tarjeta'"
                                loading="lazy"
                                decoding="async"
                                class="w-24 h-24 object-contain transition-transform duration-500 group-hover:scale-110 dark:invert dark:opacity-90"
                            />
                        </div>
                        <ResponsiveImage
                            v-else
                            :src="item.image"
                            :alt="item.title || 'Tarjeta'"
                            :width="600"
                            :height="338"
                            fit="cover"
                            :quality="85"
                            :widths="[320, 480, 640]"
                            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px"
                            :imgClass="['w-full h-full', imageFitClass]"
                            containerClass="w-full h-full"
                        />

                        <!-- Badge superpuesto en la imagen si la imagen es a sangre superior -->
                        <div v-if="item.badge && props.content?.show_badge !== false && props.content?.image_position === 'flush'" class="absolute top-3 left-3 z-10">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-zinc-900/80 text-white backdrop-blur-sm shadow-sm">
                                {{ item.badge }}
                            </span>
                        </div>
                    </div>

                    <!-- Icono Bloque Superior (top_card / por defecto sin posición explícita cuando no hay imagen visible) -->
                    <div
                        v-if="item.icon && props.content?.show_icon !== false && (iconPosition === 'top_card' || !iconPosition) && (!item.image || props.content?.show_image === false)"
                        :class="[
                            'flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shrink-0',
                            iconBgStyleClass,
                            iconTextColorClass,
                            (props.content?.text_align === 'center' ? 'mx-auto' : (props.content?.text_align === 'right' ? 'ml-auto' : ''))
                        ]"
                        :style="iconStyle"
                    >
                        <i :class="item.icon" :style="{ fontSize: `${iconSizePx}px` }" />
                    </div>

                    <!-- Título y Descripción con soporte de posicionamiento de icono (Izquierda, Derecha, Encima, Debajo) -->
                    <div class="flex-1 space-y-2 mb-4 w-full">

                        <!-- Icono A LA IZQUIERDA O DERECHA del Título -->
                        <div
                            v-if="item.icon && props.content?.show_icon !== false && (iconPosition === 'left_title' || iconPosition === 'right_title')"
                            :class="['flex w-full', iconVerticalAlignClass, iconGapClass, (props.content?.text_align === 'center' ? 'justify-center' : (props.content?.text_align === 'right' ? 'justify-end' : 'justify-start'))]"
                        >
                            <div
                                v-if="iconPosition === 'left_title'"
                                :class="[
                                    'flex items-center justify-center shrink-0 transition-transform group-hover:scale-110',
                                    iconBgStyleClass,
                                    iconTextColorClass
                                ]"
                                :style="iconStyle"
                            >
                                <i :class="item.icon" :style="{ fontSize: `${iconSizePx}px` }" />
                            </div>

                            <component
                                v-if="props.content?.show_title !== false"
                                :is="titleTag"
                                :class="[titleSizeClass, 'text-zinc-900 dark:text-zinc-100 group-hover:text-[var(--primary-color,#10b981)] transition-colors flex-1']"
                                :style="titleStyle"
                                v-html="sanitize(item.title || 'Tarjeta de Contenido')"
                            />

                            <div
                                v-if="iconPosition === 'right_title'"
                                :class="[
                                    'flex items-center justify-center shrink-0 transition-transform group-hover:scale-110',
                                    iconBgStyleClass,
                                    iconTextColorClass
                                ]"
                                :style="iconStyle"
                            >
                                <i :class="item.icon" :style="{ fontSize: `${iconSizePx}px` }" />
                            </div>
                        </div>

                        <!-- Icono ENCIMA del Título -->
                        <template v-else-if="item.icon && props.content?.show_icon !== false && iconPosition === 'above_title'">
                            <div :class="['w-full flex', iconMarginBottomClass, (props.content?.text_align === 'center' ? 'justify-center' : (props.content?.text_align === 'right' ? 'justify-end' : 'justify-start'))]">
                                <div
                                    :class="[
                                        'flex items-center justify-center shrink-0 transition-transform group-hover:scale-110',
                                        iconBgStyleClass,
                                        iconTextColorClass
                                    ]"
                                    :style="iconStyle"
                                >
                                    <i :class="item.icon" :style="{ fontSize: `${iconSizePx}px` }" />
                                </div>
                            </div>
                            <component
                                v-if="props.content?.show_title !== false"
                                :is="titleTag"
                                :class="[titleSizeClass, 'text-zinc-900 dark:text-zinc-100 group-hover:text-[var(--primary-color,#10b981)] transition-colors']"
                                :style="titleStyle"
                                v-html="sanitize(item.title || 'Tarjeta de Contenido')"
                            />
                        </template>

                        <!-- Icono DEBAJO del Título -->
                        <template v-else-if="item.icon && props.content?.show_icon !== false && iconPosition === 'below_title'">
                            <component
                                v-if="props.content?.show_title !== false"
                                :is="titleTag"
                                :class="[titleSizeClass, 'text-zinc-900 dark:text-zinc-100 group-hover:text-[var(--primary-color,#10b981)] transition-colors']"
                                :style="titleStyle"
                                v-html="sanitize(item.title || 'Tarjeta de Contenido')"
                            />
                            <div :class="['w-full flex', iconMarginTopClass, (props.content?.text_align === 'center' ? 'justify-center' : (props.content?.text_align === 'right' ? 'justify-end' : 'justify-start'))]">
                                <div
                                    :class="[
                                        'flex items-center justify-center shrink-0 transition-transform group-hover:scale-110',
                                        iconBgStyleClass,
                                        iconTextColorClass
                                    ]"
                                    :style="iconStyle"
                                >
                                    <i :class="item.icon" :style="{ fontSize: `${iconSizePx}px` }" />
                                </div>
                            </div>
                        </template>

                        <!-- Título estándar (sin posicionamiento especial de icono) -->
                        <template v-else>
                            <component
                                v-if="props.content?.show_title !== false"
                                :is="titleTag"
                                :class="[titleSizeClass, 'text-zinc-900 dark:text-zinc-100 group-hover:text-[var(--primary-color,#10b981)] transition-colors']"
                                :style="titleStyle"
                                v-html="sanitize(item.title || 'Tarjeta de Contenido')"
                            />
                        </template>

                        <!-- Subtítulo en texto pequeño debajo del título (ej. Catálogo de Descargas o tarjetas manuales) -->
                        <small
                            v-if="item.subtitle && props.content?.show_subtitle !== false"
                            class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 leading-snug -mt-1"
                            v-html="sanitize(item.subtitle)"
                        />

                        <div
                            v-if="item.description && props.content?.show_description !== false"
                            class="text-zinc-600 dark:text-zinc-400 leading-relaxed prose dark:prose-invert max-w-none [&_p]:my-0 [&_p]:[font-size:inherit] [&_span]:[font-size:inherit] [&_div]:[font-size:inherit] [&_a]:[font-size:inherit]"
                            :style="{ fontSize: textSizeCss }"
                            v-html="sanitize(item.description)"
                        />
                    </div>

                    <!-- Botón o Enlace Destino -->
                    <div v-if="item.url && props.content?.show_button !== false" :class="['pt-3 border-t border-zinc-100 dark:border-zinc-800/80 w-full flex', (props.content?.text_align === 'center' ? 'justify-center' : (props.content?.text_align === 'right' ? 'justify-end' : 'justify-start'))]">
                        <component
                            :is="item.useSpaLink ? Link : 'a'"
                            :href="item.url"
                            :rel="!item.useSpaLink ? 'nofollow' : undefined"
                            :class="buttonStyleClass"
                            :aria-label="`${item.button_text || 'Ver más'}: ${item.title}`"
                        >
                            <span>{{ item.button_text || 'Ver más' }}</span>
                            <i class="pi pi-arrow-right text-[10px]" />
                        </component>
                    </div>
                </component>
            </div>
        </div>

        <!-- Estado Vacío Informativo -->
        <div v-else class="py-12 px-4 text-center bg-zinc-50 dark:bg-zinc-900/40 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 space-y-2">
            <i class="pi pi-th-large text-2xl text-zinc-400 mb-1" />
            <h4 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Cuadrícula sin contenidos</h4>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto">
                No hay artículos ni tarjetas manuales configuradas para mostrar. Selecciona una categoría con artículos o añade tarjetas en la pestaña de Opciones Avanzadas.
            </p>
        </div>
    </div>
</template>
