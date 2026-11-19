<script setup>
/**
 * Panel principal del Módulo Autónomo de Descargas (Backend).
 *
 * Permite subir, buscar, filtrar, cambiar estado inline, reordenar mediante Drag & Drop (`vuedraggable`),
 * consultar métricas diferidas (`Deferred`) y configurar el título, descripción, icono y diseño (Grid/Listado)
 * de la página de Descargas.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import DownloadSettingsDialog from '@/Pages/Descargas/Partials/DownloadSettingsDialog.vue';
import { Head, Link, router, Deferred } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';

import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const confirm = useConfirm();

const props = defineProps({
    descargas: { type: Object, required: true },
    configuracion: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    conteoPapelera: { type: Number, default: 0 },
    estadosDisponibles: { type: Object, default: () => ({}) },
    tiposAccesoDisponibles: { type: Object, default: () => ({}) },
    estadisticas: { type: Object, default: undefined },
});

const mostrarAjustesDialog = ref(false);

// Lista reactiva para reordenamiento Drag & Drop
const listaDescargas = ref([...(props.descargas.data || [])]);
watch(
    () => props.descargas.data,
    (val) => {
        listaDescargas.value = [...(val || [])];
    },
    { deep: true }
);

// Filtros reactivos siguiendo estrictamente la Regla PrimeVue 4 Selects (nunca '')
const busqueda = ref(props.filtros.search || '');
const estadoFiltro = ref(props.filtros.status || 'all');
const accesoFiltro = ref(props.filtros.access_type || 'all');
const sortBy = ref(props.filtros.sort_by || '');
const sortDir = ref(props.filtros.sort_dir || '');

const opcionesEstados = computed(() => [
    { label: 'Todos los estados', value: 'all' },
    ...Object.entries(props.estadosDisponibles || {}).map(([value, label]) => ({ label, value })),
]);

const opcionesAcceso = computed(() => [
    { label: 'Todos los accesos', value: 'all' },
    ...Object.entries(props.tiposAccesoDisponibles || {}).map(([value, label]) => ({ label, value })),
]);

const hayFiltrosActivos = computed(() =>
    Boolean(
        busqueda.value ||
        (estadoFiltro.value && estadoFiltro.value !== 'all') ||
        (accesoFiltro.value && accesoFiltro.value !== 'all') ||
        sortBy.value
    )
);

// El Drag & Drop está activo siempre que estemos viendo el catálogo plano sin filtros ni orden por columna
const puedeReordenar = computed(() => !hayFiltrosActivos.value);

function aplicarFiltros(extra = {}) {
    const params = {
        search: busqueda.value || undefined,
        status: estadoFiltro.value !== 'all' ? estadoFiltro.value : undefined,
        access_type: accesoFiltro.value !== 'all' ? accesoFiltro.value : undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value || undefined,
        ...extra,
    };

    router.get(route('downloads.admin.index'), params, { preserveState: true, replace: true });
}

let debounceTimer = null;
watch(busqueda, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => aplicarFiltros(), 300);
});

function limpiarFiltros() {
    busqueda.value = '';
    estadoFiltro.value = 'all';
    accesoFiltro.value = 'all';
    sortBy.value = '';
    sortDir.value = '';
    router.get(route('downloads.admin.index'), {}, { replace: true });
}

function guardarNuevoOrden() {
    if (!puedeReordenar.value) return;

    router.post(
        route('downloads.admin.reorder'),
        {
            ids: listaDescargas.value.map(item => item.id),
            page: props.descargas.meta?.current_page || props.descargas.current_page || 1,
            per_page: props.descargas.meta?.per_page || props.descargas.per_page || 20,
        },
        { preserveScroll: true, preserveState: true }
    );
}

function alternarEstadoRapido(item) {
    const nuevoEstado = item.status === 'published' ? 'draft' : 'published';
    router.patch(
        route('downloads.admin.change-status', item.id),
        { status: nuevoEstado },
        { preserveScroll: true }
    );
}

function duplicarDescarga(item) {
    router.post(route('downloads.admin.duplicate', item.id), {}, { preserveScroll: true });
}

function confirmarPapelera(item) {
    confirm.require({
        message: `¿Enviar la descarga "${item.title}" a la papelera? Su archivo privado se conservará por si deseas restaurarla.`,
        header: 'Enviar a la papelera',
        icon: 'pi pi-trash',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, enviar a papelera',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('downloads.admin.destroy', item.id), { preserveScroll: true }),
    });
}

function formatearBytesTotales(bytes) {
    const n = Number(bytes || 0);
    if (n <= 0) return '0 MB';
    const mb = n / (1024 * 1024);
    if (mb >= 1024) return `${(mb / 1024).toFixed(2)} GB`;
    return `${mb.toFixed(2)} MB`;
}

const linksPaginacion = computed(() => props.descargas.meta?.links || props.descargas.links || []);
</script>

<template>
    <Head title="Descargas — Panel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Gestión de Descargas
                    </h1>
                </div>

                <!-- Conmutador Catálogo / Papelera en Topbar (Solo Desktop md+) -->
                <div class="hidden md:flex items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs shrink-0">
                    <Link
                        :href="route('downloads.admin.index')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm no-underline"
                    >
                        <i class="pi pi-download text-xs" />
                        <span>Catálogo Activo</span>
                    </Link>
                    <Link
                        :href="route('downloads.admin.trashed')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 no-underline"
                    >
                        <i class="pi pi-trash text-xs" />
                        <span>Papelera</span>
                        <span v-if="conteoPapelera > 0" class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                            {{ conteoPapelera }}
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 w-full space-y-6">
            <!-- Conmutador Catálogo / Papelera para Móvil (< md) -->
            <div class="flex md:hidden items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs w-full">
                <Link
                    :href="route('downloads.admin.index')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm text-center no-underline"
                >
                    <i class="pi pi-download text-xs" />
                    <span>Catálogo Activo</span>
                </Link>
                <Link
                    :href="route('downloads.admin.trashed')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 text-center no-underline"
                >
                    <i class="pi pi-trash text-xs" />
                    <span>Papelera</span>
                    <span v-if="conteoPapelera > 0" class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                        {{ conteoPapelera }}
                    </span>
                </Link>
            </div>

            <!-- Cabecera contextual de sección con acciones responsivas -->
            <PageHeader
                :icon="configuracion.icon || 'pi pi-download'"
                :title="configuracion.page_title || 'Descargas'"
                description="Administra tus recursos descargables, plantillas visuales y métricas del catálogo"
                divider
            >
                <template #badge>
                    <Tag
                        :value="configuracion.is_strictly_public ? 'Público' : 'Solo Backend (Privado)'"
                        :severity="configuracion.is_strictly_public ? 'success' : 'warn'"
                    />
                </template>

                <template #actions>
                    <a
                        :href="configuracion.public_url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors no-underline"
                    >
                        <i class="pi pi-eye text-xs" />
                        <span>{{ configuracion.is_strictly_public ? 'Ver Catálogo' : 'Vista Previa' }}</span>
                    </a>

                    <Link v-if="route().has('actividad.index')" :href="route('actividad.index', { tab: 'descargas' })">
                        <Button
                            label="Historial e IPs"
                            icon="pi pi-globe"
                            severity="secondary"
                            outlined
                            size="small"
                            v-tooltip.bottom="'Ver registro de IPs de descargas, ranking histórico y limpieza de logs'"
                        />
                    </Link>

                    <Button
                        label="Diseño y Tamaños"
                        icon="pi pi-sliders-h"
                        severity="secondary"
                        size="small"
                        @click="mostrarAjustesDialog = true"
                    />

                    <Link :href="route('downloads.admin.builder')">
                        <Button
                            label="Maquetar Portada"
                            icon="pi pi-palette"
                            severity="contrast"
                            size="small"
                            v-tooltip.bottom="'Abrir Portada de Descargas en el Page Builder'"
                        />
                    </Link>

                    <Link :href="route('downloads.admin.item-builder')">
                        <Button
                            label="Maquetar Ficha Ítem"
                            icon="pi pi-objects-column"
                            severity="secondary"
                            outlined
                            size="small"
                            v-tooltip.bottom="'Diseñar la plantilla visual de cada Ficha de Descarga en el Builder'"
                        />
                    </Link>

                    <Link :href="route('downloads.admin.create')">
                        <Button label="Nueva Descarga" icon="pi pi-plus" severity="emerald" size="small" />
                    </Link>
                </template>
            </PageHeader>
            <!-- Aviso de estado privado en Backend -->
            <div
                v-if="!configuracion.is_strictly_public"
                class="rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/80 dark:bg-amber-950/30 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div class="flex items-start gap-3">
                    <i class="pi pi-lock text-amber-600 dark:text-amber-400 text-lg mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-amber-950 dark:text-amber-200">
                            Modo Visibilidad Solo Backend activo
                        </p>
                        <p class="text-xs text-amber-800 dark:text-amber-300/90 mt-0.5">
                            Puedes subir, ordenar y maquetar tus plugins de YOOtheme con total tranquilidad. Los visitantes anónimos reciben <code>404</code> hasta que cambies la visibilidad a Público en Ajustes de Página.
                        </p>
                    </div>
                </div>
                <Button
                    label="Configurar Visibilidad / Diseño"
                    icon="pi pi-cog"
                    severity="warn"
                    size="small"
                    outlined
                    class="shrink-0"
                    @click="mostrarAjustesDialog = true"
                />
            </div>

            <!-- Métricas de Backend cargadas de forma diferida (Inertia::defer) -->
            <Deferred data="estadisticas">
                <template #fallback>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="n in 4" :key="n" class="h-20 rounded-2xl bg-zinc-100 dark:bg-zinc-800/60 animate-pulse border border-zinc-200/60 dark:border-zinc-800" />
                    </div>
                </template>

                <div v-if="estadisticas" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-2xs">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Recursos activos</span>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">
                            {{ estadisticas.published_items }} <span class="text-xs font-normal text-zinc-400">/ {{ estadisticas.total_items }}</span>
                        </p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-2xs">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Descargas totales</span>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ estadisticas.total_downloads }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-2xs">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Últimos 30 días</span>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ estadisticas.downloads_last_30d }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-2xs" title="Suma del tamaño que ocupan en el servidor todos los archivos descargables subidos">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Peso total en disco</span>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ formatearBytesTotales(estadisticas.total_vault_bytes) }}</p>
                    </div>
                </div>
            </Deferred>

            <!-- Barra de Búsqueda y Filtros -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <IconField class="w-full sm:max-w-xs">
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="busqueda"
                            placeholder="Buscar plugin, versión..."
                            class="w-full text-sm"
                        />
                    </IconField>

                    <Select
                        v-model="estadoFiltro"
                        :options="opcionesEstados"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full sm:w-48 text-sm"
                        @change="aplicarFiltros()"
                    />

                    <Select
                        v-model="accesoFiltro"
                        :options="opcionesAcceso"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full sm:w-56 text-sm"
                        @change="aplicarFiltros()"
                    />

                    <Button
                        v-if="hayFiltrosActivos"
                        label="Limpiar"
                        icon="pi pi-filter-slash"
                        severity="secondary"
                        text
                        size="small"
                        @click="limpiarFiltros"
                    />
                </div>

                <div class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-2 shrink-0">
                    <i class="pi pi-arrows-v text-emerald-600 dark:text-emerald-400" />
                    <span>{{ puedeReordenar ? 'Arrastra las filas para ordenar el catálogo' : 'Limpia filtros para reordenar manualmente' }}</span>
                </div>
            </div>

            <!-- Tabla / Lista Drag & Drop -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div v-if="listaDescargas.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-800/40 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <th class="w-10 px-3 py-3 text-center">#</th>
                                <th class="px-4 py-3">Recurso / Plugin</th>
                                <th class="px-4 py-3 hidden md:table-cell">Versión / Paquete</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Acceso</th>
                                <th class="px-4 py-3 text-center">Descargas</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <draggable
                            v-model="listaDescargas"
                            tag="tbody"
                            item-key="id"
                            handle=".dl-drag-handle"
                            :disabled="!puedeReordenar"
                            animation="200"
                            ghost-class="opacity-40"
                            class="divide-y divide-zinc-100 dark:divide-zinc-800/80"
                            @end="guardarNuevoOrden"
                        >
                            <template #item="{ element: item }">
                                <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors">
                                    <!-- Handle Drag & Drop -->
                                    <td class="px-3 py-3.5 text-center">
                                        <button
                                            type="button"
                                            :disabled="!puedeReordenar"
                                            :class="[
                                                'dl-drag-handle p-1.5 rounded-lg transition-colors',
                                                puedeReordenar
                                                    ? 'cursor-grab active:cursor-grabbing text-zinc-400 hover:text-emerald-600 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                                    : 'cursor-not-allowed text-zinc-200 dark:text-zinc-700',
                                            ]"
                                            title="Arrastrar para cambiar orden"
                                        >
                                            <i class="pi pi-bars text-xs" />
                                        </button>
                                    </td>

                                    <!-- Imagen, Título y Compatibilidad -->
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-3.5 min-w-0">
                                            <div class="w-14 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/70 dark:border-zinc-700 overflow-hidden flex items-center justify-center shrink-0">
                                                <img
                                                    v-if="item.featured_image"
                                                    :src="item.featured_image"
                                                    :alt="item.title"
                                                    :class="[
                                                        'w-full h-full',
                                                        String(item.featured_image).toLowerCase().endsWith('.svg')
                                                            ? 'object-contain p-1 dark:invert dark:opacity-90'
                                                            : 'object-cover',
                                                    ]"
                                                />
                                                <i v-else :class="[item.icon || 'pi pi-box', 'text-zinc-400 text-base']" />
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <Link
                                                        :href="route('downloads.admin.edit', item.id)"
                                                        class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors truncate no-underline"
                                                    >
                                                        {{ item.title }}
                                                    </Link>
                                                    <i v-if="item.is_featured" class="pi pi-star-fill text-amber-400 text-xs" title="Destacado" />
                                                </div>
                                                <small v-if="item.subtitle" class="block text-xs font-medium text-emerald-700 dark:text-emerald-400 truncate">
                                                    {{ item.subtitle }}
                                                </small>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1 mt-0.5 max-w-xl">
                                                    {{ item.short_description || item.compatibility || `/${configuracion.slug}/${item.slug}` }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Versión y Archivo Privado -->
                                    <td class="px-4 py-3.5 hidden md:table-cell">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <span v-if="item.version" class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-xs font-mono font-semibold text-zinc-700 dark:text-zinc-300">
                                                    v{{ item.version }}
                                                </span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ item.formatted_file_size }}</span>
                                            </div>
                                            <span v-if="!item.has_file" class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">
                                                Sin archivo ZIP subido
                                            </span>
                                            <span v-else class="text-[11px] text-zinc-400 font-mono truncate max-w-[190px]">
                                                {{ item.original_filename }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Acceso / Precio -->
                                    <td class="px-4 py-3.5 hidden sm:table-cell">
                                        <Tag
                                            :value="item.formatted_price"
                                            :severity="item.access_type === 'paid' ? 'info' : 'secondary'"
                                        />
                                    </td>

                                    <!-- Contador de descargas en Backend -->
                                    <td class="px-4 py-3.5 text-center font-mono text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ item.downloads_count }}
                                    </td>

                                    <!-- Cambio rápido de Estado -->
                                    <td class="px-4 py-3.5 text-center">
                                        <button
                                            type="button"
                                            @click="alternarEstadoRapido(item)"
                                            class="cursor-pointer bg-transparent border-0 p-0"
                                            title="Clic para alternar Publicado / Borrador"
                                        >
                                            <Tag
                                                :value="item.status === 'published' ? 'Publicado' : 'Borrador'"
                                                :severity="item.status === 'published' ? 'success' : 'secondary'"
                                            />
                                        </button>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <a
                                                v-if="item.download_request_url"
                                                :href="item.download_request_url"
                                                class="p-2 rounded-lg text-zinc-500 hover:text-emerald-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                                                title="Descargar paquete (Prueba segura)"
                                            >
                                                <i class="pi pi-download text-xs" />
                                            </a>
                                            <Link
                                                :href="route('downloads.admin.edit', item.id)"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 inline-flex items-center gap-1.5 transition-colors no-underline"
                                                title="Editar título, descripción, imagen y paquete"
                                            >
                                                <i class="pi pi-pencil text-[11px]" />
                                                <span>Editar</span>
                                            </Link>
                                            <button
                                                type="button"
                                                @click="duplicarDescarga(item)"
                                                class="p-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                                title="Duplicar ficha"
                                            >
                                                <i class="pi pi-copy text-xs" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmarPapelera(item)"
                                                class="p-2 rounded-lg text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors cursor-pointer"
                                                title="Enviar a la papelera"
                                            >
                                                <i class="pi pi-trash text-xs" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </draggable>
                    </table>
                </div>

                <!-- Estado vacío -->
                <div v-else class="py-16 px-6 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="pi pi-download text-2xl" />
                    </div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                        Todavía no hay descargas registradas
                    </h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md mx-auto mt-1 mb-5">
                        Sube tu primer archivo descargable (.zip) y maqueta su ficha de presentación.
                    </p>
                    <Link :href="route('downloads.admin.create')">
                        <Button label="Subir Primera Descarga" icon="pi pi-plus" severity="emerald" />
                    </Link>
                </div>
            </div>

            <!-- Paginación administrativa -->
            <div v-if="linksPaginacion.length > 3" class="flex justify-center gap-1.5 flex-wrap">
                <template v-for="(link, idx) in linksPaginacion" :key="idx">
                    <span
                        v-if="link.active"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-emerald-600 text-white"
                        v-html="link.label"
                    />
                    <Link
                        v-else-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 no-underline"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Modal de Configuración de Página de Descargas -->
        <DownloadSettingsDialog
            v-model:visible="mostrarAjustesDialog"
            :configuracion="configuracion"
        />
    </AuthenticatedLayout>
</template>
