<script setup>
/**
 * Inspector Atómico de Opciones de Sección.
 * Controla anchos de contenedor, altura, paddings, márgenes, fondos, overlays, sticky, ID, clases y CSS.
 */
import { ref, computed } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Textarea from 'primevue/textarea';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import VideoUploader from '@/Components/Admin/VideoUploader.vue';
import AnimationBlock from './AnimationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
    opcionesContenedor: { type: Array, required: true },
    opcionesAltura: { type: Array, required: true },
    opcionesAlineacionVertical: { type: Array, required: true },
    opcionesColorTexto: { type: Array, required: true },
    opcionesContrasteCabecera: { type: Array, default: () => [] },
    opcionesPadding: { type: Array, required: true },
    opcionesMargin: { type: Array, required: true },
    opcionesElementoHtml: { type: Array, required: true },
    opcionesSticky: { type: Array, required: true },
    opcionesFondos: { type: Array, required: true },
    opcionesBgSize: { type: Array, default: () => [] },
    opcionesBgPosition: { type: Array, default: () => [] },
    opcionesBgAttachment: { type: Array, default: () => [] },
    opcionesBgRepeat: { type: Array, default: () => [] },
    opcionesBgBlendMode: { type: Array, default: () => [] },
    opcionesBgFilter: { type: Array, default: () => [] },
    opcionesBgHideMobile: { type: Array, default: () => [] },
});

const dispositivoEspaciado = ref('desktop');
const dispositivoFondo = ref('desktop');
const dispositivoFondoDark = ref('desktop');
const temaFondo = ref('light');

function seleccionarTemaFondo(tema) {
    temaFondo.value = tema;
    if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('cms-builder-preview-theme', { detail: { theme: tema } }));
    }
}

const opcionesModoFondoOscuro = [
    { label: 'Heredar fondo de Modo Claro (Por defecto)', value: 'inherit' },
    { label: 'Configuración independiente en Modo Oscuro', value: 'custom' },
];

const opcionesColorTextoDark = [
    { label: 'Mismo ajuste que Modo Claro', value: 'same' },
    { label: 'Heredar del tema oscuro (Automático)', value: 'inherit' },
    { label: 'Texto Claro Forzado (Blanco)', value: 'light' },
    { label: 'Texto Oscuro Forzado (Negro)', value: 'dark' },
];

const opcionesContrasteCabeceraDark = [
    { label: 'Mismo ajuste que Modo Claro', value: 'same' },
    { label: 'Heredar colores del tema oscuro', value: 'inherit' },
    { label: 'Texto Claro / Blanco (Sobre fondos oscuros)', value: 'light' },
    { label: 'Texto Oscuro / Negro (Sobre fondos claros)', value: 'dark' },
];

const opcionesBgSizeCompleto = [
    { label: 'Cover (Cubrir toda la sección)', value: 'cover' },
    { label: 'Contain (Ajustar imagen completa)', value: 'contain' },
    { label: 'Auto (Tamaño original en px)', value: 'auto' },
    { label: 'Ancho 100% (100% auto)', value: '100% auto' },
    { label: 'Alto 100% (auto 100%)', value: 'auto 100%' },
];

const opcionesBgSizeResponsive = [
    { label: 'Heredar de Escritorio', value: 'inherit' },
    { label: 'Cover (Cubrir todo)', value: 'cover' },
    { label: 'Contain (Ajustar imagen completa)', value: 'contain' },
    { label: 'Auto (Tamaño original)', value: 'auto' },
    { label: 'Ancho 100% (100% auto)', value: '100% auto' },
    { label: 'Alto 100% (auto 100%)', value: 'auto 100%' },
];

const opcionesBgPositionResponsive = [
    { label: 'Heredar de Escritorio', value: 'inherit' },
    { label: 'Centro (Centrado)', value: 'center center' },
    { label: 'Arriba Centro', value: 'top center' },
    { label: 'Abajo Centro', value: 'bottom center' },
    { label: 'Izquierda Centro', value: 'left center' },
    { label: 'Derecha Centro', value: 'right center' },
    { label: 'Arriba Izquierda', value: 'top left' },
    { label: 'Arriba Derecha', value: 'top right' },
    { label: 'Abajo Izquierda', value: 'bottom left' },
    { label: 'Abajo Derecha', value: 'bottom right' },
];

const opcionesPaddingTabletMobile = [
    { label: 'Heredar de Escritorio', value: '' },
    { label: 'Sin Padding (0px)', value: 'none' },
    { label: 'Pequeño (py-6 / 24px)', value: 'sm' },
    { label: 'Mediano (py-12 / 48px)', value: 'md' },
    { label: 'Grande (py-20 / 80px)', value: 'lg' },
    { label: 'Extra Grande (py-28 / 112px)', value: 'xl' },
    { label: 'Gigante (py-36 / 144px)', value: '2xl' },
];

const opcionesMarginTabletMobile = [
    { label: 'Heredar de Escritorio', value: '' },
    { label: 'Sin Margen (my-0)', value: 'none' },
    { label: 'Pequeño (my-6 / 24px)', value: 'sm' },
    { label: 'Mediano (my-12 / 48px)', value: 'md' },
    { label: 'Grande (my-20 / 80px)', value: 'lg' },
];

const opcionesRowGapTabletMobile = [
    { label: 'Heredar de Escritorio', value: '' },
    { label: 'Compacto (24px)', value: 'sm' },
    { label: 'Estándar (48px)', value: 'md' },
    { label: 'Amplio (64px)', value: 'lg' },
    { label: 'Separado (80px)', value: 'xl' },
    { label: 'Sin espacio (0px)', value: 'none' },
];

// Modelos computados para garantizar que PrimeVue Select coincida siempre con el valor o valor por defecto
const bgPresetModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_preset;
        if (!val || val === '') return 'transparent';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_preset = val;
        }
    }
});

const textColorModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.text_color;
        if (!val || val === '') return 'inherit';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.text_color = val;
        }
    }
});

const opcionesBgVideoPosition = [
    { label: 'Centro (Centrado)', value: 'center center' },
    { label: 'Arriba Centro', value: 'top center' },
    { label: 'Abajo Centro', value: 'bottom center' },
    { label: 'Izquierda Centro', value: 'left center' },
    { label: 'Derecha Centro', value: 'right center' },
];

const opcionesBgVideoMobile = [
    { label: 'Mostrar imagen en móviles (Ahorro de datos y SEO)', value: 'fallback' },
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
        return props.elementoSeleccionado?.settings?.bg_type || 'image';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_type = val;
        }
    }
});

const bgVideoPositionModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_video_position || 'center center';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_video_position = val;
        }
    }
});

const bgVideoMobileModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_video_mobile || 'fallback';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_video_mobile = val;
        }
    }
});

const bgVideoPlaybackRateModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_video_playback_rate;
        return val ? String(val) : '1.0';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_video_playback_rate = Number(val);
        }
    }
});

const containerModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.container || 'boxed';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.container = val;
        }
    }
});

const heightTypeModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.height_type || 'none';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.height_type = val;
        }
    }
});

const verticalAlignModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.vertical_align || 'top';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.vertical_align = val;
        }
    }
});

const htmlElementModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.html_element || 'section';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.html_element = val;
        }
    }
});

const stickyModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.sticky;
        if (!val || val === false || val === '') return 'none';
        if (val === true || val === 'cover') return 'cover';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.sticky = val;
        }
    }
});

const customBgColorPickerModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_color;
        if (!val || typeof val !== 'string' || !val.startsWith('#')) return '#ffffff';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_color = val;
        }
    }
});

const bgSizeModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_size || 'cover';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_size = val;
        }
    }
});

const bgPositionModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_position || 'center center';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_position = val;
        }
    }
});

const bgSizeTabletModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_size_tablet;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_size_tablet = (val === 'inherit') ? '' : val;
        }
    }
});

const bgPositionTabletModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_position_tablet;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_position_tablet = (val === 'inherit') ? '' : val;
        }
    }
});

const bgSizeMobileModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_size_mobile;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_size_mobile = (val === 'inherit') ? '' : val;
        }
    }
});

const bgPositionMobileModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_position_mobile;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_position_mobile = (val === 'inherit') ? '' : val;
        }
    }
});

const bgAttachmentModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_attachment || 'scroll';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_attachment = val;
        }
    }
});

const bgRepeatModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_repeat || 'no-repeat';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_repeat = val;
        }
    }
});

const bgBlendModeModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_blend_mode || 'normal';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_blend_mode = val;
        }
    }
});

const bgFilterModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_filter || 'none';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_filter = val;
        }
    }
});

const bgHideMobileModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_hide_mobile || 'none';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_hide_mobile = val;
        }
    }
});

const transparentHeaderModel = computed({
    get() {
        return Boolean(props.elementoSeleccionado?.settings?.transparent_header);
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.transparent_header = Boolean(val);
        }
    }
});

const transparentHeaderPullModel = computed({
    get() {
        return Boolean(props.elementoSeleccionado?.settings?.transparent_header_pull);
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.transparent_header_pull = Boolean(val);
        }
    }
});

const transparentHeaderNavColorModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.transparent_header_nav_color;
        if (!val || val === '') return 'inherit';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.transparent_header_nav_color = val;
        }
    }
});

const transparentHeaderNavColorDarkModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.transparent_header_nav_color_dark;
        if (!val || val === '') return 'same';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.transparent_header_nav_color_dark = val;
        }
    }
});

// ─── Modelos Computados Independientes para Fondo en Modo Oscuro (Regla PrimeVue 4: sin cadenas vacías) ───
const bgDarkModeModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_dark_mode;
        return val === 'custom' ? 'custom' : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_dark_mode = val === 'custom' ? 'custom' : 'inherit';
        }
    }
});

const bgTypeDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_type_dark || 'image';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_type_dark = val;
        }
    }
});

const bgSizeDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_size_dark || 'cover';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_size_dark = val;
        }
    }
});

const bgPositionDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_position_dark || 'center center';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_position_dark = val;
        }
    }
});

const bgSizeTabletDarkModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_size_tablet_dark;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_size_tablet_dark = (val === 'inherit') ? '' : val;
        }
    }
});

const bgPositionTabletDarkModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_position_tablet_dark;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_position_tablet_dark = (val === 'inherit') ? '' : val;
        }
    }
});

const bgSizeMobileDarkModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_size_mobile_dark;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_size_mobile_dark = (val === 'inherit') ? '' : val;
        }
    }
});

const bgPositionMobileDarkModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_position_mobile_dark;
        return (val && val !== '') ? val : 'inherit';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_position_mobile_dark = (val === 'inherit') ? '' : val;
        }
    }
});

const bgAttachmentDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_attachment_dark || 'scroll';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_attachment_dark = val;
        }
    }
});

const bgRepeatDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_repeat_dark || 'no-repeat';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_repeat_dark = val;
        }
    }
});

const bgBlendModeDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_blend_mode_dark || 'normal';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_blend_mode_dark = val;
        }
    }
});

const bgFilterDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_filter_dark || 'none';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_filter_dark = val;
        }
    }
});

const bgHideMobileDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_hide_mobile_dark || 'none';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_hide_mobile_dark = val;
        }
    }
});

const bgVideoPositionDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_video_position_dark || 'center center';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_video_position_dark = val;
        }
    }
});

const bgVideoMobileDarkModel = computed({
    get() {
        return props.elementoSeleccionado?.settings?.bg_video_mobile_dark || 'fallback';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_video_mobile_dark = val;
        }
    }
});

const bgVideoPlaybackRateDarkModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_video_playback_rate_dark;
        return val ? String(val) : '1.0';
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_video_playback_rate_dark = Number(val);
        }
    }
});

const customBgColorDarkPickerModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.bg_color_dark;
        if (!val || typeof val !== 'string' || !val.startsWith('#')) return '#09090b';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.bg_color_dark = val;
        }
    }
});

const textColorDarkModel = computed({
    get() {
        const val = props.elementoSeleccionado?.settings?.text_color_dark;
        if (!val || val === '') return 'same';
        return val;
    },
    set(val) {
        if (props.elementoSeleccionado?.settings) {
            props.elementoSeleccionado.settings.text_color_dark = val;
        }
    }
});

function activarModoOscuroLimpio() {
    const s = props.elementoSeleccionado?.settings;
    if (!s) return;
    s.bg_dark_mode = 'custom';
    if (s.bg_type_dark === undefined) s.bg_type_dark = 'image';
    if (s.bg_image_dark === undefined) s.bg_image_dark = '';
    if (s.bg_color_dark === undefined) s.bg_color_dark = '';
    if (s.bg_overlay_dark === undefined) s.bg_overlay_dark = '';
    seleccionarTemaFondo('dark');
}

function copiarFondoClaroAOscuro() {
    const s = props.elementoSeleccionado?.settings;
    if (!s) return;
    s.bg_dark_mode = 'custom';
    s.bg_type_dark = s.bg_type || 'image';
    s.bg_image_dark = s.bg_image || '';
    s.bg_image_mobile_dark = s.bg_image_mobile || '';
    s.bg_size_dark = s.bg_size || 'cover';
    s.bg_position_dark = s.bg_position || 'center center';
    s.bg_size_tablet_dark = s.bg_size_tablet || '';
    s.bg_position_tablet_dark = s.bg_position_tablet || '';
    s.bg_size_mobile_dark = s.bg_size_mobile || '';
    s.bg_position_mobile_dark = s.bg_position_mobile || '';
    s.bg_attachment_dark = s.bg_attachment || 'scroll';
    s.bg_repeat_dark = s.bg_repeat || 'no-repeat';
    s.bg_blend_mode_dark = s.bg_blend_mode || 'normal';
    s.bg_filter_dark = s.bg_filter || 'none';
    s.bg_hide_mobile_dark = s.bg_hide_mobile || 'none';
    s.bg_video_dark = s.bg_video || '';
    s.bg_video_poster_dark = s.bg_video_poster || '';
    s.bg_video_mobile_dark = s.bg_video_mobile || 'fallback';
    s.bg_video_position_dark = s.bg_video_position || 'center center';
    s.bg_video_playback_rate_dark = s.bg_video_playback_rate || 1;
    s.bg_color_dark = s.bg_color || '';
    s.bg_overlay_dark = s.bg_overlay || '';
    seleccionarTemaFondo('dark');
}
</script>

<template>
    <div v-if="elementoSeleccionado?.settings" class="space-y-4">
        <!-- BLOQUE 1: CONTENEDOR Y ALTURA -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-box text-emerald-600 dark:text-emerald-400" />
                <span>Contenedor y Maquetación Principal</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Ancho Máximo del Contenedor</label>
                <Select
                    v-model="containerModel"
                    :options="opcionesContenedor"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Control de Altura</label>
                    <Select
                        v-model="heightTypeModel"
                        :options="opcionesAltura"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div v-if="elementoSeleccionado.settings.height_type === 'pixels'">
                    <label class="block text-xs font-semibold mb-1.5">Altura Fija (px)</label>
                    <InputText
                        v-model.number="elementoSeleccionado.settings.height_px"
                        type="number"
                        min="100"
                        max="2000"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <div v-if="elementoSeleccionado.settings.height_type && elementoSeleccionado.settings.height_type !== 'none'">
                <label class="block text-xs font-semibold mb-1.5">Alineación Vertical del Contenido</label>
                <Select
                    v-model="verticalAlignModel"
                    :options="opcionesAlineacionVertical"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- BLOQUE 2: FONDOS, COLORES Y OVERLAYS (CON SOPORTE INDEPENDIENTE MODO CLARO / OSCURO) -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="pi pi-palette text-emerald-600 dark:text-emerald-400" />
                    <span>Estilos Visuales, Fondos y Capas</span>
                </div>

                <!-- Conmutador de Tema de Fondo (Modo Claro / Modo Oscuro) -->
                <div class="flex items-center bg-zinc-200/80 dark:bg-zinc-800 p-0.5 rounded-lg text-[10px] shrink-0">
                    <button
                        type="button"
                        @click="seleccionarTemaFondo('light')"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                            temaFondo === 'light'
                                ? 'bg-white dark:bg-zinc-700 text-amber-600 dark:text-amber-400 shadow-xs'
                                : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Editar fondo para Modo Claro'"
                    >
                        <i class="pi pi-sun text-[10px]" />
                        <span>Claro</span>
                    </button>
                    <button
                        type="button"
                        @click="seleccionarTemaFondo('dark')"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                            temaFondo === 'dark'
                                ? 'bg-white dark:bg-zinc-700 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Editar fondo independiente para Modo Oscuro'"
                    >
                        <i class="pi pi-moon text-[10px]" />
                        <span>Oscuro</span>
                        <span
                            v-if="bgDarkModeModel === 'custom'"
                            class="w-1.5 h-1.5 rounded-full bg-emerald-500"
                            title="Fondo personalizado activo en Modo Oscuro"
                        />
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Estilo Predefinido de Fondo</label>
                <Select
                    v-model="bgPresetModel"
                    :options="opcionesFondos"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════════════
                 PESTAÑA 1: CONFIGURACIÓN DE FONDO EN MODO CLARO (O BASE COMPARTIDA)
                 ═══════════════════════════════════════════════════════════════════════════ -->
            <template v-if="temaFondo === 'light'">
                <div class="px-2.5 py-1.5 rounded-lg bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-800/50 text-[10.5px] text-amber-800 dark:text-amber-200 flex items-center justify-between gap-2">
                    <span class="flex items-center gap-1.5 font-semibold">
                        <i class="pi pi-sun text-[11px]" />
                        <span>Editando: <strong>Modo Claro</strong></span>
                    </span>
                    <span class="text-[9.5px] opacity-80">
                        {{ bgDarkModeModel === 'custom' ? 'Modo Oscuro independiente activo' : 'Compartido con Modo Oscuro' }}
                    </span>
                </div>

                <!-- Selector de Tipo de Fondo Multimedia: Imagen o Vídeo -->
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Medio de Fondo</label>
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

                <!-- ═══ MODO 1: IMAGEN DE FONDO (CLARO) ═══ -->
            <template v-if="bgTypeModel === 'image'">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Imagen de Fondo de Sección</label>
                    <ImageUploader
                        v-model="elementoSeleccionado.settings.bg_image"
                        directorio="fondos"
                        ayuda="Imagen panorámica de alta resolución."
                    />
                </div>

            <!-- PANEL AVANZADO DE AJUSTES DE IMAGEN DE FONDO (Solo visible cuando hay una imagen asignada) -->
            <div
                v-if="elementoSeleccionado.settings.bg_image"
                class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200/60 dark:border-emerald-800/40 space-y-3 transition-all"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-800 dark:text-emerald-300">
                        <i class="pi pi-sliders-h text-xs" />
                        <span>Opciones de Fondo</span>
                    </div>

                    <!-- Selector de Dispositivo para la Imagen de Fondo -->
                    <div class="flex items-center bg-white dark:bg-zinc-800 p-0.5 rounded-lg border border-emerald-200/60 dark:border-emerald-800/40 text-[10px]">
                        <button
                            type="button"
                            @click="dispositivoFondo = 'desktop'"
                            :class="[
                                'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                                dispositivoFondo === 'desktop' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                            ]"
                            v-tooltip.top="'Escritorio (PC)'"
                        >
                            <i class="pi pi-desktop text-[10px]" />
                            <span>PC</span>
                        </button>
                        <button
                            type="button"
                            @click="dispositivoFondo = 'tablet'"
                            :class="[
                                'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                                dispositivoFondo === 'tablet' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                            ]"
                            v-tooltip.top="'Tablet'"
                        >
                            <i class="pi pi-tablet text-[10px]" />
                            <span>Tablet</span>
                            <span v-if="(elementoSeleccionado.settings.bg_size_tablet && elementoSeleccionado.settings.bg_size_tablet !== 'inherit') || (elementoSeleccionado.settings.bg_position_tablet && elementoSeleccionado.settings.bg_position_tablet !== 'inherit')" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        </button>
                        <button
                            type="button"
                            @click="dispositivoFondo = 'mobile'"
                            :class="[
                                'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                                dispositivoFondo === 'mobile' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                            ]"
                            v-tooltip.top="'Móvil'"
                        >
                            <i class="pi pi-mobile text-[10px]" />
                            <span>Móvil</span>
                            <span v-if="(elementoSeleccionado.settings.bg_size_mobile && elementoSeleccionado.settings.bg_size_mobile !== 'inherit') || (elementoSeleccionado.settings.bg_position_mobile && elementoSeleccionado.settings.bg_position_mobile !== 'inherit')" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        </button>
                    </div>
                </div>

                <!-- CONTROLES ESCRITORIO (PC) -->
                <template v-if="dispositivoFondo === 'desktop'">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Tamaño / Escalado (PC)</label>
                            <Select
                                v-model="bgSizeModel"
                                :options="opcionesBgSizeCompleto"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Posición / Encuadre (PC)</label>
                            <Select
                                v-model="bgPositionModel"
                                :options="opcionesBgPosition"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Efecto Desplazamiento</label>
                            <Select
                                v-model="bgAttachmentModel"
                                :options="opcionesBgAttachment"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Repetición / Patrón</label>
                            <Select
                                v-model="bgRepeatModel"
                                :options="opcionesBgRepeat"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Modo Fusión (Blend)</label>
                            <Select
                                v-model="bgBlendModeModel"
                                :options="opcionesBgBlendMode"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Filtro / Efecto Visual</label>
                            <Select
                                v-model="bgFilterModel"
                                :options="opcionesBgFilter"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>
                </template>

                <!-- CONTROLES TABLET -->
                <template v-else-if="dispositivoFondo === 'tablet'">
                    <div class="px-2 py-1.5 rounded-lg bg-emerald-100/60 dark:bg-emerald-900/30 text-[10px] text-emerald-800 dark:text-emerald-200">
                        <i class="pi pi-info-circle mr-1" />
                        Ajustes exclusivos para Tablet (768px - 1024px). Si eliges 'Heredar', usará el valor de PC.
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Tamaño / Escalado (Tablet)</label>
                            <Select
                                v-model="bgSizeTabletModel"
                                :options="opcionesBgSizeResponsive"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Posición / Encuadre (Tablet)</label>
                            <Select
                                v-model="bgPositionTabletModel"
                                :options="opcionesBgPositionResponsive"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>
                </template>

                <!-- CONTROLES MÓVIL -->
                <template v-else-if="dispositivoFondo === 'mobile'">
                    <div class="px-2 py-1.5 rounded-lg bg-emerald-100/60 dark:bg-emerald-900/30 text-[10px] text-emerald-800 dark:text-emerald-200">
                        <i class="pi pi-info-circle mr-1" />
                        Ajustes exclusivos para Smartphones (&lt; 768px). Si eliges 'Heredar', usará el valor de PC.
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Tamaño / Escalado (Móvil)</label>
                            <Select
                                v-model="bgSizeMobileModel"
                                :options="opcionesBgSizeResponsive"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Posición / Encuadre (Móvil)</label>
                            <Select
                                v-model="bgPositionMobileModel"
                                :options="opcionesBgPositionResponsive"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold mb-1">
                            Imagen Alternativa para Móvil (Opcional)
                        </label>
                        <ImageUploader
                            :modelValue="elementoSeleccionado.settings.bg_image_mobile || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.bg_image_mobile = val"
                            placeholder="Dejar vacío para usar la de escritorio optimizada..."
                            help="Si se deja vacía, el sistema optimiza y redimensiona automáticamente la imagen de PC a 768px en WebP."
                            :compact="true"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Visibilidad en Móviles</label>
                        <Select
                            v-model="bgHideMobileModel"
                            :options="opcionesBgHideMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </template>
            </div>
            </template>

            <!-- ═══ MODO 2: VÍDEO DE FONDO ═══ -->
            <template v-else-if="bgTypeModel === 'video'">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Vídeo de Fondo (HTML5 MP4 / WebM)</label>
                    <VideoUploader
                        v-model="elementoSeleccionado.settings.bg_video"
                        placeholder="Seleccionar vídeo de la biblioteca o URL..."
                        help="Vídeo en bucle continuo y silenciado de fondo."
                    />
                </div>

                <!-- PANEL DE AJUSTES DE VÍDEO DE FONDO -->
                <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200/60 dark:border-emerald-800/40 space-y-3 transition-all">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-800 dark:text-emerald-300">
                        <i class="pi pi-sliders-h text-xs" />
                        <span>Opciones de Vídeo de Fondo</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-semibold">Imagen Póster / Fallback Móvil</label>
                            <span
                                v-if="!elementoSeleccionado.settings.bg_video_poster && elementoSeleccionado.settings.bg_image"
                                class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium"
                            >
                                (Usando imagen previa como póster)
                            </span>
                        </div>
                        <ImageUploader
                            :modelValue="elementoSeleccionado.settings.bg_video_poster || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.bg_video_poster = val"
                            directorio="fondos"
                            placeholder="Seleccionar imagen póster..."
                            help="Se muestra instantáneamente mientras carga el vídeo o en móviles con ahorro de batería."
                            :compact="true"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Comportamiento en Móviles</label>
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
                            <label class="block text-[11px] font-semibold mb-1">Encuadre Focal</label>
                            <Select
                                v-model="bgVideoPositionModel"
                                :options="opcionesBgVideoPosition"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Velocidad</label>
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
                        <span>El vídeo se reproduce silenciado, en bucle y se pausa automáticamente al salir de pantalla para optimizar batería y FPS.</span>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold">Color Personalizado</label>
                        <button
                            v-if="elementoSeleccionado.settings.bg_color"
                            type="button"
                            @click="elementoSeleccionado.settings.bg_color = ''"
                            class="text-[10px] text-red-500 hover:underline cursor-pointer"
                        >
                            Limpiar
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            v-model="customBgColorPickerModel"
                            class="w-8 h-8 rounded border border-zinc-300 dark:border-zinc-700 cursor-pointer"
                        />
                        <InputText
                            v-model="elementoSeleccionado.settings.bg_color"
                            placeholder="Opcional (ej. #10b981)"
                            class="w-full text-xs font-mono"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Capa Superposición (Overlay)</label>
                    <InputText
                        v-model="elementoSeleccionado.settings.bg_overlay"
                        placeholder="Opcional (ej. rgba(0,0,0,0.4))"
                        class="w-full text-xs font-mono"
                    />
                </div>
            </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Color de Texto Forzado (Modo Claro)</label>
                    <Select
                        v-model="textColorModel"
                        :options="opcionesColorTexto"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════════════════
                 PESTAÑA 2: CONFIGURACIÓN INDEPENDIENTE DE FONDO EN MODO OSCURO
                 ═══════════════════════════════════════════════════════════════════════════ -->
            <template v-else>
                <div class="px-2.5 py-1.5 rounded-lg bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/70 dark:border-indigo-800/50 text-[10.5px] text-indigo-800 dark:text-indigo-200 flex items-center justify-between gap-2">
                    <span class="flex items-center gap-1.5 font-semibold">
                        <i class="pi pi-moon text-[11px]" />
                        <span>Editando: <strong>Modo Oscuro</strong></span>
                    </span>
                    <span class="text-[9.5px] opacity-80">SSR nativo sin parpadeo</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Comportamiento en Modo Oscuro</label>
                    <Select
                        v-model="bgDarkModeModel"
                        :options="opcionesModoFondoOscuro"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Estado heredado por defecto -->
                <div
                    v-if="bgDarkModeModel === 'inherit'"
                    class="p-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 space-y-2.5"
                >
                    <p class="text-[11px] text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Esta sección reutiliza actualmente el mismo fondo, imagen/vídeo y capa de superposición configurados en <strong>Modo Claro</strong>.
                    </p>
                    <div class="grid grid-cols-1 gap-1.5">
                        <button
                            type="button"
                            @click="activarModoOscuroLimpio"
                            class="w-full py-1.5 px-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-semibold transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-moon text-[10px]" />
                            <span>Crear fondo exclusivo para Modo Oscuro</span>
                        </button>
                        <button
                            v-if="elementoSeleccionado.settings.bg_image || elementoSeleccionado.settings.bg_video || elementoSeleccionado.settings.bg_overlay || elementoSeleccionado.settings.bg_color"
                            type="button"
                            @click="copiarFondoClaroAOscuro"
                            class="w-full py-1.5 px-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[11px] font-semibold transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-copy text-[10px]" />
                            <span>Copiar ajustes de Modo Claro para adaptar</span>
                        </button>
                    </div>
                </div>

                <!-- Campos independientes activos para Modo Oscuro -->
                <template v-else>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Medio de Fondo (Modo Oscuro)</label>
                        <div class="grid grid-cols-2 gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800/80 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                            <button
                                type="button"
                                @click="bgTypeDarkModel = 'image'"
                                :class="[
                                    'py-1.5 px-3 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer',
                                    bgTypeDarkModel === 'image'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                                ]"
                            >
                                <i class="pi pi-image text-xs" />
                                <span>Imagen</span>
                            </button>
                            <button
                                type="button"
                                @click="bgTypeDarkModel = 'video'"
                                :class="[
                                    'py-1.5 px-3 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer',
                                    bgTypeDarkModel === 'video'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                                ]"
                            >
                                <i class="pi pi-video text-xs" />
                                <span>Vídeo</span>
                            </button>
                        </div>
                    </div>

                    <!-- MODO 1 (OSCURO): IMAGEN DE FONDO -->
                    <template v-if="bgTypeDarkModel === 'image'">
                        <div>
                            <label class="block text-xs font-semibold mb-1.5">Imagen de Fondo (Modo Oscuro)</label>
                            <ImageUploader
                                :modelValue="elementoSeleccionado.settings.bg_image_dark || ''"
                                @update:modelValue="(val) => elementoSeleccionado.settings.bg_image_dark = val"
                                directorio="fondos"
                                ayuda="Se carga y renderiza únicamente en Modo Oscuro (cero impacto en Modo Claro)."
                            />
                        </div>

                        <div
                            v-if="elementoSeleccionado.settings.bg_image_dark"
                            class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-200/60 dark:border-indigo-800/40 space-y-3 transition-all"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5 text-[11px] font-bold text-indigo-800 dark:text-indigo-300">
                                    <i class="pi pi-sliders-h text-xs" />
                                    <span>Opciones de Fondo (Oscuro)</span>
                                </div>

                                <div class="flex items-center bg-white dark:bg-zinc-800 p-0.5 rounded-lg border border-indigo-200/60 dark:border-indigo-800/40 text-[10px]">
                                    <button
                                        type="button"
                                        @click="dispositivoFondoDark = 'desktop'"
                                        :class="[
                                            'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                                            dispositivoFondoDark === 'desktop' ? 'bg-indigo-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                                        ]"
                                    >
                                        <i class="pi pi-desktop text-[10px]" />
                                        <span>PC</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="dispositivoFondoDark = 'tablet'"
                                        :class="[
                                            'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                                            dispositivoFondoDark === 'tablet' ? 'bg-indigo-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                                        ]"
                                    >
                                        <i class="pi pi-tablet text-[10px]" />
                                        <span>Tablet</span>
                                        <span v-if="(elementoSeleccionado.settings.bg_size_tablet_dark && elementoSeleccionado.settings.bg_size_tablet_dark !== 'inherit') || (elementoSeleccionado.settings.bg_position_tablet_dark && elementoSeleccionado.settings.bg_position_tablet_dark !== 'inherit')" class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="dispositivoFondoDark = 'mobile'"
                                        :class="[
                                            'px-2 py-0.5 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer',
                                            dispositivoFondoDark === 'mobile' ? 'bg-indigo-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'
                                        ]"
                                    >
                                        <i class="pi pi-mobile text-[10px]" />
                                        <span>Móvil</span>
                                        <span v-if="(elementoSeleccionado.settings.bg_size_mobile_dark && elementoSeleccionado.settings.bg_size_mobile_dark !== 'inherit') || (elementoSeleccionado.settings.bg_position_mobile_dark && elementoSeleccionado.settings.bg_position_mobile_dark !== 'inherit') || elementoSeleccionado.settings.bg_image_mobile_dark" class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- PC (OSCURO) -->
                            <template v-if="dispositivoFondoDark === 'desktop'">
                                <div class="grid grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Tamaño / Escalado (PC)</label>
                                        <Select
                                            v-model="bgSizeDarkModel"
                                            :options="opcionesBgSizeCompleto"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Posición / Encuadre (PC)</label>
                                        <Select
                                            v-model="bgPositionDarkModel"
                                            :options="opcionesBgPosition"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Efecto Desplazamiento</label>
                                        <Select
                                            v-model="bgAttachmentDarkModel"
                                            :options="opcionesBgAttachment"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Repetición / Patrón</label>
                                        <Select
                                            v-model="bgRepeatDarkModel"
                                            :options="opcionesBgRepeat"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Modo Fusión (Blend)</label>
                                        <Select
                                            v-model="bgBlendModeDarkModel"
                                            :options="opcionesBgBlendMode"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Filtro / Efecto Visual</label>
                                        <Select
                                            v-model="bgFilterDarkModel"
                                            :options="opcionesBgFilter"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                </div>
                            </template>

                            <!-- TABLET (OSCURO) -->
                            <template v-else-if="dispositivoFondoDark === 'tablet'">
                                <div class="grid grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Tamaño / Escalado (Tablet)</label>
                                        <Select
                                            v-model="bgSizeTabletDarkModel"
                                            :options="opcionesBgSizeResponsive"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Posición / Encuadre (Tablet)</label>
                                        <Select
                                            v-model="bgPositionTabletDarkModel"
                                            :options="opcionesBgPositionResponsive"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                </div>
                            </template>

                            <!-- MÓVIL (OSCURO) -->
                            <template v-else-if="dispositivoFondoDark === 'mobile'">
                                <div class="grid grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Tamaño / Escalado (Móvil)</label>
                                        <Select
                                            v-model="bgSizeMobileDarkModel"
                                            :options="opcionesBgSizeResponsive"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold mb-1">Posición / Encuadre (Móvil)</label>
                                        <Select
                                            v-model="bgPositionMobileDarkModel"
                                            :options="opcionesBgPositionResponsive"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[11px] font-semibold mb-1">Imagen Alternativa Móvil (Oscuro)</label>
                                    <ImageUploader
                                        :modelValue="elementoSeleccionado.settings.bg_image_mobile_dark || ''"
                                        @update:modelValue="(val) => elementoSeleccionado.settings.bg_image_mobile_dark = val"
                                        placeholder="Dejar vacío para usar la de PC optimizada..."
                                        :compact="true"
                                    />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold mb-1">Visibilidad en Móviles (Oscuro)</label>
                                    <Select
                                        v-model="bgHideMobileDarkModel"
                                        :options="opcionesBgHideMobile"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full text-xs"
                                    />
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- MODO 2 (OSCURO): VÍDEO DE FONDO -->
                    <template v-else-if="bgTypeDarkModel === 'video'">
                        <div>
                            <label class="block text-xs font-semibold mb-1.5">Vídeo de Fondo (Modo Oscuro)</label>
                            <VideoUploader
                                :modelValue="elementoSeleccionado.settings.bg_video_dark || ''"
                                @update:modelValue="(val) => elementoSeleccionado.settings.bg_video_dark = val"
                                placeholder="Seleccionar vídeo para Modo Oscuro..."
                                help="Se reproduce exclusivamente cuando el visitante navega en Modo Oscuro."
                            />
                        </div>

                        <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-200/60 dark:border-indigo-800/40 space-y-3 transition-all">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-indigo-800 dark:text-indigo-300">
                                <i class="pi pi-sliders-h text-xs" />
                                <span>Opciones de Vídeo (Oscuro)</span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold mb-1">Imagen Póster / Fallback Móvil (Oscuro)</label>
                                <ImageUploader
                                    :modelValue="elementoSeleccionado.settings.bg_video_poster_dark || ''"
                                    @update:modelValue="(val) => elementoSeleccionado.settings.bg_video_poster_dark = val"
                                    directorio="fondos"
                                    placeholder="Seleccionar imagen póster oscura..."
                                    :compact="true"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold mb-1">Comportamiento en Móviles (Oscuro)</label>
                                <Select
                                    v-model="bgVideoMobileDarkModel"
                                    :options="opcionesBgVideoMobile"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-semibold mb-1">Encuadre Focal</label>
                                    <Select
                                        v-model="bgVideoPositionDarkModel"
                                        :options="opcionesBgVideoPosition"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold mb-1">Velocidad</label>
                                    <Select
                                        v-model="bgVideoPlaybackRateDarkModel"
                                        :options="opcionesBgVideoPlaybackRate"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full text-xs"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold">Color / Gradiente (Oscuro)</label>
                                <button
                                    v-if="elementoSeleccionado.settings.bg_color_dark"
                                    type="button"
                                    @click="elementoSeleccionado.settings.bg_color_dark = ''"
                                    class="text-[10px] text-red-500 hover:underline cursor-pointer"
                                >
                                    Limpiar
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="color"
                                    v-model="customBgColorDarkPickerModel"
                                    class="w-8 h-8 rounded border border-zinc-300 dark:border-zinc-700 cursor-pointer"
                                />
                                <InputText
                                    :modelValue="elementoSeleccionado.settings.bg_color_dark || ''"
                                    @update:modelValue="(val) => elementoSeleccionado.settings.bg_color_dark = val"
                                    placeholder="Transparente o #09090b"
                                    class="w-full text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold">Overlay (Oscuro)</label>
                                <button
                                    v-if="elementoSeleccionado.settings.bg_overlay_dark"
                                    type="button"
                                    @click="elementoSeleccionado.settings.bg_overlay_dark = ''"
                                    class="text-[10px] text-red-500 hover:underline cursor-pointer"
                                >
                                    Limpiar
                                </button>
                            </div>
                            <InputText
                                :modelValue="elementoSeleccionado.settings.bg_overlay_dark || ''"
                                @update:modelValue="(val) => elementoSeleccionado.settings.bg_overlay_dark = val"
                                placeholder="ej. rgba(0,0,0,0.6)"
                                class="w-full text-xs font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Color de Texto Forzado (Modo Oscuro)</label>
                        <Select
                            v-model="textColorDarkModel"
                            :options="opcionesColorTextoDark"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </template>
            </template>
        </div>

        <!-- BLOQUE 3: ESPACIADO INTERNO Y EXTERNO -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="pi pi-arrows-v text-emerald-600 dark:text-emerald-400" />
                    <span>Espaciado Interno y Externo</span>
                </div>

                <!-- Selector de Dispositivo (Escritorio / Tablet / Móvil) -->
                <div class="flex items-center bg-zinc-200/80 dark:bg-zinc-800 p-0.5 rounded-lg text-xs">
                    <button
                        type="button"
                        @click="dispositivoEspaciado = 'desktop'"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                            dispositivoEspaciado === 'desktop' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Escritorio (Desktop)'"
                    >
                        <i class="pi pi-desktop text-[10px]" />
                        <span>PC</span>
                    </button>
                    <button
                        type="button"
                        @click="dispositivoEspaciado = 'tablet'"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                            dispositivoEspaciado === 'tablet' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Tablet (Pantallas medianas)'"
                    >
                        <i class="pi pi-tablet text-[10px]" />
                        <span>Tablet</span>
                        <span v-if="elementoSeleccionado.settings.padding_top_tablet || elementoSeleccionado.settings.padding_bottom_tablet || elementoSeleccionado.settings.margin_tablet" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </button>
                    <button
                        type="button"
                        @click="dispositivoEspaciado = 'mobile'"
                        :class="[
                            'px-2 py-1 rounded-md flex items-center gap-1 font-semibold transition-all cursor-pointer text-[10px]',
                            dispositivoEspaciado === 'mobile' ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        v-tooltip.top="'Móvil (Pantallas pequeñas)'"
                    >
                        <i class="pi pi-mobile text-[10px]" />
                        <span>Móvil</span>
                        <span v-if="elementoSeleccionado.settings.padding_top_mobile || elementoSeleccionado.settings.padding_bottom_mobile || elementoSeleccionado.settings.margin_mobile" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </button>
                </div>
            </div>

            <!-- Indicador del dispositivo activo -->
            <div class="px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 text-[10px] text-emerald-700 dark:text-emerald-300 font-semibold flex items-center justify-between">
                <span>Modo de visualización: <strong class="uppercase font-bold">{{ dispositivoEspaciado === 'desktop' ? 'Escritorio (PC)' : dispositivoEspaciado }}</strong></span>
                <span v-if="dispositivoEspaciado !== 'desktop'" class="text-[9px] text-emerald-600 dark:text-emerald-400 font-normal">Sobrescribe el valor de PC</span>
            </div>

            <!-- VISTA ESCRITORIO (DEFAULT) -->
            <template v-if="dispositivoEspaciado === 'desktop'">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Padding Superior (Top - PC)</label>
                        <Select v-model="elementoSeleccionado.settings.padding_top" :options="opcionesPadding" optionLabel="label" optionValue="value" class="w-full text-xs" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Padding Inferior (Bottom - PC)</label>
                        <Select v-model="elementoSeleccionado.settings.padding_bottom" :options="opcionesPadding" optionLabel="label" optionValue="value" class="w-full text-xs" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Margen Vertical (PC)</label>
                        <Select v-model="elementoSeleccionado.settings.margin" :options="opcionesMargin" optionLabel="label" optionValue="value" class="w-full text-xs" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Espacio Entre Filas (PC)</label>
                        <Select
                            v-model="elementoSeleccionado.settings.row_gap"
                            :options="[
                                { label: 'Compacto (24px)', value: 'sm' },
                                { label: 'Estándar (48px)', value: 'md' },
                                { label: 'Amplio (64px)', value: 'lg' },
                                { label: 'Separado (80px)', value: 'xl' },
                                { label: 'Sin espacio (0px)', value: 'none' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </template>

            <!-- VISTA TABLET -->
            <template v-else-if="dispositivoEspaciado === 'tablet'">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Padding Superior (Tablet)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.padding_top_tablet || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.padding_top_tablet = val"
                            :options="opcionesPaddingTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Padding Inferior (Tablet)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.padding_bottom_tablet || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.padding_bottom_tablet = val"
                            :options="opcionesPaddingTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Margen Vertical (Tablet)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.margin_tablet || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.margin_tablet = val"
                            :options="opcionesMarginTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Espacio Entre Filas (Tablet)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.row_gap_tablet || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.row_gap_tablet = val"
                            :options="opcionesRowGapTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </template>

            <!-- VISTA MÓVIL -->
            <template v-else-if="dispositivoEspaciado === 'mobile'">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Padding Superior (Móvil)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.padding_top_mobile || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.padding_top_mobile = val"
                            :options="opcionesPaddingTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Padding Inferior (Móvil)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.padding_bottom_mobile || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.padding_bottom_mobile = val"
                            :options="opcionesPaddingTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Margen Vertical (Móvil)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.margin_mobile || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.margin_mobile = val"
                            :options="opcionesMarginTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Espacio Entre Filas (Móvil)</label>
                        <Select
                            :modelValue="elementoSeleccionado.settings.row_gap_mobile || ''"
                            @update:modelValue="(val) => elementoSeleccionado.settings.row_gap_mobile = val"
                            :options="opcionesRowGapTabletMobile"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </template>
        </div>

        <!-- BLOQUE 4: SEMÁNTICA HTML Y EFECTOS -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-code text-emerald-600 dark:text-emerald-400" />
                <span>Semántica HTML y Efectos</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Etiqueta Semántica HTML</label>
                <Select
                    v-model="htmlElementModel"
                    :options="opcionesElementoHtml"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Efecto Pegajoso (Sticky)</label>
                <Select
                    v-model="stickyModel"
                    :options="opcionesSticky"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- BLOQUE: CABECERA TRANSPARENTE -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-window-maximize text-emerald-600 dark:text-emerald-400" />
                <span>Cabecera Transparente</span>
            </div>

            <div class="space-y-2.5">
                <div class="flex items-start gap-2.5">
                    <Checkbox
                        v-model="transparentHeaderModel"
                        :binary="true"
                        inputId="chk_transparent_header"
                        class="mt-0.5"
                    />
                    <label for="chk_transparent_header" class="text-xs font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none">
                        Make header transparent
                    </label>
                </div>

                <div
                    v-if="transparentHeaderModel"
                    class="pl-6 space-y-3 pt-1 border-l-2 border-emerald-500/30 ml-2"
                >
                    <div class="flex items-start gap-2.5">
                        <Checkbox
                            v-model="transparentHeaderPullModel"
                            :binary="true"
                            inputId="chk_transparent_header_pull"
                            class="mt-0.5"
                        />
                        <label for="chk_transparent_header_pull" class="text-xs font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none">
                            Colocar contenido detrás del encabezado
                        </label>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold mb-1 text-zinc-700 dark:text-zinc-300">
                            Estilo del Menú en Transparencia (Modo Claro)
                        </label>
                        <Select
                            v-model="transparentHeaderNavColorModel"
                            :options="opcionesContrasteCabecera"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold mb-1 text-zinc-700 dark:text-zinc-300">
                            Estilo del Menú en Transparencia (Modo Oscuro)
                        </label>
                        <Select
                            v-model="transparentHeaderNavColorDarkModel"
                            :options="opcionesContrasteCabeceraDark"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed pt-1">
                    Haga que el encabezado sea transparente y superponga esta sección si sigue directamente al encabezado.
                </p>
            </div>
        </div>

        <!-- BLOQUE: ANIMACIÓN AL VISUALIZAR -->
        <AnimationBlock :target="elementoSeleccionado.settings" allowCascade />

        <!-- BLOQUE 5: PERSONALIZACIÓN AVANZADA Y CÓDIGO -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5">
            <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
                <i class="pi pi-cog text-emerald-600 dark:text-emerald-400" />
                <span>Personalización Avanzada y Código</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">ID HTML Único (`id`)</label>
                <InputText
                    v-model="elementoSeleccionado.settings.element_id"
                    placeholder="ej. mi-seccion-hero"
                    class="w-full text-xs font-mono"
                />
                <span class="text-[10px] text-zinc-400 block mt-1">Permite enlazar directamente con anclas de enlace (`#mi-seccion-hero`).</span>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Clases CSS Adicionales</label>
                <InputText
                    v-model="elementoSeleccionado.settings.custom_classes"
                    placeholder="ej. shadow-xl border-b-2 mi-clase-custom"
                    class="w-full text-xs font-mono"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Atributos HTML Personalizados</label>
                <InputText
                    v-model="elementoSeleccionado.settings.custom_attributes"
                    placeholder='ej. data-aos="fade-up" aria-label="Sección Hero"'
                    class="w-full text-xs font-mono"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">CSS Personalizado</label>
                <Textarea
                    v-model="elementoSeleccionado.settings.custom_css"
                    rows="4"
                    class="w-full text-xs font-mono"
                    placeholder="/* Reglas CSS específicas */&#10;p { color: #10b981; }"
                />
            </div>
        </div>
    </div>
</template>
