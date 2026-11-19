<script setup>
/**
 * Inspector Atómico para el Elemento Texto Párrafo (Text).
 * Controla texto/HTML enriquecido, presets (lead, muted), columnas de periódico, letra capitular, pesos y márgenes.
 */
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import WysiwygEditor from '@/Components/Admin/WysiwygEditor.vue';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import DynamicTagSelector from '../DynamicTagSelector.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

function insertarEtiqueta(tag) {
    props.elementoSeleccionado.content.html = (props.elementoSeleccionado.content.html || '') + tag;
}
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-semibold">Texto / HTML Enriquecido</label>
                <DynamicTagSelector @insertar="insertarEtiqueta" />
            </div>
            <WysiwygEditor
                v-model="elementoSeleccionado.content.html"
                placeholder="Escribe el contenido del párrafo..."
                height="220px"
            />
        </div>

        <!-- Preset Tipográfico y Columnas de Periódico -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Estilo Preset de Texto</label>
                <Select
                    :modelValue="elementoSeleccionado.content.style_preset || 'default'"
                    @update:modelValue="(val) => elementoSeleccionado.content.style_preset = val"
                    :options="[
                        { label: 'Estándar', value: 'default' },
                        { label: 'Lead (Texto Destacado)', value: 'lead' },
                        { label: 'Muted (Texto Secundario)', value: 'muted' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Columnas de Periódico</label>
                <Select
                    :modelValue="String(elementoSeleccionado.content.columns || '1')"
                    @update:modelValue="(val) => elementoSeleccionado.content.columns = val"
                    :options="[
                        { label: '1 Columna (100%)', value: '1' },
                        { label: '2 Columnas', value: '2' },
                        { label: '3 Columnas', value: '3' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Tamaño, Peso y Alineación -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Tamaño Fuente</label>
                <Select
                    :modelValue="elementoSeleccionado.content.size || 'base'"
                    @update:modelValue="(val) => elementoSeleccionado.content.size = val"
                    :options="[
                        { label: 'Pequeño (14px)', value: 'sm' },
                        { label: 'Estándar (16px)', value: 'base' },
                        { label: 'Grande (18px)', value: 'lg' },
                        { label: 'Extra Grande (20px)', value: 'xl' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Peso Tipográfico</label>
                <Select
                    :modelValue="elementoSeleccionado.content.weight || 'default'"
                    @update:modelValue="(val) => elementoSeleccionado.content.weight = val"
                    :options="[
                        { label: 'Por defecto', value: 'default' },
                        { label: 'Ligero (Light)', value: 'light' },
                        { label: 'Normal (Regular)', value: 'normal' },
                        { label: 'Seminegrita (Semibold)', value: 'semibold' },
                        { label: 'Negrita (Bold)', value: 'bold' },
                        { label: 'Extra Negrita', value: 'extrabold' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Alineación</label>
                <Select
                    :modelValue="elementoSeleccionado.content.align || 'left'"
                    @update:modelValue="(val) => elementoSeleccionado.content.align = val"
                    :options="[
                        { label: 'Izquierda', value: 'left' },
                        { label: 'Centro', value: 'center' },
                        { label: 'Derecha', value: 'right' },
                        { label: 'Justificado', value: 'justify' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Color y Letra Capitular (Drop Cap) -->
        <div class="grid grid-cols-2 gap-3 items-center">
            <div>
                <label class="block text-xs font-semibold mb-1">Color de Texto</label>
                <Select
                    :modelValue="elementoSeleccionado.content.color || 'default'"
                    @update:modelValue="(val) => elementoSeleccionado.content.color = val"
                    :options="[
                        { label: 'Predeterminado del Tema', value: 'default' },
                        { label: 'Color Primario (Marca)', value: 'primary' },
                        { label: 'Color Secundario (Marca)', value: 'secondary' },
                        { label: 'Color Acento (Marca)', value: 'accent' },
                        { label: 'Gris Suave / Muted', value: 'muted' },
                        { label: 'Texto Negro', value: 'dark' },
                        { label: 'Texto Blanco', value: 'white' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div class="pt-4">
                <div class="flex items-center gap-2">
                    <ToggleSwitch
                        :modelValue="!!elementoSeleccionado.content.drop_cap"
                        @update:modelValue="(val) => elementoSeleccionado.content.drop_cap = val"
                    />
                    <span class="text-xs font-semibold">Letra Capitular (Drop Cap)</span>
                </div>
            </div>
        </div>

        <!-- Márgenes Top y Bottom -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Margen Superior (Top)</label>
                <Select
                    :modelValue="elementoSeleccionado.content.margin_top || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.margin_top = val"
                    :options="[
                        { label: 'Sin Margen (0px)', value: 'none' },
                        { label: 'Pequeño (12px)', value: 'sm' },
                        { label: 'Mediano (24px)', value: 'md' },
                        { label: 'Grande (40px)', value: 'lg' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Margen Inferior (Bottom)</label>
                <Select
                    :modelValue="elementoSeleccionado.content.margin_bottom || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.margin_bottom = val"
                    :options="[
                        { label: 'Sin Margen (0px)', value: 'none' },
                        { label: 'Pequeño (12px)', value: 'sm' },
                        { label: 'Mediano (24px)', value: 'md' },
                        { label: 'Grande (40px)', value: 'lg' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- PERSONALIZACIÓN AVANZADA Y CÓDIGO -->
        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" />
    </div>
</template>
