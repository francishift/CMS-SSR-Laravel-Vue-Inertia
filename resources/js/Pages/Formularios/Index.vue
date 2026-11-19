<script setup>
/**
 * Listado paginado de formularios dinámicos del panel de administración.
 *
 * Muestra métricas de envíos, estado activo/inactivo con toggle instantáneo,
 * acciones de duplicación, edición y eliminación con confirmación.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    formularios: { type: Object, required: true },
    filtros:     { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const busqueda = ref(props.filtros?.buscar || '');

function buscar() {
    router.get(
        route('panel.formularios.index'),
        { buscar: busqueda.value },
        { preserveState: true, replace: true }
    );
}

function limpiarBusqueda() {
    busqueda.value = '';
    buscar();
}

function irAEditar(form) {
    if (!form?.id) return;
    router.get(route('panel.formularios.edit', form.id));
}

function toggleActivo(form) {
    router.patch(route('panel.formularios.toggle-active', form.id), {}, {
        preserveScroll: true,
    });
}

function duplicar(form) {
    router.post(route('panel.formularios.duplicate', form.id), {}, {
        preserveScroll: true,
    });
}

function eliminar(form) {
    confirm.require({
        message: `¿Estás seguro de eliminar el formulario «${form.name}»? Esta acción eliminará permanentemente todos sus envíos asociados.`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => router.delete(route('panel.formularios.destroy', form.id)),
    });
}

function formatearFecha(isoString) {
    if (!isoString) return '—';
    const fecha = new Date(isoString);
    return fecha.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Formularios Dinámicos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Gestión de Formularios
                    </h1>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 w-full space-y-6">
            <PageHeader
                title="Formularios Dinámicos"
                description="Crea, configura y gestiona formularios de contacto y captación de leads"
                divider
            >
                <template #actions>
                    <Link :href="route('panel.formularios.create')">
                        <Button
                            label="Nuevo Formulario"
                            icon="pi pi-plus"
                            severity="emerald"
                            class="shadow-sm font-medium"
                        />
                    </Link>
                </template>
            </PageHeader>
            <!-- Barra superior: Filtros y buscador -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="relative w-full sm:w-80">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm" />
                    <InputText
                        v-model="busqueda"
                        placeholder="Buscar por nombre o slug..."
                        class="w-full pl-9 pr-8 text-sm"
                        @keydown.enter="buscar"
                    />
                    <button
                        v-if="busqueda"
                        @click="limpiarBusqueda"
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                        title="Limpiar búsqueda"
                    >
                        <i class="pi pi-times text-xs" />
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        label="Buscar"
                        icon="pi pi-search"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="buscar"
                    />
                </div>
            </div>

            <!-- Tabla de formularios -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="text-left px-5 py-3.5 font-medium">Nombre & Identificador</th>
                            <th class="text-center px-4 py-3.5 font-medium">Campos</th>
                            <th class="text-center px-4 py-3.5 font-medium">Envíos</th>
                            <th class="text-center px-4 py-3.5 font-medium">Seguridad</th>
                            <th class="text-center px-4 py-3.5 font-medium">Estado</th>
                            <th class="text-left px-4 py-3.5 font-medium">Creado</th>
                            <th class="text-right px-5 py-3.5 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        <tr
                            v-for="f in formularios.data"
                            :key="f.id"
                            @click="irAEditar(f)"
                            class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors cursor-pointer group"
                        >
                            <!-- Nombre y Slug -->
                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                        {{ f.name }}
                                    </span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs font-mono text-zinc-500 dark:text-zinc-400">
                                            slug: {{ f.slug }}
                                        </span>
                                        <span v-if="f.description" class="text-xs text-zinc-400 truncate max-w-xs" :title="f.description">
                                            • {{ f.description }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Total Campos -->
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                    {{ f.fields_schema?.length ?? 0 }} campos
                                </span>
                            </td>

                            <!-- Total Envíos -->
                            <td class="px-4 py-4 text-center" @click.stop>
                                <Link
                                    :href="route('panel.formularios.submissions', f.id)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                                    title="Ver bandeja de envíos"
                                >
                                    <i class="pi pi-inbox text-xs" />
                                    <span>{{ f.submissions_count ?? 0 }}</span>
                                    <span v-if="f.unread_count > 0" class="w-2 h-2 rounded-full bg-red-500 animate-pulse" title="Nuevos envíos no leídos" />
                                </Link>
                            </td>

                            <!-- Badges de seguridad -->
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <i
                                        v-if="f.honeypot_field"
                                        class="pi pi-shield text-xs text-emerald-600 dark:text-emerald-400"
                                        title="Honeypot anti-spam activado"
                                    />
                                    <i
                                        v-if="f.requires_captcha"
                                        class="pi pi-lock text-xs text-blue-600 dark:text-blue-400"
                                        title="Cloudflare Turnstile activado"
                                    />
                                    <i
                                        v-if="f.email_admin_enabled"
                                        class="pi pi-envelope text-xs text-amber-600 dark:text-amber-400"
                                        title="Notificación admin activa"
                                    />
                                    <i
                                        v-if="f.email_user_enabled"
                                        class="pi pi-send text-xs text-purple-600 dark:text-purple-400"
                                        title="Autorespuesta activa"
                                    />
                                </div>
                            </td>

                            <!-- Estado (Activo / Inactivo) con Toggle -->
                            <td class="px-4 py-4 text-center" @click.stop>
                                <div class="flex items-center justify-center gap-2">
                                    <ToggleSwitch
                                        :model-value="f.is_active"
                                        @update:model-value="toggleActivo(f)"
                                    />
                                    <span class="text-xs text-zinc-500">
                                        {{ f.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Fecha -->
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400 text-xs whitespace-nowrap">
                                {{ formatearFecha(f.created_at) }}
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-4 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="route('panel.formularios.submissions', f.id)">
                                        <Button
                                            icon="pi pi-inbox"
                                            severity="secondary"
                                            text
                                            rounded
                                            v-tooltip.top="'Bandeja de envíos'"
                                        />
                                    </Link>
                                    <Button
                                        icon="pi pi-copy"
                                        severity="secondary"
                                        text
                                        rounded
                                        @click="duplicar(f)"
                                        v-tooltip.top="'Duplicar formulario'"
                                    />
                                    <Link :href="route('panel.formularios.edit', f.id)">
                                        <Button
                                            icon="pi pi-pencil"
                                            severity="secondary"
                                            text
                                            rounded
                                            v-tooltip.top="'Editar formulario'"
                                        />
                                    </Link>
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        @click="eliminar(f)"
                                        v-tooltip.top="'Eliminar permanentemente'"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Estado vacío -->
                <div v-if="!formularios.data?.length" class="text-center py-16 bg-white dark:bg-zinc-900">
                    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-4 text-zinc-400">
                        <i class="pi pi-inbox text-2xl" />
                    </div>
                    <p class="text-zinc-900 dark:text-zinc-100 font-semibold text-base">
                        {{ busqueda ? 'No se encontraron formularios' : 'No hay formularios registrados' }}
                    </p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                        {{ busqueda ? 'Prueba con otro término de búsqueda o limpia el filtro.' : 'Crea tu primer formulario dinámico con campos arrastrables y notificaciones automáticas.' }}
                    </p>
                    <div class="mt-4">
                        <Link v-if="!busqueda" :href="route('panel.formularios.create')">
                            <Button label="Crear primer formulario" icon="pi pi-plus" severity="emerald" />
                        </Link>
                        <Button v-else label="Limpiar búsqueda" icon="pi pi-times" severity="secondary" @click="limpiarBusqueda" />
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <nav v-if="formularios.last_page > 1" class="mt-6 flex justify-center gap-1">
                <template v-for="link in formularios.links" :key="link.label">
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
