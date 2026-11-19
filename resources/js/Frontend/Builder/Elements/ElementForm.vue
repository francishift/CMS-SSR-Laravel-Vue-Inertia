<script setup>
/**
 * Elemento Formulario Público del Page Builder (SSR-compatible).
 *
 * Renderiza un formulario dinámico con 23 tipos de campo en la web pública,
 * con validación en cliente en tiempo real, envío asíncrono seguro,
 * feedback visual premium (estados, animaciones, transiciones)
 * y protección anti-bot (honeypot invisible + CSRF automático de Axios).
 *
 * SEGURIDAD:
 * - CSRF: Axios incluye automáticamente el token X-XSRF-TOKEN desde la cookie.
 * - Honeypot: Campo invisible renderizado si el formulario lo tiene configurado.
 * - Rate Limit: El servidor aplica throttle 5/minuto por IP.
 * - Sanitización: Toda entrada se sanitiza en el backend (HtmlSanitizerService).
 * - Validación doble: Cliente (UX) + Servidor (seguridad real).
 * - Anti-enumeración: Se usa slug público, no ID numérico.
 */
import { computed, onMounted, onBeforeUnmount, ref, reactive, nextTick, watch, defineAsyncComponent } from 'vue';
import axios from 'axios';
import { useElementAnimation } from '@/Composables/useElementAnimation';
import { useSanitize, sanitizeInlineCss } from '@/Composables/useSanitize';

const PageBuilderRenderer = defineAsyncComponent(() => import('@/Frontend/Builder/PageBuilderRenderer.vue'));

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
    resolvedData: { type: [Object, Array], default: null },
});

const { animAttrs, animStyle } = useElementAnimation(props);
const { sanitize } = useSanitize();

// ─── Estado del Modal Legal (Términos / Privacidad) ──────────────────────────
const modalLegalVisible = ref(false);
const modalLegalCargando = ref(false);
const modalLegalTitulo = ref('');
const modalLegalContenido = ref('');
const modalLegalLayout = ref(null);
const modalLegalError = ref('');
const modalLegalUrl = ref('');

function tieneEnlace(campo) {
    if (!campo) return false;
    if (campo.link_url && campo.link_url.trim() !== '') return true;
    if (campo.label && /<a\s+[^>]*href=["']([^"']*)["'][^>]*>(.*?)<\/a>/i.test(campo.label)) return true;
    return false;
}

function obtenerUrlEnlace(campo) {
    if (!campo) return '';
    if (campo.link_url && campo.link_url.trim() !== '') {
        return campo.link_url.trim();
    }
    const match = campo.label?.match(/<a\s+[^>]*href=["']([^"']*)["'][^>]*>/i);
    return match ? match[1] : '';
}

function obtenerTextoLabel(campo) {
    if (!campo || !campo.label) return '';
    return campo.label.replace(/<[^>]*>/g, '').trim();
}

function esEnlaceModal(campo) {
    return (campo?.link_target || 'modal') === 'modal';
}

async function abrirModalLegal(url, tituloFallback) {
    if (!url) return;

    modalLegalVisible.value = true;
    modalLegalCargando.value = true;
    modalLegalTitulo.value = tituloFallback || 'Información Legal';
    modalLegalContenido.value = '';
    modalLegalLayout.value = null;
    modalLegalError.value = '';
    modalLegalUrl.value = url;

    if (typeof document !== 'undefined') {
        document.body.style.overflow = 'hidden';
    }

    try {
        const slugLimpio = url.replace(/^\/+|\/+$/g, '').split('?')[0];
        const res = await axios.get(`/api/paginas/${encodeURIComponent(slugLimpio)}`);
        if (res.data?.success) {
            modalLegalTitulo.value = res.data.title || modalLegalTitulo.value;
            modalLegalContenido.value = res.data.body || '';
            modalLegalLayout.value = res.data.layout_json || null;
        } else {
            throw new Error('Respuesta no satisfactoria');
        }
    } catch {
        modalLegalError.value = 'No se pudo cargar el documento directamente en la ventana emergente.';
    } finally {
        modalLegalCargando.value = false;
    }
}

function cerrarModalLegal() {
    modalLegalVisible.value = false;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
}

function manejarTecladoModal(e) {
    if (e.key === 'Escape' && modalLegalVisible.value) {
        cerrarModalLegal();
    }
}

// Slug del formulario configurado en el inspector del Builder o recibido vía resolvedData
const slug = computed(() => props.content?.form_slug || props.resolvedData?.slug || '');

// ─── Estado del componente ──────────────────────────────────────────────────
const formData = ref(props.resolvedData || null);        // Esquema del formulario cargado (SSR o AJAX)
const valores = reactive({});                            // Valores reactivos del formulario
const errores = reactive({});                            // Errores de validación por campo
const estado = ref(props.resolvedData || !slug.value ? 'idle' : 'loading'); // idle | loading | submitting | success | error
const mensajeExito = ref('');
const mensajeError = ref('');
const redirectUrl = ref(null);
const honeypotValue = ref('');                           // Campo trampa: si un bot lo rellena → spam
const formRef = ref(null);
const turnstileToken = ref('');
const turnstileContainer = ref(null);
let turnstileWidgetId = null;

function inicializarValoresConDefaults(schema) {
    if (!schema?.fields_schema) return;
    for (const campo of schema.fields_schema) {
        if (!campo.name || ['heading', 'paragraph', 'separator'].includes(campo.type)) continue;
        const def = campo.default_value || '';
        if (campo.type === 'checkbox' || campo.type === 'toggle') {
            valores[campo.name] = (def === 'true' || def === '1');
        } else if (campo.type === 'multiselect') {
            valores[campo.name] = [];
        } else {
            valores[campo.name] = def;
        }
    }
}

// Si ya viene pre-resuelto desde el backend / SSR (Nivel 1), inicializar valores de inmediato
if (formData.value) {
    inicializarValoresConDefaults(formData.value);
}

// ─── Estado del Toast flotante de éxito ────────────────────────────────────
const toastVisible = ref(false);
const toastMensaje = ref('');
const toastProgress = ref(100);
let toastTimer = null;
let toastProgressInterval = null;

function mostrarToast(mensaje, duracionMs = 6000) {
    toastMensaje.value = mensaje;
    toastVisible.value = true;
    toastProgress.value = 100;

    if (toastTimer) clearTimeout(toastTimer);
    if (toastProgressInterval) clearInterval(toastProgressInterval);

    const step = 50;
    const decrement = (step / duracionMs) * 100;

    toastProgressInterval = setInterval(() => {
        toastProgress.value = Math.max(0, toastProgress.value - decrement);
        if (toastProgress.value <= 0) {
            clearInterval(toastProgressInterval);
        }
    }, step);

    toastTimer = setTimeout(() => {
        cerrarToast();
    }, duracionMs);
}

function cerrarToast() {
    toastVisible.value = false;
    if (toastTimer) clearTimeout(toastTimer);
    if (toastProgressInterval) clearInterval(toastProgressInterval);
}

function loadTurnstileScript(cb) {
    if (window.turnstile && typeof window.turnstile.render === 'function') {
        cb();
        return;
    }
    const existing = document.querySelector('script[src*="turnstile/v0/api.js"]');
    if (existing) {
        if (window.turnstile && typeof window.turnstile.render === 'function') {
            cb();
        } else {
            existing.addEventListener('load', () => cb(), { once: true });
        }
        return;
    }
    const script = document.createElement('script');
    // Inyectar preconnect dinámico para acelerar la carga del captcha
    if (!document.querySelector('link[rel="preconnect"][href="https://challenges.cloudflare.com"]')) {
        const link = document.createElement('link');
        link.rel = 'preconnect';
        link.href = 'https://challenges.cloudflare.com';
        link.crossOrigin = 'anonymous';
        document.head.appendChild(link);
    }
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
    script.async = true;
    script.defer = true;
    script.onload = () => cb();
    document.head.appendChild(script);
}

function initTurnstile() {
    if (!formData.value?.requires_captcha || !formData.value?.turnstile_site_key || !turnstileContainer.value) {
        return;
    }

    loadTurnstileScript(() => {
        if (!window.turnstile || !turnstileContainer.value) return;

        if (turnstileWidgetId !== null) {
            try { window.turnstile.remove(turnstileWidgetId); } catch (_) {}
            turnstileWidgetId = null;
        }

        try {
            turnstileWidgetId = window.turnstile.render(turnstileContainer.value, {
                sitekey: formData.value.turnstile_site_key,
                theme: 'auto',
                callback: (token) => {
                    turnstileToken.value = token;
                },
                'error-callback': () => {
                    turnstileToken.value = '';
                },
                'expired-callback': () => {
                    turnstileToken.value = '';
                    if (turnstileWidgetId !== null && window.turnstile) {
                        try { window.turnstile.reset(turnstileWidgetId); } catch (_) {}
                    }
                },
            });
        } catch (e) {
            console.warn('Turnstile init error:', e);
        }
    });
}

onBeforeUnmount(() => {
    if (turnstileWidgetId !== null && window.turnstile) {
        try { window.turnstile.remove(turnstileWidgetId); } catch (_) {}
        turnstileWidgetId = null;
    }
    if (toastTimer) clearTimeout(toastTimer);
    if (toastProgressInterval) clearInterval(toastProgressInterval);
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', manejarTecladoModal);
    }
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
// Campos que se validan y envían (excluye decorativos)
const camposValidables = computed(() => {
    if (!formData.value?.fields_schema) return [];
    return formData.value.fields_schema.filter(
        f => f.name && !['heading', 'paragraph', 'separator'].includes(f.type)
    );
});

// ─── Carga asíncrona del esquema del formulario (Fallback cliente / Inspector) ────
async function cargarFormularioAjax() {
    if (!slug.value) {
        estado.value = 'idle';
        return;
    }

    estado.value = 'loading';
    mensajeError.value = '';
    try {
        const { data } = await axios.get(`/api/formularios/${slug.value}`);
        formData.value = data;
        inicializarValoresConDefaults(data);
        estado.value = 'idle';

        if (data.requires_captcha && data.turnstile_site_key) {
            await nextTick();
            initTurnstile();
        }

        if (data.scripts?.before) {
            try {
                new Function('valores', data.scripts.before)(valores);
            } catch (e) {
                console.error('Error en scripts_before:', e);
            }
        }
    } catch {
        estado.value = 'error';
        mensajeError.value = 'No se pudo cargar el formulario.';
    }
}

// ─── Ciclo de vida y reactividad ─────────────────────────────────────────────
onMounted(async () => {
    if (typeof window !== 'undefined') {
        window.addEventListener('keydown', manejarTecladoModal);
    }

    // Si ya venía resuelto desde el servidor (SSR / Nivel 1)
    if (formData.value) {
        if (formData.value.requires_captcha && formData.value.turnstile_site_key) {
            await nextTick();
            initTurnstile();
        }

        if (formData.value.scripts?.before) {
            try {
                new Function('valores', formData.value.scripts.before)(valores);
            } catch (e) {
                console.error('Error en scripts_before:', e);
            }
        }
        return;
    }

    if (!slug.value) {
        estado.value = 'idle';
        return;
    }

    await cargarFormularioAjax();
});

// Sincronizar si cambian los datos pre-resueltos (ej. navegación SPA o Inertia)
watch(() => props.resolvedData, (newResolved) => {
    if (newResolved) {
        formData.value = newResolved;
        inicializarValoresConDefaults(newResolved);
        estado.value = 'idle';
    }
}, { deep: true });

// Sincronizar si en el inspector del builder se cambia el slug seleccionado
watch(slug, async (newSlug, oldSlug) => {
    if (newSlug !== oldSlug) {
        if (formData.value && formData.value.slug === newSlug) {
            return;
        }
        if (!newSlug) {
            formData.value = null;
            estado.value = 'idle';
            return;
        }
        await cargarFormularioAjax();
    }
});

// Todos los campos incluyendo decorativos (para renderizar)
const todosCampos = computed(() => formData.value?.fields_schema || []);

// Configuración general del formulario
const settings = computed(() => formData.value?.settings || {});

// ─── Validación en cliente ──────────────────────────────────────────────────
function validarCampo(campo) {
    const val = valores[campo.name];
    const err = [];

    if (campo.required) {
        if (campo.type === 'checkbox' || campo.type === 'toggle') {
            if (!val) err.push('Este campo es obligatorio.');
        } else if (Array.isArray(val)) {
            if (!val.length) err.push('Selecciona al menos una opción.');
        } else if (!val || (typeof val === 'string' && !val.trim())) {
            err.push('Este campo es obligatorio.');
        }
    }

    if (val && typeof val === 'string' && val.trim()) {
        if (campo.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            err.push('Introduce un email válido.');
        }
        if (campo.type === 'url' && !/^https?:\/\/.+/i.test(val)) {
            err.push('Introduce una URL válida (https://...).');
        }
        if (campo.type === 'tel' && !/^[+]?[0-9\s\-()]{6,20}$/.test(val)) {
            err.push('Introduce un teléfono válido.');
        }
        if (campo.min_length && val.length < campo.min_length) {
            err.push(`Mínimo ${campo.min_length} caracteres.`);
        }
        if (campo.max_length && val.length > campo.max_length) {
            err.push(`Máximo ${campo.max_length} caracteres.`);
        }
    }

    if (val !== undefined && val !== '' && val !== null) {
        if (campo.type === 'number' || campo.type === 'slider' || campo.type === 'rating') {
            const num = Number(val);
            if (campo.min !== undefined && campo.min !== null && num < Number(campo.min)) {
                err.push(`El valor mínimo es ${campo.min}.`);
            }
            if (campo.max !== undefined && campo.max !== null && num > Number(campo.max)) {
                err.push(`El valor máximo es ${campo.max}.`);
            }
        }
    }

    errores[campo.name] = err.length ? err[0] : '';
    return err.length === 0;
}

function validarTodo() {
    let valido = true;
    for (const campo of camposValidables.value) {
        if (!validarCampo(campo)) valido = false;
    }
    return valido;
}

// ─── Lógica condicional ─────────────────────────────────────────────────────
function esVisible(campo) {
    if (!campo.conditional?.field) return true;
    const valorRef = valores[campo.conditional.field];
    const valorCond = campo.conditional.value;
    const op = campo.conditional.operator || 'equals';

    switch (op) {
        case 'equals': return String(valorRef) === String(valorCond);
        case 'not_equals': return String(valorRef) !== String(valorCond);
        case 'contains': return String(valorRef || '').includes(valorCond);
        case 'not_empty': return !!valorRef && String(valorRef).trim() !== '';
        case 'empty': return !valorRef || String(valorRef).trim() === '';
        default: return true;
    }
}

// ─── Envío seguro ───────────────────────────────────────────────────────────
async function enviarFormulario() {
    // Limpiar errores previos
    Object.keys(errores).forEach(k => errores[k] = '');
    mensajeError.value = '';

    // Validación en cliente
    if (!validarTodo()) {
        mensajeError.value = 'Por favor, revisa los campos marcados.';
        // Scroll al primer campo con error
        await nextTick();
        const primerError = formRef.value?.querySelector('.form-field-error');
        if (primerError) primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // Preparar payload (solo campos visibles por lógica condicional)
    const payload = { fields: {} };
    for (const campo of camposValidables.value) {
        if (esVisible(campo)) {
            payload.fields[campo.name] = valores[campo.name];
        }
    }

    // Scripts On Submit
    if (formData.value?.scripts?.on_submit) {
        try {
            const cancel = new Function('valores', formData.value.scripts.on_submit)(valores);
            if (cancel === false) return;
        } catch (e) {
            console.error('Error en scripts_on_submit:', e);
        }
    }

    // Honeypot: incluir si existe
    if (formData.value?.honeypot_field) {
        payload[formData.value.honeypot_field] = honeypotValue.value;
    }

    // Cloudflare Turnstile token
    if (formData.value?.requires_captcha && turnstileToken.value) {
        payload.turnstile_token = turnstileToken.value;
    }

    estado.value = 'submitting';

    try {
        const { data } = await axios.post(`/formularios/${slug.value}/enviar`, payload);

        mensajeExito.value = data.message || '¡Formulario enviado correctamente!';
        redirectUrl.value = data.redirect_url || null;

        const feedbackType = formData.value?.settings?.success_feedback || 'both';

        if (feedbackType === 'toast') {
            // Solo Toast: limpia los campos y deja el formulario interactivo
            resetearFormulario();
            mostrarToast(mensajeExito.value);
        } else {
            // 'both' o 'inline': muestra el estado de éxito en el contenedor
            estado.value = 'success';
            if (feedbackType === 'both') {
                mostrarToast(mensajeExito.value);
            }
        }

        // Ancla / Auto-scroll suave hacia el formulario
        await nextTick();
        if (formRef.value) {
            formRef.value.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (formData.value?.scripts?.after) {
            try {
                new Function('valores', 'respuesta', formData.value.scripts.after)(valores, data);
            } catch (e) {
                console.error('Error en scripts_after:', e);
            }
        }

        turnstileToken.value = '';
        if (turnstileWidgetId !== null && window.turnstile) {
            try { window.turnstile.reset(turnstileWidgetId); } catch (_) {}
        }

        // Redireccionar si está configurado
        if (redirectUrl.value) {
            setTimeout(() => {
                window.location.href = redirectUrl.value;
            }, 2000);
        }
    } catch (err) {
        estado.value = 'error';

        if (err.response?.status === 422) {
            // Errores de validación del servidor
            const serverErrors = err.response.data?.errors || {};
            Object.keys(serverErrors).forEach(key => {
                // Las claves vienen como 'fields.nombre', extraer el nombre limpio
                const cleanKey = key.replace('fields.', '');
                errores[cleanKey] = Array.isArray(serverErrors[key])
                    ? serverErrors[key][0]
                    : serverErrors[key];
            });
            mensajeError.value = err.response.data?.message || 'Por favor, revisa los campos del formulario.';
        } else if (err.response?.status === 429) {
            mensajeError.value = err.response.data?.message || 'Demasiados envíos. Espera un momento antes de intentarlo de nuevo.';
        } else if (err.response?.status === 404) {
            mensajeError.value = 'Este formulario ya no está disponible.';
        } else {
            mensajeError.value = 'Ha ocurrido un error inesperado. Inténtalo de nuevo más tarde.';
        }

        turnstileToken.value = '';
    }
}

function resetearFormulario() {
    estado.value = 'idle';
    mensajeExito.value = '';
    mensajeError.value = '';
    for (const campo of camposValidables.value) {
        if (campo.type === 'checkbox' || campo.type === 'toggle') {
            valores[campo.name] = false;
        } else if (campo.type === 'multiselect') {
            valores[campo.name] = [];
        } else {
            valores[campo.name] = campo.default_value || '';
        }
    }
    Object.keys(errores).forEach(k => errores[k] = '');
}

// ─── Estilos dinámicos ──────────────────────────────────────────────────────
const temaClase = computed(() => {
    const t = props.content?.theme || 'default';
    return {
        default: '',
        transparent: 'bg-transparent',
        card: 'bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800',
        glass: 'backdrop-blur-xl bg-white/60 dark:bg-zinc-900/60 border border-white/20 dark:border-zinc-700/30',
    }[t] || '';
});

const borderRadiusClase = computed(() => {
    const r = props.content?.border_radius || 'lg';
    return { none: '', sm: 'rounded-sm', md: 'rounded-md', lg: 'rounded-lg', xl: 'rounded-xl', '2xl': 'rounded-2xl' }[r] || 'rounded-lg';
});

const shadowClase = computed(() => {
    const s = props.content?.shadow || 'sm';
    return { none: '', sm: 'shadow-sm', md: 'shadow-md', lg: 'shadow-lg', xl: 'shadow-xl' }[s] || 'shadow-sm';
});

const botonColor = computed(() => {
    const c = settings.value?.submit_button_color || 'emerald';
    return {
        emerald: 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500',
        blue: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        indigo: 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500',
        rose: 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500',
        amber: 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500',
        violet: 'bg-violet-600 hover:bg-violet-700 text-white focus:ring-violet-500',
        cyan: 'bg-cyan-600 hover:bg-cyan-700 text-white focus:ring-cyan-500',
        zinc: 'bg-zinc-800 hover:bg-zinc-900 text-white focus:ring-zinc-500 dark:bg-zinc-200 dark:hover:bg-zinc-300 dark:!text-zinc-900',
        dark: 'bg-zinc-900 hover:bg-black text-white focus:ring-zinc-900 dark:bg-zinc-100 dark:hover:bg-white dark:!text-zinc-900',
        black: 'bg-black hover:bg-zinc-900 text-white focus:ring-zinc-900 dark:bg-white dark:hover:bg-zinc-200 dark:!text-black',
    }[c] || 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500';
});

const botonAlineacion = computed(() => {
    const a = settings.value?.submit_button_align || 'left';
    return { left: 'justify-start', center: 'justify-center', right: 'justify-end', full: '' }[a] || 'justify-start';
});

// ID personalizado y clases del inspector
const customId = computed(() => props.content?.element_id || undefined);
const customClasses = computed(() => props.content?.custom_classes || '');
const customCss = computed(() => sanitizeInlineCss(props.content?.custom_css || ''));

// Anchura de campo según configuración
function anchoClase(campo) {
    const w = campo.width || 'full';
    return {
        full: 'col-span-1 sm:col-span-12',
        half: 'col-span-1 sm:col-span-6',
        third: 'col-span-1 sm:col-span-4',
    }[w] || 'col-span-1 sm:col-span-12';
}

// Opciones parseadas de un campo de selección
function opcionesCampo(campo) {
    if (!campo.options || !Array.isArray(campo.options)) return [];
    return campo.options.map(opt => {
        if (typeof opt === 'string') return { label: opt, value: opt };
        return { label: opt.label || opt.value || '', value: opt.value || opt.label || '' };
    });
}
</script>

<template>
    <div
        :id="customId"
        ref="formRef"
        :class="['element-form w-full transition-all duration-500', temaClase, borderRadiusClase, shadowClase, customClasses]"
        :style="animStyle"
        v-bind="animAttrs"
    >
        <!-- CSS personalizado del inspector -->
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <!-- Estado: Cargando esquema -->
        <div v-if="estado === 'loading'" class="p-8 flex flex-col items-center justify-center gap-3">
            <div class="w-8 h-8 border-3 border-emerald-500/30 border-t-emerald-500 rounded-full animate-spin" />
            <p class="text-sm text-zinc-500 dark:text-zinc-400 animate-pulse">Cargando formulario…</p>
        </div>

        <!-- Estado: Sin formulario configurado -->
        <div v-else-if="!slug" class="p-8 text-center">
            <div class="w-14 h-14 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-3">
                <i class="pi pi-inbox text-xl text-zinc-400" />
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Ningún formulario seleccionado.</p>
        </div>

        <!-- Estado: Error de carga -->
        <div v-else-if="estado === 'error' && !formData" class="p-8 text-center">
            <div class="w-14 h-14 rounded-full bg-red-50 dark:bg-red-950/30 flex items-center justify-center mx-auto mb-3">
                <i class="pi pi-exclamation-triangle text-xl text-red-500" />
            </div>
            <p class="text-sm text-red-600 dark:text-red-400 font-medium">{{ mensajeError }}</p>
        </div>

        <!-- Estado: Envío exitoso -->
        <div
            v-else-if="estado === 'success'"
            class="p-8 sm:p-12 text-center"
        >
            <div class="form-success-icon w-20 h-20 rounded-full bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" class="form-check-anim" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">¡Mensaje enviado!</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 max-w-md mx-auto leading-relaxed">{{ mensajeExito }}</p>
            <p v-if="redirectUrl" class="text-xs text-zinc-400 dark:text-zinc-500 mt-4 animate-pulse">Redirigiendo…</p>
            <button
                v-else
                type="button"
                @click="resetearFormulario"
                class="mt-6 text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer"
            >
                Enviar otro mensaje
            </button>
        </div>

        <!-- Estado: Formulario activo -->
        <form
            v-else-if="formData"
            @submit.prevent="enviarFormulario"
            novalidate
            :class="['p-5 sm:p-8 space-y-1', content.compact ? 'space-y-0.5' : '']"
        >
            <!-- Alerta de error global -->
            <Transition name="form-fade">
                <div v-if="mensajeError && estado === 'error'" class="mb-5 p-3.5 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/50 flex items-start gap-2.5">
                    <i class="pi pi-exclamation-circle text-red-500 mt-0.5 shrink-0" />
                    <div class="text-sm text-red-700 dark:text-red-300">
                        <p class="font-medium">{{ mensajeError }}</p>
                        <p v-if="errores.turnstile" class="text-xs mt-1 text-red-600 dark:text-red-400 font-normal">
                            {{ errores.turnstile }}
                        </p>
                    </div>
                </div>
            </Transition>

            <!-- Grid de campos -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-x-4" :class="content.compact ? 'gap-y-3' : 'gap-y-5'">
                <template v-for="campo in todosCampos" :key="campo.id || campo.name">
                    <!-- Campos decorativos -->
                    <template v-if="campo.type === 'separator'">
                        <div class="col-span-1 sm:col-span-12 my-2">
                            <hr class="border-zinc-200 dark:border-zinc-700" />
                        </div>
                    </template>
                    <template v-else-if="campo.type === 'heading'">
                        <div class="col-span-1 sm:col-span-12">
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white mt-2">{{ campo.content || campo.label || '' }}</h3>
                        </div>
                    </template>
                    <template v-else-if="campo.type === 'paragraph'">
                        <div class="col-span-1 sm:col-span-12">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ campo.content || campo.label || '' }}</p>
                        </div>
                    </template>

                    <!-- Campos interactivos (con lógica condicional) -->
                    <template v-else-if="esVisible(campo)">
                        <div :class="[anchoClase(campo), 'form-field-wrapper']">
                            <!-- Label (siempre presente en DOM para lectores de pantalla; oculto visualmente si show_labels es false) -->
                            <label
                                v-if="campo.type !== 'checkbox' && campo.type !== 'toggle'"
                                :for="`field-${campo.name}`"
                                :class="content.show_labels !== false ? 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5' : 'sr-only'"
                            >
                                {{ campo.label || campo.name }}
                                <span v-if="campo.required" class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                            </label>

                            <!-- Input Text / Email / URL / Tel / Password -->
                            <input
                                v-if="['text', 'email', 'url', 'tel', 'password'].includes(campo.type)"
                                :id="`field-${campo.name}`"
                                v-model="valores[campo.name]"
                                :type="campo.type"
                                :placeholder="campo.placeholder || ''"
                                :required="campo.required"
                                :aria-required="campo.required ? 'true' : 'false'"
                                :aria-label="campo.label || campo.name"
                                :maxlength="campo.max_length || undefined"
                                :autocomplete="campo.type === 'email' ? 'email' : campo.type === 'tel' ? 'tel' : 'off'"
                                @blur="validarCampo(campo)"
                                class="form-input w-full px-3.5 py-2.5 text-base sm:text-sm rounded-lg border transition-all duration-200 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 outline-none"
                                :class="errores[campo.name]
                                    ? 'border-red-400 dark:border-red-500 focus:ring-2 focus:ring-red-500/20'
                                    : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20'"
                            />

                            <!-- Textarea -->
                            <textarea
                                v-else-if="campo.type === 'textarea'"
                                :id="`field-${campo.name}`"
                                v-model="valores[campo.name]"
                                :placeholder="campo.placeholder || ''"
                                :required="campo.required"
                                :aria-required="campo.required ? 'true' : 'false'"
                                :aria-label="campo.label || campo.name"
                                :maxlength="campo.max_length || undefined"
                                rows="4"
                                @blur="validarCampo(campo)"
                                class="form-input w-full px-3.5 py-2.5 text-base sm:text-sm rounded-lg border transition-all duration-200 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 outline-none resize-y min-h-[80px]"
                                :class="errores[campo.name]
                                    ? 'border-red-400 dark:border-red-500 focus:ring-2 focus:ring-red-500/20'
                                    : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20'"
                            />

                            <!-- Number -->
                            <input
                                v-else-if="campo.type === 'number'"
                                :id="`field-${campo.name}`"
                                v-model.number="valores[campo.name]"
                                type="number"
                                :placeholder="campo.placeholder || ''"
                                :min="campo.min"
                                :max="campo.max"
                                :required="campo.required"
                                :aria-required="campo.required ? 'true' : 'false'"
                                :aria-label="campo.label || campo.name"
                                @blur="validarCampo(campo)"
                                class="form-input w-full px-3.5 py-2.5 text-base sm:text-sm rounded-lg border transition-all duration-200 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white outline-none"
                                :class="errores[campo.name]
                                    ? 'border-red-400 dark:border-red-500 focus:ring-2 focus:ring-red-500/20'
                                    : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20'"
                            />

                            <!-- Select -->
                            <select
                                v-else-if="campo.type === 'select'"
                                :id="`field-${campo.name}`"
                                v-model="valores[campo.name]"
                                :required="campo.required"
                                :aria-required="campo.required ? 'true' : 'false'"
                                :aria-label="campo.label || campo.name"
                                @change="validarCampo(campo)"
                                class="form-input w-full px-3.5 py-2.5 text-base sm:text-sm rounded-lg border transition-all duration-200 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white outline-none appearance-none cursor-pointer"
                                :class="errores[campo.name]
                                    ? 'border-red-400 dark:border-red-500'
                                    : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20'"
                            >
                                <option value="" disabled>{{ campo.placeholder || 'Seleccionar…' }}</option>
                                <option v-for="opt in opcionesCampo(campo)" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>

                            <!-- Radio Group -->
                            <div v-else-if="campo.type === 'radio'" class="flex flex-wrap gap-3 mt-1" role="radiogroup" :aria-label="campo.label || campo.name">
                                <label
                                    v-for="opt in opcionesCampo(campo)"
                                    :key="opt.value"
                                    class="flex items-center gap-2 cursor-pointer text-sm text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                                >
                                    <input
                                        type="radio"
                                        :name="`field-${campo.name}`"
                                        :value="opt.value"
                                        v-model="valores[campo.name]"
                                        @change="validarCampo(campo)"
                                        class="form-radio accent-emerald-600"
                                    />
                                    {{ opt.label }}
                                </label>
                            </div>

                            <!-- Checkbox -->
                            <div
                                v-else-if="campo.type === 'checkbox'"
                                class="flex items-start sm:items-center gap-2.5 mt-1"
                            >
                                <label
                                    :for="`field-${campo.name}`"
                                    class="relative inline-flex items-center justify-center w-5 h-5 rounded border-2 transition-all duration-200 cursor-pointer shrink-0 mt-0.5 sm:mt-0"
                                    :class="valores[campo.name]
                                        ? 'bg-emerald-600 border-emerald-600'
                                        : 'border-zinc-300 dark:border-zinc-600 hover:border-emerald-500'"
                                >
                                    <!-- Texto accesible para lectores de pantalla y agentes de IA -->
                                    <span class="sr-only">{{ obtenerTextoLabel(campo) || campo.label || campo.name }}</span>

                                    <svg v-if="valores[campo.name]" class="w-3 h-3 text-white" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M2 6l3 3 5-5" />
                                    </svg>
                                    <input
                                        type="checkbox"
                                        :id="`field-${campo.name}`"
                                        v-model="valores[campo.name]"
                                        @change="validarCampo(campo)"
                                        :aria-label="obtenerTextoLabel(campo) || campo.label || campo.name"
                                        :aria-required="campo.required ? 'true' : 'false'"
                                        class="sr-only"
                                    />
                                </label>
                                <div class="text-sm text-zinc-700 dark:text-zinc-300 select-none">
                                    <template v-if="tieneEnlace(campo)">
                                        <button
                                            v-if="esEnlaceModal(campo)"
                                            type="button"
                                            @click.stop="abrirModalLegal(obtenerUrlEnlace(campo), obtenerTextoLabel(campo))"
                                            class="text-left text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors cursor-pointer"
                                        >
                                            {{ obtenerTextoLabel(campo) }}
                                        </button>
                                        <a
                                            v-else
                                            :href="obtenerUrlEnlace(campo)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            @click.stop
                                            class="text-left text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors cursor-pointer"
                                        >
                                            {{ obtenerTextoLabel(campo) }}
                                        </a>
                                        <span v-if="campo.required" class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                                    </template>
                                    <label v-else :for="`field-${campo.name}`" class="cursor-pointer">
                                        {{ campo.label || '' }}
                                        <span v-if="campo.required" class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Toggle (interruptor on/off) -->
                            <div
                                v-else-if="campo.type === 'toggle'"
                                class="flex items-start sm:items-center gap-3 mt-1"
                            >
                                <label
                                    :for="`field-${campo.name}`"
                                    class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors duration-200 ease-in-out cursor-pointer mt-0.5 sm:mt-0"
                                    :class="valores[campo.name]
                                        ? 'bg-emerald-600'
                                        : 'bg-zinc-300 dark:bg-zinc-600 hover:bg-zinc-400 dark:hover:bg-zinc-500'"
                                >
                                    <!-- Texto accesible para lectores de pantalla y agentes de IA -->
                                    <span class="sr-only">{{ obtenerTextoLabel(campo) || campo.label || campo.name }}</span>

                                    <span
                                        aria-hidden="true"
                                        class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transform transition-transform duration-200 ease-in-out mt-0.5"
                                        :class="valores[campo.name] ? 'translate-x-5 ml-0.5' : 'translate-x-0 ml-0.5'"
                                    />
                                    <input
                                        type="checkbox"
                                        role="switch"
                                        :id="`field-${campo.name}`"
                                        v-model="valores[campo.name]"
                                        @change="validarCampo(campo)"
                                        :aria-label="obtenerTextoLabel(campo) || campo.label || campo.name"
                                        :aria-checked="valores[campo.name] ? 'true' : 'false'"
                                        :aria-required="campo.required ? 'true' : 'false'"
                                        class="sr-only"
                                    />
                                </label>
                                <div class="text-sm text-zinc-700 dark:text-zinc-300 select-none">
                                    <template v-if="tieneEnlace(campo)">
                                        <button
                                            v-if="esEnlaceModal(campo)"
                                            type="button"
                                            @click.stop="abrirModalLegal(obtenerUrlEnlace(campo), obtenerTextoLabel(campo))"
                                            class="text-left text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors cursor-pointer"
                                        >
                                            {{ obtenerTextoLabel(campo) }}
                                        </button>
                                        <a
                                            v-else
                                            :href="obtenerUrlEnlace(campo)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            @click.stop
                                            class="text-left text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors cursor-pointer"
                                        >
                                            {{ obtenerTextoLabel(campo) }}
                                        </a>
                                        <span v-if="campo.required" class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                                    </template>
                                    <label v-else :for="`field-${campo.name}`" class="cursor-pointer">
                                        {{ campo.label || '' }}
                                        <span v-if="campo.required" class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Date / DateTime / Time -->
                            <input
                                v-else-if="['date', 'datetime', 'time'].includes(campo.type)"
                                :id="`field-${campo.name}`"
                                v-model="valores[campo.name]"
                                :type="campo.type === 'datetime' ? 'datetime-local' : campo.type"
                                :required="campo.required"
                                :aria-required="campo.required ? 'true' : 'false'"
                                :aria-label="campo.label || campo.name"
                                :min="campo.min_date"
                                :max="campo.max_date"
                                @blur="validarCampo(campo)"
                                class="form-input w-full px-3.5 py-2.5 text-base sm:text-sm rounded-lg border transition-all duration-200 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white outline-none"
                                :class="errores[campo.name]
                                    ? 'border-red-400 dark:border-red-500'
                                    : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20'"
                            />

                            <!-- Color -->
                            <div v-else-if="campo.type === 'color'" class="flex items-center gap-3 mt-1">
                                <input
                                    :id="`field-${campo.name}`"
                                    v-model="valores[campo.name]"
                                    type="color"
                                    :aria-label="campo.label || campo.name"
                                    class="w-10 h-10 rounded-lg border border-zinc-300 dark:border-zinc-700 cursor-pointer"
                                />
                                <span class="text-xs font-mono text-zinc-500">{{ valores[campo.name] || '#000000' }}</span>
                            </div>

                            <!-- Hidden -->
                            <input
                                v-else-if="campo.type === 'hidden'"
                                type="hidden"
                                v-model="valores[campo.name]"
                            />

                            <!-- Slider -->
                            <div v-else-if="campo.type === 'slider'" class="mt-1">
                                <input
                                    :id="`field-${campo.name}`"
                                    v-model.number="valores[campo.name]"
                                    type="range"
                                    :min="campo.min || 0"
                                    :max="campo.max || 100"
                                    :aria-label="campo.label || campo.name"
                                    :aria-valuemin="campo.min || 0"
                                    :aria-valuemax="campo.max || 100"
                                    :aria-valuenow="valores[campo.name] || 0"
                                    class="w-full accent-emerald-600"
                                />
                                <div class="flex justify-between text-xs text-zinc-400 mt-1" aria-hidden="true">
                                    <span>{{ campo.min || 0 }}</span>
                                    <span class="font-semibold text-emerald-600">{{ valores[campo.name] || 0 }}</span>
                                    <span>{{ campo.max || 100 }}</span>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div v-else-if="campo.type === 'rating'" class="flex items-center gap-1 mt-1" role="group" :aria-label="campo.label || campo.name">
                                <button
                                    v-for="n in (campo.max_rating || 5)"
                                    :key="n"
                                    type="button"
                                    @click="valores[campo.name] = n"
                                    :aria-label="`Valorar ${n} de ${campo.max_rating || 5} estrellas`"
                                    class="text-2xl transition-all duration-150 hover:scale-125 focus:outline-none"
                                    :class="n <= (valores[campo.name] || 0) ? 'text-amber-400' : 'text-zinc-300 dark:text-zinc-600'"
                                >
                                    ★
                                </button>
                            </div>

                            <!-- Mensaje de error del campo -->
                            <Transition name="form-fade">
                                <p
                                    v-if="errores[campo.name]"
                                    class="form-field-error text-xs text-red-500 dark:text-red-400 mt-1.5 flex items-center gap-1"
                                >
                                    <i class="pi pi-exclamation-circle text-[10px]" />
                                    {{ errores[campo.name] }}
                                </p>
                            </Transition>
                        </div>
                    </template>
                </template>
            </div>

            <!-- Honeypot invisible (protección anti-bot) -->
            <div
                v-if="formData?.honeypot_field"
                :aria-hidden="true"
                :style="{ position: 'absolute', left: '-9999px', top: '-9999px', opacity: 0, height: 0, width: 0, overflow: 'hidden', tabIndex: -1 }"
            >
                <label :for="`hp-${formData.honeypot_field}`">No rellenar este campo</label>
                <input
                    :id="`hp-${formData.honeypot_field}`"
                    :name="formData.honeypot_field"
                    v-model="honeypotValue"
                    type="text"
                    autocomplete="off"
                    tabindex="-1"
                />
            </div>



            <!-- Cloudflare Turnstile Widget (Invisible en dashboard) -->
            <div
                v-if="formData?.requires_captcha"
                ref="turnstileContainer"
                class="cf-turnstile-wrapper my-2 flex justify-center"
            ></div>

            <!-- Botón de envío -->
            <div class="pt-4" :class="['flex', botonAlineacion]">
                <button
                    type="submit"
                    :disabled="estado === 'submitting'"
                    :class="[
                        'form-submit-btn relative inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-zinc-900',
                        botonColor,
                        estado === 'submitting' ? 'opacity-80 cursor-not-allowed' : 'hover:shadow-lg active:scale-[0.98]',
                        settings.submit_button_align === 'full' ? 'w-full' : '',
                    ]"
                >
                    <!-- Spinner de envío -->
                    <svg v-if="estado === 'submitting'" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-linecap="round" class="opacity-30" />
                        <path d="M12 2a10 10 0 019.95 9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    <span>{{ estado === 'submitting' ? 'Enviando…' : (settings.submit_button_text || 'Enviar') }}</span>
                </button>
            </div>
        </form>

        <!-- Toast flotante premium de éxito (Glassmorphism teletransportado a body) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-4 scale-95"
            >
                <div
                    v-if="toastVisible"
                    class="fixed top-5 left-4 right-4 sm:left-auto sm:right-6 sm:w-[420px] z-[999999] pointer-events-auto"
                    role="alert"
                >
                    <div class="relative overflow-hidden rounded-2xl bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl border border-emerald-500/30 dark:border-emerald-500/40 shadow-2xl shadow-emerald-950/15 p-4 sm:p-5 flex items-start gap-3.5 ring-1 ring-black/5 dark:ring-white/10">
                        <!-- Icono animado de éxito -->
                        <div class="shrink-0 relative flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-600/30">
                            <span class="absolute inline-flex h-full w-full rounded-xl bg-emerald-400 opacity-30 animate-ping" />
                            <svg class="w-6 h-6 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <!-- Textos -->
                        <div class="flex-1 min-w-0 pr-2 pt-0.5">
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 tracking-tight flex items-center gap-1.5">
                                ¡Mensaje Recibido!
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500" />
                            </h4>
                            <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-1 leading-relaxed break-words">
                                {{ toastMensaje }}
                            </p>
                        </div>

                        <!-- Botón cerrar -->
                        <button
                            type="button"
                            @click="cerrarToast"
                            aria-label="Cerrar notificación"
                            class="shrink-0 -mt-1 -mr-1 p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Barra de progreso temporizada inferior -->
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-zinc-100 dark:bg-zinc-800">
                            <div
                                class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-75 ease-linear rounded-full"
                                :style="{ width: `${toastProgress}%` }"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal Flotante para Información Legal / Términos -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="modalLegalVisible"
                    class="fixed inset-0 z-[999999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-black/60 backdrop-blur-sm"
                    @click.self="cerrarModalLegal"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="relative w-full max-w-2xl bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 flex flex-col max-h-[85vh] overflow-hidden transform transition-all"
                    >
                        <!-- Cabecera del modal -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 shrink-0">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-semibold">
                                    <i class="pi pi-shield" />
                                </div>
                                <h3 class="text-base font-bold text-zinc-900 dark:text-white truncate">
                                    {{ modalLegalTitulo }}
                                </h3>
                            </div>
                            <button
                                type="button"
                                @click="cerrarModalLegal"
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                aria-label="Cerrar modal"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Cuerpo con scroll -->
                        <div class="px-6 py-5 overflow-y-auto flex-1 text-zinc-700 dark:text-zinc-300 text-sm leading-relaxed">
                            <!-- Estado cargando -->
                            <div v-if="modalLegalCargando" class="flex flex-col items-center justify-center py-12 gap-3">
                                <div class="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin" />
                                <span class="text-xs text-zinc-400">Cargando documento legal...</span>
                            </div>

                            <!-- Estado de error -->
                            <div v-else-if="modalLegalError" class="text-center py-10 space-y-3">
                                <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-500 flex items-center justify-center mx-auto text-lg">
                                    <i class="pi pi-exclamation-circle" />
                                </div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ modalLegalError }}</p>
                                <a
                                    v-if="modalLegalUrl"
                                    :href="modalLegalUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium"
                                >
                                    Abrir en una pestaña nueva
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>

                            <!-- Contenido (HTML sanitizado o PageBuilder) -->
                            <div v-else>
                                <div
                                    v-if="modalLegalContenido"
                                    v-html="sanitize(modalLegalContenido)"
                                    class="prose dark:prose-invert max-w-none text-sm space-y-4"
                                />
                                <div v-else-if="modalLegalLayout?.sections?.length">
                                    <PageBuilderRenderer :layout="modalLegalLayout" />
                                </div>
                                <div v-else class="text-center py-8 text-zinc-400 italic">
                                    No hay contenido disponible para este documento.
                                </div>
                            </div>
                        </div>

                        <!-- Pie del modal -->
                        <div class="flex items-center justify-end gap-3 px-6 py-3.5 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 shrink-0">
                            <button
                                type="button"
                                @click="cerrarModalLegal"
                                class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-sm shadow-emerald-600/20 cursor-pointer"
                            >
                                Entendido
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
/* Transición de aparición de errores y alertas */
.form-fade-enter-active,
.form-fade-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.form-fade-enter-from,
.form-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/* Animación del check de éxito */
.form-check-anim {
    stroke-dasharray: 24;
    stroke-dashoffset: 24;
    animation: formCheckDraw 0.5s ease 0.2s forwards;
}
@keyframes formCheckDraw {
    to { stroke-dashoffset: 0; }
}

/* Animación de aparición del icono de éxito */
.form-success-icon {
    animation: formSuccessScale 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
@keyframes formSuccessScale {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

/* Focus ring premium en inputs */
.form-input:focus {
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

/* Shake animation para el botón en error */
.form-submit-btn.shake {
    animation: formShake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}
@keyframes formShake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-3px); }
    40%, 60% { transform: translateX(3px); }
}
</style>
