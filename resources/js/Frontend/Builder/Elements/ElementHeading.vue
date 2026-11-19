<script setup>
/**
 * Elemento de Encabezado / Título Avanzado para el Page Builder.
 * Usa modificadores de alta prioridad (!important) para garantizar que los pesos y tamaños tipográficos
 * elegidos por el usuario invaliden cualquier estilo global de navegador o de etiquetas HTML.
 * Soporta ID único, Clases CSS adicionales, Atributos HTML y CSS dinámico personalizado.
 */
import { computed, inject, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useSanitize } from '@/Composables/useSanitize';
import { useElementAnimation } from '@/Composables/useElementAnimation';

const { sanitize } = useSanitize();

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

// Etiqueta semántica HTML
const tag = computed(() => {
    const rawTag = props.content?.tag || 'h2';
    return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'].includes(rawTag) ? rawTag : 'h2';
});

// Texto principal y subtítulo
const textContent = computed(() => props.content?.text || 'Encabezado sin título');
const subtitleText = computed(() => props.content?.subtitle || '');
const subtitlePosition = computed(() => props.content?.subtitle_position || 'below');

// Alineación del contenedor
const alignClass = computed(() => {
    const align = props.content?.align || 'left';
    return {
        left: 'text-left items-start',
        center: 'text-center items-center',
        right: 'text-right items-end',
        justify: 'text-justify items-start',
    }[align] || 'text-left items-start';
});

import { getHeadingClasses } from '@/Utils/headingScale';

// Escala tipográfica visual adaptada al dispositivo activo (Móvil -> Tablet -> Escritorio)
const sizeClass = computed(() => {
    const sizeDesk = props.content?.size || 'default';
    const sizeTab = props.content?.size_tablet || '';
    const sizeMob = props.content?.size_mobile || '';
    return getHeadingClasses(sizeDesk, sizeTab, sizeMob, vistaDispositivo.value, tag.value);
});

// Estilos inline por defecto: aplicar las variables CSS globales de tipografía
const headingStyle = computed(() => {
    const size = props.content?.size || 'default';
    const t = tag.value;
    const style = { fontFamily: "var(--heading-font), system-ui, sans-serif" };

    const levelKey = (size !== 'default') ? size : t;
    if (['hero_2xl','hero_xl','h1','h2','h3','h4','h5','h6'].includes(levelKey)) {
        style.fontSize = `var(--${levelKey}-size)`;
        style.fontWeight = `var(--${levelKey}-font-weight)`;
        style.letterSpacing = `var(--${levelKey}-letter-spacing)`;
        style.lineHeight = `var(--${levelKey}-line-height)`;
    }

    return style;
});

// Peso tipográfico (Font Weight) opcional por elemento
const weightClass = computed(() => {
    const weight = props.content?.weight;
    if (!weight || weight === 'default') return '';
    return {
        light: '!font-light',
        normal: '!font-normal',
        medium: '!font-medium',
        semibold: '!font-semibold',
        bold: '!font-bold',
        extrabold: '!font-extrabold',
        black: '!font-black',
    }[weight] || '';
});

// Transformación de texto
const transformClass = computed(() => {
    const trans = props.content?.transform;
    return {
        uppercase: 'uppercase tracking-wider',
        lowercase: 'lowercase',
        capitalize: 'capitalize',
        none: '',
    }[trans] || '';
});

// Color de texto
const colorClass = computed(() => {
    const col = props.content?.color;
    return {
        primary: 'text-[var(--primary-color,#10b981)]',
        secondary: 'text-[var(--secondary-color,#6366f1)]',
        accent: 'text-[var(--accent-color,#f59e0b)]',
        dark: 'text-zinc-900 dark:text-zinc-100',
        muted: 'text-zinc-500 dark:text-zinc-400',
        white: 'text-white',
        inverse: 'text-white',
        default: 'text-zinc-900 dark:text-zinc-100',
    }[col] || 'text-zinc-900 dark:text-zinc-100';
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

import { esNavegacionInertia } from '@/Composables/useUrlHelpers';

// Verificación de enlace del título
const linkUrl = computed(() => props.content?.link_url || '');
const isSpaLink = computed(() => {
    if (!linkUrl.value) return false;
    return esNavegacionInertia(linkUrl.value, props.content?.link_target);
});

// Verificación de enlace del subtítulo
const subtitleLinkUrl = computed(() => props.content?.subtitle_link_url || '');
const isSubtitleSpaLink = computed(() => {
    if (!subtitleLinkUrl.value) return false;
    return esNavegacionInertia(subtitleLinkUrl.value, props.content?.subtitle_link_target);
});
</script>

<template>
    <div
        :id="customId"
        :class="['builder-heading-wrapper flex flex-col', alignClass, marginTopClass, marginBottomClass, customClasses]"
        :style="animStyle"
        v-bind="{ ...animAttrs, ...parsedCustomAttributes }"
    >
        <component :is="'style'" v-if="customCss" v-text="customCss" />

        <!-- Subtítulo superior -->
        <span
            v-if="subtitleText && subtitlePosition === 'above'"
            class="text-xs sm:text-sm font-semibold tracking-wider uppercase text-[var(--primary-color,#10b981)] mb-1.5"
        >
            <template v-if="subtitleLinkUrl">
                <Link
                    v-if="isSubtitleSpaLink"
                    :href="subtitleLinkUrl"
                    class="text-inherit hover:opacity-80 transition-opacity no-underline"
                    v-html="sanitize(subtitleText)"
                />
                <a
                    v-else
                    :href="subtitleLinkUrl"
                    :target="props.content?.subtitle_link_target || '_self'"
                    class="text-inherit hover:opacity-80 transition-opacity no-underline"
                    v-html="sanitize(subtitleText)"
                />
            </template>
            <template v-else>
                <span v-html="sanitize(subtitleText)" />
            </template>
        </span>

        <!-- Título principal -->
        <component
            :is="tag"
            :class="[
                'builder-heading',
                `builder-${tag}`,
                sizeClass,
                weightClass,
                transformClass,
                colorClass,
                props.content?.decoration === 'bullet' ? 'border-l-4 border-[var(--primary-color,#10b981)] pl-4' : ''
            ]"
            :style="headingStyle"
        >
            <!-- Enlace en el título si existe -->
            <template v-if="linkUrl">
                <Link
                    v-if="isSpaLink"
                    :href="linkUrl"
                    class="text-inherit hover:opacity-85 transition-opacity no-underline"
                    v-html="sanitize(textContent)"
                />
                <a
                    v-else
                    :href="linkUrl"
                    :target="props.content?.link_target || '_self'"
                    class="text-inherit hover:opacity-85 transition-opacity no-underline"
                    v-html="sanitize(textContent)"
                />
            </template>

            <!-- Título estándar sin enlace -->
            <template v-else>
                <span v-html="sanitize(textContent)" />
            </template>
        </component>

        <!-- Decorador de línea inferior (Divider) -->
        <div
            v-if="props.content?.decoration === 'divider'"
            class="w-16 h-1 bg-[var(--primary-color,#10b981)] rounded-full mt-3 mb-1"
        />

        <!-- Subtítulo inferior -->
        <p
            v-if="subtitleText && subtitlePosition === 'below'"
            class="text-sm sm:text-base text-zinc-500 dark:text-zinc-400 mt-2 font-normal max-w-3xl"
        >
            <template v-if="subtitleLinkUrl">
                <Link
                    v-if="isSubtitleSpaLink"
                    :href="subtitleLinkUrl"
                    class="text-inherit hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors no-underline"
                    v-html="sanitize(subtitleText)"
                />
                <a
                    v-else
                    :href="subtitleLinkUrl"
                    :target="props.content?.subtitle_link_target || '_self'"
                    class="text-inherit hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors no-underline"
                    v-html="sanitize(subtitleText)"
                />
            </template>
            <template v-else>
                <span v-html="sanitize(subtitleText)" />
            </template>
        </p>
    </div>
</template>
