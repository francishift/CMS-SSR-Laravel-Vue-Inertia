<script setup>
/**
 * Componente SeoSection: Título, metadescripción por defecto e imagen Open Graph fallback para SEO.
 */
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';

const props = defineProps({
    form: { type: Object, required: true },
});
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg">
                <i class="pi pi-search" />
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">SEO Global Fallback</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Valores de reserva para páginas o entradas que no definan metadatos SEO específicos</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Título SEO por Defecto (máx. 70 caracteres)</label>
                <InputText v-model="form.seo_default_title" maxlength="70" class="w-full text-xs" placeholder="ej. Mi Sitio Web | Desarrollo & Diseño de Software" />
                <p class="text-[11px] text-zinc-500 mt-1 font-mono">{{ (form.seo_default_title || '').length }}/70 caracteres</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Descripción SEO por Defecto (máx. 160 caracteres)</label>
                <Textarea v-model="form.seo_default_description" rows="2" maxlength="160" class="w-full text-xs" placeholder="ej. Descripción breve de la plataforma para los motores de búsqueda." />
                <p class="text-[11px] text-zinc-500 mt-1 font-mono">{{ (form.seo_default_description || '').length }}/160 caracteres</p>
            </div>

            <div>
                <ImageUploader
                    v-model="form.seo_default_og_image"
                    label="Imagen Open Graph por Defecto (Redes Sociales)"
                    help="Recomendado: 1200×630 px"
                />
            </div>
        </div>
    </section>
</template>
