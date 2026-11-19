<script setup>
/**
 * Bloque lateral fijo (Sticky) de Descarga y Ficha Técnica Estructurada.
 *
 * Calcula dinámicamente el borde inferior del <header> público para mantenerse
 * siempre exactamente a 20px por debajo de la cabecera en cualquier portal o tema.
 * Compatible 100% con SSR y con el lienzo de previsualización del Page Builder.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    descarga: { type: Object, required: true },
});

const btnPrimarioStyle = {
    backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
    color: 'var(--btn-primary-text, #ffffff)',
};

const shaCopiado = ref(false);
let shaTimer = null;

// Resumen compacto de 1 línea para la huella criptográfica SHA-256
const shaResumen = computed(() => {
    const raw = String(props.descarga?.sha256_hash || '').trim();
    if (!raw) return '';
    if (raw.length <= 20) return raw;
    return `${raw.slice(0, 10)}…${raw.slice(-8)}`;
});

async function copiarSha256() {
    const raw = String(props.descarga?.sha256_hash || '').trim();
    if (!raw || typeof navigator === 'undefined' || !navigator.clipboard) return;
    try {
        await navigator.clipboard.writeText(raw);
        shaCopiado.value = true;
        if (shaTimer) clearTimeout(shaTimer);
        shaTimer = setTimeout(() => {
            shaCopiado.value = false;
        }, 2000);
    } catch {
        // Silencioso si el navegador restringe portapapeles
    }
}

const stickyTopPx = ref(122);
let headerResizeObserver = null;

function actualizarOffsetSticky() {
    if (typeof document === 'undefined') return;
    const headerEl = document.querySelector('header');
    if (!headerEl) return;
    const style = window.getComputedStyle(headerEl);
    if (style.position === 'sticky' || style.position === 'fixed') {
        const rect = headerEl.getBoundingClientRect();
        const topCss = parseFloat(style.top) || 0;
        const altura = rect.height || headerEl.offsetHeight || 82;
        stickyTopPx.value = Math.round(topCss + altura + 20);
    } else {
        stickyTopPx.value = 24;
    }
}

onMounted(() => {
    actualizarOffsetSticky();
    window.addEventListener('resize', actualizarOffsetSticky, { passive: true });
    if (typeof ResizeObserver !== 'undefined') {
        const headerEl = document.querySelector('header');
        if (headerEl) {
            headerResizeObserver = new ResizeObserver(actualizarOffsetSticky);
            headerResizeObserver.observe(headerEl);
        }
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', actualizarOffsetSticky);
    }
    if (headerResizeObserver) {
        headerResizeObserver.disconnect();
        headerResizeObserver = null;
    }
    if (shaTimer) {
        clearTimeout(shaTimer);
        shaTimer = null;
    }
});
</script>

<template>
    <aside
        class="dl-sticky-aside lg:col-span-4 lg:sticky space-y-6 transition-[top] duration-200"
        :style="{ '--dl-sticky-top': `${stickyTopPx}px` }"
    >
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-md space-y-5">
            <div class="flex items-baseline justify-between border-b border-zinc-100 dark:border-zinc-800 pb-4">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Licencia / Acceso</span>
                <span class="text-2xl font-extrabold text-zinc-900 dark:text-white">
                    {{ descarga.formatted_price || 'Gratis' }}
                </span>
            </div>

            <!-- Acción principal según tipo de acceso -->
            <div class="space-y-2.5">
                <a
                    v-if="descarga.has_file && !descarga.is_paid && descarga.download_request_url"
                    :href="descarga.download_request_url"
                    rel="nofollow"
                    class="w-full py-3.5 px-5 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-sm hover:opacity-90 transition-opacity no-underline"
                    :style="btnPrimarioStyle"
                >
                    <i class="pi pi-download text-sm" />
                    <span>Descargar Paquete ({{ descarga.formatted_file_size }})</span>
                </a>

                <div
                    v-else-if="descarga.is_paid"
                    class="w-full py-3.5 px-5 rounded-xl font-bold text-sm text-center bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700"
                >
                    Disponible próximamente bajo licencia comercial
                </div>

                <div
                    v-else
                    class="w-full py-3 px-4 rounded-xl text-xs font-medium text-center bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400"
                >
                    Paquete en preparación
                </div>

                <!-- Enlaces auxiliares: Demo y Documentación -->
                <div v-if="descarga.demo_url || descarga.documentation_url" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2 pt-1">
                    <a
                        v-if="descarga.demo_url"
                        :href="descarga.demo_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 flex items-center justify-center gap-2 transition-colors no-underline"
                    >
                        <i class="pi pi-desktop text-xs" />
                        <span>Ver Demo en Vivo</span>
                    </a>

                    <a
                        v-if="descarga.documentation_url"
                        :href="descarga.documentation_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 flex items-center justify-center gap-2 transition-colors no-underline"
                    >
                        <i class="pi pi-book text-xs" />
                        <span>Documentación</span>
                    </a>
                </div>
            </div>

            <!-- Ficha técnica estructurada -->
            <dl class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-2.5 text-xs">
                <div v-if="descarga.version" class="flex justify-between gap-2">
                    <dt class="text-zinc-500 dark:text-zinc-400">Versión</dt>
                    <dd class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ descarga.version }}</dd>
                </div>
                <div v-if="descarga.compatibility" class="flex justify-between gap-2 text-right">
                    <dt class="text-zinc-500 dark:text-zinc-400 text-left">Compatibilidad</dt>
                    <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ descarga.compatibility }}</dd>
                </div>
                <div v-if="descarga.license_type" class="flex justify-between gap-2">
                    <dt class="text-zinc-500 dark:text-zinc-400">Licencia</dt>
                    <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ descarga.license_type }}</dd>
                </div>
                <div v-if="descarga.updated_at_human" class="flex justify-between gap-2">
                    <dt class="text-zinc-500 dark:text-zinc-400">Última actualización</dt>
                    <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ descarga.updated_at_human }}</dd>
                </div>
                <div v-if="descarga.downloads_count !== null && descarga.downloads_count !== undefined" class="flex justify-between gap-2">
                    <dt class="text-zinc-500 dark:text-zinc-400">Descargas</dt>
                    <dd class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ descarga.downloads_count }}</dd>
                </div>
            </dl>

            <!-- Verificación de integridad SHA-256 compacta en 1 línea con copia rápida -->
            <div v-if="descarga.sha256_hash" class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-2 text-[11px]">
                <span class="inline-flex items-center gap-1.5 text-zinc-400 dark:text-zinc-500 font-medium shrink-0" title="Huella criptográfica SHA-256 del paquete instalable">
                    <i class="pi pi-shield text-[11px] text-emerald-500" />
                    <span>SHA-256</span>
                </span>
                <button
                    type="button"
                    @click="copiarSha256"
                    :title="`Copiar SHA-256 completo: ${descarga.sha256_hash}`"
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-zinc-50 dark:bg-zinc-800/80 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 dark:text-zinc-400 font-mono text-[10px] transition-colors cursor-pointer border border-zinc-200/70 dark:border-zinc-700/70"
                >
                    <span>{{ shaCopiado ? 'Copiado' : shaResumen }}</span>
                    <i :class="[shaCopiado ? 'pi pi-check text-emerald-500' : 'pi pi-copy', 'text-[10px]']" />
                </button>
            </div>
        </div>
    </aside>
</template>

<style scoped>
@media (min-width: 1024px) {
    .dl-sticky-aside {
        top: var(--dl-sticky-top, 122px);
    }
}
</style>
