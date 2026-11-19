<script setup>
/**
 * Listado paginado de artículos del CMS con cambio rápido de estado inline y reordenamiento Drag & Drop.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { useListAutoExpire } from '@/Composables/useAutoExpire';

// Componentes PrimeVue
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Button from 'primevue/button';

const confirm = useConfirm();

const props = defineProps({
    articulos: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    categorias: { type: [Array, Object], default: () => [] },
    conteoPapelera: { type: Number, default: 0 },
    estadosArticulo: { type: Object, default: () => ({}) },
});

// Auto-actualización reactiva en tiempo real en la pantalla del administrador (Zero Polling)
// Al llegar la hora exacta de inicio o fin, refresca la tabla y actualiza los badges de estado automáticamente
useListAutoExpire(
    () => props.articulos?.data || [],
    () => {
        router.reload({ preserveScroll: true });
    }
);

// Lista local reactiva para Drag & Drop
const listaArticulos = ref([...(props.articulos.data || [])]);

watch(() => props.articulos.data, (val) => {
    listaArticulos.value = [...(val || [])];
}, { deep: true });

// Ordenación por columnas (solo visual en el panel administrativo, no altera 'order' ni el frontend)
const sortBy = ref(props.filtros.sort_by || '');
const sortDir = ref(props.filtros.sort_dir || '');

// El reordenamiento manual por arrastre solo tiene sentido dentro de una categoría concreta
// y cuando no hay ninguna ordenación por columnas activa (modo orden manual original).
const puedeReordenar = computed(() => {
    const hayCategoria = categoriaFiltro.value && categoriaFiltro.value !== 'all';
    const sinOrdenColumna = !sortBy.value;
    return Boolean(hayCategoria && sinOrdenColumna);
});

const tooltipReordenar = computed(() => {
    if (sortBy.value) {
        return 'Reordenamiento manual pausado mientras esté activa una ordenación por columna. Haz clic en restablecer para volver al orden manual.';
    }
    if (!categoriaFiltro.value || categoriaFiltro.value === 'all') {
        return 'Filtra por una categoría específica para habilitar el reorden manual por arrastre.';
    }
    return 'Arrastrar para reordenar artículos en esta categoría';
});

function guardarNuevoOrden() {
    if (!puedeReordenar.value) return;
    router.post(
        route('articulos.reorder'),
        {
            ids: listaArticulos.value.map(a => a.id),
            category_id: Number(categoriaFiltro.value),
            page: props.articulos.meta?.current_page || props.articulos.current_page || 1,
            per_page: props.articulos.meta?.per_page || props.articulos.per_page || 12,
        },
        { preserveScroll: true, preserveState: true }
    );
}

// Filtros reactivos (conforme a la regla PrimeVue 4: usar 'all' en lugar de cadena vacía)
const busqueda = ref(props.filtros.search || '');
const estadoFiltro = ref(props.filtros.status || 'all');
const categoriaFiltro = ref(props.filtros.category_id ? String(props.filtros.category_id) : 'all');

const opcionesEstados = computed(() => [
    { label: 'Todos los estados', value: 'all' },
    ...Object.entries(props.estadosArticulo || {}).map(([key, label]) => ({ label, value: key })),
]);

const opcionesCategorias = computed(() => {
    const lista = Array.isArray(props.categorias) ? props.categorias : (props.categorias?.data || []);
    return [
        { label: 'Todas las categorías', value: 'all' },
        ...lista.map(c => ({ label: c.name, value: String(c.id) })),
    ];
});

// Helper para extraer los parámetros de filtro y paginación activos
function obtenerParamsActivos() {
    const params = {};
    if (busqueda.value) params.search = busqueda.value;
    if (estadoFiltro.value && estadoFiltro.value !== 'all') params.status = estadoFiltro.value;
    if (categoriaFiltro.value && categoriaFiltro.value !== 'all') params.category_id = categoriaFiltro.value;
    if (sortBy.value) params.sort_by = sortBy.value;
    if (sortDir.value) params.sort_dir = sortDir.value;
    const pag = props.articulos.meta?.current_page || props.articulos.current_page;
    if (pag && pag > 1) params.page = pag;
    return params;
}

const hayFiltrosActivos = computed(() => {
    return Boolean(
        busqueda.value ||
        (estadoFiltro.value && estadoFiltro.value !== 'all') ||
        (categoriaFiltro.value && categoriaFiltro.value !== 'all') ||
        sortBy.value
    );
});

function limpiarFiltros() {
    busqueda.value = '';
    estadoFiltro.value = 'all';
    categoriaFiltro.value = 'all';
    sortBy.value = '';
    sortDir.value = '';
    router.get(route('articulos.index'), {}, { replace: true });
}

let timeout = null;
watch(busqueda, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => aplicarFiltros(), 300);
});

function aplicarFiltros(extraParams = {}) {
    const params = {
        search: busqueda.value || undefined,
        status: estadoFiltro.value !== 'all' ? estadoFiltro.value : undefined,
        category_id: categoriaFiltro.value !== 'all' ? categoriaFiltro.value : undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value || undefined,
        ...extraParams,
    };

    router.get(route('articulos.index'), params, { preserveState: true, replace: true });
}

function alternarOrden(columna) {
    if (sortBy.value === columna) {
        if (sortDir.value === 'asc') {
            sortDir.value = 'desc';
        } else if (sortDir.value === 'desc') {
            // Tercer clic: restablece al orden por defecto
            sortBy.value = '';
            sortDir.value = '';
        }
    } else {
        sortBy.value = columna;
        // La fecha se ordena descendente primero; las demás columnas ascendente
        sortDir.value = (columna === 'published_at') ? 'desc' : 'asc';
    }
    aplicarFiltros();
}

function restablecerOrdenManual() {
    sortBy.value = '';
    sortDir.value = '';
    aplicarFiltros();
}

function iconoOrden(columna) {
    if (sortBy.value !== columna) {
        return 'pi pi-sort text-zinc-300 dark:text-zinc-600 group-hover/th:text-zinc-400';
    }
    return sortDir.value === 'asc'
        ? 'pi pi-sort-amount-up-alt text-emerald-600 dark:text-emerald-400 font-bold'
        : 'pi pi-sort-amount-down text-emerald-600 dark:text-emerald-400 font-bold';
}

function etiquetaColumnaOrden(col) {
    const labels = {
        title: 'Título',
        category: 'Categoría',
        status: 'Estado',
        author: 'Autor',
        published_at: 'Fecha',
    };
    return labels[col] || col;
}

// Metadatos y enlaces de paginación blindados ante formato Resource ({ data, meta, links })
const totalPaginas = computed(() => props.articulos.meta?.last_page || props.articulos.last_page || 1);
const paginacionLinks = computed(() => props.articulos.meta?.links || props.articulos.links || []);
const totalRegistros = computed(() => props.articulos.meta?.total ?? props.articulos.total ?? listaArticulos.value.length);
const desdeRegistro = computed(() => props.articulos.meta?.from ?? props.articulos.from ?? (listaArticulos.value.length ? 1 : 0));
const hastaRegistro = computed(() => props.articulos.meta?.to ?? props.articulos.to ?? listaArticulos.value.length);

function cambiarEstadoArticulo(articulo, nuevoEstado) {
    router.patch(
        route('articulos.change-status', articulo.id),
        { status: nuevoEstado },
        { preserveScroll: true }
    );
}

function irAEditar(articulo) {
    if (!articulo?.id) return;
    router.get(route('articulos.edit', { article: articulo.id, ...obtenerParamsActivos() }));
}

function duplicar(articulo) {
    confirm.require({
        message: `¿Duplicar "${articulo.title}" como nuevo artículo despublicado?`,
        header: 'Duplicar artículo',
        icon: 'pi pi-copy',
        acceptClass: 'p-button-emerald',
        accept: () => router.post(route('articulos.duplicate', articulo.id)),
    });
}

function eliminar(articulo) {
    confirm.require({
        message: `¿Mover "${articulo.title}" a la papelera?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('articulos.destroy', articulo.id)),
    });
}
</script>

<template>
    <Head title="Artículos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Gestión de Artículos
                    </h1>
                </div>

                <!-- Pestañas Activos / Papelera en el Header (Solo Desktop md+) -->
                <div class="hidden md:flex items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs shrink-0">
                    <Link
                        :href="route('articulos.index')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm"
                    >
                        <i class="pi pi-file text-xs" />
                        <span>Artículos Activos</span>
                    </Link>
                    <Link
                        :href="route('articulos.trashed')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"
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
            <!-- Pestañas Activos / Papelera para Móvil (< md) -->
            <div class="flex md:hidden items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs w-full">
                <Link
                    :href="route('articulos.index')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm text-center"
                >
                    <i class="pi pi-file text-xs" />
                    <span>Artículos Activos</span>
                </Link>
                <Link
                    :href="route('articulos.trashed')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 text-center"
                >
                    <i class="pi pi-trash text-xs" />
                    <span>Papelera</span>
                    <span v-if="conteoPapelera > 0" class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                        {{ conteoPapelera }}
                    </span>
                </Link>
            </div>
            <div class="flex items-center justify-between gap-4 pb-2 border-b border-zinc-200 dark:border-zinc-800">
                <div>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Listado de Artículos</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Administra las publicaciones de tu CMS</p>
                </div>
                <Link :href="route('articulos.create', obtenerParamsActivos())">
                    <Button
                        label="Nuevo Artículo"
                        icon="pi pi-plus"
                        severity="emerald"
                    />
                </Link>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-center gap-3 p-4 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <IconField class="w-full sm:w-72 md:w-80">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="busqueda"
                        type="search"
                        placeholder="Buscar por título..."
                        class="w-full text-sm"
                    />
                </IconField>

                <div class="w-full sm:w-48">
                    <Select
                        v-model="estadoFiltro"
                        :options="opcionesEstados"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Todos los estados"
                        class="w-full"
                        @change="() => aplicarFiltros()"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <Select
                        v-model="categoriaFiltro"
                        :options="opcionesCategorias"
                        optionLabel="label"
                        optionValue="value"
                        filter
                        placeholder="Todas las categorías"
                        class="w-full"
                        @change="() => aplicarFiltros()"
                    />
                </div>

                <!-- Botón de Limpiar Filtros cuando hay filtros activos -->
                <Button
                    v-if="hayFiltrosActivos"
                    label="Limpiar filtros"
                    icon="pi pi-filter-slash"
                    severity="secondary"
                    variant="text"
                    size="small"
                    class="text-xs"
                    @click="limpiarFiltros"
                />
            </div>

            <!-- Aviso visual cuando la ordenación por columnas está activa -->
            <div v-if="sortBy" class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5 bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs text-emerald-900 dark:text-emerald-200 shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="pi pi-info-circle text-emerald-600 dark:text-emerald-400 text-sm shrink-0" />
                    <span>
                        Ordenado visualmente por <strong>{{ etiquetaColumnaOrden(sortBy) }}</strong> ({{ sortDir === 'asc' ? 'ascendente' : 'descendente' }}). <span class="hidden sm:inline text-emerald-800/80 dark:text-emerald-300/80">Esta ordenación es exclusiva para el panel de administración y no altera el orden del frontend.</span>
                    </span>
                </div>
                <button
                    type="button"
                    @click="restablecerOrdenManual"
                    class="inline-flex items-center gap-1.5 font-medium px-2.5 py-1 rounded-lg bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/60 dark:hover:bg-emerald-800/60 text-emerald-800 dark:text-emerald-200 transition-colors cursor-pointer"
                >
                    <i class="pi pi-refresh text-xs" />
                    <span>Restablecer orden manual</span>
                </button>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="w-10 text-center px-3 py-3.5" :title="tooltipReordenar">
                                <i :class="['pi pi-arrows-v text-xs', puedeReordenar ? 'text-zinc-500 dark:text-zinc-300' : 'opacity-30']" />
                            </th>
                            <th
                                class="text-left px-5 py-3.5 font-medium cursor-pointer select-none group/th hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                @click="alternarOrden('title')"
                                title="Haga clic para ordenar por título"
                            >
                                <div class="inline-flex items-center gap-1.5">
                                    <span>Título</span>
                                    <i :class="iconoOrden('title')" class="text-xs transition-colors" />
                                </div>
                            </th>
                            <th
                                class="text-left px-5 py-3.5 font-medium cursor-pointer select-none group/th hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                @click="alternarOrden('category')"
                                title="Haga clic para ordenar por categoría"
                            >
                                <div class="inline-flex items-center gap-1.5">
                                    <span>Categoría</span>
                                    <i :class="iconoOrden('category')" class="text-xs transition-colors" />
                                </div>
                            </th>
                            <th
                                class="text-center px-5 py-3.5 font-medium cursor-pointer select-none group/th hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                @click="alternarOrden('status')"
                                title="Haga clic para ordenar por estado"
                            >
                                <div class="inline-flex items-center gap-1.5 justify-center">
                                    <span>Estado</span>
                                    <i :class="iconoOrden('status')" class="text-xs transition-colors" />
                                </div>
                            </th>
                            <th
                                class="text-left px-5 py-3.5 font-medium cursor-pointer select-none group/th hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                @click="alternarOrden('author')"
                                title="Haga clic para ordenar por autor"
                            >
                                <div class="inline-flex items-center gap-1.5">
                                    <span>Autor</span>
                                    <i :class="iconoOrden('author')" class="text-xs transition-colors" />
                                </div>
                            </th>
                            <th
                                class="text-left px-5 py-3.5 font-medium cursor-pointer select-none group/th hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                @click="alternarOrden('published_at')"
                                title="Haga clic para ordenar por fecha de publicación"
                            >
                                <div class="inline-flex items-center gap-1.5">
                                    <span>Fecha</span>
                                    <i :class="iconoOrden('published_at')" class="text-xs transition-colors" />
                                </div>
                            </th>
                            <th class="text-right px-5 py-3.5 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <draggable
                        v-model="listaArticulos"
                        tag="tbody"
                        item-key="id"
                        animation="200"
                        ghost-class="opacity-40"
                        handle=".art-drag-handle"
                        :disabled="!puedeReordenar"
                        @end="guardarNuevoOrden"
                        class="divide-y divide-zinc-200 dark:divide-zinc-800"
                    >
                        <template #item="{ element: art }">
                            <tr
                                @click="irAEditar(art)"
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors cursor-pointer select-none group"
                            >
                                <td
                                    @click.stop
                                    :class="[
                                        'px-3 py-4 text-center transition-colors',
                                        puedeReordenar
                                            ? 'art-drag-handle text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-grab active:cursor-grabbing'
                                            : 'text-zinc-200 dark:text-zinc-700 cursor-not-allowed opacity-40'
                                    ]"
                                    :title="tooltipReordenar"
                                >
                                    <i class="pi pi-bars text-sm" />
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-zinc-900 dark:text-zinc-100 font-semibold leading-snug group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ art.title }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">{{ art.slug }}</div>
                                </td>
                                <td class="px-5 py-4 text-zinc-700 dark:text-zinc-300">
                                    <span v-if="art.category" class="px-2.5 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-md text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ art.category.name }}
                                    </span>
                                    <span v-else class="text-zinc-400">—</span>
                                </td>
                                <td class="px-5 py-4 text-center" @click.stop>
                                    <div class="inline-flex flex-col items-center">
                                        <!-- Selector inline de estado -->
                                        <select
                                            :value="art.is_expired ? 'expired' : (art.raw_status || art.status)"
                                            @change="(e) => cambiarEstadoArticulo(art, e.target.value)"
                                            class="text-xs font-semibold rounded-full px-3 py-1 border transition-all cursor-pointer shadow-2xs focus:ring-2 focus:ring-emerald-500/20"
                                            v-tooltip.top="'Cambiar el estado de publicación'"
                                            :class="{
                                                'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 font-bold': art.is_expired,
                                                'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800': (art.raw_status || art.status) === 'published' && !art.is_future && !art.is_expired,
                                                'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800': (art.is_future || (art.raw_status || art.status) === 'scheduled') && !art.is_expired,
                                                'bg-zinc-100 text-zinc-700 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700': (art.raw_status || art.status) === 'draft',
                                            }"
                                        >
                                            <option v-if="art.is_expired" value="expired" disabled>Expirado / Finalizado</option>
                                            <option value="draft">Despublicado</option>
                                            <option value="published">Publicado</option>
                                            <option value="scheduled">Programado</option>
                                        </select>
                                        <div v-if="art.is_expired" class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold mt-1 inline-flex items-center gap-1">
                                            <i class="pi pi-times-circle text-[10px]" />
                                            <span>Expiró: {{ art.published_until }}</span>
                                        </div>
                                        <div v-else-if="art.is_future" class="text-[11px] text-amber-600 dark:text-amber-400 font-medium mt-1 inline-flex items-center gap-1">
                                            <i class="pi pi-clock text-[10px]" />
                                            <span>Prog: {{ art.published_at }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400 text-xs">{{ art.author?.name || 'Sistema' }}</td>
                                <td class="px-5 py-4 text-zinc-500 dark:text-zinc-400 text-xs font-mono">
                                    <div>{{ art.published_at || art.created_at }}</div>
                                    <div v-if="art.published_until" class="text-[11px] mt-0.5 flex items-center gap-1" :class="art.is_expired ? 'text-rose-500 font-medium' : 'text-zinc-400 dark:text-zinc-500'">
                                        <i class="pi pi-hourglass text-[10px]" />
                                        <span>Fin: {{ art.published_until }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right" @click.stop>
                                    <div class="flex justify-end gap-1">
                                        <Link :href="route('articulos.edit', { article: art.id, ...obtenerParamsActivos() })">
                                            <Button
                                                icon="pi pi-pencil text-xs"
                                                text
                                                rounded
                                                size="small"
                                                class="!w-8 !h-8 !p-0"
                                                v-tooltip.top="'Editar artículo'"
                                            />
                                        </Link>
                                        <Button
                                            icon="pi pi-copy text-xs"
                                            text
                                            rounded
                                            severity="secondary"
                                            size="small"
                                            class="!w-8 !h-8 !p-0"
                                            @click="duplicar(art)"
                                            v-tooltip.top="'Duplicar artículo'"
                                        />
                                        <Button
                                            icon="pi pi-trash text-xs"
                                            text
                                            rounded
                                            severity="danger"
                                            size="small"
                                            class="!w-8 !h-8 !p-0"
                                            @click="eliminar(art)"
                                            v-tooltip.top="'Mover a la papelera'"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </draggable>
                </table>

                <div v-if="!listaArticulos.length" class="text-center py-16 bg-white dark:bg-zinc-900">
                    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-4 text-zinc-400">
                        <i class="pi pi-file-edit text-2xl" />
                    </div>
                    <p class="text-zinc-900 dark:text-zinc-100 font-semibold text-base">No hay artículos</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Crea tu primer artículo para empezar a publicar contenido.</p>
                </div>
            </div>

            <!-- Paginación y resumen de registros -->
            <div v-if="totalRegistros > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-1">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 text-center sm:text-left">
                    Mostrando <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ desdeRegistro }}</span> a <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ hastaRegistro }}</span> de <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ totalRegistros }}</span> artículos
                </div>

                <nav v-if="totalPaginas > 1" class="flex items-center gap-1 flex-wrap justify-center">
                    <template v-for="(link, idx) in paginacionLinks" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1.5 text-xs sm:text-sm rounded-lg border transition-colors font-medium"
                            :class="link.active
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 text-xs sm:text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 text-zinc-300 dark:text-zinc-600 pointer-events-none opacity-50"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
