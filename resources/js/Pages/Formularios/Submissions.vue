<script setup>
/**
 * Bandeja de envíos recibidos de formularios (Panel Admin).
 *
 * Soporta tanto vista global de todos los envíos con filtro desplegable por formulario,
 * como vista específica de un único formulario.
 * Incluye filtros de estado (todos, no leídos, leídos, spam), búsqueda textual,
 * exportación a CSV y modal de inspección completa.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';

const props = defineProps({
    formulario:             { type: Object, default: null },
    formulariosDisponibles: { type: Array, default: () => [] },
    envios:                 { type: Object, required: true },
    filtros:                { type: Object, default: () => ({}) },
    estadisticas:           { type: Object, default: () => ({ total: 0, no_leidos: 0, spam: 0 }) },
});

const confirm = useConfirm();

// Determinar si nos encontramos en la bandeja global o de un formulario concreto
const esVistaGlobal = computed(() => !props.formulario);
const formObj = computed(() => props.formulario?.data || props.formulario || {});
const formularioId = computed(() => formObj.value?.id || null);

// Envío seleccionado para el diálogo de detalle
const envioSeleccionado = ref(null);
const modalDetalleAbierto = ref(false);

// Filtros reactivos
const estadoFiltro = computed(() => props.filtros?.estado || 'todos');
const formIdFiltro = ref(props.filtros?.form_id ? String(props.filtros.form_id) : 'todos');
const textoBusqueda = ref(props.filtros?.buscar || '');

// Opciones para el selector de formularios (Regla PrimeVue 4 Selects: sin valores vacíos)
const opcionesFormularios = computed(() => [
    { label: 'Todos los formularios', value: 'todos' },
    ...(props.formulariosDisponibles || []).map(f => ({
        label: f.name,
        value: String(f.id),
    })),
]);

/**
 * Aplicar navegación fluida con filtros combinados.
 */
function aplicarFiltros(sobreescribir = {}) {
    const params = {
        estado: estadoFiltro.value,
        form_id: formIdFiltro.value,
        buscar: textoBusqueda.value?.trim() || undefined,
        ...sobreescribir,
    };

    if (params.estado === 'todos') delete params.estado;
    if (params.form_id === 'todos') delete params.form_id;
    if (!params.buscar) delete params.buscar;

    // Si estamos en la vista de formulario concreto y no se cambia de formulario
    if (formularioId.value && !esVistaGlobal.value && (!sobreescribir.form_id || sobreescribir.form_id === String(formularioId.value))) {
        router.get(
            route('panel.formularios.submissions', formularioId.value),
            params,
            { preserveState: true, replace: true }
        );
    } else {
        router.get(
            route('panel.formularios.submissions.all'),
            params,
            { preserveState: true, replace: true }
        );
    }
}

function cambiarFiltroEstado(nuevoEstado) {
    aplicarFiltros({ estado: nuevoEstado });
}

function cambiarFiltroFormulario(event) {
    const nuevoFormId = event.value;
    formIdFiltro.value = nuevoFormId;
    aplicarFiltros({ form_id: nuevoFormId });
}

function ejecutarBusqueda() {
    aplicarFiltros();
}

function limpiarBusqueda() {
    textoBusqueda.value = '';
    aplicarFiltros({ buscar: undefined });
}

function verDetalle(envio) {
    envioSeleccionado.value = envio;
    modalDetalleAbierto.value = true;

    // Si no está leído, marcarlo como leído silenciosamente vía Axios sin recargar la página
    if (!envio.is_read) {
        envio.is_read = true;
        const formId = envio.form_id || formularioId.value;
        const url = formId
            ? route('panel.formularios.submissions.mark-read', [formId, envio.id])
            : route('panel.formularios.submissions.mark-read-direct', envio.id);

        axios.patch(url).catch(() => {});
    }
}

function marcarSpam(envio) {
    if (!envio?.id) return;
    const formId = envio.form_id || formularioId.value;
    const url = formId
        ? route('panel.formularios.submissions.spam', [formId, envio.id])
        : route('panel.formularios.submissions.spam-direct', envio.id);

    router.patch(url, {}, { preserveScroll: true });
}

function eliminarEnvio(envio) {
    if (!envio?.id) return;
    confirm.require({
        message: `¿Eliminar permanentemente el envío #${envio.id}?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            const formId = envio.form_id || formularioId.value;
            const url = formId
                ? route('panel.formularios.submissions.destroy', [formId, envio.id])
                : route('panel.formularios.submissions.destroy-direct', envio.id);

            router.delete(url, {
                preserveScroll: true,
                onSuccess: () => {
                    if (envioSeleccionado.value?.id === envio.id) {
                        modalDetalleAbierto.value = false;
                    }
                }
            });
        },
    });
}

function exportarCsv() {
    if (formularioId.value && !esVistaGlobal.value) {
        window.location.href = route('panel.formularios.export-csv', formularioId.value);
    } else {
        const params = new URLSearchParams();
        if (formIdFiltro.value && formIdFiltro.value !== 'todos') {
            params.append('form_id', formIdFiltro.value);
        }
        if (estadoFiltro.value && estadoFiltro.value !== 'todos') {
            params.append('estado', estadoFiltro.value);
        }
        const qs = params.toString() ? `?${params.toString()}` : '';
        window.location.href = route('panel.formularios.submissions.export-all-csv') + qs;
    }
}

function formatearFecha(isoString) {
    if (!isoString) return '—';
    const fecha = new Date(isoString);
    return fecha.toLocaleString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Extrae un resumen legible de los campos principales para la fila de la tabla.
 */
function resumenDatos(data) {
    if (!data || typeof data !== 'object') return '—';
    const claves = Object.keys(data);
    if (!claves.length) return '—';

    // Priorizar campos comunes: nombre, email, telefono, asunto
    const prioritarias = ['nombre', 'nombre_completo', 'name', 'email', 'telefono', 'phone', 'asunto', 'subject', 'mensaje', 'message'];
    const encontradas = prioritarias.filter(k => data[k]);

    if (encontradas.length > 0) {
        return encontradas.map(k => `${data[k]}`).join(' • ');
    }

    return claves.slice(0, 2).map(k => {
        const val = data[k];
        return typeof val === 'object' ? JSON.stringify(val) : `${val}`;
    }).join(' • ');
}
</script>

<template>
    <Head :title="esVistaGlobal ? 'Bandeja Global de Envíos' : `Envíos: ${formObj.name || ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <Link
                        :href="esVistaGlobal ? route('dashboard') : route('panel.formularios.index')"
                        class="p-1.5 sm:p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors shrink-0 flex items-center justify-center"
                        :title="esVistaGlobal ? 'Volver al panel de control' : 'Volver a formularios'"
                    >
                        <i class="pi pi-arrow-left text-xs sm:text-sm" />
                    </Link>
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        {{ esVistaGlobal ? 'Bandeja de Envíos' : `Envíos: ${formObj.name}` }}
                    </h1>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 w-full space-y-6">
            <PageHeader
                icon="pi pi-inbox"
                :title="esVistaGlobal ? 'Bandeja Global de Envíos' : `Envíos: ${formObj.name}`"
                :description="esVistaGlobal ? 'Supervisa y responde todos los mensajes y leads captados desde los formularios' : 'Bandeja de mensajes y solicitudes recibidas'"
                divider
            >
                <template v-if="!esVistaGlobal && formObj.slug" #badge>
                    <span class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-xs font-mono text-zinc-500 dark:text-zinc-400">
                        /{{ formObj.slug }}
                    </span>
                </template>

                <template #actions>
                    <Button
                        label="Exportar CSV"
                        icon="pi pi-download"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="exportarCsv"
                    />
                    <Link v-if="formularioId && !esVistaGlobal" :href="route('panel.formularios.edit', formularioId)">
                        <Button
                            label="Editar Formulario"
                            icon="pi pi-pencil"
                            severity="emerald"
                            size="small"
                        />
                    </Link>
                    <Link v-if="esVistaGlobal" :href="route('panel.formularios.index')">
                        <Button
                            label="Gestionar Formularios"
                            icon="pi pi-file-check"
                            severity="secondary"
                            outlined
                            size="small"
                        />
                    </Link>
                </template>
            </PageHeader>
            <!-- Barra superior: Filtros por formulario, buscador y estado -->
            <div class="flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-4 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <!-- Filtros izquierdos: Selector de Formulario y Buscador -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Selector de Formulario -->
                    <div v-if="formulariosDisponibles?.length > 0" class="w-full sm:w-64">
                        <Select
                            v-model="formIdFiltro"
                            :options="opcionesFormularios"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filtrar por formulario"
                            class="w-full text-xs"
                            @change="cambiarFiltroFormulario"
                        />
                    </div>

                    <!-- Buscador -->
                    <div class="relative w-full sm:w-72">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs" />
                        <InputText
                            v-model="textoBusqueda"
                            placeholder="Buscar en mensajes, email o IP..."
                            class="w-full pl-8 pr-7 text-xs"
                            @keydown.enter="ejecutarBusqueda"
                        />
                        <button
                            v-if="textoBusqueda"
                            @click="limpiarBusqueda"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            title="Limpiar búsqueda"
                        >
                            <i class="pi pi-times text-[10px]" />
                        </button>
                    </div>

                    <Button
                        label="Buscar"
                        icon="pi pi-search"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="ejecutarBusqueda"
                    />
                </div>

                <!-- Filtros por estado -->
                <div class="flex items-center gap-1.5 overflow-x-auto shrink-0">
                    <button
                        type="button"
                        @click="cambiarFiltroEstado('todos')"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                        :class="estadoFiltro === 'todos'
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                    >
                        <i class="pi pi-inbox text-xs" />
                        <span>Todos</span>
                        <span v-if="estadisticas?.total !== undefined" class="text-[11px] px-1.5 py-0.2 rounded-full" :class="estadoFiltro === 'todos' ? 'bg-white/20 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'">
                            {{ estadisticas.total }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="cambiarFiltroEstado('no_leidos')"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                        :class="estadoFiltro === 'no_leidos'
                            ? 'bg-rose-600 text-white shadow-sm'
                            : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                    >
                        <i class="pi pi-envelope text-xs" />
                        <span>No leídos</span>
                        <span v-if="estadisticas?.no_leidos !== undefined" class="text-[11px] px-1.5 py-0.2 rounded-full font-bold" :class="estadoFiltro === 'no_leidos' ? 'bg-white/20 text-white' : (estadisticas.no_leidos > 0 ? 'bg-rose-500 text-white animate-pulse' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300')">
                            {{ estadisticas.no_leidos }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="cambiarFiltroEstado('leidos')"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                        :class="estadoFiltro === 'leidos'
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                    >
                        <i class="pi pi-envelope-open text-xs" />
                        <span>Leídos</span>
                    </button>

                    <button
                        type="button"
                        @click="cambiarFiltroEstado('spam')"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                        :class="estadoFiltro === 'spam'
                            ? 'bg-amber-600 text-white shadow-sm'
                            : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                    >
                        <i class="pi pi-ban text-xs" />
                        <span>Spam</span>
                        <span v-if="estadisticas?.spam !== undefined && estadisticas.spam > 0" class="text-[11px] px-1.5 py-0.2 rounded-full bg-amber-500/20 text-amber-700 dark:text-amber-300">
                            {{ estadisticas.spam }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Tabla de envíos -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="w-12 text-center py-3.5 px-3">#</th>
                            <th v-if="esVistaGlobal" class="text-left px-4 py-3.5 font-medium">Formulario</th>
                            <th class="text-left px-4 py-3.5 font-medium">Datos del Remitente</th>
                            <th class="text-left px-4 py-3.5 font-medium">IP</th>
                            <th class="text-center px-4 py-3.5 font-medium">Estado</th>
                            <th class="text-left px-4 py-3.5 font-medium">Recibido</th>
                            <th class="text-right px-5 py-3.5 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        <tr
                            v-for="envio in envios.data"
                            :key="envio.id"
                            @click="verDetalle(envio)"
                            class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors cursor-pointer group"
                            :class="{'font-medium bg-rose-50/20 dark:bg-rose-950/10': !envio.is_read}"
                        >
                            <!-- Indicador y ID -->
                            <td class="text-center py-4 px-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span
                                        v-if="!envio.is_read"
                                        class="w-2 h-2 rounded-full bg-rose-500 shrink-0 animate-ping"
                                        title="Nuevo envío sin leer"
                                    />
                                    <span class="text-xs text-zinc-400 font-mono">
                                        #{{ envio.id }}
                                    </span>
                                </div>
                            </td>

                            <!-- Formulario de origen (en vista global) -->
                            <td v-if="esVistaGlobal" class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800/60">
                                    <i class="pi pi-file-check text-[10px]" />
                                    <span>{{ envio.form?.name || 'Formulario' }}</span>
                                </span>
                            </td>

                            <!-- Resumen Datos -->
                            <td class="px-4 py-4">
                                <div class="flex flex-col">
                                    <span class="text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors line-clamp-1">
                                        {{ resumenDatos(envio.data) }}
                                    </span>
                                </div>
                            </td>

                            <!-- IP -->
                            <td class="px-4 py-4 text-xs font-mono text-zinc-500 dark:text-zinc-400">
                                {{ envio.ip_address }}
                            </td>

                            <!-- Badge Estado -->
                            <td class="px-4 py-4 text-center">
                                <Tag
                                    v-if="envio.is_spam"
                                    value="Spam"
                                    severity="danger"
                                    size="small"
                                />
                                <Tag
                                    v-else-if="!envio.is_read"
                                    value="Nuevo"
                                    severity="danger"
                                    size="small"
                                />
                                <Tag
                                    v-else
                                    value="Leído"
                                    severity="secondary"
                                    size="small"
                                />
                            </td>

                            <!-- Fecha y Hora -->
                            <td class="px-4 py-4 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ formatearFecha(envio.created_at) }}
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-4 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        icon="pi pi-eye"
                                        severity="secondary"
                                        text
                                        rounded
                                        @click="verDetalle(envio)"
                                        title="Ver detalle completo"
                                    />
                                    <Button
                                        :icon="envio.is_spam ? 'pi pi-check' : 'pi pi-ban'"
                                        :severity="envio.is_spam ? 'success' : 'warn'"
                                        text
                                        rounded
                                        @click="marcarSpam(envio)"
                                        :title="envio.is_spam ? 'Desmarcar spam' : 'Marcar como spam'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        @click="eliminarEnvio(envio)"
                                        title="Eliminar envío"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Estado vacío -->
                <div v-if="!envios.data?.length" class="text-center py-16 bg-white dark:bg-zinc-900">
                    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-4 text-zinc-400">
                        <i class="pi pi-inbox text-2xl" />
                    </div>
                    <p class="text-zinc-900 dark:text-zinc-100 font-semibold text-base">
                        No hay envíos en esta bandeja
                    </p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                        {{ esVistaGlobal ? 'Los mensajes enviados por los usuarios a través de los formularios del sitio web aparecerán aquí.' : 'Los mensajes enviados por los usuarios a través de este formulario aparecerán aquí en tiempo real.' }}
                    </p>
                </div>
            </div>

            <!-- Paginación -->
            <nav v-if="envios.last_page > 1" class="mt-6 flex justify-center gap-1">
                <template v-for="link in envios.links" :key="link.label">
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

        <!-- Diálogo Modal: Detalle del envío -->
        <Dialog
            v-model:visible="modalDetalleAbierto"
            modal
            :header="`Envío #${envioSeleccionado?.id} — ${envioSeleccionado?.form?.name || formObj.name || 'Detalle'}`"
            :style="{ width: '90vw', maxWidth: '680px' }"
        >
            <div v-if="envioSeleccionado" class="space-y-6 text-sm">
                <!-- Metadatos de seguridad y origen -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-800 text-xs">
                    <div v-if="envioSeleccionado.form?.name">
                        <span class="text-zinc-400 block">Formulario</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ envioSeleccionado.form.name }}
                        </span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block">Fecha y Hora</span>
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">
                            {{ formatearFecha(envioSeleccionado.created_at) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block">Dirección IP</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">
                            {{ envioSeleccionado.ip_address }}
                        </span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block">Estado</span>
                        <span class="font-medium" :class="envioSeleccionado.is_spam ? 'text-red-500' : 'text-emerald-500'">
                            {{ envioSeleccionado.is_spam ? 'Marcado como Spam' : 'Válido' }}
                        </span>
                    </div>
                    <div v-if="envioSeleccionado.referer_url" class="col-span-2 sm:col-span-3">
                        <span class="text-zinc-400 block">Página de origen (Referer)</span>
                        <span class="font-mono text-zinc-600 dark:text-zinc-400 break-all">
                            {{ envioSeleccionado.referer_url }}
                        </span>
                    </div>
                </div>

                <!-- Campos enviados -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Valores enviados por el usuario
                    </h3>

                    <div class="divide-y divide-zinc-200 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden bg-white dark:bg-zinc-900">
                        <div
                            v-for="(valor, campo) in envioSeleccionado.data"
                            :key="campo"
                            class="p-3.5 flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4"
                        >
                            <span class="font-mono text-xs font-semibold text-zinc-500 dark:text-zinc-400 sm:w-1/3 shrink-0">
                                {{ campo }}
                            </span>
                            <div class="text-zinc-900 dark:text-zinc-100 flex-1 break-words">
                                <template v-if="Array.isArray(valor)">
                                    <ul class="list-disc list-inside space-y-0.5">
                                        <li v-for="(item, idx) in valor" :key="idx">{{ item }}</li>
                                    </ul>
                                </template>
                                <template v-else-if="typeof valor === 'boolean'">
                                    <Tag :value="valor ? 'Sí (Marcado)' : 'No'" :severity="valor ? 'success' : 'secondary'" size="small" />
                                </template>
                                <template v-else>
                                    <p class="whitespace-pre-wrap">{{ valor || '—' }}</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full">
                    <Button
                        :label="envioSeleccionado?.is_spam ? 'Desmarcar Spam' : 'Marcar Spam'"
                        :icon="envioSeleccionado?.is_spam ? 'pi pi-check' : 'pi pi-ban'"
                        :severity="envioSeleccionado?.is_spam ? 'success' : 'warn'"
                        text
                        size="small"
                        @click="marcarSpam(envioSeleccionado)"
                    />
                    <div class="flex gap-2">
                        <Button
                            label="Eliminar"
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            size="small"
                            @click="eliminarEnvio(envioSeleccionado)"
                        />
                        <Button
                            label="Cerrar"
                            icon="pi pi-times"
                            severity="secondary"
                            size="small"
                            @click="modalDetalleAbierto = false"
                        />
                    </div>
                </div>
            </template>
        </Dialog>
    </AuthenticatedLayout>
</template>
