<script setup>
/**
 * Inspector Atómico para el Elemento Vídeo HTML5 Optimizado.
 *
 * Configura fuente de vídeo, imagen poster/portada, aspectos (16:9, 4:3, 1:1, 9:16, 21:9),
 * controles de reproducción (autoplay, muted, loop, controls, auto-pause), superposiciones y botón de play.
 */
import { computed, watch } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import VideoUploader from '@/Components/Admin/VideoUploader.vue';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import DynamicTagSelector from '../DynamicTagSelector.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

const content = computed(() => props.elementoSeleccionado?.content || {});

function insertarEtiquetaSrc(tag) {
    if (props.elementoSeleccionado?.content) {
        props.elementoSeleccionado.content.src = tag;
    }
}

function insertarEtiquetaPoster(tag) {
    if (props.elementoSeleccionado?.content) {
        props.elementoSeleccionado.content.poster = tag;
    }
}

// Forzar silenciado (muted: true) si se activa autoplay debido a restricciones de navegadores
watch(
    () => content.value.autoplay,
    (val) => {
        if (val) {
            content.value.muted = true;
        }
    }
);
</script>

<template>
    <div v-if="elementoSeleccionado && elementoSeleccionado.content" class="space-y-5">
        <!-- 1. FUENTE DE VÍDEO -->
        <div class="space-y-3 bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    1. Archivo de Vídeo
                </h4>
                <DynamicTagSelector tipo="video" @insertar="insertarEtiquetaSrc" />
            </div>
            <VideoUploader
                v-model="elementoSeleccionado.content.src"
                label="Vídeo Principal (MP4, WebM)"
                help="Sube un archivo a la biblioteca, elige uno existente o usa una variable dinámica."
            />
        </div>

        <!-- 2. PORTADA / COVER IMAGE -->
        <div class="space-y-3 bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    2. Portada (Poster Image)
                </h4>
                <DynamicTagSelector tipo="image" @insertar="insertarEtiquetaPoster" />
            </div>
            <ImageUploader
                v-model="elementoSeleccionado.content.poster"
                label="Imagen de Portada (Opcional)"
                help="Imagen estática que se muestra antes de reproducir el vídeo."
            />

            <div class="flex items-center justify-between pt-1">
                <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    Usar primer fotograma como portada (#t=0.001)
                </label>
                <ToggleSwitch
                    :modelValue="elementoSeleccionado.content.use_first_frame !== false"
                    @update:modelValue="(val) => elementoSeleccionado.content.use_first_frame = val"
                />
            </div>
            <p class="text-[10px] text-zinc-400">
                Evita la pantalla en negro en dispositivos móviles y Safari si no especificas una portada.
            </p>
        </div>

        <!-- 3. CONTROLES Y REPRODUCCIÓN -->
        <div class="space-y-3 bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800">
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                3. Opciones de Reproducción y Controles
            </h4>

            <div class="space-y-3">
                <!-- Tipo de Controles -->
                <div>
                    <label class="block text-xs font-semibold mb-1 text-zinc-800 dark:text-zinc-200">Tipo de Controles / Reproductor</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.control_type || (elementoSeleccionado.content.controls ? 'native' : (elementoSeleccionado.content.disable_pointer_events ? 'none' : 'custom'))"
                        @update:modelValue="(val) => {
                            elementoSeleccionado.content.control_type = val;
                            if (val === 'native') {
                                elementoSeleccionado.content.controls = true;
                                elementoSeleccionado.content.disable_pointer_events = false;
                            } else if (val === 'none') {
                                elementoSeleccionado.content.controls = false;
                                elementoSeleccionado.content.show_play_button = false;
                                elementoSeleccionado.content.show_mini_player = false;
                                elementoSeleccionado.content.disable_pointer_events = true;
                            } else {
                                elementoSeleccionado.content.controls = false;
                                elementoSeleccionado.content.disable_pointer_events = false;
                                if (elementoSeleccionado.content.show_play_button === undefined) {
                                    elementoSeleccionado.content.show_play_button = true;
                                }
                            }
                        }"
                        :options="[
                            { label: 'Ninguno / Modo Fondo Limpio (Hero Ininterrumpido)', value: 'none' },
                            { label: 'Controles Nativos del Navegador', value: 'native' },
                            { label: 'Controles Personalizados CMS', value: 'custom' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Opciones Específicas de Controles Personalizados -->
                <div v-if="(elementoSeleccionado.content.control_type || (elementoSeleccionado.content.controls ? 'native' : (elementoSeleccionado.content.disable_pointer_events ? 'none' : 'custom'))) === 'custom'" class="p-2.5 bg-zinc-100 dark:bg-zinc-800/60 rounded-lg space-y-2.5 border border-zinc-200 dark:border-zinc-700/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Botón Play/Pause Central (Hover)</label>
                            <p class="text-[10px] text-zinc-400">Muestra el icono gigante flotante blanco sobre el vídeo.</p>
                        </div>
                        <ToggleSwitch
                            :modelValue="elementoSeleccionado.content.show_play_button !== false"
                            @update:modelValue="(val) => elementoSeleccionado.content.show_play_button = val"
                        />
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                        <div>
                            <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Mostrar Mini-Reproductor Avanzado</label>
                            <p class="text-[10px] text-zinc-400">Mini-barra inferior con tiempo, scrubber, volumen y pantalla completa.</p>
                        </div>
                        <ToggleSwitch
                            :modelValue="Boolean(elementoSeleccionado.content.show_mini_player)"
                            @update:modelValue="(val) => elementoSeleccionado.content.show_mini_player = val"
                        />
                    </div>

                    <div v-if="elementoSeleccionado.content.show_mini_player" class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                        <label class="block text-xs font-semibold mb-1">Posición del Mini-Reproductor</label>
                        <Select
                            :modelValue="elementoSeleccionado.content.mini_player_position || 'center'"
                            @update:modelValue="(val) => elementoSeleccionado.content.mini_player_position = val"
                            :options="[
                                { label: 'Inferior Centro', value: 'center' },
                                { label: 'Inferior Izquierda', value: 'left' },
                                { label: 'Inferior Derecha', value: 'right' }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div>
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Reproducción Automática (Autoplay)</label>
                        <p class="text-[10px] text-zinc-400">Requiere silenciar audio por políticas del navegador.</p>
                    </div>
                    <ToggleSwitch v-model="elementoSeleccionado.content.autoplay" />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Silenciado por Defecto (Muted)</label>
                    </div>
                    <ToggleSwitch
                        v-model="elementoSeleccionado.content.muted"
                        :disabled="elementoSeleccionado.content.autoplay"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Bucle Infinito (Loop)</label>
                    </div>
                    <ToggleSwitch v-model="elementoSeleccionado.content.loop" />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Pausar al salir de pantalla (Auto-Pause)</label>
                        <p class="text-[10px] text-zinc-400">Optimiza el rendimiento a 60 FPS deteniendo vídeos fuera de visión.</p>
                    </div>
                    <ToggleSwitch
                        :modelValue="elementoSeleccionado.content.pause_on_scroll !== false"
                        @update:modelValue="(val) => elementoSeleccionado.content.pause_on_scroll = val"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Reproducir al pasar el ratón (Hover)</label>
                    </div>
                    <ToggleSwitch v-model="elementoSeleccionado.content.play_on_hover" />
                </div>
            </div>
        </div>

        <!-- 4. FORMATO, MEDIDAS Y DISEÑO -->
        <div class="space-y-3 bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800">
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                4. Formato, Medidas y Estilo
            </h4>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Relación de Aspecto (Aspect Ratio)</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.aspect_ratio || '16:9'"
                        @update:modelValue="(val) => elementoSeleccionado.content.aspect_ratio = val"
                        :options="[
                            { label: '16:9 (Panorámico TV / YouTube)', value: '16:9' },
                            { label: '4:3 (Estándar)', value: '4:3' },
                            { label: '1:1 (Cuadrado)', value: '1:1' },
                            { label: '21:9 (Ultra-Panorámico Cine)', value: '21:9' },
                            { label: '32:9 (Super Ultra-Panorámico / Banner)', value: '32:9' },
                            { label: '3:1 (Franja Panorámica 3:1)', value: '3:1' },
                            { label: '9:16 (Vertical Reels / Shorts)', value: '9:16' },
                            { label: 'Original / Auto', value: 'auto' },
                            { label: 'Alto Personalizado', value: 'custom' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Ajuste de Vídeo (Object Fit)</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.object_fit || 'cover'"
                        @update:modelValue="(val) => elementoSeleccionado.content.object_fit = val"
                        :options="[
                            { label: 'Cubrir Todo (Cover - Rellenar)', value: 'cover' },
                            { label: 'Contener Completo (Contain)', value: 'contain' },
                            { label: 'Estirar (Fill)', value: 'fill' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <div v-if="elementoSeleccionado.content.aspect_ratio === 'custom'">
                <label class="block text-xs font-semibold mb-1">Alto Personalizado (ej: 400px, 50vh)</label>
                <InputText
                    v-model="elementoSeleccionado.content.custom_height"
                    placeholder="ej: 450px"
                    class="w-full text-xs font-mono"
                />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Ancho Máximo (Max Width)</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.max_width || '100%'"
                        @update:modelValue="(val) => elementoSeleccionado.content.max_width = val"
                        :options="[
                            { label: '100% Ancho Total', value: '100%' },
                            { label: '75% Ancho Fila', value: '75%' },
                            { label: '50% Ancho Fila', value: '50%' },
                            { label: 'Max 800px', value: '800px' },
                            { label: 'Max 600px', value: '600px' },
                            { label: 'Max 400px', value: '400px' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Alineación Horizontal</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.align || 'center'"
                        @update:modelValue="(val) => elementoSeleccionado.content.align = val"
                        :options="[
                            { label: 'Izquierda', value: 'left' },
                            { label: 'Centrado', value: 'center' },
                            { label: 'Derecha', value: 'right' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Bordes Redondeados</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.rounded || 'lg'"
                        @update:modelValue="(val) => elementoSeleccionado.content.rounded = val"
                        :options="[
                            { label: 'Recto (0px)', value: 'none' },
                            { label: 'Pequeño (4px)', value: 'sm' },
                            { label: 'Mediano (8px)', value: 'md' },
                            { label: 'Grande (12px)', value: 'lg' },
                            { label: 'Extra Grande (16px)', value: 'xl' },
                            { label: '2X Grande (24px)', value: '2xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Sombra de Caja (Drop Shadow)</label>
                    <Select
                        :modelValue="elementoSeleccionado.content.shadow || 'md'"
                        @update:modelValue="(val) => elementoSeleccionado.content.shadow = val"
                        :options="[
                            { label: 'Sin Sombra', value: 'none' },
                            { label: 'Suave / Pequeña', value: 'sm' },
                            { label: 'Mediana Estándar', value: 'md' },
                            { label: 'Intensa / Elevada', value: 'lg' },
                            { label: 'Extra Profunda', value: 'xl' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- 5. SUPERPOSICIÓN Y BOTÓN PLAY OVERLAY -->
        <div class="space-y-3 bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800">
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                5. Superposición & Botón Play (Video Cover)
            </h4>

            <div class="flex items-center justify-between">
                <div>
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Activar Capa de Superposición (Overlay)</label>
                    <p class="text-[10px] text-zinc-400">Oscurece el vídeo para destacar elementos o textos superiores.</p>
                </div>
                <ToggleSwitch v-model="elementoSeleccionado.content.overlay_enabled" />
            </div>

            <div v-if="elementoSeleccionado.content.overlay_enabled">
                <label class="block text-xs font-semibold mb-1">Color / Opacidad de Superposición</label>
                <InputText
                    v-model="elementoSeleccionado.content.overlay_color"
                    placeholder="rgba(0, 0, 0, 0.4)"
                    class="w-full text-xs font-mono"
                />
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <div>
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Mostrar Botón Play Centralizado</label>
                </div>
                <ToggleSwitch
                    :modelValue="elementoSeleccionado.content.show_play_button !== false"
                    @update:modelValue="(val) => elementoSeleccionado.content.show_play_button = val"
                />
            </div>
        </div>

        <!-- PERSONALIZACIÓN AVANZADA Y CÓDIGO -->
        <AdvancedCustomizationBlock :content="elementoSeleccionado.content" />
    </div>
</template>
