<script setup>
/**
 * Componente Reutilizable VideoUploader (Selección y Gestión de Vídeos).
 *
 * Interfaz unificada con la Biblioteca de Medios (MediaPickerModal):
 *  1. Botón único estilizado para abrir la biblioteca, donde el usuario puede:
 *     - Seleccionar un vídeo optimizado existente.
 *     - Subir un nuevo vídeo (procesado y optimizado automáticamente con FFmpeg +faststart).
 *  2. Soporte nativo para variables dinámicas (ej: {article.extra_fields.video_principal}).
 *  3. Previsualización HTML5 fluida con poster/fotograma inicial (#t=0.001).
 *  4. Acceso opcional a URL manual/externa.
 * Compatible con v-model.
 */
import { ref, computed } from 'vue';
import InputText from 'primevue/inputtext';
import MediaPickerModal from '@/Components/Admin/MediaPickerModal.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Seleccionar vídeo o introducir URL...' },
    help: { type: String, default: '' },
    compact: { type: Boolean, default: false },
    labelClass: { type: String, default: 'text-zinc-800 dark:text-zinc-200' },
    helpClass: { type: String, default: 'text-zinc-400 dark:text-zinc-500' },
});

const emit = defineEmits(['update:modelValue']);

const pickerAbierto = ref(false);
const mostrarUrlManual = ref(false);

// Detección de etiqueta o variable dinámica (ej: {article.extra_fields.video})
const esDinamico = computed(() => {
    if (typeof props.modelValue !== 'string') return false;
    const v = props.modelValue.trim();
    return v.startsWith('{') && v.endsWith('}');
});

function onMedioSeleccionado(medio) {
    const url = typeof medio === 'string' ? medio : medio?.url;
    if (url) {
        emit('update:modelValue', url);
    }
}

function eliminarVideo() {
    emit('update:modelValue', '');
}
</script>

<template>
    <div class="space-y-2">
        <!-- Etiqueta y ayuda superior -->
        <div v-if="label" class="flex items-center justify-between">
            <label :class="['block text-xs font-semibold', labelClass]">{{ label }}</label>
            <span v-if="help" :class="['text-[10px]', helpClass]">{{ help }}</span>
        </div>

        <!-- ═══ CASO 1: Variable Dinámica Vinculada ({article.extra_fields...}) ═══ -->
        <div
            v-if="esDinamico"
            class="p-3.5 bg-purple-50/70 dark:bg-purple-950/30 rounded-xl border border-purple-200/80 dark:border-purple-800/60 flex items-center justify-between gap-3 transition-colors"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/60 flex items-center justify-center text-purple-600 dark:text-purple-300 shrink-0">
                    <i class="pi pi-bolt text-xs" />
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-purple-900 dark:text-purple-200">Campo Dinámico Vinculado</span>
                        <span class="text-[9px] uppercase px-1.5 py-0.2 rounded-full font-bold bg-purple-200/70 dark:bg-purple-800/60 text-purple-800 dark:text-purple-200">
                            Dinámico
                        </span>
                    </div>
                    <div class="font-mono text-[11px] text-purple-700 dark:text-purple-300 truncate mt-0.5">
                        {{ modelValue }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <button
                    type="button"
                    @click="pickerAbierto = true"
                    class="px-2.5 py-1 text-[11px] font-semibold text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/50 rounded-lg transition-colors cursor-pointer"
                    title="Seleccionar archivo estático de la biblioteca"
                >
                    Biblioteca
                </button>
                <button
                    type="button"
                    @click="eliminarVideo"
                    class="p-1.5 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg transition-colors cursor-pointer"
                    title="Quitar variable dinámica"
                >
                    <i class="pi pi-times text-xs" />
                </button>
            </div>
        </div>

        <!-- ═══ CASO 2: Archivo de Vídeo Estático (URL o Biblioteca) ═══ -->
        <div
            v-else-if="modelValue"
            class="video-uploader-preview group relative rounded-xl border border-zinc-200/80 dark:border-zinc-800 overflow-hidden bg-zinc-950 p-2 flex items-center justify-center min-h-[140px] max-h-[220px] transition-all hover:border-zinc-300 dark:hover:border-zinc-700 hover:shadow-md"
        >
            <video
                :src="modelValue.includes('#t=') ? modelValue : modelValue + '#t=0.001'"
                preload="metadata"
                controls
                class="max-h-44 w-auto max-w-full rounded-lg shadow-sm"
            />

            <!-- Botones de Acción al pasar el ratón -->
            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5 z-10">
                <a
                    :href="modelValue"
                    target="_blank"
                    class="px-2.5 py-1 bg-zinc-900/85 hover:bg-zinc-800 text-white rounded-lg text-[11px] font-semibold backdrop-blur-md transition-colors flex items-center gap-1"
                    title="Ver archivo en pestaña nueva"
                >
                    <i class="pi pi-external-link text-[10px]" />
                    <span>Ver</span>
                </a>
                <button
                    type="button"
                    @click="pickerAbierto = true"
                    class="px-2.5 py-1 bg-emerald-600/85 hover:bg-emerald-600 text-white rounded-lg text-[11px] font-semibold backdrop-blur-md transition-colors cursor-pointer flex items-center gap-1"
                    title="Cambiar vídeo desde la biblioteca"
                >
                    <i class="pi pi-sync text-[10px]" />
                    <span>Cambiar</span>
                </button>
                <button
                    type="button"
                    @click="eliminarVideo"
                    class="px-2.5 py-1 bg-red-600/85 hover:bg-red-600 text-white rounded-lg text-[11px] font-semibold backdrop-blur-md transition-colors cursor-pointer flex items-center gap-1"
                    title="Eliminar vídeo"
                >
                    <i class="pi pi-trash text-[10px]" />
                    <span>Quitar</span>
                </button>
            </div>
        </div>

        <!-- ═══ CASO 3: Estado Vacío: Botón Elegante para Seleccionar ═══ -->
        <button
            v-else
            type="button"
            @click="pickerAbierto = true"
            class="video-uploader-empty w-full group cursor-pointer"
        >
            <div class="relative overflow-hidden rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 hover:border-emerald-400 dark:hover:border-emerald-500 bg-zinc-50/50 dark:bg-zinc-800/30 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all duration-300 px-4 py-5">
                <div class="flex flex-col items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-zinc-200/70 dark:bg-zinc-700/50 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:shadow-md">
                        <i class="pi pi-video text-base text-zinc-400 dark:text-zinc-500 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors duration-300" />
                    </div>
                    <div class="text-center">
                        <span class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-300">
                            Seleccionar vídeo
                        </span>
                        <span class="block text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                            Abrir biblioteca de medios (seleccionar o subir con FFmpeg)
                        </span>
                    </div>
                </div>

                <!-- Destello decorativo en hover -->
                <div class="absolute -top-10 -right-10 w-20 h-20 bg-emerald-400/10 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            </div>
        </button>

        <!-- Entrada opcional de URL externa o ruta manual -->
        <div class="pt-0.5">
            <div class="flex items-center justify-between">
                <button
                    type="button"
                    @click="mostrarUrlManual = !mostrarUrlManual"
                    class="text-[10px] text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors flex items-center gap-1 cursor-pointer font-medium"
                >
                    <i :class="['text-[9px] transition-transform', mostrarUrlManual ? 'pi pi-chevron-down' : 'pi pi-chevron-right']" />
                    <span>{{ mostrarUrlManual ? 'Ocultar entrada de URL manual' : 'O introducir URL externa o variable...' }}</span>
                </button>
            </div>
            <div v-if="mostrarUrlManual" class="mt-1.5">
                <InputText
                    :model-value="modelValue"
                    @input="emit('update:modelValue', $event.target.value)"
                    :placeholder="placeholder"
                    class="w-full !text-xs font-mono"
                />
            </div>
        </div>

        <!-- Modal Selector de Medios (con subida y optimización FFmpeg) -->
        <MediaPickerModal
            v-model:visible="pickerAbierto"
            filtro-tipo-inicial="video"
            @seleccionar="onMedioSeleccionado"
        />
    </div>
</template>
