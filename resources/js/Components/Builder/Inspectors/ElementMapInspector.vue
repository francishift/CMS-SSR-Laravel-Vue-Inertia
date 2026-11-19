<script setup>
/**
 * Componente ElementMapInspector del Page Builder.
 * Inspector avanzado con opciones completas de personalización para el elemento de mapa:
 *  - Selección de motor (Google Maps con API Key vs OpenStreetMap gratuito)
 *  - Múltiples marcadores manuales o vinculación dinámica con artículos / campos extra
 *  - Colorimetría: saturación (-100 a 100), tono/hue, luminosidad, modo oscuro/invertido
 *  - Controles de UI, scroll cooperativo, auto-fit bounds y altura flexible
 */
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Slider from 'primevue/slider';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import ColorPicker from 'primevue/colorpicker';
import AnimationBlock from './AnimationBlock.vue';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import LocationPickerModal from '../Modals/LocationPickerModal.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
    opcionesCategoriasConTodas: { type: Array, default: () => [] },
});

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings || {});
const googleMapsApiKeyGlobal = computed(() => siteSettings.value.google_maps_api_key || '');

const pestanaActiva = ref('general');

const content = computed(() => {
    if (!props.elementoSeleccionado.content) {
        props.elementoSeleccionado.content = {};
    }
    return props.elementoSeleccionado.content;
});

// Inicializar defaults si no existen
if (!content.value.engine) content.value.engine = 'osm';
if (!content.value.height) content.value.height = '450px';
if (!content.value.border_radius) content.value.border_radius = 'xl';
if (!content.value.shadow) content.value.shadow = 'md';
if (!content.value.zoom) content.value.zoom = 14;
if (content.value.auto_fit_bounds === undefined) content.value.auto_fit_bounds = true;
if (content.value.cooperative_gesture === undefined) content.value.cooperative_gesture = true;
if (!content.value.color_mode) content.value.color_mode = 'default';
if (content.value.saturation === undefined) content.value.saturation = 0;
if (content.value.lightness === undefined) content.value.lightness = 0;
if (!content.value.controls) {
    content.value.controls = { zoom: true, map_type: true, fullscreen: true, street_view: false };
}
if (!content.value.mode) content.value.mode = 'manual';
if (!content.value.marker_title_tag) content.value.marker_title_tag = 'div';
if (!content.value.marker_title_aspect) content.value.marker_title_aspect = 'h4';
if (!Array.isArray(content.value.items)) {
    content.value.items = [
        {
            id: 'm_' + Math.random().toString(36).substring(2, 7),
            title: 'Nuestra Ubicación',
            description: 'Visítanos en nuestras oficinas centrales.',
            lat: 40.416775,
            lng: -3.703790,
            address: 'Puerta del Sol, Madrid',
            icon_url: '',
            link_url: '',
            link_text: 'Cómo llegar',
            open_popup: false,
        },
    ];
}
if (!content.value.dynamic_binding) {
    content.value.dynamic_binding = {
        source: 'current_article',
        category_id: 'all',
        location_field: 'coordenadas',
        limit: 20,
    };
}

// Opciones para Selects (PrimeVue 4: sin cadenas vacías)
const opcionesMotor = [
    { label: 'OpenStreetMap (Gratuito, sin clave, RGPD)', value: 'osm' },
    { label: 'Google Maps (Requiere API Key)', value: 'google' },
];

const opcionesTipoMapa = computed(() => {
    if (content.value.engine === 'google') {
        return [
            { label: 'Hoja de Ruta (Calles)', value: 'roadmap' },
            { label: 'Satélite', value: 'satellite' },
            { label: 'Híbrido', value: 'hybrid' },
            { label: 'Terreno', value: 'terrain' },
        ];
    }
    return [
        { label: 'Estándar OpenStreetMap', value: 'osm_standard' },
        { label: 'CartoDB Positron (Minimalista Claro)', value: 'carto_light' },
        { label: 'CartoDB Dark Matter (Modo Oscuro)', value: 'carto_dark' },
    ];
});

const opcionesAltura = [
    { label: '350 px (Compacto)', value: '350px' },
    { label: '450 px (Estándar)', value: '450px' },
    { label: '550 px (Amplio)', value: '550px' },
    { label: '65 vh (Pantalla media)', value: '65vh' },
    { label: '80 vh (Gran formato)', value: '80vh' },
    { label: 'Expandir a la altura de la columna (Match)', value: 'match' },
    { label: 'Personalizado', value: 'custom' },
];

const opcionesRedondeo = [
    { label: 'Sin redondeo', value: 'none' },
    { label: 'Pequeño (sm)', value: 'sm' },
    { label: 'Medio (md)', value: 'md' },
    { label: 'Grande (lg)', value: 'lg' },
    { label: 'Muy grande (xl)', value: 'xl' },
    { label: 'Extra grande (2xl)', value: '2xl' },
    { label: 'Máximo (3xl)', value: '3xl' },
];

const opcionesSombra = [
    { label: 'Sin sombra', value: 'none' },
    { label: 'Suave (sm)', value: 'sm' },
    { label: 'Media (md)', value: 'md' },
    { label: 'Pronunciada (lg)', value: 'lg' },
    { label: 'Elevada (xl)', value: 'xl' },
    { label: 'Flotante (2xl)', value: '2xl' },
];

const opcionesModoColor = [
    { label: 'Estándar (Color original)', value: 'default' },
    { label: 'Escala de Grises (Blanco y Negro)', value: 'grayscale' },
    { label: 'Modo Noche / Oscuro', value: 'dark' },
    { label: 'Personalizado (Sliders)', value: 'custom' },
];

const opcionesModoDatos = [
    { label: 'Manual (Marcadores fijos en el mapa)', value: 'manual' },
    { label: 'Dinámico (Generado desde artículos del CMS)', value: 'dynamic' },
];

const opcionesFuenteDinamica = [
    { label: 'Artículo Actual (Plantilla de Artículo)', value: 'current_article' },
    { label: 'Colección de Artículos por Categoría', value: 'articles' },
];

const opcionesMarkerTitleTag = [
    { label: 'div', value: 'div' },
    { label: 'h1', value: 'h1' },
    { label: 'h2', value: 'h2' },
    { label: 'h3', value: 'h3' },
    { label: 'h4', value: 'h4' },
    { label: 'h5', value: 'h5' },
    { label: 'h6', value: 'h6' },
    { label: 'p', value: 'p' },
    { label: 'span', value: 'span' },
];

const opcionesMarkerTitleAspect = [
    { label: 'H4 (Subtítulo estándar)', value: 'h4' },
    { label: 'H1 (Encabezado grande)', value: 'h1' },
    { label: 'H2 (Encabezado mediano)', value: 'h2' },
    { label: 'H3 (Encabezado intermedio)', value: 'h3' },
    { label: 'H5 (Pequeño destacado)', value: 'h5' },
    { label: 'H6 (Mini)', value: 'h6' },
    { label: 'Párrafo / Base (16px)', value: 'base' },
    { label: 'Pequeño (14px)', value: 'sm' },
    { label: 'Mini (12px)', value: 'xs' },
];

const opcionesMarkerItemTitleTag = [
    { label: 'Heredar del mapa', value: 'inherit' },
    { label: 'div', value: 'div' },
    { label: 'h1', value: 'h1' },
    { label: 'h2', value: 'h2' },
    { label: 'h3', value: 'h3' },
    { label: 'h4', value: 'h4' },
    { label: 'h5', value: 'h5' },
    { label: 'h6', value: 'h6' },
    { label: 'p', value: 'p' },
    { label: 'span', value: 'span' },
];

const opcionesMarkerItemTitleAspect = [
    { label: 'Heredar del mapa', value: 'inherit' },
    { label: 'H4 (Subtítulo estándar)', value: 'h4' },
    { label: 'H1 (Encabezado grande)', value: 'h1' },
    { label: 'H2 (Encabezado mediano)', value: 'h2' },
    { label: 'H3 (Encabezado intermedio)', value: 'h3' },
    { label: 'H5 (Pequeño destacado)', value: 'h5' },
    { label: 'H6 (Mini)', value: 'h6' },
    { label: 'Párrafo / Base (16px)', value: 'base' },
    { label: 'Pequeño (14px)', value: 'sm' },
    { label: 'Mini (12px)', value: 'xs' },
];

// Gestión de marcadores manuales
const marcadorSeleccionadoIdx = ref(0);

const anadirMarcador = () => {
    content.value.items.push({
        id: 'm_' + Math.random().toString(36).substring(2, 7),
        title: 'Nuevo Marcador',
        title_tag: 'inherit',
        title_aspect: 'inherit',
        description: 'Descripción del punto de interés.',
        lat: 40.416775,
        lng: -3.703790,
        address: '',
        icon_url: '',
        link_url: '',
        link_text: 'Ver más',
        open_popup: false,
    });
    marcadorSeleccionadoIdx.value = content.value.items.length - 1;
};

const eliminarMarcador = (idx) => {
    if (content.value.items.length <= 1) {
        alert('El mapa debe contener al menos un marcador.');
        return;
    }
    content.value.items.splice(idx, 1);
    if (marcadorSeleccionadoIdx.value >= content.value.items.length) {
        marcadorSeleccionadoIdx.value = content.value.items.length - 1;
    }
};

const clonarMarcador = (idx) => {
    const clon = JSON.parse(JSON.stringify(content.value.items[idx]));
    clon.id = 'm_' + Math.random().toString(36).substring(2, 7);
    clon.title = (clon.title || '') + ' (Copia)';
    content.value.items.splice(idx + 1, 0, clon);
    marcadorSeleccionadoIdx.value = idx + 1;
};

const moverMarcador = (idx, direccion) => {
    const destino = idx + direccion;
    if (destino < 0 || destino >= content.value.items.length) return;
    const item = content.value.items.splice(idx, 1)[0];
    content.value.items.splice(destino, 0, item);
    marcadorSeleccionadoIdx.value = destino;
};

// Estado y métodos del Selector Visual Interactivo (LocationPickerModal)
const modalPickerVisible = ref(false);
const pickerTarget = ref('marker'); // 'marker' | 'center'
const pickerInitialLat = ref(40.416775);
const pickerInitialLng = ref(-3.703790);
const pickerInitialAddress = ref('');
const pickerTitle = ref('Ajustar Punto Exacto en el Mapa');
const buscandoDireccionMarcador = ref(false);

const abrirPickerMarcador = (idx) => {
    pickerTarget.value = 'marker';
    marcadorSeleccionadoIdx.value = idx;
    const m = content.value.items[idx];
    pickerInitialLat.value = Number(m?.lat) || 40.416775;
    pickerInitialLng.value = Number(m?.lng) || -3.703790;
    pickerInitialAddress.value = m?.address || '';
    pickerTitle.value = `Ajustar Punto Exacto: ${m?.title || `Marcador ${idx + 1}`}`;
    modalPickerVisible.value = true;
};

const abrirPickerCentro = () => {
    pickerTarget.value = 'center';
    pickerInitialLat.value = Number(content.value.center_lat) || (content.value.items[0]?.lat ? Number(content.value.items[0].lat) : 40.416775);
    pickerInitialLng.value = Number(content.value.center_lng) || (content.value.items[0]?.lng ? Number(content.value.items[0].lng) : -3.703790);
    pickerInitialAddress.value = '';
    pickerTitle.value = 'Seleccionar Centro del Mapa';
    modalPickerVisible.value = true;
};

const centrarEnMarcadorPrincipal = () => {
    const primer = content.value.items[0];
    if (primer && !isNaN(Number(primer.lat)) && !isNaN(Number(primer.lng))) {
        content.value.center_lat = Number(primer.lat);
        content.value.center_lng = Number(primer.lng);
    }
};

const aplicarUbicacionPicker = ({ lat, lng, address }) => {
    if (pickerTarget.value === 'marker') {
        const idx = marcadorSeleccionadoIdx.value;
        if (content.value.items[idx]) {
            content.value.items[idx].lat = lat;
            content.value.items[idx].lng = lng;
            if (address && !content.value.items[idx].address) {
                content.value.items[idx].address = address;
            }
        }
        // Sincronizar automáticamente el centro del mapa si hay 1 solo marcador
        // para que el mapa se actualice y centre al instante
        if (content.value.items.length === 1 || idx === 0) {
            content.value.center_lat = lat;
            content.value.center_lng = lng;
        }
    } else if (pickerTarget.value === 'center') {
        content.value.center_lat = lat;
        content.value.center_lng = lng;
        // Si hay 1 solo marcador, sincronizar también el marcador con el centro seleccionado
        if (content.value.items && content.value.items.length === 1) {
            content.value.items[0].lat = lat;
            content.value.items[0].lng = lng;
            if (address && !content.value.items[0].address) {
                content.value.items[0].address = address;
            }
        }
    }
};

const geocodificarDireccionMarcador = async (idx) => {
    const m = content.value.items[idx];
    if (!m || !m.address?.trim()) return;

    buscandoDireccionMarcador.value = true;
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(m.address.trim())}&limit=1`, {
            headers: { 'Accept-Language': 'es' }
        });
        if (res.ok) {
            const data = await res.json();
            if (data && data.length > 0) {
                const lat = Number(Number(data[0].lat).toFixed(6));
                const lng = Number(Number(data[0].lon).toFixed(6));
                m.lat = lat;
                m.lng = lng;
                if (content.value.items.length === 1 || idx === 0) {
                    content.value.center_lat = lat;
                    content.value.center_lng = lng;
                }
            } else {
                alert('No se encontraron coordenadas para esa dirección. Puedes usar el botón "Ajustar punto exacto en el mapa" para buscar o seleccionar visualmente.');
            }
        }
    } catch (e) {
        alert('Error al buscar coordenadas. Utiliza el botón "Ajustar punto exacto en el mapa".');
    } finally {
        buscandoDireccionMarcador.value = false;
    }
};
</script>

<template>
    <div class="space-y-4">
        <!-- Pestañas de Navegación del Inspector -->
        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl text-xs font-semibold">
            <button
                type="button"
                :class="[
                    'flex-1 py-1.5 px-2 rounded-lg transition-all text-center cursor-pointer',
                    pestanaActiva === 'general' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                ]"
                @click="pestanaActiva = 'general'"
            >
                General
            </button>
            <button
                type="button"
                :class="[
                    'flex-1 py-1.5 px-2 rounded-lg transition-all text-center cursor-pointer',
                    pestanaActiva === 'marcadores' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                ]"
                @click="pestanaActiva = 'marcadores'"
            >
                Marcadores
                <span class="ml-1 text-[10px] bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded-full font-mono">
                    {{ content.mode === 'dynamic' ? 'DYN' : content.items.length }}
                </span>
            </button>
            <button
                type="button"
                :class="[
                    'flex-1 py-1.5 px-2 rounded-lg transition-all text-center cursor-pointer',
                    pestanaActiva === 'estilo' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                ]"
                @click="pestanaActiva = 'estilo'"
            >
                Color & Estilo
            </button>
            <button
                type="button"
                :class="[
                    'flex-1 py-1.5 px-2 rounded-lg transition-all text-center cursor-pointer',
                    pestanaActiva === 'controles' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                ]"
                @click="pestanaActiva = 'controles'"
            >
                Controles
            </button>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 1: GENERAL & DIMENSIONES           -->
        <!-- ========================================== -->
        <div v-show="pestanaActiva === 'general'" class="space-y-4">
            <!-- Motor de Mapa -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    Motor del Mapa
                </label>
                <Select
                    v-model="content.engine"
                    :options="opcionesMotor"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Aviso sobre API Key de Google Maps (Solo configuración global) -->
            <div v-if="content.engine === 'google'" class="p-3 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-[11px] text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                        <i class="pi pi-key text-xs text-amber-500" />
                        <span>Google Maps API Key</span>
                    </span>
                    <span v-if="googleMapsApiKeyGlobal" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded flex items-center gap-1">
                        <i class="pi pi-check text-[10px]" />
                        <span>Configurada en Panel</span>
                    </span>
                    <span v-else class="text-[10px] text-amber-600 dark:text-amber-400 font-bold bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded">
                        Sin clave global
                    </span>
                </div>
                <p v-if="googleMapsApiKeyGlobal" class="text-[11px] text-zinc-500 dark:text-zinc-400">
                    Este mapa utiliza de forma segura la API Key definida en <a href="/panel/configuracion" target="_blank" class="text-emerald-600 dark:text-emerald-400 underline font-semibold">Configuración Global &gt; Integraciones</a>.
                </p>
                <p v-else class="text-[11px] text-amber-600 dark:text-amber-400">
                    Para visualizar Google Maps debes introducir la API Key en <a href="/panel/configuracion" target="_blank" class="underline font-bold">Configuración Global &gt; Integraciones</a>.
                </p>
            </div>

            <!-- Map ID de Google (Opcional, para AdvancedMarkerElement y Cloud Styling) -->
            <div v-if="content.engine === 'google'" class="space-y-1">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    Google Map ID (Opcional)
                </label>
                <InputText
                    v-model="content.map_id"
                    placeholder="Ej: 8e0a97af13b7d1e o tu Map ID de Google Cloud"
                    class="w-full text-xs"
                />
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">
                    Si dispones de un Map ID configurado en Google Cloud Console, introdúcelo aquí para habilitar los marcadores modernos (<code class="text-[10px] bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded font-mono">AdvancedMarkerElement</code>). Si lo dejas vacío, se usarán los marcadores estándar compatibles con la colorimetría de la pestaña Estilos.
                </p>
            </div>

            <!-- Tipo de Mapa -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    Tipo de Mapa / Capa Base
                </label>
                <Select
                    v-model="content.map_type"
                    :options="opcionesTipoMapa"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Altura del Mapa -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    Altura del Mapa
                </label>
                <Select
                    v-model="content.height"
                    :options="opcionesAltura"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
                <div v-if="content.height === 'custom'" class="mt-2">
                    <InputText
                        v-model="content.custom_height"
                        placeholder="ej. 500px o 70vh"
                        class="w-full text-xs"
                    />
                </div>
                <p v-if="content.height === 'match'" class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1">
                    <i class="pi pi-info-circle mr-1" />
                    El mapa se adaptará automáticamente a la altura exacta de la columna adyacente.
                </p>
            </div>

            <!-- Redondeo y Sombra -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Redondeo</label>
                    <Select
                        v-model="content.border_radius"
                        :options="opcionesRedondeo"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Sombra</label>
                    <Select
                        v-model="content.shadow"
                        :options="opcionesSombra"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- Zoom y Centro Inicial -->
            <div class="border-t border-zinc-200 dark:border-zinc-800 pt-3 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Encuadre Automático (Auto-fit)</h4>
                        <p class="text-[11px] text-zinc-500">Centra y ajusta el zoom automáticamente si hay varios marcadores</p>
                    </div>
                    <ToggleSwitch v-model="content.auto_fit_bounds" />
                </div>

                <div v-if="!content.auto_fit_bounds || content.items.length <= 1">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nivel de Zoom Inicial</label>
                        <span class="text-xs font-mono font-bold text-emerald-600">{{ content.zoom }}</span>
                    </div>
                    <Slider v-model="content.zoom" :min="1" :max="20" class="w-full" />
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Latitud Centro</label>
                        <InputText
                            v-model.number="content.center_lat"
                            type="number"
                            step="any"
                            class="w-full text-xs font-mono"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Longitud Centro</label>
                        <InputText
                            v-model.number="content.center_lng"
                            type="number"
                            step="any"
                            class="w-full text-xs font-mono"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-1.5 pt-0.5">
                    <Button
                        type="button"
                        label="Seleccionar centro en el mapa"
                        icon="pi pi-compass"
                        size="small"
                        severity="secondary"
                        variant="outlined"
                        class="flex-1 text-[11px]"
                        @click="abrirPickerCentro"
                    />
                    <Button
                        v-if="content.items.length > 0"
                        type="button"
                        icon="pi pi-map-marker"
                        size="small"
                        severity="secondary"
                        variant="outlined"
                        v-tooltip.top="'Centrar mapa en el primer marcador'"
                        @click="centrarEnMarcadorPrincipal"
                    />
                </div>

                <!-- Desplazamiento Cooperativo -->
                <div class="flex items-center justify-between pt-1">
                    <div>
                        <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Scroll Cooperativo</h4>
                        <p class="text-[11px] text-zinc-500">Evita atrapar el scroll de la página con la rueda del ratón</p>
                    </div>
                    <ToggleSwitch v-model="content.cooperative_gesture" />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 2: MARCADORES & DATOS DINÁMICOS    -->
        <!-- ========================================== -->
        <div v-show="pestanaActiva === 'marcadores'" class="space-y-4">
            <!-- Selector de Modo Manual vs Dinámico -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    Origen de los Marcadores
                </label>
                <Select
                    v-model="content.mode"
                    :options="opcionesModoDatos"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Títulos de Marcadores en Popups (Tipo Semántico y Aspecto Visual) -->
            <div class="p-3 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2.5">
                <span class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    Títulos de Marcadores en Popups
                </span>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                            Tipo de Etiqueta
                        </label>
                        <Select
                            :modelValue="content.marker_title_tag || 'div'"
                            @update:modelValue="(val) => content.marker_title_tag = val"
                            :options="opcionesMarkerTitleTag"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                            Aspecto Visual
                        </label>
                        <Select
                            :modelValue="content.marker_title_aspect || 'h4'"
                            @update:modelValue="(val) => content.marker_title_aspect = val"
                            :options="opcionesMarkerTitleAspect"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </div>

            <!-- MODO DINÁMICO (VINCULADO A ARTÍCULOS O CAMPOS EXTRA) -->
            <div v-if="content.mode === 'dynamic'" class="space-y-3 p-4 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-bold text-xs">
                    <i class="pi pi-database text-sm" />
                    <span>Configuración de Datos Dinámicos</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Fuente Dinámica</label>
                    <Select
                        v-model="content.dynamic_binding.source"
                        :options="opcionesFuenteDinamica"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Si la fuente son los Artículos de una Categoría -->
                <div v-if="content.dynamic_binding.source === 'articles'" class="space-y-2">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Categoría de Artículos</label>
                        <Select
                            v-model="content.dynamic_binding.category_id"
                            :options="opcionesCategoriasConTodas"
                            optionLabel="name"
                            optionValue="id"
                            class="w-full text-xs"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Límite de Marcadores</label>
                            <InputText
                                v-model.number="content.dynamic_binding.limit"
                                type="number"
                                min="1"
                                max="100"
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Campo de Ubicación</label>
                            <InputText
                                v-model="content.dynamic_binding.location_field"
                                placeholder="coordenadas"
                                class="w-full text-xs font-mono"
                            />
                        </div>
                    </div>
                </div>

                <!-- Si la fuente es el Artículo Actual (Plantilla de detalle) -->
                <div v-else class="space-y-2">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Campo de Ubicación del Artículo</label>
                        <InputText
                            v-model="content.dynamic_binding.location_field"
                            placeholder="ej. coordenadas o ubicacion"
                            class="w-full text-xs font-mono"
                        />
                        <p class="text-[11px] text-zinc-500 mt-1">
                            Clave del campo extra en el artículo (ej: <code>coordenadas</code> en formato <code>40.4168, -3.7038</code> o dirección de texto).
                        </p>
                    </div>
                </div>

                <!-- Textos del botón del popup dinámico -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Texto del Botón en Popup</label>
                    <InputText
                        v-model="content.link_text"
                        placeholder="Ver detalles"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- MODO MANUAL (LISTA DE MARCADORES CRUD) -->
            <div v-else class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                        Lista de Marcadores ({{ content.items.length }})
                    </span>
                    <Button
                        icon="pi pi-plus"
                        label="Añadir Punto"
                        size="small"
                        severity="primary"
                        @click="anadirMarcador"
                    />
                </div>

                <!-- Selector de Puntos en Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="(m, idx) in content.items"
                        :key="m.id"
                        type="button"
                        @click="marcadorSeleccionadoIdx = idx"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer shrink-0 flex items-center gap-1',
                            marcadorSeleccionadoIdx === idx
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                        ]"
                    >
                        <span>{{ m.title || `Punto ${idx + 1}` }}</span>
                    </button>
                </div>

                <!-- Formulario del Marcador Seleccionado -->
                <div
                    v-if="content.items[marcadorSeleccionadoIdx]"
                    class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3"
                >
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-2">
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                            Editando: {{ content.items[marcadorSeleccionadoIdx].title || `Marcador ${marcadorSeleccionadoIdx + 1}` }}
                        </span>
                        <div class="flex items-center gap-1">
                            <Button
                                icon="pi pi-arrow-up"
                                size="small"
                                severity="secondary"
                                variant="text"
                                :disabled="marcadorSeleccionadoIdx === 0"
                                @click="moverMarcador(marcadorSeleccionadoIdx, -1)"
                            />
                            <Button
                                icon="pi pi-arrow-down"
                                size="small"
                                severity="secondary"
                                variant="text"
                                :disabled="marcadorSeleccionadoIdx === content.items.length - 1"
                                @click="moverMarcador(marcadorSeleccionadoIdx, 1)"
                            />
                            <Button
                                icon="pi pi-copy"
                                size="small"
                                severity="secondary"
                                variant="text"
                                v-tooltip.top="'Clonar marcador'"
                                @click="clonarMarcador(marcadorSeleccionadoIdx)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                variant="text"
                                v-tooltip.top="'Eliminar marcador'"
                                @click="eliminarMarcador(marcadorSeleccionadoIdx)"
                            />
                        </div>
                    </div>

                    <!-- Título del Marcador -->
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Título del Marcador</label>
                        <InputText
                            v-model="content.items[marcadorSeleccionadoIdx].title"
                            placeholder="ej. Sede Principal"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Tipo Semántico y Aspecto Visual del Marcador -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Tipo de Etiqueta</label>
                            <Select
                                :modelValue="content.items[marcadorSeleccionadoIdx].title_tag || 'inherit'"
                                @update:modelValue="(val) => content.items[marcadorSeleccionadoIdx].title_tag = val"
                                :options="opcionesMarkerItemTitleTag"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Aspecto Visual</label>
                            <Select
                                :modelValue="content.items[marcadorSeleccionadoIdx].title_aspect || 'inherit'"
                                @update:modelValue="(val) => content.items[marcadorSeleccionadoIdx].title_aspect = val"
                                :options="opcionesMarkerItemTitleAspect"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <!-- Coordenadas -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Latitud</label>
                            <InputText
                                v-model.number="content.items[marcadorSeleccionadoIdx].lat"
                                type="number"
                                step="any"
                                placeholder="40.4168"
                                class="w-full text-xs font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Longitud</label>
                            <InputText
                                v-model.number="content.items[marcadorSeleccionadoIdx].lng"
                                type="number"
                                step="any"
                                placeholder="-3.7038"
                                class="w-full text-xs font-mono"
                            />
                        </div>
                    </div>

                    <!-- Botón Selector Visual Interactivo (Pin Arrastrable) -->
                    <div>
                        <Button
                            type="button"
                            label="Ajustar punto exacto en el mapa"
                            icon="pi pi-map-marker"
                            size="small"
                            severity="primary"
                            class="w-full text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 border-none text-white py-2 shadow-2xs cursor-pointer flex items-center justify-center gap-2"
                            @click="abrirPickerMarcador(marcadorSeleccionadoIdx)"
                        />
                    </div>

                    <!-- Dirección de Texto (Fallback o Geocoding) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Dirección de Texto (Opcional)</label>
                        <div class="flex gap-1.5">
                            <InputText
                                v-model="content.items[marcadorSeleccionadoIdx].address"
                                placeholder="ej. Gran Vía 28, Madrid"
                                class="flex-1 text-xs"
                                @keyup.enter="geocodificarDireccionMarcador(marcadorSeleccionadoIdx)"
                            />
                            <Button
                                type="button"
                                icon="pi pi-search"
                                size="small"
                                severity="secondary"
                                v-tooltip.top="'Buscar coordenadas desde esta dirección'"
                                :loading="buscandoDireccionMarcador"
                                @click="geocodificarDireccionMarcador(marcadorSeleccionadoIdx)"
                            />
                        </div>
                    </div>

                    <!-- Descripción / Contenido del Popup -->
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Descripción / Texto del Popup</label>
                        <Textarea
                            v-model="content.items[marcadorSeleccionadoIdx].description"
                            rows="2"
                            placeholder="Información adicional que aparecerá al hacer clic en el marcador."
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Icono Personalizado del Marcador -->
                    <div>
                        <ImageUploader
                            v-model="content.items[marcadorSeleccionadoIdx].icon_url"
                            label="Icono Personalizado (Imagen/Pin)"
                            placeholder="Seleccionar icono desde la biblioteca..."
                            compact
                            :showPreview="true"
                        />
                    </div>

                    <!-- Enlace / Botón del Popup -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Texto del Enlace</label>
                            <InputText
                                v-model="content.items[marcadorSeleccionadoIdx].link_text"
                                placeholder="Cómo llegar"
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">URL de Destino</label>
                            <InputText
                                v-model="content.items[marcadorSeleccionadoIdx].link_url"
                                placeholder="https://..."
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <!-- Toggle abrir popup al cargar -->
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">Abrir popup por defecto al cargar</span>
                        <ToggleSwitch v-model="content.items[marcadorSeleccionadoIdx].open_popup" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 3: COLOR & ESTILO (COLORIMETRÍA)   -->
        <!-- ========================================== -->
        <div v-show="pestanaActiva === 'estilo'" class="space-y-4">
            <!-- Preset de Color -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                    Preset de Colorimetría
                </label>
                <Select
                    v-model="content.color_mode"
                    :options="opcionesModoColor"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Sliders de Colorimetría Avanzada -->
            <div class="space-y-4 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <!-- Saturación (-100 a +100) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Saturación de Color
                        </label>
                        <span class="text-xs font-mono font-bold" :class="content.saturation < 0 ? 'text-zinc-500' : 'text-emerald-600'">
                            {{ content.saturation }}%
                        </span>
                    </div>
                    <Slider v-model="content.saturation" :min="-100" :max="100" class="w-full" />
                    <div class="flex justify-between text-[10px] text-zinc-400 mt-1">
                        <span>Gris (-100)</span>
                        <span>Natural (0)</span>
                        <span>Vívido (+100)</span>
                    </div>
                </div>

                <!-- Tono / Hue Corporativo -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Tono de Color Corporativo (Hue Tint)
                    </label>
                    <div class="flex items-center gap-2">
                        <ColorPicker v-model="content.hue" format="hex" />
                        <InputText
                            v-model="content.hue"
                            placeholder="#10b981 o dejar vacío"
                            class="flex-1 text-xs font-mono"
                        />
                        <Button
                            v-if="content.hue"
                            icon="pi pi-times"
                            size="small"
                            severity="secondary"
                            variant="text"
                            @click="content.hue = ''"
                        />
                    </div>
                </div>

                <!-- Luminosidad / Brillo (-100 a +100) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Luminosidad / Brillo
                        </label>
                        <span class="text-xs font-mono font-bold text-zinc-600 dark:text-zinc-400">
                            {{ content.lightness }}%
                        </span>
                    </div>
                    <Slider v-model="content.lightness" :min="-100" :max="100" class="w-full" />
                </div>

                <!-- Invertir Luminosidad (Modo Oscuro Invertido) -->
                <div class="flex items-center justify-between pt-1">
                    <div>
                        <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Invertir Luminosidad (Modo Noche)</h4>
                        <p class="text-[11px] text-zinc-500">Convierte el mapa en un tema oscuro/nocturno de alto contraste</p>
                    </div>
                    <ToggleSwitch v-model="content.invert_lightness" />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 4: CONTROLES DE MAPA               -->
        <!-- ========================================== -->
        <div v-show="pestanaActiva === 'controles'" class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Controles de Zoom (+ / -)</h4>
                    <p class="text-[11px] text-zinc-500">Muestra los botones de aumento y reducción</p>
                </div>
                <ToggleSwitch v-model="content.controls.zoom" />
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Selector de Tipo de Mapa</h4>
                    <p class="text-[11px] text-zinc-500">Permite al usuario cambiar entre Satélite y Relieve</p>
                </div>
                <ToggleSwitch v-model="content.controls.map_type" />
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Botón de Pantalla Completa</h4>
                    <p class="text-[11px] text-zinc-500">Permite expandir el mapa a pantalla completa</p>
                </div>
                <ToggleSwitch v-model="content.controls.fullscreen" />
            </div>

            <div v-if="content.engine === 'google'" class="flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Street View (Google)</h4>
                    <p class="text-[11px] text-zinc-500">Muestra el icono de hombre naranja de vista de calle</p>
                </div>
                <ToggleSwitch v-model="content.controls.street_view" />
            </div>
        </div>

        <!-- Bloques Estándar: Animación y Personalización Avanzada -->
        <AnimationBlock :elementoSeleccionado="elementoSeleccionado" />
        <AdvancedCustomizationBlock :elementoSeleccionado="elementoSeleccionado" />

        <!-- Modal Selector Visual Interactivo de Ubicación -->
        <LocationPickerModal
            v-model:visible="modalPickerVisible"
            :initialLat="pickerInitialLat"
            :initialLng="pickerInitialLng"
            :initialAddress="pickerInitialAddress"
            :title="pickerTitle"
            @seleccionar="aplicarUbicacionPicker"
        />
    </div>
</template>
