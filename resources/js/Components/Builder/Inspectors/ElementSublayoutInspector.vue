<script setup>
/**
 * Inspector de Opciones para el elemento Subdiseño / Sublayout.
 * Permite configurar la distribución de subcolumnas (1/2+1/2, 1/3+1/3+1/3, etc.),
 * espaciado (gap), alineación vertical y horizontal, márgenes y gestión de elementos internos.
 */
import { ref, computed } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

const emit = defineEmits([
    'refrescar-previsualizacion',
    'abrir-modal-elemento',
    'seleccionar-elemento-hijo'
]);

function uuid() {
    return 'id_' + Math.random().toString(36).substr(2, 9);
}

// Inicializar content si no existe
if (!props.elementoSeleccionado.content) {
    props.elementoSeleccionado.content = {};
}

// Fila activa del subdiseño (por defecto la primera fila)
const primeraFila = computed(() => {
    if (!Array.isArray(props.elementoSeleccionado.content.rows) || props.elementoSeleccionado.content.rows.length === 0) {
        // Inicializar fila por defecto 1/2+1/2
        props.elementoSeleccionado.content.rows = [
            {
                id: uuid(),
                layout: '1/2+1/2',
                gap: 'md',
                align: 'center',
                justify: 'start',
                columns: [
                    { id: uuid(), width: { xs: '1/1', md: '1/2' }, elements: [] },
                    { id: uuid(), width: { xs: '1/1', md: '1/2' }, elements: [] },
                ]
            }
        ];
    }
    return props.elementoSeleccionado.content.rows[0];
});

// Layout activo
const layoutActivo = computed({
    get: () => primeraFila.value.layout || props.elementoSeleccionado.content.layout || '1/2+1/2',
    set: (nuevoLayout) => cambiarLayout(nuevoLayout)
});

// Presets comunes de columnas para subdiseños
const presetsSublayout = [
    {
        id: '1',
        label: '1 Columna',
        desc: '100%',
        cols: 1,
        bars: ['w-full'],
    },
    {
        id: '1/2+1/2',
        label: '2 Columnas',
        desc: '50% + 50%',
        cols: 2,
        bars: ['w-1/2', 'w-1/2'],
    },
    {
        id: '1/3+1/3+1/3',
        label: '3 Columnas',
        desc: '33% cada una',
        cols: 3,
        bars: ['w-1/3', 'w-1/3', 'w-1/3'],
    },
    {
        id: '1/4+1/4+1/4+1/4',
        label: '4 Columnas',
        desc: '25% cada una',
        cols: 4,
        bars: ['w-1/4', 'w-1/4', 'w-1/4', 'w-1/4'],
    },
    {
        id: '1/3+2/3',
        label: '2 Cols (1/3 + 2/3)',
        desc: '33% + 66%',
        cols: 2,
        bars: ['w-1/3', 'w-2/3'],
    },
    {
        id: '2/3+1/3',
        label: '2 Cols (2/3 + 1/3)',
        desc: '66% + 33%',
        cols: 2,
        bars: ['w-2/3', 'w-1/3'],
    },
    {
        id: '1/4+3/4',
        label: '2 Cols (1/4 + 3/4)',
        desc: '25% + 75%',
        cols: 2,
        bars: ['w-1/4', 'w-3/4'],
    },
    {
        id: '3/4+1/4',
        label: '2 Cols (3/4 + 1/4)',
        desc: '75% + 25%',
        cols: 2,
        bars: ['w-3/4', 'w-1/4'],
    },
    {
        id: 'auto',
        label: 'Auto (Contenido)',
        desc: 'Ajustado al contenido (N columnas)',
        isAuto: true,
        bars: ['w-1/4', 'w-1/4', 'w-1/4', 'w-1/4'],
    },
];

// Detectar si el subdiseño está en modo de ancho automático
const layoutEsAuto = computed(() => {
    const lay = layoutActivo.value;
    return lay === 'auto' || lay === 'auto+auto' || (typeof lay === 'string' && lay.startsWith('auto'));
});

// Comprobar si un preset visual está activo
function esPresetActivo(presetId) {
    if (presetId === 'auto') {
        return layoutEsAuto.value;
    }
    return layoutActivo.value === presetId;
}

// Cambiar la distribución de columnas sin perder elementos
function cambiarLayout(nuevoLayoutId) {
    const preset = presetsSublayout.find(p => p.id === nuevoLayoutId) || presetsSublayout[1];
    const row = primeraFila.value;

    if (!Array.isArray(row.columns)) {
        row.columns = [];
    }

    if (preset.isAuto || nuevoLayoutId === 'auto' || nuevoLayoutId === 'auto+auto') {
        // En modo auto NO destruimos columnas si ya tiene varias (ej. 3, 4 o más).
        // Si no tiene columnas o tiene menos de 2, nos aseguramos de crear al menos 2.
        if (row.columns.length < 2) {
            while (row.columns.length < 2) {
                row.columns.push({
                    id: uuid(),
                    width: { xs: 'auto', md: 'auto' },
                    elements: [],
                });
            }
        }
        row.layout = 'auto';
        props.elementoSeleccionado.content.layout = 'auto';
        emit('refrescar-previsualizacion');
        return;
    }

    const targetCols = preset.cols;
    const currentCols = row.columns.length;

    if (currentCols < targetCols) {
        // Añadir las columnas faltantes
        for (let i = currentCols; i < targetCols; i++) {
            row.columns.push({
                id: uuid(),
                width: { xs: '1/1', md: '1/2' },
                elements: [],
            });
        }
    } else if (currentCols > targetCols) {
        // Si hay que reducir columnas, preservar los elementos migrándolos a la última columna activa
        const columnasSobrantes = row.columns.splice(targetCols);
        const ultimaColumnaActiva = row.columns[targetCols - 1];
        if (ultimaColumnaActiva) {
            columnasSobrantes.forEach(col => {
                if (Array.isArray(col.elements) && col.elements.length > 0) {
                    ultimaColumnaActiva.elements.push(...col.elements);
                }
            });
        }
    }

    row.layout = nuevoLayoutId;
    props.elementoSeleccionado.content.layout = nuevoLayoutId;
    emit('refrescar-previsualizacion');
}

// Añadir una subcolumna adicional
function añadirSubcolumna() {
    const row = primeraFila.value;
    if (!Array.isArray(row.columns)) {
        row.columns = [];
    }
    row.columns.push({
        id: uuid(),
        width: { xs: 'auto', md: 'auto' },
        elements: [],
    });
    // Si no estaba en auto, pasar a auto para respetar el número flexible de columnas
    if (!layoutEsAuto.value) {
        row.layout = 'auto';
        props.elementoSeleccionado.content.layout = 'auto';
    }
    emit('refrescar-previsualizacion');
}

// Eliminar una subcolumna concreta migrando sus elementos para evitar pérdida de datos
function eliminarSubcolumna(index) {
    const row = primeraFila.value;
    if (!Array.isArray(row.columns) || row.columns.length <= 1) {
        return;
    }

    const colEliminada = row.columns.splice(index, 1)[0];
    if (colEliminada && Array.isArray(colEliminada.elements) && colEliminada.elements.length > 0) {
        const destino = row.columns[Math.max(0, index - 1)];
        if (destino) {
            if (!Array.isArray(destino.elements)) destino.elements = [];
            destino.elements.push(...colEliminada.elements);
        }
    }
    emit('refrescar-previsualizacion');
}

// Fijar directamente la cantidad de columnas en modo auto (ej. 2, 3, 4, 5, 6)
function fijarNumeroColumnasAuto(num) {
    const targetCols = Math.max(1, Math.min(8, num));
    const row = primeraFila.value;
    if (!Array.isArray(row.columns)) row.columns = [];

    row.layout = 'auto';
    props.elementoSeleccionado.content.layout = 'auto';

    while (row.columns.length < targetCols) {
        row.columns.push({
            id: uuid(),
            width: { xs: 'auto', md: 'auto' },
            elements: [],
        });
    }

    if (row.columns.length > targetCols) {
        const columnasSobrantes = row.columns.splice(targetCols);
        const ultimaColumnaActiva = row.columns[targetCols - 1];
        if (ultimaColumnaActiva) {
            columnasSobrantes.forEach(col => {
                if (Array.isArray(col.elements) && col.elements.length > 0) {
                    ultimaColumnaActiva.elements.push(...col.elements);
                }
            });
        }
    }
    emit('refrescar-previsualizacion');
}

// Opciones de Espaciado (Gap) conformes a PrimeVue 4 (sin cadenas vacías)
const opcionesGap = [
    { label: 'Ninguno (0px)', value: 'none' },
    { label: 'Muy Pequeño (6px)', value: 'xs' },
    { label: 'Pequeño (12px)', value: 'sm' },
    { label: 'Medio Estándar (20px)', value: 'md' },
    { label: 'Grande (28px)', value: 'lg' },
    { label: 'Extra Grande (40px)', value: 'xl' },
];

// Opciones de Alineación Vertical
const opcionesAlign = [
    { label: 'Arriba (Top)', value: 'start' },
    { label: 'Centro (Center)', value: 'center' },
    { label: 'Abajo (Bottom)', value: 'end' },
    { label: 'Estirar Altura (Stretch)', value: 'stretch' },
];

// Opciones de Alineación Horizontal / Distribución
const opcionesJustify = [
    { label: 'Izquierda (Start)', value: 'start' },
    { label: 'Centro (Center)', value: 'center' },
    { label: 'Derecha (End)', value: 'end' },
    { label: 'Espacio Distribuido (Between)', value: 'between' },
];

// Opciones de Márgenes
const opcionesMargen = [
    { label: 'Ninguno (0)', value: 'none' },
    { label: 'Extra Pequeño (6px)', value: 'xs' },
    { label: 'Pequeño (12px)', value: 'sm' },
    { label: 'Medio (20px)', value: 'md' },
    { label: 'Grande (32px)', value: 'lg' },
    { label: 'Extra Grande (48px)', value: 'xl' },
];

// Total de elementos dentro del subdiseño
const totalElementosInternos = computed(() => {
    let count = 0;
    for (const col of primeraFila.value.columns || []) {
        count += (col.elements || []).length;
    }
    return count;
});

function getElementIcon(type) {
    return {
        heading: 'pi pi-heading',
        text: 'pi pi-file-edit',
        image: 'pi pi-image',
        button: 'pi pi-external-link',
        icon: 'pi pi-star',
        pagination: 'pi pi-arrows-h',
        grid_articles: 'pi pi-th-large',
        accordion: 'pi pi-list',
        menu: 'pi pi-compass',
        social: 'pi pi-share-alt',
        html: 'pi pi-code',
    }[type] || 'pi pi-box';
}

function getElementLabel(type) {
    return {
        heading: 'Título',
        text: 'Texto',
        image: 'Imagen',
        button: 'Botón',
        icon: 'Icono',
        pagination: 'Paginación',
        grid_articles: 'Grid',
        accordion: 'Acordeón',
        menu: 'Menú',
        social: 'Social',
        html: 'HTML',
    }[type] || type;
}
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- Encabezado Informativo del Subdiseño -->
        <div class="p-3 bg-indigo-50/80 dark:bg-indigo-950/40 rounded-xl border border-indigo-200 dark:border-indigo-800/60 flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-2xs">
                    <i class="pi pi-table text-sm" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">
                        {{ elementoSeleccionado.name || elementoSeleccionado.admin_label || 'Subdiseño Interno' }}
                    </h3>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                        {{ (primeraFila.columns || []).length }} subcolumnas · {{ totalElementosInternos }} elementos
                    </p>
                </div>
            </div>
            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300">
                {{ layoutActivo }}
            </span>
        </div>

        <!-- BLOQUE 1: DISTRIBUCIÓN DE COLUMNAS -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="pi pi-th-large text-indigo-600 dark:text-indigo-400" />
                    <span>Diseño de Columnas</span>
                </span>
                <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                    {{ (primeraFila.columns || []).length }} columnas
                </span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="preset in presetsSublayout"
                    :key="preset.id"
                    type="button"
                    @click="cambiarLayout(preset.id)"
                    :class="[
                        'p-2 rounded-lg border text-left transition-all cursor-pointer flex flex-col gap-1.5',
                        esPresetActivo(preset.id)
                            ? 'border-indigo-600 bg-indigo-50/70 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 ring-2 ring-indigo-500/20 shadow-2xs'
                            : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-800/80 hover:border-zinc-300 dark:hover:border-zinc-700 text-zinc-700 dark:text-zinc-300'
                    ]"
                >
                    <!-- Miniatura gráfica de las columnas -->
                    <div class="h-3 w-full flex gap-0.5 bg-zinc-200 dark:bg-zinc-700/60 rounded p-0.5 overflow-hidden">
                        <div
                            v-for="(bar, bIdx) in preset.bars"
                            :key="bIdx"
                            :class="[
                                'h-full rounded-2xs transition-colors',
                                bar,
                                esPresetActivo(preset.id) ? 'bg-indigo-600 dark:bg-indigo-400' : 'bg-zinc-400 dark:bg-zinc-500'
                            ]"
                        />
                    </div>
                    <span class="text-[10px] font-bold truncate leading-tight">{{ preset.label }}</span>
                </button>
            </div>

            <!-- Panel de Control Rápido de Columnas en Modo Auto -->
            <div v-if="layoutEsAuto" class="p-3 bg-indigo-50/80 dark:bg-indigo-950/50 rounded-xl border border-indigo-200 dark:border-indigo-800/70 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-indigo-950 dark:text-indigo-200 flex items-center gap-1.5">
                        <i class="pi pi-sliders-h text-indigo-600 dark:text-indigo-400 text-xs" />
                        <span>Columnas en Modo Auto</span>
                    </span>
                    <span class="text-[10px] font-mono font-bold text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded bg-white dark:bg-zinc-800 border border-indigo-200 dark:border-indigo-800/80 shadow-2xs">
                        {{ (primeraFila.columns || []).length }} activas
                    </span>
                </div>
                <p class="text-[11px] text-zinc-600 dark:text-zinc-400 leading-normal">
                    En modo auto, cada subcolumna adapta su ancho al contenido que contiene. Puedes elegir cuántas deseas o añadir más libremente:
                </p>
                <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                    <button
                        v-for="n in [2, 3, 4, 5, 6]"
                        :key="n"
                        type="button"
                        @click="fijarNumeroColumnasAuto(n)"
                        :class="[
                            'px-2.5 py-1 text-xs font-bold rounded-lg border transition-all cursor-pointer shadow-2xs',
                            (primeraFila.columns || []).length === n
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60'
                        ]"
                    >
                        {{ n }} cols
                    </button>
                    <button
                        type="button"
                        @click="añadirSubcolumna"
                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-600 transition-all cursor-pointer shadow-2xs ml-auto"
                        title="Añadir una nueva subcolumna al subdiseño"
                    >
                        <i class="pi pi-plus text-[10px]" />
                        <span>Añadir Columna</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- BLOQUE 2: ESPACIADO Y ALINEACIÓN -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="pi pi-sliders-h text-indigo-600 dark:text-indigo-400" />
                <span>Espaciado y Alineación</span>
            </span>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Separación (Gap) -->
                <div>
                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                        Separación entre Columnas
                    </label>
                    <Select
                        v-model="primeraFila.gap"
                        :options="opcionesGap"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                        @change="() => { elementoSeleccionado.content.gap = primeraFila.gap; emit('refrescar-previsualizacion'); }"
                    />
                </div>

                <!-- Alineación Vertical -->
                <div>
                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                        Alineación Vertical
                    </label>
                    <Select
                        v-model="primeraFila.align"
                        :options="opcionesAlign"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                        @change="() => { elementoSeleccionado.content.align = primeraFila.align; emit('refrescar-previsualizacion'); }"
                    />
                </div>

                <!-- Alineación Horizontal -->
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                        Distribución Horizontal (Justify)
                    </label>
                    <Select
                        v-model="primeraFila.justify"
                        :options="opcionesJustify"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                        @change="() => { elementoSeleccionado.content.justify = primeraFila.justify; emit('refrescar-previsualizacion'); }"
                    />
                </div>

                <!-- Auto-colapsar subcolumnas vacías -->
                <div class="sm:col-span-2 flex items-center justify-between p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Auto-colapsar Columnas Vacías</span>
                        <span class="text-[11px] text-zinc-500">Oculta las columnas sin datos dinámicos y expande las restantes al 100%.</span>
                    </div>
                    <ToggleSwitch
                        :modelValue="Boolean(elementoSeleccionado.content?.collapse_empty_columns || primeraFila.collapse_empty_columns)"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.collapse_empty_columns = val;
                            primeraFila.collapse_empty_columns = val;
                            emit('refrescar-previsualizacion');
                        }"
                    />
                </div>
            </div>
        </div>

        <!-- BLOQUE 3: MÁRGENES DEL CONTENEDOR -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="pi pi-arrows-v text-indigo-600 dark:text-indigo-400" />
                <span>Márgenes del Subdiseño</span>
            </span>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                        Margen Superior
                    </label>
                    <Select
                        v-model="elementoSeleccionado.content.margin_top"
                        :options="opcionesMargen"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                        @change="emit('refrescar-previsualizacion')"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                        Margen Inferior
                    </label>
                    <Select
                        v-model="elementoSeleccionado.content.margin_bottom"
                        :options="opcionesMargen"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                        @change="emit('refrescar-previsualizacion')"
                    />
                </div>
            </div>
        </div>

        <!-- BLOQUE 4: GESTIÓN RÁPIDA DE SUBCOLUMNAS Y ELEMENTOS -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="pi pi-list text-indigo-600 dark:text-indigo-400" />
                    <span>Contenido por Subcolumna</span>
                </span>
                <Button
                    label="Nueva Subcolumna"
                    icon="pi pi-plus"
                    size="small"
                    severity="indigo"
                    variant="outlined"
                    class="!text-[10px] !py-1 !px-2.5"
                    @click="añadirSubcolumna"
                />
            </div>

            <div class="space-y-2">
                <div
                    v-for="(col, cIdx) in primeraFila.columns || []"
                    :key="col.id"
                    class="p-2.5 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <i class="pi pi-table text-indigo-500 text-[10px]" />
                            <span>Subcolumna {{ cIdx + 1 }}</span>
                            <span class="text-[10px] font-normal text-zinc-400 font-mono">({{ (col.elements || []).length }} elem.)</span>
                        </span>

                        <div class="flex items-center gap-1.5">
                            <Button
                                label="Añadir Elemento"
                                icon="pi pi-plus"
                                size="small"
                                severity="emerald"
                                variant="outlined"
                                class="!text-[10px] !py-1 !px-2"
                                @click="emit('abrir-modal-elemento', col.id)"
                            />
                            <button
                                v-if="(primeraFila.columns || []).length > 1"
                                type="button"
                                class="p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 rounded cursor-pointer transition-colors"
                                @click="eliminarSubcolumna(cIdx)"
                                title="Eliminar Subcolumna (los elementos se mueven a la columna anterior)"
                            >
                                <i class="pi pi-trash text-[11px]" />
                            </button>
                        </div>
                    </div>

                    <!-- Lista de elementos contenidos en la subcolumna -->
                    <div v-if="(col.elements || []).length > 0" class="space-y-1 pt-1">
                        <div
                            v-for="elem in col.elements"
                            :key="elem.id"
                            class="flex items-center justify-between px-2 py-1 rounded bg-zinc-50 dark:bg-zinc-800/60 text-zinc-700 dark:text-zinc-300 text-xs border border-zinc-100 dark:border-zinc-800"
                        >
                            <span class="flex items-center gap-1.5 truncate">
                                <i :class="[getElementIcon(elem.type), 'text-[10px] text-zinc-500']" />
                                <span class="truncate font-medium">{{ elem.name || elem.admin_label || elem.content?.text || getElementLabel(elem.type) }}</span>
                            </span>
                            <span class="text-[9px] px-1 rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-mono">
                                {{ getElementLabel(elem.type) }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-[10px] text-zinc-400 italic py-1">
                        Sin elementos aún. Pulsa "Añadir Elemento" o arrastra uno desde el árbol.
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOQUE 5: PERSONALIZACIÓN AVANZADA -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="pi pi-cog text-zinc-500" />
                <span>Identificación y Clases</span>
            </span>

            <div>
                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                    Nombre Administrativo (Etiqueta en el Árbol)
                </label>
                <InputText
                    v-model="elementoSeleccionado.name"
                    placeholder="Ej. Iconos de Teléfono y WhatsApp"
                    class="w-full text-xs"
                    @input="() => { elementoSeleccionado.admin_label = elementoSeleccionado.name; }"
                />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                    ID HTML Personalizado
                </label>
                <InputText
                    v-model="elementoSeleccionado.content.element_id"
                    placeholder="Ej. sublayout-iconos-contacto"
                    class="w-full text-xs font-mono"
                    @input="emit('refrescar-previsualizacion')"
                />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                    Clases CSS Adicionales
                </label>
                <InputText
                    v-model="elementoSeleccionado.content.custom_classes"
                    placeholder="Ej. my-custom-sublayout"
                    class="w-full text-xs font-mono"
                    @input="emit('refrescar-previsualizacion')"
                />
            </div>
        </div>
    </div>
</template>
