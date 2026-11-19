<script setup>
/**
 * Componente Sección del Page Builder.
 * Soporta control de altura (Viewport, Píxeles, Calc), alineación vertical,
 * paddings top/bottom independientes, semántica HTML, color de texto, fondos,
 * ID HTML único, Clases CSS adicionales, Atributos HTML y CSS Personalizado.
 */
import BuilderRow from './BuilderRow.vue';
import { computed, inject, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { imageUrl } from '@/Composables/useImage';
import { sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    section: { type: Object, required: true },
    index: { type: Number, default: 0 },
});

const page = usePage();
const siteSettings = computed(() => page.props?.siteSettings || {});

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
        settings.value.transparent_header_nav_color,
        settings.value.transparent_header_nav_color_dark,
    ],
    ([isFirst, isTrans, pull, navColor, navColorDark]) => {
        if (setTransparentHeaderOverride && isFirst) {
            setTransparentHeaderOverride(
                isTrans
                    ? {
                        active: true,
                        pull: Boolean(pull),
                        navColor: navColor || 'inherit',
                        navColorDark: navColorDark || 'same',
                    }
                    : { active: false, pull: false, navColor: 'inherit', navColorDark: 'same' }
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

// Personalización avanzada de código (sanitizada sin escapar selectores CSS '>' en SSR)
const customId = computed(() => settings.value.element_id || undefined);
const customClasses = computed(() => settings.value.custom_classes || '');
const customCss = computed(() => sanitizeInlineCss(settings.value.custom_css || ''));

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

// Determinar si la sección utiliza una configuración de fondo independiente para Modo Oscuro
const isCustomDarkBg = computed(() => settings.value.bg_dark_mode === 'custom');

const lightCustomBg = computed(() => {
    const val = settings.value.bg_color;
    return typeof val === 'string' ? val.trim() : '';
});

const darkCustomBg = computed(() => {
    if (!isCustomDarkBg.value) return lightCustomBg.value;
    const val = settings.value.bg_color_dark;
    return typeof val === 'string' ? val.trim() : '';
});

const hasCustomSectionBg = computed(() => {
    const bgPreset = settings.value.bg_preset;
    return (!bgPreset || bgPreset === 'transparent') && Boolean(lightCustomBg.value || darkCustomBg.value);
});

const bgPresetClass = computed(() => {
    const bg = settings.value.bg_preset;
    const hasAnyMediaOrBg = Boolean(
        lightCustomBg.value
        || settings.value.bg_image
        || settings.value.bg_video
        || (isCustomDarkBg.value && (darkCustomBg.value || settings.value.bg_image_dark || settings.value.bg_video_dark))
    );

    const presetMap = {
        transparent: hasCustomSectionBg.value
            ? 'builder-section-custom-bg text-[var(--site-text)]'
            : 'bg-transparent text-[var(--site-text)]',
        white: 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100',
        zinc_50: 'bg-zinc-50 dark:bg-zinc-900/60 text-zinc-900 dark:text-zinc-100',
        zinc_900: 'bg-zinc-900 dark:bg-zinc-900 text-white dark',
        emerald_subtle: 'bg-emerald-50/70 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-100 border-y border-emerald-100 dark:border-emerald-900/40',
        gradient_dark: 'bg-gradient-to-br from-zinc-900 via-zinc-950 to-zinc-900 text-white dark',
        gradient_emerald: 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white dark',
    };

    if (bg && presetMap[bg]) return presetMap[bg];
    if (hasCustomSectionBg.value) return 'builder-section-custom-bg text-[var(--site-text)]';
    if (hasAnyMediaOrBg) return 'text-[var(--site-text)]';
    return 'bg-transparent text-[var(--site-text)]';
});

// Color de texto forzado (Light / Dark / Inherit) con soporte independiente en Modo Oscuro
const textColorClass = computed(() => {
    const lightColor = settings.value.text_color || 'inherit';
    const rawDarkColor = settings.value.text_color_dark || 'same';
    const darkColor = (!isCustomDarkBg.value || !rawDarkColor || rawDarkColor === 'same')
        ? lightColor
        : rawDarkColor;

    if (lightColor === darkColor) {
        if (lightColor === 'light') return 'section-text-light !text-white dark';
        if (lightColor === 'dark') return 'section-text-dark !text-zinc-900 light';
        return '';
    }

    const classes = [];
    if (lightColor === 'light') classes.push('section-light-mode-text-light');
    else if (lightColor === 'dark') classes.push('section-light-mode-text-dark');

    if (darkColor === 'light') classes.push('section-dark-mode-text-light');
    else if (darkColor === 'dark') classes.push('section-dark-mode-text-dark');

    return classes.join(' ');
});

// Alineación vertical del contenido cuando hay altura activa
const verticalAlignClass = computed(() => {
    const align = settings.value.vertical_align;
    if (!align || align === 'top') return 'flex flex-col justify-start';
    if (align === 'center') return 'flex flex-col justify-center';
    if (align === 'bottom') return 'flex flex-col justify-end';
    return 'flex flex-col justify-start';
});

// Referencias a elementos <video> (Claro y Oscuro) y observadores
const bgVideoRef = ref(null);
const bgVideoDarkRef = ref(null);
let videoObserver = null;
let themeClassObserver = null;

/**
 * Resuelve URLs responsivas optimizadas en WebP para una imagen de fondo (PC, Tablet y Móvil).
 */
function resolveResponsiveBgUrls(mainImg, mobImg) {
    if (!mainImg || typeof mainImg !== 'string') {
        return { desktop: '', tablet: '', mobile: '' };
    }
    const cleanMain = mainImg.trim();
    if (!cleanMain || (cleanMain.startsWith('{') && cleanMain.endsWith('}'))) {
        return { desktop: '', tablet: '', mobile: '' };
    }

    const deskUrl = imageUrl(cleanMain, { w: 1920, q: 85, f: 'webp', fit: 'scale' }) || cleanMain;
    const tabUrl = imageUrl(cleanMain, { w: 1280, q: 85, f: 'webp', fit: 'scale' }) || deskUrl;

    let mobUrl = '';
    if (mobImg && typeof mobImg === 'string' && mobImg.trim() && !(mobImg.trim().startsWith('{') && mobImg.trim().endsWith('}'))) {
        mobUrl = imageUrl(mobImg.trim(), { w: 768, q: 82, f: 'webp', fit: 'scale' }) || mobImg.trim();
    } else {
        mobUrl = imageUrl(cleanMain, { w: 768, q: 82, f: 'webp', fit: 'scale' }) || deskUrl;
    }

    return {
        desktop: deskUrl,
        tablet: tabUrl,
        mobile: mobUrl,
    };
}

/**
 * Normaliza la URL de un vídeo de fondo (local o remota), descartando tokens dinámicos sin resolver.
 */
function normalizeVideoSrc(raw) {
    if (!raw || typeof raw !== 'string') return '';
    const clean = raw.trim();
    if (!clean || (clean.startsWith('{') && clean.endsWith('}'))) return '';
    if (clean.startsWith('http://') || clean.startsWith('https://') || clean.startsWith('/')) {
        return clean;
    }
    return `/${clean}`;
}

/**
 * Resuelve la imagen póster de un vídeo o reutiliza la imagen de fondo correspondiente.
 */
function resolvePosterUrl(customPoster, fallbackDesktopUrl) {
    if (customPoster && typeof customPoster === 'string' && customPoster.trim()) {
        const clean = customPoster.trim();
        if (!(clean.startsWith('{') && clean.endsWith('}'))) {
            return imageUrl(clean, { w: 1920, q: 85, f: 'webp', fit: 'scale' }) || clean;
        }
    }
    return fallbackDesktopUrl || '';
}

/**
 * Construye el objeto reactivo de estilos y variables CSS para una capa `.builder-bg-layer`.
 */
function computeBgImageLayerStyle(urls, cfg) {
    if (!urls.desktop) return {};

    const sz = cfg.size || 'cover';
    const pos = cfg.position || 'center center';
    const rep = cfg.repeat || 'no-repeat';
    const att = cfg.attachment || 'scroll';
    const blend = cfg.blendMode || 'normal';
    const filter = cfg.filter || 'none';
    const customBg = cfg.customBg || '';

    const szTab = cfg.sizeTablet;
    const szMob = cfg.sizeMobile;
    const posTab = cfg.posTablet;
    const posMob = cfg.posMobile;

    const styles = {
        backgroundRepeat: rep,
    };

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

    // Solo aplicar backgroundColor en la capa de imagen cuando sea un color CSS válido (no un gradiente)
    if (customBg && !customBg.includes('gradient')) {
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
}

// ─── Recursos y Estilos Multimedia: Modo Claro (o compartido si bg_dark_mode !== 'custom') ───
const bgUrls = computed(() => resolveResponsiveBgUrls(settings.value.bg_image, settings.value.bg_image_mobile));
const normalizedBgVideoUrl = computed(() => normalizeVideoSrc(settings.value.bg_video));
const hasBgVideo = computed(() => (settings.value.bg_type || 'image') === 'video' && Boolean(normalizedBgVideoUrl.value));
const hasLightBgImage = computed(() => !hasBgVideo.value && Boolean(bgUrls.value.desktop));
const effectivePosterUrl = computed(() => resolvePosterUrl(settings.value.bg_video_poster, bgUrls.value.desktop));
const bgHideMobileClass = computed(() => settings.value.bg_hide_mobile === 'hidden_mobile' ? 'hidden md:block' : '');
const bgVideoPositionStyle = computed(() => ({
    objectPosition: settings.value.bg_video_position || 'center center',
}));
const bgImageLayerStyle = computed(() => computeBgImageLayerStyle(bgUrls.value, {
    size: settings.value.bg_size,
    position: settings.value.bg_position,
    sizeTablet: settings.value.bg_size_tablet,
    posTablet: settings.value.bg_position_tablet,
    sizeMobile: settings.value.bg_size_mobile,
    posMobile: settings.value.bg_position_mobile,
    attachment: settings.value.bg_attachment,
    repeat: settings.value.bg_repeat,
    blendMode: settings.value.bg_blend_mode,
    filter: settings.value.bg_filter,
    customBg: lightCustomBg.value,
}));

// ─── Recursos y Estilos Multimedia: Modo Oscuro Independiente (bg_dark_mode === 'custom') ───
const darkBgUrls = computed(() => {
    if (!isCustomDarkBg.value) return { desktop: '', tablet: '', mobile: '' };
    return resolveResponsiveBgUrls(settings.value.bg_image_dark, settings.value.bg_image_mobile_dark);
});
const normalizedDarkBgVideoUrl = computed(() => {
    if (!isCustomDarkBg.value) return '';
    return normalizeVideoSrc(settings.value.bg_video_dark);
});
const hasDarkBgVideo = computed(() => {
    return isCustomDarkBg.value && (settings.value.bg_type_dark || 'image') === 'video' && Boolean(normalizedDarkBgVideoUrl.value);
});
const hasDarkBgImage = computed(() => {
    return isCustomDarkBg.value && !hasDarkBgVideo.value && Boolean(darkBgUrls.value.desktop);
});
const effectiveDarkPosterUrl = computed(() => resolvePosterUrl(settings.value.bg_video_poster_dark, darkBgUrls.value.desktop));
const darkBgHideMobileClass = computed(() => settings.value.bg_hide_mobile_dark === 'hidden_mobile' ? 'hidden md:block' : '');
const darkBgVideoPositionStyle = computed(() => ({
    objectPosition: settings.value.bg_video_position_dark || 'center center',
}));
const darkBgImageLayerStyle = computed(() => computeBgImageLayerStyle(darkBgUrls.value, {
    size: settings.value.bg_size_dark,
    position: settings.value.bg_position_dark,
    sizeTablet: settings.value.bg_size_tablet_dark,
    posTablet: settings.value.bg_position_tablet_dark,
    sizeMobile: settings.value.bg_size_mobile_dark,
    posMobile: settings.value.bg_position_mobile_dark,
    attachment: settings.value.bg_attachment_dark,
    repeat: settings.value.bg_repeat_dark,
    blendMode: settings.value.bg_blend_mode_dark,
    filter: settings.value.bg_filter_dark,
    customBg: darkCustomBg.value,
}));

// Clase de Posicionamiento y Efecto Pegajoso (Efecto Telón)
const positionClass = computed(() => {
    const sticky = settings.value.sticky;
    if (sticky === 'cover' || sticky === true) {
        return 'sticky top-0 lg:top-16 z-0';
    }
    if (sticky === 'fixed_top') {
        return 'sticky top-0 lg:top-16 z-30 shadow-md';
    }
    const hasMediaBg = hasLightBgImage.value || hasBgVideo.value || hasDarkBgImage.value || hasDarkBgVideo.value;
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

const bgStyle = computed(() => {
    const styles = { ...heightStyle.value };

    if (hasCustomSectionBg.value) {
        styles['--sec-bg-light'] = lightCustomBg.value || 'transparent';
        styles['--sec-bg-dark'] = darkCustomBg.value || 'transparent';
    }

    if (sectionAnimation.value !== 'none' && sectionDelay.value > 0) {
        styles['--cms-anim-delay'] = `${sectionDelay.value}ms`;
    }

    return styles;
});

// ─── Capa de Superposición (Overlay) con resolución instantánea SSR por variables CSS ───
const lightOverlay = computed(() => {
    const val = settings.value.bg_overlay;
    return typeof val === 'string' ? val.trim() : '';
});

const darkOverlay = computed(() => {
    if (!isCustomDarkBg.value) return lightOverlay.value;
    const val = settings.value.bg_overlay_dark;
    return typeof val === 'string' ? val.trim() : '';
});

const hasAnyOverlay = computed(() => Boolean(lightOverlay.value || darkOverlay.value));

const overlayVisibilityClass = computed(() => {
    if (!isCustomDarkBg.value) return '';
    if (!lightOverlay.value && darkOverlay.value) return 'builder-media-only-dark';
    if (lightOverlay.value && !darkOverlay.value) return 'builder-media-only-light';
    return '';
});

const overlayStyle = computed(() => {
    if (!hasAnyOverlay.value) return {};
    return {
        '--sec-overlay-light': lightOverlay.value || 'transparent',
        '--sec-overlay-dark': darkOverlay.value || 'transparent',
    };
});

// ─── Preload LCP Inteligente en <Head> consciente del Tema Público (Cero descargas innecesarias en SEO) ───
function buildSidePreloads(hasVid, poster, urls, hideMob, schemeMedia = '') {
    const list = [];
    const prefix = schemeMedia ? `${schemeMedia} and ` : '';

    if (hasVid && poster) {
        list.push({ href: poster, media: schemeMedia || undefined });
        return list;
    }

    if (!hasVid && urls.desktop) {
        if (hideMob !== 'hidden_mobile' && urls.mobile) {
            list.push({ href: urls.mobile, media: `${prefix}(max-width: 767px)` });
        }
        if (urls.tablet) {
            list.push({ href: urls.tablet, media: `${prefix}(min-width: 768px) and (max-width: 1023px)` });
        }
        list.push({ href: urls.desktop, media: `${prefix}(min-width: 1024px)` });
    }

    return list;
}

const preloadEntries = computed(() => {
    if (!isFirstSection.value) return [];

    // Si ambos temas comparten la misma imagen/vídeo, siempre se utiliza independientemente de localStorage
    if (!isCustomDarkBg.value) {
        return buildSidePreloads(
            hasBgVideo.value,
            effectivePosterUrl.value,
            bgUrls.value,
            settings.value.bg_hide_mobile
        );
    }

    const mode = siteSettings.value.frontend_theme_mode || 'light';

    // Cuando el tema del sitio es fijo ('light' o 'dark'), precargar con seguridad la variante garantizada.
    // En modo 'user_choice' el visitante puede sobreescribir el tema del S.O. vía localStorage, por lo que
    // no se emiten preloads especulativos para evitar descargas ociosas y avisos de consola en Chrome.
    if (mode === 'light') {
        return buildSidePreloads(
            hasBgVideo.value,
            effectivePosterUrl.value,
            bgUrls.value,
            settings.value.bg_hide_mobile
        );
    }

    if (mode === 'dark') {
        return buildSidePreloads(
            hasDarkBgVideo.value,
            effectiveDarkPosterUrl.value,
            darkBgUrls.value,
            settings.value.bg_hide_mobile_dark
        );
    }

    return [];
});

// ─── Ciclo de vida y reproducción eficiente de vídeos de fondo por tema y viewport ───
function syncVideoElement(el, rateValue, shouldBeActiveForCurrentTheme) {
    if (!el || typeof window === 'undefined') return;
    el.muted = true;
    el.defaultMuted = true;

    const rate = Number(rateValue);
    if (!isNaN(rate) && rate > 0) {
        el.playbackRate = rate;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || !shouldBeActiveForCurrentTheme) {
        el.pause();
        return;
    }

    el.play().catch(() => {});
}

function syncVideosByTheme() {
    if (typeof document === 'undefined') return;
    const isDarkDoc = document.documentElement.classList.contains('dark');

    if (hasBgVideo.value && bgVideoRef.value) {
        const activeInTheme = !isCustomDarkBg.value || !isDarkDoc;
        syncVideoElement(bgVideoRef.value, settings.value.bg_video_playback_rate, activeInTheme);
    }

    if (hasDarkBgVideo.value && bgVideoDarkRef.value) {
        syncVideoElement(bgVideoDarkRef.value, settings.value.bg_video_playback_rate_dark, isDarkDoc);
    }
}

onMounted(() => {
    if (typeof window === 'undefined') return;

    if (hasBgVideo.value || hasDarkBgVideo.value) {
        syncVideosByTheme();

        if ('IntersectionObserver' in window) {
            videoObserver = new IntersectionObserver(
                (entries) => {
                    const isDarkDoc = document.documentElement.classList.contains('dark');
                    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    entries.forEach((entry) => {
                        const el = entry.target;
                        const isDarkVideo = el === bgVideoDarkRef.value;
                        const activeForTheme = isDarkVideo ? isDarkDoc : (!isCustomDarkBg.value || !isDarkDoc);

                        if (!prefersReducedMotion && activeForTheme && entry.isIntersecting && entry.intersectionRatio > 0) {
                            el.play().catch(() => {});
                        } else {
                            el.pause();
                        }
                    });
                },
                { rootMargin: '150px 0px', threshold: [0, 0.05] }
            );

            if (bgVideoRef.value) videoObserver.observe(bgVideoRef.value);
            if (bgVideoDarkRef.value) videoObserver.observe(bgVideoDarkRef.value);
        }

        if (typeof MutationObserver !== 'undefined') {
            themeClassObserver = new MutationObserver(() => {
                syncVideosByTheme();
            });
            themeClassObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        }
    }
});

onBeforeUnmount(() => {
    if (videoObserver) {
        videoObserver.disconnect();
        videoObserver = null;
    }
    if (themeClassObserver) {
        themeClassObserver.disconnect();
        themeClassObserver = null;
    }
    for (const vidEl of [bgVideoRef.value, bgVideoDarkRef.value]) {
        if (vidEl) {
            vidEl.pause();
            vidEl.removeAttribute('src');
            vidEl.load();
        }
    }
});

watch(
    () => [
        settings.value.bg_video,
        settings.value.bg_video_playback_rate,
        settings.value.bg_type,
        settings.value.bg_dark_mode,
        settings.value.bg_video_dark,
        settings.value.bg_video_playback_rate_dark,
        settings.value.bg_type_dark,
    ],
    () => {
        if (typeof window === 'undefined') return;
        syncVideosByTheme();
    }
);
</script>

<template>
    <!-- Preload LCP optimizado para SSR y SEO (con aislamiento por esquema de color cuando hay fondos independientes) -->
    <Head v-if="preloadEntries.length">
        <link
            v-for="(entry, pIdx) in preloadEntries"
            :key="`sec-preload-${section.id}-${pIdx}`"
            rel="preload"
            as="image"
            :href="entry.href"
            :media="entry.media"
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
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <!-- Capa de Vídeo de Fondo (Modo Claro, o Compartida si bg_dark_mode !== 'custom') -->
        <div
            v-if="hasBgVideo"
            :class="[
                'absolute inset-0 z-0 overflow-hidden pointer-events-none builder-video-layer',
                isCustomDarkBg ? 'builder-media-only-light' : ''
            ]"
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

            <div
                v-if="effectivePosterUrl && (settings.bg_video_mobile === 'fallback' || vistaDispositivo === 'mobile')"
                :class="[
                    'absolute inset-0 bg-cover bg-center',
                    vistaDispositivo === 'mobile' ? 'block' : 'md:hidden'
                ]"
                :style="{ backgroundImage: `url(${effectivePosterUrl})` }"
            />
        </div>

        <!-- Capa de Vídeo de Fondo Exclusiva para Modo Oscuro (bg_dark_mode === 'custom') -->
        <div
            v-if="hasDarkBgVideo"
            class="absolute inset-0 z-0 overflow-hidden pointer-events-none builder-video-layer builder-media-only-dark"
        >
            <video
                ref="bgVideoDarkRef"
                :src="normalizedDarkBgVideoUrl"
                :poster="effectiveDarkPosterUrl || undefined"
                autoplay
                loop
                muted
                playsinline
                webkit-playsinline
                :preload="isFirstSection ? 'metadata' : 'none'"
                :class="[
                    'w-full h-full object-cover',
                    settings.bg_video_mobile_dark === 'fallback' && (vistaDispositivo === 'mobile' ? 'hidden' : 'hidden md:block')
                ]"
                :style="darkBgVideoPositionStyle"
            />

            <div
                v-if="effectiveDarkPosterUrl && (settings.bg_video_mobile_dark === 'fallback' || vistaDispositivo === 'mobile')"
                :class="[
                    'absolute inset-0 bg-cover bg-center',
                    vistaDispositivo === 'mobile' ? 'block' : 'md:hidden'
                ]"
                :style="{ backgroundImage: `url(${effectiveDarkPosterUrl})` }"
            />
        </div>

        <!-- Capa de Imagen de Fondo (Modo Claro, o Compartida si bg_dark_mode !== 'custom') -->
        <div
            v-if="hasLightBgImage"
            :class="[
                'absolute inset-0 z-0 pointer-events-none builder-bg-layer',
                bgHideMobileClass,
                isCustomDarkBg ? 'builder-media-only-light' : ''
            ]"
            :style="bgImageLayerStyle"
        />

        <!-- Capa de Imagen de Fondo Exclusiva para Modo Oscuro (bg_dark_mode === 'custom') -->
        <div
            v-if="hasDarkBgImage"
            :class="[
                'absolute inset-0 z-0 pointer-events-none builder-bg-layer builder-media-only-dark',
                darkBgHideMobileClass
            ]"
            :style="darkBgImageLayerStyle"
        />

        <!-- Overlay oscuro/claro personalizable (Resuelto por variables CSS --sec-overlay-light / --sec-overlay-dark) -->
        <div
            v-if="hasAnyOverlay"
            :class="[
                'absolute inset-0 z-[1] pointer-events-none builder-overlay-layer',
                overlayVisibilityClass
            ]"
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
