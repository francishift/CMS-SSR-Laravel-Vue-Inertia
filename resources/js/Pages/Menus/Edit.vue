<script setup>
/**
 * Editor de menú y sus elementos.
 *
 * Utiliza componentes PrimeVue: Dialog, Select (con búsqueda), ToggleSwitch, Button.
 * Permite crear y editar libremente elementos de menú existentes.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch, computed, nextTick } from 'vue';
import draggable from 'vuedraggable';

// Componentes PrimeVue
import Dialog from 'primevue/dialog';
import ToggleSwitch from 'primevue/toggleswitch';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const confirm = useConfirm();

const props = defineProps({
    menu: { type: Object, required: true },
    categorias: { type: Array, default: () => [] },
    articulos: { type: Array, default: () => [] },
    paginas: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: 'Menús', href: route('menus.index') },
    { label: props.menu.name },
];

// ─── Configuración General del Menú ─────────────────
const formMenu = useForm({
    name: props.menu.name,
    location: props.menu.location,
    is_active: Boolean(props.menu.is_active),
});

function guardarMenu() {
    formMenu.put(route('menus.update', props.menu.id));
}

const opcionesUbicacion = [
    { label: 'Cabecera (Header)', value: 'header' },
    { label: 'Barra lateral (Sidebar)', value: 'sidebar' },
    { label: 'Sin asignar (Solo Page Builder)', value: 'custom' },
];

// ─── Opciones de Tipo de Destino (PrimeIcons) ────────
const moduloDescargasInstalado = computed(() => {
    try {
        return route().has('public.downloads.index');
    } catch {
        return false;
    }
});

const opcionesTipo = computed(() => [
    { label: 'Página de Inicio', value: 'home', icon: 'pi pi-home' },
    { label: 'Página Estática', value: 'page', icon: 'pi pi-file-o' },
    { label: 'Categoría', value: 'category', icon: 'pi pi-folder' },
    { label: 'Artículo', value: 'article', icon: 'pi pi-file' },
    ...(moduloDescargasInstalado.value
        ? [{ label: 'Descargas (Catálogo)', value: 'downloads', icon: 'pi pi-download' }]
        : []),
    { label: 'URL Personalizada / Externa', value: 'url', icon: 'pi pi-link' },
]);

function getIconoTipo(val) {
    const opt = opcionesTipo.value.find(o => o.value === val);
    return opt ? opt.icon : 'pi pi-link';
}

function getLabelTipo(val) {
    const opt = opcionesTipo.value.find(o => o.value === val);
    return opt ? opt.label : '';
}

const opcionesCategorias = computed(() =>
    props.categorias.map(c => ({ label: `${c.name} (/${c.slug})`, value: c.id }))
);

const opcionesArticulos = computed(() =>
    props.articulos.map(a => ({
        label: `${a.title} ${a.category ? `[${a.category.name}]` : ''}`,
        value: a.id,
    }))
);

const opcionesPaginas = computed(() =>
    props.paginas.map(p => ({ label: `${p.title} (/${p.slug})`, value: p.id }))
);

const opcionesTarget = [
    { label: 'Misma ventana (_self)', value: '_self' },
    { label: 'Nueva pestaña (_blank)', value: '_blank' },
];

// ─── Estado reactivo para ordenación con vuedraggable ─
const listaItems = ref([...(props.menu.all_items || [])]);
const guardandoOrden = ref(false);

watch(() => props.menu.all_items, (nuevosItems) => {
    listaItems.value = [...(nuevosItems || [])];
}, { deep: true });

function guardarNuevoOrden() {
    guardandoOrden.value = true;
    const ordenData = listaItems.value.map((item, index) => ({
        id: item.id,
        order: index,
        parent_id: item.parent_id || null,
    }));

    router.post(
        route('menus.items.reorder', props.menu.id),
        { items: ordenData },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                guardandoOrden.value = false;
            },
        }
    );
}

// ─── Nuevo Elemento de Menú ─────────────────────────
const mostrarFormularioItem = ref(false);
const tipoItem = ref('page');

const formItem = useForm({
    title: '',
    url: '',
    route_name: '',
    category_id: null,
    article_id: null,
    page_id: null,
    target: '_self',
    is_nofollow: false,
    icon: 'pi pi-file-o',
    is_active: true,
});

watch(tipoItem, (nuevoTipo) => {
    formItem.url = '';
    formItem.route_name = '';
    formItem.category_id = null;
    formItem.article_id = null;
    formItem.page_id = null;

    if (nuevoTipo === 'home') {
        formItem.title = 'Página de Inicio';
        formItem.url = '/';
        formItem.icon = 'pi pi-home';
    } else if (nuevoTipo === 'downloads') {
        formItem.title = 'Descargas';
        formItem.route_name = 'public.downloads.index';
        formItem.icon = 'pi pi-download';
    } else if (nuevoTipo === 'category') {
        formItem.title = '';
        formItem.icon = 'pi pi-folder';
    } else if (nuevoTipo === 'article') {
        formItem.title = '';
        formItem.icon = 'pi pi-file';
    } else if (nuevoTipo === 'page') {
        formItem.title = '';
        formItem.icon = 'pi pi-file-o';
    } else if (nuevoTipo === 'url') {
        formItem.title = '';
        formItem.icon = 'pi pi-link';
    }
});

watch(() => formItem.category_id, (catId) => {
    if (catId && !formItem.title) {
        const cat = props.categorias.find(c => c.id === catId);
        if (cat) formItem.title = cat.name;
    }
});

watch(() => formItem.article_id, (artId) => {
    if (artId && !formItem.title) {
        const art = props.articulos.find(a => a.id === artId);
        if (art) formItem.title = art.title;
    }
});

watch(() => formItem.page_id, (pageId) => {
    if (pageId && !formItem.title) {
        const p = props.paginas.find(page => page.id === pageId);
        if (p) formItem.title = p.title;
    }
});

function crearItem() {
    formItem.post(route('menus.items.store', props.menu.id), {
        onSuccess: () => {
            formItem.reset();
            tipoItem.value = 'page';
            formItem.title = '';
            formItem.url = '';
            formItem.icon = 'pi pi-file-o';
            mostrarFormularioItem.value = false;
        },
    });
}

// ─── Edición de Elemento Existente ──────────────────
const mostrarDialogoEditarItem = ref(false);
const tipoItemEditar = ref('url');
let cargandoItemEnDialogo = false;

const formEditarItem = useForm({
    id: null,
    title: '',
    url: '',
    route_name: '',
    category_id: null,
    article_id: null,
    page_id: null,
    target: '_self',
    is_nofollow: false,
    icon: '',
    is_active: true,
});

function abrirEditarItem(item) {
    cargandoItemEnDialogo = true;

    formEditarItem.id = item.id;
    formEditarItem.title = item.title;
    formEditarItem.url = item.url || '';
    formEditarItem.route_name = item.route_name || '';
    formEditarItem.category_id = item.category_id !== undefined && item.category_id !== null ? Number(item.category_id) : null;
    formEditarItem.article_id = item.article_id !== undefined && item.article_id !== null ? Number(item.article_id) : null;
    formEditarItem.page_id = item.page_id !== undefined && item.page_id !== null ? Number(item.page_id) : null;
    formEditarItem.target = item.target || '_self';
    formEditarItem.is_nofollow = Boolean(item.is_nofollow);
    formEditarItem.icon = item.icon || '';
    formEditarItem.is_active = Boolean(item.is_active);

    if (item.category_id) tipoItemEditar.value = 'category';
    else if (item.article_id) tipoItemEditar.value = 'article';
    else if (item.page_id) tipoItemEditar.value = 'page';
    else if (item.route_name === 'public.downloads.index') tipoItemEditar.value = 'downloads';
    else if (item.url === '/') tipoItemEditar.value = 'home';
    else tipoItemEditar.value = 'url';

    mostrarDialogoEditarItem.value = true;

    nextTick(() => {
        cargandoItemEnDialogo = false;
    });
}

watch(tipoItemEditar, (nuevoTipo) => {
    if (!mostrarDialogoEditarItem.value || cargandoItemEnDialogo) return;

    formEditarItem.category_id = null;
    formEditarItem.article_id = null;
    formEditarItem.page_id = null;
    formEditarItem.url = '';
    formEditarItem.route_name = '';

    if (nuevoTipo === 'home') {
        formEditarItem.url = '/';
        formEditarItem.icon = formEditarItem.icon || 'pi pi-home';
    } else if (nuevoTipo === 'downloads') {
        formEditarItem.route_name = 'public.downloads.index';
        formEditarItem.icon = formEditarItem.icon || 'pi pi-download';
    } else if (nuevoTipo === 'category') {
        formEditarItem.icon = formEditarItem.icon || 'pi pi-folder';
    } else if (nuevoTipo === 'article') {
        formEditarItem.icon = formEditarItem.icon || 'pi pi-file';
    } else if (nuevoTipo === 'page') {
        formEditarItem.icon = formEditarItem.icon || 'pi pi-file-o';
    } else if (nuevoTipo === 'url') {
        formEditarItem.icon = formEditarItem.icon || 'pi pi-link';
    }
});

function actualizarItem() {
    formEditarItem.put(route('menus.items.update', formEditarItem.id), {
        onSuccess: () => {
            mostrarDialogoEditarItem.value = false;
        },
    });
}

function eliminarItem(item) {
    confirm.require({
        message: `¿Mover el elemento "${item.title}" a la papelera?`,
        header: 'Enviar a la papelera',
        icon: 'pi pi-trash',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, enviar a papelera',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('menus.items.destroy', item.id)),
    });
}

function restaurarItem(id) {
    router.patch(route('menus.items.restore', id), {}, { preserveScroll: true });
}

function eliminarDefinitivoItem(item) {
    confirm.require({
        message: `¿Eliminar DEFINITIVAMENTE el elemento "${item.title}"? Esta acción no se puede deshacer.`,
        header: 'Eliminación permanente',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, eliminar permanentemente',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('menus.items.force-delete', item.id), { preserveScroll: true }),
    });
}

function badgeTipo(item) {
    if (item.category_id) return { value: 'Categoría', severity: 'info' };
    if (item.article_id) return { value: 'Artículo', severity: 'help' };
    if (item.page_id) return { value: 'Página', severity: 'warn' };
    if (item.route_name === 'public.downloads.index') return { value: 'Descargas', severity: 'success' };
    if (item.url === '/') return { value: 'Inicio', severity: 'success' };
    if (item.url) return { value: 'URL', severity: 'secondary' };
    return { value: 'Sin enlace', severity: 'contrast' };
}
</script>

<template>
    <Head :title="`Editar: ${menu.name}`" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 w-full space-y-6 sm:space-y-8">

            <PageHeader
                :title="`Editar Menú: ${menu.name}`"
                description="Configura la ubicación del menú y administra sus elementos de navegación."
                :breadcrumbs="breadcrumbs"
                divider
            />

            <!-- ═══════════════════════════════════════════════ -->
            <!-- SECCIÓN 1: Configuración General del Menú      -->
            <!-- ═══════════════════════════════════════════════ -->
            <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i class="pi pi-cog text-sm" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Configuración General</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre del menú, ubicación en la plantilla y estado global</p>
                    </div>
                </div>

                <form @submit.prevent="guardarMenu">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                Nombre del menú *
                            </label>
                            <InputText
                                v-model="formMenu.name"
                                required
                                class="w-full"
                                placeholder="Ej: Menú Principal"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                Ubicación en la plantilla *
                            </label>
                            <Select
                                v-model="formMenu.location"
                                :options="opcionesUbicacion"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>

                        <div class="flex items-center gap-3 h-10 px-2">
                            <ToggleSwitch v-model="formMenu.is_active" id="menu_active" />
                            <label for="menu_active" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                                Menú activo
                            </label>
                        </div>

                        <div>
                            <Button
                                type="submit"
                                label="Guardar Ajustes"
                                icon="pi pi-check"
                                :loading="formMenu.processing"
                                severity="emerald"
                                class="w-full"
                            />
                        </div>
                    </div>
                </form>
            </section>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- SECCIÓN 2: Elementos del Menú                  -->
            <!-- ═══════════════════════════════════════════════ -->
            <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/60 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <i class="pi pi-list text-sm" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Elementos del Menú</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ (menu.all_items || []).length }} enlaces configurados</p>
                        </div>
                    </div>

                    <Button
                        :label="mostrarFormularioItem ? 'Cancelar' : 'Añadir Elemento'"
                        :icon="mostrarFormularioItem ? 'pi pi-times' : 'pi pi-plus'"
                        :severity="mostrarFormularioItem ? 'secondary' : 'emerald'"
                        @click="mostrarFormularioItem = !mostrarFormularioItem"
                    />
                </div>

                <!-- Formulario desplegable para nuevo item -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <form
                        v-if="mostrarFormularioItem"
                        @submit.prevent="crearItem"
                        class="mb-6 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4 shadow-sm"
                    >
                        <div class="flex items-center gap-2 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                            <i class="pi pi-link text-emerald-600 dark:text-emerald-400 text-sm" />
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nuevo Elemento de Menú</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Tipo de destino -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                    Tipo de destino *
                                </label>
                                <Select
                                    v-model="tipoItem"
                                    :options="opcionesTipo"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                >
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value" class="flex items-center gap-2">
                                            <i :class="getIconoTipo(slotProps.value)" class="text-emerald-600 dark:text-emerald-400 text-sm" />
                                            <span>{{ getLabelTipo(slotProps.value) }}</span>
                                        </div>
                                    </template>
                                    <template #option="slotProps">
                                        <div class="flex items-center gap-2.5">
                                            <i :class="slotProps.option.icon" class="text-zinc-500 text-sm" />
                                            <span>{{ slotProps.option.label }}</span>
                                        </div>
                                    </template>
                                </Select>
                            </div>

                            <!-- Título visible -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                    Título visible *
                                </label>
                                <InputText
                                    v-model="formItem.title"
                                    required
                                    class="w-full"
                                    placeholder="Ej: Inicio, Blog, Sobre Nosotros"
                                />
                                <p v-if="formItem.errors.title" class="text-red-500 text-xs mt-1">{{ formItem.errors.title }}</p>
                            </div>

                            <!-- Campo dinámico según destino seleccionado -->
                            <div class="sm:col-span-2">
                                <!-- Página de Inicio -->
                                <div v-if="tipoItem === 'home'" class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                    <i class="pi pi-home text-base" />
                                    <span>Este elemento enlaza directamente a la página principal del sitio (<code>/</code>).</span>
                                </div>

                                <!-- Catálogo de Descargas -->
                                <div v-if="tipoItem === 'downloads'" class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                    <i class="pi pi-download text-base" />
                                    <span>Enlaza dinámicamente al catálogo de Descargas según el slug configurado en el módulo.</span>
                                </div>

                                <!-- Enlace a Categoría -->
                                <div v-if="tipoItem === 'category'">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Seleccionar Categoría *
                                    </label>
                                    <Select
                                        v-model="formItem.category_id"
                                        :options="opcionesCategorias"
                                        optionLabel="label"
                                        optionValue="value"
                                        filter
                                        placeholder="Buscar o seleccionar categoría..."
                                        class="w-full"
                                    />
                                </div>

                                <!-- Enlace a Artículo -->
                                <div v-if="tipoItem === 'article'">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Seleccionar Artículo Publicado *
                                    </label>
                                    <Select
                                        v-model="formItem.article_id"
                                        :options="opcionesArticulos"
                                        optionLabel="label"
                                        optionValue="value"
                                        filter
                                        placeholder="Buscar o seleccionar artículo..."
                                        class="w-full"
                                    />
                                </div>

                                <!-- Enlace a Página Estática -->
                                <div v-if="tipoItem === 'page'">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Seleccionar Página Estática *
                                    </label>
                                    <Select
                                        v-model="formItem.page_id"
                                        :options="opcionesPaginas"
                                        optionLabel="label"
                                        optionValue="value"
                                        filter
                                        placeholder="Buscar o seleccionar página estática..."
                                        class="w-full"
                                    />
                                </div>

                                <!-- URL Personalizada -->
                                <div v-if="tipoItem === 'url'">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        URL de destino
                                    </label>
                                    <InputText
                                        v-model="formItem.url"
                                        class="w-full font-mono text-xs"
                                        placeholder="Ej: /mi-ruta o https://sitioexterno.com"
                                    />
                                </div>
                            </div>

                            <!-- Icono (PrimeIcons) -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                    Icono del Enlace
                                </label>
                                <IconPicker
                                    v-model="formItem.icon"
                                    placeholder="Seleccionar icono visual..."
                                />
                            </div>

                            <!-- Comportamiento Target y NoFollow -->
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                                        Apertura de ventana
                                    </label>
                                    <Select
                                        v-model="formItem.target"
                                        :options="opcionesTarget"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full"
                                    />
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <ToggleSwitch v-model="formItem.is_nofollow" id="new_item_nofollow" />
                                    <label for="new_item_nofollow" class="text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                                        No Follow (<code class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400">rel="nofollow"</code>)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-end">
                            <Button
                                type="submit"
                                label="Añadir al Menú"
                                icon="pi pi-plus"
                                :loading="formItem.processing"
                                severity="emerald"
                            />
                        </div>
                    </form>
                </Transition>

                <!-- Listado de elementos arrastrales (vuedraggable) -->
                <div class="space-y-2">
                    <draggable
                        v-model="listaItems"
                        item-key="id"
                        animation="200"
                        ghost-class="opacity-40"
                        drag-class="scale-[1.01]"
                        handle=".menu-drag-handle"
                        @end="guardarNuevoOrden"
                        class="space-y-2"
                    >
                        <template #item="{ element: item, index }">
                            <div
                                :class="[
                                    'group rounded-xl border p-4 flex items-center justify-between transition-all shadow-sm',
                                    item.parent_id
                                        ? 'ml-8 bg-zinc-50/80 dark:bg-zinc-800/40 border-zinc-200 dark:border-zinc-800'
                                        : 'bg-white dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700/80 hover:border-zinc-300 dark:hover:border-zinc-600',
                                ]"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <!-- Botón de arrastre -->
                                    <div
                                        class="menu-drag-handle text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-grab active:cursor-grabbing p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors shrink-0 flex items-center justify-center"
                                        title="Arrastrar para reordenar"
                                    >
                                        <i class="pi pi-bars text-sm" />
                                    </div>

                                    <span class="text-xs text-zinc-400 font-mono w-4 text-center shrink-0">{{ index + 1 }}</span>

                                    <i v-if="item.icon" :class="item.icon" class="text-zinc-500 dark:text-zinc-400 text-sm shrink-0" />
                                    <i v-else class="pi pi-link text-zinc-400 text-xs shrink-0" />

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-zinc-900 dark:text-zinc-100 font-semibold text-sm truncate">{{ item.title }}</span>
                                            <Tag v-if="!item.is_active" value="Inactivo" severity="danger" />
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Tag :value="badgeTipo(item).value" :severity="badgeTipo(item).severity" />
                                            <Tag v-if="item.is_nofollow" value="nofollow" severity="warn" class="text-[10px]" />
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono truncate">{{ item.resolved_url }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acciones: Editar y Eliminar -->
                                <div class="flex items-center gap-1">
                                    <Button
                                        icon="pi pi-pencil"
                                        severity="secondary"
                                        text
                                        rounded
                                        @click="abrirEditarItem(item)"
                                        title="Editar elemento"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        @click="eliminarItem(item)"
                                        title="Eliminar elemento"
                                    />
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <!-- Estado vacío -->
                    <div v-if="!listaItems.length" class="text-center py-12 bg-zinc-50/50 dark:bg-zinc-800/20 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800">
                        <i class="pi pi-link text-3xl text-zinc-400 mb-2" />
                        <p class="text-zinc-700 dark:text-zinc-300 font-medium text-sm">El menú no contiene elementos</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Haz clic en "Añadir Elemento" arriba para agregar accesos de navegación.</p>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- MODAL: Editar Elemento de Menú                 -->
            <!-- ═══════════════════════════════════════════════ -->
            <Dialog
                v-model:visible="mostrarDialogoEditarItem"
                header="Editar Elemento de Menú"
                :modal="true"
                class="w-full max-w-lg"
            >
                <form @submit.prevent="actualizarItem" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                            Tipo de destino *
                        </label>
                        <Select
                            v-model="tipoItemEditar"
                            :options="opcionesTipo"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2">
                                    <i :class="getIconoTipo(slotProps.value)" class="text-emerald-600 dark:text-emerald-400 text-sm" />
                                    <span>{{ getLabelTipo(slotProps.value) }}</span>
                                </div>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center gap-2.5">
                                    <i :class="slotProps.option.icon" class="text-zinc-500 text-sm" />
                                    <span>{{ slotProps.option.label }}</span>
                                </div>
                            </template>
                        </Select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">
                            Título visible *
                        </label>
                        <InputText
                            v-model="formEditarItem.title"
                            required
                            class="w-full"
                        />
                    </div>

                    <!-- Destinos dinámicos en edición -->
                    <div v-if="tipoItemEditar === 'category'">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Categoría *</label>
                        <Select
                            v-model="formEditarItem.category_id"
                            :options="opcionesCategorias"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            class="w-full"
                        />
                    </div>

                    <div v-if="tipoItemEditar === 'article'">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Artículo *</label>
                        <Select
                            v-model="formEditarItem.article_id"
                            :options="opcionesArticulos"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            class="w-full"
                        />
                    </div>

                    <div v-if="tipoItemEditar === 'page'">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Página Estática *</label>
                        <Select
                            v-model="formEditarItem.page_id"
                            :options="opcionesPaginas"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            class="w-full"
                        />
                    </div>

                    <div v-if="tipoItemEditar === 'downloads'" class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                        <i class="pi pi-download text-base" />
                        <span>Enlaza dinámicamente al catálogo de Descargas.</span>
                    </div>

                    <div v-if="tipoItemEditar === 'url'">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">URL de destino</label>
                        <InputText
                            v-model="formEditarItem.url"
                            class="w-full font-mono text-xs"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Icono del Enlace</label>
                        <IconPicker
                            v-model="formEditarItem.icon"
                            placeholder="Seleccionar icono visual..."
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Apertura</label>
                        <Select
                            v-model="formEditarItem.target"
                            :options="opcionesTarget"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        />
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <ToggleSwitch v-model="formEditarItem.is_nofollow" id="edit_item_nofollow" />
                        <label for="edit_item_nofollow" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                            No Follow (<code class="text-xs font-mono text-emerald-600 dark:text-emerald-400">rel="nofollow"</code>)
                        </label>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <ToggleSwitch v-model="formEditarItem.is_active" id="edit_item_active" />
                        <label for="edit_item_active" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                            Elemento activo
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <Button
                            type="button"
                            label="Cancelar"
                            severity="secondary"
                            @click="mostrarDialogoEditarItem = false"
                        />
                        <Button
                            type="submit"
                            label="Guardar Cambios"
                            icon="pi pi-check"
                            :loading="formEditarItem.processing"
                            severity="emerald"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Papelera de Elementos de Menú -->
            <div v-if="props.menu.trashed_items?.length" class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-trash text-red-500" />
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Elementos Eliminados (Papelera)</h3>
                        <span class="rounded-full bg-red-100 dark:bg-red-950/80 px-2 py-0.5 text-xs font-bold text-red-600 dark:text-red-400">
                            {{ props.menu.trashed_items.length }}
                        </span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div v-for="tItem in props.menu.trashed_items" :key="tItem.id" class="flex items-center justify-between p-3.5 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
                        <div>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 text-sm">{{ tItem.title }}</span>
                            <span class="text-xs text-zinc-500 ml-2">Eliminado {{ tItem.deleted_at }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button label="Restaurar" icon="pi pi-undo" severity="emerald" size="small" outlined @click="restaurarItem(tItem.id)" />
                            <Button label="Eliminar Definitivamente" icon="pi pi-times" severity="danger" size="small" outlined @click="eliminarDefinitivoItem(tItem)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
