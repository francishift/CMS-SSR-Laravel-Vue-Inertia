import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';

// Directiva ligera accesible para tooltips en el frontend público sin cargar el framework PrimeVue
const publicTooltipDirective = {
    mounted(el, binding) {
        const text = typeof binding?.value === 'string' ? binding.value : binding?.value?.value;
        if (text) el.setAttribute('title', text);
    },
    updated(el, binding) {
        const text = typeof binding?.value === 'string' ? binding.value : binding?.value?.value;
        if (text) el.setAttribute('title', text);
        else el.removeAttribute('title');
    },
};

createInertiaApp({
    title: (title) => {
        // Las vistas públicas ya incluyen el sufijo dinámico (— site_name).
        // Solo se aplica el fallback cuando no se proporciona título (ej: vistas de admin).
        const fallback = import.meta.env.VITE_APP_NAME || 'CMS SSR';
        return title || fallback;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    async setup({ el, App, props, plugin }) {
        const initialComponent = String(props?.initialPage?.component || '');
        const isPublicRoute = initialComponent.startsWith('Public/') || initialComponent === 'Error';

        // Preservar los parámetros query reales del navegador (ej. utm_*, gclid, fbclid) al hidratar desde caché Redis compartida
        if (isPublicRoute && typeof window !== 'undefined' && window.location.search && props?.initialPage) {
            props.initialPage.url = `${window.location.pathname}${window.location.search}${window.location.hash || ''}`;
        }

        const vueApp = createApp({ render: () => h(App, props) }).use(plugin);

        if (isPublicRoute) {
            // Si algún enlace interno redirigiese hacia autenticación o administración, cargar documento completo con PrimeVue
            router.on('before', (event) => {
                const path = event.detail.visit.url?.pathname || '';
                if (/^\/(panel|acceso|login|register|forgot-password|reset-password)(\/|$)/i.test(path)) {
                    window.location.assign(event.detail.visit.url.href);
                    return false;
                }
            });

            return vueApp
                .directive('tooltip', publicTooltipDirective)
                .mount(el);
        }

        // Cargar PrimeVue, tema Aura y Ziggy únicamente en rutas de administración, autenticación o vista previa del Builder
        const [
            { ZiggyVue },
            { default: PrimeVue },
            { default: Tooltip },
            { default: ToastService },
            { default: ConfirmationService },
            { CmsTheme },
            { EsLocale },
        ] = await Promise.all([
            import('../../vendor/tightenco/ziggy'),
            import('primevue/config'),
            import('primevue/tooltip'),
            import('primevue/toastservice'),
            import('primevue/confirmationservice'),
            import('@/Config/theme'),
            import('@/Config/locale'),
        ]);

        return vueApp
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: CmsTheme,
                    options: { darkModeSelector: '.dark' },
                },
                locale: EsLocale,
            })
            .use(ToastService)        // Notificaciones globales vía useToast()
            .use(ConfirmationService) // Diálogos de confirmación vía useConfirm()
            .directive('tooltip', Tooltip)
            .mount(el);
    },
    progress: {
        color: '#10b981', // emerald-500
    },
});

