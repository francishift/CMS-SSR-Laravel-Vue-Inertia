<script setup>
import Textarea from 'primevue/textarea';

defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="max-w-4xl space-y-6">
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/80 text-xs text-amber-800 dark:text-amber-300 space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <i class="pi pi-info-circle" />
                <span>Ejecución Segura en el Navegador del Usuario (Frontend)</span>
            </div>
            <p>
                Estos scripts se ejecutan exclusivamente en JavaScript en el navegador del visitante en diferentes fases del ciclo de vida. Ideales para analítica (Google Ads, Meta Pixel), precarga de parámetros o validaciones personalizadas.
            </p>
        </div>

        <!-- 1. Script Antes de Mostrar -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    1. Script Antes de Mostrar el Formulario (scripts_before)
                </label>
                <span class="text-[10px] text-zinc-400 font-mono">Al montar el componente</span>
            </div>
            <Textarea
                v-model="form.scripts_before"
                rows="4"
                placeholder="// console.log('Formulario inicializado');&#10;// Pre-rellenar campos desde parámetros URL..."
                class="w-full font-mono text-xs bg-zinc-900 text-zinc-100 dark:bg-black"
            />
        </div>

        <!-- 2. Script al Enviar (Antes del POST) -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    2. Script al Enviar (scripts_on_submit)
                </label>
                <span class="text-[10px] text-zinc-400 font-mono">Antes de realizar la petición HTTP</span>
            </div>
            <Textarea
                v-model="form.scripts_on_submit"
                rows="4"
                placeholder="// Validaciones personalizadas extra antes de enviar&#10;// if (condicion) return false; // cancela el envío"
                class="w-full font-mono text-xs bg-zinc-900 text-zinc-100 dark:bg-black"
            />
        </div>

        <!-- 3. Script Después del Envío Exitoso -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    3. Script Tras Envío Exitoso (scripts_after)
                </label>
                <span class="text-[10px] text-zinc-400 font-mono">Tras confirmación exitosa del servidor</span>
            </div>
            <Textarea
                v-model="form.scripts_after"
                rows="4"
                placeholder="// gtag('event', 'generate_lead', { form: formSlug });&#10;// fbq('track', 'Lead');"
                class="w-full font-mono text-xs bg-zinc-900 text-zinc-100 dark:bg-black"
            />
        </div>
    </div>
</template>
