<script setup>
/**
 * Re-exportación de compatibilidad para AuthenticatedLayout desde el módulo Admin.
 */
import AuthenticatedLayout from '@/Admin/Layouts/AuthenticatedLayout.vue';
</script>

<template>
    <AuthenticatedLayout>
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>
        <slot />
    </AuthenticatedLayout>
</template>
