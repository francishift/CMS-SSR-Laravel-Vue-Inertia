<script setup>
/**
 * Configuración Global del Sitio (Identidad, Tipografía Local SEO, Colores Corporativos, Botones y Redes Sociales).
 *
 * Interfaz limpia, ágil y modular mediante navegación lateral por pestañas y componentes desacoplados.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';

// Componentes Modulares de Sección
import IdentidadSection from './Partials/IdentidadSection.vue';
import CabeceraSection from './Partials/CabeceraSection.vue';
import FuentesSection from './Partials/FuentesSection.vue';
import ColoresSection from './Partials/ColoresSection.vue';
import BotonesSection from './Partials/BotonesSection.vue';
import RedesSection from './Partials/RedesSection.vue';
import SeoSection from './Partials/SeoSection.vue';
import IntegracionesSection from './Partials/IntegracionesSection.vue';
import FooterSection from './Partials/FooterSection.vue';

const props = defineProps({
    configuracion: { type: Object, required: true },
    fuentesDisponibles: { type: Array, default: () => [] },
});

const toast = useToast();
const pestañaActiva = ref('identidad');

// Definición del menú lateral de secciones principales
const seccionesMenu = [
    { id: 'identidad', titulo: 'Identidad & Marca', icono: 'pi pi-globe', desc: 'Nombre del sitio, descripción, logotipo y favicon' },
    { id: 'cabecera', titulo: 'Cabecera & Navegación', icono: 'pi pi-bars', desc: 'Disposición del menú, submenús, botón CTA y cajón móvil' },
    { id: 'fuentes', titulo: 'Tipografía', icono: 'pi pi-align-left', desc: 'Fuentes alojadas en local para encabezados y cuerpo' },
    { id: 'colores', titulo: 'Paleta Corporativa', icono: 'pi pi-palette', desc: 'Color primario, secundario y tonos de acento' },
    { id: 'botones', titulo: 'Botones & Controles', icono: 'pi pi-box', desc: 'Fondo y texto de botones primarios y secundarios' },
    { id: 'redes', titulo: 'Contacto y Redes Sociales', icono: 'pi pi-share-alt', desc: 'Canales de contacto seguro y perfiles oficiales' },
    { id: 'seo', titulo: 'SEO Global Fallback', icono: 'pi pi-search', desc: 'Título, descripción por defecto e imagen Open Graph' },
    { id: 'integraciones', titulo: 'Integraciones & APIs', icono: 'pi pi-sliders-h', desc: 'Google Maps, Google Analytics 4 y Cookies RGPD' },
    { id: 'footer', titulo: 'Pie de Página', icono: 'pi pi-file', desc: 'Texto legal y copyright del footer' },
];

const form = useForm({
    site_name: props.configuracion.site_name || 'Mi Sitio Web',
    site_description: props.configuracion.site_description || '',
    logo_path: props.configuracion.logo_path || '',
    logo_invert: Boolean(props.configuracion.logo_invert),
    logo_brightness: props.configuracion.logo_brightness ?? 100,
    logo_dark_path: props.configuracion.logo_dark_path || '',
    logo_dark_invert: Boolean(props.configuracion.logo_dark_invert),
    logo_dark_brightness: props.configuracion.logo_dark_brightness ?? 100,
    logo_height: props.configuracion.logo_height ?? 36,
    logo_height_mobile: props.configuracion.logo_height_mobile ?? 32,
    favicon_path: props.configuracion.favicon_path || '/favicon.ico',
    apple_touch_icon_path: props.configuracion.apple_touch_icon_path || '',
    primary_color: props.configuracion.primary_color || '#10b981',
    secondary_color: props.configuracion.secondary_color || '#6366f1',
    accent_color: props.configuracion.accent_color || '#f59e0b',
    link_color: props.configuracion.link_color || props.configuracion.primary_color || '#10b981',
    link_hover_color: props.configuracion.link_hover_color || '#059669',
    link_color_dark: props.configuracion.link_color_dark || '#d4d4d8',
    link_hover_color_dark: props.configuracion.link_hover_color_dark || '#ffffff',
    link_decoration: props.configuracion.link_decoration || 'hover',
    link_font_weight: props.configuracion.link_font_weight || 'inherit',
    link_underline_offset: props.configuracion.link_underline_offset || 'auto',
    link_underline_style: props.configuracion.link_underline_style || 'solid',
    button_primary_bg: props.configuracion.button_primary_bg || props.configuracion.primary_color || '#10b981',
    button_primary_text: props.configuracion.button_primary_text || '#ffffff',
    button_secondary_bg: props.configuracion.button_secondary_bg || props.configuracion.secondary_color || '#6366f1',
    button_secondary_text: props.configuracion.button_secondary_text || '#ffffff',
    button_primary_bg_dark: props.configuracion.button_primary_bg_dark || props.configuracion.button_primary_bg || '#10b981',
    button_primary_text_dark: props.configuracion.button_primary_text_dark || '#ffffff',
    button_secondary_bg_dark: props.configuracion.button_secondary_bg_dark || '#27272a',
    button_secondary_text_dark: props.configuracion.button_secondary_text_dark || '#ffffff',
    bg_color_light: props.configuracion.bg_color_light || '#ffffff',
    bg_color_dark: props.configuracion.bg_color_dark || '#09090b',
    bg_image_light: props.configuracion.bg_image_light || 'none',
    bg_image_dark: props.configuracion.bg_image_dark || 'none',
    text_color_light: props.configuracion.text_color_light || '#18181b',
    text_color_dark: props.configuracion.text_color_dark || '#f4f4f5',
    button_border_radius: props.configuracion.button_border_radius || 'rounded-xl',
    button_font_size: props.configuracion.button_font_size || 'text-sm',
    button_padding_x: props.configuracion.button_padding_x || 'px-5',
    button_padding_y: props.configuracion.button_padding_y || 'py-2.5',
    header_style: props.configuracion.header_style || 'sticky',
    header_layout: props.configuracion.header_layout || 'logo_left_menu_right',
    header_width: props.configuracion.header_width || 'contained',
    header_padding_y: props.configuracion.header_padding_y || 'standard',
    header_bg_type: props.configuracion.header_bg_type || 'glassmorphism',
    header_shadow: props.configuracion.header_shadow || 'border',
    header_capsule_shadow: props.configuracion.header_capsule_shadow || 'shadow-xl',
    header_capsule_padding: props.configuracion.header_capsule_padding || 'standard',
    header_border_radius: props.configuracion.header_border_radius || 'rounded-2xl',
    header_nav_color_light: props.configuracion.header_nav_color_light || '#3f3f46',
    header_nav_hover_light: props.configuracion.header_nav_hover_light || '#18181b',
    header_nav_active_light: props.configuracion.header_nav_active_light || props.configuracion.primary_color || '#10b981',
    header_nav_color_dark: props.configuracion.header_nav_color_dark || '#d4d4d8',
    header_nav_hover_dark: props.configuracion.header_nav_hover_dark || '#ffffff',
    header_nav_active_dark: props.configuracion.header_nav_active_dark || props.configuracion.primary_color || '#10b981',
    header_cta_enable: Boolean(props.configuracion.header_cta_enable),
    header_cta_text: props.configuracion.header_cta_text || '',
    header_cta_url: props.configuracion.header_cta_url || '',
    header_cta_icon: props.configuracion.header_cta_icon || '',
    header_cta_variant: props.configuracion.header_cta_variant || 'primary',
    header_buttons: Array.isArray(props.configuracion.header_buttons)
        ? props.configuracion.header_buttons
        : (props.configuracion.header_cta_enable && props.configuracion.header_cta_text ? [{
            id: 'btn_legacy',
            text: props.configuracion.header_cta_text || '',
            url: props.configuracion.header_cta_url || '',
            icon: props.configuracion.header_cta_icon || '',
            variant: props.configuracion.header_cta_variant || 'primary',
            target: '_self',
            is_active: true,
            show_on_desktop: true,
            show_on_mobile: true,
          }] : []),
    mobile_menu_style: props.configuracion.mobile_menu_style || 'drawer_right',
    mobile_show_socials: props.configuracion.mobile_show_socials ?? true,
    mobile_show_cta: props.configuracion.mobile_show_cta ?? true,
    header_show_socials: props.configuracion.header_show_socials ?? true,
    frontend_theme_mode: props.configuracion.frontend_theme_mode || 'light',
    frontend_default_theme: props.configuracion.frontend_default_theme || 'light',
    heading_font: props.configuracion.heading_font || 'Hanken Grotesk',
    body_font: props.configuracion.body_font || 'Hanken Grotesk',
    body_font_size: props.configuracion.body_font_size || '',
    body_font_weight: props.configuracion.body_font_weight || '',
    body_line_height: props.configuracion.body_line_height || '',
    body_letter_spacing: props.configuracion.body_letter_spacing || '',
    heading_styles: props.configuracion.heading_styles || {
        hero_2xl: { size: '4.5rem',  font_weight: '800', letter_spacing: '-0.03em',  line_height: '1.05' },
        hero_xl:  { size: '3.75rem', font_weight: '800', letter_spacing: '-0.025em', line_height: '1.1' },
        h1:       { size: '3rem',    font_weight: '800', letter_spacing: '-0.025em', line_height: '1.1' },
        h2:       { size: '2.25rem', font_weight: '700', letter_spacing: '-0.02em',  line_height: '1.2' },
        h3:       { size: '1.875rem',font_weight: '600', letter_spacing: '0em',      line_height: '1.25' },
        h4:       { size: '1.25rem', font_weight: '600', letter_spacing: '0em',      line_height: '1.35' },
        h5:       { size: '1rem',    font_weight: '500', letter_spacing: '0.025em',  line_height: '1.4' },
        h6:       { size: '0.75rem', font_weight: '500', letter_spacing: '0.05em',   line_height: '1.5' },
    },
    footer_text: props.configuracion.footer_text || '',
    social_links: {
        github: '',
        twitter: '',
        facebook: '',
        instagram: '',
        linkedin: '',
        youtube: '',
        whatsapp: '',
        ...(props.configuracion.social_links || {})
    },
    whatsapp_phone: props.configuracion.whatsapp_phone || '',
    whatsapp_message: props.configuracion.whatsapp_message || '',
    contact_email: props.configuracion.contact_email || '',
    contact_phone: props.configuracion.contact_phone || '',
    seo_default_title: props.configuracion.seo_default_title || '',
    seo_default_description: props.configuracion.seo_default_description || '',
    seo_default_og_image: props.configuracion.seo_default_og_image || '',
    google_maps_api_key: props.configuracion.google_maps_api_key || '',
    analytics_enabled: Boolean(props.configuracion.analytics_enabled),
    analytics_provider: props.configuracion.analytics_provider || 'ga4',
    analytics_measurement_id: props.configuracion.analytics_measurement_id || '',
    analytics_anonymize_ip: props.configuracion.analytics_anonymize_ip ?? true,
    cookie_consent_enabled: props.configuracion.cookie_consent_enabled ?? true,
    cookie_consent_layout: props.configuracion.cookie_consent_layout || 'box',
    cookie_consent_position: props.configuracion.cookie_consent_position || 'bottom right',
    cookie_consent_theme: props.configuracion.cookie_consent_theme || 'auto',
    cookie_consent_revision: Number(props.configuracion.cookie_consent_revision) || 1,
    cookie_consent_privacy_url: props.configuracion.cookie_consent_privacy_url || '',
    cookie_consent_cookie_url: props.configuracion.cookie_consent_cookie_url || '',
    cookie_consent_title: props.configuracion.cookie_consent_title || '',
    cookie_consent_description: props.configuracion.cookie_consent_description || '',
});

// Al cambiar el color primario, sincronizar el fondo del botón primario por defecto
watch(() => form.primary_color, (nuevoVal) => {
    if (nuevoVal && form.button_primary_bg === props.configuracion.primary_color) {
        form.button_primary_bg = nuevoVal;
    }
});

// Al cambiar el color secundario, sincronizar el fondo del botón secundario por defecto
watch(() => form.secondary_color, (nuevoVal) => {
    if (nuevoVal && form.button_secondary_bg === props.configuracion.secondary_color) {
        form.button_secondary_bg = nuevoVal;
    }
});

function guardar() {
    form.put(route('configuracion.update'), {
        preserveScroll: true,
        onError: (errors) => {
            console.error('Error al guardar configuración:', errors);
            const primerError = Object.values(errors)[0];
            toast.add({
                severity: 'error',
                summary: 'Error al guardar',
                detail: Array.isArray(primerError) ? primerError[0] : (primerError || 'Revise los campos del formulario.'),
                life: 5000,
            });
        },
    });
}
</script>

<template>
    <Head title="Configuración del Sitio" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3 sm:gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white leading-none truncate">
                        Configuración Global
                    </h1>
                </div>

                <button
                    type="button"
                    @click="guardar()"
                    :disabled="form.processing"
                    class="px-3.5 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition-colors shadow-sm cursor-pointer inline-flex items-center gap-2 shrink-0"
                >
                    <i v-if="form.processing" class="pi pi-spin pi-spinner" />
                    <i v-else class="pi pi-check text-xs" />
                    <span class="hidden sm:inline">Guardar Ajustes</span>
                    <span class="sm:hidden">Guardar</span>
                </button>
            </div>
        </template>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Menú Lateral de Navegación por Secciones (Tabs Verticals) -->
                <aside class="lg:col-span-3">
                    <div class="sticky top-24 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-3 shadow-xs space-y-1">
                        <div class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            Ajustes Globales
                        </div>

                        <button
                            v-for="sec in seccionesMenu"
                            :key="sec.id"
                            type="button"
                            @click="pestañaActiva = sec.id"
                            :class="[
                                'w-full text-left px-3 py-2.5 rounded-xl transition-all flex items-start gap-3 cursor-pointer group',
                                pestañaActiva === sec.id
                                    ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-900 dark:text-emerald-100 font-bold border border-emerald-500/30'
                                    : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800/60 hover:text-zinc-900 dark:hover:text-zinc-100'
                            ]"
                        >
                            <i :class="[sec.icono, 'text-base mt-0.5 shrink-0', pestañaActiva === sec.id ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300']" />
                            <div class="min-w-0 flex-1">
                                <span class="block text-xs font-semibold leading-tight">{{ sec.titulo }}</span>
                                <span class="block text-[10px] text-zinc-400 dark:text-zinc-500 font-normal truncate mt-0.5">{{ sec.desc }}</span>
                            </div>
                        </button>
                    </div>
                </aside>

                <!-- Panel Principal de Formulario Modular -->
                <main class="lg:col-span-9 space-y-6">
                    <form @submit.prevent="guardar()" class="space-y-6">

                        <IdentidadSection v-show="pestañaActiva === 'identidad'" :form="form" />
                        <CabeceraSection v-show="pestañaActiva === 'cabecera'" :form="form" />
                        <FuentesSection v-show="pestañaActiva === 'fuentes'" :form="form" :fuentesDisponibles="fuentesDisponibles" />
                        <ColoresSection v-show="pestañaActiva === 'colores'" :form="form" />
                        <BotonesSection v-show="pestañaActiva === 'botones'" :form="form" />
                        <RedesSection v-show="pestañaActiva === 'redes'" :form="form" />
                        <SeoSection v-show="pestañaActiva === 'seo'" :form="form" />
                        <IntegracionesSection v-show="pestañaActiva === 'integraciones'" :form="form" />
                        <FooterSection v-show="pestañaActiva === 'footer'" :form="form" />

                        <!-- Botón Inferior de Guardar Cambios -->
                        <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-800">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition-colors shadow-sm cursor-pointer inline-flex items-center gap-2"
                            >
                                <i v-if="form.processing" class="pi pi-spin pi-spinner" />
                                <i v-else class="pi pi-check" />
                                <span>Guardar Configuración</span>
                            </button>
                        </div>
                    </form>
                </main>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
