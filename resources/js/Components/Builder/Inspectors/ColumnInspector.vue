<script setup>
/**
 * Inspector Atómico de Columna para el Page Builder.
 * Gestiona la alineación vertical del contenido (arriba, centro, abajo, distribuido),
 * imagen y color de fondo, superposición (overlay), color de texto, paddings, esquinas y CSS personalizado.
 */
import { ref, watch, computed } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import VideoUploader from '@/Components/Admin/VideoUploader.vue';
import AnimationBlock from './AnimationBlock.vue';
import {
    COLUMN_WIDTH_GROUPS,
    getColumnWidthOptionsWithInherit,
    COLUMN_ORDER_OPTIONS
} from '@/Config/gridPresets';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
    opcionesAlineacionVertical: { type: Array, default: () => [] },
    opcionesPadding: { type: Array, default: () => [] },
});

// Pestaña activa dentro del inspector de columna
const subTabActiva = ref('general');

function asegurarEstructuraColumna(col) {
    if (!col) return;
    if (!col.width || typeof col.width !== 'object') {
        const prevWidth = typeof col.width === 'string' && col.width.trim() ? col.width : 'inherit';
        col.width = {
            xs: '1/1',
            sm: 'inherit',
            md: 'inherit',
            lg: prevWidth,
            xl: 'inherit',
        };
    } else {
        if (!col.width.xs) col.width.xs = '1/1';
        if (!col.width.sm) col.width.sm = 'inherit';
        if (!col.width.md) col.width.md = 'inherit';
        if (!col.width.lg) col.width.lg = 'inherit';
        if (!col.width.xl) col.width.xl = 'inherit';
    }

    if (!col.order || typeof col.order !== 'object') {
        col.order = {
            xs: 'none',
            sm: 'none',
            md: 'none',
            lg: 'none',
            xl: 'none',
        };
    }
}

watch(() => props.elementoSeleccionado, (col) => {
    asegurarEstructuraColumna(col);
}, { immediate: true });

function updateWidth(bp, val) {
    if (!props.elementoSeleccionado) return;
    asegurarEstructuraColumna(props.elementoSeleccionado);
    props.elementoSeleccionado.width = {
        ...props.elementoSeleccionado.width,
        [bp]: val,
    };
}

function updateOrder(bp, val) {
    if (!props.elementoSeleccionado) return;
    asegurarEstructuraColumna(props.elementoSeleccionado);
    props.elementoSeleccionado.order = {
        ...props.elementoSeleccionado.order,
        [bp]: val,
    };
}

const opcionesAlineacionColumn = [
    { label: 'Arriba (Inicio)', value: 'start', icon: 'pi pi-align-top' },
    { label: 'Centro (Centrado Vertical)', value: 'center', icon: 'pi pi-align-center' },
    { label: 'Abajo (Pie)', value: 'end', icon: 'pi pi-align-bottom' },
    { label: 'Distribuido (Espacio Entre)', value: 'stretch', icon: 'pi pi-arrows-v' },
];

const opcionesOverlay = [
    { label: 'Sin superposición (Transparente)', value: null },
    { label: 'Oscuro Suave (20%)', value: 'black_20' },
    { label: 'Oscuro Medio (40%)', value: 'black_40' },
    { label: 'Oscuro Intenso (60%)', value: 'black_60' },
    { label: 'Claro Translúcido (50%)', value: 'white_50' },
];

const opcionesColorTexto = [
    { label: 'Por defecto (Heredar)', value: null },
    { label: 'Claro / Blanco (para fondos oscuros)', value: 'light' },
    { label: 'Oscuro / Negro (para fondos claros)', value: 'dark' },
];

const opcionesRadius = [
    { label: 'Sin redondeo (Recto)', value: 'none' },
    { label: 'Pequeño (sm)', value: 'sm' },
    { label: 'Mediano (md)', value: 'md' },
    { label: 'Grande (lg)', value: 'lg' },
    { label: 'Muy Grande (xl)', value: 'xl' },
    { label: 'Píldora Redonda (2xl)', value: '2xl' },
];

const opcionesBgVideoPosition = [
    { label: 'Centro (Centrado)', value: 'center center' },
    { label: 'Arriba Centro', value: 'top center' },
    { label: 'Abajo Centro', value: 'bottom center' },
    { label: 'Izquierda Centro', value: 'left center' },
    { label: 'Derecha Centro', value: 'right center' },
];

const opcionesBgVideoMobile = [
    { label: 'Mostrar imagen en móviles (Ahorro de datos)', value: 'fallback' },
    { label: 'Reproducir vídeo también en móviles', value: 'play' },
];

const opcionesBgVideoPlaybackRate = [
    { label: '1.0x (Velocidad normal)', value: '1.0' },
    { label: '0.75x (Cinemático suave)', value: '0.75' },
    { label: '0.5x (Cámara lenta)', value: '0.5' },
    { label: '1.25x (Rápido)', value: '1.25' },
];

const bgTypeModel = computed({
    get() {
        return props.elementoSeleccionado?.bg_type || 'image';
    },
    set(val) {
        if (props.elementoSeleccionado) {
            props.elementoSeleccionado.bg_type = val;
        }
    }
});

const bgVideoPositionModel = computed({
    get() {
        return props.elementoSeleccionado?.bg_video_position || 'center center';
    },
    set(val) {
        if (props.elementoSeleccionado) {
            props.elementoSeleccionado.bg_video_position = val;
        }
    }
});

const bgVideoMobileModel = computed({
    get() {
        return props.elementoSeleccionado?.bg_video_mobile || 'fallback';
    },
    set(val) {
        if (props.elementoSeleccionado) {
            props.elementoSeleccionado.bg_video_mobile = val;
        }
    }
});

const bgVideoPlaybackRateModel = computed({
    get() {
        return props.elementoSeleccionado?.bg_video_playback_rate || '1.0';
    },
    set(val) {
        if (props.elementoSeleccionado) {
            props.elementoSeleccionado.bg_video_playback_rate = val;
        }
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Navegación de Pestañas del Inspector de Columna -->
        <div class="flex items-center gap-1 border-b border-zinc-200 dark:border-zinc-800 pb-2">
            <button
                type="button"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    subTabActiva === 'general' ? 'bg-emerald-500 text-white shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                ]"
                @click="subTabActiva = 'general'"
            >
                <i class="pi pi-sliders-h text-xs" />
                <span>Disposición</span>
            </button>
            <button
                type="button"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    subTabActiva === 'ancho' ? 'bg-emerald-500 text-white shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                ]"
                @click="subTabActiva = 'ancho'"
            >
                <i class="pi pi-table text-xs" />
                <span>Ancho & Grid</span>
            </button>
            <button
                type="button"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    subTabActiva === 'fondo' ? 'bg-emerald-500 text-white shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                ]"
                @click="subTabActiva = 'fondo'"
            >
                <i class="pi pi-image text-xs" />
                <span>Fondo</span>
            </button>
            <button
                type="button"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    subTabActiva === 'avanzado' ? 'bg-emerald-500 text-white shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                ]"
                @click="subTabActiva = 'avanzado'"
            >
                <i class="pi pi-cog text-xs" />
                <span>Avanzado</span>
            </button>
        </div>

        <!-- PESTAÑA ANCHO & GRID RESPONSIVO -->
        <div v-show="subTabActiva === 'ancho'" class="space-y-3.5 p-3.5 bg-zinc-50/80 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800">
            <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                <i class="pi pi-table text-emerald-500 text-xs" />
                <span>Ancho de Columna por Breakpoint</span>
            </div>

            <!-- Móvil Vertical -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Móvil en Vertical (&lt; 640px)
                </label>
                <Select
                    :modelValue="elementoSeleccionado.width?.xs || '1/1'"
                    @update:modelValue="(val) => updateWidth('xs', val)"
                    :options="COLUMN_WIDTH_GROUPS"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Móvil Horizontal -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Móvil en Horizontal (&ge; 640px)
                </label>
                <Select
                    :modelValue="elementoSeleccionado.width?.sm || 'inherit'"
                    @update:modelValue="(val) => updateWidth('sm', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Tablet -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Tableta en Horizontal (&ge; 768px)
                </label>
                <Select
                    :modelValue="elementoSeleccionado.width?.md || 'inherit'"
                    @update:modelValue="(val) => updateWidth('md', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Escritorio -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Escritorio (&ge; 1024px)
                </label>
                <Select
                    :modelValue="elementoSeleccionado.width?.lg || 'inherit'"
                    @update:modelValue="(val) => updateWidth('lg', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Pantalla Grande -->
            <div>
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                    Pantalla grande (&ge; 1280px)
                </label>
                <Select
                    :modelValue="elementoSeleccionado.width?.xl || 'inherit'"
                    @update:modelValue="(val) => updateWidth('xl', val)"
                    :options="getColumnWidthOptionsWithInherit()"
                    optionGroupLabel="label"
                    optionGroupChildren="items"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Orden Visual Responsivo -->
            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                        Orden Visual de la Columna
                    </label>
                    <span class="text-[10px] text-zinc-400">Flexbox Order</span>
                </div>

                <!-- Orden en Escritorio (>= 1024px) -->
                <div>
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mb-0.5">
                        Escritorio (&ge; 1024px)
                    </label>
                    <Select
                        :modelValue="elementoSeleccionado.order?.lg || 'none'"
                        @update:modelValue="(val) => updateOrder('lg', val)"
                        :options="COLUMN_ORDER_OPTIONS"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Orden en Tableta (>= 768px) -->
                <div>
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mb-0.5">
                        Tableta (&ge; 768px)
                    </label>
                    <Select
                        :modelValue="elementoSeleccionado.order?.md || 'none'"
                        @update:modelValue="(val) => updateOrder('md', val)"
                        :options="COLUMN_ORDER_OPTIONS"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Orden en Móvil (< 768px) -->
                <div>
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mb-0.5">
                        Móvil (&lt; 768px)
                    </label>
                    <Select
                        :modelValue="elementoSeleccionado.order?.xs || 'none'"
                        @update:modelValue="(val) => updateOrder('xs', val)"
                        :options="COLUMN_ORDER_OPTIONS"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- PESTAÑA 1: DISPOSICIÓN Y ALINEACIÓN VERTICAL -->
        <div v-show="subTabActiva === 'general'" class="space-y-4">
            <!-- Colapsar Columna Si Está Vacía -->
            <div class="p-3 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <div class="space-y-0.5 min-w-0">
                        <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                            <i class="pi pi-arrows-alt text-emerald-500 text-xs" />
                            <span>Colapsar columna si está vacía</span>
                        </label>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-snug">
                            Si esta columna no tiene contenido en el artículo (ej. vídeo ausente), se ocultará automáticamente y la columna contigua ocupará todo el ancho.
                        </p>
                    </div>
                    <ToggleSwitch
                        :modelValue="Boolean(elementoSeleccionado.collapse_if_empty)"
                        @update:modelValue="(val) => elementoSeleccionado.collapse_if_empty = val"
                    />
                </div>
            </div>

            <!-- Alineación Vertical del Contenido de la Columna -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    Alineación Vertical del Contenido
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="op in opcionesAlineacionColumn"
                        :key="op.value"
                        type="button"
                        :class="[
                            'p-2.5 rounded-xl border text-left flex items-center gap-2 text-xs font-semibold transition-all cursor-pointer',
                            (elementoSeleccionado.align || 'start') === op.value
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                                : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300'
                        ]"
                        @click="elementoSeleccionado.align = op.value"
                    >
                        <i :class="[op.icon, 'text-sm shrink-0']" />
                        <span class="truncate">{{ op.label }}</span>
                    </button>
                </div>
                <p class="text-[11px] text-zinc-400 mt-1">
                    Controla cómo se distribuyen verticalmente los elementos dentro de esta columna.
                </p>
            </div>

            <!-- Relleno Interno (Padding) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Relleno Interno (Padding)</label>
                <Select
                    :modelValue="elementoSeleccionado.padding || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.padding = val"
                    :options="opcionesPadding"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Redondeo de Esquinas -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Esquinas Redondeadas</label>
                <Select
                    :modelValue="elementoSeleccionado.border_radius || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.border_radius = val"
                    :options="opcionesRadius"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Color del Texto -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Color del Texto de la Columna</label>
                <Select
                    :modelValue="elementoSeleccionado.text_color || null"
                    @update:modelValue="(val) => elementoSeleccionado.text_color = val"
                    :options="opcionesColorTexto"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Divisor Vertical / Separador -->
            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    Divisores y Separadores
                </label>
                <p class="text-[11px] text-zinc-400">
                    Los divisores simétricos entre columnas se configuran a nivel de la Fila contenedora.
                </p>
            </div>
        </div>

        <!-- PESTAÑA 2: FONDO, COLOR E IMAGEN / VÍDEO -->
        <div v-show="subTabActiva === 'fondo'" class="space-y-4">
            <!-- Selector de Tipo de Fondo Multimedia: Imagen o Vídeo -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Medio de Fondo de la Columna</label>
                <div class="grid grid-cols-2 gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800/80 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                    <button
                        type="button"
                        @click="bgTypeModel = 'image'"
                        :class="[
                            'py-1.5 px-3 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer',
                            bgTypeModel === 'image'
                                ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                        ]"
                    >
                        <i class="pi pi-image text-xs" />
                        <span>Imagen</span>
                    </button>
                    <button
                        type="button"
                        @click="bgTypeModel = 'video'"
                        :class="[
                            'py-1.5 px-3 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer',
                            bgTypeModel === 'video'
                                ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                        ]"
                    >
                        <i class="pi pi-video text-xs" />
                        <span>Vídeo</span>
                    </button>
                </div>
            </div>

            <!-- ═══ MODO 1: IMAGEN DE FONDO ═══ -->
            <div v-if="bgTypeModel === 'image'" class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Imagen de Fondo</label>
                <ImageUploader
                    :modelValue="elementoSeleccionado.bg_image || ''"
                    @update:modelValue="(val) => elementoSeleccionado.bg_image = val"
                    directorio="fondos"
                />
            </div>

            <!-- ═══ MODO 2: VÍDEO DE FONDO ═══ -->
            <div v-else-if="bgTypeModel === 'video'" class="space-y-3.5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Vídeo de Fondo (HTML5 MP4 / WebM)</label>
                    <VideoUploader
                        :modelValue="elementoSeleccionado.bg_video || ''"
                        @update:modelValue="(val) => elementoSeleccionado.bg_video = val"
                        placeholder="Seleccionar vídeo de la biblioteca o URL..."
                        help="Vídeo en bucle continuo y silenciado de fondo."
                    />
                </div>

                <!-- Panel de Ajustes de Vídeo de Columna -->
                <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200/60 dark:border-emerald-800/40 space-y-3 transition-all">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-800 dark:text-emerald-300">
                        <i class="pi pi-sliders-h text-xs" />
                        <span>Opciones de Vídeo de Fondo</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">Imagen Póster / Fallback Móvil</label>
                            <span
                                v-if="!elementoSeleccionado.bg_video_poster && elementoSeleccionado.bg_image"
                                class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium"
                            >
                                (Usando imagen previa como póster)
                            </span>
                        </div>
                        <ImageUploader
                            :modelValue="elementoSeleccionado.bg_video_poster || ''"
                            @update:modelValue="(val) => elementoSeleccionado.bg_video_poster = val"
                            directorio="fondos"
                            placeholder="Seleccionar imagen póster..."
                            help="Se muestra instantáneamente mientras carga el vídeo o en móviles para ahorrar datos."
                            :compact="true"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Comportamiento en Móviles</label>
                        <Select
                            v-model="bgVideoMobileModel"
                            :options="opcionesBgVideoMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Encuadre Focal</label>
                            <Select
                                v-model="bgVideoPositionModel"
                                :options="opcionesBgVideoPosition"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Velocidad</label>
                            <Select
                                v-model="bgVideoPlaybackRateModel"
                                :options="opcionesBgVideoPlaybackRate"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <div class="p-2 rounded-lg bg-emerald-100/50 dark:bg-emerald-900/20 text-[10.5px] text-emerald-900 dark:text-emerald-200 leading-relaxed flex items-start gap-2">
                        <i class="pi pi-info-circle text-xs mt-0.5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>El vídeo se reproduce silenciado, en bucle y se pausa automáticamente al salir del viewport para optimizar batería y FPS.</span>
                    </div>
                </div>
            </div>

            <!-- Color de Fondo de la Columna (Aplica a ambos como base) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Color de Fondo de la Columna</label>
                <div class="flex items-center gap-2">
                    <input
                        type="color"
                        :value="elementoSeleccionado.bg_color || '#ffffff'"
                        @input="(e) => elementoSeleccionado.bg_color = e.target.value"
                        class="w-9 h-9 rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-pointer p-0.5 bg-transparent shrink-0"
                    />
                    <InputText
                        :modelValue="elementoSeleccionado.bg_color || ''"
                        @update:modelValue="(val) => elementoSeleccionado.bg_color = val"
                        placeholder="ej: #f4f4f5 o transparent"
                        class="w-full text-xs"
                    />
                    <button
                        v-if="elementoSeleccionado.bg_color"
                        type="button"
                        @click="elementoSeleccionado.bg_color = ''"
                        class="text-xs text-red-500 hover:underline shrink-0 font-semibold px-1"
                    >
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Superposición Oscura / Translúcida (Overlay) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Superposición sobre Fondo (Overlay)</label>
                <Select
                    :modelValue="elementoSeleccionado.bg_overlay || null"
                    @update:modelValue="(val) => elementoSeleccionado.bg_overlay = val"
                    :options="opcionesOverlay"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- PESTAÑA 3: PERSONALIZACIÓN AVANZADA Y ANIMACIONES -->
        <div v-show="subTabActiva === 'avanzado'" class="space-y-4">
            <!-- Animación de Entrada -->
            <AnimationBlock :elemento="elementoSeleccionado" />

            <!-- Identificador HTML único (ID) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">ID del Elemento HTML</label>
                <InputText
                    :modelValue="elementoSeleccionado.element_id || ''"
                    @update:modelValue="(val) => elementoSeleccionado.element_id = val"
                    placeholder="ej: columna-destacada"
                    class="w-full text-xs"
                />
            </div>

            <!-- Clases CSS Personalizadas -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Clases CSS Personalizadas</label>
                <InputText
                    :modelValue="elementoSeleccionado.custom_classes || ''"
                    @update:modelValue="(val) => elementoSeleccionado.custom_classes = val"
                    placeholder="ej: shadow-xl border-l-4 border-emerald-500"
                    class="w-full text-xs"
                />
            </div>

            <!-- Estilos CSS Personalizados Scoped -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">CSS Personalizado</label>
                <Textarea
                    :modelValue="elementoSeleccionado.custom_css || ''"
                    @update:modelValue="(val) => elementoSeleccionado.custom_css = val"
                    rows="3"
                    placeholder="backdrop-filter: blur(8px);"
                    class="w-full text-xs font-mono"
                />
            </div>
        </div>
    </div>
</template>
