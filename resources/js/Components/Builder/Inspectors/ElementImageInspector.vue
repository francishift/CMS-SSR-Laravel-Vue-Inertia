<script setup>
/**
 * Inspector Atómico para el Elemento Imagen.
 * Controla subida de imagen, texto alternativo, bordes redondeados (Border Radius), dimensiones en píxeles y filtros visuales (Imagen Blanca).
 */
import { computed, inject } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import DynamicTagSelector from '../DynamicTagSelector.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

const articuloMuestra = inject('articuloMuestra', computed(() => null));
const categoriaMuestra = inject('categoriaMuestra', computed(() => null));
const descargaMuestra = inject('descargaMuestra', computed(() => null));

/**
 * Resuelve variables dinámicas de imagen ({download.featured_image}, {article.featured_image}, etc.)
 * para mostrar su miniatura real dentro del panel Inspector.
 */
const resolvedPreviewSrc = computed(() => {
    const raw = String(props.elementoSeleccionado?.content?.src || '').trim();
    if (!raw.startsWith('{') || !raw.endsWith('}')) return '';

    const key = raw.toLowerCase();
    if (key === '{download.featured_image}' || key === '{download.image}') {
        return descargaMuestra.value?.featured_image || '';
    }
    if (key === '{article.featured_image}' || key === '{article.image}') {
        return articuloMuestra.value?.featured_image || '';
    }
    if (key === '{category.featured_image}' || key === '{category.image}') {
        return categoriaMuestra.value?.featured_image || articuloMuestra.value?.category?.featured_image || '';
    }
    if (key.startsWith('{article.extra_fields.')) {
        const extraName = raw.slice('{article.extra_fields.'.length, -1);
        const extraData = articuloMuestra.value?.extra_fields_data || {};
        const matchKey = Object.keys(extraData).find(k => k.toLowerCase() === extraName.toLowerCase());
        return matchKey ? String(extraData[matchKey] || '') : '';
    }
    return '';
});

function insertarEtiquetaSrc(tag) {
    props.elementoSeleccionado.content.src = tag;
}

function insertarEtiquetaAlt(tag) {
    props.elementoSeleccionado.content.alt = (props.elementoSeleccionado.content.alt || '') + tag;
}
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- 1. SELECCIÓN DE IMAGEN -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-semibold">Imagen de Ilustración</label>
                <DynamicTagSelector tipo="image" @insertar="insertarEtiquetaSrc" />
            </div>
            <ImageUploader
                v-model="elementoSeleccionado.content.src"
                :preview-src="resolvedPreviewSrc"
                directorio="paginas"
                ayuda="Sube una imagen o inserta una variable dinámica."
            />
        </div>

        <!-- 2. ALT TEXT -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-semibold">Texto Alternativo (Alt Text - SEO)</label>
                <DynamicTagSelector @insertar="insertarEtiquetaAlt" />
            </div>
            <InputText v-model="elementoSeleccionado.content.alt" placeholder="Descripción de la imagen para Google..." class="w-full text-xs" />
        </div>

        <!-- 3. TAMAÑO EN PÍXELES (ANCHO Y ALTO MÁXIMO) -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                <i class="pi pi-arrows-alt text-xs text-emerald-500" />
                Dimensiones y Tamaño en Píxeles (px)
            </span>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Ancho Máximo (px)</label>
                    <InputNumber
                        v-model="elementoSeleccionado.content.max_width_px"
                        placeholder="ej. 200"
                        suffix=" px"
                        class="w-full text-xs"
                        :min="10"
                        :max="2000"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Alto Máximo (px)</label>
                    <InputNumber
                        v-model="elementoSeleccionado.content.max_height_px"
                        placeholder="ej. 150"
                        suffix=" px"
                        class="w-full text-xs"
                        :min="10"
                        :max="2000"
                    />
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Alineación de la Imagen</label>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="elementoSeleccionado.content.align = 'left'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold border flex items-center gap-1.5 transition-all cursor-pointer',
                            (!elementoSeleccionado.content.align || elementoSeleccionado.content.align === 'left')
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold'
                                : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400'
                        ]"
                    >
                        <i class="pi pi-align-left text-xs" />
                        <span>Izquierda</span>
                    </button>
                    <button
                        type="button"
                        @click="elementoSeleccionado.content.align = 'center'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold border flex items-center gap-1.5 transition-all cursor-pointer',
                            elementoSeleccionado.content.align === 'center'
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold'
                                : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400'
                        ]"
                    >
                        <i class="pi pi-align-center text-xs" />
                        <span>Centro</span>
                    </button>
                    <button
                        type="button"
                        @click="elementoSeleccionado.content.align = 'right'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold border flex items-center gap-1.5 transition-all cursor-pointer',
                            elementoSeleccionado.content.align === 'right'
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold'
                                : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400'
                        ]"
                    >
                        <i class="pi pi-align-right text-xs" />
                        <span>Derecha</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. FILTRO VISUAL Y MONOCROMO (EFECTO BLANCO COMO LOGO) -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                        <i class="pi pi-sun text-xs text-amber-500" />
                        Hacer Imagen Blanca (Efecto Logo)
                    </span>
                    <span class="block text-[11px] text-zinc-500 dark:text-zinc-400">Convierte la imagen en blanca pura (ideal para logos o iconos sobre fondo oscuro)</span>
                </div>
                <ToggleSwitch v-model="elementoSeleccionado.content.is_white" />
            </div>

            <div v-if="!elementoSeleccionado.content.is_white">
                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Filtro de Color Avanzado</label>
                <Select
                    :modelValue="elementoSeleccionado.content.color_filter || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.color_filter = val"
                    :options="[
                        { label: 'Color Original (Sin Filtro)', value: 'none' },
                        { label: 'Blanca Pura (Monocromo Blanco)', value: 'white' },
                        { label: 'Blanca Solo en Modo Oscuro', value: 'dark_white' },
                        { label: 'Negra Pura (Monocromo Negro)', value: 'black' },
                        { label: 'Escala de Grises (Grayscale)', value: 'grayscale' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- 5. BORDES Y AJUSTE -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Bordes Redondeados (Corners)</label>
                <Select
                    :modelValue="elementoSeleccionado.content.rounded || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.rounded = val"
                    :options="[
                        { label: 'Recto (Sin redondeado - 0px)', value: 'none' },
                        { label: 'Pequeño (4px)', value: 'sm' },
                        { label: 'Mediano (8px)', value: 'md' },
                        { label: 'Grande (12px)', value: 'lg' },
                        { label: 'Extra Grande (16px)', value: 'xl' },
                        { label: '2X Grande (24px)', value: '2xl' },
                        { label: 'Redondo / Círculo', value: 'full' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Ajuste de Imagen (Object Fit)</label>
                <Select
                    :modelValue="elementoSeleccionado.content.fit || 'cover'"
                    @update:modelValue="(val) => elementoSeleccionado.content.fit = val"
                    :options="[
                        { label: 'Cubrir (Cover)', value: 'cover' },
                        { label: 'Contener (Contain)', value: 'contain' },
                        { label: 'Escalar (Scale)', value: 'scale' }
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
