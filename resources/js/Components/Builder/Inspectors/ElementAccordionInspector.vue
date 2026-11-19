<script setup>
/**
 * Inspector Atómico para el Elemento Acordeón (FAQ).
 * Controla ítems de preguntas/respuestas, comportamiento solitario/múltiple, estilos visuales, tamaño de título y márgenes.
 */
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import WysiwygEditor from '@/Components/Admin/WysiwygEditor.vue';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

const emit = defineEmits([
    'añadir-item-acordeon',
    'eliminar-item-acordeon',
    'refrescar-previsualizacion'
]);

import { OPCIONES_TAMANO_VISUAL, OPCIONES_TAMANO_VISUAL_RESPONSIVE } from '@/Utils/headingScale';

const dispositivoTamanoTituloAcc = ref('desktop');
const opcionesTamanoVisualAccordion = OPCIONES_TAMANO_VISUAL;
const opcionesTamanoVisualAccordionTabletMobile = OPCIONES_TAMANO_VISUAL_RESPONSIVE;
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- BLOQUE 1: GESTIÓN DE PREGUNTAS / ITEMS -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="pi pi-list text-emerald-600 dark:text-emerald-400" />
                    <span>Preguntas y Respuestas ({{ (elementoSeleccionado.content?.items || []).length }})</span>
                </div>
                <button
                    type="button"
                    @click="emit('añadir-item-acordeon')"
                    class="text-xs font-bold text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 flex items-center gap-1 cursor-pointer"
                >
                    <i class="pi pi-plus text-[10px]" />
                    <span>Añadir Pregunta</span>
                </button>
            </div>

            <div v-if="elementoSeleccionado.content?.items?.length">
                <Accordion value="0">
                    <draggable
                        v-model="elementoSeleccionado.content.items"
                        item-key="id"
                        animation="200"
                        ghost-class="opacity-40"
                        handle=".accordion-drag-handle"
                        @end="emit('refrescar-previsualizacion')"
                    >
                        <template #item="{ element: item, index: aIdx }">
                            <AccordionPanel :value="aIdx" class="border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden mb-2">
                                <AccordionHeader class="!py-2 !px-3 text-xs font-semibold">
                                    <div class="flex items-center justify-between w-full pr-2 gap-2">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <span
                                                class="accordion-drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-emerald-600 p-1 shrink-0"
                                                @click.stop
                                            >
                                                <i class="pi pi-bars text-xs" />
                                            </span>
                                            <span class="truncate">{{ item.title || ('Pregunta ' + (aIdx + 1)) }}</span>
                                        </div>

                                        <div class="flex items-center gap-1 shrink-0" @click.stop>
                                            <button
                                                type="button"
                                                @click="emit('eliminar-item-acordeon', aIdx)"
                                                class="p-1 text-zinc-400 hover:text-red-600 cursor-pointer ml-1"
                                                v-tooltip.top="'Eliminar pregunta'"
                                            >
                                                <i class="pi pi-trash text-[10px]" />
                                            </button>
                                        </div>
                                    </div>
                                </AccordionHeader>
                                <AccordionContent class="!p-4 space-y-3 bg-white dark:bg-zinc-900">
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Título de la Pregunta</label>
                                        <InputText v-model="item.title" placeholder="¿Cómo funciona?" class="w-full text-xs" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Subtítulo / Meta Texto (Opcional)</label>
                                        <InputText v-model="item.subtitle" placeholder="ej. Información relevante" class="w-full text-xs" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Icono Acompañante</label>
                                        <IconPicker v-model="item.icon" placeholder="pi pi-question-circle" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Respuesta / Contenido Enriquecido</label>
                                        <WysiwygEditor v-model="item.content" placeholder="Escribe la respuesta..." />
                                    </div>
                                </AccordionContent>
                            </AccordionPanel>
                        </template>
                    </draggable>
                </Accordion>
            </div>
        </div>

        <!-- BLOQUE 2: COMPORTAMIENTO -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-cog text-emerald-600 dark:text-emerald-400" />
                <span>Comportamiento del Desplegable</span>
            </div>

            <div class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                <div>
                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Permitir Múltiples Abiertos</span>
                    <span class="text-[11px] text-zinc-500">Permite desplegar varias preguntas simultáneamente.</span>
                </div>
                <ToggleSwitch
                    :modelValue="!!elementoSeleccionado.content.allow_multiple"
                    @update:modelValue="(val) => elementoSeleccionado.content.allow_multiple = val"
                />
            </div>

            <div class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                <div>
                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Abrir Primer Ítem por Defecto</span>
                    <span class="text-[11px] text-zinc-500">Muestra la primera pregunta desplegada al cargar la página.</span>
                </div>
                <ToggleSwitch
                    :modelValue="!!elementoSeleccionado.content.first_open"
                    @update:modelValue="(val) => elementoSeleccionado.content.first_open = val"
                />
            </div>
        </div>

        <!-- BLOQUE 3: ESTILO VISUAL E INDICADORES -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-palette text-emerald-600 dark:text-emerald-400" />
                <span>Estilo Visual e Indicadores</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Preset de Estilo del Contenedor</label>
                <Select
                    :modelValue="elementoSeleccionado.content.style_preset || 'default'"
                    @update:modelValue="(val) => elementoSeleccionado.content.style_preset = val"
                    :options="[
                        { label: 'Estándar (Tarjeta Limpia)', value: 'default' },
                        { label: 'Divisores Limpios (Líneas horizontales sin bordes)', value: 'divider' },
                        { label: 'Tarjetas Separadas (Bloques independientes)', value: 'boxed' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Icono del Indicador</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.indicator_icon || 'chevron'"
                        @update:modelValue="(val) => elementoSeleccionado.content.indicator_icon = val"
                        :options="[
                            { label: 'Flecha (Chevron)', value: 'chevron' },
                            { label: 'Más / Menos (+/-)', value: 'plus_minus' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Alineación del Indicador</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.icon_align || 'right'"
                        @update:modelValue="(val) => elementoSeleccionado.content.icon_align = val"
                        :options="[
                            { label: 'A la Derecha', value: 'right' },
                            { label: 'A la Izquierda', value: 'left' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <div class="space-y-3 p-3 bg-zinc-100/70 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60">
                <div>
                    <label class="block text-xs font-semibold mb-1">Etiqueta Semántica HTML Título</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.title_tag || 'h3'"
                        @update:modelValue="(val) => elementoSeleccionado.content.title_tag = val"
                        :options="[
                            { label: 'H1 (Encabezado Principal)', value: 'h1' },
                            { label: 'H2 (Sección Secundaria)', value: 'h2' },
                            { label: 'H3 (Subsección / Pregunta)', value: 'h3' },
                            { label: 'H4 (Subtítulo)', value: 'h4' },
                            { label: 'H5 (Pequeño)', value: 'h5' },
                            { label: 'H6 (Mini)', value: 'h6' },
                            { label: 'div (Sin peso semántico)', value: 'div' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Selector Responsive de Escala Visual del Título de Acordeón -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                            <i class="pi pi-text-height text-emerald-600 dark:text-emerald-400 text-xs" />
                            <span>Tamaño Visual del Título</span>
                        </label>

                        <!-- Selector de Dispositivo (PC / Tablet / Móvil) -->
                        <div class="flex items-center bg-zinc-200 dark:bg-zinc-800 p-0.5 rounded-lg text-xs">
                            <button
                                type="button"
                                @click="dispositivoTamanoTituloAcc = 'desktop'"
                                :class="[
                                    'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                                    dispositivoTamanoTituloAcc === 'desktop' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                ]"
                                v-tooltip.top="'Escritorio (Desktop)'"
                            >
                                <i class="pi pi-desktop text-[10px]" />
                                <span>PC</span>
                            </button>
                            <button
                                type="button"
                                @click="dispositivoTamanoTituloAcc = 'tablet'"
                                :class="[
                                    'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                                    dispositivoTamanoTituloAcc === 'tablet' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                ]"
                                v-tooltip.top="'Tablet (Pantallas medianas)'"
                            >
                                <i class="pi pi-tablet text-[10px]" />
                                <span>Tablet</span>
                                <span v-if="elementoSeleccionado.content?.title_size_tablet" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            </button>
                            <button
                                type="button"
                                @click="dispositivoTamanoTituloAcc = 'mobile'"
                                :class="[
                                    'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                                    dispositivoTamanoTituloAcc === 'mobile' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                ]"
                                v-tooltip.top="'Móvil (Pantallas pequeñas)'"
                            >
                                <i class="pi pi-mobile text-[10px]" />
                                <span>Móvil</span>
                                <span v-if="elementoSeleccionado.content?.title_size_mobile" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            </button>
                        </div>
                    </div>

                    <!-- ESCRITORIO (PC) -->
                    <div v-if="dispositivoTamanoTituloAcc === 'desktop'">
                        <Select
                            :modelValue="elementoSeleccionado.content?.title_size || 'default'"
                            @update:modelValue="(val) => elementoSeleccionado.content.title_size = val"
                            :options="opcionesTamanoVisualAccordion"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- TABLET -->
                    <div v-else-if="dispositivoTamanoTituloAcc === 'tablet'">
                        <Select
                            :modelValue="elementoSeleccionado.content?.title_size_tablet || ''"
                            @update:modelValue="(val) => elementoSeleccionado.content.title_size_tablet = val"
                            :options="opcionesTamanoVisualAccordionTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- MÓVIL -->
                    <div v-else-if="dispositivoTamanoTituloAcc === 'mobile'">
                        <Select
                            :modelValue="elementoSeleccionado.content?.title_size_mobile || ''"
                            @update:modelValue="(val) => elementoSeleccionado.content.title_size_mobile = val"
                            :options="opcionesTamanoVisualAccordionTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Separación Interna de Respuesta (Padding)</label>
                <Select
                    :modelValue="elementoSeleccionado.content.content_padding || 'md'"
                    @update:modelValue="(val) => elementoSeleccionado.content.content_padding = val"
                    :options="[
                        { label: 'Compacto (10px Top)', value: 'sm' },
                        { label: 'Estándar (16px Top)', value: 'md' },
                        { label: 'Generoso (24px Top)', value: 'lg' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- BLOQUE 4: MÁRGENES TOP Y BOTTOM DE BLOQUE -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Margen Superior Bloque (Top)</label>
                <Select
                    :modelValue="elementoSeleccionado.content.margin_top || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.margin_top = val"
                    :options="[
                        { label: 'Sin Margen (0px)', value: 'none' },
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
                <label class="block text-xs font-semibold mb-1">Margen Inferior Bloque (Bottom)</label>
                <Select
                    :modelValue="elementoSeleccionado.content.margin_bottom || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.margin_bottom = val"
                    :options="[
                        { label: 'Sin Margen (0px)', value: 'none' },
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

        <!-- PERSONALIZACIÓN AVANZADA Y CÓDIGO -->
        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" allowCascade />
    </div>
</template>
