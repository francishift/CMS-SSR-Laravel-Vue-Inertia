<script setup>
/**
 * Componente Sección del Page Builder.
 * Soporta control de altura (Viewport, Píxeles, Calc), alineación vertical,
 * paddings top/bottom independientes, semántica HTML, color de texto, fondos,
 * ID HTML único, Clases CSS adicionales, Atributos HTML y CSS Personalizado.
 */
import BuilderRow from './BuilderRow.vue';
import { computed, inject, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { imageUrl } from '@/Composables/useImage';

const props = defineProps({
    section: { type: Object, required: true },
    index: { type: Number, default: 0 },
});

// Detectar si la sección es la primera (Hero / LCP)
const isFirstSection = computed(() => props.index === 0);

// Simulación de dispositivo en el maquetador visual (Escritorio, Tablet, Móvil)
const vistaDispositivo = inject('vistaDispositivo', ref(null));
const setTransparentHeaderOverride = inject('setTransparentHeaderOverride', null);

const settings = computed(() => props.section?.settings || {});

// Notificar al Layout público si la primera sección tiene cabecera transparente
watch(
    () => [
        isFirstSection.value,
        settings.value.transparent_header,
        settings.value.transparent_header_pull,
        settings.value.transparent_header_nav_color
    ],
    ([isFirst, isTrans, pull, navColor]) => {
        if (setTransparentHeaderOverride && isFirst) {
            setTransparentHeaderOverride(
                isTrans
                    ? { active: true, pull: Boolean(pull), navColor: navColor || 'inherit' }
                    : { active: false, pull: false, navColor: 'inherit' }
            );
        }
    },
    { immediate: true }
);

const sectionAnimation = computed(() => settings.value.animation || 'none');
const sectionAnimMode = computed(() => settings.value.animation_mode || 'cascade');
const sectionDelay = computed(() => {
    if (sectionAnimation.value === 'none') return 0;
    return Number(settings.value.animation_delay) || 0;
});
const sectionStagger = computed(() => {
    const s = Number(settings.value.animation_stagger);
    if (!isNaN(s) && s > 0) return s;
    return sectionDelay.value > 0 ? sectionDelay.value : 100;
});

// Configuración de cascada para los descendientes (filas, columnas y elementos)
const sectionCascadeConfig = computed(() => ({
    animation: sectionAnimation.value,
    mode: sectionAnimMode.value,
    baseDelay: sectionDelay.value,
    stagger: sectionStagger.value,
}));

// Indicar si la sección padre ya aporta padding horizontal para que las filas hijas no lo dupliquen
const sectionHasHPadding = computed(() => {
    const cont = settings.value.container || 'boxed';
    const noHPadding = !!settings.value.no_h_padding;
    return !noHPadding && cont !== 'full_bleed';
});

// Transmitir la configuración de cascada, retardo padre y padding horizontal
provide('sectionCascadeConfig', sectionCascadeConfig);
provide('parentSectionDelay', sectionDelay);
provide('sectionHasHPadding', sectionHasHPadding);

// Elemento semántico HTML (<section>, <div>, <header>, <footer>, <main>, <article>)
const htmlTag = computed(() => {
    const tag = settings.value.html_element || 'section';
    return ['section', 'div', 'header', 'footer', 'main', 'article'].includes(tag) ? tag : 'section';
});

// Personalización avanzada de código
const customId = computed(() => settings.value.element_id || undefined);
const customClasses = computed(() => settings.value.custom_classes || '');
const customCss = computed(() => settings.value.custom_css || '');

const parsedCustomAttributes = computed(() => {
    const raw = settings.value.custom_attributes;
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

// Ancho máximo del contenedor e inclusión de padding horizontal
const containerClass = computed(() => {
    const cont = settings.value.container || 'boxed';
    const noHPadding = !!settings.value.no_h_padding;
    const paddingH = noHPadding ? 'px-0' : 'px-4 sm:px-6 lg:px-8';

    const baseMap = {
        boxed: `max-w-7xl mx-auto ${paddingH}`,
        small: `max-w-3xl mx-auto ${paddingH}`,
        expand: `max-w-screen-2xl mx-auto ${paddingH}`,
        full_margin: `w-full ${paddingH}`,
        full_bleed: 'w-full px-0',
    };

    return baseMap[cont] || `max-w-7xl mx-auto ${paddingH}`;
});

// Desplazamiento superior compensatorio si la cabecera es transparente pero NO se coloca el contenido detrás
const transparentHeaderContentOffsetClass = computed(() => {
    if (isFirstSection.value && settings.value.transparent_header && !settings.value.transparent_header_pull) {
        return 'pt-16 sm:pt-20 lg:pt-24';
    }
    return '';
});

// Paddings independientes Top / Bottom con fallback responsive
const paddingTopClass = computed(() => {
    const padTop = settings.value.padding_top || settings.value.padding || 'md';
    const padTopTablet = settings.value.padding_top_tablet;
    const padTopMobile = settings.value.padding_top_mobile;

    const mobMap = {
        none: 'pt-0',
        sm: 'pt-6',
        md: 'pt-12',
        lg: 'pt-20',
        xl: 'pt-28',
        '2xl': 'pt-36',
    };

    const tabMap = {
        none: 'md:pt-0',
        sm: 'md:pt-6',
        md: 'md:pt-12',
        lg: 'md:pt-20',
        xl: 'md:pt-28',
        '2xl': 'md:pt-36',
    };

    const deskMap = {
        none: 'lg:pt-0',
        sm: 'lg:pt-6',
        md: 'lg:pt-12',
        lg: 'lg:pt-20',
        xl: 'lg:pt-28',
        '2xl': 'lg:pt-36',
    };

    // Si el usuario está previsualizando en un modo de dispositivo específico en el builder:
    if (vistaDispositivo.value === 'mobile') {
        const val = padTopMobile || padTopTablet || padTop;
        return mobMap[val] || 'pt-6';
    }

    if (vistaDispositivo.value === 'tablet') {
        const val = padTopTablet || padTop;
        return mobMap[val] || 'pt-12';
    }

    if (vistaDispositivo.value === 'desktop') {
        return mobMap[padTop] || 'pt-36';
    }

    // Modo público frontend (sin simulación manual de dispositivo):
    if (!padTopTablet && !padTopMobile) {
        return mobMap[padTop] || 'pt-12';
    }

    const mClass = padTopMobile ? mobMap[padTopMobile] : (padTopTablet ? mobMap[padTopTablet] : mobMap[padTop]);
    const tClass = padTopTablet ? tabMap[padTopTablet] : (padTopMobile ? tabMap[padTop] : null);
    const dClass = deskMap[padTop] || deskMap['md'];

    return [mClass, tClass, dClass].filter(Boolean).join(' ');
});

const paddingBottomClass = computed(() => {
    const padBottom = settings.value.padding_bottom || settings.value.padding || 'md';
    const padBottomTablet = settings.value.padding_bottom_tablet;
    const padBottomMobile = settings.value.padding_bottom_mobile;

    const mobMap = {
        none: 'pb-0',
        sm: 'pb-6',
        md: 'pb-12',
        lg: 'pb-20',
        xl: 'pb-28',
        '2xl': 'pb-36',
    };

    const tabMap = {
        none: 'md:pb-0',
        sm: 'md:pb-6',
        md: 'md:pb-12',
        lg: 'md:pb-20',
        xl: 'md:pb-28',
        '2xl': 'md:pb-36',
    };

    const deskMap = {
        none: 'lg:pb-0',
        sm: 'lg:pb-6',
        md: 'lg:pb-12',
        lg: 'lg:pb-20',
        xl: 'lg:pb-28',
        '2xl': 'lg:pb-36',
    };

    if (vistaDispositivo.value === 'mobile') {
        const val = padBottomMobile || padBottomTablet || padBottom;
        return mobMap[val] || 'pb-6';
    }

    if (vistaDispositivo.value === 'tablet') {
        const val = padBottomTablet || padBottom;
        return mobMap[val] || 'pb-12';
    }

    if (vistaDispositivo.value === 'desktop') {
        return mobMap[padBottom] || 'pb-36';
    }

    if (!padBottomTablet && !padBottomMobile) {
        return mobMap[padBottom] || 'pb-12';
    }

    const mClass = padBottomMobile ? mobMap[padBottomMobile] : (padBottomTablet ? mobMap[padBottomTablet] : mobMap[padBottom]);
    const tClass = padBottomTablet ? tabMap[padBottomTablet] : (padBottomMobile ? tabMap[padBottom] : null);
    const dClass = deskMap[padBottom] || deskMap['md'];

    return [mClass, tClass, dClass].filter(Boolean).join(' ');
});

const marginClass = computed(() => {
    const mar = settings.value.margin || 'none';
    const marTablet = settings.value.margin_tablet;
    const marMobile = settings.value.margin_mobile;

    const mobMap = {
        none: 'my-0',
        sm: 'my-6',
        md: 'my-12',
        lg: 'my-20',
    };

    const tabMap = {
        none: 'md:my-0',
        sm: 'md:my-6',
        md: 'md:my-12',
        lg: 'md:my-20',
    };

    const deskMap = {
        none: 'lg:my-0',
        sm: 'lg:my-6',
        md: 'lg:my-12',
        lg: 'lg:my-20',
    };

    if (vistaDispositivo.value === 'mobile') {
        const val = marMobile || marTablet || mar;
        return mobMap[val] || 'my-0';
    }

    if (vistaDispositivo.value === 'tablet') {
        const val = marTablet || mar;
        return mobMap[val] || 'my-0';
    }

    if (vistaDispositivo.value === 'desktop') {
        return mobMap[mar] || 'my-0';
    }

    if (!marTablet && !marMobile) {
        return mobMap[mar] || 'my-0';
    }

    const mClass = marMobile ? mobMap[marMobile] : (marTablet ? mobMap[marTablet] : mobMap[mar]);
    const tClass = marTablet ? tabMap[marTablet] : (marMobile ? tabMap[mar] : null);
    const dClass = deskMap[mar] || deskMap['none'];

    return [mClass, tClass, dClass].filter(Boolean).join(' ');
});

const rowGapClass = computed(() => {
    const gap = settings.value.row_gap || 'md';
    const gapTablet = settings.value.row_gap_tablet;
    const gapMobile = settings.value.row_gap_mobile;

    const mobMap = {
        none: 'space-y-0',
        sm: 'space-y-6',
        md: 'space-y-12',
        lg: 'space-y-16',
        xl: 'space-y-20',
    };

    const tabMap = {
        none: 'md:space-y-0',
        sm: 'md:space-y-6',
        md: 'md:space-y-12',
        lg: 'md:space-y-16',
        xl: 'md:space-y-20',
    };

    const deskMap = {
        none: 'lg:space-y-0',
        sm: 'lg:space-y-6',
        md: 'lg:space-y-12',
        lg: 'lg:space-y-16',
        xl: 'lg:space-y-20',
    };

    if (vistaDispositivo.value === 'mobile') {
        const val = gapMobile || gapTablet || gap;
        return mobMap[val] || 'space-y-6';
    }

    if (vistaDispositivo.value === 'tablet') {
        const val = gapTablet || gap;
        return mobMap[val] || 'space-y-12';
    }

    if (vistaDispositivo.value === 'desktop') {
        return mobMap[gap] || 'space-y-12';
    }

    if (!gapTablet && !gapMobile) {
        return mobMap[gap] || 'space-y-12';
    }

    const mClass = gapMobile ? mobMap[gapMobile] : (gapTablet ? mobMap[gapTablet] : mobMap[gap]);
    const tClass = gapTablet ? tabMap[gapTablet] : (gapMobile ? tabMap[gap] : null);
    const dClass = deskMap[gap] || deskMap['md'];

    return [mClass, tClass, dClass].filter(Boolean).join(' ');
});

const bgPresetClass = computed(() => {
    const bg = settings.value.bg_preset;
    const customBg = settings.value.bg_color || settings.value.bg_image;
    
    const presetMap = {
        transparent: 'bg-transparent text-[var(--site-text)]',
        white: 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100',
        zinc_50: 'bg-zinc-50 dark:bg-zinc-900/60 text-zinc-900 dark:text-zinc-100',
        zinc_900: 'bg-zinc-900 dark:bg-zinc-900 text-white dark',
        emerald_subtle: 'bg-emerald-50/70 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-100 border-y border-emerald-100 dark:border-emerald-900/40',
        gradient_dark: 'bg-gradient-to-br from-zinc-900 via-zinc-950 to-zinc-900 text-white dark',
        gradient_emerald: 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white dark',
    };

    if (bg && presetMap[bg]) return presetMap[bg];
    if (customBg) return '';
    return 'bg-transparent text-[var(--site-text)]';
});

// Color de texto forzado (Light / Dark / Inherit)
const textColorClass = computed(() => {
    const color = settings.value.text_color;
    if (color === 'light') return 'section-text-light !text-white dark';
    if (color === 'dark') return 'section-text-dark !text-zinc-900 light';
    return '';
});

// Alineación vertical del contenido cuando hay altura activa
const verticalAlignClass = computed(() => {
    const align = settings.value.vertical_align;
    if (!align || align === 'top') return 'flex flex-col justify-start';
    if (align === 'center') return 'flex flex-col justify-center';
    if (align === 'bottom') return 'flex flex-col justify-end';
    return 'flex flex-col justify-start';
});

// Referencia al elemento <video> y observador de visibilidad
const bgVideoRef = ref(null);
let videoObserver = null;

// Detección de Vídeo de Fondo
const hasBgVideo = computed(() => {
    return settings.value.bg_type === 'video' && Boolean(settings.value.bg_video);
});

// Clase de Posicionamiento y Efecto Pegajoso (Efecto Telón)
const positionClass = computed(() => {
    const sticky = settings.value.sticky;
    if (sticky === 'cover' || sticky === true) {
        return 'sticky top-0 lg:top-16 z-0';
    }
    if (sticky === 'fixed_top') {
        return 'sticky top-0 lg:top-16 z-30 shadow-md';
    }
    const hasMediaBg = Boolean(settings.value.bg_image) || hasBgVideo.value;
    return hasMediaBg ? 'relative z-10 overflow-hidden' : 'relative z-10';
});

// Altura calculada dinámicamente
const heightStyle = computed(() => {
    const heightType = settings.value.height_type || 'none';
    const heightPx = settings.value.height_px || 500;
    const isPreview = Boolean(vistaDispositivo.value);
    const styles = {};

    // Si la cabecera es transparente en la primera sección, la sección ya cubre desde y = 0
    const esDetrasCabecera = isFirstSection.value && settings.value.transparent_header;

    if (heightType === 'pixels') {
        styles.minHeight = `${heightPx}px`;
    } else if (heightType === 'viewport') {
        styles.minHeight = isPreview ? '100%' : '100vh';
    } else if (heightType === 'viewport_minus_header') {
        styles.minHeight = esDetrasCabecera
            ? (isPreview ? '100%' : '100vh')
            : (isPreview ? 'calc(100% - 64px)' : 'calc(100vh - 64px)');
    } else if (heightType === 'expand') {
        styles.minHeight = isPreview ? 'calc(100% - 160px)' : 'calc(100vh - 160px)';
    }

    return styles;
});

const bgHideMobileClass = computed(() => {
    return settings.value.bg_hide_mobile === 'hidden_mobile' ? 'hidden md:block' : '';
});

const bgUrls = computed(() => {
    const mainImg = settings.value.bg_image;
    if (!mainImg || typeof mainImg !== 'string') {
        return { desktop: '', tablet: '', mobile: '' };
    }
    const cleanMain = mainImg.trim();
    if (!cleanMain) {
        return { desktop: '', tablet: '', mobile: '' };
    }

    const deskUrl = imageUrl(cleanMain, { w: 1920, q: 85, f: 'webp', fit: 'scale' }) || cleanMain;
    const tabUrl = imageUrl(cleanMain, { w: 1280, q: 85, f: 'webp', fit: 'scale' }) || deskUrl;

    // Si el usuario configuró una imagen independiente para móvil:
    const mobImg = settings.value.bg_image_mobile;
    let mobUrl = '';
    if (mobImg && typeof mobImg === 'string' && mobImg.trim()) {
        mobUrl = imageUrl(mobImg.trim(), { w: 768, q: 82, f: 'webp', fit: 'scale' }) || mobImg.trim();
    } else {
        mobUrl = imageUrl(cleanMain, { w: 768, q: 82, f: 'webp', fit: 'scale' }) || deskUrl;
    }

    return {
        desktop: deskUrl,
        tablet: tabUrl,
        mobile: mobUrl,
    };
});

const formattedBgImageUrl = computed(() => {
    return bgUrls.value.desktop;
});

// Normalización de la URL del vídeo de fondo (local o remota)
const normalizedBgVideoUrl = computed(() => {
    const raw = settings.value.bg_video;
    if (!raw || typeof raw !== 'string') return '';
    const clean = raw.trim();
    if (!clean) return '';
    // Detectar si es una etiqueta dinámica sin resolver (ej: {article.extra_fields.video})
    if (clean.startsWith('{') && clean.endsWith('}')) return '';
    if (clean.startsWith('http://') || clean.startsWith('https://') || clean.startsWith('/')) {
        return clean;
    }
    return `/${clean}`;
});

// Imagen Póster o Fallback (específica o heredada de la imagen de fondo)
const effectivePosterUrl = computed(() => {
    const customPoster = settings.value.bg_video_poster;
    if (customPoster && typeof customPoster === 'string' && customPoster.trim()) {
        const clean = customPoster.trim();
        return imageUrl(clean, { w: 1920, q: 85, f: 'webp', fit: 'scale' }) || clean;
    }
    return bgUrls.value.desktop || '';
});

// Encuadre focal del vídeo (object-position)
const bgVideoPositionStyle = computed(() => {
    const pos = settings.value.bg_video_position || 'center center';
    return {
        objectPosition: pos,
    };
});

const bgImageLayerStyle = computed(() => {
    const urls = bgUrls.value;
    if (!urls.desktop) return {};

    const sz = settings.value.bg_size || 'cover';
    const pos = settings.value.bg_position || 'center center';
    const rep = settings.value.bg_repeat || 'no-repeat';
    const att = settings.value.bg_attachment || 'scroll';
    const blend = settings.value.bg_blend_mode || 'normal';
    const filter = settings.value.bg_filter || 'none';
    const customBg = settings.value.bg_color;

    // Configuración responsiva para Tablet y Móvil
    const szTab = settings.value.bg_size_tablet;
    const szMob = settings.value.bg_size_mobile;
    const posTab = settings.value.bg_position_tablet;
    const posMob = settings.value.bg_position_mobile;

    const styles = {
        backgroundRepeat: rep,
    };

    // Si estamos en la previsualización del editor con simulación manual de dispositivo:
    if (vistaDispositivo.value === 'mobile') {
        const effectiveSz = (szMob && szMob !== 'inherit') ? szMob : ((szTab && szTab !== 'inherit') ? szTab : sz);
        const effectivePos = (posMob && posMob !== 'inherit') ? posMob : ((posTab && posTab !== 'inherit') ? posTab : pos);
        styles.backgroundImage = `url(${urls.mobile})`;
        styles.backgroundSize = effectiveSz;
        styles.backgroundPosition = effectivePos;
    } else if (vistaDispositivo.value === 'tablet') {
        const effectiveSz = (szTab && szTab !== 'inherit') ? szTab : sz;
        const effectivePos = (posTab && posTab !== 'inherit') ? posTab : pos;
        styles.backgroundImage = `url(${urls.tablet})`;
        styles.backgroundSize = effectiveSz;
        styles.backgroundPosition = effectivePos;
    } else {
        // En frontend público o escritorio: variables CSS para resolución limpia vía media queries
        const effectiveTabSz = (szTab && szTab !== 'inherit') ? szTab : sz;
        const effectiveTabPos = (posTab && posTab !== 'inherit') ? posTab : pos;
        const effectiveMobSz = (szMob && szMob !== 'inherit') ? szMob : effectiveTabSz;
        const effectiveMobPos = (posMob && posMob !== 'inherit') ? posMob : effectiveTabPos;

        styles['--bg-url-desk'] = `url(${urls.desktop})`;
        styles['--bg-url-tab'] = `url(${urls.tablet})`;
        styles['--bg-url-mob'] = `url(${urls.mobile})`;

        styles['--bg-sz-desk'] = sz;
        styles['--bg-pos-desk'] = pos;
        styles['--bg-sz-tab'] = effectiveTabSz;
        styles['--bg-pos-tab'] = effectiveTabPos;
        styles['--bg-sz-mob'] = effectiveMobSz;
        styles['--bg-pos-mob'] = effectiveMobPos;
    }

    // Color base para permitir la mezcla de color con backgroundBlendMode y mixBlendMode
    if (customBg) {
        styles.backgroundColor = customBg;
    }

    if (att === 'fixed') {
        styles.backgroundAttachment = 'fixed';
    }

    if (blend && blend !== 'normal') {
        styles.backgroundBlendMode = blend;
        styles.mixBlendMode = blend;
    }

    if (filter === 'grayscale') {
        styles.filter = 'grayscale(100%)';
    } else if (filter === 'blur') {
        styles.filter = 'blur(8px)';
        styles.transform = 'scale(1.08)';
    } else if (filter === 'sepia') {
        styles.filter = 'sepia(100%)';
    } else if (filter === 'brightness_contrast') {
        styles.filter = 'brightness(1.2) contrast(1.2)';
    }

    return styles;
});

const bgStyle = computed(() => {
    const customBg = settings.value.bg_color;
    const bgPreset = settings.value.bg_preset;
    const styles = { ...heightStyle.value };

    // Si no hay preset o el preset es transparente/por defecto, aplicar el color personalizado inline
    if (customBg && (!bgPreset || bgPreset === 'transparent')) {
        styles.backgroundColor = customBg;
    }

    if (sectionAnimation.value !== 'none' && sectionDelay.value > 0) {
        styles['--cms-anim-delay'] = `${sectionDelay.value}ms`;
    }

    return styles;
});

const overlayStyle = computed(() => {
    const val = settings.value.bg_overlay;
    if (!val || typeof val !== 'string') return {};
    const clean = val.trim();
    if (clean.includes('gradient')) {
        return { background: clean };
    }
    return { backgroundColor: clean };
});

// Ciclo de vida: Inicialización segura de vídeo, Muted forzado y Observador de Visibilidad
onMounted(() => {
    if (typeof window === 'undefined') return;

    if (hasBgVideo.value && bgVideoRef.value) {
        const el = bgVideoRef.value;
        el.muted = true;
        el.defaultMuted = true;

        const rate = Number(settings.value.bg_video_playback_rate);
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
    () => [settings.value.bg_video, settings.value.bg_video_playback_rate, settings.value.bg_type],
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
    <!-- Preload LCP de póster si la primera sección tiene vídeo de fondo -->
    <Head v-if="hasBgVideo && effectivePosterUrl && isFirstSection">
        <link
            rel="preload"
            as="image"
            :href="effectivePosterUrl"
            fetchpriority="high"
        />
    </Head>
    <!-- Preload LCP optimizado de la imagen de fondo con coincidencia exacta de media queries CSS para SSR y SEO -->
    <Head v-else-if="bgUrls.desktop && isFirstSection">
        <link
            rel="preload"
            as="image"
            :href="bgUrls.mobile"
            media="(max-width: 767px)"
            fetchpriority="high"
        />
        <link
            rel="preload"
            as="image"
            :href="bgUrls.tablet"
            media="(min-width: 768px) and (max-width: 1023px)"
            fetchpriority="high"
        />
        <link
            rel="preload"
            as="image"
            :href="bgUrls.desktop"
            media="(min-width: 1024px)"
            fetchpriority="high"
        />
    </Head>

    <component
        :is="htmlTag"
        :id="customId"
        :class="[
            paddingTopClass,
            paddingBottomClass,
            marginClass,
            bgPresetClass,
            textColorClass,
            verticalAlignClass,
            positionClass,
            customClasses,
            'flow-root'
        ]"
        :style="[bgStyle, heightStyle, (sectionAnimation !== 'none' && sectionAnimMode === 'block' && sectionDelay > 0) ? { '--cms-anim-delay': `${sectionDelay}ms` } : {}]"
        :data-builder-id="section.id"
        :data-cms-animate="(sectionAnimation !== 'none' && sectionAnimMode === 'block') ? sectionAnimation : undefined"
        :data-cms-delay="(sectionAnimation !== 'none' && sectionAnimMode === 'block' && sectionDelay > 0) ? sectionDelay : undefined"
        v-bind="parsedCustomAttributes"
    >
        <component :is="'style'" v-if="customCss" v-text="customCss" />

        <!-- Capa Dedicada de Vídeo de Fondo HTML5 Silenciado y en Bucle -->
        <div
            v-if="hasBgVideo && normalizedBgVideoUrl"
            class="absolute inset-0 z-0 overflow-hidden pointer-events-none builder-video-layer"
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
                :preload="isFirstSection ? 'metadata' : 'none'"
                :class="[
                    'w-full h-full object-cover',
                    settings.bg_video_mobile === 'fallback' && (vistaDispositivo === 'mobile' ? 'hidden' : 'hidden md:block')
                ]"
                :style="bgVideoPositionStyle"
            />

            <!-- Fallback en smartphone si está configurado para ahorrar datos o en simulación móvil -->
            <div
                v-if="effectivePosterUrl && (settings.bg_video_mobile === 'fallback' || vistaDispositivo === 'mobile')"
                :class="[
                    'absolute inset-0 bg-cover bg-center',
                    vistaDispositivo === 'mobile' ? 'block' : 'md:hidden'
                ]"
                :style="{ backgroundImage: `url(${effectivePosterUrl})` }"
            />
        </div>

        <!-- Capa Dedicada de Imagen de Fondo (Con soporte 100% real para Parallax Fixed, Repetición Mosaico, Blend Modes y Filtros Visuales) -->
        <div
            v-if="!hasBgVideo && formattedBgImageUrl"
            :class="[
                'absolute inset-0 z-0 pointer-events-none builder-bg-layer',
                bgHideMobileClass
            ]"
            :style="bgImageLayerStyle"
        />

        <!-- Overlay oscuro/claro personalizable (Capa intermedia z-[1] sobre el fondo y bajo el contenido z-10) -->
        <div
            v-if="settings.bg_overlay"
            class="absolute inset-0 z-[1] pointer-events-none"
            :style="overlayStyle"
        />

        <div :class="[containerClass, rowGapClass, transparentHeaderContentOffsetClass, 'relative z-10 w-full']">
            <BuilderRow
                v-for="row in section.rows || []"
                :key="row.id"
                :row="row"
            />
        </div>
    </component>
</template>
