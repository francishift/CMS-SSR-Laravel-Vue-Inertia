<script setup>
/**
 * Componente Reutilizable ImageUploader (Selección de Medios).
 *
 * Interfaz simplificada con un único botón "Seleccionar imagen" que abre
 * la Biblioteca de Medios (MediaPickerModal), donde el usuario puede:
 *  1. Seleccionar un archivo existente de la biblioteca.
 *  2. Subir un nuevo archivo (con asignación de colección).
 * Compatible con v-model.
 */
import { ref, computed } from 'vue';
import MediaPickerModal from '@/Components/Admin/MediaPickerModal.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    /** URL real resuelta cuando modelValue contiene una variable dinámica como {download.featured_image} */
    previewSrc: { type: String, default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Seleccionar imagen...' },
    help: { type: String, default: '' },
    compact: { type: Boolean, default: false },
    /** Filtrar el picker por tipo: 'image', 'video', o null para ambos */
    filtroTipo: { type: String, default: 'image' },
    labelClass: { type: String, default: 'text-zinc-800 dark:text-zinc-200' },
    helpClass: { type: String, default: 'text-zinc-400 dark:text-zinc-500' },
    showPreview: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const pickerAbierto = ref(false);

const esVariableDinamica = computed(() => {
    const v = String(props.modelValue || '').trim();
    return v.startsWith('{') && v.endsWith('}');
});

const urlEfectivaPreview = computed(() => {
    if (props.previewSrc) return props.previewSrc;
    if (esVariableDinamica.value) return '';
    return props.modelValue || '';
});

const esPreviewSvg = computed(() => {
    const clean = (urlEfectivaPreview.value || '').split('?')[0].split('#')[0].toLowerCase();
    return clean.endsWith('.svg');
});

function onMedioSeleccionado(medio) {
    const url = typeof medio === 'string' ? medio : medio?.url;
    if (url) {
        emit('update:modelValue', url);
    }
}

function eliminarImagen() {
    emit('update:modelValue', '');
}
</script>

<template>
    <div class="space-y-2">
        <!-- Etiqueta y ayuda -->
        <div v-if="label" class="flex items-center justify-between">
            <label :class="['block text-xs font-semibold', labelClass]">{{ label }}</label>
            <span v-if="help" :class="['text-[10px]', helpClass]">{{ help }}</span>
        </div>

        <!-- ═══ Estado CON imagen: Previsualización premium ═══ -->
        <div
            v-if="showPreview && modelValue"
            class="image-uploader-preview group relative rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-zinc-800 transition-all duration-300 hover:shadow-lg hover:border-zinc-300 dark:hover:border-zinc-700"
        >
            <!-- Fondo de patrón (tablero de ajedrez) para imágenes con transparencia -->
            <div class="absolute inset-0 opacity-30 dark:opacity-20" style="background-image: repeating-conic-gradient(#e4e4e7 0% 25%, transparent 0% 50%); background-size: 16px 16px;" />

            <!-- Etiqueta informativa si proviene de una variable dinámica -->
            <div
                v-if="esVariableDinamica"
                class="absolute top-2 left-2 z-[3] inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-600/90 text-white text-[10px] font-mono font-semibold shadow-xs"
            >
                <i class="pi pi-bolt text-[9px]" />
                <span>{{ modelValue }}</span>
            </div>

            <!-- Imagen con aspect ratio contenido -->
            <div class="relative flex items-center justify-center p-4 min-h-[110px] max-h-[200px]">
                <img
                    v-if="urlEfectivaPreview"
                    :src="urlEfectivaPreview"
                    alt="Previsualización"
                    :class="[
                        'max-h-[160px] w-auto max-w-full object-contain rounded-lg relative z-[1] transition-transform duration-300 group-hover:scale-[0.97]',
                        esPreviewSvg ? 'w-20 h-20 dark:invert dark:opacity-90' : 'shadow-sm',
                    ]"
                />
                <div v-else class="flex flex-col items-center gap-1 text-zinc-500 dark:text-zinc-400 text-xs py-3 relative z-[1]">
                    <i class="pi pi-bolt text-emerald-500 text-base" />
                    <span class="font-mono text-[11px]">{{ modelValue }}</span>
                    <span class="text-[10px] text-zinc-400">Sin imagen en el elemento de muestra actual</span>
                </div>
            </div>

            <!-- Overlay con acciones al hacer hover -->
            <div class="absolute inset-0 z-[2] bg-gradient-to-t from-zinc-950/80 via-zinc-950/40 to-zinc-950/20 opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col items-center justify-end p-3 gap-2">
                <!-- Fila de botones -->
                <div class="flex items-center gap-2 translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                    <a
                        v-if="urlEfectivaPreview"
                        :href="urlEfectivaPreview"
                        target="_blank"
                        class="px-3 py-1.5 bg-white/15 hover:bg-white/25 backdrop-blur-md text-white rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1.5 border border-white/10"
                        title="Ver imagen completa en pestaña nueva"
                    >
                        <i class="pi pi-external-link text-[10px]" />
                        <span>Ver</span>
                    </a>
                    <button
                        type="button"
                        @click="pickerAbierto = true"
                        class="px-3 py-1.5 bg-emerald-500/80 hover:bg-emerald-500 backdrop-blur-md text-white rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1.5 cursor-pointer border border-emerald-400/20"
                        title="Cambiar imagen"
                    >
                        <i class="pi pi-sync text-[10px]" />
                        <span>Cambiar</span>
                    </button>
                    <button
                        type="button"
                        @click="eliminarImagen"
                        class="px-3 py-1.5 bg-red-500/80 hover:bg-red-500 backdrop-blur-md text-white rounded-lg text-[11px] font-semibold transition-all cursor-pointer flex items-center gap-1.5 border border-red-400/20"
                        title="Eliminar imagen"
                    >
                        <i class="pi pi-trash text-[10px]" />
                        <span>Quitar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ Estado SIN imagen: Botón elegante para seleccionar ═══ -->
        <button
            v-else
            type="button"
            @click="pickerAbierto = true"
            class="image-uploader-empty w-full group cursor-pointer"
        >
            <div class="relative overflow-hidden rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 hover:border-emerald-400 dark:hover:border-emerald-500 bg-zinc-50/50 dark:bg-zinc-800/30 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all duration-300 px-4 py-5">
                <!-- Icono circular con efecto -->
                <div class="flex flex-col items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-zinc-200/70 dark:bg-zinc-700/50 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:shadow-md">
                        <i class="pi pi-image text-base text-zinc-400 dark:text-zinc-500 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors duration-300" />
                    </div>
                    <div class="text-center">
                        <span class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-300">
                            Seleccionar imagen
                        </span>
                        <span class="block text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                            Abrir biblioteca de medios
                        </span>
                    </div>
                </div>

                <!-- Destello decorativo en hover -->
                <div class="absolute -top-10 -right-10 w-20 h-20 bg-emerald-400/10 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            </div>
        </button>

        <!-- Modal Selector de Medios -->
        <MediaPickerModal
            v-model:visible="pickerAbierto"
            :filtro-tipo-inicial="filtroTipo"
            @seleccionar="onMedioSeleccionado"
        />
    </div>
</template>
