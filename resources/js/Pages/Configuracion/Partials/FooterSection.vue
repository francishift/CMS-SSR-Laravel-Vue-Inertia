<script setup>
/**
 * Componente FooterSection: Texto legal de copyright e integración con el constructor visual de pie de página.
 */
import { Link } from '@inertiajs/vue3';
import Textarea from 'primevue/textarea';

const props = defineProps({
    form: { type: Object, required: true },
});
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                <i class="pi pi-file" />
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Pie de Página (Footer)</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Maquetación visual con el Page Builder y textos legales</p>
            </div>
        </div>

        <!-- TARJETA DESTACADA: MAQUETAR CON PAGE BUILDER -->
        <div class="p-5 rounded-2xl bg-gradient-to-r from-zinc-900 via-zinc-900 to-zinc-950 text-white border border-zinc-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-md">
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-emerald-400 flex items-center gap-2">
                    <i class="pi pi-th-large text-base" />
                    <span>Constructor Visual de Pie de Página</span>
                </h3>
                <p class="text-xs text-zinc-300 max-w-xl leading-relaxed">
                    Diseña el pie de página de tu sitio web de forma 100% personalizada agregando secciones, columnas, menús de navegación, iconos de redes sociales y textos legales.
                </p>
            </div>
            <Link
                :href="route('configuracion.footer.builder')"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition-all shadow-sm inline-flex items-center gap-2 cursor-pointer shrink-0"
            >
                <i class="pi pi-pencil text-xs" />
                <span>Maquetar Footer con Builder</span>
            </Link>
        </div>

        <div>
            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Texto Legal del Footer / Copyright Fallback</label>
            <Textarea v-model="form.footer_text" rows="3" class="w-full text-xs" placeholder="ej. © 2026 Mi Empresa. Todos los derechos reservados." />
        </div>
    </section>
</template>
