<script setup>
/**
 * Formulario de edición de categoría con diseño 2 columnas (CMS Style) y acciones superiores.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';

import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import IconPicker from '@/Components/Admin/IconPicker.vue';

const props = defineProps({
    categoria: { type: Object, required: true },
    categoriasParaSelector: { type: Array, default: () => [] },
    plantillasDisponibles: { type: Array, default: () => [] },
    plantillasCategoria: { type: Array, default: () => [] },
    plantillasArticulo: { type: Array, default: () => [] },
    tiposCampoExtra: { type: [Array, Object], default: () => [] },
});

const modeloTemplateCategoria = computed({
    get: () => form.category_builder_template_id ? Number(form.category_builder_template_id) : 'none',
    set: (val) => {
        form.category_builder_template_id = (val === 'none' || val === null) ? null : Number(val);
    }
});

const modeloTemplateArticulo = computed({
    get: () => form.article_builder_template_id ? Number(form.article_builder_template_id) : 'none',
    set: (val) => {
        form.article_builder_template_id = (val === 'none' || val === null) ? null : Number(val);
    }
});

const opcionesPlantillasCategoria = computed(() => [
    { label: 'Ninguna (Vista Estándar del Sistema)', value: 'none' },
    ...(props.plantillasCategoria || []).map(p => ({
        label: p.name,
        value: Number(p.id),
    }))
]);

const opcionesPlantillasArticulo = computed(() => [
    { label: 'Ninguna (Vista Estándar del Sistema)', value: 'none' },
    ...(props.plantillasArticulo || []).map(p => ({
        label: p.name,
        value: Number(p.id),
    }))
]);

const tiposNormalizados = computed(() => {
    if (Array.isArray(props.tiposCampoExtra) && props.tiposCampoExtra.length) {
        return props.tiposCampoExtra;
    }
    if (props.tiposCampoExtra && typeof props.tiposCampoExtra === 'object') {
        return Object.entries(props.tiposCampoExtra).map(([clave, nombre]) => ({
            clave,
            nombre: typeof nombre === 'string' ? nombre : (nombre.nombre || clave),
        }));
    }
    return [
        { clave: 'text', nombre: 'Texto corto' },
        { clave: 'textarea', nombre: 'Texto largo' },
        { clave: 'wysiwyg', nombre: 'Editor enriquecido (HTML)' },
        { clave: 'number', nombre: 'Número' },
        { clave: 'select', nombre: 'Selector (opciones)' },
        { clave: 'boolean', nombre: 'Sí / No (Interruptor)' },
        { clave: 'date', nombre: 'Fecha' },
        { clave: 'datetime', nombre: 'Fecha y Hora' },
        { clave: 'time', nombre: 'Hora' },
        { clave: 'image', nombre: 'Imagen (Biblioteca de Medios)' },
        { clave: 'video', nombre: 'Vídeo (Biblioteca de Medios)' },
        { clave: 'url', nombre: 'Enlace URL' },
        { clave: 'email', nombre: 'Correo electrónico' },
        { clave: 'color', nombre: 'Color (Hexadecimal)' },
        { clave: 'icon', nombre: 'Icono (PrimeIcons)' },
    ];
});

const form = useForm({
    name: props.categoria.name,
    slug: props.categoria.slug,
    description: props.categoria.description || '',
    parent_id: props.categoria.parent_id,
    template: props.categoria.template || 'grid',
    category_builder_template_id: props.categoria.category_builder_template_id || null,
    article_builder_template_id: props.categoria.article_builder_template_id || null,
    cols_mobile: props.categoria.cols_mobile ?? 1,
    cols_tablet: props.categoria.cols_tablet ?? 2,
    cols_desktop: props.categoria.cols_desktop ?? 3,
    gap_size: props.categoria.gap_size || 'md',
    align_grid: props.categoria.align_grid || 'start',
    header_align: props.categoria.header_align || 'left',
    title_tag: props.categoria.title_tag || 'h3',
    articles_order: props.categoria.articles_order || 'published_at_desc',
    per_page: props.categoria.per_page ?? 12,
    show_breadcrumb: props.categoria.show_breadcrumb ?? true,
    show_pagination: props.categoria.show_pagination ?? true,
    show_author: props.categoria.show_author ?? true,
    show_date: props.categoria.show_date ?? true,
    show_reading_time: props.categoria.show_reading_time ?? true,
    show_category_link: props.categoria.show_category_link ?? true,
    icon: props.categoria.icon || '',
    color: props.categoria.color || '#10b981',
    extra_fields_schema: props.categoria.extra_fields_schema || [],
    seo_title: props.categoria.seo_title || '',
    seo_description: props.categoria.seo_description || '',
    seo_og_image: props.categoria.seo_og_image || '',
    meta_robots: props.categoria.meta_robots || null,
    order: props.categoria.order || 0,
    is_active: Boolean(props.categoria.is_active),
});

const seoNoIndex = ref(Boolean(props.categoria?.meta_robots?.includes('noindex')));
const seoNoFollow = ref(Boolean(props.categoria?.meta_robots?.includes('nofollow')));

watch([seoNoIndex, seoNoFollow], ([noindex, nofollow]) => {
    if (noindex && nofollow) {
        form.meta_robots = 'noindex, nofollow';
    } else if (noindex) {
        form.meta_robots = 'noindex, follow';
    } else if (nofollow) {
        form.meta_robots = 'index, nofollow';
    } else {
        form.meta_robots = null;
    }
});

function agregarCampoExtra() {
    form.extra_fields_schema.push({
        key: '',
        label: '',
        type: 'text',
        options: [],
        required: false,
    });
}

function eliminarCampoExtra(index) {
    form.extra_fields_schema.splice(index, 1);
}

function guardar() {
    form.put(route('categorias.update', props.categoria.id));
}
</script>

<template>
    <Head :title="`Editar Categoría: ${categoria.name}`" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 w-full space-y-6">
            <form @submit.prevent="guardar" class="space-y-6">
                <!-- ═══════════════════════════════════════════════ -->
                <!-- CABECERA DE PÁGINA Y BOTONES DE ACCIÓN SUPERIOR -->
                <!-- ═══════════════════════════════════════════════ -->
                <PageHeader
                    :title="`Editar Categoría: ${categoria.name}`"
                    description="Modifica la configuración de layout, paginación y campos extra."
                    :breadcrumbs="[
                        { label: 'Categorías', href: route('categorias.index') },
                        { label: categoria.name },
                    ]"
                    divider
                >
                    <template #actions>
                        <Link
                            :href="route('categorias.index')"
                            class="px-4 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors inline-flex items-center gap-1.5"
                        >
                            <i class="pi pi-times text-xs" />
                            <span>Cancelar</span>
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors shadow-xs inline-flex items-center gap-2 cursor-pointer"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                            <i v-else class="pi pi-check text-xs" />
                            <span>Guardar Cambios</span>
                        </button>
                    </template>
                </PageHeader>

                <!-- ═══════════════════════════════════════════════ -->
                <!-- LAYOUT 2 COLUMNAS (CONTENIDO IZQUIERDO + SIDEBAR)-->
                <!-- ═══════════════════════════════════════════════ -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- ─────────────────────────────────────────── -->
                    <!-- COLUMNA IZQUIERDA: CONFIGURACIÓN PRINCIPAL (8 cols) -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- Datos Básicos -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                            <div class="flex items-center gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <i class="pi pi-tag text-sm" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Información General</h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre, slug y descripción de la categoría</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Nombre *</label>
                                    <input v-model="form.name" type="text" required class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Slug</label>
                                    <input v-model="form.slug" type="text" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100 font-mono text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Descripción</label>
                                    <textarea v-model="form.description" rows="3" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                                </div>
                            </div>
                        </section>

                        <!-- Plantillas Visuales del Maquetador Visual (Opcionales) -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                            <div class="flex items-center gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <i class="pi pi-palette text-sm" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Plantillas Visuales (Page Builder)</h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Personaliza la vista de esta categoría o de sus artículos con diseños creados en el Builder (Opcional)</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Plantilla para la Vista de esta Categoría
                                    </label>
                                    <Select
                                        v-model="modeloTemplateCategoria"
                                        :options="opcionesPlantillasCategoria"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full text-xs"
                                    />
                                    <p class="text-[11px] text-zinc-500 mt-1">
                                        Si seleccionas "Ninguna", se utilizará la disposición estándar de grid configurada abajo.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Plantilla para los Artículos de esta Categoría
                                    </label>
                                    <Select
                                        v-model="modeloTemplateArticulo"
                                        :options="opcionesPlantillasArticulo"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full text-xs"
                                    />
                                    <p class="text-[11px] text-zinc-500 mt-1">
                                        Si seleccionas "Ninguna", los artículos de esta categoría se mostrarán con la plantilla clásica de lectura.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Configuración de Layout de Grid y Paginación -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                            <div class="flex items-center gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-950/60 flex items-center justify-center text-teal-600 dark:text-teal-400">
                                    <i class="pi pi-th-large text-sm" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Disposición del Grid y Paginación</h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Columnas para dispositivos, márgenes, alineaciones y etiqueta SEO</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Columnas Móvil (&lt; 640px)</label>
                                    <select v-model="form.cols_mobile" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option :value="1">1 Columna</option>
                                        <option :value="2">2 Columnas</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Columnas Tablet (&gt;= 640px)</label>
                                    <select v-model="form.cols_tablet" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option :value="1">1 Columna</option>
                                        <option :value="2">2 Columnas</option>
                                        <option :value="3">3 Columnas</option>
                                        <option :value="4">4 Columnas</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Columnas Escritorio (&gt;= 1024px)</label>
                                    <select v-model="form.cols_desktop" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option :value="1">1 Columna</option>
                                        <option :value="2">2 Columnas</option>
                                        <option :value="3">3 Columnas</option>
                                        <option :value="4">4 Columnas</option>
                                        <option :value="5">5 Columnas</option>
                                        <option :value="6">6 Columnas</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Separación (Gap)</label>
                                    <select v-model="form.gap_size" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option value="sm">Pequeño (12px)</option>
                                        <option value="md">Mediano (24px - Estándar)</option>
                                        <option value="lg">Grande (32px)</option>
                                        <option value="xl">Extra Grande (48px)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Alineación Grid</label>
                                    <select v-model="form.align_grid" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option value="start">Izquierda / Inicio</option>
                                        <option value="center">Centrado</option>
                                        <option value="end">Derecha / Final</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Alineación Cabecera</label>
                                    <select v-model="form.header_align" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option value="left">Izquierda</option>
                                        <option value="center">Centrado</option>
                                        <option value="right">Derecha</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Etiqueta Título Tarjeta (SEO)</label>
                                    <select v-model="form.title_tag" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option value="h2">H2 (Recomendado para SEO)</option>
                                        <option value="h3">H3 (Predeterminado)</option>
                                        <option value="h1">H1</option>
                                        <option value="h4">H4</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Ordenación de Artículos</label>
                                    <select v-model="form.articles_order" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                        <option value="published_at_desc">Fecha de Publicación (Más reciente primero)</option>
                                        <option value="published_at_asc">Fecha de Publicación (Más antiguo primero)</option>
                                        <option value="title_asc">Alfabético (A - Z)</option>
                                        <option value="title_desc">Alfabético Inverso (Z - A)</option>
                                        <option value="manual">Orden Manual (Drag & Drop en panel)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Artículos por Página</label>
                                    <input v-model.number="form.per_page" type="number" min="1" max="100" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100" />
                                </div>
                            </div>
                        </section>

                        <!-- Opciones de Visualización y Metadatos -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                            <div class="pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    <i class="pi pi-eye text-emerald-600 dark:text-emerald-400" />
                                    <span>Opciones de Visualización y Metadatos</span>
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Controla qué elementos globales y datos de artículo se muestran públicamente en esta categoría.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                                    <div>
                                        <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Ruta del Sitio (Breadcrumb)</label>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block">Mostrar migas de pan (Inicio / Categoría / Artículo)</span>
                                    </div>
                                    <ToggleSwitch v-model="form.show_breadcrumb" />
                                </div>

                                <div class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                                    <div>
                                        <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Paginación de Artículos</label>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block">Mostrar controles de página en el listado</span>
                                    </div>
                                    <ToggleSwitch v-model="form.show_pagination" />
                                </div>

                                <div class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                                    <div>
                                        <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Autor del Artículo</label>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block">Mostrar nombre del autor en tarjetas y detalle</span>
                                    </div>
                                    <ToggleSwitch v-model="form.show_author" />
                                </div>

                                <div class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                                    <div>
                                        <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Fecha de Publicación</label>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block">Mostrar fecha de publicación en tarjetas y detalle</span>
                                    </div>
                                    <ToggleSwitch v-model="form.show_date" />
                                </div>

                                <div class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                                    <div>
                                        <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Tiempo Estimado de Lectura</label>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block">Mostrar minutos de lectura (ej. 3 min) en tarjetas y detalle</span>
                                    </div>
                                    <ToggleSwitch v-model="form.show_reading_time" />
                                </div>

                                <div class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                                    <div>
                                        <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100 block">Insignia / Enlace a la Categoría</label>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block">Mostrar la etiqueta/enlace a la categoría en la vista de detalle del artículo</span>
                                    </div>
                                    <ToggleSwitch v-model="form.show_category_link" />
                                </div>
                            </div>
                        </section>

                        <!-- Campos extra dinámicos -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Esquema de Campos Extra</h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Atributos adicionales para los artículos de esta categoría</p>
                                </div>
                                <Button type="button" icon="pi pi-plus" label="Añadir Campo" severity="secondary" size="small" @click="agregarCampoExtra" />
                            </div>

                            <div v-for="(campo, i) in form.extra_fields_schema" :key="i" class="p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Clave (ID técnico)</label>
                                        <InputText v-model="campo.key" placeholder="ej: fecha_evento" class="w-full text-xs font-mono" />
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Etiqueta visible</label>
                                        <InputText v-model="campo.label" placeholder="ej: Fecha del Evento" class="w-full text-xs" />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Tipo de campo</label>
                                        <Select
                                            v-model="campo.type"
                                            :options="tiposNormalizados"
                                            optionLabel="nombre"
                                            optionValue="clave"
                                            class="w-full text-xs"
                                        />
                                    </div>
                                    <div class="sm:col-span-2 flex items-center justify-between h-9 pb-1">
                                        <div class="flex items-center gap-1.5">
                                            <ToggleSwitch v-model="campo.required" />
                                            <span class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">Requerido</span>
                                        </div>
                                        <Button icon="pi pi-trash" severity="danger" variant="text" size="small" @click="eliminarCampoExtra(i)" />
                                    </div>
                                </div>

                                <!-- Opciones configurables si el tipo es 'select' -->
                                <div v-if="campo.type === 'select'" class="pt-2 border-t border-zinc-200/60 dark:border-zinc-700/60">
                                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">
                                        Opciones del desplegable (separadas por comas)
                                    </label>
                                    <InputText
                                        :model-value="Array.isArray(campo.options) ? campo.options.join(', ') : (campo.options || '')"
                                        @update:model-value="val => campo.options = val.split(',').map(s => s.trim()).filter(Boolean)"
                                        placeholder="ej: Opción 1, Opción 2, Opción 3"
                                        class="w-full text-xs"
                                    />
                                </div>
                            </div>

                            <div v-if="!form.extra_fields_schema.length" class="text-center py-6 text-zinc-500 text-xs border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
                                No has definido campos extra. Haz clic en "Añadir Campo" si los artículos de esta categoría requieren atributos personalizados.
                            </div>
                        </section>

                        <!-- SEO -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                            <div class="flex items-center gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-950/60 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <i class="pi pi-search text-sm" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Posicionamiento SEO</h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Metadatos para motores de búsqueda</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Título SEO (máx. 70)</label>
                                    <input v-model="form.seo_title" type="text" maxlength="70" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100" />
                                    <p class="text-xs text-zinc-400 mt-1">{{ (form.seo_title || '').length }}/70 caracteres</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Descripción SEO (máx. 160)</label>
                                    <textarea v-model="form.seo_description" rows="2" maxlength="160" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3.5 py-2 text-sm text-zinc-900 dark:text-zinc-100" />
                                    <p class="text-xs text-zinc-400 mt-1">{{ (form.seo_description || '').length }}/160 caracteres</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Imagen Open Graph (Compartir en Redes)</label>
                                    <ImageUploader v-model="form.seo_og_image" directorio="seo" ayuda="Imagen para redes sociales (1200×630px recomendado)" />
                                </div>

                                <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Indexación en Buscadores</p>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="seoNoIndex ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400'">
                                            {{ seoNoIndex ? 'No indexar' : 'Indexable (estándar)' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-zinc-500">Por defecto la categoría se indexa en Google. Activa solo si deseas restringirla.</p>

                                    <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                        <div class="text-xs pr-3">
                                            <label for="category_edit_seo_noindex" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                Bloquear Indexación (<code class="font-mono text-amber-600 dark:text-amber-400">noindex</code>)
                                            </label>
                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">Indica a los buscadores que no muestren esta categoría en los resultados.</p>
                                        </div>
                                        <ToggleSwitch v-model="seoNoIndex" id="category_edit_seo_noindex" />
                                    </div>

                                    <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                        <div class="text-xs pr-3">
                                            <label for="category_edit_seo_nofollow" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                No Seguir Enlaces (<code class="font-mono text-amber-600 dark:text-amber-400">nofollow</code>)
                                            </label>
                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">Indica a los buscadores no rastrear los hipervínculos de esta categoría.</p>
                                        </div>
                                        <ToggleSwitch v-model="seoNoFollow" id="category_edit_seo_nofollow" />
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div>

                    <!-- ─────────────────────────────────────────── -->
                    <!-- COLUMNA DERECHA: SIDEBAR DE OPCIONES (4 cols) -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-6">

                        <!-- Panel de Propiedades de Categoría -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm space-y-4">
                            <div class="flex items-center gap-2.5 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/60 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <i class="pi pi-cog text-xs" />
                                </div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Propiedades de Categoría</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Categoría padre</label>
                                    <select v-model="form.parent_id" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500/20">
                                        <option :value="null">— Ninguna (Categoría Principal) —</option>
                                        <option v-for="cat in categoriasParaSelector.filter(c => c.id !== categoria.id)" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Plantilla de visualización</label>
                                    <select v-model="form.template" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500/20">
                                        <option v-for="pl in plantillasDisponibles" :key="pl.clave" :value="pl.clave">{{ pl.nombre }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Color distintivo</label>
                                    <div class="flex items-center gap-3">
                                        <input v-model="form.color" type="color" class="w-10 h-10 rounded cursor-pointer border-0 bg-transparent shrink-0" />
                                        <input v-model="form.color" type="text" maxlength="7" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm font-mono text-zinc-900 dark:text-zinc-100" />
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                    <label class="flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                        <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-700 text-emerald-600 focus:ring-emerald-500/20 dark:bg-zinc-800" />
                                        <span>Categoría Activa (Visible públicamente)</span>
                                    </label>
                                </div>
                            </div>
                        </section>

                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════ -->
                <!-- BOTONES DE ACCIÓN INFERIOR (REPETIDOS POR COMODIDAD) -->
                <!-- ═══════════════════════════════════════════════ -->
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <Link
                        :href="route('categorias.index')"
                        class="px-5 py-2.5 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors shadow-sm inline-flex items-center gap-2 cursor-pointer"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                        <i v-else class="pi pi-check text-xs" />
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
