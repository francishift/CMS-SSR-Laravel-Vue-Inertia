<script setup>
/**
 * Inspector Atómico para el Elemento Botón CTA / Enlace.
 * Controla etiqueta, URL destino, abrir en nueva pestaña, variantes visuales (primario, secundario, contorno, enlace simple),
 * tamaño, alineación, icono acompañante (PrimeIcons) con posición, márgenes e inspección avanzada.
 */
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import DynamicTagSelector from '../DynamicTagSelector.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

function insertarEtiquetaLabel(tag) {
    props.elementoSeleccionado.content.label = (props.elementoSeleccionado.content.label || '') + tag;
}

function insertarEtiquetaUrl(tag) {
    props.elementoSeleccionado.content.url = (props.elementoSeleccionado.content.url || '') + tag;
}
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- Texto del Botón -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-semibold">Texto del Botón / Enlace (Label)</label>
                <DynamicTagSelector @insertar="insertarEtiquetaLabel" />
            </div>
            <InputText v-model="elementoSeleccionado.content.label" placeholder="ej. Contactar Ahora" class="w-full text-xs font-medium" />
        </div>

        <!-- URL y Destino -->
        <div class="grid grid-cols-3 gap-2">
            <div class="col-span-2">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold">Enlace / URL de Destino</label>
                    <DynamicTagSelector @insertar="insertarEtiquetaUrl" />
                </div>
                <InputText v-model="elementoSeleccionado.content.url" placeholder="ej. /contacto o https://..." class="w-full text-xs font-mono" />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Destino</label>
                <Select
                    :modelValue="elementoSeleccionado.content.target || '_self'"
                    @update:modelValue="(val) => elementoSeleccionado.content.target = val"
                    :options="[
                        { label: 'Misma pestaña (_self)', value: '_self' },
                        { label: 'Nueva pestaña (_blank)', value: '_blank' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Variante Visual y Tamaño -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Variante Visual</label>
                <Select
                    v-model="elementoSeleccionado.content.variant"
                    :options="[
                        { label: 'Primario', value: 'primary' },
                        { label: 'Secundario', value: 'secondary' },
                        { label: 'Contorno', value: 'outlined' },
                        { label: 'Enlace', value: 'link' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Tamaño</label>
                <Select
                    :modelValue="elementoSeleccionado.content.size || 'md'"
                    @update:modelValue="(val) => elementoSeleccionado.content.size = val"
                    :options="[
                        { label: 'Pequeño', value: 'sm' },
                        { label: 'Por defecto', value: 'md' },
                        { label: 'Grande', value: 'lg' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Alineación Horizontal -->
        <div>
            <label class="block text-xs font-semibold mb-1">Alineación Horizontal</label>
            <Select
                :modelValue="elementoSeleccionado.content.align || 'left'"
                @update:modelValue="(val) => elementoSeleccionado.content.align = val"
                :options="[
                    { label: 'A la Izquierda', value: 'left' },
                    { label: 'Al Centro', value: 'center' },
                    { label: 'A la Derecha', value: 'right' },
                    { label: 'Ancho Completo (100%)', value: 'full' }
                ]"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <!-- Icono Acompañante y Posición -->
        <div class="grid grid-cols-3 gap-2">
            <div class="col-span-2">
                <label class="block text-xs font-semibold mb-1">Icono Acompañante (PrimeIcons)</label>
                <IconPicker
                    v-model="elementoSeleccionado.content.icon"
                    placeholder="pi pi-arrow-right"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Posición Icono</label>
                <Select
                    :modelValue="elementoSeleccionado.content.icon_position || 'left'"
                    @update:modelValue="(val) => elementoSeleccionado.content.icon_position = val"
                    :options="[
                        { label: 'Izquierda', value: 'left' },
                        { label: 'Derecha', value: 'right' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Márgenes Superior e Inferior -->
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
                        { label: 'Grande (40px)', value: 'lg' },
                        { label: 'Extra Grande (64px)', value: 'xl' }
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
                        { label: 'Grande (40px)', value: 'lg' },
                        { label: 'Extra Grande (64px)', value: 'xl' }
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
