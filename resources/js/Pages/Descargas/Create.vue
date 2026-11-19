<script setup>
/**
 * Vista administrativa para crear una nueva descarga.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DownloadItemForm from '@/Pages/Descargas/Partials/DownloadItemForm.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    configuracion: { type: Object, required: true },
    estadosDisponibles: { type: Object, default: () => ({}) },
    tiposAccesoDisponibles: { type: Object, default: () => ({}) },
    limitesSubida: { type: Object, default: () => ({}) },
    monedaPorDefecto: { type: String, default: 'EUR' },
    filtrosRetorno: { type: Object, default: () => ({}) },
});

const urlRetorno = computed(() => route('downloads.admin.index', props.filtrosRetorno || {}));
</script>

<template>
    <Head title="Nueva Descarga — Panel" />

    <AuthenticatedLayout>
        <DownloadItemForm
            modo="create"
            :configuracion="configuracion"
            :estados-disponibles="estadosDisponibles"
            :tipos-acceso-disponibles="tiposAccesoDisponibles"
            :limites-subida="limitesSubida"
            :moneda-por-defecto="monedaPorDefecto"
            :url-retorno="urlRetorno"
        />
    </AuthenticatedLayout>
</template>
