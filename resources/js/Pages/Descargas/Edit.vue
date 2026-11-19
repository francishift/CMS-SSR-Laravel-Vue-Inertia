<script setup>
/**
 * Vista administrativa para editar y maquetar una descarga existente.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DownloadItemForm from '@/Pages/Descargas/Partials/DownloadItemForm.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    descarga: { type: Object, required: true },
    configuracion: { type: Object, required: true },
    estadosDisponibles: { type: Object, default: () => ({}) },
    tiposAccesoDisponibles: { type: Object, default: () => ({}) },
    limitesSubida: { type: Object, default: () => ({}) },
    filtrosRetorno: { type: Object, default: () => ({}) },
});

const urlRetorno = computed(() => route('downloads.admin.index', props.filtrosRetorno || {}));
</script>

<template>
    <Head :title="`Editar: ${descarga.title} — Descargas`" />

    <AuthenticatedLayout>
        <DownloadItemForm
            modo="edit"
            :descarga="descarga"
            :configuracion="configuracion"
            :estados-disponibles="estadosDisponibles"
            :tipos-acceso-disponibles="tiposAccesoDisponibles"
            :limites-subida="limitesSubida"
            :url-retorno="urlRetorno"
        />
    </AuthenticatedLayout>
</template>
