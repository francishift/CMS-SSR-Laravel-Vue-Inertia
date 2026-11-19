<script setup>
/**
 * Layout público del CMS.
 *
 * Incluye header con menú dinámico SPA (Link), contenido principal
 * con reserva de altura mínima (min-h) para evitar colapsos o saltos de layout,
 * transición suave y fluida al cambiar de página (page-slide-fade) y footer responsive.
 */
import { usePage, Link, Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { esNavegacionInertia, getRutaInterna } from '@/Composables/useUrlHelpers';
import { useCookieConsent } from '@/Composables/useCookieConsent';

const page = usePage();
const settings = computed(() => page.props.siteSettings || {});
const menuHeader = computed(() => page.props.menuHeader);
const menuFooter = computed(() => page.props.menuFooter);

function normalizarColor(val, fallback = '#10b981') {
    if (!val || typeof val !== 'string') return fallback;
    const clean = val.trim();
    if (clean.startsWith('#')) return clean;
    if (/^[0-9a-fA-F]{3,6}$/.test(clean)) {
        return '#' + clean;
    }
    return clean || fallback;
}

// CSS variables dinámicas para fuentes y colores
const estilosDinamicos = computed(() => ({
    '--heading-font': `'${settings.value.heading_font || 'Hanken Grotesk'}', system-ui, sans-serif`,
    '--body-font': `'${settings.value.body_font || 'Hanken Grotesk'}', system-ui, sans-serif`,
    '--body-font-size': settings.value.body_font_size || '1rem',
    '--body-font-weight': settings.value.body_font_weight || '400',
    '--body-line-height': settings.value.body_line_height || '1.625',
    '--body-letter-spacing': settings.value.body_letter_spacing || '0em',
    '--primary-color': normalizarColor(settings.value.primary_color, '#10b981'),
    '--secondary-color': normalizarColor(settings.value.secondary_color, '#6366f1'),
    '--accent-color': normalizarColor(settings.value.accent_color, '#f59e0b'),
}));

const { initCookieConsent, openCookiePreferences } = useCookieConsent();

onMounted(() => {
    if (settings.value.cookie_consent_enabled) {
        initCookieConsent(settings.value);
    }
});
</script>

<template>
    <div :style="estilosDinamicos" class="public-site min-h-screen flex flex-col transition-colors duration-300">
        <Head>
            <meta name="robots" content="index, follow" />
        </Head>

        <!-- Header -->
        <header class="border-b border-zinc-200 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo / Nombre del sitio -->
                    <Link href="/" class="flex items-center gap-3">
                        <img v-if="settings.logo_path" :src="settings.logo_path" :alt="settings.site_name" fetchpriority="high" class="h-8 w-auto" />
                        <span class="text-lg font-bold" style="font-family: var(--heading-font), system-ui, sans-serif;">
                            {{ settings.site_name || 'CMS' }}
                        </span>
                    </Link>

                    <!-- Menú de navegación principal con soporte SPA e iconos vectoriales  -->
                    <nav v-if="menuHeader?.items?.length" class="hidden md:flex items-center gap-6">
                        <template v-for="item in menuHeader.items" :key="item.id">
                            <template v-if="item.is_active">
                                <Link
                                    v-if="esNavegacionInertia(item.resolved_url, item.target)"
                                    :href="getRutaInterna(item.resolved_url)"
                                    class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors inline-flex items-center gap-1.5"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                                    <span>{{ item.title }}</span>
                                </Link>
                                <a
                                    v-else
                                    :href="item.resolved_url"
                                    :target="item.target || '_blank'"
                                    class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors inline-flex items-center gap-1.5"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                                    <span>{{ item.title }}</span>
                                </a>
                            </template>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Contenido Principal: min-h-screen reservado y animación de transición -->
        <main class="flex-1 min-h-[calc(100vh-10rem)]">
            <Transition name="page-slide-fade" mode="out-in">
                <div :key="page.url" class="w-full">
                    <slot />
                </div>
            </Transition>
        </main>

        <!-- Footer -->
        <footer class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Menú footer -->
                    <nav v-if="menuFooter?.items?.length" class="flex flex-wrap items-center gap-4">
                        <template v-for="item in menuFooter.items" :key="item.id">
                            <template v-if="item.is_active">
                                <Link
                                    v-if="esNavegacionInertia(item.resolved_url, item.target)"
                                    :href="getRutaInterna(item.resolved_url)"
                                    class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors inline-flex items-center gap-1.5"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                                    <span>{{ item.title }}</span>
                                </Link>
                                <a
                                    v-else
                                    :href="item.resolved_url"
                                    :target="item.target || '_blank'"
                                    class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors inline-flex items-center gap-1.5"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                                    <span>{{ item.title }}</span>
                                </a>
                            </template>
                        </template>
                    </nav>

                    <!-- Texto del footer -->
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-zinc-400">
                            {{ settings.footer_text || `© ${new Date().getFullYear()} ${settings.site_name || 'CMS'}` }}
                        </p>
                        <button
                            v-if="settings.cookie_consent_enabled"
                            type="button"
                            @click="openCookiePreferences"
                            class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors inline-flex items-center gap-1 cursor-pointer"
                        >
                            <i class="pi pi-shield text-[10px]" />
                            <span>Cookies</span>
                        </button>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Transición sutil de elevación y desvanecimiento de abajo hacia arriba al cambiar de página (Slide-Up & Fade-In) */
.page-slide-fade-enter-active {
    transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1), transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.page-slide-fade-leave-active {
    transition: opacity 0.12s ease-out, transform 0.12s ease-out;
}

.page-slide-fade-enter-from {
    opacity: 0;
    transform: translateY(14px);
}

.page-slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
