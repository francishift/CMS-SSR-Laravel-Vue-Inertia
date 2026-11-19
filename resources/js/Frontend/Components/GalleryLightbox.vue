<script setup>
/**
 * Lightbox Moderno para la Galería del Page Builder.
 *
 * Características:
 *  - Teleport a <body> para evitar problemas de z-index.
 *  - Navegación: flechas izquierda/derecha, teclas ←→, swipe táctil móvil.
 *  - Cierre: tecla ESC, clic en overlay oscuro, botón X, clic fuera de la imagen.
 *  - Imagen optimizada con carga progresiva, spinner de espera y fallback de error.
 *  - Soporte de vídeo con pausa automática al navegar o cerrar.
 *  - Counter: "3 / 12" indicador de posición.
 *  - Caption: muestra título y descripción si existen sin bloquear clics de cierre.
 *  - Preload circular: precarga la imagen siguiente y anterior incluso en los extremos.
 *  - Animaciones suaves de fundido y escala.
 *  - Accesibilidad: role="dialog", aria-modal, captura de foco al abrir.
 */
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { imageUrl } from '@/Composables/useImage';

const props = defineProps({
    items: { type: Array, required: true },
    startIndex: { type: Number, default: 0 },
    galleryContent: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close']);

const totalItems = computed(() => (Array.isArray(props.items) ? props.items.length : 0));

function clampIndex(idx) {
    const max = Math.max(0, totalItems.value - 1);
    return Math.max(0, Math.min(Number(idx) || 0, max));
}

const currentIndex = ref(clampIndex(props.startIndex));
const isVisible = ref(false);
const isTransitioning = ref(false);
const imageLoading = ref(true);
const imageLoadError = ref(false);
const dialogRef = ref(null);
const videoPlayerRef = ref(null);
let isClosing = false;

watch(() => props.startIndex, (newVal) => {
    currentIndex.value = clampIndex(newVal);
});

// --- Elemento actual ---
const currentItem = computed(() => {
    if (!props.items || !props.items.length) return {};
    return props.items[currentIndex.value] || {};
});

// --- Pausa de vídeos activos ---
function pauseActiveVideo() {
    if (videoPlayerRef.value) {
        try {
            videoPlayerRef.value.pause();
        } catch (_) {}
    }
}

// --- Navegación circular ---
function goNext() {
    if (isTransitioning.value || totalItems.value <= 1) return;
    pauseActiveVideo();
    isTransitioning.value = true;
    setTimeout(() => {
        currentIndex.value = (currentIndex.value + 1) % totalItems.value;
        imageLoading.value = true;
        imageLoadError.value = false;
        isTransitioning.value = false;
    }, 150);
}

function goPrev() {
    if (isTransitioning.value || totalItems.value <= 1) return;
    pauseActiveVideo();
    isTransitioning.value = true;
    setTimeout(() => {
        currentIndex.value = (currentIndex.value - 1 + totalItems.value) % totalItems.value;
        imageLoading.value = true;
        imageLoadError.value = false;
        isTransitioning.value = false;
    }, 150);
}

function close() {
    if (isClosing) return;
    isClosing = true;
    pauseActiveVideo();
    isVisible.value = false;
    setTimeout(() => emit('close'), 250);
}

// --- Detección de tipo ---
function isVideo(item) {
    if (!item) return false;
    if (item.type === 'video') return true;
    if (!item.src) return false;
    const ext = item.src.split('?')[0].split('#')[0].split('.').pop()?.toLowerCase();
    return ['mp4', 'webm', 'mov'].includes(ext);
}

function isSvg(item) {
    if (!item) return false;
    if (item.mime === 'image/svg+xml') return true;
    if (!item.src || typeof item.src !== 'string') return false;
    const clean = item.src.split('?')[0].split('#')[0].toLowerCase();
    return clean.endsWith('.svg');
}

// --- Filtro de color para logos e imágenes (heredado del ítem y de la galería) ---
function getItemFilterClass(item) {
    if (!item) return '';

    // 1. Ajuste individual del ítem si está configurado explícitamente
    if (item.is_white === true) return 'brightness-0 invert';
    if (item.color_filter && item.color_filter !== 'inherit') {
        switch (item.color_filter) {
            case 'white': return 'brightness-0 invert';
            case 'dark_white': return 'brightness-0 invert';
            case 'black': return 'brightness-0';
            case 'grayscale': return 'grayscale';
            case 'grayscale_hover': return 'grayscale hover:grayscale-0 transition-all duration-300';
            case 'none': return '';
        }
    }

    // 2. Heredar de la configuración global de la galería
    const gc = props.galleryContent || {};
    if (gc.is_white || gc.force_white) return 'brightness-0 invert';
    const globalFilter = gc.color_filter;
    if (globalFilter === 'white') return 'brightness-0 invert';
    if (globalFilter === 'dark_white') return 'brightness-0 invert';
    if (globalFilter === 'black') return 'brightness-0';
    if (globalFilter === 'grayscale') return 'grayscale';
    if (globalFilter === 'grayscale_hover') return 'grayscale hover:grayscale-0 transition-all duration-300';

    return '';
}

// --- URL de imagen optimizada para lightbox (alta calidad) ---
function getLightboxImageUrl(item) {
    if (!item?.src) return '';
    let src = item.src;
    if (!src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('/') && !src.startsWith('data:')) {
        if (src.startsWith('storage/') || src.startsWith('media/')) {
            src = '/' + src;
        }
    }
    // Si es SVG, devolver la ruta directa para vector nativo nítido sin rasterizar
    if (isSvg(item)) {
        return src;
    }
    return imageUrl(src, { w: 1600, q: 90, f: 'webp', fit: 'scale' });
}

// --- URL de vídeo normalizada ---
function getVideoSrc(item) {
    let src = item?.src || '';
    if (!src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('/') && !src.startsWith('data:')) {
        if (src.startsWith('storage/') || src.startsWith('media/')) {
            src = '/' + src;
        }
    }
    return src;
}

// --- Manejadores de carga y error de imagen ---
function onImageLoad() {
    imageLoading.value = false;
    imageLoadError.value = false;
}

function onImageError(e) {
    imageLoading.value = false;
    if (currentItem.value?.src && e.target.src !== currentItem.value.src) {
        // Si falló el render dinámico (/media/render), reintentar con el src original directamente
        e.target.src = currentItem.value.src;
    } else {
        imageLoadError.value = true;
    }
}

// --- Preload circular de imágenes adyacentes ---
function preloadAdjacent(idx) {
    if (totalItems.value <= 1) return;
    const nextIdx = (idx + 1) % totalItems.value;
    const nextItem = props.items[nextIdx];
    if (nextItem && !isVideo(nextItem)) {
        const img = new Image();
        img.src = getLightboxImageUrl(nextItem);
    }
    const prevIdx = (idx - 1 + totalItems.value) % totalItems.value;
    const prevItem = props.items[prevIdx];
    if (prevItem && !isVideo(prevItem)) {
        const img = new Image();
        img.src = getLightboxImageUrl(prevItem);
    }
}

watch(currentIndex, (idx) => {
    preloadAdjacent(idx);
});

// --- Teclado con preventDefault ---
function onKeydown(e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        close();
    } else if (e.key === 'ArrowRight' || e.key === 'Right') {
        e.preventDefault();
        goNext();
    } else if (e.key === 'ArrowLeft' || e.key === 'Left') {
        e.preventDefault();
        goPrev();
    }
}

// --- Swipe táctil ---
let touchStartX = 0;
let touchStartY = 0;

function onTouchStart(e) {
    const touch = e.touches?.[0];
    if (touch) {
        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
    }
}

function onTouchEnd(e) {
    const touch = e.changedTouches?.[0];
    if (!touch) return;

    const diffX = touch.clientX - touchStartX;
    const diffY = touch.clientY - touchStartY;
    const absDiffX = Math.abs(diffX);
    const absDiffY = Math.abs(diffY);

    if (absDiffX > 50 && absDiffX > absDiffY) {
        if (diffX < 0) {
            goNext();
        } else {
            goPrev();
        }
    }
}

// --- Bloquear scroll del body y ciclo de vida ---
let originalOverflow = '';

onMounted(() => {
    nextTick(() => {
        isVisible.value = true;
        dialogRef.value?.focus();
    });
    document.addEventListener('keydown', onKeydown);
    originalOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    preloadAdjacent(currentIndex.value);
});

onUnmounted(() => {
    pauseActiveVideo();
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = originalOverflow;
});
</script>

<template>
    <Teleport to="body">
        <div
            ref="dialogRef"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-label="Visor de galería"
            :class="[
                'fixed inset-0 z-[9999] flex items-center justify-center transition-opacity duration-300 outline-none select-none',
                isVisible ? 'opacity-100' : 'opacity-0 pointer-events-none'
            ]"
            @touchstart.passive="onTouchStart"
            @touchend.passive="onTouchEnd"
            @click.self="close"
        >
            <!-- Backdrop oscuro con desenfoque -->
            <div
                class="absolute inset-0 bg-black/90 backdrop-blur-md cursor-pointer"
                @click="close"
            />

            <!-- Barra superior: counter + botón cerrar -->
            <div class="absolute top-0 left-0 right-0 z-50 flex items-center justify-between px-4 sm:px-6 py-3 bg-gradient-to-b from-black/70 via-black/30 to-transparent pointer-events-auto">
                <!-- Counter -->
                <span class="text-white/80 text-sm font-medium tracking-wide">
                    {{ totalItems > 0 ? (currentIndex + 1) : 0 }} / {{ totalItems }}
                </span>

                <!-- Botón Cerrar -->
                <button
                    type="button"
                    @click="close"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white transition-all duration-200 cursor-pointer group"
                    aria-label="Cerrar visor"
                >
                    <i class="pi pi-times text-lg group-hover:rotate-90 transition-transform duration-300" />
                </button>
            </div>

            <!-- Contenido principal: imagen / vídeo (clic en espacio vacío cierra) -->
            <div
                :class="[
                    'relative z-40 max-w-[90vw] max-h-[85vh] flex items-center justify-center',
                    isSvg(currentItem) ? 'w-full' : ''
                ]"
                @click.self="close"
            >
                <!-- Spinner indicador de carga -->
                <div
                    v-if="imageLoading && currentItem.src && !isVideo(currentItem)"
                    class="absolute inset-0 flex items-center justify-center pointer-events-none"
                >
                    <i class="pi pi-spin pi-spinner text-3xl text-white/60" />
                </div>

                <!-- Estado de error -->
                <div
                    v-if="imageLoadError && !isVideo(currentItem)"
                    class="p-6 rounded-xl bg-zinc-900/80 border border-zinc-700 text-center text-zinc-300 space-y-2"
                >
                    <i class="pi pi-image text-3xl text-zinc-500" />
                    <p class="text-sm">No se pudo cargar la imagen</p>
                </div>

                <!-- Imagen con transición de opacidad y escala (soporte especial SVG sin colapso a 0x0) -->
                <img
                    v-else-if="currentItem.src && !isVideo(currentItem)"
                    :key="`img_${currentItem.src}_${currentIndex}`"
                    :src="getLightboxImageUrl(currentItem)"
                    :alt="currentItem.alt || currentItem.title || 'Imagen de galería'"
                    :class="[
                        isSvg(currentItem)
                            ? 'w-[85vw] sm:w-[65vw] max-w-[700px] h-auto max-h-[70vh] min-w-[200px] min-h-[80px] p-4 sm:p-6'
                            : 'max-w-full max-h-[80vh] w-auto h-auto',
                        'object-contain rounded-lg select-none transition-all duration-200 ease-out',
                        getItemFilterClass(currentItem),
                        isTransitioning || imageLoading ? 'opacity-0 scale-95' : 'opacity-100 scale-100'
                    ]"
                    draggable="false"
                    @load="onImageLoad"
                    @error="onImageError"
                />

                <!-- Vídeo con controles nativos -->
                <video
                    v-else-if="currentItem.src && isVideo(currentItem)"
                    ref="videoPlayerRef"
                    :key="`vid_${currentItem.src}_${currentIndex}`"
                    :src="getVideoSrc(currentItem)"
                    :poster="currentItem.poster || undefined"
                    controls
                    autoplay
                    playsinline
                    :class="[
                        'max-w-full max-h-[80vh] rounded-lg shadow-2xl',
                        getItemFilterClass(currentItem)
                    ]"
                />
            </div>

            <!-- Navegación: Anterior -->
            <button
                v-if="totalItems > 1"
                type="button"
                @click.stop="goPrev"
                class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-50 w-12 h-12 sm:w-14 sm:h-14 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/25 backdrop-blur-sm text-white transition-all duration-200 cursor-pointer group"
                aria-label="Imagen anterior"
            >
                <i class="pi pi-chevron-left text-xl group-hover:-translate-x-0.5 transition-transform duration-200" />
            </button>

            <!-- Navegación: Siguiente -->
            <button
                v-if="totalItems > 1"
                type="button"
                @click.stop="goNext"
                class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-50 w-12 h-12 sm:w-14 sm:h-14 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/25 backdrop-blur-sm text-white transition-all duration-200 cursor-pointer group"
                aria-label="Imagen siguiente"
            >
                <i class="pi pi-chevron-right text-xl group-hover:translate-x-0.5 transition-transform duration-200" />
            </button>

            <!-- Caption inferior (pointer-events-none para que los clics cierren) -->
            <div
                v-if="currentItem.title || currentItem.caption"
                class="absolute bottom-0 left-0 right-0 z-50 text-center px-6 py-4 bg-gradient-to-t from-black/70 via-black/30 to-transparent pointer-events-none"
            >
                <p v-if="currentItem.title" class="text-white text-base sm:text-lg font-semibold leading-tight mb-0.5">
                    {{ currentItem.title }}
                </p>
                <p v-if="currentItem.caption" class="text-white/70 text-sm leading-snug max-w-2xl mx-auto">
                    {{ currentItem.caption }}
                </p>
            </div>
        </div>
    </Teleport>
</template>
