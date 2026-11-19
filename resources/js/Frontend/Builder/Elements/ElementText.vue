<script setup>
/**
 * Elemento de Texto Enriquecido / Párrafo Avanzado para el Page Builder.
 * Aplica modificadores de alta prioridad ([&_p], [&_span], [&_li], !important) para invalidar los estilos
 * predeterminados de la librería Tailwind Typography (prose) e inyectar el tamaño y peso elegidos.
 * Soporta ID único, Clases CSS adicionales, Atributos HTML y CSS personalizado.
 */
import { computed, inject, ref } from 'vue';
import { useSanitize } from '@/Composables/useSanitize';
import { useElementAnimation } from '@/Composables/useElementAnimation';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
});

const { animAttrs, animStyle } = useElementAnimation(props);

// Simulación de dispositivo en el maquetador visual (Escritorio, Tablet, Móvil)
const vistaDispositivo = inject('vistaDispositivo', ref(null));

// Personalización avanzada
const customId = computed(() => props.content?.element_id || undefined);
const customClasses = computed(() => props.content?.custom_classes || '');
const customCss = computed(() => props.content?.custom_css || '');

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

const { sanitize } = useSanitize();

const bodyHtml = computed(() => {
    const raw = props.content?.html || props.content?.text || '';
    // Quill a veces genera &nbsp; entre todas las palabras al pegar texto externo,
    // lo que impide el word-wrap natural del navegador. Reemplazamos por espacio normal.
    const limpio = raw.replace(/&nbsp;/g, ' ');
    // Sanitizar contra XSS antes de renderizar con v-html
    return sanitize(limpio);
});

// Preset tipográfico (Lead, Muted, Estándar) adaptado a la vista de dispositivo activa
const presetClass = computed(() => {
    const preset = props.content?.style_preset;
    if (preset === 'lead') {
        if (vistaDispositivo.value === 'mobile') {
            return '!text-lg [&_p]:!text-lg !font-medium [&_p]:!font-medium text-zinc-800 dark:text-zinc-200 leading-relaxed';
        }
        return '!text-lg sm:!text-xl [&_p]:!text-lg sm:[&_p]:!text-xl !font-medium [&_p]:!font-medium text-zinc-800 dark:text-zinc-200 leading-relaxed';
    }
    if (preset === 'muted') {
        return '!text-sm [&_p]:!text-sm [&_span]:!text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed';
    }
    return '';
});

// Tamaño de fuente personalizado (anula .prose p)
const sizeClass = computed(() => {
    const size = props.content?.size;
    return {
        sm: '!text-sm [&_p]:!text-sm [&_span]:!text-sm [&_li]:!text-sm',
        base: '!text-base [&_p]:!text-base [&_span]:!text-base [&_li]:!text-base',
        lg: '!text-lg [&_p]:!text-lg [&_span]:!text-lg [&_li]:!text-lg',
        xl: '!text-xl [&_p]:!text-xl [&_span]:!text-xl [&_li]:!text-xl',
    }[size] || '';
});

// Peso tipográfico personalizado (anula .prose p)
const weightClass = computed(() => {
    const weight = props.content?.weight;
    if (!weight || weight === 'default') return '';
    return {
        light: '!font-light [&_p]:!font-light [&_span]:!font-light [&_li]:!font-light',
        normal: '!font-normal [&_p]:!font-normal [&_span]:!font-normal [&_li]:!font-normal',
        semibold: '!font-semibold [&_p]:!font-semibold [&_span]:!font-semibold [&_li]:!font-semibold',
        bold: '!font-bold [&_p]:!font-bold [&_span]:!font-bold [&_li]:!font-bold',
        extrabold: '!font-extrabold [&_p]:!font-extrabold [&_span]:!font-extrabold [&_li]:!font-extrabold',
    }[weight] || '';
});

// Alineación horizontal
const alignClass = computed(() => {
    const align = props.content?.align;
    return {
        left: 'text-left [&_p]:text-left',
        center: 'text-center [&_p]:text-center',
        right: 'text-right [&_p]:text-right',
        justify: 'text-justify [&_p]:text-justify',
    }[align] || 'text-left';
});

// Maquetación en múltiples columnas (Estilo periódico)
const columnsClass = computed(() => {
    const cols = String(props.content?.columns || '1');
    if (cols === '2') return 'columns-1 md:columns-2 gap-8';
    if (cols === '3') return 'columns-1 md:columns-2 lg:columns-3 gap-8';
    return '';
});

// Estilo de Letra Capitular (Drop Cap)
const dropCapClass = computed(() => {
    return props.content?.drop_cap ? 'first-letter:float-left first-letter:text-4xl first-letter:font-bold first-letter:mr-3 first-letter:text-[var(--primary-color,#10b981)] first-letter:leading-none [&_p:first-of-type]:first-letter:float-left [&_p:first-of-type]:first-letter:text-4xl [&_p:first-of-type]:first-letter:font-bold [&_p:first-of-type]:first-letter:mr-3 [&_p:first-of-type]:first-letter:text-[var(--primary-color,#10b981)]' : '';
});

// Color de texto
const colorClass = computed(() => {
    const col = props.content?.color;
    return {
        primary: '!text-[var(--primary-color,#10b981)] [&_p]:!text-[var(--primary-color,#10b981)] [&_span]:!text-[var(--primary-color,#10b981)]',
        secondary: '!text-[var(--secondary-color,#6366f1)] [&_p]:!text-[var(--secondary-color,#6366f1)] [&_span]:!text-[var(--secondary-color,#6366f1)]',
        accent: '!text-[var(--accent-color,#f59e0b)] [&_p]:!text-[var(--accent-color,#f59e0b)] [&_span]:!text-[var(--accent-color,#f59e0b)]',
        dark: '!text-zinc-900 dark:!text-zinc-100 [&_p]:!text-zinc-900 dark:[&_p]:!text-zinc-100',
        muted: '!text-zinc-500 dark:!text-zinc-400 [&_p]:!text-zinc-500 dark:[&_p]:!text-zinc-400',
        white: '!text-white [&_p]:!text-white [&_span]:!text-white',
        inverse: '!text-white [&_p]:!text-white [&_span]:!text-white',
        default: 'text-zinc-700 dark:text-zinc-300',
    }[col] || 'text-zinc-700 dark:text-zinc-300';
});

// Márgenes Top y Bottom independientes
const marginTopClass = computed(() => {
    const mt = props.content?.margin_top;
    return { none: 'mt-0', sm: 'mt-3', md: 'mt-6', lg: 'mt-10' }[mt] || 'mt-0';
});

const marginBottomClass = computed(() => {
    const mb = props.content?.margin_bottom;
    return { none: 'mb-0', sm: 'mb-3', md: 'mb-6', lg: 'mb-10' }[mb] || 'mb-0';
});
</script>

<template>
    <div
        :id="customId"
        :class="[
            'builder-text-element prose dark:prose-invert max-w-none min-w-0',
            presetClass,
            sizeClass,
            weightClass,
            alignClass,
            columnsClass,
            dropCapClass,
            colorClass,
            marginTopClass,
            marginBottomClass,
            customClasses
        ]"
        :style="animStyle"
        v-bind="{ ...animAttrs, ...parsedCustomAttributes }"
    >
        <component :is="'style'" v-if="customCss" v-text="customCss" />
        <div v-html="bodyHtml" />
    </div>
</template>
