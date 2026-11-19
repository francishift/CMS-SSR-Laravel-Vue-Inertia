<script setup>
/**
 * Listado de Páginas Estáticas.
 * 
 * Utiliza PrimeVue DataTable, InputText, Tag, ToggleSwitch, Button y ConfirmDialog.
 * Soporta modo claro y oscuro con usabilidad premium.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

// Componentes PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';

const confirm = useConfirm();

const props = defineProps({
    paginas: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    conteoPapelera: { type: Number, default: 0 },
});

const busqueda = ref(props.filtros.search || '');

function buscar() {
    router.get(route('paginas.index'), { search: busqueda.value }, { preserveState: true, replace: true });
}

function irAEditar(pagina) {
    if (!pagina?.id) return;
    router.get(route('paginas.builder', pagina.id));
}

function toggleEstado(pagina) {
    router.patch(route('paginas.toggle-active', pagina.id), {}, { preserveScroll: true });
}

function eliminar(pagina) {
    if (pagina.is_homepage) return; // Protección extra contra borrado de homepage
    confirm.require({
        message: `¿Mover la página "${pagina.title}" a la papelera?`,
        header: 'Mover a la papelera',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('paginas.destroy', pagina.id)),
    });
}

function marcarComoInicio(pagina) {
    confirm.require({
        message: `¿Establecer "${pagina.title}" como página de inicio del sitio?`,
        header: 'Marcar como Inicio',
        icon: 'pi pi-home',
        accept: () => router.put(route('paginas.set-homepage', pagina.id), {}, { preserveScroll: true }),
    });
}
</script>

<template>
    <Head title="Páginas Estáticas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Gestión de Páginas
                    </h1>
                </div>

                <!-- Pestañas Activos / Papelera en el Header (Solo Desktop md+) -->
                <div class="hidden md:flex items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs shrink-0">
                    <Link
                        :href="route('paginas.index')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm"
                    >
                        <i class="pi pi-file-o text-xs" />
                        <span>Páginas Activas</span>
                    </Link>
                    <Link
                        :href="route('paginas.trashed')"
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
                    :href="route('paginas.index')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm text-center"
                >
                    <i class="pi pi-file-o text-xs" />
                    <span>Páginas Activas</span>
                </Link>
                <Link
                    :href="route('paginas.trashed')"
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
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Listado de Páginas</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Administra las páginas estáticas del sitio web</p>
                </div>
                <Link :href="route('paginas.create')">
                    <Button
                        label="Nueva Página"
                        icon="pi pi-plus"
                        severity="emerald"
                    />
                </Link>
            </div>

            <!-- Barra de Herramientas y Búsqueda -->
            <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <IconField class="w-full sm:w-80">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="busqueda"
                        @keyup.enter="buscar"
                        placeholder="Buscar página por título o slug..."
                        class="w-full text-sm bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700"
                    />
                </IconField>

                <div class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                    Total: {{ paginas.data?.length || 0 }} páginas
                </div>
            </div>

            <!-- Tabla PrimeVue -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                <DataTable
                    :value="paginas.data"
                    responsiveLayout="scroll"
                    class="p-datatable-sm cursor-pointer"
                    stripedRows
                    rowHover
                    @row-click="(e) => irAEditar(e.data)"
                >
                    <Column field="title" header="Título">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3 py-1">
                                <div
                                    :class="[
                                        'w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 transition-transform hover:scale-105 shadow-2xs',
                                        data.is_homepage
                                            ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60'
                                            : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-800/60'
                                    ]"
                                    v-tooltip.top="data.is_homepage ? 'Portada' : 'Página'"
                                >
                                    <i :class="data.is_homepage ? 'pi pi-home text-sm' : 'pi pi-file text-sm'" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                            {{ data.title }}
                                        </span>
                                        <Tag
                                            v-if="data.is_homepage"
                                            severity="warn"
                                            value="Portada"
                                            icon="pi pi-home"
                                            class="!text-[10px] !px-2.5 !py-0.5 shadow-2xs cursor-help"
                                            v-tooltip.top="'Inicio'"
                                        />
                                    </div>
                                    <p class="text-xs text-zinc-400 font-mono">{{ data.is_homepage ? '/ (portada principal)' : `/${data.slug}` }}</p>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="is_active" header="Estado" style="width: 140px;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2" @click.stop>
                                <ToggleSwitch
                                    :modelValue="data.is_active"
                                    @update:modelValue="toggleEstado(data)"
                                    v-tooltip.top="data.is_active ? 'Publicada' : 'Despublicada'"
                                />
                                <Tag
                                    :severity="data.is_active ? 'success' : 'secondary'"
                                    :value="data.is_active ? 'Publicada' : 'Despublicada'"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column field="updated_at" header="Última modificación" style="width: 180px;">
                        <template #body="{ data }">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ data.updated_at || data.created_at }}</span>
                        </template>
                    </Column>

                    <Column header="Acciones" style="width: 160px;" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex items-center justify-end gap-1" @click.stop>
                                <!-- Marcar como Inicio (solo si NO es homepage) -->
                                <button
                                    v-if="!data.is_homepage"
                                    @click="marcarComoInicio(data)"
                                    class="p-2 rounded-lg text-zinc-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors"
                                    v-tooltip.top="'Fijar Inicio'"
                                >
                                    <i class="pi pi-home text-sm" />
                                </button>

                                <a
                                    :href="data.is_homepage ? '/' : `/${data.slug}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="p-2 rounded-lg text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors"
                                    v-tooltip.top="'Ver Web'"
                                >
                                    <i class="pi pi-external-link text-sm" />
                                </a>

                                <Link
                                    :href="route('paginas.builder', data.id)"
                                    class="p-2 rounded-lg text-zinc-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/40 transition-colors"
                                    v-tooltip.top="'Maquetador'"
                                >
                                    <i class="pi pi-objects-column text-sm" />
                                </Link>

                                <Link
                                    :href="route('paginas.edit', data.id)"
                                    class="p-2 rounded-lg text-zinc-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors"
                                    v-tooltip.top="'Editar'"
                                >
                                    <i class="pi pi-pencil text-sm" />
                                </Link>

                                <!-- Eliminar (deshabilitado para homepage) -->
                                <button
                                    v-if="!data.is_homepage"
                                    @click="eliminar(data)"
                                    class="p-2 rounded-lg text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors"
                                    v-tooltip.top="'Papelera'"
                                >
                                    <i class="pi pi-trash text-sm" />
                                </button>
                                <span
                                    v-else
                                    class="p-2 rounded-lg text-zinc-300 dark:text-zinc-700 cursor-not-allowed"
                                    v-tooltip.top="'No eliminable'"
                                >
                                    <i class="pi pi-trash text-sm" />
                                </span>
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="text-center py-12 text-zinc-500 dark:text-zinc-400 space-y-3">
                            <i class="pi pi-file-o text-3xl text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm font-medium">No se encontraron páginas estáticas.</p>
                            <Link :href="route('paginas.create')">
                                <Button label="Crear Primera Página" icon="pi pi-plus" severity="emerald" size="small" class="mt-2" />
                            </Link>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
