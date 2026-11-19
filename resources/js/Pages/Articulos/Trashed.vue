<script setup>
/**
 * Papelera de artículos con interfaz unificada al estilo Usuarios.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

import Button from 'primevue/button';
import Tag from 'primevue/tag';

const confirm = useConfirm();

const props = defineProps({
    articulos: { type: Object, required: true },
    conteoPapelera: { type: Number, default: 0 },
});

function restaurar(id) {
    router.patch(route('articulos.restore', id), {}, { preserveScroll: true });
}

function eliminarDefinitivo(art) {
    confirm.require({
        message: `¿ATENCIÓN! ¿Eliminar definitivamente el artículo "${art.title}"? Esta acción no se puede deshacer.`,
        header: 'Eliminación permanente',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, eliminar de forma permanente',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('articulos.force-delete', art.id), { preserveScroll: true }),
    });
}

function vaciarPapelera() {
    confirm.require({
        message: '¡ATENCIÓN! ¿Estás seguro de que deseas vaciar toda la papelera y eliminar PERMANENTEMENTE todos los artículos de la papelera? Esta acción no se puede deshacer.',
        header: 'Vaciar Papelera de Artículos',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, vaciar papelera por completo',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('articulos.empty-trash'), { preserveScroll: true }),
    });
}
</script>

<template>
    <Head title="Papelera — Artículos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Papelera de Artículos
                    </h1>
                </div>

                <!-- Pestañas Activos / Papelera en el Header (Solo Desktop md+) -->
                <div class="hidden md:flex items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs shrink-0">
                    <Link
                        :href="route('articulos.index')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"
                    >
                        <i class="pi pi-file text-xs" />
                        <span>Artículos Activos</span>
                    </Link>
                    <Link
                        :href="route('articulos.trashed')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm"
                    >
                        <i class="pi pi-trash text-xs" />
                        <span>Papelera</span>
                        <span class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                            {{ conteoPapelera || articulos.data?.length || 0 }}
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
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 text-center"
                >
                    <i class="pi pi-file text-xs" />
                    <span>Artículos Activos</span>
                </Link>
                <Link
                    :href="route('articulos.trashed')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm text-center"
                >
                    <i class="pi pi-trash text-xs" />
                    <span>Papelera</span>
                    <span class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                        {{ conteoPapelera || articulos.data?.length || 0 }}
                    </span>
                </Link>
            </div>

            <PageHeader
                title="Artículos en Papelera"
                description="Restaura publicaciones eliminadas o bórralas de forma definitiva"
                divider
            >
                <template #actions>
                    <Button
                        v-if="conteoPapelera > 0 || articulos.data?.length > 0"
                        label="Vaciar Papelera"
                        icon="pi pi-trash"
                        severity="danger"
                        size="small"
                        @click="vaciarPapelera"
                    />
                </template>
            </PageHeader>

            <div class="space-y-3">
                <div
                    v-for="art in articulos.data"
                    :key="art.id"
                    class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 flex items-center justify-between shadow-sm opacity-90 hover:opacity-100 transition-opacity"
                >
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-500 flex items-center justify-center shrink-0">
                            <i class="pi pi-file text-lg" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-zinc-900 dark:text-zinc-100 font-semibold text-base">{{ art.title }}</h3>
                                <Tag value="En Papelera" severity="danger" />
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                <span v-if="art.category?.name" class="font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800/80 px-2 py-0.5 rounded">
                                    {{ art.category.name }}
                                </span>
                                <span v-if="art.deleted_at">•</span>
                                <span v-if="art.deleted_at">Eliminado {{ art.deleted_at }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button
                            label="Restaurar"
                            icon="pi pi-undo"
                            severity="emerald"
                            size="small"
                            outlined
                            @click="restaurar(art.id)"
                        />
                        <Button
                            label="Eliminar Definitivamente"
                            icon="pi pi-times"
                            severity="danger"
                            size="small"
                            outlined
                            @click="eliminarDefinitivo(art)"
                        />
                    </div>
                </div>

                <div v-if="!articulos.data?.length" class="text-center py-16 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-8">
                    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-4 text-zinc-400">
                        <i class="pi pi-trash text-2xl" />
                    </div>
                    <h3 class="text-zinc-900 dark:text-zinc-100 font-semibold text-base">La papelera de artículos está vacía</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 max-w-md mx-auto">
                        No hay ningún artículo eliminado temporalmente.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
