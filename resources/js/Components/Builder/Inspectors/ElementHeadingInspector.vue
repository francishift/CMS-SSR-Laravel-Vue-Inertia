<script setup>
/**
 * Inspector Atómico para el Elemento Encabezado (Heading).
 * Controla texto, subtítulos, semántica H1-H6, escala visual, pesos tipográficos, transformaciones, decoradores y enlaces.
 */
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import DynamicTagSelector from '../DynamicTagSelector.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

import { OPCIONES_TAMANO_VISUAL, OPCIONES_TAMANO_VISUAL_RESPONSIVE } from '@/Utils/headingScale';

const dispositivoTamano = ref('desktop');
const opcionesTamanoVisual = OPCIONES_TAMANO_VISUAL;
const opcionesTamanoVisualTabletMobile = OPCIONES_TAMANO_VISUAL_RESPONSIVE;

function insertarEtiquetaTitulo(tag) {
    props.elementoSeleccionado.content.text = (props.elementoSeleccionado.content.text || '') + tag;
}

function insertarEnlaceTitulo(tag) {
    props.elementoSeleccionado.content.link_url = tag;
}

function insertarEtiquetaSubtitulo(tag) {
    props.elementoSeleccionado.content.subtitle = (props.elementoSeleccionado.content.subtitle || '') + tag;
}

function insertarEnlaceSubtitulo(tag) {
    props.elementoSeleccionado.content.subtitle_link_url = tag;
}
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- Bloque Título Principal -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2.5">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-zinc-900 dark:text-zinc-100">Texto del Título</label>
                    <DynamicTagSelector tipo="text" @insertar="insertarEtiquetaTitulo" />
                </div>
                <InputText v-model="elementoSeleccionado.content.text" placeholder="Escribe el título aquí..." class="w-full text-xs font-medium" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Enlace del Título (Opcional)</label>
                    <DynamicTagSelector tipo="link" @insertar="insertarEnlaceTitulo" />
                </div>
                <div class="flex items-center gap-2">
                    <InputText
                        v-model="elementoSeleccionado.content.link_url"
                        placeholder="ej. /servicios, {article.url} o https://..."
                        class="w-full text-xs font-mono"
                    />
                    <Select
                        :modelValue="elementoSeleccionado.content.link_target || '_self'"
                        @update:modelValue="(val) => elementoSeleccionado.content.link_target = val"
                        :options="[
                            { label: 'Misma pestaña (_self)', value: '_self' },
                            { label: 'Nueva pestaña (_blank)', value: '_blank' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-44 text-xs shrink-0"
                    />
                </div>
            </div>
        </div>

        <!-- Bloque Subtítulo / Meta Texto -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2.5">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-zinc-900 dark:text-zinc-100">Subtítulo / Meta Texto (Opcional)</label>
                    <DynamicTagSelector tipo="text" @insertar="insertarEtiquetaSubtitulo" />
                </div>
                <InputText v-model="elementoSeleccionado.content.subtitle" placeholder="ej. Categoría o Resumen corto" class="w-full text-xs" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Enlace del Subtítulo (Opcional)</label>
                    <DynamicTagSelector tipo="link" @insertar="insertarEnlaceSubtitulo" />
                </div>
                <div class="flex items-center gap-2">
                    <InputText
                        v-model="elementoSeleccionado.content.subtitle_link_url"
                        placeholder="ej. /categoria, {category.url} o https://..."
                        class="w-full text-xs font-mono"
                    />
                    <Select
                        :modelValue="elementoSeleccionado.content.subtitle_link_target || '_self'"
                        @update:modelValue="(val) => elementoSeleccionado.content.subtitle_link_target = val"
                        :options="[
                            { label: 'Misma pestaña (_self)', value: '_self' },
                            { label: 'Nueva pestaña (_blank)', value: '_blank' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-44 text-xs shrink-0"
                    />
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold mb-1 text-zinc-500">Posición del Subtítulo</label>
                <Select
                    :modelValue="elementoSeleccionado.content.subtitle_position || 'below'"
                    @update:modelValue="(val) => elementoSeleccionado.content.subtitle_position = val"
                    :options="[
                        { label: 'Debajo del Título Principal', value: 'below' },
                        { label: 'Encima del Título Principal (Antetítulo / Texto Superior)', value: 'above' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Semántica HTML -->
        <div>
            <label class="block text-xs font-semibold mb-1">Etiqueta Semántica HTML</label>
            <Select
                v-model="elementoSeleccionado.content.tag"
                :options="[
                    { label: 'H1 (Encabezado Principal)', value: 'h1' },
                    { label: 'H2 (Sección Secundaria)', value: 'h2' },
                    { label: 'H3 (Subsección)', value: 'h3' },
                    { label: 'H4 (Subtítulo)', value: 'h4' },
                    { label: 'H5 (Pequeño)', value: 'h5' },
                    { label: 'H6 (Mini Kicker)', value: 'h6' },
                    { label: 'div (Sin peso semántico)', value: 'div' }
                ]"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <!-- Selector Responsive de Escala / Tamaño Visual (PC / Tablet / Móvil) -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2.5">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                    <i class="pi pi-text-height text-emerald-600 dark:text-emerald-400" />
                    <span>Escala / Tamaño Visual</span>
                </label>

                <!-- Selector de Dispositivo (PC / Tablet / Móvil) -->
                <div class="flex items-center bg-zinc-200/80 dark:bg-zinc-800 p-0.5 rounded-lg text-xs">
                    <button
                        type="button"
                        @click="dispositivoTamano = 'desktop'"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                            dispositivoTamano === 'desktop' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Escritorio (Desktop)'"
                    >
                        <i class="pi pi-desktop text-[10px]" />
                        <span>PC</span>
                    </button>
                    <button
                        type="button"
                        @click="dispositivoTamano = 'tablet'"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                            dispositivoTamano === 'tablet' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Tablet (Pantallas medianas)'"
                    >
                        <i class="pi pi-tablet text-[10px]" />
                        <span>Tablet</span>
                        <span v-if="elementoSeleccionado.content.size_tablet" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </button>
                    <button
                        type="button"
                        @click="dispositivoTamano = 'mobile'"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                            dispositivoTamano === 'mobile' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Móvil (Pantallas pequeñas)'"
                    >
                        <i class="pi pi-mobile text-[10px]" />
                        <span>Móvil</span>
                        <span v-if="elementoSeleccionado.content.size_mobile" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </button>
                </div>
            </div>

            <!-- Indicador del modo activo -->
            <div class="px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 text-[10px] text-emerald-700 dark:text-emerald-300 font-semibold flex items-center justify-between">
                <span>Tamaño en: <strong class="uppercase font-bold">{{ dispositivoTamano === 'desktop' ? 'Escritorio (PC)' : dispositivoTamano }}</strong></span>
                <span v-if="dispositivoTamano !== 'desktop'" class="text-[9px] text-emerald-600 dark:text-emerald-400 font-normal">Sobrescribe el valor de PC</span>
            </div>

            <!-- ESCRITORIO (PC) -->
            <div v-if="dispositivoTamano === 'desktop'">
                <Select
                    :modelValue="elementoSeleccionado.content.size || 'default'"
                    @update:modelValue="(val) => elementoSeleccionado.content.size = val"
                    :options="opcionesTamanoVisual"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- TABLET -->
            <div v-else-if="dispositivoTamano === 'tablet'">
                <Select
                    :modelValue="elementoSeleccionado.content.size_tablet || ''"
                    @update:modelValue="(val) => elementoSeleccionado.content.size_tablet = val"
                    :options="opcionesTamanoVisualTabletMobile"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- MÓVIL -->
            <div v-else-if="dispositivoTamano === 'mobile'">
                <Select
                    :modelValue="elementoSeleccionado.content.size_mobile || ''"
                    @update:modelValue="(val) => elementoSeleccionado.content.size_mobile = val"
                    :options="opcionesTamanoVisualTabletMobile"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Peso tipográfico y Transformación -->
        <div class="grid grid-cols-2 gap-3">
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
                        { label: 'Extra Negrita (Extrabold)', value: 'extrabold' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Transformación de Texto</label>
                <Select
                    :modelValue="elementoSeleccionado.content.transform || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.transform = val"
                    :options="[
                        { label: 'Normal (Sin cambios)', value: 'none' },
                        { label: 'MAYÚSCULAS', value: 'uppercase' },
                        { label: 'minúsculas', value: 'lowercase' },
                        { label: 'Capitalizar Palabras', value: 'capitalize' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Color y Decoración -->
        <div class="grid grid-cols-2 gap-3">
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
            <div>
                <label class="block text-xs font-semibold mb-1">Decoración Visual</label>
                <Select
                    :modelValue="elementoSeleccionado.content.decoration || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.decoration = val"
                    :options="[
                        { label: 'Sin Decoración', value: 'none' },
                        { label: 'Línea Inferior (Divider)', value: 'divider' },
                        { label: 'Barra Vertical Lateral (Bullet)', value: 'bullet' }
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
