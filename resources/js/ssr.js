/**
 * Punto de entrada SSR (Server-Side Rendering) de Inertia.
 *
 * Este archivo es ejecutado por Node.js en el servidor para generar
 * el HTML completo de cada página en la primera visita.
 * Replica la configuración de app.js pero usando createSSRApp.
 */
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from 'vue/server-renderer';
import { createSSRApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { CmsTheme } from '@/Config/theme';
import { EsLocale } from '@/Config/locale';

/**
 * Caché efímera por instancia SSR para compatibilidad entre @inertiajs/vue3 v2
 * y @vue/server-renderer >= 3.5.40.
 *
 * @inertiajs/vue3 v2 evalúa `render(vueApp)` de forma síncrona en `innerHTML`,
 * mientras que @vue/server-renderer >= 3.5.40 convierte `props.innerHTML` con `String()`,
 * lo que transformaría una Promise pendiente en el texto literal "[object Promise]"
 * perjudicando la primera pintura y el indexado SEO.
 * Pre-resolvemos `await renderToString(ssrApp)` en `async setup()` para entregar el HTML
 * ya renderizado de forma síncrona, asegurando además que todas las etiquetas `<Head>`
 * (`@inertiaHead`) queden registradas antes de emitir la respuesta SSR y liberando
 * la referencia inmediatamente tras su lectura para cero huella de memoria.
 */
const renderedApps = new WeakMap();

createServer((page) =>
    createInertiaApp({
        page,
        render: (app) => {
            if (renderedApps.has(app)) {
                const html = renderedApps.get(app);
                renderedApps.delete(app);
                return html;
            }
            return renderToString(app);
        },
        title: (title) => {
            const defaultName = import.meta.env.VITE_APP_NAME || 'CMS SSR';
            return title || defaultName;
        },
        resolve: (name) => {
            const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
            return pages[`./Pages/${name}.vue`];
        },
        async setup({ App, props, plugin }) {
            const ssrApp = createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, {
                    ...(page.props.ziggy || {}),
                    location: new URL(page.props.ziggy?.location || 'http://localhost'),
                })
                .use(PrimeVue, {
                    theme: {
                        preset: CmsTheme,
                        options: { darkModeSelector: '.dark' },
                    },
                    locale: EsLocale,
                })
                .use(ToastService)
                .use(ConfirmationService)
                .directive('tooltip', {});

            const html = await renderToString(ssrApp);
            renderedApps.set(ssrApp, html);

            return ssrApp;
        },
    })
);
