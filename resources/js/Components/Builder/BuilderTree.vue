<script setup>
/**
 * Componente Árbol de Estructura de Secciones, Filas y Elementos para el Page Builder.
 * Gestiona el reordenado interactivo con vuedraggable, selecciones y adición de elementos.
 * Muestra dinámicamente los nombres/etiquetas personalizados asignados a Secciones, Filas y Elementos.
 * Diseñado con una paleta visual limpia y botones micro-píldoras elegantes (Añadir Fila, Añadir Elemento).
 */
import draggable from 'vuedraggable';
import Button from 'primevue/button';
import { ref } from 'vue';

const props = defineProps({
    layout: { type: Object, required: true },
    elementoSeleccionado: { type: Object, default: null },
});

const emit = defineEmits([
    'seleccionar',
    'añadir-seccion',
    'abrir-biblioteca-secciones',
    'guardar-biblioteca',
    'eliminar-seccion',
    'añadir-fila',
    'eliminar-fila',
    'abrir-modal-elemento',
    'eliminar-elemento',
    'refrescar-previsualizacion'
]);

function getElementLabel(type) {
    return {
        heading: 'Título / Encabezado',
        text: 'Texto Párrafo',
        image: 'Imagen Optimizada',
        button: 'Botón CTA',
        grid_articles: 'Grid de Artículos',
        accordion: 'Acordeón FAQ',
        menu: 'Menú de Navegación',
        social: 'Redes Sociales',
        html: 'Código HTML Libre',
        gallery: 'Galería de Imágenes',
        sublayout: 'Subdiseño / Sublayout',
    }[type] || type;
}

function getElementIcon(type) {
    return {
        heading: 'pi pi-heading',
        text: 'pi pi-file-edit',
        image: 'pi pi-image',
        button: 'pi pi-external-link',
        grid_articles: 'pi pi-th-large',
        accordion: 'pi pi-list',
        menu: 'pi pi-compass',
        social: 'pi pi-share-alt',
        html: 'pi pi-code',
        gallery: 'pi pi-images',
        sublayout: 'pi pi-table',
    }[type] || 'pi pi-box';
}

function getElementShortType(type) {
    return {
        heading: 'Título',
        text: 'Texto',
        image: 'Imagen',
        button: 'Botón',
        grid_articles: 'Grid',
        accordion: 'Acordeón',
        menu: 'Menú',
        social: 'Social',
        html: 'HTML',
        gallery: 'Galería',
        sublayout: 'Subdiseño',
    }[type] || type;
}

function obtenerNombreDisplayElemento(elem) {
    if (elem.name && elem.name.trim() !== '') return elem.name;
    if (elem.admin_label && elem.admin_label.trim() !== '') return elem.admin_label;
    if (elem.type === 'heading' && elem.content?.text) {
        const txt = elem.content.text.replace(/<[^>]*>?/gm, '').trim();
        if (txt) return txt.length > 12 ? txt.substring(0, 12) + '...' : txt;
    }
    return getElementShortType(elem.type);
}

function tieneNombrePersonalizado(elem) {
    const val = elem.name || elem.admin_label;
    return Boolean(val && val.trim() !== '' && val.trim() !== getElementLabel(elem.type));
}

// Control de colapso/despliegue para subdiseños en el árbol
const sublayoutsColapsados = ref(new Set());

function toggleSublayout(id) {
    if (sublayoutsColapsados.value.has(id)) {
        sublayoutsColapsados.value.delete(id);
    } else {
        sublayoutsColapsados.value.add(id);
    }
}

function estaSublayoutColapsado(id) {
    return sublayoutsColapsados.value.has(id);
}
</script>

<template>
    <div class="space-y-4 p-1">
        <!-- Cabecera de la Estructura -->
        <div class="flex items-center justify-between gap-2 flex-wrap pb-1">
            <h2 class="text-xs font-bold uppercase text-zinc-500 tracking-wider">Estructura de la Página</h2>
            <div class="flex items-center gap-1.5">
                <Button
                    label="Desde Biblioteca"
                    icon="pi pi-bookmark"
                    size="small"
                    severity="help"
                    variant="outlined"
                    v-tooltip.top="'Añadir sección completa desde tu biblioteca'"
                    @click="emit('abrir-biblioteca-secciones', null)"
                />
                <Button
                    label="Nueva Sección"
                    icon="pi pi-plus"
                    size="small"
                    severity="secondary"
                    variant="outlined"
                    v-tooltip.top="'Crear una sección en blanco'"
                    @click="emit('añadir-seccion')"
                />
            </div>
        </div>

        <!-- Estado vacío cuando no hay secciones -->
        <div v-if="!layout.sections || !layout.sections.length" class="py-10 px-4 text-center bg-zinc-50 dark:bg-zinc-900/40 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 space-y-3">
            <i class="pi pi-th-large text-3xl text-zinc-400 block opacity-60" />
            <h4 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Página sin secciones</h4>
            <p class="text-xs text-zinc-500 max-w-xs mx-auto">
                Crea una sección en blanco o inserta una sección completa que ya tengas guardada en tu biblioteca.
            </p>
            <div class="flex items-center justify-center gap-2 pt-2">
                <Button
                    label="Desde Biblioteca"
                    icon="pi pi-bookmark"
                    size="small"
                    severity="help"
                    variant="outlined"
                    @click="emit('abrir-biblioteca-secciones', null)"
                />
                <Button
                    label="Nueva Sección"
                    icon="pi pi-plus"
                    size="small"
                    severity="secondary"
                    variant="outlined"
                    @click="emit('añadir-seccion')"
                />
            </div>
        </div>

        <draggable
            v-else
            v-model="layout.sections"
            item-key="id"
            handle=".sec-drag-handle"
            animation="200"
            ghost-class="opacity-40"
            class="space-y-3"
            @end="emit('refrescar-previsualizacion')"
        >
            <template #item="{ element: sec, index: sIdx }">
                <div
                    :class="[
                        'border rounded-xl p-3 bg-white dark:bg-zinc-900 shadow-2xs transition-all',
                        elementoSeleccionado?.id === sec.id ? 'border-emerald-500 ring-2 ring-emerald-500/20 dark:ring-emerald-500/30' : 'border-zinc-200 dark:border-zinc-800'
                    ]"
                >
                    <!-- Cabecera de la Sección -->
                    <div class="flex items-center justify-between pb-2 border-b border-zinc-100 dark:border-zinc-800/80">
                        <div class="flex items-center gap-2 cursor-pointer flex-1 min-w-0" @click="emit('seleccionar', sec, 'section')">
                            <span class="sec-drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-emerald-600 p-1">
                                <i class="pi pi-bars text-xs" />
                            </span>
                            <i class="pi pi-th-large text-emerald-600 text-xs shrink-0" />
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">
                                {{ sec.name || sec.admin_label || ('Sección ' + (sIdx + 1)) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <!-- Guardar sección en biblioteca -->
                            <button
                                type="button"
                                class="p-1 rounded text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer transition-colors"
                                v-tooltip.top="'Guardar sección en Biblioteca'"
                                @click.stop="emit('guardar-biblioteca', sec)"
                            >
                                <i class="pi pi-bookmark text-xs" />
                            </button>
                            <!-- Insertar sección debajo desde biblioteca -->
                            <button
                                type="button"
                                class="p-1 rounded text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400 cursor-pointer transition-colors"
                                v-tooltip.top="'Insertar sección de biblioteca debajo'"
                                @click.stop="emit('abrir-biblioteca-secciones', sIdx + 1)"
                            >
                                <i class="pi pi-plus-circle text-xs" />
                            </button>
                            <!-- Añadir fila a la sección -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold transition-colors cursor-pointer border border-emerald-200/60 dark:border-emerald-800/60 shadow-2xs"
                                @click="emit('añadir-fila', sec)"
                            >
                                <i class="pi pi-plus text-[9px]" />
                                <span>Fila</span>
                            </button>
                            <!-- Eliminar sección -->
                            <button
                                type="button"
                                class="text-zinc-400 hover:text-red-600 p-1 rounded cursor-pointer"
                                @click.stop="emit('eliminar-seccion', sIdx)"
                                v-tooltip.top="'Eliminar Sección'"
                            >
                                <i class="pi pi-trash text-xs" />
                            </button>
                        </div>
                    </div>

                    <!-- Filas dentro de la Sección -->
                    <div class="mt-2 pl-2 border-l-2 border-zinc-100 dark:border-zinc-800 space-y-2">
                        <draggable
                            v-model="sec.rows"
                            item-key="id"
                            :group="{ name: 'rows', pull: true, put: true }"
                            handle=".row-drag-handle"
                            animation="200"
                            ghost-class="opacity-40"
                            class="space-y-2 min-h-[40px]"
                            @end="emit('refrescar-previsualizacion')"
                        >
                            <template #item="{ element: row, index: rIdx }">
                                <div
                                    :class="[
                                        'border rounded-lg p-2 bg-zinc-50/70 dark:bg-zinc-950/50 transition-all',
                                        elementoSeleccionado?.id === row.id ? 'border-emerald-500 ring-1 ring-emerald-500/30' : 'border-zinc-200/80 dark:border-zinc-800'
                                    ]"
                                >
                                    <!-- Cabecera de la Fila -->
                                    <div class="flex items-center justify-between pb-1.5 border-b border-zinc-200/50 dark:border-zinc-800/50">
                                        <div class="flex items-center gap-2 cursor-pointer flex-1 min-w-0" @click="emit('seleccionar', row, 'row')">
                                            <span class="row-drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-emerald-600 p-1">
                                                <i class="pi pi-bars text-[11px]" />
                                            </span>
                                            <i class="pi pi-list text-emerald-600 dark:text-emerald-400 text-xs shrink-0" />
                                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 truncate">
                                                {{ row.name || row.admin_label || ('Fila ' + (rIdx + 1) + ' (' + row.layout + ')') }}
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            class="text-zinc-400 hover:text-red-600 p-1 rounded cursor-pointer shrink-0"
                                            @click.stop="emit('eliminar-fila', sec, rIdx)"
                                            v-tooltip.top="'Eliminar Fila'"
                                        >
                                            <i class="pi pi-trash text-[10px]" />
                                        </button>
                                    </div>

                                    <!-- Columnas de la Fila -->
                                    <div
                                        class="mt-2 grid gap-1.5"
                                        :style="{
                                            gridTemplateColumns: (row.columns || []).length > 2
                                                ? 'repeat(auto-fit, minmax(130px, 1fr))'
                                                : `repeat(${(row.columns || []).length}, minmax(0, 1fr))`
                                        }"
                                    >
                                        <div
                                            v-for="(col, cIdx) in row.columns || []"
                                            :key="col.id"
                                            class="border border-dashed border-zinc-300 dark:border-zinc-700/80 rounded-md p-1.5 bg-white/80 dark:bg-zinc-900/80 flex flex-col min-w-0"
                                        >
                                            <!-- Cabecera de Columna -->
                                            <div
                                                :class="[
                                                    'flex items-center justify-between gap-1 mb-1.5 shrink-0 px-1 py-0.5 rounded transition-colors cursor-pointer select-none min-w-0',
                                                    elementoSeleccionado?.id === col.id ? 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-300 dark:border-emerald-700' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800/60'
                                                ]"
                                                @click="emit('seleccionar', col, 'column')"
                                            >
                                                <span class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400 uppercase flex items-center gap-1 min-w-0 truncate">
                                                    <i class="pi pi-table text-[10px] shrink-0" />
                                                    <span class="truncate">{{ (row.columns || []).length > 2 ? 'C' + (cIdx + 1) : 'Col ' + (cIdx + 1) }}</span>
                                                    <span v-if="col.name" class="font-normal normal-case text-zinc-400 truncate">({{ col.name }})</span>
                                                </span>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold transition-colors cursor-pointer border border-emerald-200/60 dark:border-emerald-800/60 shadow-2xs shrink-0"
                                                    @click.stop="emit('abrir-modal-elemento', col.id)"
                                                    v-tooltip.top="'Añadir Elemento a la Columna'"
                                                >
                                                    <i class="pi pi-plus text-[9px]" />
                                                    <span v-if="(row.columns || []).length <= 2">Añadir</span>
                                                </button>
                                            </div>

                                            <!-- Elementos dentro de la Columna -->
                                            <draggable
                                                v-model="col.elements"
                                                item-key="id"
                                                :group="{ name: 'elements', pull: true, put: true }"
                                                handle=".elem-drag-handle"
                                                animation="200"
                                                ghost-class="opacity-40"
                                                class="space-y-1 min-h-[44px] p-0.5 border border-dashed border-zinc-200 dark:border-zinc-800/80 rounded transition-colors hover:border-emerald-400 dark:hover:border-emerald-600 flex-1"
                                                @end="emit('refrescar-previsualizacion')"
                                                @change="emit('refrescar-previsualizacion')"
                                            >
                                                <template #item="{ element: elem, index: eIdx }">
                                                    <!-- CASO 1: Bloque Contenedor Subdiseño / Sublayout -->
                                                    <div
                                                        v-if="elem.type === 'sublayout'"
                                                        :class="[
                                                            'rounded-lg border transition-all text-xs select-none min-w-0 overflow-hidden my-1 shadow-2xs',
                                                            elementoSeleccionado?.id === elem.id
                                                                ? 'border-indigo-500 ring-2 ring-indigo-500/30 bg-indigo-50/40 dark:bg-indigo-950/40'
                                                                : 'border-indigo-200 dark:border-indigo-800/80 bg-indigo-50/20 dark:bg-zinc-900/90'
                                                        ]"
                                                    >
                                                        <!-- Cabecera del Subdiseño -->
                                                        <div
                                                            class="flex items-center justify-between p-1.5 bg-indigo-50/60 dark:bg-indigo-950/50 border-b border-indigo-100 dark:border-indigo-800/40 cursor-pointer"
                                                            @click="emit('seleccionar', elem, 'element')"
                                                        >
                                                            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                                                <span class="elem-drag-handle cursor-grab active:cursor-grabbing text-indigo-400 hover:text-indigo-600 p-0.5 shrink-0">
                                                                    <i class="pi pi-bars text-[10px]" />
                                                                </span>
                                                                <i class="pi pi-table text-indigo-600 dark:text-indigo-400 text-[11px] shrink-0" />
                                                                <span class="font-bold text-[11px] text-indigo-950 dark:text-indigo-200 truncate">
                                                                    {{ elem.name || elem.admin_label || ('Subdiseño (' + (elem.content?.rows?.[0]?.layout || elem.content?.layout || '2 Cols') + ')') }}
                                                                </span>
                                                                <span class="text-[9px] px-1 py-0.2 rounded bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-mono font-bold shrink-0">
                                                                    {{ (elem.content?.rows?.[0]?.columns || []).length }} cols
                                                                </span>
                                                            </div>

                                                            <div class="flex items-center gap-1 shrink-0">
                                                                <!-- Botón para plegar/desplegar subcolumnas -->
                                                                <button
                                                                    type="button"
                                                                    class="p-0.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-300 rounded cursor-pointer transition-transform"
                                                                    @click.stop="toggleSublayout(elem.id)"
                                                                    :title="estaSublayoutColapsado(elem.id) ? 'Desplegar subcolumnas' : 'Plegar subcolumnas'"
                                                                >
                                                                    <i :class="['pi text-[10px]', estaSublayoutColapsado(elem.id) ? 'pi-chevron-right' : 'pi-chevron-down']" />
                                                                </button>

                                                                <!-- Botón para eliminar el subdiseño -->
                                                                <button
                                                                    type="button"
                                                                    class="p-0.5 text-zinc-400 hover:text-red-600 rounded cursor-pointer"
                                                                    @click.stop="emit('eliminar-elemento', col, eIdx)"
                                                                    title="Eliminar Subdiseño"
                                                                >
                                                                    <i class="pi pi-times text-[10px]" />
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <!-- Subcolumnas y elementos internos (si no está colapsado) -->
                                                        <div v-show="!estaSublayoutColapsado(elem.id)" class="p-1.5 space-y-1.5 bg-white/50 dark:bg-zinc-950/40">
                                                            <template v-for="(subrow, srIdx) in (elem.content?.rows || [])" :key="subrow.id || ('subrow_' + srIdx)">
                                                                <div
                                                                    class="grid gap-1.5"
                                                                    :style="{
                                                                        gridTemplateColumns: (subrow.columns || []).length > 2
                                                                            ? 'repeat(auto-fit, minmax(110px, 1fr))'
                                                                            : `repeat(${(subrow.columns || []).length}, minmax(0, 1fr))`
                                                                    }"
                                                                >
                                                                    <div
                                                                        v-for="(subcol, scIdx) in subrow.columns || []"
                                                                        :key="subcol.id || ('subcol_' + scIdx)"
                                                                        class="border border-dashed border-indigo-200 dark:border-indigo-800/80 rounded p-1 bg-white/90 dark:bg-zinc-900/90 flex flex-col min-w-0"
                                                                    >
                                                                        <!-- Cabecera de la Subcolumna -->
                                                                        <div class="flex items-center justify-between gap-1 mb-1 px-1 py-0.5 rounded bg-indigo-50/50 dark:bg-indigo-950/30">
                                                                            <span class="text-[9px] font-bold text-indigo-700 dark:text-indigo-300 uppercase truncate">
                                                                                S-Col {{ scIdx + 1 }}
                                                                            </span>
                                                                            <button
                                                                                type="button"
                                                                                class="inline-flex items-center justify-center gap-0.5 px-1 py-0.2 rounded bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-900/60 dark:hover:bg-indigo-800 text-indigo-800 dark:text-indigo-200 text-[9px] font-bold cursor-pointer border border-indigo-200/50 dark:border-indigo-700/50 shadow-2xs shrink-0"
                                                                                @click.stop="emit('abrir-modal-elemento', subcol.id)"
                                                                                title="Añadir Elemento a esta Subcolumna"
                                                                            >
                                                                                <i class="pi pi-plus text-[8px]" />
                                                                                <span>Añadir</span>
                                                                            </button>
                                                                        </div>

                                                                        <!-- Elementos dentro de la Subcolumna (draggable del mismo grupo 'elements') -->
                                                                        <draggable
                                                                            v-model="subcol.elements"
                                                                            item-key="id"
                                                                            :group="{ name: 'elements', pull: true, put: true }"
                                                                            handle=".elem-drag-handle"
                                                                            animation="200"
                                                                            ghost-class="opacity-40"
                                                                            class="space-y-1 min-h-[36px] p-0.5 border border-dashed border-zinc-200 dark:border-zinc-800 rounded flex-1"
                                                                            @end="emit('refrescar-previsualizacion')"
                                                                            @change="emit('refrescar-previsualizacion')"
                                                                        >
                                                                            <template #item="{ element: subElem, index: seIdx }">
                                                                                <div
                                                                                    :class="[
                                                                                        'flex items-center justify-between p-1 rounded text-[11px] transition-all cursor-pointer select-none min-w-0',
                                                                                        elementoSeleccionado?.id === subElem.id
                                                                                            ? 'bg-emerald-500 text-white font-medium shadow-2xs'
                                                                                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                                                                                    ]"
                                                                                    @click="emit('seleccionar', subElem, 'element')"
                                                                                >
                                                                                    <div class="flex items-center gap-1 min-w-0 flex-1">
                                                                                        <span class="elem-drag-handle cursor-grab active:cursor-grabbing opacity-60 hover:opacity-100 p-0.5 shrink-0">
                                                                                            <i class="pi pi-bars text-[9px]" />
                                                                                        </span>
                                                                                        <i :class="[getElementIcon(subElem.type), 'text-[10px] shrink-0 opacity-85']" />
                                                                                        <span class="truncate text-[10px] font-medium flex-1 min-w-0">
                                                                                            {{ obtenerNombreDisplayElemento(subElem) }}
                                                                                        </span>
                                                                                    </div>
                                                                                    <button
                                                                                        type="button"
                                                                                        class="opacity-60 hover:opacity-100 hover:text-red-300 p-0.5 cursor-pointer ml-0.5 shrink-0"
                                                                                        @click.stop="emit('eliminar-elemento', subcol, seIdx)"
                                                                                        title="Eliminar elemento"
                                                                                    >
                                                                                        <i class="pi pi-times text-[9px]" />
                                                                                    </button>
                                                                                </div>
                                                                            </template>
                                                                        </draggable>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <!-- CASO 2: Elemento Modular Estándar -->
                                                    <div
                                                        v-else
                                                        :class="[
                                                            'flex items-center justify-between p-1 rounded text-xs transition-all cursor-pointer select-none min-w-0',
                                                            elementoSeleccionado?.id === elem.id ? 'bg-emerald-500 text-white font-medium shadow-2xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                                                        ]"
                                                        @click="emit('seleccionar', elem, 'element')"
                                                    >
                                                        <div class="flex items-center gap-1 min-w-0 flex-1">
                                                            <span class="elem-drag-handle cursor-grab active:cursor-grabbing opacity-60 hover:opacity-100 p-0.5 shrink-0">
                                                                <i class="pi pi-bars text-[10px]" />
                                                            </span>

                                                            <!-- Icono distintivo según el TIPO de elemento -->
                                                            <i
                                                                :class="[getElementIcon(elem.type), 'text-[11px] shrink-0 opacity-85']"
                                                                v-tooltip.top="getElementLabel(elem.type)"
                                                            />

                                                            <!-- Nombre / Título personalizado o tipo abreviado -->
                                                            <span class="truncate text-[10px] font-medium flex-1 min-w-0">
                                                                {{ obtenerNombreDisplayElemento(elem) }}
                                                            </span>

                                                            <!-- Micro-insignia sutil con el Tipo de Elemento si tiene nombre personalizado -->
                                                            <span
                                                                v-if="tieneNombrePersonalizado(elem)"
                                                                :class="[
                                                                    'text-[9px] px-1 py-0.2 rounded font-mono font-bold tracking-tight shrink-0 ml-0.5',
                                                                    elementoSeleccionado?.id === elem.id
                                                                        ? 'bg-emerald-600/90 text-white border border-emerald-400/30'
                                                                        : 'bg-zinc-200/80 dark:bg-zinc-700/80 text-zinc-600 dark:text-zinc-300 border border-zinc-300/40 dark:border-zinc-600/40'
                                                                ]"
                                                                v-tooltip.top="'Tipo de elemento: ' + getElementLabel(elem.type)"
                                                            >
                                                                {{ getElementShortType(elem.type) }}
                                                            </span>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            class="opacity-60 hover:opacity-100 hover:text-red-300 p-0.5 cursor-pointer ml-0.5 shrink-0"
                                                            @click.stop="emit('eliminar-elemento', col, eIdx)"
                                                        >
                                                            <i class="pi pi-times text-[10px]" />
                                                        </button>
                                                    </div>
                                                </template>
                                            </draggable>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </template>
        </draggable>
    </div>
</template>
