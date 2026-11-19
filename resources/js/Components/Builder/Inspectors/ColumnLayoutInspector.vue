<script setup>
/**
 * Inspector Granular de Diseño y Ancho de Columnas por Breakpoint.
 * Controla fracciones (1/1, 1/2, 2/3...), anchos fijos (Expandir, Auto, sm, md, lg...)
 * y orden visual por cada tamaño de pantalla (Móvil vertical, horizontal, Tablet, Escritorio, Pantalla grande).
 */
import { ref, computed, watch } from 'vue';
import Select from 'primevue/select';
import {
    COLUMN_WIDTH_GROUPS,
    getColumnWidthOptionsWithInherit,
    COLUMN_ORDER_OPTIONS,
    GRID_PRESETS
} from '@/Config/gridPresets';

const props = defineProps({
    columns: { type: Array, default: () => [] },
    rowLayout: { type: String, default: '1' },
});

const columnaActivaIndex = ref(0);

const presetActual = computed(() => {
    return GRID_PRESETS.find(p => p.id === props.rowLayout) || GRID_PRESETS[0];
});

function asegurarEstructuraColumna(col, idx) {
    if (!col) return;
    const defaultFraction = presetActual.value.fractions?.[idx] || '1/1';

    if (!col.width || typeof col.width !== 'object') {
        const prevWidth = typeof col.width === 'string' && col.width.trim() ? col.width : defaultFraction;
        col.width = {
            xs: '1/1',
            sm: 'inherit',
            md: 'inherit',
            lg: prevWidth,
            xl: 'inherit',
        };
    } else {
        if (!col.width.xs) col.width.xs = '1/1';
        if (!col.width.sm) col.width.sm = 'inherit';
        if (!col.width.md) col.width.md = 'inherit';
        if (!col.width.lg) col.width.lg = 'inherit';
        if (!col.width.xl) col.width.xl = 'inherit';
    }

    if (!col.order || typeof col.order !== 'object') {
        col.order = {
            xs: 'none',
            sm: 'none',
            md: 'none',
            lg: 'none',
            xl: 'none',
        };
    }
}

// Asegurar que todas las columnas tengan su estructura independiente inicializada
watch(() => props.columns, (cols) => {
    if (Array.isArray(cols)) {
        cols.forEach((col, i) => asegurarEstructuraColumna(col, i));
    }
}, { immediate: true, deep: false });

const columnaActual = computed(() => {
    const col = props.columns[columnaActivaIndex.value] || props.columns[0] || null;
    if (col) {
        asegurarEstructuraColumna(col, columnaActivaIndex.value);
    }
    return col;
});

function getDisplayWidthForCol(col, index) {
    if (!col) return '';
    asegurarEstructuraColumna(col, index);
    const lgVal = col.width?.lg;
    if (lgVal && lgVal !== 'inherit') return lgVal;
    return presetActual.value.fractions?.[index] || '1/2';
}

function updateWidth(bp, val) {
    if (!columnaActual.value) return;
    asegurarEstructuraColumna(columnaActual.value, columnaActivaIndex.value);
    columnaActual.value.width = {
        ...columnaActual.value.width,
        [bp]: val,
    };
}

function updateOrder(bp, val) {
    if (!columnaActual.value) return;
    asegurarEstructuraColumna(columnaActual.value, columnaActivaIndex.value);
    columnaActual.value.order = {
        ...columnaActual.value.order,
        [bp]: val,
    };
}
</script>

<template>
    <div v-if="columns && columns.length > 0" class="space-y-4">
        <!-- Selector de Columna (Pestañas de Columnas) -->
        <div>
            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                Seleccionar Columna a Configurar
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                <button
                    v-for="(col, idx) in columns"
                    :key="col.id || idx"
                    type="button"
                    @click="columnaActivaIndex = idx"
                    :class="[
                        'px-2.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-between border',
                        columnaActivaIndex === idx
                            ? 'bg-emerald-500 text-white border-emerald-600 shadow-2xs'
                            : 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:border-emerald-500/50'
                    ]"
                >
                    <span class="truncate">Columna {{ idx + 1 }}</span>
                    <span
                        :class="[
                            'text-[10px] px-1.5 py-0.5 rounded font-mono',
                            columnaActivaIndex === idx ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-900 text-zinc-500'
                        ]"
                    >
                        {{ getDisplayWidthForCol(col, idx) }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Panel de Configuración de la Columna Seleccionada -->
        <div v-if="columnaActual" class="p-3.5 bg-zinc-50/80 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-2">
                <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                    <i class="pi pi-table text-emerald-500 text-xs" />
                    <span>Diseño de Columna {{ columnaActivaIndex + 1 }}</span>
                </div>
                <span class="text-[10px] text-zinc-400 font-mono">
                    Preset: {{ presetActual.fractions?.[columnaActivaIndex] || '1/2' }}
                </span>
            </div>

            <!-- 1. Móvil en Vertical (< 640px) -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Móvil en Vertical (&lt; 640px)
                </label>
                <Select
                    :modelValue="columnaActual.width?.xs || '1/1'"
                    @update:modelValue="(val) => updateWidth('xs', val)"
                    :options="COLUMN_WIDTH_GROUPS"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- 2. Móvil en Horizontal (>= 640px / sm) -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Móvil en Horizontal (&ge; 640px)
                </label>
                <Select
                    :modelValue="columnaActual.width?.sm || 'inherit'"
                    @update:modelValue="(val) => updateWidth('sm', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- 3. Tableta en Horizontal (>= 768px / md) -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Tableta en Horizontal (&ge; 768px)
                </label>
                <Select
                    :modelValue="columnaActual.width?.md || 'inherit'"
                    @update:modelValue="(val) => updateWidth('md', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- 4. Escritorio (>= 1024px / lg) -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Escritorio (&ge; 1024px)
                </label>
                <Select
                    :modelValue="columnaActual.width?.lg || 'inherit'"
                    @update:modelValue="(val) => updateWidth('lg', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- 5. Pantalla grande (>= 1280px / xl) -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Pantalla grande (&ge; 1280px)
                </label>
                <Select
                    :modelValue="columnaActual.width?.xl || 'inherit'"
                    @update:modelValue="(val) => updateWidth('xl', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- 6. Orden Visual Responsivo -->
            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                        Orden Visual de la Columna
                    </label>
                    <span class="text-[10px] text-zinc-400">Flexbox Order</span>
                </div>

                <!-- Orden en Escritorio (>= 1024px) -->
                <div>
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mb-0.5">
                        Escritorio (&ge; 1024px)
                    </label>
                    <Select
                        :modelValue="columnaActual.order?.lg || 'none'"
                        @update:modelValue="(val) => updateOrder('lg', val)"
                        :options="COLUMN_ORDER_OPTIONS"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Orden en Tableta (>= 768px) -->
                <div>
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mb-0.5">
                        Tableta (&ge; 768px)
                    </label>
                    <Select
                        :modelValue="columnaActual.order?.md || 'none'"
                        @update:modelValue="(val) => updateOrder('md', val)"
                        :options="COLUMN_ORDER_OPTIONS"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Orden en Móvil (< 768px) -->
                <div>
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mb-0.5">
                        Móvil (&lt; 768px)
                    </label>
                    <Select
                        :modelValue="columnaActual.order?.xs || 'none'"
                        @update:modelValue="(val) => updateOrder('xs', val)"
                        :options="COLUMN_ORDER_OPTIONS"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
