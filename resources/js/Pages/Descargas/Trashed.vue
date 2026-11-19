<script setup>
/**
 * Papelera de reciclaje del Módulo de Descargas.
 *
 * Permite restaurar elementos o eliminarlos definitivamente purgando su archivo físico privado.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const confirm = useConfirm();

const props = defineProps({
    descargas: { type: Object, required: true },
});

function restaurar(id) {
    router.patch(route('downloads.admin.restore', id), {}, { preserveScroll: true });
}

function eliminarDefinitivo(item) {
    confirm.require({
        message: `¿ATENCIÓN! ¿Eliminar DEFINITIVAMENTE "${item.title}" y borrar su archivo ZIP del servidor? Esta acción no se puede deshacer.`,
        header: 'Eliminación permanente',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, eliminar permanentemente',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('downloads.admin.force-delete', item.id), { preserveScroll: true }),
    });
}

function vaciarPapelera() {
    confirm.require({
        message: '¿Estás seguro de vaciar toda la papelera de descargas? Se purgarán permanentemente todos los archivos privados asociados.',
        header: 'Vaciar Papelera de Descargas',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, vaciar papelera',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('downloads.admin.empty-trash'), { preserveScroll: true }),
    });
}
</script>

<template>
    <Head title="Papelera — Descargas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Papelera de Descargas
                    </h1>
                </div>

                <!-- Conmutador Catálogo / Papelera en Topbar (Solo Desktop md+) -->
                <div class="hidden md:flex items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs shrink-0">
                    <Link
                        :href="route('downloads.admin.index')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 no-underline"
                    >
                        <i class="pi pi-download text-xs" />
                        <span>Catálogo Activo</span>
                    </Link>
                    <Link
                        :href="route('downloads.admin.trashed')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm no-underline"
                    >
                        <i class="pi pi-trash text-xs" />
                        <span>Papelera</span>
                        <span class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                            {{ descargas.total ?? descargas.data?.length ?? 0 }}
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
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 text-center no-underline"
                >
                    <i class="pi pi-download text-xs" />
                    <span>Catálogo Activo</span>
                </Link>
                <Link
                    :href="route('downloads.admin.trashed')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm text-center no-underline"
                >
                    <i class="pi pi-trash text-xs" />
                    <span>Papelera</span>
                    <span class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                        {{ descargas.total ?? descargas.data?.length ?? 0 }}
                    </span>
                </Link>
            </div>

            <PageHeader
                :back-href="route('downloads.admin.index')"
                back-title="Volver al catálogo de descargas"
                title="Papelera de Descargas"
                description="Restaura recursos eliminados o púrgalos definitivamente del servidor"
                divider
            >
                <template #actions>
                    <Button
                        v-if="descargas.data?.length > 0"
                        label="Vaciar Papelera"
                        icon="pi pi-trash"
                        severity="danger"
                        size="small"
                        @click="vaciarPapelera"
                    />
                    <Link :href="route('downloads.admin.index')">
                        <Button label="Volver a Descargas" icon="pi pi-arrow-left" severity="secondary" size="small" />
                    </Link>
                </template>
            </PageHeader>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div v-if="descargas.data?.length" class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <div
                        v-for="item in descargas.data"
                        :key="item.id"
                        class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ item.title }}</span>
                                <Tag v-if="item.version" :value="`v${item.version}`" severity="secondary" />
                                <Tag :value="item.formatted_file_size" severity="info" />
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                Eliminado el {{ item.deleted_at_human }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <Button label="Restaurar" icon="pi pi-undo" severity="emerald" size="small" outlined @click="restaurar(item.id)" />
                            <Button label="Eliminar Definitivo" icon="pi pi-times" severity="danger" size="small" outlined @click="eliminarDefinitivo(item)" />
                        </div>
                    </div>
                </div>

                <div v-else class="py-16 px-6 text-center text-zinc-500 dark:text-zinc-400">
                    <i class="pi pi-check-circle text-3xl text-emerald-500 mb-2" />
                    <p class="text-sm font-medium">La papelera de descargas está vacía.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
