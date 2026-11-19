<script setup>
import { ref, computed } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import GridPresetModal from '@/Components/Builder/GridPresetModal.vue';
import ColumnLayoutInspector from './ColumnLayoutInspector.vue';
import { GRID_PRESETS } from '@/Config/gridPresets';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
    opcionesLayoutFila: { type: Array, required: true },
    opcionesContenedor: { type: Array, default: () => [] },
});

const emit = defineEmits(['cambiar-layout', 'eliminar-fila-seleccionada']);

const modalGridVisible = ref(false);
const subPestanaFila = ref('layout'); // 'layout' | 'columnas'

const presetActivo = computed(() => {
    return GRID_PRESETS.find(p => p.id === props.elementoSeleccionado.layout) || GRID_PRESETS[0];
});

// Opciones de contenedor con soporte de herencia y valores explícitos no vacíos para PrimeVue 4
const opcionesContenedorFila = computed(() => [
    { label: 'Heredar de la Sección (Por defecto)', value: 'inherit' },
    ...(props.opcionesContenedor || []),
]);

const containerModel = computed({
    get() {
        const val = props.elementoSeleccionado?.container || props.elementoSeleccionado?.settings?.container;
        if (!val || val === '') return 'inherit';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado) {
            props.elementoSeleccionado.container = val;
            if (props.elementoSeleccionado.settings) {
                props.elementoSeleccionado.settings.container = val;
            }
        }
    }
});
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- BLOQUE 1: GRID RESPONSIVO Y SELECTOR VISUAL -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="pi pi-table text-emerald-600 dark:text-emerald-400" />
                    <span>Distribución de Columnas</span>
                </div>
                <!-- Sub-pestañas Layout / Columnas -->
                <div class="flex items-center gap-1 bg-zinc-200/70 dark:bg-zinc-800 p-0.5 rounded-lg text-[10px] font-bold">
                    <button
                        type="button"
                        @click="subPestanaFila = 'layout'"
                        :class="[
                            'px-2 py-1 rounded-md transition-all cursor-pointer',
                            subPestanaFila === 'layout' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-2xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                    >
                        Layout Fila
                    </button>
                    <button
                        type="button"
                        @click="subPestanaFila = 'columnas'"
                        :class="[
                            'px-2 py-1 rounded-md transition-all cursor-pointer',
                            subPestanaFila === 'columnas' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-2xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                    >
                        Diseño Columnas
                    </button>
                </div>
            </div>

            <!-- VISTA 1: LAYOUT GENERAL DE FILA (Presets Visuales + Desplegable) -->
            <div v-show="subPestanaFila === 'layout'" class="space-y-3">
                <!-- Tarjeta Interactiva de Diseño Actual con Botón al Modal de 24 Presets -->
                <div
                    @click="modalGridVisible = true"
                    class="group p-3 bg-white dark:bg-zinc-800/80 rounded-xl border border-zinc-200 dark:border-zinc-700/80 hover:border-emerald-500/80 transition-all cursor-pointer shadow-2xs space-y-2"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                            {{ presetActivo.name }} ({{ presetActivo.label }})
                        </span>
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                            <i class="pi pi-th-large text-[10px]" />
                            <span>Cambiar</span>
                        </span>
                    </div>

                    <!-- Miniatura Wireframe Proporcional -->
                    <div class="h-9 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700/60 rounded-lg p-1 flex gap-1 items-stretch">
                        <div
                            v-for="(rect, rIdx) in presetActivo.rectangles"
                            :key="rIdx"
                            :style="{ flex: rect }"
                            class="h-full rounded-xs bg-emerald-500/20 dark:bg-emerald-500/30 border border-emerald-500/40"
                        />
                    </div>
                </div>

                <div class="pt-1">
                    <label class="block text-xs font-semibold mb-1.5 text-zinc-700 dark:text-zinc-300">
                        O seleccionar de la lista rápida:
                    </label>
                    <Select
                        :modelValue="elementoSeleccionado.layout || '1'"
                        @update:modelValue="(val) => emit('cambiar-layout', elementoSeleccionado, val)"
                        :options="opcionesLayoutFila"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Ancho de Contenedor de la Fila (Coherente con las opciones de sección) -->
                <div class="pt-2.5 border-t border-zinc-200 dark:border-zinc-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Ancho del Contenedor
                        </label>
                        <span class="text-[10px] text-zinc-400 font-mono">
                            {{ containerModel === 'inherit' ? 'Heredado' : containerModel }}
                        </span>
                    </div>
                    <Select
                        v-model="containerModel"
                        :options="opcionesContenedorFila"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                    <p class="text-[11px] text-zinc-500 leading-tight">
                        Permite reducir el ancho de esta fila dentro de secciones full-width o amplias (ej. Boxed, Pequeño, Expandido).
                    </p>
                </div>
            </div>

            <!-- VISTA 2: DISEÑO GRANULAR DE COLUMNAS -->
            <div v-show="subPestanaFila === 'columnas'">
                <ColumnLayoutInspector
                    :columns="elementoSeleccionado.columns || []"
                    :row-layout="elementoSeleccionado.layout || '1'"
                />
            </div>
        </div>

        <!-- MODAL VISUAL DE 24 PRESETS DE CUADRÍCULA -->
        <GridPresetModal
            v-model:visible="modalGridVisible"
            :current-layout="elementoSeleccionado.layout || '1'"
            @select-preset="(preset) => emit('cambiar-layout', elementoSeleccionado, preset.id)"
        />

        <!-- BLOQUE 2: GAPS E ESPACIADOS INDEPENDIENTES -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-[arrow-right-left] text-emerald-600 dark:text-emerald-400" />
                <span>Espaciado entre Columnas (Gaps)</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Espacio Horizontal (Gap X)</label>
                    <Select
                        :modelValue="elementoSeleccionado.gap_x || ''"
                        @update:modelValue="(val) => elementoSeleccionado.gap_x = val"
                        :options="[
                            { label: 'Heredado (Mediano 32px)', value: '' },
                            { label: 'Sin espacio (0px)', value: 'none' },
                            { label: 'Pequeño (16px)', value: 'sm' },
                            { label: 'Mediano (32px)', value: 'md' },
                            { label: 'Grande (48px)', value: 'lg' },
                            { label: 'Extra Grande (64px)', value: 'xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Espacio Vertical (Gap Y)</label>
                    <Select
                        :modelValue="elementoSeleccionado.gap_y || ''"
                        @update:modelValue="(val) => elementoSeleccionado.gap_y = val"
                        :options="[
                            { label: 'Heredado (Mediano 32px)', value: '' },
                            { label: 'Sin espacio (0px)', value: 'none' },
                            { label: 'Pequeño (16px)', value: 'sm' },
                            { label: 'Mediano (32px)', value: 'md' },
                            { label: 'Grande (48px)', value: 'lg' },
                            { label: 'Extra Grande (64px)', value: 'xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- BLOQUE 3: ALINEACIÓN Y DIVISORES DE ALTURA -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-sliders-h text-emerald-600 dark:text-emerald-400" />
                <span>Alineación e Igualado de Altura</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Alineación Vertical de Columnas</label>
                <Select
                    :modelValue="elementoSeleccionado.align || 'start'"
                    @update:modelValue="(val) => elementoSeleccionado.align = val"
                    :options="[
                        { label: 'Arriba (Superior - Alineado estándar)', value: 'start' },
                        { label: 'Centro (Centrado vertical)', value: 'center' },
                        { label: 'Abajo (Inferior)', value: 'end' },
                        { label: 'Estirar / Justificar (Stretch)', value: 'stretch' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Mostrar Divisores entre Columnas</span>
                        <span class="text-[11px] text-zinc-500">Añade una línea vertical simétrica entre columnas.</span>
                    </div>
                    <ToggleSwitch
                        :modelValue="!!elementoSeleccionado.show_dividers"
                        @update:modelValue="(val) => elementoSeleccionado.show_dividers = val"
                    />
                </div>

                <div class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Igualar Altura de Columnas (Match Height)</span>
                        <span class="text-[11px] text-zinc-500">Fuerza a todas las columnas a ocupar el 100% de la altura de la fila.</span>
                    </div>
                    <ToggleSwitch
                        :modelValue="!!elementoSeleccionado.match_height"
                        @update:modelValue="(val) => elementoSeleccionado.match_height = val"
                    />
                </div>

                <div class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Auto-colapsar Columnas Vacías</span>
                        <span class="text-[11px] text-zinc-500">Oculta las columnas sin datos dinámicos y expande las restantes al 100%.</span>
                    </div>
                    <ToggleSwitch
                        :modelValue="!!elementoSeleccionado.collapse_empty_columns"
                        @update:modelValue="(val) => elementoSeleccionado.collapse_empty_columns = val"
                    />
                </div>
            </div>
        </div>

        <!-- BLOQUE 4: MÁRGENES Y SEMÁNTICA HTML -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-code text-emerald-600 dark:text-emerald-400" />
                <span>Márgenes y Semántica HTML</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Margen Superior (Top)</label>
                    <Select
                        :modelValue="elementoSeleccionado.margin_top || 'none'"
                        @update:modelValue="(val) => elementoSeleccionado.margin_top = val"
                        :options="[
                            { label: 'Sin Margen (0px)', value: 'none' },
                            { label: 'Pequeño (16px)', value: 'sm' },
                            { label: 'Mediano (32px)', value: 'md' },
                            { label: 'Grande (48px)', value: 'lg' },
                            { label: 'Extra Grande (64px)', value: 'xl' },
                            { label: 'Gigante (96px)', value: '2xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Margen Inferior (Bottom)</label>
                    <Select
                        :modelValue="elementoSeleccionado.margin_bottom || 'none'"
                        @update:modelValue="(val) => elementoSeleccionado.margin_bottom = val"
                        :options="[
                            { label: 'Sin Margen (0px)', value: 'none' },
                            { label: 'Pequeño (16px)', value: 'sm' },
                            { label: 'Mediano (32px)', value: 'md' },
                            { label: 'Grande (48px)', value: 'lg' },
                            { label: 'Extra Grande (64px)', value: 'xl' },
                            { label: 'Gigante (96px)', value: '2xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Etiqueta Semántica HTML</label>
                <Select
                    :modelValue="elementoSeleccionado.html_element || 'div'"
                    @update:modelValue="(val) => elementoSeleccionado.html_element = val"
                    :options="[
                        { label: 'div (División Estándar)', value: 'div' },
                        { label: 'section (Sección interna)', value: 'section' },
                        { label: 'article (Articulo/Tarjeta)', value: 'article' },
                        { label: 'header (Encabezado)', value: 'header' },
                        { label: 'footer (Pie)', value: 'footer' },
                        { label: 'nav (Navegación)', value: 'nav' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- BLOQUE 5: PERSONALIZACIÓN AVANZADA Y CÓDIGO -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-cog text-emerald-600 dark:text-emerald-400" />
                <span>Personalización Avanzada y Código</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">ID HTML Único (`id`)</label>
                <InputText
                    v-model="elementoSeleccionado.element_id"
                    placeholder="ej. mi-fila-servicios"
                    class="w-full text-xs font-mono"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Clases CSS Adicionales</label>
                <InputText
                    v-model="elementoSeleccionado.custom_classes"
                    placeholder="ej. shadow-md rounded-xl mi-clase-fila"
                    class="w-full text-xs font-mono"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Atributos HTML Personalizados</label>
                <InputText
                    v-model="elementoSeleccionado.custom_attributes"
                    placeholder='ej. data-speed="1.2" aria-label="Fila de contenido"'
                    class="w-full text-xs font-mono"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">CSS Personalizado</label>
                <Textarea
                    v-model="elementoSeleccionado.custom_css"
                    rows="4"
                    class="w-full text-xs font-mono"
                    placeholder="/* Reglas CSS específicas de esta fila */"
                />
            </div>
        </div>

        <div class="pt-3 border-t border-zinc-200 dark:border-zinc-800">
            <Button
                label="Eliminar esta Fila"
                icon="pi pi-trash"
                severity="danger"
                variant="outlined"
                size="small"
                class="w-full"
                @click="emit('eliminar-fila-seleccionada')"
            />
        </div>
    </div>
</template>
