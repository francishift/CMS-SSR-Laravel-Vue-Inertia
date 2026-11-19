<script setup>
/**
 * Componente Fila del Page Builder.
 * Mapeo fluido y responsivo de columnas (Flexbox con soporte de 24 layouts y anchos granulares).
 * Soporta simulación reactiva de dispositivo en el maquetador visual, divisores, gaps independientes,
 * igualado de altura, ID HTML, Clases CSS y Atributos.
 */
import BuilderColumn from './BuilderColumn.vue';
import { computed, inject, ref } from 'vue';
import { filtrarColumnasVisibles, calcularLayoutEfectivo } from '@/Composables/useColumnCollapse';
import { sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    row: { type: Object, required: true },
});

const vistaDispositivo = inject('vistaDispositivo', ref('desktop'));
const sectionHasHPadding = inject('sectionHasHPadding', ref(true));

// Elemento semántico HTML
const htmlTag = computed(() => {
    const tag = props.row?.html_element || props.row?.settings?.html_element || 'div';
    return ['div', 'section', 'article', 'header', 'footer', 'nav'].includes(tag) ? tag : 'div';
});

// Ancho de contenedor e inclusión de padding horizontal condicional coherente con la sección
const rowContainer = computed(() => {
    return props.row?.container || props.row?.settings?.container || 'inherit';
});

const rowContainerClass = computed(() => {
    const cont = rowContainer.value;
    if (!cont || cont === 'inherit') {
        return 'w-full';
    }

    // Si la sección padre ya incluye padding horizontal (boxed, small, expand, full_margin),
    // la fila no lo duplica para evitar sangría doble en pantallas pequeñas.
    // Si la sección padre es full_bleed (sin padding), la fila boxed/small/expand/full_margin
    // sí aplica el padding lateral protector responsivo.
    const paddingH = sectionHasHPadding.value ? 'px-0' : 'px-4 sm:px-6 lg:px-8';

    const baseMap = {
        boxed: `max-w-7xl mx-auto ${paddingH}`,
        small: `max-w-3xl mx-auto ${paddingH}`,
        expand: `max-w-screen-2xl mx-auto ${paddingH}`,
        full_margin: `w-full ${paddingH}`,
        full_bleed: 'w-full px-0',
    };

    return baseMap[cont] || 'w-full';
});

// Personalización avanzada de código
const customId = computed(() => props.row?.element_id || props.row?.settings?.element_id || undefined);
const customClasses = computed(() => props.row?.custom_classes || props.row?.settings?.custom_classes || '');
const customCss = computed(() => sanitizeInlineCss(props.row?.custom_css || props.row?.settings?.custom_css || ''));

const parsedCustomAttributes = computed(() => {
    const raw = props.row?.custom_attributes || props.row?.settings?.custom_attributes;
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

// Gaps X e Y independientes con compensación de margen negativo para Flexbox fluido
const gapX = computed(() => {
    if (props.row?.show_dividers || props.row?.settings?.show_dividers) return 'none';
    const gx = props.row?.gap_x ?? props.row?.settings?.gap_x;
    if (gx !== undefined && gx !== null && gx !== '') return gx;
    return props.row?.gap || props.row?.settings?.gap || 'md';
});

const gapY = computed(() => {
    const gy = props.row?.gap_y ?? props.row?.settings?.gap_y;
    if (gy !== undefined && gy !== null && gy !== '') return gy;
    return props.row?.gap || props.row?.settings?.gap || 'md';
});

const rowGutterClass = computed(() => {
    const mapX = {
        none: 'mx-0',
        sm: '-mx-2 sm:-mx-3',
        md: '-mx-3 sm:-mx-4',
        lg: '-mx-4 sm:-mx-6',
        xl: '-mx-6 sm:-mx-8',
    };
    const mapY = {
        none: 'my-0',
        sm: '-my-2',
        md: '-my-3 sm:-my-4',
        lg: '-my-4 sm:-my-5',
        xl: '-my-6',
    };
    return [mapX[gapX.value] || '-mx-3 sm:-mx-4', mapY[gapY.value] || '-my-3 sm:-my-4'].join(' ');
});

const columnGutterPaddingClass = computed(() => {
    const mapX = {
        none: 'px-0',
        sm: 'px-2 sm:px-3',
        md: 'px-3 sm:px-4',
        lg: 'px-4 sm:px-6',
        xl: 'px-6 sm:px-8',
    };
    const mapY = {
        none: 'py-0',
        sm: 'py-2',
        md: 'py-3 sm:py-4',
        lg: 'py-4 sm:py-5',
        xl: 'py-6',
    };
    return [mapX[gapX.value] || 'px-3 sm:px-4', mapY[gapY.value] || 'py-3 sm:py-4'].join(' ');
});

// Márgenes Top y Bottom independientes
const marginTopClass = computed(() => {
    const mt = props.row?.margin_top ?? props.row?.settings?.margin_top;
    return {
        none: 'mt-0',
        sm: 'mt-4',
        md: 'mt-8',
        lg: 'mt-12',
        xl: 'mt-16',
        '2xl': 'mt-24',
    }[mt] || 'mt-0';
});

const marginBottomClass = computed(() => {
    const mb = props.row?.margin_bottom ?? props.row?.settings?.margin_bottom;
    return {
        none: 'mb-0',
        sm: 'mb-4',
        md: 'mb-8',
        lg: 'mb-12',
        xl: 'mb-16',
        '2xl': 'mb-24',
    }[mb] || 'mb-0';
});

// Alineación vertical de la fila (items-start, items-center, items-end, items-stretch)
const alignClass = computed(() => {
    const align = props.row?.align || props.row?.settings?.align || 'stretch';
    return {
        start: 'items-start',
        center: 'items-center',
        end: 'items-end',
        stretch: 'items-stretch',
    }[align] || 'items-stretch';
});

// Columnas visibles tras evaluar el colapso de columnas vacías
const rowAutoCollapse = computed(() => Boolean(props.row?.collapse_empty_columns || props.row?.settings?.collapse_empty_columns));
const columnasOriginales = computed(() => props.row?.columns || []);

const columnasVisibles = computed(() => {
    return filtrarColumnasVisibles(columnasOriginales.value, rowAutoCollapse.value);
});

// Layout adaptativo cuando colapsan columnas para expandir las restantes
const layoutEfectivo = computed(() => {
    return calcularLayoutEfectivo(
        props.row?.layout || '1',
        columnasOriginales.value.length,
        columnasVisibles.value.length
    );
});
</script>

<template>
    <component
        :is="htmlTag"
        :id="customId"
        :data-builder-id="row.id"
        :class="['flow-root', rowContainerClass, marginTopClass, marginBottomClass, customClasses]"
        v-bind="parsedCustomAttributes"
    >
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <div v-if="columnasVisibles.length" :class="['flex flex-wrap', rowGutterClass, alignClass]">
            <BuilderColumn
                v-for="(col, index) in columnasVisibles"
                :key="col.id"
                :column="col"
                :col-index="index"
                :row-layout="layoutEfectivo"
                :gutter-padding="columnGutterPaddingClass"
                :row-align="row.align || row.settings?.align"
                :match-height="!!(row.match_height || row.settings?.match_height)"
                :show-dividers="!!(row.show_dividers || row.settings?.show_dividers)"
                :is-first="index === 0"
                :is-last="index === (columnasVisibles.length - 1)"
            />
        </div>
    </component>
</template>
