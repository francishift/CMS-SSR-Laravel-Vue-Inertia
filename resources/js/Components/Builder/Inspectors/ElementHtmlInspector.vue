<script setup>
/**
 * Inspector Atómico para el Elemento Código HTML Libre (Html).
 * Controla marcado HTML, alineación horizontal, márgenes independientes, ID único, clases CSS y CSS dinámico.
 */
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <div>
            <label class="block text-xs font-semibold mb-1.5">Código HTML / SVG / Iframe / Scripts Personalizado</label>
            <Textarea
                :modelValue="elementoSeleccionado.content.code ?? elementoSeleccionado.content.html ?? ''"
                @update:modelValue="(val) => { elementoSeleccionado.content.code = val; elementoSeleccionado.content.html = val; }"
                rows="10"
                class="w-full text-xs font-mono"
                placeholder="<div class='p-4 bg-emerald-50'>...</div>"
            />
            <span class="text-[11px] text-zinc-500 block mt-1">
                Admite código HTML completo, SVGs, iFrames, formularios, bloques de estilo &lt;style&gt; y scripts &lt;script&gt; sin restricciones.
            </span>
        </div>

        <!-- Alineación Horizontal -->
        <div>
            <label class="block text-xs font-semibold mb-1">Alineación Horizontal</label>
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

        <!-- PERSONALIZACIÓN AVANZADA DE BLOQUE -->
        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" />
    </div>
</template>
