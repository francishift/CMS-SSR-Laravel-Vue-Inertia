<script setup>
/**
 * Elemento Acordeón FAQ Avanzado para el Page Builder.
 * Soporta comportamiento solitario/múltiple, primer ítem abierto por defecto,
 * estilos visuales (tarjetas, divisores limpios, tarjetas separadas boxed),
 * alineación e iconos de indicador (flecha, más/menos), tipografía, espaciado interno de respuesta y personalización avanzada.
 */
import { ref, computed, inject } from 'vue';
import { useSanitize, sanitizeInlineCss } from '@/Composables/useSanitize';
import { getHeadingClasses, getHeadingStyle } from '@/Utils/headingScale';
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

const items = computed(() => Array.isArray(props.content?.items) ? props.content.items : []);

// Opciones de comportamiento
const allowMultiple = computed(() => !!props.content?.allow_multiple);
const firstOpen = computed(() => !!props.content?.first_open);

// Estado de items abiertos (soporta solitario y múltiple)
const openIndexes = ref(firstOpen.value && items.value.length ? [0] : []);

function toggleItem(index) {
    if (allowMultiple.value) {
        if (openIndexes.value.includes(index)) {
            openIndexes.value = openIndexes.value.filter(i => i !== index);
        } else {
            openIndexes.value.push(index);
        }
    } else {
        if (openIndexes.value.includes(index)) {
            openIndexes.value = [];
        } else {
            openIndexes.value = [index];
        }
    }
}

function isItemOpen(index) {
    return openIndexes.value.includes(index);
}

// Presets visuales (Estándar, Divisores, Tarjetas Separadas)
const stylePreset = computed(() => props.content?.style_preset || 'default');
const iconAlign = computed(() => props.content?.icon_align || 'right');
const indicatorType = computed(() => props.content?.indicator_icon || 'chevron');

// Tag semántico para títulos
const titleTag = computed(() => props.content?.title_tag || 'h3');

// Escala tipográfica del título adaptada al dispositivo activo (Móvil -> Tablet -> Escritorio)
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

// Espaciado interno (padding) del contenido desplegable
const contentPaddingClass = computed(() => {
    const pad = props.content?.content_padding || 'md';
    return {
        sm: 'pt-2.5 pb-3.5 px-4',
        md: 'pt-4 pb-5 px-5',
        lg: 'pt-6 pb-7 px-6',
    }[pad] || 'pt-4 pb-5 px-5';
});

// Márgenes Top y Bottom independientes del bloque acordeón
const marginTopClass = computed(() => {
    const mt = props.content?.margin_top;
    return { none: 'mt-0', sm: 'mt-4', md: 'mt-8', lg: 'mt-12', xl: 'mt-16' }[mt] || 'mt-0';
});

const marginBottomClass = computed(() => {
    const mb = props.content?.margin_bottom;
    return { none: 'mb-0', sm: 'mb-4', md: 'mb-8', lg: 'mb-12', xl: 'mb-16' }[mb] || 'mb-0';
});
</script>

<template>
    <div
        v-if="items.length"
        :id="customId"
        :class="[
            'builder-element-accordion w-full block',
            stylePreset === 'boxed' ? 'space-y-3' : (stylePreset === 'divider' ? 'divide-y divide-zinc-200 dark:divide-zinc-800 border-y border-zinc-200 dark:border-zinc-800' : 'space-y-2'),
            marginTopClass,
            marginBottomClass,
            customClasses
        ]"
        :style="animStyle"
        v-bind="{ ...animAttrs, ...parsedCustomAttributes }"
    >
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <div
            v-for="(item, idx) in items"
            :key="idx"
            :class="[
                'transition-all duration-200 overflow-hidden',
                stylePreset === 'boxed' ? 'rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-2xs' : (stylePreset === 'default' ? 'rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900' : 'bg-transparent')
            ]"
        >
            <!-- Cabecera / Pregunta desplegable -->
            <button
                type="button"
                @click="toggleItem(idx)"
                :class="[
                    'w-full px-5 py-4 text-left font-bold text-zinc-900 dark:text-zinc-100 flex items-center justify-between gap-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors',
                    iconAlign === 'left' ? 'flex-row-reverse justify-end' : ''
                ]"
            >
                <div class="flex-1 min-w-0">
                    <span
                        v-if="item.subtitle"
                        class="text-[11px] font-bold uppercase tracking-wider text-[var(--primary-color,#10b981)] block mb-0.5"
                        v-html="sanitize(item.subtitle)"
                    />
                    <component :is="titleTag" :class="[titleSizeClass]" :style="titleStyle">
                        <i v-if="item.icon" :class="[item.icon, 'mr-2 text-[var(--primary-color,#10b981)]']" />
                        <span v-html="sanitize(item.title || ('Pregunta ' + (idx + 1)))" />
                    </component>
                </div>

                <!-- Icono Indicador (Flecha o Más/Menos) -->
                <div class="shrink-0 text-[var(--primary-color,#10b981)]">
                    <template v-if="indicatorType === 'plus_minus'">
                        <i :class="[isItemOpen(idx) ? 'pi pi-minus' : 'pi pi-plus', 'text-xs transition-transform duration-200']" />
                    </template>
                    <template v-else>
                        <i :class="['pi pi-chevron-down text-xs transition-transform duration-300', isItemOpen(idx) ? 'rotate-180' : '']" />
                    </template>
                </div>
            </button>

            <!-- Contenido desplegable -->
            <div
                v-show="isItemOpen(idx)"
                :class="[
                    'text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed border-t border-zinc-100 dark:border-zinc-800/60',
                    contentPaddingClass
                ]"
            >
                <div v-html="sanitize(item.content || '')" />
            </div>
        </div>
    </div>
</template>
