<script setup>
/**
 * Componente Columna del Page Builder.
 * Renderiza los elementos modulares contenidos en la columna con soporte para:
 *  - Ancho fraccional o fijo granular por breakpoint (1/1, 1/2, 2/3, flex-1, w-auto, etc.)
 *  - Reordenación visual (order-first, md:order-last, etc.)
 *  - Imagen y color de fondo, superposición (overlay)
 *  - Alineación vertical (arriba, centro, abajo, distribuido)
 *  - Rellenos (padding), esquinas redondeadas, divisores y animaciones en cascada.
 */
import ElementHeading from '../Elements/ElementHeading.vue';
import ElementText from '../Elements/ElementText.vue';
import ElementImage from '../Elements/ElementImage.vue';
import ElementVideo from '../Elements/ElementVideo.vue';
import ElementButton from '../Elements/ElementButton.vue';
import ElementIcon from '../Elements/ElementIcon.vue';
import ElementPagination from '../Elements/ElementPagination.vue';
import ElementGridArticles from '../Elements/ElementGridArticles.vue';
import ElementAccordion from '../Elements/ElementAccordion.vue';
import ElementMenu from '../Elements/ElementMenu.vue';
import { imageUrl } from '@/Composables/useImage';
import ElementSocial from '../Elements/ElementSocial.vue';
import ElementHtml from '../Elements/ElementHtml.vue';
import ElementGallery from '../Elements/ElementGallery.vue';
import ElementSublayout from '../Elements/ElementSublayout.vue';
import ElementMap from '../Elements/ElementMap.vue';
import ElementForm from '../Elements/ElementForm.vue';
import { computed, inject, provide, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { calculateColumnLayoutClasses } from '@/Config/gridPresets';
import { sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    column: { type: Object, required: true },
    colIndex: { type: Number, default: 0 },
    rowLayout: { type: String, default: '1' },
    gutterPadding: { type: String, default: '' },
    rowAlign: { type: String, default: 'start' },
    matchHeight: { type: Boolean, default: false },
    showDividers: { type: Boolean, default: false },
    isFirst: { type: Boolean, default: false },
    isLast: { type: Boolean, default: false },
});

const vistaDispositivo = inject('vistaDispositivo', ref('desktop'));
const parentSectionDelay = inject('parentSectionDelay', ref(0));
const sectionCascadeConfig = inject('sectionCascadeConfig', ref({
    animation: 'none',
    mode: 'cascade',
    baseDelay: 0,
    stagger: 100,
}));

// Clases de ancho y orden responsivo
const layoutClasses = computed(() => {
    return calculateColumnLayoutClasses(
        props.column,
        props.colIndex,
        props.rowLayout,
        vistaDispositivo.value
    );
});

const columnOwnAnimation = computed(() => props.column?.animation || 'none');
const columnOwnDelay = computed(() => {
    if (columnOwnAnimation.value === 'none') return 0;
    return Number(props.column?.animation_delay) || 0;
});

// Animación efectiva de la columna (propia o heredada de la sección en modo cascada)
const effectiveColumnAnimation = computed(() => {
    if (columnOwnAnimation.value !== 'none') {
        return columnOwnAnimation.value;
    }
    if (sectionCascadeConfig.value?.mode === 'cascade' && sectionCascadeConfig.value?.animation !== 'none') {
        return sectionCascadeConfig.value.animation;
    }
    return 'none';
});

// Retardo efectivo de la columna:
const effectiveColumnDelay = computed(() => {
    if (effectiveColumnAnimation.value === 'none') return 0;

    // Si la columna tiene animación propia explícita:
    if (columnOwnAnimation.value !== 'none') {
        return (parentSectionDelay.value || 0) + columnOwnDelay.value;
    }

    // Si hereda de la cascada de la sección:
    if (sectionCascadeConfig.value?.mode === 'cascade' && sectionCascadeConfig.value?.animation !== 'none') {
        return (sectionCascadeConfig.value.baseDelay || 0) + (props.colIndex * (sectionCascadeConfig.value.stagger || 100));
    }

    return 0;
});

// Transmitir retardo acumulado a los elementos modulares contenidos en la columna
provide('parentAnimationDelay', effectiveColumnDelay);

const animationStyle = computed(() => {
    if (effectiveColumnAnimation.value !== 'none' && effectiveColumnDelay.value > 0) {
        return { '--cms-anim-delay': `${effectiveColumnDelay.value}ms` };
    }
    return {};
});

const elementComponents = {
    heading: ElementHeading,
    text: ElementText,
    image: ElementImage,
    video: ElementVideo,
    button: ElementButton,
    icon: ElementIcon,
    pagination: ElementPagination,
    grid_articles: ElementGridArticles,
    grid: ElementGridArticles,
    accordion: ElementAccordion,
    menu: ElementMenu,
    social: ElementSocial,
    html: ElementHtml,
    gallery: ElementGallery,
    sublayout: ElementSublayout,
    map: ElementMap,
    form: ElementForm,
};

// Determinar el valor de alineación vertical (prioridad: columna > fila)
const alignValue = computed(() => {
    return props.column?.align || props.column?.settings?.align || props.rowAlign || 'start';
});

// Clase flexbox del contenedor de la columna
const columnAlignClass = computed(() => {
    const align = alignValue.value;
    return {
        start: 'justify-start',
        center: 'justify-center',
        end: 'justify-end',
        stretch: 'justify-between',
    }[align] || 'justify-start';
});

// Clase de envoltorio interno (my-auto/mt-auto) para centrado vertical garantizado
const wrapperAlignClass = computed(() => {
    const align = alignValue.value;
    if (align === 'center') return 'my-auto';
    if (align === 'end') return 'mt-auto';
    return '';
});

// Referencia al elemento <video> y observador de visibilidad
const bgVideoRef = ref(null);
let videoObserver = null;

// Detección de Vídeo de Fondo
const hasBgVideo = computed(() => {
    const bgType = props.column?.bg_type || props.column?.settings?.bg_type || 'image';
    const bgVideo = props.column?.bg_video || props.column?.settings?.bg_video;
    return bgType === 'video' && Boolean(bgVideo);
});

// Normalización de la URL del vídeo de fondo
const normalizedBgVideoUrl = computed(() => {
    const raw = props.column?.bg_video || props.column?.settings?.bg_video;
    if (!raw || typeof raw !== 'string') return '';
    const clean = raw.trim();
    if (!clean) return '';
    if (clean.startsWith('{') && clean.endsWith('}')) return '';
    if (clean.startsWith('http://') || clean.startsWith('https://') || clean.startsWith('/')) {
        return clean;
    }
    return `/${clean}`;
});

// Imagen Póster o Fallback (específica o heredada de la imagen de fondo)
const effectivePosterUrl = computed(() => {
    const customPoster = props.column?.bg_video_poster || props.column?.settings?.bg_video_poster;
    if (customPoster && typeof customPoster === 'string' && customPoster.trim()) {
        const clean = customPoster.trim();
        return imageUrl(clean, { w: 1280, q: 85, f: 'webp', fit: 'scale' }) || clean;
    }
    const fallbackImg = props.column?.bg_image || props.column?.settings?.bg_image;
    if (fallbackImg && typeof fallbackImg === 'string' && fallbackImg.trim()) {
        const clean = fallbackImg.trim();
        return imageUrl(clean, { w: 1280, q: 85, f: 'webp', fit: 'scale' }) || clean;
    }
    return '';
});

// Encuadre focal del vídeo (object-position)
const bgVideoPositionStyle = computed(() => {
    const pos = props.column?.bg_video_position || props.column?.settings?.bg_video_position || 'center center';
    return {
        objectPosition: pos,
    };
});

// Comportamiento de vídeo en smartphone
const bgVideoMobile = computed(() => {
    return props.column?.bg_video_mobile || props.column?.settings?.bg_video_mobile || 'fallback';
});

// Estilo de Imagen de Fondo (optimizada vía endpoint de variantes WebP)
const bgImageStyle = computed(() => {
    if (hasBgVideo.value) return {};
    const bgImg = props.column?.bg_image || props.column?.settings?.bg_image;
    if (!bgImg) return {};
    // Optimizar imagen local a través del endpoint de variantes (1280px WebP)
    const optimizedUrl = imageUrl(bgImg, { w: 1280, q: 85, f: 'webp', fit: 'scale' });
    return {
        backgroundImage: `url("${optimizedUrl || bgImg}")`,
        backgroundSize: 'cover',
        backgroundPosition: 'center center',
        backgroundRepeat: 'no-repeat',
    };
});

// Estilo de Color de Fondo
const bgColorStyle = computed(() => {
    const bgCol = props.column?.bg_color || props.column?.settings?.bg_color;
    if (!bgCol) return {};
    return { backgroundColor: bgCol };
});

// Capa de Superposición (Overlay)
const overlayClass = computed(() => {
    const ov = props.column?.bg_overlay || props.column?.settings?.bg_overlay;
    return {
        black_20: 'bg-black/20',
        black_40: 'bg-black/40',
        black_60: 'bg-black/60',
        white_50: 'bg-white/50',
    }[ov] || '';
});

// Color del Texto
const textColorClass = computed(() => {
    const tc = props.column?.text_color || props.column?.settings?.text_color;
    if (tc === 'light') return '!text-white dark';
    if (tc === 'dark') return '!text-zinc-900 light';
    return '';
});

// Relleno Interno (Padding)
const paddingClass = computed(() => {
    const p = props.column?.padding || props.column?.settings?.padding;
    return {
        none: 'p-0',
        sm: 'p-3 sm:p-4',
        md: 'p-5 sm:p-6',
        lg: 'p-8 sm:p-10',
        xl: 'p-10 sm:p-12',
    }[p] || '';
});

// Redondeo de Esquinas
const borderRadiusClass = computed(() => {
    const br = props.column?.border_radius || props.column?.settings?.border_radius;
    return {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
        '2xl': 'rounded-2xl',
        '3xl': 'rounded-3xl',
        full: 'rounded-full',
    }[br] || '';
});

// Clases de divisores simétricos y limpios (móvil y escritorio)
const dividerClasses = computed(() => {
    if (!props.showDividers) return '';

    const list = [];

    // Divisor superior en móvil para columnas excepto la primera
    if (!props.isFirst) {
        list.push('border-t lg:border-t-0 pt-6 lg:pt-0 border-zinc-200 dark:border-zinc-800');
    } else {
        list.push('pb-6 lg:pb-0');
    }

    // Divisor izquierdo en escritorio para columnas excepto la primera
    if (!props.isFirst) {
        list.push('lg:border-l lg:border-zinc-200 dark:lg:border-zinc-800 lg:pl-8');
    }

    // Espaciado derecho en escritorio para columnas excepto la última
    if (!props.isLast) {
        list.push('lg:pr-8');
    }

    return list.join(' ');
});

// Ciclo de vida: Inicialización segura de vídeo, Muted forzado y Observador de Visibilidad
onMounted(() => {
    if (typeof window === 'undefined') return;

    if (hasBgVideo.value && bgVideoRef.value) {
        const el = bgVideoRef.value;
        el.muted = true;
        el.defaultMuted = true;

        const rate = Number(props.column?.bg_video_playback_rate || props.column?.settings?.bg_video_playback_rate);
        if (!isNaN(rate) && rate > 0) {
            el.playbackRate = rate;
        }

        // Respetar accesibilidad de reducción de movimiento (prefers-reduced-motion)
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
            el.pause();
            return;
        }

        // Pausar cuando salga del viewport y reanudar al entrar (optimización de FPS y GPU)
        if ('IntersectionObserver' in window) {
            videoObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && entry.intersectionRatio > 0) {
                            el.play().catch(() => {});
                        } else {
                            el.pause();
                        }
                    });
                },
                { rootMargin: '150px 0px', threshold: [0, 0.05] }
            );
            videoObserver.observe(el);
        } else {
            el.play().catch(() => {});
        }
    }
});

// Liberación estricta de memoria y decodificadores multimedia en transiciones SPA
onBeforeUnmount(() => {
    if (videoObserver) {
        videoObserver.disconnect();
        videoObserver = null;
    }
    if (bgVideoRef.value) {
        bgVideoRef.value.pause();
        bgVideoRef.value.removeAttribute('src');
        bgVideoRef.value.load();
    }
});

// Sincronización en vivo con cambios en el inspector del maquetador
watch(
    () => [
        props.column?.bg_video || props.column?.settings?.bg_video,
        props.column?.bg_video_playback_rate || props.column?.settings?.bg_video_playback_rate,
        props.column?.bg_type || props.column?.settings?.bg_type
    ],
    ([newVideo, newRate, newType]) => {
        if (typeof window === 'undefined') return;
        if (newType === 'video' && bgVideoRef.value && newVideo) {
            bgVideoRef.value.muted = true;
            bgVideoRef.value.defaultMuted = true;
            const rate = Number(newRate);
            if (!isNaN(rate) && rate > 0) {
                bgVideoRef.value.playbackRate = rate;
            }
            bgVideoRef.value.play().catch(() => {});
        }
    }
);
</script>

<template>
    <div
        :id="column.element_id || undefined"
        :class="[
            'box-border',
            layoutClasses,
            gutterPadding,
            column.custom_classes
        ]"
    >
        <div
            :class="[
                'self-stretch h-full min-h-full min-w-0 flex flex-col relative',
                (bgImageStyle.backgroundImage || overlayClass || hasBgVideo) ? 'overflow-hidden' : '',
                columnAlignClass,
                dividerClasses,
                paddingClass,
                borderRadiusClass,
                textColorClass,
            ]"
            :style="[bgImageStyle, bgColorStyle, animationStyle]"
            :data-builder-id="column.id"
            :data-cms-animate="effectiveColumnAnimation !== 'none' ? effectiveColumnAnimation : undefined"
            :data-cms-delay="effectiveColumnAnimation !== 'none' && effectiveColumnDelay > 0 ? effectiveColumnDelay : undefined"
        >
            <!-- Capa Dedicada de Vídeo de Fondo HTML5 -->
            <div
                v-if="hasBgVideo"
                class="absolute inset-0 z-0 overflow-hidden pointer-events-none builder-column-video-layer"
            >
                <video
                    ref="bgVideoRef"
                    :src="normalizedBgVideoUrl"
                    :poster="effectivePosterUrl || undefined"
                    autoplay
                    loop
                    muted
                    playsinline
                    webkit-playsinline
                    preload="metadata"
                    :class="[
                        'w-full h-full object-cover',
                        bgVideoMobile === 'fallback' && (vistaDispositivo === 'mobile' ? 'hidden' : 'hidden md:block')
                    ]"
                    :style="bgVideoPositionStyle"
                />

                <!-- Fallback en smartphone si está configurado para ahorrar datos o en simulación móvil -->
                <div
                    v-if="effectivePosterUrl && (bgVideoMobile === 'fallback' || vistaDispositivo === 'mobile')"
                    :class="[
                        'absolute inset-0 bg-cover bg-center',
                        vistaDispositivo === 'mobile' ? 'block' : 'md:hidden'
                    ]"
                    :style="{ backgroundImage: `url(${effectivePosterUrl})` }"
                />
            </div>

            <!-- Overlay si existe sobre fondo (imagen o vídeo) -->
            <div v-if="overlayClass" :class="['absolute inset-0 z-0 pointer-events-none', overlayClass]" />

            <!-- Estilos CSS personalizados si existen -->
            <component :is="'style'" v-if="column.custom_css" v-html="sanitizeInlineCss(column.custom_css)" />

            <div :class="['w-full space-y-4 relative z-10', wrapperAlignClass]">
                <template v-for="element in column.elements || []" :key="element.id">
                    <component
                        :is="elementComponents[element.type] || ElementText"
                        :content="element.content || {}"
                        :style="element.style || {}"
                        :resolved-data="element.resolved_data"
                        :data-builder-id="element.id"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
