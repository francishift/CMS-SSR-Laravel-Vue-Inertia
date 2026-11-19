<script setup>
/**
 * Componente Orquestador de Inspección de Opciones del Page Builder.
 * Recibe la selección actual y renderiza dinámicamente el sub-inspector atómico correspondiente.
 * Todas las etiquetas de interfaz están traducidas a Castellano.
 */
import { computed } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import SectionInspector from './Inspectors/SectionInspector.vue';
import RowInspector from './Inspectors/RowInspector.vue';
import ColumnInspector from './Inspectors/ColumnInspector.vue';
import ElementHeadingInspector from './Inspectors/ElementHeadingInspector.vue';
import ElementTextInspector from './Inspectors/ElementTextInspector.vue';
import ElementImageInspector from './Inspectors/ElementImageInspector.vue';
import ElementVideoInspector from './Inspectors/ElementVideoInspector.vue';
import ElementButtonInspector from './Inspectors/ElementButtonInspector.vue';
import ElementIconInspector from './Inspectors/ElementIconInspector.vue';
import ElementPaginationInspector from './Inspectors/ElementPaginationInspector.vue';
import ElementGridInspector from './Inspectors/ElementGridInspector.vue';
import ElementAccordionInspector from './Inspectors/ElementAccordionInspector.vue';
import ElementMenuInspector from './Inspectors/ElementMenuInspector.vue';
import ElementSocialInspector from './Inspectors/ElementSocialInspector.vue';
import ElementHtmlInspector from './Inspectors/ElementHtmlInspector.vue';
import ElementGalleryInspector from './Inspectors/ElementGalleryInspector.vue';
import ElementSublayoutInspector from './Inspectors/ElementSublayoutInspector.vue';
import ElementMapInspector from './Inspectors/ElementMapInspector.vue';
import ElementFormInspector from './Inspectors/ElementFormInspector.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, default: null },
    elementoSeleccionadoTipo: { type: String, default: null },
    opcionesCategoriasConTodas: { type: Array, required: true },
    opcionesContenedor: { type: Array, required: true },
    opcionesAltura: { type: Array, required: true },
    opcionesAlineacionVertical: { type: Array, required: true },
    opcionesColorTexto: { type: Array, required: true },
    opcionesContrasteCabecera: { type: Array, default: () => [] },
    opcionesElementoHtml: { type: Array, required: true },
    opcionesSticky: { type: Array, required: true },
    opcionesPadding: { type: Array, required: true },
    opcionesMargin: { type: Array, required: true },
    opcionesFondos: { type: Array, required: true },
    opcionesBgSize: { type: Array, default: () => [] },
    opcionesBgPosition: { type: Array, default: () => [] },
    opcionesBgAttachment: { type: Array, default: () => [] },
    opcionesBgRepeat: { type: Array, default: () => [] },
    opcionesBgBlendMode: { type: Array, default: () => [] },
    opcionesBgFilter: { type: Array, default: () => [] },
    opcionesBgHideMobile: { type: Array, default: () => [] },
    opcionesLayoutFila: { type: Array, required: true },
});

const emit = defineEmits([
    'cambiar-layout',
    'eliminar-fila-seleccionada',
    'añadir-item-manual',
    'eliminar-item-manual',
    'start-drag-item',
    'drop-item',
    'mover-item',
    'añadir-item-acordeon',
    'eliminar-item-acordeon',
    'refrescar-previsualizacion',
    'guardar-biblioteca',
    'abrir-modal-elemento',
]);

// Etiqueta descriptiva en Castellano para la insignia superior del tipo seleccionado
const tipoLabel = computed(() => {
    if (props.elementoSeleccionadoTipo === 'section') return 'Sección';
    if (props.elementoSeleccionadoTipo === 'row') return 'Fila';
    if (props.elementoSeleccionadoTipo === 'column') return 'Columna';
    if (props.elementoSeleccionadoTipo === 'element') {
        const type = props.elementoSeleccionado?.type;
        return {
            heading: 'Título / Encabezado',
            text: 'Texto Párrafo',
            image: 'Imagen Optimizada',
            video: 'Vídeo Optimizado',
            button: 'Botón CTA',
            icon: 'Icono',
            grid_articles: 'Cuadrícula / Grid',
            accordion: 'Acordeón FAQ',
            menu: 'Menú de Navegación',
            social: 'Redes Sociales',
            html: 'Código HTML Libre',
            gallery: 'Galería de Imágenes',
            sublayout: 'Subdiseño / Sublayout',
            map: 'Mapa Interactivo',
            form: 'Formulario',
        }[type] || 'Elemento Modular';
    }
    return 'Elemento';
});

const tipoIcon = computed(() => {
    if (props.elementoSeleccionadoTipo === 'section') return 'pi pi-th-large';
    if (props.elementoSeleccionadoTipo === 'row') return 'pi pi-bars';
    if (props.elementoSeleccionadoTipo === 'column') return 'pi pi-table';
    if (props.elementoSeleccionadoTipo === 'element') {
        const type = props.elementoSeleccionado?.type;
        return {
            heading: 'pi pi-hashtag',
            text: 'pi pi-align-left',
            image: 'pi pi-image',
            button: 'pi pi-link',
            grid_articles: 'pi pi-th-large',
            accordion: 'pi pi-list',
            menu: 'pi pi-compass',
            social: 'pi pi-share-alt',
            html: 'pi pi-code',
            gallery: 'pi pi-images',
            sublayout: 'pi pi-table',
            map: 'pi pi-map-marker',
            form: 'pi pi-inbox',
        }[type] || 'pi pi-box';
    }
    return 'pi pi-box';
});

// Placeholder contextual según el tipo seleccionado
const placeholderNombre = computed(() => {
    if (props.elementoSeleccionadoTipo === 'section') return 'ej. Cabecera Hero';
    if (props.elementoSeleccionadoTipo === 'row') return 'ej. Fila de Características';
    return 'ej. Título Principal';
});
</script>

<template>
    <div class="p-1">
        <div v-if="!elementoSeleccionado" class="p-8 text-center text-zinc-400">
            <i class="pi pi-mouse text-3xl mb-2 block opacity-50" />
            <p class="text-xs">Selecciona un elemento, fila o sección en el árbol para editar sus propiedades.</p>
        </div>

        <div v-else class="space-y-4">
            <!-- Título y Etiqueta Administrativa en Castellano -->
            <div class="pb-3 border-b border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded">
                        <i :class="tipoIcon" class="mr-1 text-[10px]" />
                        <span>{{ tipoLabel }}</span>
                    </span>
                    <button
                        v-if="elementoSeleccionadoTipo === 'element' || elementoSeleccionadoTipo === 'section'"
                        type="button"
                        class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 px-2 py-0.5 rounded border border-emerald-200/60 dark:border-emerald-800/60 transition-colors cursor-pointer shadow-2xs"
                        v-tooltip.top="'Guardar este preset en tu biblioteca'"
                        @click="emit('guardar-biblioteca', elementoSeleccionado)"
                    >
                        <i class="pi pi-bookmark text-[9px]" />
                        <span>Guardar en Biblioteca</span>
                    </button>
                </div>
                <div class="mt-2">
                    <label class="block text-[11px] font-semibold text-zinc-500 mb-1">Nombre / Etiqueta Administrativa</label>
                    <InputText
                        :modelValue="elementoSeleccionado.name || elementoSeleccionado.admin_label || ''"
                        @update:modelValue="(val) => { elementoSeleccionado.name = val; elementoSeleccionado.admin_label = val; }"
                        :placeholder="placeholderNombre"
                        class="w-full text-xs font-medium"
                    />
                </div>
            </div>

            <!-- INSPECTOR ATÓMICO SEGÚN EL TIPO -->
            <SectionInspector
                v-if="elementoSeleccionadoTipo === 'section'"
                :elementoSeleccionado="elementoSeleccionado"
                :opcionesContenedor="opcionesContenedor"
                :opcionesAltura="opcionesAltura"
                :opcionesAlineacionVertical="opcionesAlineacionVertical"
                :opcionesColorTexto="opcionesColorTexto"
                :opcionesContrasteCabecera="opcionesContrasteCabecera"
                :opcionesPadding="opcionesPadding"
                :opcionesMargin="opcionesMargin"
                :opcionesElementoHtml="opcionesElementoHtml"
                :opcionesSticky="opcionesSticky"
                :opcionesFondos="opcionesFondos"
                :opcionesBgSize="opcionesBgSize"
                :opcionesBgPosition="opcionesBgPosition"
                :opcionesBgAttachment="opcionesBgAttachment"
                :opcionesBgRepeat="opcionesBgRepeat"
                :opcionesBgBlendMode="opcionesBgBlendMode"
                :opcionesBgFilter="opcionesBgFilter"
                :opcionesBgHideMobile="opcionesBgHideMobile"
            />

            <RowInspector
                v-else-if="elementoSeleccionadoTipo === 'row'"
                :elementoSeleccionado="elementoSeleccionado"
                :opcionesLayoutFila="opcionesLayoutFila"
                :opcionesContenedor="opcionesContenedor"
                @cambiar-layout="(fila, layout) => emit('cambiar-layout', fila, layout)"
                @eliminar-fila-seleccionada="emit('eliminar-fila-seleccionada')"
            />

            <ColumnInspector
                v-else-if="elementoSeleccionadoTipo === 'column'"
                :elementoSeleccionado="elementoSeleccionado"
                :opcionesAlineacionVertical="opcionesAlineacionVertical"
                :opcionesPadding="opcionesPadding"
            />

            <template v-else-if="elementoSeleccionadoTipo === 'element'">
                <ElementHeadingInspector
                    v-if="elementoSeleccionado.type === 'heading'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementTextInspector
                    v-else-if="elementoSeleccionado.type === 'text'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementImageInspector
                    v-else-if="elementoSeleccionado.type === 'image'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementVideoInspector
                    v-else-if="elementoSeleccionado.type === 'video'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementButtonInspector
                    v-else-if="elementoSeleccionado.type === 'button'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementIconInspector
                    v-else-if="elementoSeleccionado.type === 'icon'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementPaginationInspector
                    v-else-if="elementoSeleccionado.type === 'pagination'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementGridInspector
                    v-else-if="elementoSeleccionado.type === 'grid_articles' || elementoSeleccionado.type === 'grid'"
                    :elementoSeleccionado="elementoSeleccionado"
                    :opcionesCategoriasConTodas="opcionesCategoriasConTodas"
                    @añadir-item-manual="emit('añadir-item-manual')"
                    @eliminar-item-manual="(idx) => emit('eliminar-item-manual', idx)"
                    @start-drag-item="(idx) => emit('start-drag-item', idx)"
                    @drop-item="(idx) => emit('drop-item', idx)"
                    @mover-item="(idx, dir) => emit('mover-item', idx, dir)"
                />

                <ElementAccordionInspector
                    v-else-if="elementoSeleccionado.type === 'accordion'"
                    :elementoSeleccionado="elementoSeleccionado"
                    @añadir-item-acordeon="emit('añadir-item-acordeon')"
                    @eliminar-item-acordeon="(idx) => emit('eliminar-item-acordeon', idx)"
                    @refrescar-previsualizacion="emit('refrescar-previsualizacion')"
                />

                <ElementMenuInspector
                    v-else-if="elementoSeleccionado.type === 'menu'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementSocialInspector
                    v-else-if="elementoSeleccionado.type === 'social'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementHtmlInspector
                    v-else-if="elementoSeleccionado.type === 'html'"
                    :elementoSeleccionado="elementoSeleccionado"
                />

                <ElementGalleryInspector
                    v-else-if="elementoSeleccionado.type === 'gallery'"
                    :elementoSeleccionado="elementoSeleccionado"
                    :opcionesCategoriasConTodas="opcionesCategoriasConTodas"
                />

                <ElementSublayoutInspector
                    v-else-if="elementoSeleccionado.type === 'sublayout'"
                    :elementoSeleccionado="elementoSeleccionado"
                    @refrescar-previsualizacion="emit('refrescar-previsualizacion')"
                    @abrir-modal-elemento="(colId) => emit('abrir-modal-elemento', colId)"
                />

                <ElementMapInspector
                    v-else-if="elementoSeleccionado.type === 'map'"
                    :elementoSeleccionado="elementoSeleccionado"
                    :opcionesCategoriasConTodas="opcionesCategoriasConTodas"
                />

                <ElementFormInspector
                    v-else-if="elementoSeleccionado.type === 'form'"
                    :elementoSeleccionado="elementoSeleccionado"
                />
            </template>
        </div>
    </div>
</template>
