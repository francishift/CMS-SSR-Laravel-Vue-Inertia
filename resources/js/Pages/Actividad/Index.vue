<script setup>
/**
 * Centro Unificado de Actividad, Telemetría IP de Descargas y Auditoría del CMS (Spatie Activitylog).
 *
 * Exclusivo para administradores (`role:admin`). Combina:
 * 1. Registro detallado de descargas con IP real de origen, navegador/SO, ranking perpetuo
 *    y limpieza selectiva sin perder jamás los contadores históricos globales ni por ítem.
 * 2. Registro de auditoría de acciones administrativas e inicios de sesión con comparador Antes/Después.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, computed, watch } from 'vue';

import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';

const confirm = useConfirm();

const props = defineProps({
    tabActiva: { type: String, default: 'descargas' },
    metricasDescargas: { type: Object, required: true },
    logsDescargas: { type: Object, required: true },
    logsAuditoria: { type: Object, required: true },
    totalAuditoria: { type: Number, default: 0 },
    filtros: { type: Object, default: () => ({}) },
});

const tab = ref(props.tabActiva || 'descargas');
watch(() => props.tabActiva, (v) => {
    if (v) tab.value = v;
});

// ─── Filtros Pestaña 1: Descargas e IPs (Regla PrimeVue 4: nunca '') ───
const dlSearch = ref(props.filtros.dl_search || '');
const dlItem = ref(props.filtros.dl_item || 'all');
const dlPeriod = ref(props.filtros.dl_period || 'all');
const modoPurgaDescargas = ref('older_90');
const ipCopiadaId = ref(null);

const opcionesItemsDescarga = computed(() => [
    { label: 'Todas las descargas', value: 'all' },
    ...(props.metricasDescargas?.ranking_items || []).map(item => ({
        label: `${item.title} (${item.downloads_count})`,
        value: String(item.id),
    })),
]);

const opcionesPeriodo = [
    { label: 'Cualquier fecha', value: 'all' },
    { label: 'Hoy', value: 'today' },
    { label: 'Últimos 7 días', value: '7d' },
    { label: 'Últimos 30 días', value: '30d' },
    { label: 'Últimos 90 días', value: '90d' },
];

const opcionesPurga = [
    { label: 'Más antiguos de 90 días', value: 'older_90' },
    { label: 'Más antiguos de 30 días', value: 'older_30' },
    { label: 'Más antiguos de 7 días', value: 'older_7' },
    { label: 'Vaciar todos los logs detallados', value: 'all' },
];

// ─── Filtros Pestaña 2: Auditoría Spatie (Regla PrimeVue 4: nunca '') ───
const actSearch = ref(props.filtros.act_search || '');
const actModule = ref(props.filtros.act_module || 'all');
const actEvent = ref(props.filtros.act_event || 'all');
const modoPurgaAuditoria = ref('older_90');

const opcionesModulos = [
    { label: 'Todos los módulos', value: 'all' },
    { label: 'Contenido (Artículos / Páginas / Menús)', value: 'contenido' },
    { label: 'Descargas', value: 'descargas' },
    { label: 'Maquetador Visual (Builder)', value: 'maquetador' },
    { label: 'Seguridad y Accesos', value: 'seguridad' },
    { label: 'Usuarios', value: 'usuarios' },
    { label: 'Configuración Global', value: 'configuracion' },
];

const opcionesEventos = [
    { label: 'Todos los eventos', value: 'all' },
    { label: 'Creación', value: 'created' },
    { label: 'Actualización', value: 'updated' },
    { label: 'Eliminación / Papelera', value: 'deleted' },
    { label: 'Inicio de sesión', value: 'login' },
    { label: 'Intento fallido de acceso', value: 'login_failed' },
    { label: 'Limpieza de logs', value: 'purged' },
];

// Modal de inspección de cambios Antes vs Después
const registroSeleccionado = ref(null);
const mostrarModalCambios = ref(false);

function abrirDetalleCambios(log) {
    registroSeleccionado.value = log;
    mostrarModalCambios.value = true;
}

function construirQueryParams(extra = {}) {
    return {
        tab: tab.value,
        dl_search: dlSearch.value || undefined,
        dl_item: dlItem.value !== 'all' ? dlItem.value : undefined,
        dl_period: dlPeriod.value !== 'all' ? dlPeriod.value : undefined,
        act_search: actSearch.value || undefined,
        act_module: actModule.value !== 'all' ? actModule.value : undefined,
        act_event: actEvent.value !== 'all' ? actEvent.value : undefined,
        ...extra,
    };
}

function navegarConFiltros(extra = {}) {
    router.get(route('actividad.index'), construirQueryParams(extra), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function cambiarTab(nuevaTab) {
    tab.value = nuevaTab;
    navegarConFiltros({ tab: nuevaTab });
}

let timerDl = null;
watch(dlSearch, () => {
    clearTimeout(timerDl);
    timerDl = setTimeout(() => navegarConFiltros({ dl_page: undefined }), 320);
});

let timerAct = null;
watch(actSearch, () => {
    clearTimeout(timerAct);
    timerAct = setTimeout(() => navegarConFiltros({ act_page: undefined }), 320);
});

function filtrarPorIp(ip) {
    if (!ip || ip.startsWith('IP anonimizada')) return;
    dlSearch.value = ip;
}

function filtrarPorItemRanking(itemId) {
    const idStr = String(itemId);
    dlItem.value = dlItem.value === idStr ? 'all' : idStr;
    navegarConFiltros({ dl_page: undefined });
}

function limpiarFiltrosDescargas() {
    dlSearch.value = '';
    dlItem.value = 'all';
    dlPeriod.value = 'all';
    navegarConFiltros({ dl_search: undefined, dl_item: undefined, dl_period: undefined, dl_page: undefined });
}

function limpiarFiltrosAuditoria() {
    actSearch.value = '';
    actModule.value = 'all';
    actEvent.value = 'all';
    navegarConFiltros({ act_search: undefined, act_module: undefined, act_event: undefined, act_page: undefined });
}

async function copiarIp(ip, rowId) {
    if (!ip || typeof navigator === 'undefined' || !navigator.clipboard) return;
    try {
        await navigator.clipboard.writeText(ip);
        ipCopiadaId.value = rowId;
        setTimeout(() => {
            if (ipCopiadaId.value === rowId) ipCopiadaId.value = null;
        }, 1800);
    } catch {
        // Silencioso si el navegador restringe portapapeles
    }
}

function confirmarPurgaDescargas() {
    const etiqueta = opcionesPurga.find(o => o.value === modoPurgaDescargas.value)?.label || '';
    confirm.require({
        header: 'Confirmar limpieza de registros IP de descargas',
        message: `Se eliminarán los registros detallados (${etiqueta.toLowerCase()}). El contador histórico general (${props.metricasDescargas.historico_global}) y el total acumulado de cada descarga se mantendrán 100 % intactos.`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Cancelar', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Limpiar registros', severity: 'danger' },
        accept: () => {
            router.delete(route('actividad.purge-downloads'), {
                data: { mode: modoPurgaDescargas.value },
                preserveScroll: true,
            });
        },
    });
}

function confirmarPurgaAuditoria() {
    const etiqueta = opcionesPurga.find(o => o.value === modoPurgaAuditoria.value)?.label || '';
    confirm.require({
        header: 'Confirmar limpieza de auditoría del CMS',
        message: `¿Seguro que deseas purgar los eventos de auditoría administrativa (${etiqueta.toLowerCase()})?`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Cancelar', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Purgar auditoría', severity: 'danger' },
        accept: () => {
            router.delete(route('actividad.purge-activity'), {
                data: { mode: modoPurgaAuditoria.value },
                preserveScroll: true,
            });
        },
    });
}

function badgeEvento(evento) {
    return matchEvento[evento] || { label: evento || 'Evento', severity: 'secondary' };
}

const matchEvento = {
    created:      { label: 'Creado', severity: 'success' },
    updated:      { label: 'Editado', severity: 'info' },
    deleted:      { label: 'Eliminado', severity: 'danger' },
    restored:     { label: 'Restaurado', severity: 'warn' },
    login:        { label: 'Acceso OK', severity: 'success' },
    login_failed: { label: 'Fallo Acceso', severity: 'danger' },
    purged:       { label: 'Limpieza', severity: 'warn' },
};

const hayFiltrosDl = computed(() =>
    Boolean(dlSearch.value || dlItem.value !== 'all' || dlPeriod.value !== 'all')
);

const hayFiltrosAct = computed(() =>
    Boolean(actSearch.value || actModule.value !== 'all' || actEvent.value !== 'all')
);
</script>

<template>
    <Head title="Actividad, IPs y Auditoría" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Actividad y Telemetría
                    </h1>
                </div>

                <!-- Conmutador de pestañas principales en Topbar (Solo Desktop lg+) -->
                <div class="hidden lg:inline-flex items-center rounded-xl bg-zinc-200/70 dark:bg-zinc-800 p-1 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="cambiarTab('descargas')"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all cursor-pointer',
                            tab === 'descargas'
                                ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200',
                        ]"
                    >
                        <i class="pi pi-download text-xs" />
                        <span>Descargas e IPs</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            {{ metricasDescargas.historico_global }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="cambiarTab('auditoria')"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all cursor-pointer',
                            tab === 'auditoria'
                                ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200',
                        ]"
                    >
                        <i class="pi pi-shield text-xs" />
                        <span>Auditoría CMS (Spatie)</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-zinc-500/10 text-zinc-600 dark:text-zinc-300">
                            {{ totalAuditoria }}
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 w-full space-y-6">
            <!-- Conmutador de pestañas principales para Móvil y Tablet (< lg) -->
            <div class="flex lg:hidden items-center rounded-xl bg-zinc-200/70 dark:bg-zinc-800 p-1 gap-1 w-full">
                <button
                    type="button"
                    @click="cambiarTab('descargas')"
                    :class="[
                        'flex-1 px-2.5 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer min-w-0',
                        tab === 'descargas'
                            ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                            : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200',
                    ]"
                >
                    <i class="pi pi-download text-xs shrink-0" />
                    <span class="truncate">Descargas e IPs</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                        {{ metricasDescargas.historico_global }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="cambiarTab('auditoria')"
                    :class="[
                        'flex-1 px-2.5 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer min-w-0',
                        tab === 'auditoria'
                            ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                            : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200',
                    ]"
                >
                    <i class="pi pi-shield text-xs shrink-0" />
                    <span class="truncate">Auditoría CMS</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-zinc-500/10 text-zinc-600 dark:text-zinc-300 shrink-0">
                        {{ totalAuditoria }}
                    </span>
                </button>
            </div>

            <!-- Cabecera contextual de sección -->
            <PageHeader
                icon="pi pi-history"
                title="Centro de Actividad y Telemetría"
                description="Control exclusivo de Administrador · IPs de descargas, históricos perpetuos y auditoría Spatie"
                divider
            />
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- PESTAÑA 1: TELEMETRÍA DE DESCARGAS, DIRECCIONES IP Y RANKING        -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <template v-if="tab === 'descargas'">
                <!-- Tarjetas KPI superiores -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="rounded-2xl border border-emerald-500/25 bg-white dark:bg-zinc-900 p-5 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Histórico Total Global
                            </span>
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <i class="pi pi-chart-line text-sm" />
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white font-mono">
                            {{ metricasDescargas.historico_global }}
                        </p>
                        <p class="mt-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i class="pi pi-lock text-[10px]" />
                            <span>Acumulado perpetuo (inmune a limpiezas)</span>
                        </p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Últimos 30 días
                            </span>
                            <span class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                                <i class="pi pi-calendar text-sm" />
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white font-mono">
                            {{ metricasDescargas.descargas_30d }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                            Últimos 7 días: <strong class="text-zinc-800 dark:text-zinc-200">{{ metricasDescargas.descargas_7d }}</strong> descargas
                        </p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                IPs Únicas (30d)
                            </span>
                            <span class="w-8 h-8 rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                                <i class="pi pi-globe text-sm" />
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white font-mono">
                            {{ metricasDescargas.ips_unicas_30d }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                            Direcciones IP distintas registradas
                        </p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Logs IP en Tabla
                            </span>
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <i class="pi pi-database text-sm" />
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white font-mono">
                            {{ metricasDescargas.logs_almacenados }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                            Auto-limpieza programada &gt; {{ metricasDescargas.retencion_automatica }} días
                        </p>
                    </div>
                </div>

                <!-- Ranking Histórico Perpetuo por Ítem -->
                <div
                    v-if="metricasDescargas.ranking_items?.length"
                    class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-2xs space-y-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <i class="pi pi-trophy text-emerald-500 text-sm" />
                                <span>Acumulado Histórico por Descarga (clic en un ítem para filtrar sus IPs)</span>
                            </h2>
                        </div>
                        <Link
                            :href="route('downloads.admin.index')"
                            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline no-underline"
                        >
                            Ir a Gestión de Descargas →
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        <button
                            v-for="item in metricasDescargas.ranking_items"
                            :key="item.id"
                            type="button"
                            @click="filtrarPorItemRanking(item.id)"
                            :class="[
                                'text-left p-3.5 rounded-xl border transition-all cursor-pointer space-y-2',
                                dlItem === String(item.id)
                                    ? 'border-emerald-500 bg-emerald-500/5 dark:bg-emerald-400/10'
                                    : 'border-zinc-200/80 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/40',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">
                                        {{ item.title }}
                                    </p>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono">
                                        v{{ item.version }} · {{ item.views_count }} vistas
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-base font-black font-mono text-zinc-900 dark:text-white">
                                        {{ item.downloads_count }}
                                    </span>
                                    <span class="block text-[10px] text-zinc-400">
                                        {{ item.share_percent }}%
                                    </span>
                                </div>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                                    :style="{ width: `${item.bar_percent}%` }"
                                />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Barra de filtros + Purgado Inteligente -->
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-2xs flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        <IconField class="w-full sm:w-64">
                            <InputIcon class="pi pi-search" />
                            <InputText
                                v-model="dlSearch"
                                placeholder="Buscar IP, plugin, navegador..."
                                size="small"
                                class="w-full"
                            />
                        </IconField>

                        <Select
                            v-model="dlItem"
                            :options="opcionesItemsDescarga"
                            optionLabel="label"
                            optionValue="value"
                            size="small"
                            class="w-full sm:w-56"
                            @change="navegarConFiltros({ dl_page: undefined })"
                        />

                        <Select
                            v-model="dlPeriod"
                            :options="opcionesPeriodo"
                            optionLabel="label"
                            optionValue="value"
                            size="small"
                            class="w-full sm:w-44"
                            @change="navegarConFiltros({ dl_page: undefined })"
                        />

                        <Button
                            v-if="hayFiltrosDl"
                            label="Limpiar filtros"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            text
                            size="small"
                            @click="limpiarFiltrosDescargas"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-3 xl:pt-0 border-t xl:border-t-0 border-zinc-100 dark:border-zinc-800">
                        <Select
                            v-model="modoPurgaDescargas"
                            :options="opcionesPurga"
                            optionLabel="label"
                            optionValue="value"
                            size="small"
                            class="w-full sm:w-60"
                        />
                        <Button
                            label="Purgar IPs"
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            size="small"
                            :disabled="metricasDescargas.logs_almacenados === 0"
                            @click="confirmarPurgaDescargas"
                        />
                    </div>
                </div>

                <!-- Tabla de Eventos de Descarga e IPs -->
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs sm:text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/80 dark:bg-zinc-900/60 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    <th class="py-3.5 px-4">Fecha / Hora</th>
                                    <th class="py-3.5 px-4">Descarga</th>
                                    <th class="py-3.5 px-4">Dirección IP</th>
                                    <th class="py-3.5 px-4">Dispositivo / Navegador</th>
                                    <th class="py-3.5 px-4">Origen (Referer)</th>
                                    <th class="py-3.5 px-4">Usuario</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                                <tr v-if="!logsDescargas.data?.length">
                                    <td colspan="6" class="py-12 text-center text-zinc-500 dark:text-zinc-400 text-sm">
                                        <i class="pi pi-inbox text-2xl mb-2 block opacity-50" />
                                        No hay registros detallados de descargas para los filtros actuales.
                                    </td>
                                </tr>

                                <tr
                                    v-for="row in logsDescargas.data"
                                    :key="row.id"
                                    class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors"
                                >
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="block font-semibold text-zinc-800 dark:text-zinc-200 text-xs">
                                            {{ row.created_at_exact }}
                                        </span>
                                        <span class="text-[11px] text-zinc-400">
                                            {{ row.created_at_human }}
                                        </span>
                                    </td>

                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-zinc-900 dark:text-zinc-100 text-xs sm:text-sm">
                                                {{ row.item_title }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                                v{{ row.version_downloaded }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div v-if="row.has_plain_ip" class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="filtrarPorIp(row.ip_address)"
                                                class="px-2.5 py-1 rounded-lg font-mono text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors cursor-pointer"
                                                title="Clic para filtrar todas las descargas desde esta IP"
                                            >
                                                {{ row.ip_address }}
                                            </button>
                                            <span
                                                v-if="row.is_private_ip"
                                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
                                                title="Conexión a través de túnel VPN (ej. WireGuard) o red interna del servidor"
                                            >
                                                VPN / Interna
                                            </span>
                                            <button
                                                type="button"
                                                @click="copiarIp(row.ip_address, row.id)"
                                                class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                                title="Copiar IP"
                                            >
                                                <i :class="[ipCopiadaId === row.id ? 'pi pi-check text-emerald-500' : 'pi pi-copy', 'text-xs']" />
                                            </button>
                                        </div>
                                        <span
                                            v-else
                                            class="px-2 py-0.5 rounded-md text-[11px] bg-zinc-100 dark:bg-zinc-800 text-zinc-400"
                                            title="Descarga registrada antes de activar el guardado de IP en claro (el hash SHA-256 previo es irreversible)"
                                        >
                                            Anterior a IP activa
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap" :title="row.user_agent">
                                        <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ row.browser }}
                                        </span>
                                        <span class="text-xs text-zinc-400"> · {{ row.os }}</span>
                                    </td>

                                    <td class="py-3 px-4 max-w-[200px] truncate text-xs text-zinc-600 dark:text-zinc-400" :title="row.referer_url || 'Acceso directo'">
                                        {{ row.referer_display }}
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap text-xs">
                                        <span v-if="row.user_name" class="font-semibold text-zinc-800 dark:text-zinc-200" :title="row.user_email">
                                            {{ row.user_name }}
                                        </span>
                                        <span v-else class="text-zinc-400">Visitante</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Descargas -->
                    <div
                        v-if="logsDescargas.meta?.last_page > 1"
                        class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500"
                    >
                        <span>
                            Página {{ logsDescargas.meta.current_page }} de {{ logsDescargas.meta.last_page }} ({{ logsDescargas.meta.total }} registros)
                        </span>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(lnk, i) in logsDescargas.meta.links" :key="i">
                                <Link
                                    v-if="lnk.url"
                                    :href="lnk.url"
                                    preserve-scroll
                                    preserve-state
                                    :class="[
                                        'px-2.5 py-1 rounded-lg font-semibold no-underline transition-colors',
                                        lnk.active
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700',
                                    ]"
                                    v-html="lnk.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- PESTAÑA 2: AUDITORÍA DE ACCIONES Y ACCESOS (SPATIE ACTIVITYLOG)     -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <template v-else>
                <!-- Barra de filtros + Purgado Auditoría -->
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-2xs flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        <IconField class="w-full sm:w-64">
                            <InputIcon class="pi pi-search" />
                            <InputText
                                v-model="actSearch"
                                placeholder="Buscar acción, usuario, IP..."
                                size="small"
                                class="w-full"
                            />
                        </IconField>

                        <Select
                            v-model="actModule"
                            :options="opcionesModulos"
                            optionLabel="label"
                            optionValue="value"
                            size="small"
                            class="w-full sm:w-56"
                            @change="navegarConFiltros({ act_page: undefined })"
                        />

                        <Select
                            v-model="actEvent"
                            :options="opcionesEventos"
                            optionLabel="label"
                            optionValue="value"
                            size="small"
                            class="w-full sm:w-48"
                            @change="navegarConFiltros({ act_page: undefined })"
                        />

                        <Button
                            v-if="hayFiltrosAct"
                            label="Limpiar filtros"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            text
                            size="small"
                            @click="limpiarFiltrosAuditoria"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-3 xl:pt-0 border-t xl:border-t-0 border-zinc-100 dark:border-zinc-800">
                        <Select
                            v-model="modoPurgaAuditoria"
                            :options="opcionesPurga"
                            optionLabel="label"
                            optionValue="value"
                            size="small"
                            class="w-full sm:w-60"
                        />
                        <Button
                            label="Purgar Auditoría"
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            size="small"
                            :disabled="totalAuditoria === 0"
                            @click="confirmarPurgaAuditoria"
                        />
                    </div>
                </div>

                <!-- Tabla Spatie Activitylog -->
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs sm:text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/80 dark:bg-zinc-900/60 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    <th class="py-3.5 px-4">Fecha / Hora</th>
                                    <th class="py-3.5 px-4">Módulo / Evento</th>
                                    <th class="py-3.5 px-4">Descripción</th>
                                    <th class="py-3.5 px-4">Autor</th>
                                    <th class="py-3.5 px-4">IP</th>
                                    <th class="py-3.5 px-4 text-right">Detalle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                                <tr v-if="!logsAuditoria.data?.length">
                                    <td colspan="6" class="py-12 text-center text-zinc-500 dark:text-zinc-400 text-sm">
                                        <i class="pi pi-shield text-2xl mb-2 block opacity-50" />
                                        Aún no hay eventos registrados para los filtros seleccionados.
                                    </td>
                                </tr>

                                <tr
                                    v-for="act in logsAuditoria.data"
                                    :key="act.id"
                                    class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors"
                                >
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="block font-semibold text-zinc-800 dark:text-zinc-200 text-xs">
                                            {{ act.created_at_exact }}
                                        </span>
                                        <span class="text-[11px] text-zinc-400">
                                            {{ act.created_at_human }}
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                                {{ act.log_name }}
                                            </span>
                                            <Tag :value="badgeEvento(act.event).label" :severity="badgeEvento(act.event).severity" />
                                        </div>
                                    </td>

                                    <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 text-xs sm:text-sm">
                                        {{ act.description }}
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap text-xs">
                                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ act.causer_name }}</span>
                                        <span v-if="act.causer_email && act.causer_email !== act.causer_name" class="block text-[11px] text-zinc-400">
                                            {{ act.causer_email }}
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ act.ip_address || '—' }}
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap text-right">
                                        <Button
                                            v-if="act.tiene_cambios"
                                            label="Ver cambios"
                                            icon="pi pi-eye"
                                            size="small"
                                            severity="secondary"
                                            outlined
                                            @click="abrirDetalleCambios(act)"
                                        />
                                        <span v-else class="text-xs text-zinc-400">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Auditoría -->
                    <div
                        v-if="logsAuditoria.meta?.last_page > 1"
                        class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500"
                    >
                        <span>
                            Página {{ logsAuditoria.meta.current_page }} de {{ logsAuditoria.meta.last_page }} ({{ logsAuditoria.meta.total }} eventos)
                        </span>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(lnk, i) in logsAuditoria.meta.links" :key="i">
                                <Link
                                    v-if="lnk.url"
                                    :href="lnk.url"
                                    preserve-scroll
                                    preserve-state
                                    :class="[
                                        'px-2.5 py-1 rounded-lg font-semibold no-underline transition-colors',
                                        lnk.active
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700',
                                    ]"
                                    v-html="lnk.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal Antes vs Después de atributos modificados -->
        <Dialog
            v-model:visible="mostrarModalCambios"
            modal
            :header="registroSeleccionado?.description || 'Detalle de cambios'"
            :style="{ width: '44rem', maxWidth: '95vw' }"
        >
            <div v-if="registroSeleccionado" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400 pb-2 border-b border-zinc-200 dark:border-zinc-800">
                    <span>Autor: <strong class="text-zinc-800 dark:text-zinc-200">{{ registroSeleccionado.causer_name }}</strong></span>
                    <span>Fecha: <strong class="text-zinc-800 dark:text-zinc-200">{{ registroSeleccionado.created_at_exact }}</strong></span>
                    <span v-if="registroSeleccionado.ip_address">IP: <code class="font-mono">{{ registroSeleccionado.ip_address }}</code></span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 font-bold text-zinc-500 uppercase">
                                <th class="py-2.5 px-3">Campo</th>
                                <th class="py-2.5 px-3">Valor Anterior</th>
                                <th class="py-2.5 px-3">Valor Nuevo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="(chg, idx) in registroSeleccionado.cambios" :key="idx">
                                <td class="py-2.5 px-3 font-mono font-semibold text-zinc-800 dark:text-zinc-200 whitespace-nowrap">
                                    {{ chg.campo }}
                                </td>
                                <td class="py-2.5 px-3 text-rose-600 dark:text-rose-400 break-all">
                                    {{ chg.antes }}
                                </td>
                                <td class="py-2.5 px-3 text-emerald-600 dark:text-emerald-400 break-all font-medium">
                                    {{ chg.despues }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>
