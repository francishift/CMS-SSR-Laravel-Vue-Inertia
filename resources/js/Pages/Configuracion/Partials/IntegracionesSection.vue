<script setup>
/**
 * Componente IntegracionesSection: Gestión de APIs y Servicios Externos.
 *
 * Incluye:
 * 1. Google Maps JavaScript API (con fallback nativo OpenStreetMap).
 * 2. Analítica Web (Google Analytics 4 & Google Tag Manager con Google Consent Mode v2).
 * 3. Banner de Consentimiento de Cookies (Orest Bida vanilla-cookieconsent v3).
 */
import { ref, computed } from 'vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    form: { type: Object, required: true },
});

const toast = useToast();
const mostrarClaveMaps = ref(false);
const mostrarAvanzadoCookies = ref(false);

// Google Maps
const tieneClaveMaps = computed(() => {
    return Boolean(props.form.google_maps_api_key && props.form.google_maps_api_key.trim().length > 0);
});

const dominioReferrer = computed(() => {
    if (typeof window === 'undefined') return 'https://tudominio.com/*';
    return `${window.location.origin}/*`;
});

const copiarReferrer = async () => {
    try {
        await navigator.clipboard.writeText(dominioReferrer.value);
        toast.add({
            severity: 'success',
            summary: 'Copiado',
            detail: 'Patrón de dominio copiado al portapapeles.',
            life: 2500,
        });
    } catch {
        // Fallback
    }
};

// Analytics
const tieneAnalyticsActivo = computed(() => {
    return Boolean(props.form.analytics_enabled && props.form.analytics_measurement_id && props.form.analytics_measurement_id.trim().length > 0);
});

const opcionesProveedores = [
    { label: 'Google Analytics 4 (Recomendado)', value: 'ga4' },
    { label: 'Google Tag Manager (GTM)', value: 'gtm' },
];

const placeholderAnalyticsId = computed(() => {
    return props.form.analytics_provider === 'gtm' ? 'GTM-XXXXXXX' : 'G-XXXXXXXXXX';
});

// Cookie Consent
const opcionesLayoutCookies = [
    { label: 'Caja flotante moderna (Box)', value: 'box' },
    { label: 'Barra horizontal inferior (Bar)', value: 'bar' },
    { label: 'Nube flotante compacta (Cloud)', value: 'cloud' },
];

const opcionesPosicionCookies = [
    { label: 'Inferior derecha (Recomendada)', value: 'bottom right' },
    { label: 'Inferior izquierda', value: 'bottom left' },
    { label: 'Inferior centro', value: 'bottom center' },
    { label: 'Superior derecha', value: 'top right' },
    { label: 'Superior izquierda', value: 'top left' },
    { label: 'Barra inferior completa', value: 'bottom' },
];

const opcionesTemaCookies = [
    { label: 'Automático (hereda tema claro/oscuro del sitio)', value: 'auto' },
    { label: 'Siempre claro (Light)', value: 'light' },
    { label: 'Siempre oscuro (Dark)', value: 'dark' },
];

const incrementarRevision = () => {
    props.form.cookie_consent_revision = (Number(props.form.cookie_consent_revision) || 1) + 1;
    toast.add({
        severity: 'info',
        summary: 'Revisión incrementada',
        detail: `Nueva revisión: ${props.form.cookie_consent_revision}. Al guardar, los usuarios que ya hayan aceptado volverán a ver el banner para dar su consentimiento renovado.`,
        life: 4000,
    });
};
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-8">
        <!-- Encabezado de la Sección -->
        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                    <i class="pi pi-sliders-h" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Integraciones & Servicios Externos</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Google Maps, Analítica Web y gestión de consentimiento de Cookies RGPD</p>
                </div>
            </div>

            <!-- Resumen de Estados -->
            <div class="hidden sm:flex items-center gap-2">
                <span
                    v-if="tieneAnalyticsActivo"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                    Analytics Activo
                </span>
                <span
                    v-if="form.cookie_consent_enabled"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500" />
                    Cookies RGPD
                </span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 1. TARJETA: ANALÍTICA WEB (GA4 / GTM)       -->
        <!-- ========================================== -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-950/50 text-orange-600 dark:text-orange-400 flex items-center justify-center text-sm">
                        <i class="pi pi-chart-line" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Analítica Web & Métricas (Google Analytics 4 / GTM)</h3>
                        <p class="text-[11px] text-zinc-500">Mide visitas y eventos SPA respetando el consentimiento del visitante mediante Google Consent Mode v2</p>
                    </div>
                </div>

                <!-- Interruptor Activar Analítica -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                        {{ form.analytics_enabled ? 'Activo' : 'Desactivado' }}
                    </span>
                    <ToggleSwitch v-model="form.analytics_enabled" />
                </div>
            </div>

            <div v-if="form.analytics_enabled" class="space-y-4 pt-2 border-t border-zinc-200/80 dark:border-zinc-800">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Proveedor -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Proveedor de Analítica
                        </label>
                        <Select
                            v-model="form.analytics_provider"
                            :options="opcionesProveedores"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- ID de Medición -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            ID de Medición (Measurement ID / Container ID)
                        </label>
                        <div class="flex items-center gap-2">
                            <InputText
                                v-model="form.analytics_measurement_id"
                                :placeholder="placeholderAnalyticsId"
                                class="w-full text-xs font-mono"
                            />
                            <Button
                                v-if="form.analytics_measurement_id"
                                icon="pi pi-times"
                                size="small"
                                severity="secondary"
                                variant="outlined"
                                v-tooltip.top="'Limpiar ID'"
                                @click="form.analytics_measurement_id = ''"
                            />
                        </div>
                    </div>
                </div>

                <!-- Anonimizar IP -->
                <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <div class="space-y-0.5">
                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">Anonimización estricta de IP (Anonymize IP)</span>
                        <p class="text-[11px] text-zinc-500">Oculta los últimos dígitos de la IP del visitante antes de almacenarla en los servidores de Google.</p>
                    </div>
                    <ToggleSwitch v-model="form.analytics_anonymize_ip" />
                </div>

                <!-- Notificación Google Consent Mode v2 -->
                <div class="flex items-start gap-2.5 p-3.5 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl border border-blue-200/60 dark:border-blue-800/60 text-[11px] text-zinc-600 dark:text-zinc-300">
                    <i class="pi pi-shield text-blue-600 dark:text-blue-400 text-sm mt-0.5" />
                    <div>
                        <strong class="text-zinc-900 dark:text-zinc-100">Blindaje con Google Consent Mode v2 y Navegación SPA:</strong>
                        Las etiquetas no recopilarán datos personales ni cookies analíticas hasta que el visitante acepte explícitamente en el banner. Además, las visitas entre páginas SPA se registran de forma asíncrona y fluida en Inertia sin duplicar recuentos.
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 2. TARJETA: BANNER COOKIECONSENT (OREST BIDA) -->
        <!-- ========================================== -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm">
                        <i class="pi pi-check-circle" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Consentimiento de Cookies (Orest Bida CookieConsent)</h3>
                        <p class="text-[11px] text-zinc-500">Banner nativo ultraligero y accesible que cumple con el RGPD, ePrivacy y la normativa europea</p>
                    </div>
                </div>

                <!-- Interruptor Activar Banner de Cookies -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                        {{ form.cookie_consent_enabled ? 'Activo' : 'Desactivado' }}
                    </span>
                    <ToggleSwitch v-model="form.cookie_consent_enabled" />
                </div>
            </div>

            <div v-if="form.cookie_consent_enabled" class="space-y-4 pt-2 border-t border-zinc-200/80 dark:border-zinc-800">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Formato / Disposición -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Diseño del Banner
                        </label>
                        <Select
                            v-model="form.cookie_consent_layout"
                            :options="opcionesLayoutCookies"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Posición -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Posición en Pantalla
                        </label>
                        <Select
                            v-model="form.cookie_consent_position"
                            :options="opcionesPosicionCookies"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- Tema -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Tema Visual
                        </label>
                        <Select
                            v-model="form.cookie_consent_theme"
                            :options="opcionesTemaCookies"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Enlaces Legales -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Ruta / URL de la Política de Cookies
                        </label>
                        <InputText
                            v-model="form.cookie_consent_cookie_url"
                            placeholder="/politica-de-cookies"
                            class="w-full text-xs"
                        />
                        <p class="text-[10px] text-zinc-500 mt-1">Enlace hacia la página donde detallas el uso de cookies.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Ruta / URL de la Política de Privacidad
                        </label>
                        <InputText
                            v-model="form.cookie_consent_privacy_url"
                            placeholder="/politica-de-privacidad"
                            class="w-full text-xs"
                        />
                        <p class="text-[10px] text-zinc-500 mt-1">Enlace a la política de protección de datos personales.</p>
                    </div>
                </div>

                <!-- Control de Revisión / Forzar Re-consentimiento -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Revisión Actual de Consentimiento:</span>
                            <span class="px-2 py-0.5 text-xs font-mono font-bold rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                v{{ form.cookie_consent_revision }}
                            </span>
                        </div>
                        <p class="text-[11px] text-zinc-500 max-w-xl">
                            Si cambias las condiciones o añades nuevos servicios de rastreo, debes incrementar la revisión para solicitar de nuevo la aprobación del visitante.
                        </p>
                    </div>

                    <Button
                        type="button"
                        label="Forzar Re-consentimiento"
                        icon="pi pi-refresh"
                        size="small"
                        severity="secondary"
                        variant="outlined"
                        class="text-xs shrink-0"
                        @click="incrementarRevision"
                    />
                </div>

                <!-- Acordeón / Alternador de Textos Personalizados -->
                <div class="border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden bg-white dark:bg-zinc-900">
                    <button
                        type="button"
                        @click="mostrarAvanzadoCookies = !mostrarAvanzadoCookies"
                        class="w-full flex items-center justify-between p-3.5 text-xs font-bold text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <i :class="mostrarAvanzadoCookies ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" class="text-xs text-zinc-400" />
                            <span>Personalización de Textos del Banner (Opcional)</span>
                        </div>
                        <span class="text-[11px] font-normal text-zinc-400">
                            {{ mostrarAvanzadoCookies ? 'Ocultar' : 'Modificar textos por defecto' }}
                        </span>
                    </button>

                    <div v-show="mostrarAvanzadoCookies" class="p-4 pt-1 space-y-3.5 border-t border-zinc-200/80 dark:border-zinc-800">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Título del Banner
                            </label>
                            <InputText
                                v-model="form.cookie_consent_title"
                                placeholder="Gestionar cookies y privacidad (Dejar vacío para el título por defecto)"
                                class="w-full text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Descripción o Mensaje Explicativo
                            </label>
                            <Textarea
                                v-model="form.cookie_consent_description"
                                rows="3"
                                placeholder="Utilizamos cookies propias y de terceros para fines analíticos... (Dejar vacío para el texto estándar)"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 3. TARJETA: GOOGLE MAPS JAVASCRIPT API     -->
        <!-- ========================================== -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center text-sm">
                        <i class="pi pi-map-marker" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Google Maps JavaScript API</h3>
                        <p class="text-[11px] text-zinc-500">Necesaria si deseas utilizar Google Maps en lugar de OpenStreetMap en el constructor de páginas</p>
                    </div>
                </div>

                <a
                    href="https://console.cloud.google.com/google/maps-apis/credentials"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-xs text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 font-semibold inline-flex items-center gap-1 cursor-pointer shrink-0"
                >
                    <span>Consola de Google Cloud</span>
                    <i class="pi pi-external-link text-[10px]" />
                </a>
            </div>

            <!-- Campo Clave API con alternador de contraseña -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                    Clave de API de Google Maps (API Key)
                </label>
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <InputText
                            v-model="form.google_maps_api_key"
                            :type="mostrarClaveMaps ? 'text' : 'password'"
                            autocomplete="new-password"
                            placeholder="AIzaSy..."
                            class="w-full text-xs font-mono pr-10"
                        />
                        <button
                            type="button"
                            @click="mostrarClaveMaps = !mostrarClaveMaps"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded transition-colors cursor-pointer"
                            :title="mostrarClaveMaps ? 'Ocultar clave' : 'Mostrar clave'"
                        >
                            <i :class="mostrarClaveMaps ? 'pi pi-eye-slash' : 'pi pi-eye'" class="text-xs" />
                        </button>
                    </div>

                    <Button
                        v-if="form.google_maps_api_key"
                        icon="pi pi-times"
                        size="small"
                        severity="secondary"
                        variant="outlined"
                        v-tooltip.top="'Limpiar clave'"
                        @click="form.google_maps_api_key = ''"
                    />
                </div>
                <p class="text-[11px] text-zinc-500 mt-1.5">
                    Esta clave se almacena de forma cifrada en la base de datos para máxima protección.
                </p>
            </div>

            <!-- Panel de Seguridad y Buenas Prácticas -->
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 text-xs text-amber-900 dark:text-amber-200 space-y-2.5">
                <div class="flex items-center gap-2 font-bold text-amber-800 dark:text-amber-300">
                    <i class="pi pi-shield text-sm" />
                    <span>Recomendación Crítica de Seguridad (Google Cloud):</span>
                </div>
                <p class="text-[11px] leading-relaxed text-zinc-600 dark:text-zinc-300">
                    Dado que las llamadas al mapa se efectúan desde el navegador del visitante, para evitar que terceros copien tu clave y generen costes, <strong>debes restringirla en Google Cloud Console</strong>:
                </p>
                <ol class="list-decimal list-inside space-y-1 text-[11px] text-zinc-600 dark:text-zinc-300 pl-1">
                    <li>Accede a tu credencial en Google Cloud &gt; <em>Restricciones de aplicaciones</em> &gt; Selecciona <strong>Sitios web (referencias HTTP)</strong>.</li>
                    <li>
                        Añade la URL autorizada de este sitio:
                        <span class="inline-flex items-center gap-1.5 ml-1 font-mono font-bold bg-white dark:bg-zinc-900 px-2 py-0.5 rounded border border-amber-300/40 dark:border-amber-700/40 text-[10px]">
                            {{ dominioReferrer }}
                            <button
                                type="button"
                                @click="copiarReferrer"
                                class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200 cursor-pointer"
                                title="Copiar patrón"
                            >
                                <i class="pi pi-copy text-[10px]" />
                            </button>
                        </span>
                    </li>
                    <li>En <em>Restricciones de API</em>, selecciona únicamente <strong>Maps JavaScript API</strong>.</li>
                </ol>
            </div>

            <!-- Alternativa OpenStreetMap (Sin Coste) -->
            <div class="flex items-start gap-3 p-3.5 bg-emerald-50/60 dark:bg-emerald-950/30 rounded-xl border border-emerald-200/60 dark:border-emerald-800/60">
                <i class="pi pi-check-circle text-emerald-600 dark:text-emerald-400 text-sm mt-0.5" />
                <div class="text-[11px] text-zinc-600 dark:text-zinc-300 leading-normal">
                    <strong class="text-zinc-900 dark:text-zinc-100">¿No tienes clave de Google Maps?</strong>
                    El elemento Mapa del constructor de páginas incluye soporte nativo y gratuito con <strong>OpenStreetMap (Leaflet)</strong>. Funciona sin tarjeta de crédito, sin clave API, con filtros de saturación y modo oscuro, y cumpliendo al 100% con la privacidad RGPD.
                </div>
            </div>
        </div>
    </section>
</template>
