<script setup>
/**
 * Vista de detalle individual de un envío de formulario.
 *
 * Muestra metadatos del envío (IP, referer, timestamps, estado spam)
 * y los valores enviados por el usuario en formato estructurado.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    formulario: { type: Object, required: true },
    envio:      { type: Object, required: true },
});

const confirm = useConfirm();

// Normalización de props (soporta formato envuelto en .data o resuelto plano)
const formObj = computed(() => props.formulario?.data || props.formulario || {});
const envioObj = computed(() => props.envio?.data || props.envio || {});
const formularioId = computed(() => formObj.value?.id || null);
const envioId = computed(() => envioObj.value?.id || null);

function marcarSpam() {
    if (!formularioId.value || !envioId.value) return;
    router.patch(
        route('panel.formularios.submissions.spam', [formularioId.value, envioId.value]),
        {},
        { preserveScroll: true }
    );
}

function eliminar() {
    if (!formularioId.value || !envioId.value) return;
    confirm.require({
        message: `¿Eliminar permanentemente el envío #${envioId.value}?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(
                route('panel.formularios.submissions.destroy', [formularioId.value, envioId.value]),
                {
                    onSuccess: () => router.get(route('panel.formularios.submissions', formularioId.value)),
                }
            );
        },
    });
}

function formatearFecha(isoString) {
    if (!isoString) return '—';
    return new Date(isoString).toLocaleString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`Envío #${envioObj.id || ''} — ${formObj.name || ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <Link
                        v-if="formularioId"
                        :href="route('panel.formularios.submissions', formularioId)"
                        class="p-1.5 sm:p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors shrink-0 flex items-center justify-center"
                        title="Volver a bandeja de envíos"
                    >
                        <i class="pi pi-arrow-left text-xs sm:text-sm" />
                    </Link>
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Envío #{{ envioObj.id }}
                    </h1>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 max-w-4xl mx-auto space-y-6">
            <PageHeader
                :title="`Envío #${envioObj.id}`"
                :description="`Formulario: ${formObj.name || '—'} (${formObj.slug || '—'})`"
                divider
            >
                <template #actions>
                    <Button
                        :label="envioObj.is_spam ? 'Desmarcar Spam' : 'Marcar Spam'"
                        :icon="envioObj.is_spam ? 'pi pi-check' : 'pi pi-ban'"
                        :severity="envioObj.is_spam ? 'success' : 'warn'"
                        outlined
                        size="small"
                        @click="marcarSpam"
                    />
                    <Button
                        label="Eliminar"
                        icon="pi pi-trash"
                        severity="danger"
                        size="small"
                        @click="eliminar"
                    />
                </template>
            </PageHeader>
            <!-- Metadatos del envío -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm text-sm">
                <div>
                    <span class="text-zinc-400 text-xs block">Fecha y Hora</span>
                    <span class="font-medium text-zinc-800 dark:text-zinc-200">
                        {{ formatearFecha(envioObj.created_at) }}
                    </span>
                </div>
                <div>
                    <span class="text-zinc-400 text-xs block">IP de Origen</span>
                    <span class="font-mono text-xs text-zinc-800 dark:text-zinc-200">
                        {{ envioObj.ip_address }}
                    </span>
                </div>
                <div>
                    <span class="text-zinc-400 text-xs block">Estado</span>
                    <Tag
                        :value="envioObj.is_spam ? 'Spam' : 'Válido'"
                        :severity="envioObj.is_spam ? 'danger' : 'success'"
                        size="small"
                    />
                </div>
                <div>
                    <span class="text-zinc-400 text-xs block">Emails Despachados</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <i
                            class="pi pi-shield text-xs"
                            :class="envioObj.email_sent_admin ? 'text-emerald-500' : 'text-zinc-300 dark:text-zinc-600'"
                            :title="envioObj.email_sent_admin ? 'Notificación a admin enviada' : 'Sin notificación a admin'"
                        />
                        <i
                            class="pi pi-send text-xs"
                            :class="envioObj.email_sent_user ? 'text-purple-500' : 'text-zinc-300 dark:text-zinc-600'"
                            :title="envioObj.email_sent_user ? 'Autorespuesta a usuario enviada' : 'Sin autorespuesta a usuario'"
                        />
                    </div>
                </div>
                <div v-if="envioObj.referer_url" class="col-span-2 sm:col-span-4 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <span class="text-zinc-400 text-xs block">Referer (Página de envío)</span>
                    <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400 break-all">
                        {{ envioObj.referer_url }}
                    </span>
                </div>
            </div>

            <!-- Valores enviados -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                <div class="px-5 py-3.5 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                        Campos y respuestas del usuario
                    </h2>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <div
                        v-for="(valor, campo) in envioObj.data"
                        :key="campo"
                        class="p-4 flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-6"
                    >
                        <span class="font-mono text-xs font-semibold text-zinc-500 dark:text-zinc-400 sm:w-1/3 shrink-0">
                            {{ campo }}
                        </span>
                        <div class="text-zinc-900 dark:text-zinc-100 flex-1 break-words text-sm">
                            <template v-if="Array.isArray(valor)">
                                <ul class="list-disc list-inside space-y-0.5">
                                    <li v-for="(item, idx) in valor" :key="idx">{{ item }}</li>
                                </ul>
                            </template>
                            <template v-else-if="typeof valor === 'boolean'">
                                <Tag :value="valor ? 'Marcado (true)' : 'Desmarcado (false)'" :severity="valor ? 'success' : 'secondary'" size="small" />
                            </template>
                            <template v-else>
                                <p class="whitespace-pre-wrap">{{ valor || '—' }}</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
