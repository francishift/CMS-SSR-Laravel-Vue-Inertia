<script setup>
/**
 * Diálogo de configuración global y maquetación granular del portal de Descargas.
 *
 * Incluye:
 *  - Cabecera, título, descripción, icono, tamaños tipográficos (`headingScale.js`), ancho de contenedor y visibilidad.
 *  - Selector entre Vista Configurable Estándar y Maquetación Libre en el Page Builder.
 *  - Disposición Grid / Listado y columnas responsivas.
 *  - Control completo de tamaños, truncado, imagen/SVG, superficie y botones de cada ítem/tarjeta.
 *  - SEO de portada.
 * Cumple estrictamente la Regla PrimeVue 4 Selects (cero cadenas vacías en optionValue).
 */
import { watch, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import { OPCIONES_TAMANO_VISUAL } from '@/Utils/headingScale';

import Dialog from 'primevue/dialog';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';

const props = defineProps({
    visible: { type: Boolean, default: false },
    configuracion: { type: Object, required: true },
});

const emit = defineEmits(['update:visible']);

const tabActiva = ref('general');

const opcionesVisibilidad = [
    { label: 'Solo Backend / Administradores (Privado)', value: 'admin_only' },
    { label: 'Público (Abierto e indexable en buscadores)', value: 'public' },
];

const opcionesContenedor = [
    { label: 'Estándar Centrado (1280px)', value: 'boxed' },
    { label: 'Expandido Amplio (1536px)', value: 'expand' },
    { label: 'Compacto Lectura (896px)', value: 'small' },
    { label: 'Ancho Completo con Márgenes (100%)', value: 'full_margin' },
];

const opcionesTamanoDescripcionCabecera = [
    { label: 'Pequeño (14px)', value: 'sm' },
    { label: 'Estándar (16px)', value: 'base' },
    { label: 'Grande (18px)', value: 'lg' },
    { label: 'Extra Grande (20px)', value: 'xl' },
];

const opcionesModoVista = [
    { label: 'Cuadrícula (Grid de tarjetas)', value: 'grid' },
    { label: 'Listado detallado (Filas horizontales)', value: 'list' },
];

const opcionesColsDesktop = [
    { label: '1 columna', value: 1 },
    { label: '2 columnas', value: 2 },
    { label: '3 columnas (Recomendado)', value: 3 },
    { label: '4 columnas', value: 4 },
];

const opcionesColsTablet = [
    { label: '1 columna', value: 1 },
    { label: '2 columnas', value: 2 },
    { label: '3 columnas', value: 3 },
];

const opcionesColsMobile = [
    { label: '1 columna', value: 1 },
    { label: '2 columnas', value: 2 },
];

const opcionesGap = [
    { label: 'Compacto (16px)', value: 'sm' },
    { label: 'Estándar (24px)', value: 'md' },
    { label: 'Amplio (32px)', value: 'lg' },
    { label: 'Extra amplio (40px)', value: 'xl' },
];

const opcionesAlineacion = [
    { label: 'Izquierda', value: 'left' },
    { label: 'Centrada', value: 'center' },
    { label: 'Derecha', value: 'right' },
];

const opcionesPerPage = [
    { label: '6 por página', value: 6 },
    { label: '9 por página', value: 9 },
    { label: '12 por página', value: 12 },
    { label: '18 por página', value: 18 },
    { label: '24 por página', value: 24 },
];

// Opciones de estilo y tamaños de cada ítem/tarjeta
const opcionesCardStyle = [
    { label: 'Estándar (Borde sutil + Hover)', value: 'default' },
    { label: 'Sombra Elevada', value: 'shadow' },
    { label: 'Plana Suave (Sin borde)', value: 'flat' },
    { label: 'Borde Destacado Primario', value: 'bordered' },
];

const opcionesTitleTag = [
    { label: 'H2 (Recomendado SEO)', value: 'h2' },
    { label: 'H3 (Subsección)', value: 'h3' },
    { label: 'H4 (Compacto)', value: 'h4' },
    { label: 'DIV (Sin peso semántico)', value: 'div' },
];

const opcionesItemDescSize = [
    { label: 'Compacto (12px)', value: 'xs' },
    { label: 'Estándar (14px)', value: 'sm' },
    { label: 'Medio (16px)', value: 'base' },
    { label: 'Grande (18px)', value: 'lg' },
];

const opcionesItemDescLines = [
    { label: '2 líneas máximo', value: '2' },
    { label: '3 líneas máximo', value: '3' },
    { label: '4 líneas máximo', value: '4' },
    { label: 'Texto completo (Sin recortar)', value: 'none' },
];

const opcionesImageRatio = [
    { label: '16:9 Panorámica', value: '16:9' },
    { label: '4:3 Clásica', value: '4:3' },
    { label: '1:1 Cuadrada', value: '1:1' },
    { label: 'Banda Compacta (144px)', value: 'compact' },
    { label: 'Automática Natural', value: 'auto' },
];

const opcionesSvgSize = [
    { label: 'Pequeño (56px)', value: 'sm' },
    { label: 'Mediano (80px)', value: 'md' },
    { label: 'Grande (96px - Estándar)', value: 'lg' },
    { label: 'Extra Grande (128px)', value: 'xl' },
];

const opcionesImagePosition = [
    { label: 'A Sangre Superior (Flush)', value: 'flush' },
    { label: 'Con Margen Interno (Boxed)', value: 'boxed' },
];

const opcionesButtonStyle = [
    { label: 'Botón Primario Sólido', value: 'button_primary' },
    { label: 'Botón Secundario', value: 'button_secondary' },
    { label: 'Botón Contorno (Outline)', value: 'button_outline' },
    { label: 'Enlace de Texto', value: 'link' },
];

const opcionesButtonSize = [
    { label: 'Pequeño (sm)', value: 'sm' },
    { label: 'Estándar (md)', value: 'md' },
    { label: 'Grande (lg)', value: 'lg' },
];

const opcionesRobots = [
    { label: 'Indexar y seguir enlaces (index, follow)', value: 'default_index' },
    { label: 'No indexar, seguir enlaces (noindex, follow)', value: 'noindex, follow' },
    { label: 'No indexar ni seguir (noindex, nofollow)', value: 'noindex, nofollow' },
];

function extraerDatosConfig(cfg) {
    return {
        page_title: cfg?.page_title || 'Descargas',
        slug: cfg?.slug || 'descargas',
        description: cfg?.description || '',
        icon: cfg?.icon || 'pi pi-download',
        public_visibility: cfg?.public_visibility || 'admin_only',
        use_builder_layout: Boolean(cfg?.use_builder_layout ?? false),
        use_item_builder_layout: Boolean(cfg?.use_item_builder_layout ?? false),
        default_view_mode: cfg?.default_view_mode || 'grid',
        allow_view_toggle: Boolean(cfg?.allow_view_toggle ?? true),
        cols_desktop: Number(cfg?.cols_desktop || 3),
        cols_tablet: Number(cfg?.cols_tablet || 2),
        cols_mobile: Number(cfg?.cols_mobile || 1),
        gap_size: cfg?.gap_size || 'md',
        header_align: cfg?.header_align || 'left',
        container_width: cfg?.container_width || 'boxed',
        show_breadcrumb: Boolean(cfg?.show_breadcrumb ?? true),
        show_header_icon: Boolean(cfg?.show_header_icon ?? true),
        show_description: Boolean(cfg?.show_description ?? true),
        header_title_size: cfg?.header_title_size || 'h1',
        header_desc_size: cfg?.header_desc_size || 'lg',
        per_page: Number(cfg?.per_page || 12),
        card_style: cfg?.card_style || 'default',
        card_text_align: cfg?.card_text_align || 'left',
        item_title_tag: cfg?.item_title_tag || 'h2',
        item_title_size: cfg?.item_title_size || 'h4',
        item_desc_size: cfg?.item_desc_size || 'sm',
        item_desc_lines: String(cfg?.item_desc_lines || '3'),
        show_item_image: Boolean(cfg?.show_item_image ?? true),
        show_item_description: Boolean(cfg?.show_item_description ?? true),
        show_item_badges: Boolean(cfg?.show_item_badges ?? true),
        show_item_meta: Boolean(cfg?.show_item_meta ?? true),
        item_image_ratio: cfg?.item_image_ratio || '16:9',
        item_svg_size: cfg?.item_svg_size || 'lg',
        item_image_position: cfg?.item_image_position || 'flush',
        item_button_style: cfg?.item_button_style || 'button_primary',
        item_button_size: cfg?.item_button_size || 'md',
        show_quick_download: Boolean(cfg?.show_quick_download ?? true),
        show_downloads_count_public: Boolean(cfg?.show_downloads_count_public ?? false),
        seo_title: cfg?.seo_title || '',
        seo_description: cfg?.seo_description || '',
        seo_og_image: cfg?.seo_og_image || '',
        meta_robots: cfg?.meta_robots || null,
    };
}

const form = useForm(extraerDatosConfig(props.configuracion));
const robotsSelectModel = ref(props.configuracion?.meta_robots || 'default_index');

watch(robotsSelectModel, (val) => {
    form.meta_robots = val === 'default_index' ? null : val;
});

watch(
    () => props.configuracion,
    (cfg) => {
        if (!cfg) return;
        const nuevos = extraerDatosConfig(cfg);
        Object.keys(nuevos).forEach((k) => {
            form[k] = nuevos[k];
        });
        robotsSelectModel.value = cfg.meta_robots || 'default_index';
    },
    { deep: true }
);

function guardarConfiguracion() {
    form.put(route('downloads.admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:visible', false);
        },
    });
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        header="Diseño, Tamaños y Textos del Catálogo de Descargas"
        :modal="true"
        class="w-full max-w-4xl"
    >
        <form @submit.prevent="guardarConfiguracion" class="space-y-5 pt-1">
            <Tabs v-model:value="tabActiva">
                <TabList class="flex-wrap">
                    <Tab value="general">
                        <i class="pi pi-sliders-h mr-1.5 text-xs" />
                        Cabecera y Textos
                    </Tab>
                    <Tab value="layout">
                        <i class="pi pi-th-large mr-1.5 text-xs" />
                        Columnas y Contenedor
                    </Tab>
                    <Tab value="cards">
                        <i class="pi pi-id-card mr-1.5 text-xs" />
                        Tamaños de cada Ítem
                    </Tab>
                    <Tab value="seo">
                        <i class="pi pi-globe mr-1.5 text-xs" />
                        SEO Portada
                    </Tab>
                </TabList>

                <TabPanels class="px-0 pt-4 pb-0">
                    <!-- PESTAÑA 1: Cabecera, Textos, Tamaños de Cabecera y Modo Builder -->
                    <TabPanel value="general" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/70 dark:bg-amber-950/30">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-1.5">
                                    Visibilidad Global *
                                </label>
                                <Select
                                    v-model="form.public_visibility"
                                    :options="opcionesVisibilidad"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </div>

                            <div class="p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/60 dark:bg-emerald-950/30 flex flex-col justify-between gap-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <span class="block text-xs font-bold text-emerald-950 dark:text-emerald-200">
                                            Portada en Page Builder
                                        </span>
                                        <span class="block text-[11px] text-emerald-800/80 dark:text-emerald-300/80">
                                            Usa el diseño libre del Builder para `/{{ form.slug }}`.
                                        </span>
                                    </div>
                                    <ToggleSwitch v-model="form.use_builder_layout" />
                                </div>
                                <div class="flex items-center justify-between gap-2 pt-2 border-t border-emerald-200/60 dark:border-emerald-800/50">
                                    <div>
                                        <span class="block text-xs font-bold text-emerald-950 dark:text-emerald-200">
                                            Ficha Ítem en Page Builder
                                        </span>
                                        <span class="block text-[11px] text-emerald-800/80 dark:text-emerald-300/80">
                                            Usa la plantilla visual para cada ficha individual.
                                        </span>
                                    </div>
                                    <ToggleSwitch v-model="form.use_item_builder_layout" />
                                </div>
                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <Link
                                        :href="route('downloads.admin.builder')"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:underline no-underline"
                                    >
                                        <i class="pi pi-palette text-xs" />
                                        <span>Maquetar Portada →</span>
                                    </Link>
                                    <Link
                                        :href="route('downloads.admin.item-builder')"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:underline no-underline"
                                    >
                                        <i class="pi pi-objects-column text-xs" />
                                        <span>Maquetar Ficha →</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Título Principal de la Página *
                                </label>
                                <InputText v-model="form.page_title" required class="w-full" placeholder="Ej: Elementos y Plugins para YOOtheme Pro" />
                                <p v-if="form.errors.page_title" class="text-xs text-red-500 mt-1">{{ form.errors.page_title }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Slug URL *
                                </label>
                                <InputText v-model="form.slug" required class="w-full font-mono text-xs" placeholder="descargas" />
                                <p v-if="form.errors.slug" class="text-xs text-red-500 mt-1">{{ form.errors.slug }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                Descripción Introductoria de la Página
                            </label>
                            <Textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full text-sm"
                                placeholder="Texto descriptivo que aparece debajo del título principal..."
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Tamaño Título Página
                                </label>
                                <Select
                                    v-model="form.header_title_size"
                                    :options="OPCIONES_TAMANO_VISUAL"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Tamaño Descripción
                                </label>
                                <Select
                                    v-model="form.header_desc_size"
                                    :options="opcionesTamanoDescripcionCabecera"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Alineación Cabecera
                                </label>
                                <Select
                                    v-model="form.header_align"
                                    :options="opcionesAlineacion"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Icono Cabecera
                                </label>
                                <IconPicker v-model="form.icon" placeholder="Icono..." />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <ToggleSwitch v-model="form.show_header_icon" inputId="dl_sh_icon" />
                                <label for="dl_sh_icon" class="text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">Mostrar icono de cabecera</label>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <ToggleSwitch v-model="form.show_description" inputId="dl_sh_desc" />
                                <label for="dl_sh_desc" class="text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">Mostrar descripción</label>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <ToggleSwitch v-model="form.show_breadcrumb" inputId="dl_sh_bread" />
                                <label for="dl_sh_bread" class="text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">Mostrar migas de pan</label>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- PESTAÑA 2: Columnas, Contenedor y Paginación -->
                    <TabPanel value="layout" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Ancho del Contenedor
                                </label>
                                <Select v-model="form.container_width" :options="opcionesContenedor" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Vista por defecto
                                </label>
                                <Select v-model="form.default_view_mode" :options="opcionesModoVista" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Elementos por página
                                </label>
                                <Select v-model="form.per_page" :options="opcionesPerPage" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Cols Escritorio
                                </label>
                                <Select v-model="form.cols_desktop" :options="opcionesColsDesktop" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Cols Tablet
                                </label>
                                <Select v-model="form.cols_tablet" :options="opcionesColsTablet" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Cols Móvil
                                </label>
                                <Select v-model="form.cols_mobile" :options="opcionesColsMobile" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Separación (Gap)
                                </label>
                                <Select v-model="form.gap_size" :options="opcionesGap" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                        </div>

                        <div class="space-y-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-3">
                                <ToggleSwitch v-model="form.allow_view_toggle" inputId="dl_allow_toggle" />
                                <label for="dl_allow_toggle" class="text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                    Permitir al visitante alternar entre vista <strong>Grid</strong> y <strong>Listado</strong>
                                </label>
                            </div>
                            <div class="flex items-center gap-3">
                                <ToggleSwitch v-model="form.show_downloads_count_public" inputId="dl_public_count" />
                                <label for="dl_public_count" class="text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                    Mostrar contador total de descargas al público (desactivado = visible solo en backend)
                                </label>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- PESTAÑA 3: Control Granular de Tamaños y Estilo de Cada Ítem / Tarjeta -->
                    <TabPanel value="cards" class="space-y-5">
                        <!-- Tipografía del Título y Descripción de cada Ítem -->
                        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-700/80 space-y-3">
                            <span class="block text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                Tipografía y Tamaños de Texto del Ítem
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold mb-1">Etiqueta SEO Título</label>
                                    <Select v-model="form.item_title_tag" :options="opcionesTitleTag" optionLabel="label" optionValue="value" class="w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold mb-1">Tamaño Visual Título</label>
                                    <Select v-model="form.item_title_size" :options="OPCIONES_TAMANO_VISUAL" optionLabel="label" optionValue="value" class="w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold mb-1">Tamaño Descripción</label>
                                    <Select v-model="form.item_desc_size" :options="opcionesItemDescSize" optionLabel="label" optionValue="value" class="w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold mb-1">Límite Líneas Desc.</label>
                                    <Select v-model="form.item_desc_lines" :options="opcionesItemDescLines" optionLabel="label" optionValue="value" class="w-full text-xs" />
                                </div>
                            </div>
                        </div>

                        <!-- Tamaños de Imagen, Icono SVG, Superficie y Botones -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Proporción Caja Imagen
                                </label>
                                <Select v-model="form.item_image_ratio" :options="opcionesImageRatio" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Tamaño Icono SVG
                                </label>
                                <Select v-model="form.item_svg_size" :options="opcionesSvgSize" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Relleno de Imagen
                                </label>
                                <Select v-model="form.item_image_position" :options="opcionesImagePosition" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Estilo de Tarjeta
                                </label>
                                <Select v-model="form.card_style" :options="opcionesCardStyle" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Alineación Tarjeta
                                </label>
                                <Select v-model="form.card_text_align" :options="opcionesAlineacion" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Estilo de Botón
                                </label>
                                <Select v-model="form.item_button_style" :options="opcionesButtonStyle" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Tamaño de Botón
                                </label>
                                <Select v-model="form.item_button_size" :options="opcionesButtonSize" optionLabel="label" optionValue="value" class="w-full text-xs" />
                            </div>
                        </div>

                        <!-- Visibilidad de elementos individuales dentro de cada tarjeta -->
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-2">
                                <ToggleSwitch v-model="form.show_item_image" inputId="dl_it_img" />
                                <label for="dl_it_img" class="text-xs font-medium cursor-pointer">Imagen / SVG</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <ToggleSwitch v-model="form.show_item_description" inputId="dl_it_desc" />
                                <label for="dl_it_desc" class="text-xs font-medium cursor-pointer">Descripción</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <ToggleSwitch v-model="form.show_item_badges" inputId="dl_it_bdg" />
                                <label for="dl_it_bdg" class="text-xs font-medium cursor-pointer">Precio / Versión</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <ToggleSwitch v-model="form.show_item_meta" inputId="dl_it_meta" />
                                <label for="dl_it_meta" class="text-xs font-medium cursor-pointer">Compatibilidad / KB</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <ToggleSwitch v-model="form.show_quick_download" inputId="dl_it_quick" />
                                <label for="dl_it_quick" class="text-xs font-medium cursor-pointer">Descarga rápida</label>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- PESTAÑA 4: SEO del Catálogo -->
                    <TabPanel value="seo" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                Meta Title personalizado (Opcional)
                            </label>
                            <InputText v-model="form.seo_title" maxlength="160" class="w-full" placeholder="Por defecto usa el Título de la página" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                Meta Description (Opcional)
                            </label>
                            <Textarea v-model="form.seo_description" rows="2" maxlength="320" class="w-full" placeholder="Resumen atractivo para Google..." />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Directiva Robots (cuando sea Público)
                                </label>
                                <Select
                                    v-model="robotsSelectModel"
                                    :options="opcionesRobots"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <ImageUploader
                                    v-model="form.seo_og_image"
                                    label="Imagen OpenGraph / Redes Sociales (PNG/JPG/WebP)"
                                    compact
                                />
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <Button type="button" label="Cancelar" severity="secondary" @click="emit('update:visible', false)" />
                <Button type="submit" label="Guardar Diseño y Ajustes" icon="pi pi-check" severity="emerald" :loading="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
