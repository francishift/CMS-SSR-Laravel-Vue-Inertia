<script setup>
/**
 * Inspector Atómico Avanzado para el Elemento Grid de Artículos / Tarjetas (Grid dynamic, manual & mixed).
 * Controla modo de contenido, columnas responsive, gap entre columnas, presets visuales de tarjetas,
 * proporción de imagen, estilos de botón, fuentes dinámicas CMS, mapeo de campos estándar y personalizados (extra_fields_data)
 * y gestor interactivo mediante Acordeón Plegable con Drag & Drop para tarjetas manuales.
 */
import { computed, ref } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import draggable from 'vuedraggable';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import WysiwygEditor from '@/Components/Admin/WysiwygEditor.vue';
import { OPCIONES_TAMANO_VISUAL, OPCIONES_TAMANO_VISUAL_RESPONSIVE } from '@/Utils/headingScale';

const dispositivoTamanoTitulo = ref('desktop');
const opcionesTamanoVisualGrid = OPCIONES_TAMANO_VISUAL;
const opcionesTamanoVisualGridTabletMobile = OPCIONES_TAMANO_VISUAL_RESPONSIVE;
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
    opcionesCategoriasConTodas: { type: Array, required: true },
});

const emit = defineEmits([
    'añadir-item-manual',
    'eliminar-item-manual',
    'start-drag-item',
    'drop-item',
    'mover-item'
]);

// Obtener la categoría seleccionada para inspeccionar sus campos personalizados (extra_fields_schema).
// Usa String() para evitar fallos de comparación estricta entre int y string (ej: 3 vs '3').
const categoriaSeleccionadaObj = computed(() => {
    const catId = props.elementoSeleccionado.content?.dynamic_binding?.category_id;
    if (!catId || catId === 'all' || catId === 'current') return null;
    return props.opcionesCategoriasConTodas.find(c => c.id != null && String(c.id) === String(catId)) || null;
});

/**
 * Esquema de campos extra agregado, usado por los computed de mapeo.
 *
 * - Categoría específica: devuelve el esquema de esa categoría.
 * - Categoría activa / Todas: agrega los campos de TODAS las categorías
 *   eliminando duplicados por clave, para que el usuario pueda mapear
 *   cualquier campo extra independientemente de la categoría activa.
 */
const extraFieldsSchemaAgregado = computed(() => {
    // Caso 1: Categoría específica → esquema directo
    if (categoriaSeleccionadaObj.value?.extra_fields_schema) {
        return categoriaSeleccionadaObj.value.extra_fields_schema;
    }

    // Caso 2: Categoría activa o Todas → unión de esquemas de todas las categorías
    const catId = props.elementoSeleccionado.content?.dynamic_binding?.category_id;
    if (catId === 'current' || !catId || catId === 'all') {
        const clavesVistas = new Set();
        const camposUnicos = [];

        props.opcionesCategoriasConTodas.forEach(cat => {
            if (!Array.isArray(cat.extra_fields_schema)) return;
            cat.extra_fields_schema.forEach(field => {
                if (!field.key || clavesVistas.has(field.key)) return;
                clavesVistas.add(field.key);
                camposUnicos.push(field);
            });
        });

        return camposUnicos;
    }

    return [];
});

// Detectar si el módulo autónomo de Descargas está disponible en el portal
const moduloDescargasDisponible = computed(() => {
    try {
        return typeof route === 'function' && route().has('downloads.admin.index');
    } catch {
        return false;
    }
});

const esFuenteDescargas = computed(() =>
    props.elementoSeleccionado?.content?.dynamic_binding?.source === 'downloads'
);

// Opciones dinámicas para el mapeo de campos (Campos estándar + Campos extra de la categoría o Descargas)
const opcionesMapeoTexto = computed(() => {
    if (esFuenteDescargas.value) {
        return [
            { label: 'Por Defecto (Título de la Descarga)', value: 'title' },
            { label: 'Subtítulo', value: 'subtitle' },
            { label: 'Compatibilidad (YOOtheme / CMS)', value: 'compatibility' },
            { label: 'Descripción Corta', value: 'short_description' },
            { label: 'Versión (ej. v1.0.0)', value: 'version' },
        ];
    }

    const base = [
        { label: 'Por Defecto (Título del Artículo)', value: 'title' },
        { label: 'Extracto / Resumen Corto', value: 'excerpt' },
        { label: 'Nombre de la Categoría', value: 'category.name' },
        { label: 'Fecha de Publicación', value: 'published_at' },
    ];

    const schema = extraFieldsSchemaAgregado.value;
    if (Array.isArray(schema) && schema.length > 0) {
        schema.forEach(field => {
            base.push({
                label: `Campo Extra: ${field.label || field.key}`,
                value: `extra_fields_data.${field.key}`
            });
        });
    }

    return base;
});

const opcionesMapeoDescripcion = computed(() => {
    if (esFuenteDescargas.value) {
        return [
            { label: 'Por Defecto (Descripción Corta)', value: 'short_description' },
            { label: 'Subtítulo', value: 'subtitle' },
            { label: 'Compatibilidad (YOOtheme / CMS)', value: 'compatibility' },
            { label: 'Tamaño del Paquete ZIP', value: 'formatted_file_size' },
            { label: 'Sin Descripción', value: 'none' },
        ];
    }

    const base = [
        { label: 'Por Defecto (Extracto del Artículo)', value: 'excerpt' },
        { label: 'Título del Artículo', value: 'title' },
        { label: 'Nombre de la Categoría', value: 'category.name' },
    ];

    const schema = extraFieldsSchemaAgregado.value;
    if (Array.isArray(schema) && schema.length > 0) {
        schema.forEach(field => {
            base.push({
                label: `Campo Extra: ${field.label || field.key}`,
                value: `extra_fields_data.${field.key}`
            });
        });
    }

    return base;
});

const opcionesMapeoBadge = computed(() => {
    if (esFuenteDescargas.value) {
        return [
            { label: 'Precio + Versión (ej. Gratis · v1.0.0)', value: 'version_price' },
            { label: 'Precio / Gratuidad (Gratis / €)', value: 'formatted_price' },
            { label: 'Versión (ej. v1.0.0)', value: 'version' },
            { label: 'Compatibilidad', value: 'compatibility' },
            { label: 'Tamaño del ZIP', value: 'formatted_file_size' },
            { label: 'Sin Insignia', value: 'none' },
        ];
    }

    const base = [
        { label: 'Por Defecto (Nombre de Categoría)', value: 'category.name' },
        { label: 'Fecha de Publicación', value: 'published_at' },
    ];

    const schema = extraFieldsSchemaAgregado.value;
    if (Array.isArray(schema) && schema.length > 0) {
        schema.forEach(field => {
            base.push({
                label: `Campo Extra: ${field.label || field.key}`,
                value: `extra_fields_data.${field.key}`
            });
        });
    }

    return base;
});

const opcionesMapeoImagen = computed(() => {
    const base = [
        { label: 'Por Defecto (Imagen Destacada)', value: 'featured_image' },
    ];

    const schema = extraFieldsSchemaAgregado.value;
    if (Array.isArray(schema) && schema.length > 0) {
        schema.forEach(field => {
            // Incluir todos los campos cuyo tipo sea 'image' o 'url' (pueden contener rutas de imagen)
            if (field.type === 'image' || field.type === 'url') {
                base.push({
                    label: `Campo Extra: ${field.label || field.key}`,
                    value: `extra_fields_data.${field.key}`
                });
            }
        });
    }

    return base;
});

const opcionesMapeoIcono = computed(() => {
    const base = [
        { label: 'Por Defecto (Icono del Artículo)', value: 'icon' },
        { label: 'Sin icono', value: 'none' },
    ];

    const schema = extraFieldsSchemaAgregado.value;
    if (Array.isArray(schema) && schema.length > 0) {
        schema.forEach(field => {
            // Incluir campos de tipo 'icon' o 'text' que puedan contener clases de icono
            if (field.type === 'icon' || field.type === 'text') {
                base.push({
                    label: `Campo Extra: ${field.label || field.key}`,
                    value: `extra_fields_data.${field.key}`
                });
            }
        });
    }

    return base;
});
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- BLOQUE 1: MODO Y DISTRIBUCIÓN RESPONSIVE -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-th-large text-emerald-600 dark:text-emerald-400" />
                <span>Modo y Distribución de Cuadrícula</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Modo de Contenido</label>
                <Select
                    :modelValue="elementoSeleccionado.content?.mode || 'dynamic'"
                    @update:modelValue="(val) => {
                        if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                        elementoSeleccionado.content.mode = val;
                    }"
                    :options="[
                        { label: 'Dinámico (Consultar Artículos del CMS en tiempo real)', value: 'dynamic' },
                        { label: 'Manual (Crear Tarjetas / Servicios personalizados)', value: 'manual' },
                        { label: 'Mixto (Artículos dinámicos + Tarjetas manuales)', value: 'mixed' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                    <label class="block text-[11px] font-semibold mb-1">Cols Escritorio</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.columns_desktop || elementoSeleccionado.content?.columns || 3"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.columns_desktop = val;
                            elementoSeleccionado.content.columns = val;
                        }"
                        :options="[
                            { label: '1 Columna', value: 1 },
                            { label: '2 Columnas', value: 2 },
                            { label: '3 Columnas', value: 3 },
                            { label: '4 Columnas', value: 4 }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold mb-1">Cols Tablet</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.columns_tablet || 'auto'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.columns_tablet = val;
                        }"
                        :options="[
                            { label: 'Auto (Adaptativo)', value: 'auto' },
                            { label: '1 Columna', value: '1' },
                            { label: '2 Columnas', value: '2' },
                            { label: '3 Columnas', value: '3' },
                            { label: '4 Columnas', value: '4' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold mb-1">Cols Móvil</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.columns_mobile || '1'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.columns_mobile = val;
                        }"
                        :options="[
                            { label: '1 Col (100%)', value: '1' },
                            { label: '2 Cols (50%)', value: '2' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Alineación Grid</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.grid_align || 'left'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.grid_align = val;
                        }"
                        :options="[
                            { label: 'Izquierda', value: 'left' },
                            { label: 'Centrado (Centrar tarjetas)', value: 'center' },
                            { label: 'Derecha', value: 'right' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Separación entre Tarjetas</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.gap || 'md'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.gap = val;
                        }"
                        :options="[
                            { label: 'Compacto (16px)', value: 'sm' },
                            { label: 'Estándar (24px)', value: 'md' },
                            { label: 'Espacioso (32px)', value: 'lg' },
                            { label: 'Muy Espacioso (48px)', value: 'xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- BLOQUE 2: ESTILO VISUAL DE TARJETAS -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-palette text-emerald-600 dark:text-emerald-400" />
                <span>Estilo Visual de Tarjetas</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Alineación Texto Interno</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.text_align || 'left'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.text_align = val;
                        }"
                        :options="[
                            { label: 'Izquierda', value: 'left' },
                            { label: 'Centrado', value: 'center' },
                            { label: 'Derecha', value: 'right' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Preset de Tarjeta</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.card_style || 'default'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.card_style = val;
                        }"
                        :options="[
                            { label: 'Estándar (Tarjeta Limpia con Borde)', value: 'default' },
                            { label: 'Sombra Elevada en Hover', value: 'shadow' },
                            { label: 'Plana Transparente (Sin Fondo)', value: 'flat' },
                            { label: 'Borde Primario Accent (Glow)', value: 'accent' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- Gradientes de Fondo Personalizados para Tarjetas (Modo Claro / Oscuro) -->
            <div class="space-y-3 p-3 bg-zinc-100/70 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60">
                <div class="flex items-center gap-2 mb-1">
                    <i class="pi pi-palette text-xs text-indigo-500" />
                    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-200">Fondo Visual de Tarjeta (CSS Gradiente)</span>
                </div>
                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 leading-relaxed -mt-1">
                    Pega valores CSS de <code class="bg-zinc-200 dark:bg-zinc-700 px-1 rounded text-[9px]">background-image</code> (gradientes, radial-gradient, linear-gradient, etc.). Deja vacío para usar el fondo por defecto del preset seleccionado.
                </p>
                <div>
                    <label class="block text-[11px] font-semibold mb-1 flex items-center gap-1.5">
                        <i class="pi pi-sun text-[10px] text-amber-500" />
                        Fondo Modo Claro
                    </label>
                    <Textarea
                        :modelValue="elementoSeleccionado.content?.card_bg_gradient_light || ''"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.card_bg_gradient_light = val;
                        }"
                        placeholder="ej. radial-gradient(80% 120% at 80% 20%, rgba(99,200,170,0.15) 0%, transparent 60%), linear-gradient(#f8fafc, #f1f5f9)"
                        rows="3"
                        class="w-full text-[11px] font-mono"
                        autoResize
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold mb-1 flex items-center gap-1.5">
                        <i class="pi pi-moon text-[10px] text-indigo-400" />
                        Fondo Modo Oscuro
                    </label>
                    <Textarea
                        :modelValue="elementoSeleccionado.content?.card_bg_gradient_dark || ''"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.card_bg_gradient_dark = val;
                        }"
                        placeholder="ej. radial-gradient(127% 234% at 10% 142%,#d97e4b22 0,#fff0 37%),radial-gradient(67% 136% at 77% 24%,#0400ff17 0,#a3a78800 80%),linear-gradient(#18132999 0 100%)"
                        rows="3"
                        class="w-full text-[11px] font-mono"
                        autoResize
                    />
                </div>
            </div>

            <div class="space-y-3 p-3 bg-zinc-100/70 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60">
                <div>
                    <label class="block text-xs font-semibold mb-1">Etiqueta Semántica Título</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.title_tag || 'h3'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.title_tag = val;
                        }"
                        :options="[
                            { label: 'H1 (Encabezado Principal)', value: 'h1' },
                            { label: 'H2 (Sección Secundaria)', value: 'h2' },
                            { label: 'H3 (Subsección / Tarjeta)', value: 'h3' },
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

                <!-- Selector Responsive de Escala Visual del Título de Tarjeta -->
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
                                @click="dispositivoTamanoTitulo = 'desktop'"
                                :class="[
                                    'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                                    dispositivoTamanoTitulo === 'desktop' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                ]"
                                v-tooltip.top="'Escritorio (Desktop)'"
                            >
                                <i class="pi pi-desktop text-[10px]" />
                                <span>PC</span>
                            </button>
                            <button
                                type="button"
                                @click="dispositivoTamanoTitulo = 'tablet'"
                                :class="[
                                    'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                                    dispositivoTamanoTitulo === 'tablet' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                ]"
                                v-tooltip.top="'Tablet (Pantallas medianas)'"
                            >
                                <i class="pi pi-tablet text-[10px]" />
                                <span>Tablet</span>
                                <span v-if="elementoSeleccionado.content?.title_size_tablet" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            </button>
                            <button
                                type="button"
                                @click="dispositivoTamanoTitulo = 'mobile'"
                                :class="[
                                    'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                                    dispositivoTamanoTitulo === 'mobile' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
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
                    <div v-if="dispositivoTamanoTitulo === 'desktop'">
                        <Select
                            :modelValue="elementoSeleccionado.content?.title_size || 'default'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.title_size = val;
                            }"
                            :options="opcionesTamanoVisualGrid"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- TABLET -->
                    <div v-else-if="dispositivoTamanoTitulo === 'tablet'">
                        <Select
                            :modelValue="elementoSeleccionado.content?.title_size_tablet || ''"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.title_size_tablet = val;
                            }"
                            :options="opcionesTamanoVisualGridTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- MÓVIL -->
                    <div v-else-if="dispositivoTamanoTitulo === 'mobile'">
                        <Select
                            :modelValue="elementoSeleccionado.content?.title_size_mobile || ''"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.title_size_mobile = val;
                            }"
                            :options="opcionesTamanoVisualGridTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Tamaño Texto / Descripción</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.text_size || 'xs'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.text_size = val;
                        }"
                        :options="[
                            { label: 'Muy Pequeño / Compacto (12px)', value: 'xs' },
                            { label: 'Pequeño (14px)', value: 'sm' },
                            { label: 'Medio / Estándar (16px)', value: 'base' },
                            { label: 'Grande (18px)', value: 'lg' },
                            { label: 'Extra Grande (20px)', value: 'xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- VISIBILIDAD DE ELEMENTOS DE LA TARJETA (TÍTULO, IMAGEN, ICONO, DESCRIPCIÓN, BADGE, BOTÓN) -->
            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-800 space-y-2.5">
                <div class="flex items-center gap-1.5 text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">
                    <i class="pi pi-eye text-xs text-emerald-600 dark:text-emerald-400" />
                    <span>Elementos Visibles de la Tarjeta</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <!-- Mostrar Título -->
                    <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-zinc-800/80 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">Título</label>
                            <span class="block text-[10px] text-zinc-400 truncate">Cabecera</span>
                        </div>
                        <ToggleSwitch
                            :modelValue="elementoSeleccionado.content?.show_title !== false"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.show_title = val;
                            }"
                            class="shrink-0"
                        />
                    </div>

                    <!-- Mostrar Imagen -->
                    <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-zinc-800/80 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">Imagen</label>
                            <span class="block text-[10px] text-zinc-400 truncate">Destacada</span>
                        </div>
                        <ToggleSwitch
                            :modelValue="elementoSeleccionado.content?.show_image !== false"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.show_image = val;
                            }"
                            class="shrink-0"
                        />
                    </div>

                    <!-- Mostrar Icono -->
                    <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-zinc-800/80 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">Icono</label>
                            <span class="block text-[10px] text-zinc-400 truncate">Símbolo</span>
                        </div>
                        <ToggleSwitch
                            :modelValue="elementoSeleccionado.content?.show_icon !== false"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.show_icon = val;
                            }"
                            class="shrink-0"
                        />
                    </div>

                    <!-- Mostrar Descripción -->
                    <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-zinc-800/80 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">Descripción</label>
                            <span class="block text-[10px] text-zinc-400 truncate">Extracto</span>
                        </div>
                        <ToggleSwitch
                            :modelValue="elementoSeleccionado.content?.show_description !== false"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.show_description = val;
                            }"
                            class="shrink-0"
                        />
                    </div>

                    <!-- Mostrar Insignia / Badge -->
                    <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-zinc-800/80 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">Insignia</label>
                            <span class="block text-[10px] text-zinc-400 truncate">Categoría</span>
                        </div>
                        <ToggleSwitch
                            :modelValue="elementoSeleccionado.content?.show_badge !== false"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.show_badge = val;
                            }"
                            class="shrink-0"
                        />
                    </div>

                    <!-- Mostrar Botón / Enlace -->
                    <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-zinc-800/80 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">Botón</label>
                            <span class="block text-[10px] text-zinc-400 truncate">Enlace</span>
                        </div>
                        <ToggleSwitch
                            :modelValue="elementoSeleccionado.content?.show_button !== false"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.show_button = val;
                            }"
                            class="shrink-0"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Proporción de Imagen</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.image_ratio || '16:9'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.image_ratio = val;
                        }"
                        :options="[
                            { label: '16:9 (Panorámica)', value: '16:9' },
                            { label: '4:3 (Fotografía)', value: '4:3' },
                            { label: '1:1 (Cuadrada)', value: '1:1' },
                            { label: 'Auto (Natural / Completa)', value: 'auto' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Ajuste de Imagen (Object Fit)</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.image_fit || 'cover'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.image_fit = val;
                        }"
                        :options="[
                            { label: 'Recortar y Llenar (Cover)', value: 'cover' },
                            { label: 'Sin Recortar (Contain / Completa)', value: 'contain' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Margen de Relleno de Imagen</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.image_position || 'boxed'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.image_position = val;
                        }"
                        :options="[
                            { label: 'Con Margen Interno (Boxed)', value: 'boxed' },
                            { label: 'Sin Margen de Relleno (A Sangre)', value: 'flush' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Estilo de Botón / Acción</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.button_style || 'link'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.button_style = val;
                        }"
                        :options="[
                            { label: 'Primario', value: 'button_primary' },
                            { label: 'Secundario', value: 'button_secondary' },
                            { label: 'Contorno', value: 'button_outline' },
                            { label: 'Enlace', value: 'link' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Tamaño del Botón</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.button_size || 'md'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.button_size = val;
                        }"
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
                <div>
                    <label class="block text-xs font-semibold mb-1">Acción al Hacer Clic en la Tarjeta</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.card_link_mode || 'button'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.card_link_mode = val;
                        }"
                        :options="[
                            { label: 'Solo en el Botón (Ver más)', value: 'button' },
                            { label: 'Toda la Tarjeta Clicable (Enlace completo)', value: 'card' },
                            { label: 'Ambos (Tarjeta Clicable + Botón Visual)', value: 'both' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- OPCIONES AVANZADAS DE POSICIÓN Y ESTILO DE ICONO -->
            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-800 space-y-3">
                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                    <i class="pi pi-star text-xs" />
                    <span>Disposición & Estilo del Icono</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Posición del Icono</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.icon_position || 'top_card'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.icon_position = val;
                            }"
                            :options="[
                                { label: 'Bloque Superior Tarjeta', value: 'top_card' },
                                { label: 'Encima del Título', value: 'above_title' },
                                { label: 'Debajo del Título', value: 'below_title' },
                                { label: 'A la Izquierda del Título', value: 'left_title' },
                                { label: 'A la Derecha del Título', value: 'right_title' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Alineación Vertical</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.icon_vertical_align || 'items-center'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.icon_vertical_align = val;
                            }"
                            :options="[
                                { label: 'Arriba (Start)', value: 'items-start' },
                                { label: 'Centro (Center)', value: 'items-center' },
                                { label: 'Abajo (End)', value: 'items-end' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Tamaño del Icono</label>
                            <Select
                                :modelValue="elementoSeleccionado.content?.icon_size || 'md'"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                    elementoSeleccionado.content.icon_size = val;
                                }"
                                :options="[
                                    { label: 'Pequeño (16px)', value: 'xs' },
                                    { label: 'Normal (24px)', value: 'sm' },
                                    { label: 'Mediano (32px)', value: 'md' },
                                    { label: 'Grande (48px)', value: 'lg' },
                                    { label: 'Extra Grande (64px)', value: 'xl' },
                                    { label: 'Gigante (80px)', value: '2xl' },
                                    { label: 'Enorme (96px)', value: '3xl' },
                                    { label: 'Máximo (120px)', value: '4xl' },
                                    { label: 'Personalizado (px)', value: 'custom' }
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>

                        <div v-if="elementoSeleccionado.content?.icon_size === 'custom'">
                            <label class="block text-xs font-semibold mb-1">Tamaño en Píxeles</label>
                            <InputNumber
                                :modelValue="elementoSeleccionado.content?.icon_size_px || 48"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                    elementoSeleccionado.content.icon_size_px = val;
                                }"
                                :min="12"
                                :max="300"
                                suffix=" px"
                                class="w-full text-xs"
                            />
                        </div>

                        <div v-else>
                            <label class="block text-xs font-semibold mb-1">Margen / Separación</label>
                            <Select
                                :modelValue="elementoSeleccionado.content?.icon_margin || 'sm'"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                    elementoSeleccionado.content.icon_margin = val;
                                }"
                                :options="[
                                    { label: 'Sin margen (0px)', value: 'none' },
                                    { label: 'Muy Ajustado (4px)', value: 'xs' },
                                    { label: 'Normal (8px)', value: 'sm' },
                                    { label: 'Holgado (12px)', value: 'md' },
                                    { label: 'Grande (16px)', value: 'lg' }
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <div v-if="elementoSeleccionado.content?.icon_size === 'custom'" class="w-full">
                        <label class="block text-xs font-semibold mb-1">Margen / Separación</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.icon_margin || 'sm'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.icon_margin = val;
                            }"
                            :options="[
                                { label: 'Sin margen (0px)', value: 'none' },
                                { label: 'Muy Ajustado (4px)', value: 'xs' },
                                { label: 'Normal (8px)', value: 'sm' },
                                { label: 'Holgado (12px)', value: 'md' },
                                { label: 'Grande (16px)', value: 'lg' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- CONTENEDOR DE FONDO Y COLORES DEL ICONO -->
                <div>
                    <label class="block text-xs font-semibold mb-1">Estilo de Contenedor de Icono</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.icon_style_mode || (elementoSeleccionado.content?.icon_position === 'top_card' || !elementoSeleccionado.content?.icon_position ? 'soft_bg' : 'none')"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.icon_style_mode = val;
                        }"
                        :options="[
                            { label: 'Sin Fondo (Solo Icono)', value: 'none' },
                            { label: 'Fondo Suave Cuadrado', value: 'soft_bg' },
                            { label: 'Fondo Sólido Cuadrado', value: 'solid_bg' },
                            { label: 'Fondo Suave Circular', value: 'circle_soft' },
                            { label: 'Fondo Sólido Circular', value: 'circle_solid' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Color del Icono</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.icon_color || 'primary'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.icon_color = val;
                            }"
                            :options="[
                                { label: 'Primario Corporativo', value: 'primary' },
                                { label: 'Oscuro / Negro', value: 'dark' },
                                { label: 'Blanco', value: 'light' },
                                { label: 'Gris Neutro', value: 'zinc' },
                                { label: 'Verde Esmeralda', value: 'emerald' },
                                { label: 'Índigo / Púrpura', value: 'indigo' },
                                { label: 'Azul', value: 'blue' },
                                { label: 'Ámbar / Amarillo', value: 'amber' },
                                { label: 'Rosa / Rojo', value: 'rose' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <div v-if="(elementoSeleccionado.content?.icon_style_mode || 'soft_bg') !== 'none'">
                        <label class="block text-xs font-semibold mb-1">Color de Fondo</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.icon_bg_color || 'primary_soft'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.icon_bg_color = val;
                            }"
                            :options="[
                                { label: 'Primario Suave (10%)', value: 'primary_soft' },
                                { label: 'Primario Sólido', value: 'primary_solid' },
                                { label: 'Gris Suave', value: 'zinc_soft' },
                                { label: 'Oscuro Sólido', value: 'zinc_dark' },
                                { label: 'Verde Suave', value: 'emerald_soft' },
                                { label: 'Verde Sólido', value: 'emerald_solid' },
                                { label: 'Índigo Suave', value: 'indigo_soft' },
                                { label: 'Azul Suave', value: 'blue_soft' },
                                { label: 'Ámbar Suave', value: 'amber_soft' },
                                { label: 'Rosa Suave', value: 'rose_soft' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </div>

            <!-- Margen General del Bloque Grid -->
            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-zinc-200/60 dark:border-zinc-800/60">
                <div>
                    <label class="block text-xs font-semibold mb-1">Margen Superior del Bloque</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.margin_top || 'none'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.margin_top = val;
                        }"
                        :options="[
                            { label: 'Ninguno (0px)', value: 'none' },
                            { label: 'Pequeño (16px)', value: 'sm' },
                            { label: 'Estándar (24px)', value: 'md' },
                            { label: 'Grande (32px)', value: 'lg' },
                            { label: 'Muy Grande (48px)', value: 'xl' },
                            { label: 'Extra Grande (64px)', value: '2xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Margen Inferior del Bloque</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.margin_bottom || 'none'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            elementoSeleccionado.content.margin_bottom = val;
                        }"
                        :options="[
                            { label: 'Ninguno (0px)', value: 'none' },
                            { label: 'Pequeño (16px)', value: 'sm' },
                            { label: 'Estándar (24px)', value: 'md' },
                            { label: 'Grande (32px)', value: 'lg' },
                            { label: 'Muy Grande (48px)', value: 'xl' },
                            { label: 'Extra Grande (64px)', value: '2xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- BLOQUE 3: ORIGEN DINÁMICO (CMS) Y MAPEO DE CAMPOS -->
        <div v-if="(elementoSeleccionado.content?.mode || 'dynamic') !== 'manual'" class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-database text-emerald-600 dark:text-emerald-400" />
                <span>Origen de Datos Dinámicos y Mapeo</span>
            </div>

            <div class="space-y-3">
                <!-- Origen de Artículos (explícito) -->
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Origen de Artículos</label>
                    <div class="space-y-1.5">
                        <!-- Opción: Categoría Activa (solo en plantillas de categoría) -->
                        <label
                            v-if="opcionesCategoriasConTodas.some(c => c.id === 'current')"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                                (elementoSeleccionado.content?.dynamic_binding?.category_id === 'current' || (!elementoSeleccionado.content?.dynamic_binding?.category_id && opcionesCategoriasConTodas.some(c => c.id === 'current')))
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                            ]"
                            @click="() => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                elementoSeleccionado.content.dynamic_binding.category_id = 'current';
                                elementoSeleccionado.content.dynamic_binding.source = 'current_category';
                            }"
                        >
                            <i class="pi pi-bolt text-emerald-500 text-xs" />
                            <div>
                                <span class="font-bold">Categoría activa</span>
                                <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Muestra los artículos de la categoría que esté visitando el usuario. Ideal para plantillas reutilizables.</span>
                            </div>
                        </label>

                        <!-- Opción: Todas las Categorías -->
                        <label
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                                !esFuenteDescargas && (elementoSeleccionado.content?.dynamic_binding?.category_id === null || elementoSeleccionado.content?.dynamic_binding?.category_id === 'all')
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                            ]"
                            @click="() => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                elementoSeleccionado.content.dynamic_binding.category_id = null;
                                elementoSeleccionado.content.dynamic_binding.source = 'articles';
                            }"
                        >
                            <i class="pi pi-th-large text-zinc-500 text-xs" />
                            <div>
                                <span class="font-bold">Todas las categorías</span>
                                <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Artículos recientes de todo el sitio, sin filtrar por categoría.</span>
                            </div>
                        </label>

                        <!-- Opción: Catálogo Autónomo de Descargas -->
                        <label
                            v-if="moduloDescargasDisponible"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                                esFuenteDescargas
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                            ]"
                            @click="() => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                elementoSeleccionado.content.dynamic_binding.category_id = null;
                                elementoSeleccionado.content.dynamic_binding.source = 'downloads';
                                elementoSeleccionado.content.dynamic_binding.order_by = 'order_asc';
                                elementoSeleccionado.content.image_fit = 'contain';
                            }"
                        >
                            <i class="pi pi-download text-emerald-600 dark:text-emerald-400 text-xs" />
                            <div>
                                <span class="font-bold">Catálogo de Descargas</span>
                                <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Muestra tus plugins y recursos descargables con sus iconos SVG, versión y enlaces.</span>
                            </div>
                        </label>

                        <!-- Opción: Categoría Específica -->
                        <label
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                                !esFuenteDescargas && elementoSeleccionado.content?.dynamic_binding?.category_id && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'current' && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'all' && elementoSeleccionado.content?.dynamic_binding?.category_id !== null
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                            ]"
                            @click="() => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                // Si no había una categoría numérica, poner la primera disponible
                                const cats = opcionesCategoriasConTodas.filter(c => c.id !== null && c.id !== 'current');
                                if (cats.length > 0 && (!elementoSeleccionado.content.dynamic_binding.category_id || elementoSeleccionado.content.dynamic_binding.category_id === 'current' || elementoSeleccionado.content.dynamic_binding.category_id === null)) {
                                    elementoSeleccionado.content.dynamic_binding.category_id = cats[0].id;
                                }
                                elementoSeleccionado.content.dynamic_binding.source = 'articles';
                            }"
                        >
                            <i class="pi pi-filter text-zinc-500 text-xs" />
                            <div>
                                <span class="font-bold">Categoría específica</span>
                                <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Siempre muestra artículos de una categoría concreta, sin importar la página.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Selector de Categoría (solo visible si se eligió 'Categoría específica') -->
                    <div
                        v-if="elementoSeleccionado.content?.dynamic_binding?.category_id && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'current' && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'all' && elementoSeleccionado.content?.dynamic_binding?.category_id !== null"
                        class="mt-2"
                    >
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_binding?.category_id"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                elementoSeleccionado.content.dynamic_binding.category_id = val;
                            }"
                            :options="opcionesCategoriasConTodas.filter(c => c.id !== null && c.id !== 'current')"
                            optionLabel="name"
                            optionValue="id"
                            :filter="true"
                            filterPlaceholder="Buscar categoría..."
                            placeholder="Selecciona una categoría"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Límite y Orden en la misma fila -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Límite Máximo</label>
                        <InputText
                            type="number"
                            min="1"
                            max="100"
                            :modelValue="elementoSeleccionado.content?.dynamic_binding?.limit || 6"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                const num = parseInt(val, 10);
                                elementoSeleccionado.content.dynamic_binding.limit = isNaN(num) ? 6 : num;
                            }"
                            placeholder="ej. 6"
                            class="w-full text-xs font-medium"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Orden de Artículos Dinámicos</label>
                    <Select
                        :modelValue="elementoSeleccionado.content?.dynamic_binding?.order_by || elementoSeleccionado.content?.order_by || 'published_at_desc'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                            elementoSeleccionado.content.dynamic_binding.order_by = val;
                            elementoSeleccionado.content.order_by = val;
                        }"
                        :options="[
                            { label: 'Orden Manual del Gestor de Artículos (Drag & Drop)', value: 'order_asc' },
                            { label: 'Fecha de Publicación (Recientes primero)', value: 'published_at_desc' },
                            { label: 'Fecha de Publicación (Antiguos primero)', value: 'published_at_asc' },
                            { label: 'Alfabético por Título (A - Z)', value: 'title_asc' },
                            { label: 'Alfabético por Título (Z - A)', value: 'title_desc' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- MAPEO DE CAMPOS DINÁMICOS (Campos Estándar + Campos Extra de Categoría) -->
            <div class="pt-2 border-t border-zinc-200/80 dark:border-zinc-800 space-y-2.5">
                <span class="block text-[11px] font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                    Mapeo Dinámico de Campos (Estándar y Personalizados)
                </span>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Campo para Título</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_mapping?.title_field || 'title'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                elementoSeleccionado.content.dynamic_mapping.title_field = val;
                            }"
                            :options="opcionesMapeoTexto"
                            optionLabel="label"
                            optionValue="value"
                            :filter="true"
                            filterPlaceholder="Buscar campo..."
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Campo para Descripción</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_mapping?.description_field || 'excerpt'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                elementoSeleccionado.content.dynamic_mapping.description_field = val;
                            }"
                            :options="opcionesMapeoDescripcion"
                            optionLabel="label"
                            optionValue="value"
                            :filter="true"
                            filterPlaceholder="Buscar campo..."
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Campo para Insignia / Badge</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_mapping?.badge_field || 'category.name'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                elementoSeleccionado.content.dynamic_mapping.badge_field = val;
                            }"
                            :options="opcionesMapeoBadge"
                            optionLabel="label"
                            optionValue="value"
                            :filter="true"
                            filterPlaceholder="Buscar campo..."
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Campo para Imagen</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_mapping?.image_field || 'featured_image'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                elementoSeleccionado.content.dynamic_mapping.image_field = val;
                            }"
                            :options="opcionesMapeoImagen"
                            optionLabel="label"
                            optionValue="value"
                            :filter="true"
                            filterPlaceholder="Buscar campo..."
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Campo para Icono</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_mapping?.icon_field || 'icon'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                elementoSeleccionado.content.dynamic_mapping.icon_field = val;
                            }"
                            :options="opcionesMapeoIcono"
                            optionLabel="label"
                            optionValue="value"
                            :filter="true"
                            filterPlaceholder="Buscar campo..."
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div v-if="esFuenteDescargas" class="space-y-3 pt-2 border-t border-zinc-200/80 dark:border-zinc-800">
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Diseño de Tarjeta de Descarga</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.download_card_mode || 'download_card'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                elementoSeleccionado.content.download_card_mode = val;
                            }"
                            :options="[
                                { label: 'Tarjeta Completa de Descarga (Recomendado)', value: 'download_card' },
                                { label: 'Tarjeta Minimalista del Grid', value: 'grid_card' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <div v-if="(elementoSeleccionado.content?.download_card_mode || 'download_card') === 'download_card'" class="space-y-2.5">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-semibold mb-1">Tamaño Icono SVG</label>
                                <Select
                                    :modelValue="elementoSeleccionado.content?.svg_size || 'lg'"
                                    @update:modelValue="(val) => {
                                        if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                        elementoSeleccionado.content.svg_size = val;
                                    }"
                                    :options="[
                                        { label: 'Pequeño (56px)', value: 'sm' },
                                        { label: 'Mediano (80px)', value: 'md' },
                                        { label: 'Grande (96px)', value: 'lg' },
                                        { label: 'Extra Grande (128px)', value: 'xl' }
                                    ]"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold mb-1">Líneas Descripción</label>
                                <Select
                                    :modelValue="String(elementoSeleccionado.content?.desc_lines || '3')"
                                    @update:modelValue="(val) => {
                                        if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                        elementoSeleccionado.content.desc_lines = val;
                                    }"
                                    :options="[
                                        { label: '2 líneas máx.', value: '2' },
                                        { label: '3 líneas máx.', value: '3' },
                                        { label: '4 líneas máx.', value: '4' },
                                        { label: 'Texto completo', value: 'none' }
                                    ]"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-between px-2.5 py-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/70 dark:border-zinc-700/70">
                            <span class="text-[11px] font-medium text-zinc-700 dark:text-zinc-300">Botón Descarga Rápida ZIP</span>
                            <ToggleSwitch
                                :modelValue="elementoSeleccionado.content?.show_quick_download !== false"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                    elementoSeleccionado.content.show_quick_download = val;
                                }"
                            />
                        </div>
                    </div>

                    <div v-else>
                        <label class="block text-[11px] font-semibold mb-1">Destino al pulsar la Descarga</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_mapping?.download_link_mode || 'detail'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                                if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                elementoSeleccionado.content.dynamic_mapping.download_link_mode = val;
                            }"
                            :options="[
                                { label: 'Abrir Ficha SEO Completa (/descargas/slug)', value: 'detail' },
                                { label: 'Iniciar Descarga Directa del ZIP (si es gratis)', value: 'direct_download' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold mb-1">Texto del Botón Acceso</label>
                    <InputText
                        :modelValue="elementoSeleccionado.content?.dynamic_mapping?.button_text_override || 'Leer más'"
                        @update:modelValue="(val) => {
                            if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                            if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                            elementoSeleccionado.content.dynamic_mapping.button_text_override = val;
                        }"
                        placeholder="ej. Saber más"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- BLOQUE 4: GESTOR DE TARJETAS MANUALES (ACORDEÓN PLEGABLE CON DRAG & DROP) -->
        <div v-if="(elementoSeleccionado.content?.mode || 'dynamic') !== 'dynamic'" class="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="pi pi-credit-card text-emerald-600 dark:text-emerald-400" />
                    <span>Tarjetas Creadas Manualmente ({{ (elementoSeleccionado.content?.items || []).length }})</span>
                </div>
                <button
                    type="button"
                    @click="emit('añadir-item-manual')"
                    class="text-xs font-bold text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 flex items-center gap-1 cursor-pointer"
                >
                    <i class="pi pi-plus text-[10px]" />
                    <span>Añadir Tarjeta</span>
                </button>
            </div>

            <div v-if="elementoSeleccionado.content?.items?.length">
                <Accordion value="0">
                    <draggable
                        v-model="elementoSeleccionado.content.items"
                        item-key="id"
                        animation="200"
                        ghost-class="opacity-40"
                        handle=".card-drag-handle"
                    >
                        <template #item="{ element: item, index: mIdx }">
                            <AccordionPanel :value="mIdx" class="border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden mb-2 bg-white dark:bg-zinc-900 shadow-2xs">
                                <AccordionHeader class="!py-2 !px-3 text-xs font-semibold">
                                    <div class="flex items-center justify-between w-full pr-2 gap-2">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <span
                                                class="card-drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-emerald-600 p-1 shrink-0"
                                                @click.stop
                                            >
                                                <i class="pi pi-bars text-xs" />
                                            </span>
                                            <span class="truncate font-bold text-zinc-900 dark:text-zinc-100">
                                                Tarjeta #{{ mIdx + 1 }}: {{ item.title || 'Nueva Tarjeta' }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-1 shrink-0" @click.stop>
                                            <button
                                                type="button"
                                                @click="emit('mover-item', mIdx, -1)"
                                                :disabled="mIdx === 0"
                                                class="p-1 text-zinc-400 hover:text-zinc-700 disabled:opacity-30 cursor-pointer"
                                                v-tooltip.top="'Subir'"
                                            >
                                                <i class="pi pi-chevron-up text-xs" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="emit('mover-item', mIdx, 1)"
                                                :disabled="mIdx === (elementoSeleccionado.content.items.length - 1)"
                                                class="p-1 text-zinc-400 hover:text-zinc-700 disabled:opacity-30 cursor-pointer"
                                                v-tooltip.top="'Bajar'"
                                            >
                                                <i class="pi pi-chevron-down text-xs" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="emit('eliminar-item-manual', mIdx)"
                                                class="p-1 text-zinc-400 hover:text-red-600 cursor-pointer ml-1"
                                                v-tooltip.top="'Eliminar tarjeta'"
                                            >
                                                <i class="pi pi-trash text-xs" />
                                            </button>
                                        </div>
                                    </div>
                                </AccordionHeader>
                                <AccordionContent class="!p-3.5 space-y-3 bg-zinc-50/50 dark:bg-zinc-950/40 border-t border-zinc-100 dark:border-zinc-800">
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Título de la Tarjeta</label>
                                        <InputText v-model="item.title" placeholder="ej. Asesoramiento Fiscal" class="w-full text-xs font-medium" />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Descripción / Resumen</label>
                                        <WysiwygEditor v-model="item.description" height="150px" placeholder="Detalles de la tarjeta..." />
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-semibold mb-1">Insignia / Badge</label>
                                            <InputText v-model="item.badge" placeholder="ej. Destacado" class="w-full text-xs" />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold mb-1">Icono Acompañante</label>
                                            <IconPicker v-model="item.icon" placeholder="pi pi-star" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Imagen Ilustrativa de Tarjeta</label>
                                        <ImageUploader v-model="item.image" directorio="tarjetas" ayuda="Imagen principal de la tarjeta." />
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-semibold mb-1">Enlace / URL Destino</label>
                                            <InputText v-model="item.url" placeholder="ej. /servicios/fiscal" class="w-full text-xs font-mono" />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold mb-1">Texto del Botón</label>
                                            <InputText v-model="item.button_text" placeholder="ej. Saber más" class="w-full text-xs" />
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionPanel>
                        </template>
                    </draggable>
                </Accordion>
            </div>
        </div>

        <!-- PERSONALIZACIÓN AVANZADA Y CÓDIGO -->
        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" allowCascade />
    </div>
</template>
