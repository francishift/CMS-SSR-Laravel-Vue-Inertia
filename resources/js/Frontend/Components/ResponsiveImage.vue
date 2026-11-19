<script setup>
/**
 * Componente de Imagen Responsiva con Shimmer Skeleton y Fade-In Elegante.
 *
 * Características:
 *  - Shimmer Skeleton: Animación de carga tipo Instagram/Facebook mientras la imagen se descarga.
 *  - Fade-In Suave: Transición de opacidad al completar la carga para una aparición elegante.
 *  - Detección Instantánea de Caché (onMounted img.complete): Las imágenes cacheadas en
 *    memoria del navegador aparecen al instante sin shimmer ni retardos.
 *  - Genera automáticamente `srcset` y `sizes` para cargas ligeras en móviles.
 *  - Detección de errores (@error): Muestra aviso discreto si la imagen no se pudo procesar.
 */
import { ref, computed, onMounted, watch } from 'vue';
import { imageUrl, imageSrcset } from '@/Composables/useImage';

const props = defineProps({
    src: { type: String, required: true },
    alt: { type: String, default: '' },
    width: { type: [Number, String], default: null },
    height: { type: [Number, String], default: null },
    fit: { type: String, default: 'scale' },
    quality: { type: Number, default: 90 },
    format: { type: String, default: 'webp' },
    widths: { type: Array, default: () => [360, 640, 960, 1280, 1600] },
    sizes: { type: String, default: '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw' },
    loading: { type: String, default: 'lazy' },
    decoding: { type: String, default: 'async' },
    fetchpriority: { type: String, default: null },
    imgClass: { type: [String, Array], default: '' },
    containerClass: { type: String, default: '' },
    shimmer: { type: Boolean, default: true },
});

const imgRef = ref(null);
const cargada = ref(false);
const errorCarga = ref(false);

const esSvg = computed(() => {
    const rutaLimpia = (props.src || '').split('?')[0].split('#')[0].toLowerCase();
    return rutaLimpia.endsWith('.svg');
});

watch(
    () => props.src,
    () => {
        errorCarga.value = false;
    }
);

function onLoad() {
    cargada.value = true;
    errorCarga.value = false;
}

function onError() {
    errorCarga.value = true;
    cargada.value = true;
}

onMounted(() => {
    const el = imgRef.value;
    if (!el || !el.complete) return;

    // Si la imagen ya está en la caché del navegador o completó antes de hidratar SSR, mostrar al instante
    if (el.naturalWidth > 0) {
        cargada.value = true;
        return;
    }

    // Algunos motores reportan naturalWidth === 0 en SVGs válidos sin atributos width/height explícitos
    if (esSvg.value) {
        if (typeof el.decode === 'function') {
            el.decode()
                .then(() => {
                    cargada.value = true;
                    errorCarga.value = false;
                })
                .catch(() => {
                    errorCarga.value = true;
                    cargada.value = true;
                });
        } else {
            cargada.value = true;
        }
    }
});

const defaultUrl = computed(() => {
    return imageUrl(props.src, {
        w: props.width,
        h: props.height,
        fit: props.fit,
        q: props.quality,
        f: props.format,
    });
});

const calculatedSrcset = computed(() => {
    return imageSrcset(props.src, props.widths, {
        w: props.width,
        h: props.height,
        fit: props.fit,
        q: props.quality,
        f: props.format,
    });
});

const aspectRatio = computed(() => {
    if (props.fit === 'cover' && props.width && props.height) {
        return `${props.width} / ${props.height}`;
    }
    return 'auto';
});
</script>

<template>
    <div :class="['relative overflow-hidden', containerClass]" :style="{ aspectRatio: aspectRatio }">
        <!-- Shimmer skeleton acelerado por GPU mientras la imagen carga -->
        <div
            v-if="shimmer && !cargada && !esSvg && !errorCarga"
            class="ri-shimmer absolute inset-0 z-[1] overflow-hidden"
            aria-hidden="true"
        />

        <!-- Estado de error si la imagen no se pudo procesar o cargar -->
        <div
            v-if="errorCarga"
            class="w-full h-full min-h-[140px] flex flex-col items-center justify-center p-4 text-zinc-400 text-xs text-center rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 space-y-1"
        >
            <i class="pi pi-image text-lg text-zinc-400 dark:text-zinc-500" />
            <span>Imagen no disponible</span>
        </div>

        <!-- Imagen principal con fade-in elegante -->
        <img
            v-else
            ref="imgRef"
            :src="defaultUrl"
            :srcset="calculatedSrcset || undefined"
            :sizes="calculatedSrcset ? sizes : undefined"
            :alt="alt"
            :width="width || undefined"
            :height="height || undefined"
            :loading="loading"
            :decoding="decoding"
            :fetchpriority="fetchpriority || undefined"
            @load="onLoad"
            @error="onError"
            :class="[imgClass, (cargada || esSvg) ? 'ri-visible' : 'ri-loading']"
            :style="{ aspectRatio: aspectRatio }"
        />
    </div>
</template>

<style scoped>
/* Shimmer skeleton — compuesto 100% en GPU mediante transform: translate3d */
.ri-shimmer {
    background-color: rgb(228 228 231 / 0.55);
    border-radius: inherit;
}

.ri-shimmer::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgb(255 255 255 / 0.55) 50%,
        transparent 100%
    );
    transform: translate3d(-100%, 0, 0);
    will-change: transform;
    animation: ri-shimmer-gpu 1.4s ease-in-out infinite;
}

/* Variante dark mode */
:root.dark .ri-shimmer,
.dark .ri-shimmer {
    background-color: rgb(39 39 42 / 0.7);
}

:root.dark .ri-shimmer::after,
.dark .ri-shimmer::after {
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgb(82 82 91 / 0.45) 50%,
        transparent 100%
    );
}

@keyframes ri-shimmer-gpu {
    0%   { transform: translate3d(-100%, 0, 0); }
    100% { transform: translate3d(100%, 0, 0); }
}

/* Estado inicial de la imagen: invisible */
.ri-loading {
    opacity: 0;
}

/* Fade-in suave al completar la carga + soporte para hover transform */
.ri-visible {
    opacity: 1;
    transition: opacity 0.35s ease-out, transform 0.8s cubic-bezier(0.25, 0.1, 0.25, 1), scale 0.8s cubic-bezier(0.25, 0.1, 0.25, 1);
}
</style>
