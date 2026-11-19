<script>
export default {
    name: 'ElementSublayout'
};
</script>

<script setup>
/**
 * Componente ElementSublayout del Page Builder.
 * Renderiza una cuadrícula interna de filas y subcolumnas dentro de una columna existente.
 * Soporta layouts responsivos flexibles (1/2+1/2, 1/3+1/3+1/3, etc.), gaps independientes,
 * alineación vertical y horizontal, y renderizado modular recursivo de elementos hijos.
 */
import { computed } from 'vue';
import { filtrarColumnasVisibles } from '@/Composables/useColumnCollapse';

// Elementos modulares soportados dentro de las subcolumnas
import ElementHeading from './ElementHeading.vue';
import ElementText from './ElementText.vue';
import ElementImage from './ElementImage.vue';
import ElementVideo from './ElementVideo.vue';
import ElementButton from './ElementButton.vue';
import ElementIcon from './ElementIcon.vue';
import ElementPagination from './ElementPagination.vue';
import ElementGridArticles from './ElementGridArticles.vue';
import ElementAccordion from './ElementAccordion.vue';
import ElementMenu from './ElementMenu.vue';
import ElementSocial from './ElementSocial.vue';
import ElementHtml from './ElementHtml.vue';
import ElementGallery from './ElementGallery.vue';
import ElementMap from './ElementMap.vue';
import ElementForm from './ElementForm.vue';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
    resolvedData: { type: [Object, Array], default: null },
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
    accordion: ElementAccordion,
    menu: ElementMenu,
    social: ElementSocial,
    html: ElementHtml,
    gallery: ElementGallery,
    map: ElementMap,
    form: ElementForm,
};

// Márgenes superior e inferior
const marginClass = computed(() => {
    const mtMap = {
        none: 'mt-0',
        xs: 'mt-1.5',
        sm: 'mt-3',
        md: 'mt-5',
        lg: 'mt-8',
        xl: 'mt-12',
    };
    const mbMap = {
        none: 'mb-0',
        xs: 'mb-1.5',
        sm: 'mb-3',
        md: 'mb-5',
        lg: 'mb-8',
        xl: 'mb-12',
    };
    const mt = mtMap[props.content?.margin_top] || 'mt-0';
    const mb = mbMap[props.content?.margin_bottom] || 'mb-0';
    return `${mt} ${mb}`;
});

// Lista de filas internas del subdiseño (compatibilidad si viene como objeto o array directo)
const rows = computed(() => {
    if (Array.isArray(props.content?.rows) && props.content.rows.length > 0) {
        return props.content.rows;
    }
    // Si viene en estructura simplificada directa de columnas
    if (Array.isArray(props.content?.columns) && props.content.columns.length > 0) {
        return [
            {
                id: 'subrow_auto',
                layout: props.content.layout || '1/2+1/2',
                gap: props.content.gap || 'md',
                align: props.content.align || 'center',
                justify: props.content.justify || 'start',
                columns: props.content.columns,
            }
        ];
    }
    return [];
});

// Clases de fila interna (flexbox fluido con wrap responsivo)
function getRowClasses(row) {
    const gap = row.gap || props.content?.gap || 'md';
    const align = row.align || props.content?.align || 'center';
    const justify = row.justify || props.content?.justify || 'start';

    const gapMap = {
        none: 'gap-0',
        xs: 'gap-1.5',
        sm: 'gap-2.5 sm:gap-3',
        md: 'gap-3.5 sm:gap-5',
        lg: 'gap-5 sm:gap-7',
        xl: 'gap-7 sm:gap-10',
    };

    const alignMap = {
        start: 'items-start',
        center: 'items-center',
        end: 'items-end',
        stretch: 'items-stretch',
        baseline: 'items-baseline',
    };

    const justifyMap = {
        start: 'justify-start',
        center: 'justify-center',
        end: 'justify-end',
        between: 'justify-between',
        around: 'justify-around',
    };

    return [
        'w-full flex flex-wrap',
        gapMap[gap] || 'gap-3.5 sm:gap-5',
        alignMap[align] || 'items-center',
        justifyMap[justify] || 'justify-start',
    ].join(' ');
}

// Cálculo de clases de ancho por subcolumna según el layout
function getColumnWidthClass(row, colIndex, totalCols) {
    const layout = row.layout || props.content?.layout || (totalCols === 2 ? '1/2+1/2' : (totalCols === 3 ? '1/3+1/3+1/3' : '1'));

    // Layouts predefinidos comunes
    if (layout === '1/2+1/2') {
        return 'w-full sm:w-[calc(50%-0.625rem)] flex-1 min-w-[140px]';
    }
    if (layout === '1/3+1/3+1/3') {
        return 'w-full sm:w-[calc(33.333%-0.75rem)] flex-1 min-w-[110px]';
    }
    if (layout === '1/4+1/4+1/4+1/4') {
        return 'w-full sm:w-[calc(50%-0.625rem)] md:w-[calc(25%-0.75rem)] flex-1 min-w-[100px]';
    }
    if (layout === '1/3+2/3') {
        return colIndex === 0
            ? 'w-full sm:w-[calc(33.333%-0.625rem)] min-w-[120px]'
            : 'w-full sm:w-[calc(66.666%-0.625rem)] flex-1 min-w-[160px]';
    }
    if (layout === '2/3+1/3') {
        return colIndex === 0
            ? 'w-full sm:w-[calc(66.666%-0.625rem)] flex-1 min-w-[160px]'
            : 'w-full sm:w-[calc(33.333%-0.625rem)] min-w-[120px]';
    }
    if (layout === '1/4+3/4') {
        return colIndex === 0
            ? 'w-full sm:w-[calc(25%-0.625rem)] min-w-[100px]'
            : 'w-full sm:w-[calc(75%-0.625rem)] flex-1 min-w-[180px]';
    }
    if (layout === '3/4+1/4') {
        return colIndex === 0
            ? 'w-full sm:w-[calc(75%-0.625rem)] flex-1 min-w-[180px]'
            : 'w-full sm:w-[calc(25%-0.625rem)] min-w-[100px]';
    }
    if (layout === 'auto+auto' || layout === 'auto' || (typeof layout === 'string' && layout.startsWith('auto'))) {
        return 'w-auto flex-initial';
    }

    // Por defecto: división equitativa
    if (totalCols === 1) return 'w-full';
    if (totalCols === 2) return 'w-full sm:w-[calc(50%-0.625rem)] flex-1 min-w-[140px]';
    if (totalCols === 3) return 'w-full sm:w-[calc(33.333%-0.75rem)] flex-1 min-w-[110px]';
    if (totalCols === 4) return 'w-full sm:w-[calc(50%-0.625rem)] md:w-[calc(25%-0.75rem)] flex-1';

    return 'w-full flex-1';
}

function getSubcolumnasVisibles(row) {
    const subAutoCollapse = Boolean(props.content?.collapse_empty_columns || row.collapse_empty_columns);
    return filtrarColumnasVisibles(row.columns || [], subAutoCollapse);
}
</script>

<template>
    <div
        :id="content.element_id || undefined"
        :class="[
            'sublayout-container w-full min-w-0',
            marginClass,
            content.custom_classes
        ]"
    >
        <template v-for="(row, rIdx) in rows" :key="row.id || ('subrow_' + rIdx)">
            <div :class="getRowClasses(row)" :data-builder-id="row.id">
                <template v-for="(col, cIdx) in getSubcolumnasVisibles(row)" :key="col.id || ('subcol_' + cIdx)">
                    <div
                        :class="[
                            'subcolumn-box min-w-0 box-border flex flex-col',
                            getColumnWidthClass(row, cIdx, getSubcolumnasVisibles(row).length)
                        ]"
                        :data-builder-id="col.id"
                    >
                        <div class="w-full space-y-3">
                            <template v-for="element in col.elements || []" :key="element.id">
                                <ElementSublayout
                                    v-if="element.type === 'sublayout'"
                                    :content="element.content || {}"
                                    :style="element.style || {}"
                                    :resolved-data="element.resolved_data"
                                    :data-builder-id="element.id"
                                />
                                <component
                                    v-else
                                    :is="elementComponents[element.type] || ElementText"
                                    :content="element.content || {}"
                                    :style="element.style || {}"
                                    :resolved-data="element.resolved_data"
                                    :data-builder-id="element.id"
                                />
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</template>
