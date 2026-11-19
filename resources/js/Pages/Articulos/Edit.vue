<script setup>
/**
 * Formulario de edición de artículo profesional, dinámico y ergonómico.
 * Diseño a ancho completo (w-full), layout de 2 columnas balanceado:
 * - Cabecera sticky superior con acciones rápidas y badge dinámico de estado.
 * - Barra lateral derecha sticky con Imagen Destacada e Icono arriba (sin scroll) y Publicación.
 * - Área principal en ancho completo con Tabs de PrimeVue 4 (Contenido, Campos Personalizados y SEO).
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WysiwygEditor from '@/Components/Admin/WysiwygEditor.vue';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import VideoUploader from '@/Components/Admin/VideoUploader.vue';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';

import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import ColorPicker from 'primevue/colorpicker';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const props = defineProps({
    articulo: { type: Object, required: true },
    categorias: { type: Array, default: () => [] },
    plantillasArticulo: { type: Array, default: () => [] },
    estadosArticulo: { type: Object, default: () => ({}) },
    filtrosRetorno: { type: Object, default: () => ({}) },
});

const urlRetorno = computed(() => {
    return route('articulos.index', props.filtrosRetorno || {});
});

// Pestaña activa principal
const tabActiva = ref('contenido');

const opcionesEstados = computed(() => {
    return Object.entries(props.estadosArticulo || {}).map(([key, label]) => ({
        label,
        value: key,
    }));
});

const modeloTemplateArticulo = computed({
    get: () => form.article_builder_template_id ? Number(form.article_builder_template_id) : 'none',
    set: (val) => {
        form.article_builder_template_id = (val === 'none' || val === null) ? null : Number(val);
    }
});

const opcionesPlantillasArticulo = computed(() => [
    { label: 'Heredar de la Categoría / Estándar', value: 'none' },
    ...(props.plantillasArticulo || []).map(p => ({
        label: p.name,
        value: Number(p.id),
    }))
]);

const obtenerFechaActualLocalIso = () => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
};

const normalizarFechaParaInput = (fechaIso) => {
    if (!fechaIso) return '';
    const d = new Date(fechaIso);
    if (isNaN(d.getTime())) return fechaIso.slice(0, 16);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const form = useForm({
    title: props.articulo.title || '',
    slug: props.articulo.slug || '',
    excerpt: props.articulo.excerpt || '',
    body: props.articulo.body || '',
    featured_image: props.articulo.featured_image || '',
    icon: props.articulo.icon || '',
    category_id: props.articulo.category_id || (props.categorias[0]?.id || ''),
    article_builder_template_id: props.articulo.article_builder_template_id || null,
    status: props.articulo.raw_status || props.articulo.status || 'published',
    published_at: normalizarFechaParaInput(props.articulo.published_at_iso || props.articulo.published_at) || obtenerFechaActualLocalIso(),
    published_until: props.articulo.published_until_iso ? normalizarFechaParaInput(props.articulo.published_until_iso) : (props.articulo.published_until ? normalizarFechaParaInput(props.articulo.published_until) : null),
    extra_fields_data: props.articulo.extra_fields_data || {},
    seo_title: props.articulo.seo_title || '',
    seo_description: props.articulo.seo_description || '',
    seo_og_image: props.articulo.seo_og_image || '',
    meta_robots: props.articulo.meta_robots || null,
    is_featured: Boolean(props.articulo.is_featured),
    show_read_more: props.articulo.show_read_more ?? true,
    card_full_link: props.articulo.card_full_link ?? true,
    read_more_text: props.articulo.read_more_text || 'Leer más',
});

const seoNoIndex = ref(Boolean(props.articulo?.meta_robots?.includes('noindex')));
const seoNoFollow = ref(Boolean(props.articulo?.meta_robots?.includes('nofollow')));

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

// Detección de fecha futura
const esFechaFutura = computed(() => {
    if (!form.published_at) return false;
    const fecha = new Date(form.published_at);
    return !isNaN(fecha.getTime()) && fecha > new Date();
});

// Detección de finalización indefinida vs con fecha
const finalizacionIndefinida = ref(!form.published_until);

watch(finalizacionIndefinida, (esIndefinida) => {
    if (esIndefinida) {
        form.published_until = null;
    } else if (!form.published_until) {
        const base = form.published_at ? new Date(form.published_at) : new Date();
        base.setDate(base.getDate() + 30);
        base.setMinutes(base.getMinutes() - base.getTimezoneOffset());
        form.published_until = base.toISOString().slice(0, 16);
    }
});

const esFechaFinInvalida = computed(() => {
    if (finalizacionIndefinida.value || !form.published_until || !form.published_at) return false;
    return new Date(form.published_until) <= new Date(form.published_at);
});

const esFechaFinExpirada = computed(() => {
    if (finalizacionIndefinida.value || !form.published_until) return false;
    return new Date(form.published_until) <= new Date();
});

// Badge y estado visual reactivo
const estadoBadge = computed(() => {
    if (form.status === 'draft') {
        return { label: 'Borrador', severity: 'secondary', icon: 'pi pi-pencil' };
    }
    if (esFechaFinExpirada.value && form.status === 'published') {
        return { label: 'Expirado', severity: 'danger', icon: 'pi pi-times-circle' };
    }
    if (esFechaFutura.value || form.status === 'scheduled') {
        return { label: 'Programado', severity: 'warn', icon: 'pi pi-clock' };
    }
    return { label: 'Publicado', severity: 'success', icon: 'pi pi-check-circle' };
});

// Categoría actual y campos extra
const categoriaSeleccionadaObj = computed(() => {
    if (!form.category_id) return null;
    return props.categorias.find(c => c.id === form.category_id) || null;
});

const camposExtraCategoria = computed(() => {
    return categoriaSeleccionadaObj.value?.extra_fields_schema || [];
});

function esCampoVideo(campo) {
    if (!campo) return false;
    const key = (campo.key || '').toLowerCase();
    return campo.type === 'video' || key.includes('video') || key.includes('mp4') || key.includes('webm');
}

function esCampoImagen(campo) {
    if (!campo) return false;
    if (campo.type === 'video' || esCampoVideo(campo)) return false;
    const key = (campo.key || '').toLowerCase();
    return campo.type === 'image' || key.includes('img') || key.includes('foto') || key.includes('imagen');
}

watch(camposExtraCategoria, (nuevosCampos) => {
    const dataExistente = form.extra_fields_data || props.articulo.extra_fields_data || {};
    const objFinal = { ...dataExistente };
    nuevosCampos.forEach(campo => {
        if (objFinal[campo.key] === undefined || objFinal[campo.key] === null) {
            objFinal[campo.key] = campo.type === 'boolean' ? false : '';
        } else if (['date', 'datetime', 'time'].includes(campo.type) && typeof objFinal[campo.key] === 'string' && objFinal[campo.key]) {
            const parsedDate = new Date(objFinal[campo.key]);
            if (!isNaN(parsedDate.getTime())) {
                objFinal[campo.key] = parsedDate;
            }
        }
    });
    form.extra_fields_data = objFinal;
}, { immediate: true, deep: true });

function slugificar(texto) {
    return (texto || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()
        .replace(/[^a-z0-9 -]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
}

function generarSlug() {
    if (form.title) {
        form.slug = slugificar(form.title);
    }
}

function copiarEnlace() {
    const slugCat = categoriaSeleccionadaObj.value?.slug || 'categoria';
    const url = `${window.location.origin}/${slugCat}/${form.slug || props.articulo.slug}`;
    navigator.clipboard.writeText(url).then(() => {
        toast.add({
            severity: 'info',
            summary: 'Enlace copiado',
            detail: 'URL pública copiada al portapapeles',
            life: 3000,
        });
    });
}

function guardar() {
    form.transform((data) => ({
        ...data,
        _filtros_retorno: props.filtrosRetorno,
    })).put(route('articulos.update', { article: props.articulo.id, ...props.filtrosRetorno }));
}
</script>

<template>
    <Head :title="`Editar: ${articulo.title}`" />

    <AuthenticatedLayout>
        <!-- CONTENEDOR FLUIDO A ANCHO COMPLETO (w-full) -->
        <div class="w-full">
            <form @submit.prevent="guardar">
                <!-- ═══════════════════════════════════════════════ -->
                <!-- CABECERA EDITORIAL STICKY / COMPACTA CON ACCIONES -->
                <!-- ═══════════════════════════════════════════════ -->
                <div class="sticky top-0 z-20 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-md px-4 sm:px-6 lg:px-8 py-2.5 sm:py-3 border-b border-zinc-200/80 dark:border-zinc-800/80 flex items-center justify-between gap-4 transition-colors shadow-2xs">
                    <!-- Contexto de navegación y Título editorial -->
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <Link
                            :href="urlRetorno"
                            class="p-1.5 sm:p-2 rounded-xl text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors shrink-0"
                            title="Volver a Artículos"
                        >
                            <i class="pi pi-arrow-left text-sm" />
                        </Link>
                        <div class="h-4 w-px bg-zinc-200 dark:bg-zinc-800 hidden sm:block shrink-0" />
                        <div class="min-w-0 flex items-center gap-2">
                            <span class="text-xs text-zinc-400 dark:text-zinc-500 font-medium hidden md:inline shrink-0">Artículos /</span>
                            <span class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate max-w-[130px] sm:max-w-xs lg:max-w-md">
                                {{ form.title || articulo.title || 'Editar Artículo' }}
                            </span>
                            <Tag
                                :value="estadoBadge.label"
                                :severity="estadoBadge.severity"
                                :icon="estadoBadge.icon"
                                class="hidden sm:inline-flex !text-[10px] !py-0.5 !px-2 font-semibold shrink-0"
                            />
                        </div>
                    </div>

                    <!-- Botones de Acción directos -->
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <a
                            v-if="categoriaSeleccionadaObj?.slug && form.slug"
                            :href="`/${categoriaSeleccionadaObj.slug}/${form.slug}`"
                            target="_blank"
                            class="px-2.5 sm:px-3 py-1.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-semibold rounded-xl transition-all inline-flex items-center gap-1.5"
                            title="Ver en pestaña nueva"
                        >
                            <i class="pi pi-external-link text-xs" />
                            <span class="hidden sm:inline">Ver en web</span>
                        </a>

                        <Link
                            :href="urlRetorno"
                            class="px-2.5 sm:px-3 py-1.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-semibold rounded-xl transition-all inline-flex items-center gap-1.5"
                            title="Cancelar"
                        >
                            <i class="pi pi-times text-xs" />
                            <span class="hidden sm:inline">Cancelar</span>
                        </Link>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-3 sm:px-5 py-1.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow-emerald-500/20 inline-flex items-center gap-1.5 cursor-pointer"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                            <i v-else class="pi pi-check text-xs" />
                            <span class="hidden sm:inline">Guardar Cambios</span>
                            <span class="sm:hidden">Guardar</span>
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════ -->
                <!-- CUERPO PRINCIPAL A ANCHO COMPLETO (2 COLUMNAS)   -->
                <!-- ═══════════════════════════════════════════════ -->
                <div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                    <!-- ─────────────────────────────────────────── -->
                    <!-- COLUMNA IZQUIERDA: CONTENIDO Y TABS (8/9 cols) -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="lg:col-span-8 2xl:col-span-9 space-y-6">

                        <!-- Tarjeta de Título Principal y Permalink -->
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 p-5 sm:p-6 shadow-xs space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1.5">
                                    Título del Artículo *
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="Escribe el título principal aquí..."
                                    class="w-full text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 bg-zinc-50/70 dark:bg-zinc-800/50 border border-zinc-200/90 dark:border-zinc-700/80 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                                <p v-if="form.errors.title" class="text-red-500 text-xs mt-1.5 font-medium">{{ form.errors.title }}</p>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 pt-1 text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400 shrink-0 font-medium flex items-center gap-1.5">
                                    <i class="pi pi-link text-xs text-zinc-400" />
                                    <span>Enlace permanente:</span>
                                </span>
                                <div class="flex items-center gap-1.5 flex-1 min-w-0">
                                    <span class="text-zinc-400 dark:text-zinc-500 truncate hidden sm:inline">
                                        /{{ categoriaSeleccionadaObj?.slug || 'categoria' }}/
                                    </span>
                                    <InputText
                                        v-model="form.slug"
                                        placeholder="slug-del-articulo"
                                        class="w-full !py-1 !text-xs font-mono"
                                    />
                                    <button
                                        type="button"
                                        @click="generarSlug"
                                        title="Generar desde el título"
                                        class="p-1.5 text-zinc-500 hover:text-emerald-600 dark:hover:text-emerald-400 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-lg transition-colors shrink-0 cursor-pointer"
                                    >
                                        <i class="pi pi-refresh text-xs" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="copiarEnlace"
                                        title="Copiar URL completa"
                                        class="p-1.5 text-zinc-500 hover:text-blue-600 dark:hover:text-blue-400 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-lg transition-colors shrink-0 cursor-pointer"
                                    >
                                        <i class="pi pi-copy text-xs" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ PESTAÑAS PRIMEVUE 4 (TABS) ═══ -->
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 shadow-xs overflow-hidden">
                            <Tabs :value="tabActiva" @update:value="(val) => tabActiva = val">
                                <TabList class="px-4 border-b border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/60 dark:bg-zinc-900/60 flex items-center">
                                    <Tab value="contenido" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                                        <i class="pi pi-file-edit mr-2 text-emerald-600 dark:text-emerald-400" />
                                        <span>1. Contenido y Redacción</span>
                                    </Tab>

                                    <Tab value="campos" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                                        <i class="pi pi-th-large mr-2 text-purple-600 dark:text-purple-400" />
                                        <span>2. Campos Personalizados</span>
                                        <span
                                            v-if="camposExtraCategoria.length"
                                            class="ml-2 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300"
                                        >
                                            {{ camposExtraCategoria.length }}
                                        </span>
                                    </Tab>

                                    <Tab value="seo" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                                        <i class="pi pi-search mr-2 text-amber-600 dark:text-amber-400" />
                                        <span>3. SEO y Google SERP</span>
                                    </Tab>
                                </TabList>

                                <TabPanels class="p-5 sm:p-6">
                                    <!-- ─────────────────────────────────────────── -->
                                    <!-- TAB 1: CONTENIDO Y REDACCIÓN               -->
                                    <!-- ─────────────────────────────────────────── -->
                                    <TabPanel value="contenido" class="space-y-5">
                                        <div>
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                                                    Extracto / Resumen Corto
                                                </label>
                                                <span class="text-[11px] text-zinc-400">
                                                    {{ (form.excerpt || '').length }}/500
                                                </span>
                                            </div>
                                            <Textarea
                                                v-model="form.excerpt"
                                                rows="2"
                                                maxlength="500"
                                                placeholder="Breve introducción o extracto para tarjetas, listados y vistas previas..."
                                                class="w-full !text-xs !p-3"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                                Cuerpo del artículo (HTML / Texto Enriquecido)
                                            </label>
                                            <WysiwygEditor
                                                v-model="form.body"
                                                placeholder="Escribe el cuerpo completo del artículo..."
                                                height="420px"
                                            />
                                            <p v-if="form.errors.body" class="text-red-500 text-xs mt-1.5">{{ form.errors.body }}</p>
                                        </div>
                                    </TabPanel>

                                    <!-- ─────────────────────────────────────────── -->
                                    <!-- TAB 2: CAMPOS PERSONALIZADOS DINÁMICOS      -->
                                    <!-- ─────────────────────────────────────────── -->
                                    <TabPanel value="campos" class="space-y-4">
                                        <div v-if="camposExtraCategoria.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                                            <div
                                                v-for="campo in camposExtraCategoria"
                                                :key="campo.key"
                                                :class="[
                                                    'space-y-1.5 p-3.5 bg-zinc-50/70 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/70 dark:border-zinc-800/70 transition-colors',
                                                    (campo.type === 'textarea' || campo.type === 'wysiwyg') ? 'md:col-span-2 xl:col-span-3' : ''
                                                ]"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                                        {{ campo.label || campo.key }}
                                                        <span v-if="campo.required" class="text-red-500">*</span>
                                                    </label>
                                                    <span class="text-[10px] text-zinc-400 font-mono">{{ campo.key }}</span>
                                                </div>

                                                <!-- Tipo Vídeo -->
                                                <VideoUploader
                                                    v-if="campo.type === 'video' || esCampoVideo(campo)"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    :label="campo.label || campo.key"
                                                    help="Sube un archivo de vídeo o selecciona uno de la biblioteca de medios."
                                                />

                                                <!-- Tipo Imagen -->
                                                <ImageUploader
                                                    v-else-if="campo.type === 'image' || esCampoImagen(campo)"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    directorio="articulos_extra"
                                                    :ayuda="campo.label || campo.key"
                                                />

                                                <!-- Tipo WYSIWYG -->
                                                <WysiwygEditor
                                                    v-else-if="campo.type === 'wysiwyg'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    placeholder="Escribe el contenido HTML..."
                                                    height="220px"
                                                />

                                                <!-- Tipo Textarea -->
                                                <Textarea
                                                    v-else-if="campo.type === 'textarea'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    rows="2"
                                                    :placeholder="`Escribe ${campo.label || campo.key}...`"
                                                    class="w-full !text-xs"
                                                />

                                                <!-- Tipo Número -->
                                                <InputText
                                                    v-else-if="campo.type === 'number'"
                                                    v-model.number="form.extra_fields_data[campo.key]"
                                                    type="number"
                                                    placeholder="0"
                                                    class="w-full !text-xs font-medium"
                                                />

                                                <!-- Tipo Select -->
                                                <Select
                                                    v-else-if="campo.type === 'select'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    :options="campo.options || []"
                                                    :filter="true"
                                                    filterPlaceholder="Buscar opción..."
                                                    placeholder="— Seleccionar opción —"
                                                    class="w-full !text-xs"
                                                />

                                                <!-- Tipo Booleano (Interruptor) -->
                                                <div v-else-if="campo.type === 'boolean'" class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80">
                                                    <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ campo.label || campo.key }}</span>
                                                    <ToggleSwitch v-model="form.extra_fields_data[campo.key]" />
                                                </div>

                                                <!-- Tipo Fecha -->
                                                <DatePicker
                                                    v-else-if="campo.type === 'date'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    dateFormat="dd/mm/yy"
                                                    placeholder="DD/MM/YYYY"
                                                    showIcon
                                                    fluid
                                                    class="w-full !text-xs"
                                                />

                                                <!-- Tipo Fecha y Hora -->
                                                <DatePicker
                                                    v-else-if="campo.type === 'datetime'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    dateFormat="dd/mm/yy"
                                                    showTime
                                                    hourFormat="24"
                                                    placeholder="DD/MM/YYYY HH:mm"
                                                    showIcon
                                                    fluid
                                                    class="w-full !text-xs"
                                                />

                                                <!-- Tipo Hora -->
                                                <DatePicker
                                                    v-else-if="campo.type === 'time'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    timeOnly
                                                    hourFormat="24"
                                                    placeholder="HH:mm"
                                                    showIcon
                                                    fluid
                                                    class="w-full !text-xs"
                                                />

                                                <!-- Tipo Enlace URL -->
                                                <InputText
                                                    v-else-if="campo.type === 'url'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    type="url"
                                                    placeholder="https://..."
                                                    class="w-full !text-xs font-medium"
                                                />

                                                <!-- Tipo Correo Electrónico -->
                                                <InputText
                                                    v-else-if="campo.type === 'email'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    type="email"
                                                    placeholder="correo@ejemplo.com"
                                                    class="w-full !text-xs font-medium"
                                                />

                                                <!-- Tipo Color -->
                                                <div v-else-if="campo.type === 'color'" class="flex items-center gap-2">
                                                    <ColorPicker
                                                        :modelValue="(form.extra_fields_data[campo.key] || '').replace('#', '')"
                                                        @update:modelValue="val => form.extra_fields_data[campo.key] = val ? '#' + val : ''"
                                                        format="hex"
                                                    />
                                                    <InputText v-model="form.extra_fields_data[campo.key]" placeholder="#000000" class="w-full !text-xs font-mono" />
                                                </div>

                                                <!-- Tipo Icono (PrimeIcons) -->
                                                <IconPicker
                                                    v-else-if="campo.type === 'icon'"
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    label="Seleccionar Icono"
                                                />

                                                <!-- Fallback Texto Corto -->
                                                <InputText
                                                    v-else
                                                    v-model="form.extra_fields_data[campo.key]"
                                                    placeholder="Escribe un valor..."
                                                    class="w-full !text-xs font-medium"
                                                />
                                            </div>
                                        </div>

                                        <div v-else class="py-10 text-center bg-zinc-50/60 dark:bg-zinc-800/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800">
                                            <i class="pi pi-info-circle text-2xl text-zinc-400 mb-2" />
                                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                                Configuración Estándar
                                            </p>
                                            <p class="text-[11px] text-zinc-500 mt-1 max-w-sm mx-auto">
                                                Esta categoría no tiene campos personalizados configurados.
                                            </p>
                                        </div>
                                    </TabPanel>

                                    <!-- ─────────────────────────────────────────── -->
                                    <!-- TAB 3: POSICIONAMIENTO SEO & GOOGLE SERP    -->
                                    <!-- ─────────────────────────────────────────── -->
                                    <TabPanel value="seo" class="space-y-6">
                                        <!-- SIMULADOR INTERACTIVO GOOGLE SERP -->
                                        <div class="p-5 bg-zinc-50/70 dark:bg-zinc-800/40 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 space-y-2.5">
                                            <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                                                <span class="font-bold uppercase tracking-wider flex items-center gap-2">
                                                    <i class="pi pi-google text-xs text-blue-500" />
                                                    Previsualización en Google (SERP Snippet)
                                                </span>
                                                <span class="text-[10px]">Actualización en tiempo real</span>
                                            </div>

                                            <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 space-y-1.5 shadow-2xs font-sans">
                                                <!-- URL simulada de Google -->
                                                <div class="flex items-center gap-2 text-[11px] text-zinc-600 dark:text-zinc-400">
                                                    <div class="w-4 h-4 rounded-full bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center text-[9px] font-bold text-emerald-700 dark:text-emerald-300">
                                                        <i class="pi pi-globe text-[9px]" />
                                                    </div>
                                                    <span class="truncate">
                                                        {{ typeof window !== 'undefined' ? window.location.hostname : 'tudominio.com' }} › {{ categoriaSeleccionadaObj?.slug || 'categoria' }} › {{ form.slug || 'slug-articulo' }}
                                                    </span>
                                                </div>

                                                <!-- Título azul Google -->
                                                <h3 class="text-base sm:text-lg font-medium text-[#1a0dab] dark:text-[#8ab4f8] hover:underline cursor-pointer line-clamp-1">
                                                    {{ form.seo_title || form.title || 'Título del artículo en Google' }}
                                                </h3>

                                                <!-- Descripción Google -->
                                                <p class="text-xs text-[#4d5156] dark:text-[#bdc1c6] line-clamp-2 leading-relaxed">
                                                    {{ form.seo_description || form.excerpt || 'Escribe una meta-descripción atractiva para que los usuarios hagan clic en tu artículo desde los resultados de búsqueda de Google...' }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- CAMPOS SEO EDITABLES -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                            <div class="space-y-1.5">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                                                        Título SEO (Recomendado: 40-60 car.)
                                                    </label>
                                                    <span
                                                        :class="[
                                                            'text-[11px] font-mono',
                                                            (form.seo_title || '').length > 70 ? 'text-red-500 font-bold' : ((form.seo_title || '').length >= 40 ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400')
                                                        ]"
                                                    >
                                                        {{ (form.seo_title || '').length }}/70
                                                    </span>
                                                </div>
                                                <InputText
                                                    v-model="form.seo_title"
                                                    maxlength="70"
                                                    class="w-full !text-xs"
                                                    :placeholder="form.title || 'Título optimizado para buscadores...'"
                                                />
                                                <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-1.5 rounded-full mt-2 overflow-hidden">
                                                    <div
                                                        class="h-full transition-all duration-300 rounded-full"
                                                        :class="[
                                                            (form.seo_title || '').length > 70 ? 'bg-red-500' : ((form.seo_title || '').length >= 40 ? 'bg-emerald-500' : 'bg-amber-500')
                                                        ]"
                                                        :style="{ width: `${Math.min(100, ((form.seo_title || '').length / 70) * 100)}%` }"
                                                    />
                                                </div>
                                            </div>

                                            <div class="space-y-1.5">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                                                        Descripción SEO (Recomendado: 120-160 car.)
                                                    </label>
                                                    <span
                                                        :class="[
                                                            'text-[11px] font-mono',
                                                            (form.seo_description || '').length > 160 ? 'text-red-500 font-bold' : ((form.seo_description || '').length >= 120 ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400')
                                                        ]"
                                                    >
                                                        {{ (form.seo_description || '').length }}/160
                                                    </span>
                                                </div>
                                                <Textarea
                                                    v-model="form.seo_description"
                                                    rows="2"
                                                    maxlength="160"
                                                    class="w-full !text-xs"
                                                    :placeholder="form.excerpt || 'Resumen persuasivo para los resultados de Google...'"
                                                />
                                                <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-1.5 rounded-full mt-2 overflow-hidden">
                                                    <div
                                                        class="h-full transition-all duration-300 rounded-full"
                                                        :class="[
                                                            (form.seo_description || '').length > 160 ? 'bg-red-500' : ((form.seo_description || '').length >= 120 ? 'bg-emerald-500' : 'bg-amber-500')
                                                        ]"
                                                        :style="{ width: `${Math.min(100, ((form.seo_description || '').length / 160) * 100)}%` }"
                                                    />
                                                </div>
                                            </div>

                                            <div class="md:col-span-2 space-y-1.5 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                                    Imagen para Redes Sociales (OpenGraph / Twitter)
                                                </label>
                                                <ImageUploader
                                                    v-model="form.seo_og_image"
                                                    directorio="seo"
                                                    ayuda="Imagen en miniatura para compartir en WhatsApp, Facebook y Twitter (1200x630 recomendado)."
                                                />
                                            </div>

                                            <div class="md:col-span-2 pt-3 border-t border-zinc-100 dark:border-zinc-800 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                                                        Indexación en Buscadores
                                                    </label>
                                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="seoNoIndex ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400'">
                                                        {{ seoNoIndex ? 'No indexar' : 'Indexable (estándar)' }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-zinc-500">Por defecto el artículo se indexa en Google. Activa solo si deseas restringirlo.</p>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                                        <div class="text-xs pr-3">
                                                            <label for="article_edit_seo_noindex" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                                Bloquear Indexación (<code class="font-mono text-amber-600 dark:text-amber-400">noindex</code>)
                                                            </label>
                                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">Oculta este artículo de los resultados de búsqueda.</p>
                                                        </div>
                                                        <ToggleSwitch v-model="seoNoIndex" id="article_edit_seo_noindex" />
                                                    </div>

                                                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                                        <div class="text-xs pr-3">
                                                            <label for="article_edit_seo_nofollow" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                                No Seguir Enlaces (<code class="font-mono text-amber-600 dark:text-amber-400">nofollow</code>)
                                                            </label>
                                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">Indica a los buscadores no rastrear los enlaces de este artículo.</p>
                                                        </div>
                                                        <ToggleSwitch v-model="seoNoFollow" id="article_edit_seo_nofollow" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </TabPanel>
                                </TabPanels>
                            </Tabs>
                        </div>

                    </div>

                    <!-- ─────────────────────────────────────────── -->
                    <!-- COLUMNA DERECHA: BARRA LATERAL STICKY (4/3 cols) -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="lg:col-span-4 2xl:col-span-3 space-y-5 lg:sticky lg:top-20">

                        <!-- ═══ PANEL 1: IMAGEN DESTACADA E ICONO (¡EN PRIMER PLANO!) ═══ -->
                        <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/70 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                        <i class="pi pi-image text-xs" />
                                    </div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                        Imagen Destacada
                                    </h3>
                                </div>
                                <span class="text-[10px] font-semibold text-zinc-400">Principal</span>
                            </div>

                            <div class="space-y-3.5">
                                <div>
                                    <ImageUploader
                                        v-model="form.featured_image"
                                        directorio="articulos"
                                        ayuda="Imagen visible en listados, tarjetas y cabecera."
                                    />
                                </div>

                                <div class="pt-2.5 border-t border-zinc-100 dark:border-zinc-800">
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1.5">
                                        Icono Representativo
                                    </label>
                                    <IconPicker
                                        v-model="form.icon"
                                        label="Icono"
                                        placeholder="Seleccionar icono..."
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- ═══ PANEL 2: PUBLICACIÓN Y ESTADO ═══ -->
                        <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/70 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                        <i class="pi pi-globe text-xs" />
                                    </div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                        Publicación
                                    </h3>
                                </div>
                                <Tag
                                    :value="estadoBadge.label"
                                    :severity="estadoBadge.severity"
                                    :icon="estadoBadge.icon"
                                    class="text-[10px] px-2 py-0.5"
                                />
                            </div>

                            <div class="space-y-3.5">
                                <!-- Categoría del Artículo -->
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Categoría *
                                    </label>
                                    <Select
                                        v-model="form.category_id"
                                        :options="categorias"
                                        optionLabel="name"
                                        optionValue="id"
                                        :filter="true"
                                        filterPlaceholder="Buscar..."
                                        placeholder="— Seleccionar categoría —"
                                        class="w-full !text-xs"
                                    />
                                    <p v-if="form.errors.category_id" class="text-red-500 text-[11px] mt-1">{{ form.errors.category_id }}</p>
                                </div>

                                <!-- Plantilla Visual del Builder -->
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Plantilla Builder
                                    </label>
                                    <Select
                                        v-model="modeloTemplateArticulo"
                                        :options="opcionesPlantillasArticulo"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full !text-xs"
                                    />
                                </div>

                                <!-- Estado de Publicación -->
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Estado *
                                    </label>
                                    <Select
                                        v-model="form.status"
                                        :options="opcionesEstados"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full !text-xs"
                                    />
                                </div>

                                <!-- Destacado en Portada -->
                                <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-star-fill text-amber-500 text-xs" />
                                        <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">Destacar en portada</span>
                                    </div>
                                    <ToggleSwitch v-model="form.is_featured" />
                                </div>

                                <!-- Fecha de Publicación -->
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Fecha de Publicación *
                                    </label>
                                    <input
                                        v-model="form.published_at"
                                        type="datetime-local"
                                        class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors"
                                    />
                                </div>

                                <!-- Finalización / Caducidad -->
                                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                                            Finalización
                                        </label>
                                        <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                            <button
                                                type="button"
                                                @click="finalizacionIndefinida = true"
                                                class="px-2.5 py-1 rounded-md text-[10px] font-medium transition-all"
                                                :class="finalizacionIndefinida ? 'bg-white dark:bg-zinc-700 text-emerald-700 dark:text-emerald-300 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400'"
                                            >
                                                Indefinida
                                            </button>
                                            <button
                                                type="button"
                                                @click="finalizacionIndefinida = false"
                                                class="px-2.5 py-1 rounded-md text-[10px] font-medium transition-all"
                                                :class="!finalizacionIndefinida ? 'bg-white dark:bg-zinc-700 text-blue-700 dark:text-blue-300 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400'"
                                            >
                                                Con fecha
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="!finalizacionIndefinida">
                                        <input
                                            v-model="form.published_until"
                                            type="datetime-local"
                                            :min="form.published_at || undefined"
                                            class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors"
                                        />
                                        <p v-if="esFechaFinInvalida" class="text-[10px] text-red-600 dark:text-red-400 font-medium mt-1">
                                            Debe ser posterior a la fecha de publicación.
                                        </p>
                                    </div>
                                    <div v-else class="text-[10px] text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800/40 p-2.5 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 flex items-center gap-1.5">
                                        <i class="pi pi-infinity text-xs text-emerald-500 shrink-0" />
                                        <span>Sin caducidad (publicación permanente).</span>
                                    </div>
                                </div>

                                <!-- Avisos contextuales -->
                                <div v-if="form.status === 'scheduled' || (esFechaFutura && form.status === 'published')" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-[11px] text-amber-700 dark:text-amber-300 flex items-start gap-2">
                                    <i class="pi pi-clock text-amber-500 text-xs mt-0.5 shrink-0" />
                                    <span>Se publicará automáticamente al llegar la fecha indicada.</span>
                                </div>
                                <div v-else-if="esFechaFinExpirada && form.status === 'published'" class="p-3 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-[11px] text-red-700 dark:text-red-300 flex items-start gap-2">
                                    <i class="pi pi-exclamation-circle text-red-500 text-xs mt-0.5 shrink-0" />
                                    <span>Artículo expirado: no visible públicamente.</span>
                                </div>
                            </div>
                        </section>

                        <!-- ═══ PANEL 3: OPCIONES DE TARJETA (GRID) ═══ -->
                        <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 p-5 shadow-xs space-y-3.5">
                            <div class="flex items-center gap-2.5 pb-2.5 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="w-7 h-7 rounded-lg bg-teal-100 dark:bg-teal-950/70 flex items-center justify-center text-teal-600 dark:text-teal-400">
                                    <i class="pi pi-th-large text-xs" />
                                </div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                    Opciones de Tarjeta
                                </h3>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60">
                                    <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">Tarjeta Clickable</span>
                                    <ToggleSwitch v-model="form.card_full_link" />
                                </div>

                                <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60">
                                    <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">Botón "Leer Más"</span>
                                    <ToggleSwitch v-model="form.show_read_more" />
                                </div>

                                <div v-if="form.show_read_more" class="pt-1">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                        Texto del Botón
                                    </label>
                                    <InputText v-model="form.read_more_text" class="w-full !text-xs !py-1.5" placeholder="Leer más" />
                                </div>
                            </div>
                        </section>

                    </div>

                </div>

                <!-- ═══════════════════════════════════════════════ -->
                <!-- BARRA DE ACCIÓN INFERIOR                         -->
                <!-- ═══════════════════════════════════════════════ -->
                <div class="flex items-center justify-between pt-5 border-t border-zinc-200 dark:border-zinc-800">
                    <span class="text-xs text-zinc-400">
                        Última modificación guardada para este artículo.
                    </span>

                    <div class="flex items-center gap-3">
                        <Link
                            :href="urlRetorno"
                            class="px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-semibold rounded-xl transition-colors"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow-emerald-500/20 inline-flex items-center gap-2 cursor-pointer"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                            <i v-else class="pi pi-check text-xs" />
                            <span>Guardar Cambios</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</AuthenticatedLayout>
</template>
