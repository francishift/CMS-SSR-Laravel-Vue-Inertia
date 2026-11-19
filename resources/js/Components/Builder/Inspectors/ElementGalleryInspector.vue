<script setup>
/**
 * Inspector Atómico para el Elemento Galería de Imágenes / Vídeos.
 *
 * Soporta dos modos:
 * - Manual: gestión de ítems con Acordeón + Drag & Drop (comportamiento original).
 * - Dinámico: obtiene ítems automáticamente desde artículos del CMS, con mapeo
 *   configurable de campos estándar y personalizados (extra_fields_data).
 *
 * Configuración de layout (grid/masonry, columnas, gap, ratio, fit, rounded),
 * interacción (lightbox, hover, overlay), captions, filtro por tags,
 * márgenes y personalización avanzada con animación en cascada.
 */
import { ref, computed, watch, nextTick } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import draggable from 'vuedraggable';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
    opcionesCategoriasConTodas: { type: Array, default: () => [] },
});

// --- Inicialización segura: asegurar que content NUNCA sea un Array ([]), sino un Objeto ({}) ---
watch(() => props.elementoSeleccionado, (elem) => {
    if (elem) {
        if (!elem.content || Array.isArray(elem.content) || typeof elem.content !== 'object') {
            elem.content = {};
        }
        if (!elem.content.mode) {
            elem.content.mode = 'manual';
        }
        if (!Array.isArray(elem.content.items)) {
            elem.content.items = [];
        }
    }
}, { immediate: true });

// --- Modo activo de la galería ---
const modoActual = computed(() => props.elementoSeleccionado?.content?.mode || 'manual');
const esDinamico = computed(() => modoActual.value === 'dynamic');

// --- Panel abierto del acordeón (null = todos cerrados) ---
const panelAbierto = ref(null);

// --- Inicialización segura del array de items ---
function ensureItems() {
    if (!props.elementoSeleccionado.content || Array.isArray(props.elementoSeleccionado.content) || typeof props.elementoSeleccionado.content !== 'object') {
        props.elementoSeleccionado.content = {};
    }
    if (!props.elementoSeleccionado.content.mode) {
        props.elementoSeleccionado.content.mode = 'manual';
    }
    if (!Array.isArray(props.elementoSeleccionado.content.items)) {
        props.elementoSeleccionado.content.items = [];
    }
}

function añadirItem() {
    ensureItems();
    const nuevoId = 'gal_' + Math.random().toString(36).substr(2, 9);
    props.elementoSeleccionado.content.items.push({
        id: nuevoId,
        src: '',
        alt: '',
        title: '',
        caption: '',
        tags: '',
        link: '',
        type: 'image',
        poster: '',
        is_white: false,
        color_filter: 'inherit',
    });

    // Abrir el panel del nuevo item y hacer scroll hasta él
    const nuevoIdx = props.elementoSeleccionado.content.items.length - 1;
    panelAbierto.value = nuevoIdx;

    nextTick(() => {
        const panel = document.getElementById(`gal-panel-${nuevoId}`);
        if (panel) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

function eliminarItem(idx) {
    ensureItems();
    props.elementoSeleccionado.content.items.splice(idx, 1);
    // Si el panel abierto era el eliminado, cerrar
    if (panelAbierto.value === idx) {
        panelAbierto.value = null;
    } else if (panelAbierto.value !== null && panelAbierto.value > idx) {
        // Ajustar índice si se eliminó uno anterior
        panelAbierto.value = panelAbierto.value - 1;
    }
}

// --- Nombre descriptivo para la cabecera del acordeón ---
function getNombreItem(item, idx) {
    if (item.title && item.title.trim()) {
        return item.title.length > 25 ? item.title.substring(0, 25) + '…' : item.title;
    }
    if (item.alt && item.alt.trim()) {
        return item.alt.length > 25 ? item.alt.substring(0, 25) + '…' : item.alt;
    }
    return item.type === 'video' ? 'Nuevo Vídeo' : 'Nueva Imagen';
}

// --- Miniatura del item para la cabecera ---
function getMiniatura(item) {
    if (!item.src) return null;
    // Para vídeos con poster, usar el poster
    if (item.type === 'video' && item.poster) return item.poster;
    // Para vídeos sin poster, no mostrar miniatura
    if (item.type === 'video') return null;
    return item.src;
}

// ═══════════════════════════════════════════════════════
// LÓGICA DE CAMPOS EXTRA DINÁMICOS (Patrón Grid + Artículo Actual)
// ═══════════════════════════════════════════════════════

/**
 * Origen dinámico seleccionado:
 *  - 'current_article' → campos imagen del artículo que se está viendo
 *  - 'articles' / 'current_category' → artículos del CMS (como el grid)
 */
const origenDinamico = computed(() =>
    props.elementoSeleccionado.content?.dynamic_binding?.source || 'current_article'
);

const esOrigenArticuloActual = computed(() => origenDinamico.value === 'current_article');

/**
 * Categoría seleccionada cuando se elige una categoría específica.
 * Usa String() para comparación robusta int/string.
 */
const categoriaSeleccionadaObj = computed(() => {
    const catId = props.elementoSeleccionado.content?.dynamic_binding?.category_id;
    if (!catId || catId === 'all' || catId === 'current') return null;
    return props.opcionesCategoriasConTodas.find(c => c.id != null && String(c.id) === String(catId)) || null;
});

/**
 * Esquema de campos extra agregado:
 * - Categoría específica → esquema de esa categoría.
 * - Categoría activa / Todas → unión de esquemas de todas las categorías.
 */
const extraFieldsSchemaAgregado = computed(() => {
    if (categoriaSeleccionadaObj.value?.extra_fields_schema) {
        return categoriaSeleccionadaObj.value.extra_fields_schema;
    }

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

/**
 * Campos de tipo imagen disponibles para el modo "Artículo actual".
 * Genera opciones para el multi-select de campos.
 */
const camposImagenDisponibles = computed(() => {
    const schema = extraFieldsSchemaAgregado.value;
    if (!Array.isArray(schema)) return [];
    return schema
        .filter(field => field.type === 'image' || field.type === 'url')
        .map(field => ({
            key: field.key,
            label: field.label || field.key,
        }));
});

/**
 * Campos imagen seleccionados para el modo "Artículo actual".
 * Se almacena en content.dynamic_binding.selected_image_fields como array de keys.
 */
const camposImagenSeleccionados = computed({
    get() {
        return props.elementoSeleccionado.content?.dynamic_binding?.selected_image_fields || [];
    },
    set(val) {
        if (!props.elementoSeleccionado.content || Array.isArray(props.elementoSeleccionado.content) || typeof props.elementoSeleccionado.content !== 'object') props.elementoSeleccionado.content = {};
        if (!props.elementoSeleccionado.content.dynamic_binding || Array.isArray(props.elementoSeleccionado.content.dynamic_binding) || typeof props.elementoSeleccionado.content.dynamic_binding !== 'object') props.elementoSeleccionado.content.dynamic_binding = {};
        props.elementoSeleccionado.content.dynamic_binding.selected_image_fields = val;
    }
});

/** Selecciona o deselecciona un campo de imagen individual */
function toggleCampoImagen(key) {
    const current = [...camposImagenSeleccionados.value];
    const idx = current.indexOf(key);
    if (idx >= 0) {
        current.splice(idx, 1);
    } else {
        current.push(key);
    }
    camposImagenSeleccionados.value = current;
}

/** Selecciona todos los campos de imagen disponibles */
function seleccionarTodosCamposImagen() {
    camposImagenSeleccionados.value = camposImagenDisponibles.value.map(c => c.key);
}

/** Deselecciona todos los campos de imagen */
function deseleccionarTodosCamposImagen() {
    camposImagenSeleccionados.value = [];
}

// --- Opciones del selector de origen dinámico ---
const opcionesOrigenDinamico = [
    { label: 'Campos del artículo actual', value: 'current_article', icon: 'pi-file', desc: 'Cada campo de imagen del artículo se convierte en un ítem de galería.' },
    { label: 'Artículos del CMS', value: 'articles', icon: 'pi-database', desc: 'Cada artículo contribuye una imagen (como el Grid).' },
];

// --- Opciones de mapeo dinámico para la galería (modo "articles") ---
const opcionesMapeoSrc = computed(() => {
    const base = [
        { label: 'Imagen Destacada del Artículo', value: 'featured_image' },
    ];
    const schema = extraFieldsSchemaAgregado.value;
    if (Array.isArray(schema) && schema.length > 0) {
        schema.forEach(field => {
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

const opcionesMapeoTitulo = computed(() => {
    const base = [
        { label: 'Título del Artículo', value: 'title' },
        { label: 'Extracto / Resumen', value: 'excerpt' },
        { label: 'Nombre de Categoría', value: 'category.name' },
        { label: 'Sin título', value: 'none' },
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

const opcionesMapeoCaption = computed(() => {
    const base = [
        { label: 'Extracto del Artículo', value: 'excerpt' },
        { label: 'Título del Artículo', value: 'title' },
        { label: 'Nombre de Categoría', value: 'category.name' },
        { label: 'Sin caption', value: 'none' },
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

const opcionesMapeoAlt = computed(() => {
    const base = [
        { label: 'Título del Artículo', value: 'title' },
        { label: 'Extracto / Resumen', value: 'excerpt' },
        { label: 'Sin alt text', value: 'none' },
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

const opcionesMapeoTags = computed(() => {
    const base = [
        { label: 'Nombre de Categoría', value: 'category.name' },
        { label: 'Sin tags', value: 'none' },
    ];
    const schema = extraFieldsSchemaAgregado.value;
    if (Array.isArray(schema) && schema.length > 0) {
        schema.forEach(field => {
            if (field.type === 'text' || field.type === 'select') {
                base.push({
                    label: `Campo Extra: ${field.label || field.key}`,
                    value: `extra_fields_data.${field.key}`
                });
            }
        });
    }
    return base;
});

// --- Opciones de selects (diseño y visuales) ---
const opcionesLayout = [
    { label: 'Grid (Cuadrícula uniforme)', value: 'grid' },
    { label: 'Masonry (Mosaico libre)', value: 'masonry' },
    { label: 'Carrusel Infinito (Continuo)', value: 'carousel_infinite' },
];

const opcionesFilasCarrusel = [
    { label: '1 Fila', value: 1 },
    { label: '2 Filas (Alternadas)', value: 2 },
];

const opcionesVelocidadCarrusel = [
    { label: 'Rápida (30s)', value: 30 },
    { label: 'Moderada (45s)', value: 45 },
    { label: 'Estándar (60s)', value: 60 },
    { label: 'Lenta (80s)', value: 80 },
    { label: 'Muy Lenta (100s)', value: 100 },
];

const opcionesDireccionCarrusel = [
    { label: 'Izquierda', value: 'left' },
    { label: 'Derecha', value: 'right' },
];

const opcionesEstiloTarjetaCarrusel = [
    { label: 'Estándar (Imagen suelta)', value: 'plain' },
    { label: 'Tarjeta Insignia (Borde y fondo)', value: 'badge' },
];

const opcionesTamanoItemCarrusel = [
    { label: 'Muy Pequeño (28px)', value: 'xs' },
    { label: 'Pequeño (36px)', value: 'sm' },
    { label: 'Mediano (46px)', value: 'md' },
    { label: 'Grande (60px)', value: 'lg' },
    { label: 'Extra Grande (80px)', value: 'xl' },
];

const opcionesColumnasDesktop = [
    { label: '1 Columna', value: 1 },
    { label: '2 Columnas', value: 2 },
    { label: '3 Columnas', value: 3 },
    { label: '4 Columnas', value: 4 },
    { label: '5 Columnas', value: 5 },
    { label: '6 Columnas', value: 6 },
    { label: '7 Columnas', value: 7 },
    { label: '8 Columnas', value: 8 },
];

const opcionesColumnasTablet = [
    { label: 'Automático', value: 'auto' },
    { label: '1 Columna', value: '1' },
    { label: '2 Columnas', value: '2' },
    { label: '3 Columnas', value: '3' },
    { label: '4 Columnas', value: '4' },
    { label: '5 Columnas', value: '5' },
    { label: '6 Columnas', value: '6' },
];

const opcionesColumnasMobile = [
    { label: '1 Columna', value: '1' },
    { label: '2 Columnas', value: '2' },
    { label: '3 Columnas', value: '3' },
    { label: '4 Columnas', value: '4' },
    { label: '5 Columnas', value: '5' },
    { label: '6 Columnas', value: '6' },
];

const opcionesGap = [
    { label: 'Sin separación', value: 'none' },
    { label: 'Pequeño (8px)', value: 'sm' },
    { label: 'Mediano (16px)', value: 'md' },
    { label: 'Grande (24px)', value: 'lg' },
    { label: 'Extra Grande (32px)', value: 'xl' },
    { label: '2X Grande (40px)', value: '2xl' },
    { label: '3X Grande (48px)', value: '3xl' },
    { label: '4X Grande (56px)', value: '4xl' },
    { label: '5X Grande (64px)', value: '5xl' },
    { label: '6X Grande (72px)', value: '6xl' },
    { label: '7X Grande (80px)', value: '7xl' },
    { label: '8X Grande (90px)', value: '8xl' },
    { label: 'Enorme (100px)', value: '9xl' },
];

const opcionesGapTablet = [
    { label: 'Auto (Heredar)', value: 'auto' },
    ...opcionesGap,
];

const opcionesGapMobile = [
    { label: 'Auto (Heredar)', value: 'auto' },
    ...opcionesGap,
];

const opcionesRatio = [
    { label: 'Automático (proporciones originales)', value: 'auto' },
    { label: '16:9 (Panorámico)', value: '16:9' },
    { label: '4:3 (Clásico)', value: '4:3' },
    { label: '3:2 (Fotográfico)', value: '3:2' },
    { label: '2:3 (Retrato)', value: '2:3' },
    { label: '1:1 (Cuadrado)', value: '1:1' },
];

const opcionesFit = [
    { label: 'Cubrir (Cover)', value: 'cover' },
    { label: 'Contener (Contain)', value: 'contain' },
];

const opcionesRounded = [
    { label: 'Recto (0px)', value: 'none' },
    { label: 'Pequeño (4px)', value: 'sm' },
    { label: 'Mediano (8px)', value: 'md' },
    { label: 'Grande (12px)', value: 'lg' },
    { label: 'Extra Grande (16px)', value: 'xl' },
    { label: '2X Grande (24px)', value: '2xl' },
    { label: 'Redondo / Círculo', value: 'full' },
];

const opcionesHoverEffect = [
    { label: 'Sin efecto', value: 'none' },
    { label: 'Zoom (Ampliar)', value: 'zoom' },
    { label: 'Brillo (Brightness)', value: 'brightness' },
    { label: 'Desenfoque (Blur)', value: 'blur' },
    { label: 'Escala de Grises → Color', value: 'grayscale' },
];

const opcionesOverlay = [
    { label: 'Sin overlay', value: 'none' },
    { label: 'Oscuro al pasar (Hover)', value: 'hover_dark' },
    { label: 'Gradiente al pasar (Hover)', value: 'hover_gradient' },
    { label: 'Oscuro siempre', value: 'always_dark' },
    { label: 'Gradiente siempre', value: 'always_gradient' },
];

const opcionesCaptionPosition = [
    { label: 'Debajo de la imagen', value: 'below' },
    { label: 'Superpuesto (Overlay)', value: 'overlay' },
];

const opcionesMargen = [
    { label: 'Sin margen', value: 'none' },
    { label: 'Extra Pequeño', value: 'xs' },
    { label: 'Pequeño', value: 'sm' },
    { label: 'Mediano', value: 'md' },
    { label: 'Grande', value: 'lg' },
    { label: 'Extra Grande', value: 'xl' },
    { label: '2X Grande', value: '2xl' },
];

const opcionesTipoItem = [
    { label: '📷 Imagen', value: 'image' },
    { label: '🎬 Vídeo', value: 'video' },
];

const opcionesOrden = [
    { label: 'Más recientes primero', value: 'published_at_desc' },
    { label: 'Más antiguos primero', value: 'published_at_asc' },
    { label: 'Título A → Z', value: 'title_asc' },
    { label: 'Título Z → A', value: 'title_desc' },
    { label: 'Orden manual (Drag & Drop)', value: 'order_asc' },
];

const opcionesFiltroColor = [
    { label: 'Color Original (Sin Filtro)', value: 'none' },
    { label: 'Blanco Puro (brightness-0 invert)', value: 'white' },
    { label: 'Blanco Solo en Modo Oscuro', value: 'dark_white' },
    { label: 'Negro Puro (brightness-0)', value: 'black' },
    { label: 'Escala de Grises (Grayscale)', value: 'grayscale' },
    { label: 'Escala de Grises (Color en Hover)', value: 'grayscale_hover' },
];

const opcionesFiltroColorItem = [
    { label: 'Heredar de la galería (Por defecto)', value: 'inherit' },
    { label: 'Color Original (Sin Filtro)', value: 'none' },
    { label: 'Blanco Puro (brightness-0 invert)', value: 'white' },
    { label: 'Blanco Solo en Modo Oscuro', value: 'dark_white' },
    { label: 'Negro Puro (brightness-0)', value: 'black' },
    { label: 'Escala de Grises (Grayscale)', value: 'grayscale' },
    { label: 'Escala de Grises (Color en Hover)', value: 'grayscale_hover' },
];
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">

        <!-- ═══ 0. SELECTOR DE MODO (Manual / Dinámico) ═══ -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-cog text-emerald-600 dark:text-emerald-400" />
                <span>Modo de la Galería</span>
            </div>
            <div class="flex gap-2">
                <label
                    :class="[
                        'flex-1 flex items-center gap-2 px-3 py-2.5 rounded-lg border cursor-pointer transition-all text-xs text-center justify-center',
                        !esDinamico
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                            : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                    ]"
                    @click="() => {
                        if (!elementoSeleccionado.content || Array.isArray(elementoSeleccionado.content) || typeof elementoSeleccionado.content !== 'object') elementoSeleccionado.content = {};
                        elementoSeleccionado.content.mode = 'manual';
                        if (!Array.isArray(elementoSeleccionado.content.items)) elementoSeleccionado.content.items = [];
                    }"
                >
                    <i class="pi pi-pencil text-xs" />
                    <span class="font-bold">Manual</span>
                </label>
                <label
                    :class="[
                        'flex-1 flex items-center gap-2 px-3 py-2.5 rounded-lg border cursor-pointer transition-all text-xs text-center justify-center',
                        esDinamico
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                            : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                    ]"
                    @click="() => {
                        if (!elementoSeleccionado.content || Array.isArray(elementoSeleccionado.content) || typeof elementoSeleccionado.content !== 'object') elementoSeleccionado.content = {};
                        elementoSeleccionado.content.mode = 'dynamic';
                        if (!elementoSeleccionado.content.dynamic_binding || Array.isArray(elementoSeleccionado.content.dynamic_binding) || typeof elementoSeleccionado.content.dynamic_binding !== 'object') elementoSeleccionado.content.dynamic_binding = {};
                    }"
                >
                    <i class="pi pi-database text-xs" />
                    <span class="font-bold">Dinámico (CMS)</span>
                </label>
            </div>
            <p class="text-[10px] text-zinc-400 leading-tight">
                <template v-if="esDinamico">
                    Las imágenes se obtienen automáticamente de los artículos del CMS según la categoría y mapeo configurados.
                </template>
                <template v-else>
                    Añade y gestiona manualmente cada imagen o vídeo de la galería.
                </template>
            </p>
        </div>

        <!-- ═══ ORIGEN DINÁMICO Y MAPEO DE CAMPOS (solo modo dinámico) ═══ -->
        <div v-if="esDinamico" class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-database text-emerald-600 dark:text-emerald-400" />
                <span>Origen de Datos</span>
            </div>

            <!-- Selector de tipo de origen -->
            <div class="space-y-1.5">
                <label
                    v-for="opcion in opcionesOrigenDinamico"
                    :key="opcion.value"
                    :class="[
                        'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                        origenDinamico === opcion.value
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                            : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                    ]"
                    @click="() => {
                        if (!elementoSeleccionado.content) elementoSeleccionado.content = {};
                        if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                        elementoSeleccionado.content.dynamic_binding.source = opcion.value;
                    }"
                >
                    <i :class="['pi', opcion.icon, 'text-xs', origenDinamico === opcion.value ? 'text-emerald-500' : 'text-zinc-500']" />
                    <div>
                        <span class="font-bold">{{ opcion.label }}</span>
                        <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">{{ opcion.desc }}</span>
                    </div>
                </label>
            </div>

            <!-- ═══ MODO: ARTÍCULO ACTUAL → Multi-select de campos imagen ═══ -->
            <template v-if="esOrigenArticuloActual">
                <div class="pt-2 border-t border-zinc-200/80 dark:border-zinc-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="block text-[11px] font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            Campos de Imagen a Mostrar
                        </span>
                        <div class="flex gap-1.5">
                            <button
                                type="button"
                                class="text-[10px] text-emerald-600 hover:text-emerald-700 font-semibold"
                                @click="seleccionarTodosCamposImagen"
                            >Todos</button>
                            <span class="text-zinc-300">|</span>
                            <button
                                type="button"
                                class="text-[10px] text-zinc-500 hover:text-zinc-700 font-semibold"
                                @click="deseleccionarTodosCamposImagen"
                            >Ninguno</button>
                        </div>
                    </div>

                    <div v-if="camposImagenDisponibles.length === 0" class="text-[11px] text-zinc-400 italic py-2">
                        No se encontraron campos de tipo imagen en las categorías disponibles.
                    </div>

                    <div v-else class="space-y-1">
                        <label
                            v-for="campo in camposImagenDisponibles"
                            :key="campo.key"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                                camposImagenSeleccionados.includes(campo.key)
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                            ]"
                            @click="toggleCampoImagen(campo.key)"
                        >
                            <i :class="['pi text-xs', camposImagenSeleccionados.includes(campo.key) ? 'pi-check-square text-emerald-500' : 'pi-stop text-zinc-400']" />
                            <span class="font-semibold">{{ campo.label }}</span>
                            <span class="text-[10px] text-zinc-400 ml-auto">{{ campo.key }}</span>
                        </label>
                    </div>

                    <p class="text-[10px] text-zinc-400 leading-tight mt-1">
                        Cada campo marcado del artículo actual se convertirá en un ítem de la galería. Los campos vacíos se omiten automáticamente.
                    </p>
                </div>
            </template>

            <!-- ═══ MODO: ARTÍCULOS DEL CMS → Categoría + Mapeo (como el Grid) ═══ -->
            <template v-else>
                <!-- Origen de Artículos (categoría) -->
                <div class="pt-2 border-t border-zinc-200/80 dark:border-zinc-800 space-y-1.5">
                    <label class="block text-xs font-semibold mb-1.5">Origen de Artículos</label>

                    <!-- Opción: Categoría activa (solo en plantillas) -->
                    <label
                        v-if="opcionesCategoriasConTodas.some(c => c.id === 'current')"
                        :class="[
                            'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                            (elementoSeleccionado.content?.dynamic_binding?.category_id === 'current')
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                        ]"
                        @click="() => {
                            if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                            elementoSeleccionado.content.dynamic_binding.category_id = 'current';
                        }"
                    >
                        <i class="pi pi-bolt text-emerald-500 text-xs" />
                        <div>
                            <span class="font-bold">Categoría activa</span>
                            <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Imágenes de los artículos de la categoría que visite el usuario.</span>
                        </div>
                    </label>

                    <!-- Opción: Todas las categorías -->
                    <label
                        :class="[
                            'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                            (!elementoSeleccionado.content?.dynamic_binding?.category_id || elementoSeleccionado.content?.dynamic_binding?.category_id === 'all')
                            && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'current'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                        ]"
                        @click="() => {
                            if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                            elementoSeleccionado.content.dynamic_binding.category_id = 'all';
                        }"
                    >
                        <i class="pi pi-th-large text-zinc-500 text-xs" />
                        <div>
                            <span class="font-bold">Todas las categorías</span>
                            <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Artículos recientes de todo el sitio.</span>
                        </div>
                    </label>

                    <!-- Opción: Categoría específica -->
                    <label
                        :class="[
                            'flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all text-xs',
                            elementoSeleccionado.content?.dynamic_binding?.category_id && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'current' && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'all'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                                : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'
                        ]"
                        @click="() => {
                            if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                            const cats = opcionesCategoriasConTodas.filter(c => c.id !== null && c.id !== 'current');
                            if (cats.length > 0 && (!elementoSeleccionado.content.dynamic_binding.category_id || elementoSeleccionado.content.dynamic_binding.category_id === 'current' || elementoSeleccionado.content.dynamic_binding.category_id === 'all')) {
                                elementoSeleccionado.content.dynamic_binding.category_id = cats[0].id;
                            }
                        }"
                    >
                        <i class="pi pi-filter text-zinc-500 text-xs" />
                        <div>
                            <span class="font-bold">Categoría específica</span>
                            <span class="block text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Imágenes de artículos de una categoría concreta.</span>
                        </div>
                    </label>

                    <!-- Selector de categoría (solo si es específica) -->
                    <div
                        v-if="elementoSeleccionado.content?.dynamic_binding?.category_id && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'current' && elementoSeleccionado.content?.dynamic_binding?.category_id !== 'all'"
                        class="mt-2"
                    >
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_binding?.category_id"
                            @update:modelValue="(val) => {
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

                <!-- Límite y Orden -->
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-zinc-200/80 dark:border-zinc-800">
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Límite de imágenes</label>
                        <InputNumber
                            :modelValue="elementoSeleccionado.content?.dynamic_binding?.limit || 12"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                elementoSeleccionado.content.dynamic_binding.limit = val;
                            }"
                            :min="1"
                            :max="50"
                            :showButtons="true"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Ordenación</label>
                        <Select
                            :modelValue="elementoSeleccionado.content?.dynamic_binding?.order_by || 'published_at_desc'"
                            @update:modelValue="(val) => {
                                if (!elementoSeleccionado.content.dynamic_binding) elementoSeleccionado.content.dynamic_binding = {};
                                elementoSeleccionado.content.dynamic_binding.order_by = val;
                            }"
                            :options="opcionesOrden"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Mapeo de Campos Dinámicos -->
                <div class="pt-2 border-t border-zinc-200/80 dark:border-zinc-800 space-y-2.5">
                    <span class="block text-[11px] font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                        Mapeo de Campos
                    </span>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Imagen (src)</label>
                            <Select
                                :modelValue="elementoSeleccionado.content?.dynamic_mapping?.src_field || 'featured_image'"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                    elementoSeleccionado.content.dynamic_mapping.src_field = val;
                                }"
                                :options="opcionesMapeoSrc"
                                optionLabel="label"
                                optionValue="value"
                                :filter="true"
                                filterPlaceholder="Buscar campo..."
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Título</label>
                            <Select
                                :modelValue="elementoSeleccionado.content?.dynamic_mapping?.title_field || 'title'"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                    elementoSeleccionado.content.dynamic_mapping.title_field = val;
                                }"
                                :options="opcionesMapeoTitulo"
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
                            <label class="block text-[11px] font-semibold mb-1">Caption / Pie</label>
                            <Select
                                :modelValue="elementoSeleccionado.content?.dynamic_mapping?.caption_field || 'none'"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                    elementoSeleccionado.content.dynamic_mapping.caption_field = val;
                                }"
                                :options="opcionesMapeoCaption"
                                optionLabel="label"
                                optionValue="value"
                                :filter="true"
                                filterPlaceholder="Buscar campo..."
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Texto Alt (SEO)</label>
                            <Select
                                :modelValue="elementoSeleccionado.content?.dynamic_mapping?.alt_field || 'title'"
                                @update:modelValue="(val) => {
                                    if (!elementoSeleccionado.content.dynamic_mapping) elementoSeleccionado.content.dynamic_mapping = {};
                                    elementoSeleccionado.content.dynamic_mapping.alt_field = val;
                                }"
                                :options="opcionesMapeoAlt"
                                optionLabel="label"
                                optionValue="value"
                                :filter="true"
                                filterPlaceholder="Buscar campo..."
                                class="w-full text-xs"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- ═══ 1. GESTIÓN DE ÍTEMS MANUALES (solo modo manual) ═══ -->
        <div v-if="!esDinamico" class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <div class="flex items-center justify-between">
                <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                    <i class="pi pi-images text-xs text-emerald-500" />
                    Imágenes y Vídeos de la Galería
                    <span
                        v-if="elementoSeleccionado.content?.items?.length"
                        class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold"
                    >
                        {{ elementoSeleccionado.content.items.length }}
                    </span>
                </span>
                <button
                    type="button"
                    @click="añadirItem"
                    class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 px-2.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800 transition-colors cursor-pointer"
                >
                    <i class="pi pi-plus text-[10px] mr-1" />
                    Añadir
                </button>
            </div>

            <!-- Lista de ítems con Acordeón + Drag & Drop -->
            <div v-if="elementoSeleccionado.content?.items?.length">
                <Accordion :value="panelAbierto" @update:value="(val) => panelAbierto = val">
                    <draggable
                        v-model="elementoSeleccionado.content.items"
                        item-key="id"
                        animation="200"
                        ghost-class="opacity-40"
                        handle=".gal-drag-handle"
                    >
                        <template #item="{ element: item, index: idx }">
                            <AccordionPanel
                                :value="idx"
                                :id="`gal-panel-${item.id}`"
                                class="border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden mb-2 bg-white dark:bg-zinc-900 shadow-2xs"
                            >
                                <AccordionHeader class="!py-2 !px-3 text-xs font-semibold">
                                    <div class="flex items-center justify-between w-full pr-2 gap-2">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <!-- Handle de arrastre -->
                                            <span
                                                class="gal-drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-emerald-600 p-1 shrink-0"
                                                @click.stop
                                            >
                                                <i class="pi pi-bars text-xs" />
                                            </span>

                                            <!-- Miniatura -->
                                            <div
                                                v-if="getMiniatura(item)"
                                                class="w-7 h-7 rounded-md overflow-hidden bg-zinc-100 dark:bg-zinc-800 shrink-0 border border-zinc-200 dark:border-zinc-700"
                                            >
                                                <img
                                                    :src="getMiniatura(item)"
                                                    :alt="item.alt || ''"
                                                    class="w-full h-full object-cover"
                                                    loading="lazy"
                                                />
                                            </div>
                                            <div
                                                v-else
                                                class="w-7 h-7 rounded-md bg-zinc-100 dark:bg-zinc-800 shrink-0 flex items-center justify-center border border-zinc-200 dark:border-zinc-700"
                                            >
                                                <i :class="item.type === 'video' ? 'pi pi-video' : 'pi pi-image'" class="text-[10px] text-zinc-400" />
                                            </div>

                                            <!-- Nombre del item -->
                                            <span class="truncate font-bold text-zinc-900 dark:text-zinc-100">
                                                #{{ idx + 1 }}: {{ getNombreItem(item, idx) }}
                                            </span>
                                        </div>

                                        <!-- Controles: subir, bajar, eliminar -->
                                        <div class="flex items-center gap-1 shrink-0" @click.stop>
                                            <button
                                                v-if="idx > 0"
                                                type="button"
                                                @click="elementoSeleccionado.content.items.splice(idx - 1, 0, ...elementoSeleccionado.content.items.splice(idx, 1))"
                                                class="p-1 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                                                v-tooltip.top="'Subir'"
                                            >
                                                <i class="pi pi-chevron-up text-[10px]" />
                                            </button>
                                            <button
                                                v-if="idx < elementoSeleccionado.content.items.length - 1"
                                                type="button"
                                                @click="elementoSeleccionado.content.items.splice(idx + 1, 0, ...elementoSeleccionado.content.items.splice(idx, 1))"
                                                class="p-1 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                                                v-tooltip.top="'Bajar'"
                                            >
                                                <i class="pi pi-chevron-down text-[10px]" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="eliminarItem(idx)"
                                                class="p-1 text-rose-400 hover:text-rose-600 transition-colors cursor-pointer"
                                                v-tooltip.top="'Eliminar'"
                                            >
                                                <i class="pi pi-trash text-[10px]" />
                                            </button>
                                        </div>
                                    </div>
                                </AccordionHeader>

                                <AccordionContent>
                                    <div class="p-3 space-y-2.5">
                                        <!-- Selector de tipo (Imagen / Vídeo) -->
                                        <div class="flex items-center gap-2">
                                            <Select
                                                :modelValue="item.type || 'image'"
                                                @update:modelValue="(val) => item.type = val"
                                                :options="opcionesTipoItem"
                                                optionLabel="label"
                                                optionValue="value"
                                                class="w-36 text-xs"
                                            />
                                        </div>

                                        <!-- Archivo (imagen o vídeo) — sin label redundante -->
                                        <ImageUploader
                                            v-model="item.src"
                                            :filtroTipo="item.type === 'video' ? 'video' : 'image'"
                                            :help="item.type === 'video' ? 'Sube o selecciona un vídeo MP4, WebM o MOV.' : 'Sube o selecciona una imagen para la galería.'"
                                        />

                                        <!-- Poster (solo vídeos) -->
                                        <div v-if="item.type === 'video'">
                                            <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Imagen de Portada (Poster)</label>
                                            <ImageUploader
                                                v-model="item.poster"
                                                filtroTipo="image"
                                                help="Imagen que se muestra antes de reproducir el vídeo."
                                            />
                                        </div>

                                        <!-- Alt Text -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Texto Alternativo (Alt - SEO)</label>
                                            <InputText v-model="item.alt" placeholder="Descripción accesible..." class="w-full text-xs" />
                                        </div>

                                        <!-- Título y Caption -->
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Título</label>
                                                <InputText v-model="item.title" placeholder="Título..." class="w-full text-xs" />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Caption / Pie</label>
                                                <InputText v-model="item.caption" placeholder="Descripción breve..." class="w-full text-xs" />
                                            </div>
                                        </div>

                                        <!-- Tags -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                                Etiquetas / Tags
                                                <span class="font-normal text-zinc-400">(separadas por coma)</span>
                                            </label>
                                            <InputText v-model="item.tags" placeholder="ej. paisaje, naturaleza, montaña" class="w-full text-xs" />
                                        </div>

                                        <!-- Filtro Individual del Ítem (Logo Blanco / Sobrescritura) -->
                                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="block text-[11px] font-bold text-zinc-800 dark:text-zinc-200">
                                                        Forzar Logo Blanco en este ítem
                                                    </span>
                                                    <span class="block text-[10px] text-zinc-500 dark:text-zinc-400">
                                                        Aplica brightness-0 invert a esta imagen
                                                    </span>
                                                </div>
                                                <ToggleSwitch
                                                    :modelValue="!!item.is_white"
                                                    @update:modelValue="(val) => item.is_white = val"
                                                />
                                            </div>

                                            <div v-if="!item.is_white">
                                                <label class="block text-[10px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                                                    Filtro de Color Individual
                                                </label>
                                                <Select
                                                    :modelValue="item.color_filter || 'inherit'"
                                                    @update:modelValue="(val) => item.color_filter = val"
                                                    :options="opcionesFiltroColorItem"
                                                    optionLabel="label"
                                                    optionValue="value"
                                                    class="w-full text-xs"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionPanel>
                        </template>
                    </draggable>
                </Accordion>
            </div>

            <!-- Estado vacío -->
            <div v-else class="py-6 text-center text-zinc-400 text-xs">
                <i class="pi pi-images text-lg mb-1 block" />
                <p>No hay imágenes. Pulsa "Añadir" para empezar.</p>
            </div>
        </div>

        <!-- ═══ 2. DISEÑO / LAYOUT ═══ -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                <i class="pi pi-th-large text-xs text-blue-500" />
                Diseño y Layout
            </span>

            <div>
                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Tipo de Layout</label>
                <Select
                    :modelValue="elementoSeleccionado.content.layout || 'grid'"
                    @update:modelValue="(val) => elementoSeleccionado.content.layout = val"
                    :options="opcionesLayout"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Configuración específica de Carrusel Infinito -->
            <div v-if="elementoSeleccionado.content.layout === 'carousel_infinite'" class="space-y-3 pt-1">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Filas del Carrusel</label>
                        <Select
                            :modelValue="elementoSeleccionado.content.carousel_rows || 1"
                            @update:modelValue="(val) => elementoSeleccionado.content.carousel_rows = val"
                            :options="opcionesFilasCarrusel"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Velocidad</label>
                        <Select
                            :modelValue="elementoSeleccionado.content.carousel_speed || 60"
                            @update:modelValue="(val) => elementoSeleccionado.content.carousel_speed = val"
                            :options="opcionesVelocidadCarrusel"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Dirección Inicial</label>
                        <Select
                            :modelValue="elementoSeleccionado.content.carousel_direction || 'left'"
                            @update:modelValue="(val) => elementoSeleccionado.content.carousel_direction = val"
                            :options="opcionesDireccionCarrusel"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Estilo de Elemento</label>
                        <Select
                            :modelValue="elementoSeleccionado.content.carousel_card_style || 'plain'"
                            @update:modelValue="(val) => elementoSeleccionado.content.carousel_card_style = val"
                            :options="opcionesEstiloTarjetaCarrusel"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>


                <div class="flex items-center justify-between pt-1">
                    <div>
                        <span class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">Pausar al Pasar el Ratón</span>
                        <span class="block text-[10px] text-zinc-400">Detiene el carrusel cuando el usuario interactúa</span>
                    </div>
                    <ToggleSwitch
                        :modelValue="elementoSeleccionado.content.carousel_pause_on_hover !== false"
                        @update:modelValue="(val) => elementoSeleccionado.content.carousel_pause_on_hover = val"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">Desvanecimiento en Bordes</span>
                        <span class="block text-[10px] text-zinc-400">Degradado suave en los extremos laterales</span>
                    </div>
                    <ToggleSwitch
                        :modelValue="elementoSeleccionado.content.carousel_fade_edges !== false"
                        @update:modelValue="(val) => elementoSeleccionado.content.carousel_fade_edges = val"
                    />
                </div>
            </div>

            <!-- Columnas (Grid, Masonry y Carrusel Infinito) -->
            <div>
                <span class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    {{ elementoSeleccionado.content.layout === 'carousel_infinite' ? 'Columnas Simultáneas en Pantalla' : 'Columnas' }}
                </span>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] text-zinc-500 dark:text-zinc-400 mb-1">
                            <i class="pi pi-desktop text-[10px]" /> Escritorio
                        </label>
                        <Select
                            :modelValue="elementoSeleccionado.content.columns_desktop || 3"
                            @update:modelValue="(val) => elementoSeleccionado.content.columns_desktop = val"
                            :options="opcionesColumnasDesktop"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] text-zinc-500 dark:text-zinc-400 mb-1">
                            <i class="pi pi-tablet text-[10px]" /> Tablet
                        </label>
                        <Select
                            :modelValue="elementoSeleccionado.content.columns_tablet || 'auto'"
                            @update:modelValue="(val) => elementoSeleccionado.content.columns_tablet = val"
                            :options="opcionesColumnasTablet"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] text-zinc-500 dark:text-zinc-400 mb-1">
                            <i class="pi pi-mobile text-[10px]" /> Móvil
                        </label>
                        <Select
                            :modelValue="elementoSeleccionado.content.columns_mobile || '1'"
                            @update:modelValue="(val) => elementoSeleccionado.content.columns_mobile = val"
                            :options="opcionesColumnasMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
                <span v-if="elementoSeleccionado.content.layout === 'carousel_infinite'" class="block text-[10px] text-zinc-400 mt-1">
                    Define cuántos elementos son visibles simultáneamente en el carrusel. Menos columnas = imágenes más grandes.
                </span>
            </div>

            <!-- Separación (Gap) Responsiva -->
            <div>
                <span class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Separación (Gap)</span>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] text-zinc-500 dark:text-zinc-400 mb-1">
                            <i class="pi pi-desktop text-[10px]" /> Escritorio
                        </label>
                        <Select
                            :modelValue="elementoSeleccionado.content.gap || 'md'"
                            @update:modelValue="(val) => elementoSeleccionado.content.gap = val"
                            :options="opcionesGap"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] text-zinc-500 dark:text-zinc-400 mb-1">
                            <i class="pi pi-tablet text-[10px]" /> Tablet
                        </label>
                        <Select
                            :modelValue="elementoSeleccionado.content.gap_tablet || 'auto'"
                            @update:modelValue="(val) => elementoSeleccionado.content.gap_tablet = val"
                            :options="opcionesGapTablet"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] text-zinc-500 dark:text-zinc-400 mb-1">
                            <i class="pi pi-mobile text-[10px]" /> Móvil
                        </label>
                        <Select
                            :modelValue="elementoSeleccionado.content.gap_mobile || 'auto'"
                            @update:modelValue="(val) => elementoSeleccionado.content.gap_mobile = val"
                            :options="opcionesGapMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Bordes</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.rounded || 'md'"
                        @update:modelValue="(val) => elementoSeleccionado.content.rounded = val"
                        :options="opcionesRounded"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Proporción</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.image_ratio || 'auto'"
                        @update:modelValue="(val) => elementoSeleccionado.content.image_ratio = val"
                        :options="opcionesRatio"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Ajuste (Fit)</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.image_fit || 'cover'"
                        @update:modelValue="(val) => elementoSeleccionado.content.image_fit = val"
                        :options="opcionesFit"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- Dimensiones Globales de Imagen -->
            <div class="pt-3 border-t border-zinc-200/80 dark:border-zinc-700/60 space-y-2">
                <span class="block text-[11px] font-bold text-zinc-800 dark:text-zinc-200">
                    Dimensiones Globales de Imagen
                </span>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                            Altura Máx. (px)
                        </label>
                        <InputNumber
                            :modelValue="elementoSeleccionado.content.max_height_px"
                            @update:modelValue="(val) => elementoSeleccionado.content.max_height_px = val"
                            placeholder="Auto"
                            :min="10"
                            :max="1200"
                            suffix=" px"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                            Ancho Máx. (px)
                        </label>
                        <InputNumber
                            :modelValue="elementoSeleccionado.content.max_width_px"
                            @update:modelValue="(val) => elementoSeleccionado.content.max_width_px = val"
                            placeholder="Auto"
                            :min="10"
                            :max="1920"
                            suffix=" px"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
                <span class="block text-[10px] text-zinc-400">
                    Ideal para fijar una altura o anchura uniforme a logos de clientes o patrocinadores con proporciones dispares.
                </span>
            </div>

            <!-- Sub-bloque: Divisores y Alineación de Grid (solo layout grid) -->
            <div v-if="elementoSeleccionado.content.layout === 'grid'" class="pt-3 border-t border-zinc-200/80 dark:border-zinc-700/60 space-y-3">
                <!-- Divisor -->
                <div class="flex items-center gap-4">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 w-24 shrink-0">
                        Divisor
                    </span>
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <ToggleSwitch
                            :modelValue="!!elementoSeleccionado.content.show_dividers"
                            @update:modelValue="(val) => elementoSeleccionado.content.show_dividers = val"
                        />
                        <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Mostrar Divisores</span>
                    </label>
                </div>

                <!-- Alineación -->
                <div class="flex items-start gap-4">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 w-24 shrink-0 pt-0.5">
                        Alineación
                    </span>
                    <div class="space-y-2">
                        <div>
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <ToggleSwitch
                                    :modelValue="!!elementoSeleccionado.content.center_columns"
                                    @update:modelValue="(val) => elementoSeleccionado.content.center_columns = val"
                                />
                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Centrar columnas</span>
                            </label>
                            <span class="block text-[10px] text-zinc-400 mt-0.5 ml-8">
                                Centra las filas incompletas en el medio respetando el ancho de columna.
                            </span>
                        </div>
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <ToggleSwitch
                                :modelValue="!!elementoSeleccionado.content.center_rows"
                                @update:modelValue="(val) => elementoSeleccionado.content.center_rows = val"
                            />
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Centrar Filas</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ FILTROS VISUALES Y MONOCROMO (GLOBAL) ═══ -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                <i class="pi pi-sun text-xs text-amber-500" />
                Filtros Visuales y Colorimetría
            </span>

            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                        Hacer Imágenes Blancas (Efecto Logo)
                    </span>
                    <span class="block text-[10px] text-zinc-400">
                        Aplica brightness-0 invert a toda la galería (ideal sobre fondos oscuros)
                    </span>
                </div>
                <ToggleSwitch
                    :modelValue="!!elementoSeleccionado.content.is_white"
                    @update:modelValue="(val) => elementoSeleccionado.content.is_white = val"
                />
            </div>

            <div v-if="!elementoSeleccionado.content.is_white">
                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    Filtro de Color Global
                </label>
                <Select
                    :modelValue="elementoSeleccionado.content.color_filter || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.color_filter = val"
                    :options="opcionesFiltroColor"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- ═══ 3. INTERACCIÓN ═══ -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                <i class="pi pi-eye text-xs text-violet-500" />
                Interacción y Efectos
            </span>

            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">Lightbox (Visor Ampliado)</span>
                    <span class="block text-[10px] text-zinc-400">Clic en imagen abre un visor a pantalla completa</span>
                </div>
                <ToggleSwitch
                    :modelValue="elementoSeleccionado.content.lightbox !== false && elementoSeleccionado.content.enable_lightbox !== false"
                    @update:modelValue="(val) => {
                        elementoSeleccionado.content.lightbox = val;
                        elementoSeleccionado.content.enable_lightbox = val;
                    }"
                />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Efecto Hover sobre Imagen</label>
                <Select
                    :modelValue="elementoSeleccionado.content.hover_effect || 'zoom'"
                    @update:modelValue="(val) => elementoSeleccionado.content.hover_effect = val"
                    :options="opcionesHoverEffect"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Capa Overlay</label>
                <Select
                    :modelValue="elementoSeleccionado.content.overlay_mode || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.overlay_mode = val"
                    :options="opcionesOverlay"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- ═══ 4. CAPTIONS ═══ -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                <i class="pi pi-align-left text-xs text-amber-500" />
                Títulos y Descripciones
            </span>

            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">Mostrar Captions</span>
                    <span class="block text-[10px] text-zinc-400">Muestra título y pie de foto de cada imagen</span>
                </div>
                <ToggleSwitch v-model="elementoSeleccionado.content.show_caption" />
            </div>

            <div v-if="elementoSeleccionado.content.show_caption">
                <div v-if="elementoSeleccionado.content.layout === 'carousel_infinite'" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 text-[10px] text-zinc-500 dark:text-zinc-400 flex items-start gap-1.5">
                    <i class="pi pi-info-circle text-emerald-500 mt-0.5 shrink-0" />
                    <span>En el Carrusel Infinito, el título y descripción se muestran como una tarjeta flotante (tooltip) al pasar el cursor sobre cada imagen.</span>
                </div>
                <div v-else>
                    <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Posición del Caption</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.caption_position || 'below'"
                        @update:modelValue="(val) => elementoSeleccionado.content.caption_position = val"
                        :options="opcionesCaptionPosition"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- ═══ 5. FILTRO POR TAGS ═══ -->
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                        <i class="pi pi-filter text-xs text-cyan-500" />
                        Filtro por Etiquetas
                    </span>
                    <span class="block text-[10px] text-zinc-400 mt-0.5">Muestra una barra de filtro para clasificar las imágenes por etiquetas</span>
                </div>
                <ToggleSwitch v-model="elementoSeleccionado.content.filter_tags" />
            </div>
        </div>

        <!-- ═══ 6. MÁRGENES ═══ -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Margen Superior</label>
                <Select
                    :modelValue="elementoSeleccionado.content.margin_top || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.margin_top = val"
                    :options="opcionesMargen"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Margen Inferior</label>
                <Select
                    :modelValue="elementoSeleccionado.content.margin_bottom || 'none'"
                    @update:modelValue="(val) => elementoSeleccionado.content.margin_bottom = val"
                    :options="opcionesMargen"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- ═══ 7. PERSONALIZACIÓN AVANZADA, ANIMACIÓN Y CÓDIGO ═══ -->
        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" allowCascade />
    </div>
</template>
