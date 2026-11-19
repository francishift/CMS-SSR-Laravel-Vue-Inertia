<script setup>
/**
 * Layout público principal del Frontend.
 * 
 * Ensambla los componentes modulares PublicHeader y PublicFooter,
 * aplicando variables CSS dinámicas (fuentes, colores primarios/secundarios)
 * y una transición sutil y fluida de cambio de página (page-slide-fade).
 */
import { usePage, Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted, nextTick, provide } from 'vue';
import { initScrollAnimations } from '@/Composables/useScrollAnimation';
import PublicHeader from '@/Frontend/Components/PublicHeader.vue';
import PublicFooter from '@/Frontend/Components/PublicFooter.vue';
import PublicSidebar from '@/Frontend/Components/PublicSidebar.vue';
import { normalizarColor, normalizarBgImage } from '@/Utils/colorUtils';
import { useCookieConsent } from '@/Composables/useCookieConsent';
import { esNavegacionInertia, getRutaInterna } from '@/Composables/useUrlHelpers';

const page = usePage();
const settings = computed(() => page.props.siteSettings || {});
const menuHeader = computed(() => page.props.menuHeader);
const menuFooter = computed(() => page.props.menuFooter);
const menuSidebar = computed(() => page.props.menuSidebar);

const tieneSidebar = computed(() => Boolean(menuSidebar.value?.items?.length));
const esFooter = computed(() => Boolean(page.props.esFooter));

const props = defineProps({
    cabeceraTransparente: { type: Object, default: null },
    pagina: { type: Object, default: () => ({}) },
    breadcrumb: { type: Array, default: () => [] },
});

// Gestión de Cabecera Transparente reactiva por sección
const dynamicTransparentOverride = ref(null);

const { initCookieConsent, openCookiePreferences } = useCookieConsent();
provide('openCookiePreferences', openCookiePreferences);

provide('setTransparentHeaderOverride', (config) => {
    dynamicTransparentOverride.value = config;
});

const cabeceraTransparenteConfig = computed(() => {
    // 1. Prioridad: modificación reactiva en caliente (ej: en el editor Page Builder)
    if (dynamicTransparentOverride.value !== null) {
        return dynamicTransparentOverride.value;
    }

    // 2. Prioridad: valor resuelto directamente en el servidor (HandleInertiaRequests o PublicPageController)
    if (page.props?.cabeceraTransparente) {
        return page.props.cabeceraTransparente;
    }
    if (props.cabeceraTransparente) {
        return props.cabeceraTransparente;
    }

    // 3. Fallback: inspección del layout de la página, categoría o artículo actual
    const rawLayout = page.props?.pagina?.layout_json 
        || page.props?.pagina?.data?.layout_json
        || props.pagina?.layout_json
        || props.pagina?.data?.layout_json
        || page.props?.initialLayout
        || page.props?.category?.layout_json
        || page.props?.categoria?.layout_json
        || page.props?.article?.layout_json
        || page.props?.articulo?.layout_json;

    let layoutData = rawLayout;
    if (typeof layoutData === 'string') {
        try {
            layoutData = JSON.parse(layoutData);
        } catch {
            layoutData = null;
        }
    }

    const firstSection = layoutData?.sections?.[0];
    if (firstSection?.settings?.transparent_header) {
        return {
            active: true,
            pull: Boolean(firstSection.settings.transparent_header_pull),
            navColor: firstSection.settings.transparent_header_nav_color || 'inherit',
        };
    }

    return null;
});

onMounted(() => {
    // Aplicar el tema configurado de forma independiente para el Frontend público (light, dark, user_choice)
    const mode = settings.value.frontend_theme_mode || 'light';
    const defaultInitialTheme = settings.value.frontend_default_theme || 'light';

    if (mode === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (mode === 'light') {
        document.documentElement.classList.remove('dark');
    } else if (mode === 'user_choice') {
        const pref = localStorage.getItem('public_theme_mode');
        if (pref === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (pref === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            // Si el visitante entra por primera vez sin preferencia guardada:
            if (defaultInitialTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (defaultInitialTheme === 'light') {
                document.documentElement.classList.remove('dark');
            } else if (defaultInitialTheme === 'system') {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        }
    }
    aplicarVariablesCssEnRoot();

    // Inicializar banner de cookies y analítica en reposo para no competir con el LCP inicial
    if (settings.value.cookie_consent_enabled) {
        const iniciarCookiesEnReposo = () => initCookieConsent(settings.value);
        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(iniciarCookiesEnReposo, { timeout: 1500 });
        } else {
            setTimeout(iniciarCookiesEnReposo, 600);
        }
    }

    // Delegación global para botones de shortcodes y acciones declarativas compatibles con CSP
    const handleGlobalCmsClick = (e) => {
        if (!(e.target instanceof Element)) return;

        const btn = e.target.closest('[data-cc-shortcode]');
        if (btn) {
            e.preventDefault();
            openCookiePreferences();
            return;
        }

        const navTrigger = e.target.closest('[data-cms-open-url]');
        if (navTrigger) {
            const rawUrl = navTrigger.getAttribute('data-cms-open-url');
            const target = navTrigger.getAttribute('data-cms-open-target') || '_self';
            if (!rawUrl) return;

            e.preventDefault();
            if (esNavegacionInertia(rawUrl, target)) {
                router.visit(getRutaInterna(rawUrl));
            } else if (target === '_blank') {
                window.open(rawUrl, '_blank', 'noopener,noreferrer');
            } else {
                window.location.assign(rawUrl);
            }
        }
    };
    document.addEventListener('click', handleGlobalCmsClick);
    onUnmounted(() => {
        document.removeEventListener('click', handleGlobalCmsClick);
    });
});

// Valores por defecto tipográficos para Hero 2XL a H6
const headingDefaults = {
    hero_2xl: { size: '4.5rem',  font_weight: '800', letter_spacing: '-0.03em',  line_height: '1.05' },
    hero_xl:  { size: '3.75rem', font_weight: '800', letter_spacing: '-0.025em', line_height: '1.1' },
    h1:       { size: '3rem',    font_weight: '800', letter_spacing: '-0.025em', line_height: '1.1' },
    h2:       { size: '2.25rem', font_weight: '700', letter_spacing: '-0.02em',  line_height: '1.2' },
    h3:       { size: '1.875rem',font_weight: '600', letter_spacing: '0em',      line_height: '1.25' },
    h4:       { size: '1.25rem', font_weight: '600', letter_spacing: '0em',      line_height: '1.35' },
    h5:       { size: '1.125rem',font_weight: '600', letter_spacing: '0em',      line_height: '1.4' },
    h6:       { size: '1rem',    font_weight: '600', letter_spacing: '0em',      line_height: '1.4' },
};

function aplicarVariablesCssEnRoot() {
    if (typeof document === 'undefined') return;
    const el = document.documentElement;
    const isDark = el.classList.contains('dark');

    // Inyección de fuentes y variables tipográficas de cuerpo (Body) en :root
    el.style.setProperty('--heading-font', `'${settings.value.heading_font || 'Plus Jakarta Sans'}', system-ui, sans-serif`);
    el.style.setProperty('--body-font', `'${settings.value.body_font || 'Open Sans'}', system-ui, sans-serif`);
    el.style.setProperty('--body-font-size', settings.value.body_font_size || '1rem');
    el.style.setProperty('--body-font-weight', settings.value.body_font_weight || '400');
    el.style.setProperty('--body-line-height', settings.value.body_line_height || '1.625');
    el.style.setProperty('--body-letter-spacing', settings.value.body_letter_spacing || '0em');

    // Inyección de variables tipográficas por nivel de encabezado (Hero 2XL a H6) en :root
    const hs = settings.value.heading_styles || {};
    for (const level of ['hero_2xl', 'hero_xl', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']) {
        const def = headingDefaults[level] || {};
        const custom = hs[level] || {};
        el.style.setProperty(`--${level}-size`, custom.size || def.size || '2rem');
        el.style.setProperty(`--${level}-font-weight`, custom.font_weight || def.font_weight || '700');
        el.style.setProperty(`--${level}-letter-spacing`, custom.letter_spacing || def.letter_spacing || '0em');
        el.style.setProperty(`--${level}-line-height`, custom.line_height || def.line_height || '1.2');
    }

    el.style.setProperty('--primary-color', normalizarColor(settings.value.primary_color, '#10b981'));
    el.style.setProperty('--secondary-color', normalizarColor(settings.value.secondary_color, '#6366f1'));
    el.style.setProperty('--accent-color', normalizarColor(settings.value.accent_color, '#f59e0b'));
    el.style.setProperty('--link-color', normalizarColor(settings.value.link_color || settings.value.primary_color, '#10b981'));
    el.style.setProperty('--link-hover-color', normalizarColor(settings.value.link_hover_color, '#059669'));
    el.style.setProperty('--link-color-dark', normalizarColor(settings.value.link_color_dark, '#d4d4d8'));
    el.style.setProperty('--link-hover-color-dark', normalizarColor(settings.value.link_hover_color_dark, '#ffffff'));

    const linkDec = settings.value.link_decoration || 'hover';
    let linkDecVal = 'none';
    let linkHoverDecVal = 'underline';
    if (linkDec === 'always') {
        linkDecVal = 'underline';
        linkHoverDecVal = 'underline';
    } else if (linkDec === 'none') {
        linkDecVal = 'none';
        linkHoverDecVal = 'none';
    } else if (linkDec === 'hover') {
        linkDecVal = 'none';
        linkHoverDecVal = 'underline';
    } else if (linkDec === 'reverse_hover') {
        linkDecVal = 'underline';
        linkHoverDecVal = 'none';
    }

    const weightMap = {
        inherit: 'inherit',
        normal: '400',
        medium: '500',
        semibold: '600',
        bold: '700',
    };

    el.style.setProperty('--link-decoration', linkDecVal);
    el.style.setProperty('--link-hover-decoration', linkHoverDecVal);
    el.style.setProperty('--link-font-weight', weightMap[settings.value.link_font_weight] || 'inherit');
    el.style.setProperty('--link-underline-offset', (settings.value.link_underline_offset === 'auto' || !settings.value.link_underline_offset) ? 'auto' : settings.value.link_underline_offset);
    el.style.setProperty('--link-underline-style', settings.value.link_underline_style || 'solid');

    // Variables de Fondo General y Texto (Modo Claro vs Oscuro)
    const bgColorLight = normalizarColor(settings.value.bg_color_light, '#ffffff');
    const textColorLight = normalizarColor(settings.value.text_color_light, '#18181b');
    const bgColorDark = normalizarColor(settings.value.bg_color_dark, '#09090b');
    const textColorDark = normalizarColor(settings.value.text_color_dark, '#f4f4f5');

    const bgImgLight = normalizarBgImage(settings.value.bg_image_light);
    const bgImgDark = normalizarBgImage(settings.value.bg_image_dark);

    el.style.setProperty('--bg-color-light', bgColorLight);
    el.style.setProperty('--text-color-light', textColorLight);
    el.style.setProperty('--bg-color-dark', bgColorDark);
    el.style.setProperty('--text-color-dark', textColorDark);
    el.style.setProperty('--bg-image-light', bgImgLight);
    el.style.setProperty('--bg-image-dark', bgImgDark);

    // Inyectar activamente las variables del fondo general y texto del sitio según el modo
    el.style.setProperty('--site-bg', isDark ? bgColorDark : bgColorLight);
    el.style.setProperty('--site-bg-image', isDark ? bgImgDark : bgImgLight);
    el.style.setProperty('--site-text', isDark ? textColorDark : textColorLight);

    // Variables de Botones Primarios y Secundarios (Modo Claro vs Oscuro)
    const btnPrimaryBgLight = normalizarColor(settings.value.button_primary_bg, '#10b981');
    const btnPrimaryTextLight = normalizarColor(settings.value.button_primary_text, '#ffffff');
    const btnSecondaryBgLight = normalizarColor(settings.value.button_secondary_bg, '#6366f1');
    const btnSecondaryTextLight = normalizarColor(settings.value.button_secondary_text, '#ffffff');

    const btnPrimaryBgDark = normalizarColor(settings.value.button_primary_bg_dark || settings.value.button_primary_bg, '#10b981');
    const btnPrimaryTextDark = normalizarColor(settings.value.button_primary_text_dark, '#ffffff');
    const btnSecondaryBgDark = normalizarColor(settings.value.button_secondary_bg_dark, '#27272a');
    const btnSecondaryTextDark = normalizarColor(settings.value.button_secondary_text_dark, '#ffffff');

    el.style.setProperty('--button-primary-bg', btnPrimaryBgLight);
    el.style.setProperty('--button-primary-text', btnPrimaryTextLight);
    el.style.setProperty('--button-secondary-bg', btnSecondaryBgLight);
    el.style.setProperty('--button-secondary-text', btnSecondaryTextLight);

    el.style.setProperty('--button-primary-bg-dark', btnPrimaryBgDark);
    el.style.setProperty('--button-primary-text-dark', btnPrimaryTextDark);
    el.style.setProperty('--button-secondary-bg-dark', btnSecondaryBgDark);
    el.style.setProperty('--button-secondary-text-dark', btnSecondaryTextDark);

    // Inyectar activamente las variables globales utilizadas por los elementos y grids
    el.style.setProperty('--btn-primary-bg', isDark ? btnPrimaryBgDark : btnPrimaryBgLight);
    el.style.setProperty('--btn-primary-text', isDark ? btnPrimaryTextDark : btnPrimaryTextLight);
    el.style.setProperty('--btn-secondary-bg', isDark ? btnSecondaryBgDark : btnSecondaryBgLight);
    el.style.setProperty('--btn-secondary-text', isDark ? btnSecondaryTextDark : btnSecondaryTextLight);

    const mapRadius = {
        'rounded-none': '0px',
        'rounded-sm': '0.125rem',
        'rounded': '0.25rem',
        'rounded-md': '0.375rem',
        'rounded-lg': '0.5rem',
        'rounded-xl': '0.75rem',
        'rounded-2xl': '1rem',
        'rounded-3xl': '1.5rem',
        'rounded-full': '9999px',
    };
    el.style.setProperty('--button-border-radius', mapRadius[settings.value.button_border_radius] || '0.75rem');

    // Variables independientes para la navegación de la Cabecera
    el.style.setProperty('--header-nav-color-light', normalizarColor(settings.value.header_nav_color_light, '#3f3f46'));
    el.style.setProperty('--header-nav-hover-light', normalizarColor(settings.value.header_nav_hover_light, '#18181b'));
    el.style.setProperty('--header-nav-active-light', normalizarColor(settings.value.header_nav_active_light || settings.value.primary_color, '#10b981'));
    el.style.setProperty('--header-nav-color-dark', normalizarColor(settings.value.header_nav_color_dark, '#d4d4d8'));
    el.style.setProperty('--header-nav-hover-dark', normalizarColor(settings.value.header_nav_hover_dark, '#ffffff'));
    el.style.setProperty('--header-nav-active-dark', normalizarColor(settings.value.header_nav_active_dark || settings.value.primary_color, '#10b981'));

    // Altura dinámica del logotipo (escritorio y móvil)
    el.style.setProperty('--logo-height', `${Number(settings.value.logo_height) || 36}px`);
    el.style.setProperty('--logo-height-mobile', `${Number(settings.value.logo_height_mobile) || 32}px`);
}

let themeObserver = null;

onMounted(() => {
    aplicarVariablesCssEnRoot();
    if (typeof document !== 'undefined') {
        themeObserver = new MutationObserver(() => {
            aplicarVariablesCssEnRoot();
        });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }
});

onUnmounted(() => {
    if (themeObserver) {
        themeObserver.disconnect();
    }
});

watch(() => settings.value, () => {
    aplicarVariablesCssEnRoot();
}, { immediate: true, deep: true });

watch(() => page.url, () => {
    nextTick(() => {
        initScrollAnimations();
    });
});



// Variables CSS dinámicas para personalizar tipografía y colores según siteSettings
const estilosDinamicos = computed(() => {
    const hs = settings.value.heading_styles || {};
    const vars = {
        '--heading-font': `'${settings.value.heading_font || 'Hanken Grotesk'}', system-ui, sans-serif`,
        '--body-font': `'${settings.value.body_font || 'Hanken Grotesk'}', system-ui, sans-serif`,
        '--body-font-size': settings.value.body_font_size || '1rem',
        '--body-font-weight': settings.value.body_font_weight || '400',
        '--body-line-height': settings.value.body_line_height || '1.625',
        '--body-letter-spacing': settings.value.body_letter_spacing || '0em',
        '--primary-color': normalizarColor(settings.value.primary_color, '#10b981'),
        '--secondary-color': normalizarColor(settings.value.secondary_color, '#6366f1'),
        '--accent-color': normalizarColor(settings.value.accent_color, '#f59e0b'),
        '--link-color': normalizarColor(settings.value.link_color || settings.value.primary_color, '#10b981'),
        '--link-hover-color': normalizarColor(settings.value.link_hover_color, '#059669'),
        '--link-color-dark': normalizarColor(settings.value.link_color_dark, '#d4d4d8'),
        '--link-hover-color-dark': normalizarColor(settings.value.link_hover_color_dark, '#ffffff'),
        '--link-decoration': settings.value.link_decoration === 'always' || settings.value.link_decoration === 'reverse_hover' ? 'underline' : 'none',
        '--link-hover-decoration': settings.value.link_decoration === 'always' || settings.value.link_decoration === 'hover' || !settings.value.link_decoration ? 'underline' : (settings.value.link_decoration === 'reverse_hover' ? 'none' : 'none'),
        '--link-font-weight': ({ inherit: 'inherit', normal: '400', medium: '500', semibold: '600', bold: '700' })[settings.value.link_font_weight] || 'inherit',
        '--link-underline-offset': (settings.value.link_underline_offset === 'auto' || !settings.value.link_underline_offset) ? 'auto' : settings.value.link_underline_offset,
        '--link-underline-style': settings.value.link_underline_style || 'solid',
        '--bg-color-light': normalizarColor(settings.value.bg_color_light, '#ffffff'),
        '--text-color-light': normalizarColor(settings.value.text_color_light, '#18181b'),
        '--bg-color-dark': normalizarColor(settings.value.bg_color_dark, '#09090b'),
        '--text-color-dark': normalizarColor(settings.value.text_color_dark, '#ffffff'),
        '--bg-image-light': normalizarBgImage(settings.value.bg_image_light),
        '--bg-image-dark': normalizarBgImage(settings.value.bg_image_dark),
    };

    // Filtros de logotipo para modos claro y oscuro en vivo
    const logoFilterParts = [];
    if (settings.value?.logo_invert) logoFilterParts.push('invert(100%)');
    if (settings.value?.logo_brightness && Number(settings.value.logo_brightness) !== 100) {
        logoFilterParts.push(`brightness(${settings.value.logo_brightness}%)`);
    }
    vars['--logo-filter'] = logoFilterParts.length ? logoFilterParts.join(' ') : 'none';

    const logoDarkFilterParts = [];
    if (settings.value?.logo_dark_invert) logoDarkFilterParts.push('invert(100%)');
    if (settings.value?.logo_dark_brightness && Number(settings.value.logo_dark_brightness) !== 100) {
        logoDarkFilterParts.push(`brightness(${settings.value.logo_dark_brightness}%)`);
    }
    vars['--logo-dark-filter'] = logoDarkFilterParts.length ? logoDarkFilterParts.join(' ') : 'none';
    vars['--logo-height'] = `${Number(settings.value?.logo_height) || 36}px`;
    vars['--logo-height-mobile'] = `${Number(settings.value?.logo_height_mobile) || 32}px`;

    // Inyectar variables tipográficas para cada nivel Hero 2XL - H6
    for (const level of ['hero_2xl', 'hero_xl', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']) {
        const def = headingDefaults[level];
        const custom = hs[level] || {};
        vars[`--${level}-size`]           = custom.size           || def.size;
        vars[`--${level}-font-weight`]    = custom.font_weight    || def.font_weight;
        vars[`--${level}-letter-spacing`] = custom.letter_spacing || def.letter_spacing;
        vars[`--${level}-line-height`]    = custom.line_height    || def.line_height;
    }

    return vars;
});
</script>

<template>
    <div :style="estilosDinamicos" class="public-site min-h-screen flex flex-col">
        <Head>
            <!-- meta robots lo genera SeoService vía SEOTools en servidor -->
        </Head>

        <!-- Header público modular -->
        <PublicHeader :settings="settings" :menu-header="menuHeader" :transparent-override="cabeceraTransparenteConfig" />

        <!-- Contenido Principal con soporte dinámico para Sidebar -->
        <main class="flex-1 w-full">
            <div class="w-full">
                <div v-if="tieneSidebar" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
                        <!-- Contenido principal -->
                        <div class="lg:col-span-3">
                            <slot />
                        </div>

                        <!-- Barra Lateral (Sidebar) -->
                        <aside class="lg:col-span-1 lg:sticky lg:top-24 space-y-6">
                            <PublicSidebar :menu-sidebar="menuSidebar" />
                        </aside>
                    </div>
                </div>

                <!-- Sin Sidebar: Ancho completo según cada vista -->
                <template v-else>
                    <slot />
                </template>
            </div>
        </main>

        <!-- Footer público modular -->
        <PublicFooter v-if="!esFooter" :settings="settings" :menu-footer="menuFooter" />
    </div>
</template>
