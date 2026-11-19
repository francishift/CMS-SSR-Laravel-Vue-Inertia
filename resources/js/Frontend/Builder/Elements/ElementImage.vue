<script setup>
/**
 * Elemento de Imagen para el Page Builder.
 * Integrado con el componente optimizado ResponsiveImage (WebP/AVIF, 0ms cache, 0 CLS).
 * Soporta dimensiones personalizadas en píxeles (ancho/alto máximo en px).
 * Soporta filtro de imagen blanca (monocromo blanco tipo logo) y filtros avanzados.
 * Soporta alineación (izquierda, centro, derecha) y bordes redondeados.
 */
import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';
import { computed } from 'vue';
import { useElementAnimation } from '@/Composables/useElementAnimation';
import { sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
});

const { animAttrs, animStyle } = useElementAnimation(props);

// Personalización avanzada
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

const imageSrc = computed(() => props.content?.src || '');
const imageAlt = computed(() => props.content?.alt || '');
const esImagenSvg = computed(() => {
    const clean = (imageSrc.value || '').split('?')[0].split('#')[0].toLowerCase();
    return clean.endsWith('.svg');
});
const width = computed(() => {
    if (props.content?.max_width_px || props.content?.width_px) {
        return Number(props.content?.max_width_px || props.content?.width_px) || undefined;
    }
    return esImagenSvg.value ? undefined : (props.content?.width || 1200);
});
const fit = computed(() => props.content?.fit || 'scale');

// Bordes redondeados personalizables
const roundedClass = computed(() => {
    const r = props.content?.rounded;
    return {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
        '2xl': 'rounded-2xl',
        full: 'rounded-full',
    }[r] || 'rounded-none';
});

// Alineación del contenedor
const alignContainerClass = computed(() => {
    const a = props.content?.align;
    if (a === 'center') return 'flex justify-center w-full';
    if (a === 'right') return 'flex justify-end w-full';
    if (a === 'left') return 'flex justify-start w-full';
    return 'w-full';
});

// Estilos de dimensiones en píxeles (px) compatibles con columnas tanto `w-full` como `w-auto`
const imageStyle = computed(() => {
    const styles = {};
    const mw = props.content?.max_width_px || props.content?.width_px;
    const mh = props.content?.max_height_px || props.content?.height_px;

    if (mw) {
        const valPx = typeof mw === 'number' || !isNaN(Number(mw)) ? `${mw}px` : mw;
        styles.width = valPx;
        styles.maxWidth = '100%';
    }
    if (mh) {
        styles.maxHeight = typeof mh === 'number' || !isNaN(Number(mh)) ? `${mh}px` : mh;
    }
    return styles;
});

const tieneAnchoExplicito = computed(() => Boolean(props.content?.max_width_px || props.content?.width_px));

// Filtro Monocromo Blanco (Efecto Logo) y filtros de color
const filterClass = computed(() => {
    const isWhite = props.content?.is_white || props.content?.force_white;
    const filter = props.content?.color_filter;

    if (isWhite || filter === 'white') {
        return 'brightness-0 invert';
    }
    if (filter === 'dark_white') {
        return 'dark:brightness-0 dark:invert';
    }
    if (filter === 'black') {
        return 'brightness-0';
    }
    if (filter === 'grayscale') {
        return 'grayscale';
    }
    return '';
});
</script>

<template>
    <div v-if="imageSrc" :class="[alignContainerClass]">
        <div
            :id="customId"
            :class="['relative shrink-0', roundedClass, customClasses]"
            :style="[imageStyle, animStyle]"
            v-bind="{ ...animAttrs, ...parsedCustomAttributes }"
        >
            <component :is="'style'" v-if="customCss" v-html="customCss" />
            <ResponsiveImage
                :src="imageSrc"
                :alt="imageAlt"
                :width="width"
                :fit="fit"
                sizes="(max-width: 768px) 100vw, 800px"
                :imgClass="[
                    esImagenSvg
                        ? (tieneAnchoExplicito ? 'w-full h-auto object-contain' : 'w-auto max-w-full h-auto object-contain')
                        : 'w-full h-auto object-cover',
                    roundedClass,
                    filterClass,
                ]"
            />
        </div>
    </div>
</template>
