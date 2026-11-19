<script setup>
/**
 * Inspector Atómico para el Elemento Modular de Redes Sociales.
 * Permite seleccionar el estilo visual de los botones, tamaño, alineación y paleta de colores.
 */
import Select from 'primevue/select';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

const opcionesEstilo = [
    { label: 'Cuadrado Suave (Redondeado lg)', value: 'soft' },
    { label: 'Circular (Redondeado Total)', value: 'circle' },
    { label: 'Sin Fondo (Solo Icono)', value: 'ghost' },
    { label: 'Fondo Sólido Corporativo', value: 'solid' },
];

const opcionesAlineacion = [
    { label: 'Izquierda', value: 'left', icon: 'pi pi-align-left' },
    { label: 'Centro', value: 'center', icon: 'pi pi-align-center' },
    { label: 'Derecha', value: 'right', icon: 'pi pi-align-right' },
];

const opcionesTamano = [
    { label: 'Pequeño (sm - 32px)', value: 'sm' },
    { label: 'Mediano (md - 40px - Estándar)', value: 'md' },
    { label: 'Grande (lg - 48px)', value: 'lg' },
    { label: 'Extra Grande (xl - 56px)', value: 'xl' },
];

const opcionesColor = [
    { label: 'Efecto Primario Corporativo', value: 'brand' },
    { label: 'Gris Sutil / Muted', value: 'muted' },
    { label: 'Blanco / Claro (Fondos Oscuros)', value: 'light' },
    { label: 'Negro / Oscuro (Fondos Claros)', value: 'dark' },
];

if (!props.elementoSeleccionado.content) {
    props.elementoSeleccionado.content = {};
}
const content = props.elementoSeleccionado.content;
if (!content.style_preset) content.style_preset = 'soft';
if (!content.align) content.align = 'left';
if (!content.size) content.size = 'md';
if (!content.color_preset) content.color_preset = 'brand';
</script>

<template>
    <div class="space-y-4">
        <!-- Estilo Visual de los Botones -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Estilo Visual del Botón</label>
            <Select
                :modelValue="elementoSeleccionado.content.style_preset || 'soft'"
                @update:modelValue="(val) => elementoSeleccionado.content.style_preset = val"
                :options="opcionesEstilo"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <!-- Alineación -->
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

        <!-- Escala de Tamaño -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Tamaño de los Botones</label>
            <Select
                :modelValue="elementoSeleccionado.content.size || 'md'"
                @update:modelValue="(val) => elementoSeleccionado.content.size = val"
                :options="opcionesTamano"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <!-- Paleta de Colores -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Paleta de Colores</label>
            <Select
                :modelValue="elementoSeleccionado.content.color_preset || 'brand'"
                @update:modelValue="(val) => elementoSeleccionado.content.color_preset = val"
                :options="opcionesColor"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" allowCascade />
    </div>
</template>
