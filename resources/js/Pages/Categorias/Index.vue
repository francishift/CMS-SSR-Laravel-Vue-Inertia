<script setup>
/**
 * Listado paginado de categorías del CMS.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const confirm = useConfirm();

defineProps({
    categorias: { type: Object, required: true },
    conteoPapelera: { type: Number, default: 0 },
});

function irAEditar(categoria) {
    if (!categoria?.id) return;
    router.get(route('categorias.edit', categoria.id));
}

function eliminar(categoria) {
    confirm.require({
        message: `¿Mover "${categoria.name}" a la papelera?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('categorias.destroy', categoria.id)),
    });
}
</script>

<template>
    <Head title="Categorías" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Gestión de Categorías
                    </h1>
                </div>

                <!-- Pestañas Activos / Papelera en el Header (Solo Desktop md+) -->
                <div class="hidden md:flex items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs shrink-0">
                    <Link
                        :href="route('categorias.index')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm"
                    >
                        <i class="pi pi-folder text-xs" />
                        <span>Categorías Activas</span>
                    </Link>
                    <Link
                        :href="route('categorias.trashed')"
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
                    :href="route('categorias.index')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm text-center"
                >
                    <i class="pi pi-folder text-xs" />
                    <span>Categorías Activas</span>
                </Link>
                <Link
                    :href="route('categorias.trashed')"
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
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Listado de Categorías</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Administra las categorías de tus publicaciones</p>
                </div>
                <Link :href="route('categorias.create')">
                    <Button
                        label="Nueva Categoría"
                        icon="pi pi-plus"
                        severity="emerald"
                    />
                </Link>
            </div>

            <!-- Tabla de categorías -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="text-left px-5 py-3.5 font-medium">Nombre</th>
                            <th class="text-left px-5 py-3.5 font-medium">Slug</th>
                            <th class="text-left px-5 py-3.5 font-medium">Plantilla</th>
                            <th class="text-center px-5 py-3.5 font-medium">Artículos</th>
                            <th class="text-center px-5 py-3.5 font-medium">Estado</th>
                            <th class="text-right px-5 py-3.5 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        <tr
                            v-for="cat in categorias.data"
                            :key="cat.id"
                            @click="irAEditar(cat)"
                            class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors cursor-pointer group"
                        >
                            <td class="px-5 py-4 text-zinc-900 dark:text-zinc-100 font-medium">
                                <div class="flex items-center gap-2.5">
                                    <span v-if="cat.color" class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: cat.color }" />
                                    <i v-if="cat.icon" :class="cat.icon" class="text-zinc-500 dark:text-zinc-400 shrink-0" />
                                    <span class="group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ cat.name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-500 dark:text-zinc-400 font-mono text-xs">{{ cat.slug }}</td>
                            <td class="px-5 py-4 text-zinc-700 dark:text-zinc-300 capitalize">{{ cat.template }}</td>
                            <td class="px-5 py-4 text-center text-zinc-700 dark:text-zinc-300 font-mono">{{ cat.articles_count ?? 0 }}</td>
                            <td class="px-5 py-4 text-center">
                                <Tag :value="cat.is_active ? 'Activa' : 'Inactiva'" :severity="cat.is_active ? 'success' : 'secondary'" />
                            </td>
                            <td class="px-5 py-4 text-right" @click.stop>
                                <div class="flex justify-end gap-1">
                                    <Link :href="route('categorias.edit', cat.id)">
                                        <Button
                                            icon="pi pi-pencil"
                                            severity="secondary"
                                            text
                                            rounded
                                            v-tooltip.top="'Editar categoría'"
                                        />
                                    </Link>
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        @click="eliminar(cat)"
                                        v-tooltip.top="'Mover a la papelera'"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!categorias.data?.length" class="text-center py-16 bg-white dark:bg-zinc-900">
                    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-4 text-zinc-400">
                        <i class="pi pi-tags text-2xl" />
                    </div>
                    <p class="text-zinc-900 dark:text-zinc-100 font-semibold text-base">No hay categorías registradas</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Crea tu primera categoría para organizar tus publicaciones.</p>
                </div>
            </div>

            <!-- Paginación -->
            <nav v-if="categorias.last_page > 1" class="mt-6 flex justify-center gap-1">
                <template v-for="link in categorias.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3.5 py-1.5 text-sm rounded-lg border transition-colors font-medium"
                        :class="link.active
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                            : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700'"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>
