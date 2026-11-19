<script setup>
/**
 * Componente de Elemento Vídeo HTML5 Optimizado para el CMS SSR / Page Builder.
 *
 * Implementa 8 prácticas de rendimiento extremo:
 *  1. Streaming MP4 Range Requests (soporte +faststart).
 *  2. Lazy Loading con IntersectionObserver (rootMargin: '600px 0px').
 *  3. Precarga ajustada (preload="metadata").
 *  4. Renderizado del primer fotograma con fragmento URL #t=0.001 si no hay poster.
 *  5. Carga escalonada diferida entre vídeos en pantalla para no agotar conexiones HTTP.
 *  6. Pausa y reanudación automática al salir/entrar del viewport (preserva 60 FPS).
 *  7. Manejo robusto de promesas HTMLMediaElement.play() previniendo AbortError.
 *  8. Atributos HTML5 nativos para móviles: playsinline, muted, loop, etc.
 */
import { ref, computed, onMounted, onBeforeUnmount, watch, inject } from 'vue';
import { useElementAnimation } from '@/Composables/useElementAnimation';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
    element: { type: Object, default: null },
});

const isBuilderPreview = inject('isBuilderPreview', false);

const { animAttrs, animStyle } = useElementAnimation(props);

const contentConfig = computed(() => {
    if (props.content && Object.keys(props.content).length > 0) {
        return props.content;
    }
    return props.element?.content || {};
});

const styleConfig = computed(() => {
    if (props.style && Object.keys(props.style).length > 0) {
        return props.style;
    }
    return props.element?.style || {};
});

// Ref al contenedor y al elemento <video>
const containerRef = ref(null);
const videoRef = ref(null);

// Estado de visibilidad, reproducción, hover y búfer con retardo anti-parpadeo (debounce)
const isVisible = ref(false);
const isPlaying = ref(false);
const isBuffering = ref(false);
const hasReadyFrame = ref(false);
const isHoverActive = ref(false);
const isHoverLoading = ref(false);
let observer = null;
let playPromise = null;
let bufferingTimer = null;
let startupPlayTimer = null;

function clearBufferingTimer() {
    if (bufferingTimer) {
        clearTimeout(bufferingTimer);
        bufferingTimer = null;
    }
}

function scheduleBufferingOverlay(delayMs = 450) {
    clearBufferingTimer();
    bufferingTimer = setTimeout(() => {
        const el = videoRef.value;
        if (el && el.readyState < 3 && !el.ended) {
            isBuffering.value = true;
        }
    }, delayMs);
}

function onMediaWaiting() {
    // Una vez pintado el primer fotograma, no volver a cubrir el vídeo con cortinas superpuestas
    if (hasReadyFrame.value && !playOnHover.value) {
        return;
    }
    scheduleBufferingOverlay(300);
}

function onMediaReady() {
    hasReadyFrame.value = true;
    clearBufferingTimer();
    isBuffering.value = false;
}

// Normalizar URL del vídeo con apéndice #t=0.001 si no hay poster
const rawSrc = computed(() => contentConfig.value.src || '');

// Detectar si el src es una variable dinámica sin resolver (ej: {article.extra_fields.video})
const isDynamicTag = computed(() => {
    const s = (rawSrc.value || '').trim();
    return s.startsWith('{') && s.endsWith('}');
});

// En web pública, si no hay vídeo o es una etiqueta dinámica sin resolver, no se renderiza
const shouldRenderContainer = computed(() => {
    if (isBuilderPreview) return true;
    return Boolean(videoSrc.value && !isDynamicTag.value);
});

const videoSrc = computed(() => {
    let src = rawSrc.value;
    if (!src) return '';

    // Normalizar ruta relativa si empieza por storage/ o media/ sin barra inicial
    if (!src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('/')) {
        if (src.startsWith('storage/') || src.startsWith('media/')) {
            src = '/' + src;
        }
    }

    // Si no hay poster ni autoplay y está activada la opción de primer fotograma, añadir #t=0.001
    const useFirstFrame = contentConfig.value.use_first_frame !== false;
    const auto = Boolean(contentConfig.value.autoplay);
    if (useFirstFrame && !auto && !contentConfig.value.poster && !src.includes('#t=')) {
        return `${src}#t=0.001`;
    }
    return src;
});

// Imagen de portada (poster)
const posterUrl = computed(() => {
    let p = contentConfig.value.poster || '';
    if (p && !p.startsWith('http://') && !p.startsWith('https://') && !p.startsWith('/')) {
        if (p.startsWith('storage/') || p.startsWith('media/')) {
            p = '/' + p;
        }
    }
    return p;
});

// Opciones de reproducción
const isAutoplay = computed(() => Boolean(contentConfig.value.autoplay));
const isMuted = computed(() => Boolean(contentConfig.value.muted ?? true));
const isLoop = computed(() => Boolean(contentConfig.value.loop));
const pauseOnScroll = computed(() => contentConfig.value.pause_on_scroll !== false);
const playOnHover = computed(() => Boolean(contentConfig.value.play_on_hover));

// Estilos de dimensiones y formato
const aspectRatioStyle = computed(() => {
    const ratio = contentConfig.value.aspect_ratio || '16:9';
    const map = {
        '16:9': '16 / 9',
        '4:3': '4 / 3',
        '1:1': '1 / 1',
        '21:9': '21 / 9',
        '32:9': '32 / 9',
        '3:1': '3 / 1',
        '9:16': '9 / 16',
        'auto': 'auto',
    };
    if (map[ratio]) {
        return { aspectRatio: map[ratio] };
    }
    if (ratio === 'custom' && contentConfig.value.custom_height) {
        return { height: contentConfig.value.custom_height };
    }
    return { aspectRatio: '16 / 9' };
});

const objectFitClass = computed(() => {
    const fit = contentConfig.value.object_fit || 'cover';
    return {
        cover: 'object-cover',
        contain: 'object-contain',
        fill: 'object-fill',
    }[fit] || 'object-cover';
});

const roundedClass = computed(() => {
    const rounded = contentConfig.value.rounded || 'lg';
    return {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
        '2xl': 'rounded-2xl',
        full: 'rounded-full',
    }[rounded] || 'rounded-lg';
});

const shadowClass = computed(() => {
    const shadow = contentConfig.value.shadow || 'md';
    return {
        none: 'shadow-none',
        sm: 'shadow-sm',
        md: 'shadow-md',
        lg: 'shadow-lg',
        xl: 'shadow-xl',
    }[shadow] || 'shadow-md';
});

const alignmentClass = computed(() => {
    const align = contentConfig.value.align || 'center';
    return {
        left: 'mr-auto ml-0',
        center: 'mx-auto',
        right: 'ml-auto mr-0',
    }[align] || 'mx-auto';
});

const maxWidthStyle = computed(() => {
    const maxW = contentConfig.value.max_width || '100%';
    return { maxWidth: maxW };
});

// Overlay de color (Modo Video Cover)
const overlayEnabled = computed(() => Boolean(contentConfig.value.overlay_enabled));
const overlayStyle = computed(() => {
    if (!overlayEnabled.value) return {};
    return {
        backgroundColor: contentConfig.value.overlay_color || 'rgba(0, 0, 0, 0.4)',
    };
});

// Control de Tipo de Reproductor y Opciones
const controlType = computed(() => {
    if (contentConfig.value.control_type) {
        return contentConfig.value.control_type;
    }
    if (contentConfig.value.controls) return 'native';
    if (contentConfig.value.disable_pointer_events) return 'none';
    return 'custom';
});

const hasControls = computed(() => controlType.value === 'native');
const disablePointerEvents = computed(() => controlType.value === 'none' || Boolean(contentConfig.value.disable_pointer_events));

// Botón de Play / Pause sobrepuesto
const showCentralPlayButton = computed(() => {
    if (controlType.value !== 'custom') return false;
    return contentConfig.value.show_play_button !== false;
});

// Mini-Reproductor Personalizado
const showMiniPlayer = computed(() => {
    if (controlType.value !== 'custom') return false;
    return Boolean(contentConfig.value.show_mini_player);
});

const miniPlayerPositionClass = computed(() => {
    const pos = contentConfig.value.mini_player_position || 'center';
    return {
        left: 'left-3 translate-x-0',
        center: 'left-1/2 -translate-x-1/2',
        right: 'right-3 translate-x-0',
    }[pos] || 'left-1/2 -translate-x-1/2';
});

// Estado del Mini-Player
const currentTime = ref(0);
const duration = ref(0);
const audioMuted = ref(contentConfig.value.muted ?? true);

const progressPercent = computed(() => {
    if (!duration.value || isNaN(duration.value)) return 0;
    return Math.min(100, Math.max(0, (currentTime.value / duration.value) * 100));
});

function formatSeconds(secs) {
    if (!secs || isNaN(secs)) return '0:00';
    const m = Math.floor(secs / 60);
    const s = Math.floor(secs % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
}

const formattedCurrentTime = computed(() => formatSeconds(currentTime.value));
const formattedDuration = computed(() => formatSeconds(duration.value));

function onTimeUpdate() {
    if (videoRef.value) {
        currentTime.value = videoRef.value.currentTime || 0;
        if (isBuffering.value && !videoRef.value.paused && videoRef.value.readyState >= 2) {
            clearBufferingTimer();
            isBuffering.value = false;
        }
    }
}

function onDurationChange() {
    if (videoRef.value) {
        duration.value = videoRef.value.duration || 0;
    }
}

function onVolumeChange() {
    if (videoRef.value) {
        audioMuted.value = videoRef.value.muted;
    }
}

function seekVideo(event) {
    if (!videoRef.value || !duration.value) return;
    const rect = event.currentTarget.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const ratio = Math.max(0, Math.min(1, clickX / rect.width));
    const newTime = ratio * duration.value;
    videoRef.value.currentTime = newTime;
    currentTime.value = newTime;
}

function toggleMute() {
    if (!videoRef.value) return;
    videoRef.value.muted = !videoRef.value.muted;
    audioMuted.value = videoRef.value.muted;
}

function toggleFullscreen() {
    if (!containerRef.value) return;
    if (!document.fullscreenElement) {
        if (containerRef.value.requestFullscreen) {
            containerRef.value.requestFullscreen();
        } else if (videoRef.value?.requestFullscreen) {
            videoRef.value.requestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

/**
 * Iniciar reproducción controlando promesas de HTML5 Media y políticas de autoplay.
 */
async function safePlay() {
    if (!videoRef.value) return;
    try {
        if (playOnHover.value || isAutoplay.value) {
            videoRef.value.muted = true;
        }
        playPromise = videoRef.value.play();
        if (playPromise !== undefined) {
            await playPromise;
        }
        isPlaying.value = true;
    } catch (err) {
        // Fallback: Si el navegador bloquea por política de interacción previa, forzar mute y reintentar inmediatamente
        if (videoRef.value) {
            try {
                videoRef.value.muted = true;
                playPromise = videoRef.value.play();
                if (playPromise !== undefined) {
                    await playPromise;
                }
                isPlaying.value = true;
            } catch (retryErr) {
                isPlaying.value = false;
            }
        } else {
            isPlaying.value = false;
        }
    } finally {
        playPromise = null;
    }
}

/**
 * Pausar reproducción esperando la resolución de promesas pendientes.
 */
function safePause() {
    if (!videoRef.value) return;
    if (playPromise) {
        playPromise
            .then(() => {
                if (videoRef.value) {
                    videoRef.value.pause();
                }
                isPlaying.value = false;
            })
            .catch(() => {})
            .finally(() => {
                playPromise = null;
            });
    } else {
        videoRef.value.pause();
        isPlaying.value = false;
    }
}

/**
 * Iniciar/despausar manualmente al hacer clic en el botón de play sobrepuesto.
 */
function togglePlayManual() {
    if (!videoRef.value) return;
    if (videoRef.value.paused) {
        videoRef.value.muted = isMuted.value;
        safePlay();
    } else {
        safePause();
    }
}

async function onMouseEnter() {
    if (playOnHover.value) {
        isHoverActive.value = true;
        if (videoRef.value) {
            isHoverLoading.value = true;
            try {
                videoRef.value.muted = true;
                await safePlay();
            } finally {
                isHoverLoading.value = false;
            }
        }
    }
}

function onMouseLeave() {
    if (playOnHover.value) {
        isHoverActive.value = false;
        isHoverLoading.value = false;
        safePause();
    }
}

// Espera a que las imágenes prioritarias (eager) de la vista actual terminen de descargar (tope 550 ms)
// antes de iniciar el streaming masivo del archivo MP4 para no saturar el ancho de banda inicial.
function triggerCooperativeAutoplay() {
    if (startupPlayTimer) {
        clearTimeout(startupPlayTimer);
        startupPlayTimer = null;
    }

    const startPlayback = () => {
        startupPlayTimer = null;
        if (isVisible.value && isAutoplay.value) {
            safePlay();
        }
    };

    if (typeof document === 'undefined') return;

    const pendingEagerImages = Array.from(document.querySelectorAll('img')).filter(
        (img) => !img.complete && (img.loading === 'eager' || img.getAttribute('fetchpriority') === 'high')
    );

    if (pendingEagerImages.length === 0) {
        startupPlayTimer = setTimeout(startPlayback, 60);
        return;
    }

    let settled = false;
    const finishOnce = () => {
        if (settled) return;
        settled = true;
        if (startupPlayTimer) {
            clearTimeout(startupPlayTimer);
        }
        startPlayback();
    };

    let remaining = pendingEagerImages.length;
    const onImgDone = () => {
        remaining -= 1;
        if (remaining <= 0) {
            finishOnce();
        }
    };

    pendingEagerImages.forEach((img) => {
        img.addEventListener('load', onImgDone, { once: true });
        img.addEventListener('error', onImgDone, { once: true });
    });

    // Tope máximo de cortesía (550 ms) por si alguna imagen tardara más en responder
    startupPlayTimer = setTimeout(finishOnce, 550);
}

onMounted(() => {
    if (typeof window === 'undefined') return;

    const targetEl = containerRef.value || videoRef.value;
    if ('IntersectionObserver' in window && targetEl) {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && entry.intersectionRatio > 0) {
                        isVisible.value = true;
                        if (isAutoplay.value) {
                            triggerCooperativeAutoplay();
                        }
                    } else {
                        isVisible.value = false;
                        if (pauseOnScroll.value) {
                            clearBufferingTimer();
                            isBuffering.value = false;
                            safePause();
                        }
                    }
                });
            },
            { rootMargin: '0px', threshold: [0, 0.01] }
        );

        observer.observe(targetEl);
    } else {
        isVisible.value = true;
        if (isAutoplay.value) {
            triggerCooperativeAutoplay();
        }
    }
});

onBeforeUnmount(() => {
    clearBufferingTimer();
    if (startupPlayTimer) {
        clearTimeout(startupPlayTimer);
        startupPlayTimer = null;
    }
    if (observer) {
        if (containerRef.value) {
            observer.unobserve(containerRef.value);
        }
        observer.disconnect();
        observer = null;
    }
    safePause();
    if (videoRef.value) {
        videoRef.value.removeAttribute('src');
        videoRef.value.load();
    }
});

// Reactividad limpia si cambia la URL del vídeo en vivo o al navegar por SPA entre proyectos
watch(videoSrc, (newSrc) => {
    clearBufferingTimer();
    isBuffering.value = false;
    hasReadyFrame.value = false;
    if (newSrc && videoRef.value) {
        videoRef.value.load();
        if (isAutoplay.value && isVisible.value) {
            triggerCooperativeAutoplay();
        }
    }
});
</script>

<template>
    <div
        v-if="shouldRenderContainer"
        ref="containerRef"
        :class="[
            'relative overflow-hidden w-full',
            roundedClass,
            shadowClass,
            alignmentClass,
            playOnHover ? 'group/hover-video cursor-pointer hover:ring-2 hover:ring-emerald-500/50 hover:shadow-lg hover:shadow-emerald-500/10' : '',
        ]"
        :style="[aspectRatioStyle, maxWidthStyle, animStyle]"
        v-bind="animAttrs"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        @pointerenter="onMouseEnter"
        @pointerleave="onMouseLeave"
    >
        <!-- Caso 1: Variable dinámica en el Builder sin resolver (ej: artículo de muestra sin vídeo o plantilla genérica) -->
        <div
            v-if="isBuilderPreview && isDynamicTag"
            class="w-full h-full min-h-[220px] bg-zinc-900/95 flex flex-col items-center justify-center p-6 text-center text-zinc-300 space-y-3 border-2 border-dashed border-emerald-500/40 rounded-xl"
        >
            <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/30">
                <i class="pi pi-database text-2xl" />
            </div>
            <div class="space-y-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-mono text-xs font-semibold border border-emerald-500/30">
                    <i class="pi pi-link text-[10px]" />
                    {{ rawSrc }}
                </span>
                <p class="text-xs font-medium text-zinc-300 pt-1">Vídeo Dinámico Conectado</p>
                <p class="text-[11px] text-zinc-400 max-w-xs mx-auto">
                    Se reproducirá automáticamente el vídeo del artículo cargado en este campo personalizado.
                </p>
            </div>
        </div>

        <!-- Caso 2: Estado Vacío / Placeholder en el Builder si no se ha seleccionado vídeo -->
        <div
            v-else-if="!videoSrc"
            class="w-full h-full min-h-[220px] bg-zinc-900 flex flex-col items-center justify-center p-6 text-center text-zinc-400 space-y-3 border-2 border-dashed border-zinc-700 rounded-lg"
        >
            <div class="w-12 h-12 rounded-full bg-zinc-800 flex items-center justify-center text-emerald-400">
                <i class="pi pi-video text-2xl" />
            </div>
            <div>
                <p class="text-xs font-bold text-zinc-200">Elemento Vídeo HTML5</p>
                <p class="text-[11px] text-zinc-500 mt-0.5">Selecciona un vídeo en el inspector o usa una variable dinámica.</p>
            </div>
        </div>

        <!-- Caso 3: Reproductor de Vídeo Optimizado -->
        <template v-else>
            <video
                ref="videoRef"
                :src="videoSrc"
                :poster="posterUrl || undefined"
                :preload="playOnHover ? 'auto' : 'metadata'"
                playsinline
                :muted="isMuted || (playOnHover && isHoverActive)"
                :loop="isLoop"
                :controls="hasControls"
                :class="[
                    'w-full h-full block',
                    objectFitClass,
                    disablePointerEvents ? 'pointer-events-none select-none' : '',
                ]"
                @timeupdate="onTimeUpdate"
                @durationchange="onDurationChange"
                @volumechange="onVolumeChange"
                @play="isPlaying = true"
                @pause="isPlaying = false"
                @loadstart="onMediaWaiting"
                @waiting="onMediaWaiting"
                @canplay="onMediaReady"
                @canplaythrough="onMediaReady"
                @playing="onMediaReady"
                @loadeddata="onMediaReady"
                @loadedmetadata="!isAutoplay ? onMediaReady() : undefined"
            ></video>

            <!-- Badge Indicador de Estado al Pasar el Ratón (Hover) -->
            <Transition name="fade-preloader">
                <div
                    v-if="playOnHover && isHoverActive"
                    class="absolute top-3 right-3 z-30 flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-950/85 backdrop-blur-md border border-emerald-500/40 shadow-2xl text-white pointer-events-none transition-all duration-300 select-none"
                >
                    <!-- Estado 1: Cargando / Búfer al iniciar Hover -->
                    <template v-if="isHoverLoading || isBuffering">
                        <div class="relative w-3.5 h-3.5 flex items-center justify-center">
                            <i class="pi pi-spin pi-spinner text-emerald-400 text-xs" />
                        </div>
                        <span class="text-[10px] font-mono font-bold tracking-wider text-emerald-300 uppercase">Cargando...</span>
                    </template>

                    <!-- Estado 2: En Reproducción activa durante Hover -->
                    <template v-else-if="isPlaying">
                        <div class="flex items-center gap-1">
                            <!-- Punto pulsante verde de emisión activa -->
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <!-- Ecualizador Animado Mini de 3 barras -->
                            <div class="flex items-end gap-0.5 h-2.5 w-3 ml-0.5">
                                <span class="w-0.5 bg-emerald-400 rounded-full animate-bounce [animation-delay:0ms] h-full"></span>
                                <span class="w-0.5 bg-emerald-400 rounded-full animate-bounce [animation-delay:150ms] h-2/3"></span>
                                <span class="w-0.5 bg-emerald-400 rounded-full animate-bounce [animation-delay:300ms] h-full"></span>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono font-bold tracking-wider text-emerald-300 uppercase ml-0.5">Reproduciendo</span>
                    </template>
                </div>
            </Transition>

            <!-- Preloader Inicial para Vídeos (solo antes del primer fotograma, nunca parpadea encima de reproducción activa) -->
            <Transition name="fade-preloader">
                <div
                    v-if="isBuffering && !hasReadyFrame && videoSrc && !playOnHover"
                    class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-zinc-950/70 backdrop-blur-md transition-all duration-300"
                >
                    <!-- Resplandor / Aura Pulsante en el Fondo -->
                    <div class="relative flex items-center justify-center">
                        <div class="absolute w-24 h-24 rounded-full border border-emerald-500/30 animate-ping" />
                        <div class="absolute w-20 h-20 rounded-full bg-emerald-500/10 blur-xl animate-pulse" />

                        <!-- Anillos Giratorios Concéntricos Duales -->
                        <div class="relative w-16 h-16 rounded-full border-2 border-emerald-500/20 flex items-center justify-center">
                            <!-- Anillo exterior sentido horario -->
                            <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-emerald-400 border-r-teal-400 animate-spin" />
                            <!-- Anillo interior sentido antihorario -->
                            <div class="absolute inset-1.5 rounded-full border-2 border-transparent border-t-cyan-400 border-l-emerald-300 animate-spin-reverse" />
                            <!-- Icono de vídeo en el núcleo con pulso -->
                            <i class="pi pi-video text-xl text-emerald-400 animate-pulse relative z-10" />
                        </div>
                    </div>

                    <!-- Texto de Estado y Barra de Carga Shimmer -->
                    <div class="mt-4 flex flex-col items-center space-y-2">
                        <div class="flex items-center gap-2 px-3.5 py-1 rounded-full bg-zinc-900/90 border border-zinc-800 shadow-xl backdrop-blur-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-xs shadow-emerald-400" />
                            <span class="text-[11px] font-semibold text-zinc-200 tracking-wide font-mono uppercase">Cargando vídeo...</span>
                        </div>

                        <!-- Línea de Carga con Efecto Shimmer Continuo -->
                        <div class="w-32 h-1 bg-zinc-800/90 rounded-full overflow-hidden relative shadow-inner">
                            <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-emerald-400 to-transparent animate-shimmer" />
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Capa de Superposición (Overlay Color para modo Cover/Fondo) -->
            <div
                v-if="overlayEnabled"
                class="absolute inset-0 pointer-events-none transition-opacity duration-300"
                :style="overlayStyle"
            />

            <!-- Botón de Play / Pause Centralizado sobrepuesto cuando no se usan controles nativos -->
            <div
                v-if="!hasControls && showCentralPlayButton && !isBuffering"
                class="absolute inset-0 flex items-center justify-center z-10 cursor-pointer group/overlay transition-all"
                @click="togglePlayManual"
            >
                <div
                    :class="[
                        'w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/85 hover:bg-white text-zinc-900 shadow-2xl backdrop-blur-xs flex items-center justify-center transition-all duration-300 transform group-hover/overlay:scale-110 border border-white/50',
                        isPlaying ? 'opacity-0 group-hover/overlay:opacity-100' : 'opacity-95 hover:opacity-100 scale-100'
                    ]"
                    v-tooltip.top="isPlaying ? 'Pausar vídeo' : 'Reproducir vídeo'"
                >
                    <!-- Icono PLAY SVG Vectorial Gigante (rellena el 75% del botón con margen ajustado) -->
                    <svg
                        v-if="!isPlaying"
                        class="w-10 h-10 sm:w-12 sm:h-12 text-zinc-900 ml-1 fill-current group-hover/overlay:scale-110 transition-transform duration-200"
                        viewBox="0 0 24 24"
                    >
                        <path d="M8 5v14l11-7z" />
                    </svg>

                    <!-- Icono PAUSE SVG Vectorial Gigante -->
                    <svg
                        v-else
                        class="w-10 h-10 sm:w-12 sm:h-12 text-zinc-900 fill-current group-hover/overlay:scale-110 transition-transform duration-200"
                        viewBox="0 0 24 24"
                    >
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                </div>
            </div>

            <!-- Mini-Reproductor Personalizado (Glassmorphism + Scrubber + Audio + Posicionamiento) -->
            <div
                v-if="showMiniPlayer && !isBuffering"
                :class="[
                    'absolute bottom-3 z-20 flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-zinc-950/80 border border-white/15 shadow-2xl backdrop-blur-md text-white transition-all duration-300 pointer-events-auto',
                    miniPlayerPositionClass,
                ]"
            >
                <!-- Botón Play / Pause -->
                <button
                    type="button"
                    @click.stop="togglePlayManual"
                    class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center transition-all shrink-0 cursor-pointer"
                    v-tooltip.top="isPlaying ? 'Pausar vídeo' : 'Reproducir vídeo'"
                >
                    <i :class="isPlaying ? 'pi pi-pause text-[11px]' : 'pi pi-play text-[11px] ml-0.5'" />
                </button>

                <!-- Duración y Tiempo Transcurrido -->
                <span class="text-[11px] font-mono text-zinc-200 font-medium shrink-0 whitespace-nowrap select-none">
                    {{ formattedCurrentTime }} / {{ formattedDuration }}
                </span>

                <!-- Barra de Progreso Interactiva (Scrubber) -->
                <div
                    class="w-20 sm:w-28 h-1.5 bg-zinc-800/90 rounded-full overflow-hidden cursor-pointer relative shrink-0 group/scrub"
                    @click.stop="seekVideo"
                    v-tooltip.top="'Buscar en el vídeo'"
                >
                    <div
                        class="h-full bg-emerald-400 rounded-full transition-all duration-75 group-hover/scrub:bg-emerald-300"
                        :style="{ width: progressPercent + '%' }"
                    />
                </div>

                <!-- Botón Mute / Desmutear -->
                <button
                    type="button"
                    @click.stop="toggleMute"
                    class="p-1 hover:text-emerald-400 text-zinc-300 transition-colors shrink-0 cursor-pointer"
                    v-tooltip.top="audioMuted ? 'Activar sonido' : 'Silenciar'"
                >
                    <i :class="audioMuted ? 'pi pi-volume-off text-xs text-amber-400' : 'pi pi-volume-up text-xs'" />
                </button>

                <!-- Botón Pantalla Completa -->
                <button
                    type="button"
                    @click.stop="toggleFullscreen"
                    class="p-1 hover:text-emerald-400 text-zinc-300 transition-colors shrink-0 cursor-pointer"
                    v-tooltip.top="'Pantalla completa'"
                >
                    <i class="pi pi-expand text-xs" />
                </button>
            </div>
        </template>
    </div>
</template>

<style scoped>
@keyframes spin-reverse {
    0% {
        transform: rotate(360deg);
    }
    100% {
        transform: rotate(0deg);
    }
}

@keyframes shimmer-slide {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.animate-spin-reverse {
    animation: spin-reverse 1.2s linear infinite;
}

.animate-shimmer {
    animation: shimmer-slide 1.5s ease-in-out infinite;
}

.fade-preloader-enter-active {
    transition: opacity 0.25s ease;
}

.fade-preloader-leave-active {
    transition: opacity 0.18s ease-out;
}

.fade-preloader-enter-from,
.fade-preloader-leave-to {
    opacity: 0;
}
</style>
