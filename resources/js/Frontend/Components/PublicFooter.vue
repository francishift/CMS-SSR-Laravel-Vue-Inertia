<script setup>
/**
 * Pie de página público (Footer) dinámico del Frontend.
 *
 * Si el administrador ha maquetado el pie de página mediante el Page Builder
 * (footer_layout_json), se renderiza con PageBuilderRenderer.
 * De lo contrario, muestra un fallback mínimo y limpio con el copyright.
 */
import { computed } from 'vue';
import PageBuilderRenderer from '@/Frontend/Builder/PageBuilderRenderer.vue';
import { useCookieConsent } from '@/Composables/useCookieConsent';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    menuFooter: { type: Object, default: null },
});

const { openCookiePreferences } = useCookieConsent();

const hasBuilderFooter = computed(() => {
    const layout = props.settings?.footer_layout_json;
    return Boolean(layout && Array.isArray(layout.sections) && layout.sections.length > 0);
});
</script>

<template>
    <!-- Renderizado dinámico del Footer maquetado con Page Builder -->
    <footer v-if="hasBuilderFooter" class="w-full border-t border-zinc-200 dark:border-zinc-800 shrink-0">
        <PageBuilderRenderer :layout="settings.footer_layout_json" />
    </footer>

    <!-- Fallback mínimo en caso de no haber maquetado aún el footer con el builder -->
    <footer v-else class="w-full border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50 shrink-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm text-zinc-400">
                    {{ settings.footer_text || `© ${new Date().getFullYear()} ${settings.site_name || 'CMS'}` }}
                </p>

                <button
                    v-if="settings.cookie_consent_enabled"
                    type="button"
                    @click="openCookiePreferences"
                    class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <i class="pi pi-shield text-[11px]" />
                    <span>Preferencias de cookies</span>
                </button>
            </div>
        </div>
    </footer>
</template>
