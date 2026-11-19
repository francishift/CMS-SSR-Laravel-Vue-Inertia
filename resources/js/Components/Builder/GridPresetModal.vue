<script setup>
/**
 * Modal de Selección Visual de Diseños de Cuadrícula (Grid Layouts).
 * Muestra 24 combinaciones vectoriales interactivas para reconfigurar la distribución de columnas.
 */
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import { GRID_PRESETS } from '@/Config/gridPresets';

const props = defineProps({
    visible: { type: Boolean, default: false },
    currentLayout: { type: String, default: '1' },
});

const emit = defineEmits(['update:visible', 'select-preset']);

const modalVisible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

function seleccionarPreset(preset) {
    emit('select-preset', preset);
    modalVisible.value = false;
}
</script>

<template>
    <Dialog
        v-model:visible="modalVisible"
        modal
        header="Selecciona un Diseño de Cuadrícula"
        class="w-full max-w-4xl mx-4"
        :pt="{
            root: { class: 'bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl overflow-hidden' },
            header: { class: 'p-5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-zinc-900 dark:text-zinc-100 font-bold' },
            content: { class: 'p-6 max-h-[75vh] overflow-y-auto' },
        }"
    >
        <div class="space-y-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Elige la proporción y el número de columnas para esta fila. Los elementos y contenidos existentes se redistribuirán automáticamente.
            </p>

            <!-- Galería de 24 Tarjetas de Layouts -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <button
                    v-for="preset in GRID_PRESETS"
                    :key="preset.id"
                    type="button"
                    @click="seleccionarPreset(preset)"
                    :class="[
                        'group flex flex-col items-center justify-between p-3.5 rounded-xl border transition-all duration-200 cursor-pointer text-center',
                        currentLayout === preset.id
                            ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-500 ring-2 ring-emerald-500/20 shadow-xs'
                            : 'bg-zinc-50/70 dark:bg-zinc-800/40 border-zinc-200/80 dark:border-zinc-700/60 hover:bg-white dark:hover:bg-zinc-800 hover:border-emerald-500/60 hover:shadow-md'
                    ]"
                >
                    <!-- Visualizador Wireframe Vectorial Proporcional -->
                    <div class="w-full h-16 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700/80 rounded-lg p-1.5 flex gap-1 items-stretch shadow-2xs group-hover:border-emerald-500/40 transition-colors">
                        <div
                            v-for="(rect, rIdx) in preset.rectangles"
                            :key="rIdx"
                            :style="{ flex: rect }"
                            :class="[
                                'h-full rounded-xs border transition-colors',
                                currentLayout === preset.id
                                    ? 'bg-emerald-500/20 dark:bg-emerald-500/30 border-emerald-500/50'
                                    : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-300/80 dark:border-zinc-700 group-hover:bg-emerald-500/10 group-hover:border-emerald-500/30'
                            ]"
                        />
                    </div>

                    <!-- Etiquetas de Identificación -->
                    <div class="mt-2.5 space-y-0.5 w-full">
                        <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors truncate">
                            {{ preset.name }}
                        </div>
                        <div class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate font-mono">
                            {{ preset.fractions.join(' + ') }}
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </Dialog>
</template>
