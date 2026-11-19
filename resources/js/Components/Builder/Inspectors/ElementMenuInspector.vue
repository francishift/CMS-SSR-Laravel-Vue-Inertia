<script setup>
/**
 * Inspector Atómico para el Elemento Modular Menú de Navegación.
 * Permite seleccionar qué menú renderizar, la orientación (horizontal/vertical),
 * alineación, tamaño tipográfico, espaciados y colores de los enlaces.
 */
import { computed, watch } from 'vue';
import Select from 'primevue/select';
import { usePage } from '@inertiajs/vue3';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

const page = usePage();

// Lista de menús disponibles transmitidos por Inertia o fallback
const menusDisponibles = computed(() => {
    const menus = page.props?.menus || page.props?.menusDisponibles || [];
    const list = [
        { label: 'Menú Cabecera Principal (Ubicación: Header)', value: 'location_header' },
        { label: 'Menú Barra Lateral (Ubicación: Sidebar)', value: 'location_sidebar' },
    ];

    if (Array.isArray(menus)) {
        menus.forEach(m => {
            let locText = 'Personalizado / Page Builder';
            if (m.location === 'header') locText = 'Cabecera';
            else if (m.location === 'sidebar') locText = 'Barra Lateral';
            else if (m.location === 'footer') locText = 'Pie de Página';

            list.push({
                label: `${m.name} (${locText})`,
                value: `id_${m.id}`
            });
        });
    }

    return list;
});

// Modelo reactivo y desambiguado para el menú seleccionado (PrimeVue 4)
const selectedSource = computed({
    get() {
        const val = props.elementoSeleccionado?.content?.source;
        if (!val) return 'location_header';
        // Si el valor guardado es numérico o viene como string numérico (ej. 5 o '5'), desambiguar a id_X
        if (!isNaN(Number(val)) && val !== '') {
            const match = menusDisponibles.value.find(op => op.value === `id_${val}`);
            if (match) return match.value;
        }
        return val;
    },
    set(newVal) {
        if (!props.elementoSeleccionado.content) {
            props.elementoSeleccionado.content = {};
        }
        props.elementoSeleccionado.content.source = newVal;
    }
});

const opcionesOrientacion = [
    { label: 'Horizontal (En línea)', value: 'horizontal', icon: 'pi pi-arrows-h' },
    { label: 'Vertical (En lista)', value: 'vertical', icon: 'pi pi-bars' },
];

const opcionesAlineacion = [
    { label: 'Izquierda', value: 'left', icon: 'pi pi-align-left' },
    { label: 'Centro', value: 'center', icon: 'pi pi-align-center' },
    { label: 'Derecha', value: 'right', icon: 'pi pi-align-right' },
];

const opcionesTamano = [
    { label: 'Extra Pequeño (xs)', value: 'xs' },
    { label: 'Pequeño (sm - Estándar)', value: 'sm' },
    { label: 'Mediano (base)', value: 'base' },
    { label: 'Grande (lg)', value: 'lg' },
];

const opcionesColor = [
    { label: 'Gris Sutil (Estándar)', value: 'default' },
    { label: 'Color Primario Corporativo', value: 'primary' },
    { label: 'Blanco / Claro (Fondos Oscuros)', value: 'light' },
    { label: 'Negro / Oscuro (Fondos Claros)', value: 'dark' },
];

const opcionesGap = [
    { label: 'Estrechito (gap-2)', value: 'gap-2' },
    { label: 'Medio (gap-4 - Defecto)', value: 'gap-4' },
    { label: 'Amplio (gap-6)', value: 'gap-6' },
    { label: 'Separado (gap-8)', value: 'gap-8' },
];

// Garantizar que la estructura `content` esté inicializada reactivamente ante cualquier cambio de elemento
watch(
    () => props.elementoSeleccionado,
    (el) => {
        if (el) {
            if (!el.content) el.content = {};
            if (!el.content.source) el.content.source = 'location_header';
            if (!el.content.orientation) el.content.orientation = 'horizontal';
            if (!el.content.align) el.content.align = 'left';
            if (!el.content.size) el.content.size = 'sm';
            if (!el.content.color) el.content.color = 'default';
            if (!el.content.gap) el.content.gap = 'gap-4';
        }
    },
    { immediate: true }
);
</script>

<template>
    <div class="space-y-4">
        <!-- Selección del Menú a Renderizar -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Menú a Mostrar</label>
            <Select
                v-model="selectedSource"
                :options="menusDisponibles"
                optionLabel="label"
                optionValue="value"
                placeholder="Seleccionar menú..."
                class="w-full text-xs"
            />
            <p class="text-[11px] text-zinc-400">
                Selecciona la ubicación o el menú específico que deseas mostrar en este bloque.
            </p>
        </div>

        <!-- Orientación del Menú (Horizontal / Vertical) -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Orientación</label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="op in opcionesOrientacion"
                    :key="op.value"
                    type="button"
                    :class="[
                        'p-2.5 rounded-xl border text-left flex items-center gap-2 text-xs font-semibold transition-all cursor-pointer',
                        (elementoSeleccionado.content.orientation || 'horizontal') === op.value
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                            : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300'
                    ]"
                    @click="elementoSeleccionado.content.orientation = op.value"
                >
                    <i :class="[op.icon, 'text-sm shrink-0']" />
                    <span>{{ op.label }}</span>
                </button>
            </div>
        </div>

        <!-- Alineación del Menú -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Alineación</label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="op in opcionesAlineacion"
                    :key="op.value"
                    type="button"
                    :class="[
                        'p-2 rounded-lg border text-center flex flex-col items-center justify-center gap-1 text-[11px] font-semibold transition-all cursor-pointer',
                        (elementoSeleccionado.content.align || 'left') === op.value
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                            : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300'
                    ]"
                    @click="elementoSeleccionado.content.align = op.value"
                >
                    <i :class="[op.icon, 'text-sm']" />
                    <span>{{ op.label }}</span>
                </button>
            </div>
        </div>

        <!-- Tamaño de Fuente de los Enlaces -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Tamaño del Texto</label>
            <Select
                :modelValue="elementoSeleccionado.content.size || 'sm'"
                @update:modelValue="(val) => elementoSeleccionado.content.size = val"
                :options="opcionesTamano"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <!-- Color de los Enlaces -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Color de los Enlaces</label>
            <Select
                :modelValue="elementoSeleccionado.content.color || 'default'"
                @update:modelValue="(val) => elementoSeleccionado.content.color = val"
                :options="opcionesColor"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <!-- Espaciado entre Enlaces -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Espaciado entre Opciones</label>
            <Select
                :modelValue="elementoSeleccionado.content.gap || 'gap-4'"
                @update:modelValue="(val) => elementoSeleccionado.content.gap = val"
                :options="opcionesGap"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" />
    </div>
</template>
