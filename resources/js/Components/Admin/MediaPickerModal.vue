<script setup>
/**
 * Modal reutilizable para seleccionar archivos de la Biblioteca de Medios.
 *
 * Panel lateral con previsualización, renombrado inline, edición de metadatos SEO,
 * eliminación y selección. Pestañas Biblioteca/Subir Nuevo.
 */
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';

const props = defineProps({
    visible: { type: Boolean, required: true },
    /** Filtrar por tipo: 'image', 'video' o null para todos */
    filtroTipoInicial: { type: String, default: null },
});

const emit = defineEmits(['update:visible', 'seleccionar']);

const toast = useToast();
const confirm = useConfirm();

// ─── Estado ───────────────────────────────────────────
const pestana = ref('biblioteca');
const items = ref([]);
const colecciones = ref([]);
const filtroTipo = ref(props.filtroTipoInicial || '');
const filtroColeccion = ref('');
const cargando = ref(false);
const busqueda = ref('');
const pagina = ref(1);
const ultimaPagina = ref(1);
const totalItems = ref(0);
const medioPreview = ref(null);

const opcionesTipo = [
    { label: 'Todos los tipos', value: 'all' },
    { label: 'Imágenes', value: 'image' },
    { label: 'Vídeos', value: 'video' },
];

const tipoSeleccionado = computed({
    get: () => filtroTipo.value || 'all',
    set: (val) => {
        filtroTipo.value = (val === 'all') ? '' : val;
    },
});

const opcionesColeccion = computed(() => {
    const base = [{ label: 'Todas las colecciones', value: 'all' }];
    return base.concat((colecciones.value || []).map(c => ({ label: c, value: c })));
});

const coleccionSeleccionada = computed({
    get: () => filtroColeccion.value || 'all',
    set: (val) => {
        filtroColeccion.value = (val === 'all' || val === 'none') ? '' : val;
    },
});

const totalFiltrosActivos = computed(() => {
    let count = 0;
    if (busqueda.value && busqueda.value.trim() !== '') count++;
    if (filtroTipo.value && filtroTipo.value !== 'all') count++;
    if (filtroColeccion.value && filtroColeccion.value !== 'all') count++;
    return count;
});

function limpiarFiltros() {
    busqueda.value = '';
    filtroTipo.value = '';
    filtroColeccion.value = '';
    cargarBiblioteca(true);
}

function limpiarBusqueda() {
    busqueda.value = '';
    cargarBiblioteca(true);
}

// ─── Navegación en el inspector del modal ─────────────
const indicePreview = computed(() => {
    if (!medioPreview.value || !items.value) return -1;
    return items.value.findIndex(m => m.id === medioPreview.value.id);
});

const hayAnterior = computed(() => indicePreview.value > 0);
const haySiguiente = computed(() => {
    if (!items.value) return false;
    return indicePreview.value >= 0 && indicePreview.value < items.value.length - 1;
});

function irAnterior() {
    if (hayAnterior.value && items.value) {
        editandoNombre.value = false;
        medioPreview.value = items.value[indicePreview.value - 1];
    }
}

function irSiguiente() {
    if (haySiguiente.value && items.value) {
        editandoNombre.value = false;
        medioPreview.value = items.value[indicePreview.value + 1];
    }
}

// ─── Selector inteligente de colección en inspector ───
const mostrarNuevaColeccionInspector = ref(false);
const nuevaColeccionInspectorInput = ref('');

const opcionesColeccionInspector = computed(() => {
    const base = [
        { label: 'Sin colección (general)', value: 'none' },
    ];
    const colList = (colecciones.value || []).map(c => ({ label: c, value: c }));
    return base.concat(colList, [{ label: '+ Nueva colección...', value: '__new__' }]);
});

const coleccionInspectorSeleccionada = computed({
    get: () => medioPreview.value?.collection || 'none',
    set: (val) => {
        if (val === '__new__') {
            mostrarNuevaColeccionInspector.value = true;
            nuevaColeccionInspectorInput.value = '';
        } else {
            const valorFinal = (val === 'none' || !val) ? null : val;
            if (medioPreview.value) {
                medioPreview.value.collection = valorFinal;
            }
            guardarMetaInline('collection', valorFinal);
        }
    },
});

function guardarNuevaColeccionInspector() {
    const nombre = nuevaColeccionInspectorInput.value.trim();
    if (!nombre) {
        mostrarNuevaColeccionInspector.value = false;
        return;
    }
    if (!colecciones.value.includes(nombre)) {
        colecciones.value.push(nombre);
    }
    if (medioPreview.value) {
        medioPreview.value.collection = nombre;
    }
    guardarMetaInline('collection', nombre);
    nuevaColeccionInspectorInput.value = '';
    mostrarNuevaColeccionInspector.value = false;
}

// ─── Atajos de teclado para modal ─────────────────────
function onKeyDown(e) {
    if (!props.visible) return;
    if (['INPUT', 'TEXTAREA'].includes(e.target?.tagName) || e.target?.isContentEditable) {
        return;
    }
    if (e.key === 'Escape') {
        if (editandoNombre.value) {
            editandoNombre.value = false;
        } else if (mostrarNuevaColeccionInspector.value) {
            mostrarNuevaColeccionInspector.value = false;
        } else if (medioPreview.value) {
            medioPreview.value = null;
        }
    } else if (e.key === 'ArrowLeft' && medioPreview.value) {
        irAnterior();
    } else if (e.key === 'ArrowRight' && medioPreview.value) {
        irSiguiente();
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
});

// ─── Subida ───────────────────────────────────────────
const cargandoSubida = ref(false);
const porcentajeSubida = ref(0);
const progresoNombreArchivo = ref('');
const progresoEstadoText = ref('Subiendo archivo...');
const inputUploadRef = ref(null);
const dragActivo = ref(false);
const coleccionSubida = ref('none');
const nuevaColeccionInput = ref('');
const mostrarNuevaColeccion = ref(false);

/**
 * Opciones de colección para el selector de la pestaña de subida.
 * Incluye las colecciones existentes cargadas desde el backend.
 * Sigue la regla de PrimeVue 4 evitando cadena vacía en optionValue.
 */
const opcionesColeccionSubida = computed(() => {
    const base = [{ label: 'Sin colección (general)', value: 'none' }];
    return base.concat((colecciones.value || []).map(c => ({ label: c, value: c })));
});

/**
 * Añade una nueva colección escrita por el usuario al selector y la selecciona.
 */
function agregarNuevaColeccion() {
    const nombre = nuevaColeccionInput.value.trim();
    if (!nombre) return;
    // Añadir a la lista local si no existe
    if (!colecciones.value.includes(nombre)) {
        colecciones.value.push(nombre);
    }
    coleccionSubida.value = nombre;
    nuevaColeccionInput.value = '';
    mostrarNuevaColeccion.value = false;
}

// ─── Renombrado ───────────────────────────────────────
const editandoNombre = ref(false);
const nuevoNombre = ref('');

// ─── Carga de la biblioteca ───────────────────────────
async function cargarBiblioteca(reset = false) {
    if (reset) {
        pagina.value = 1;
        items.value = [];
    }
    cargando.value = true;
    try {
        const params = {
            page: pagina.value,
            search: busqueda.value || undefined,
            collection: filtroColeccion.value || undefined,
        };
        if (filtroTipo.value) {
            params.type = filtroTipo.value;
        }

        const res = await axios.get(route('media.list'), { params });
        if (res.data?.success) {
            if (res.data.colecciones) {
                colecciones.value = res.data.colecciones;
                // Si la colección activa en el filtro ya no existe, limpiarla y recargar
                if (filtroColeccion.value && !res.data.colecciones.includes(filtroColeccion.value)) {
                    filtroColeccion.value = '';
                    cargarBiblioteca(true);
                    return;
                }
            }
            if (reset) {
                items.value = res.data.items?.data || res.data.items || [];
            } else {
                const nuevos = res.data.items?.data || res.data.items || [];
                items.value = [...items.value, ...nuevos];
            }
            ultimaPagina.value = res.data.meta?.last_page || 1;
            totalItems.value = res.data.meta?.total || items.value.length;
        }
    } catch (e) {
        console.error('Error al cargar biblioteca de medios:', e);
    } finally {
        cargando.value = false;
    }
}

function cargarMas() {
    if (pagina.value < ultimaPagina.value) {
        pagina.value++;
        cargarBiblioteca(false);
    }
}

let debounceTimer = null;
function buscarConDebounce() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => cargarBiblioteca(true), 400);
}

watch([filtroTipo, filtroColeccion], () => cargarBiblioteca(true));

// ─── Selección ────────────────────────────────────────
function previewMedio(medio) {
    // Cancelar edición de nombre si estaba activa
    editandoNombre.value = false;
    medioPreview.value = medio;
}

function confirmarSeleccion() {
    if (!medioPreview.value) return;
    emit('seleccionar', {
        url: medioPreview.value.url,
        alt_text: medioPreview.value.alt_text || '',
        title: medioPreview.value.title || '',
    });
    emit('update:visible', false);
    medioPreview.value = null;
}

function seleccionRapida(medio) {
    emit('seleccionar', {
        url: medio.url,
        alt_text: medio.alt_text || '',
        title: medio.title || '',
    });
    emit('update:visible', false);
}

// ─── Subida directa desde el modal ───────────────────
async function subirArchivo(archivos) {
    if (!archivos || !archivos.length) return;
    cargandoSubida.value = true;

    for (const file of archivos) {
        porcentajeSubida.value = 0;
        progresoNombreArchivo.value = file.name;
        progresoEstadoText.value = 'Iniciando subida...';

        const esVideo = file.type.startsWith('video/');
        const formData = new FormData();
        if (esVideo) {
            formData.append('video', file);
        } else {
            formData.append('image', file);
        }

        // Adjuntar colección si se ha seleccionado una distinta de 'none'
        if (coleccionSubida.value && coleccionSubida.value !== 'none') {
            formData.append('collection', coleccionSubida.value);
        }

        try {
            const res = await axios.post(route('media.upload'), formData, {
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        porcentajeSubida.value = percent;
                        if (percent < 100) {
                            progresoEstadoText.value = `Subiendo archivo... (${percent}%)`;
                        } else {
                            progresoEstadoText.value = esVideo
                                ? 'Procesando y optimizando vídeo con FFmpeg...'
                                : 'Procesando y optimizando imagen (WebP / AVIF)...';
                        }
                    }
                }
            });

            if (res.data?.success) {
                toast.add({ severity: 'success', summary: 'Subido', detail: `${file.name} subido y optimizado correctamente.`, life: 3000 });
                await cargarBiblioteca(true);
                pestana.value = 'biblioteca';
            }
        } catch (err) {
            const msg = err.response?.data?.message || 'Error al subir';
            toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 5000 });
        }
    }

    cargandoSubida.value = false;
    porcentajeSubida.value = 0;
    progresoNombreArchivo.value = '';
    progresoEstadoText.value = '';
}

function onFileUpload(e) {
    subirArchivo(e.target.files);
    if (e.target) e.target.value = '';
}

function onDrop(e) {
    e.preventDefault();
    dragActivo.value = false;
    subirArchivo(e.dataTransfer?.files);
}

// ─── Eliminación ─────────────────────────────────────
function eliminarMedioDesdeModal() {
    if (!medioPreview.value) return;
    confirm.require({
        message: `¿Eliminar "${medioPreview.value.filename}" definitivamente? Esta acción no se puede deshacer.`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: async () => {
            try {
                const res = await axios.delete(route('media.destroy', medioPreview.value.id));
                if (res.data?.success) {
                    toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Archivo eliminado.', life: 3000 });
                    medioPreview.value = null;
                    await cargarBiblioteca(true);
                }
            } catch (err) {
                console.error('Error al eliminar medio:', err);
                const msg = err.response?.data?.message || 'No se pudo eliminar.';
                toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 5000 });
            }
        },
    });
}

// ─── Renombrado ──────────────────────────────────────
function iniciarRenombrado() {
    if (!medioPreview.value) return;
    const sinExt = medioPreview.value.filename.replace(/\.[^.]+$/, '');
    nuevoNombre.value = sinExt;
    editandoNombre.value = true;
}

async function renombrarArchivo() {
    if (!medioPreview.value || !nuevoNombre.value.trim()) {
        editandoNombre.value = false;
        return;
    }
    const nombreFinal = nuevoNombre.value.trim() + '.' + medioPreview.value.extension.toLowerCase();
    if (nombreFinal === medioPreview.value.filename) {
        editandoNombre.value = false;
        return;
    }
    try {
        const res = await axios.put(route('media.update', medioPreview.value.id), { filename: nombreFinal });
        if (res.data?.success && res.data?.media) {
            Object.assign(medioPreview.value, res.data.media);
            toast.add({ severity: 'success', summary: 'Renombrado', detail: `Archivo renombrado a "${res.data.media.filename}"`, life: 3000 });
            await cargarBiblioteca(true);
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'No se pudo renombrar.';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 5000 });
    } finally {
        editandoNombre.value = false;
    }
}

// ─── Guardar metadato inline ─────────────────────────
async function guardarMetaInline(campo, valor) {
    if (!medioPreview.value) return;
    try {
        const res = await axios.put(route('media.update', medioPreview.value.id), {
            [campo]: valor || null,
        });
        if (res.data?.success) {
            if (res.data.media) {
                Object.assign(medioPreview.value, res.data.media);
                const idx = items.value.findIndex(m => m.id === medioPreview.value.id);
                if (idx !== -1) {
                    Object.assign(items.value[idx], res.data.media);
                }
            }
            if (res.data.colecciones) {
                colecciones.value = res.data.colecciones;
                if (filtroColeccion.value && !res.data.colecciones.includes(filtroColeccion.value)) {
                    filtroColeccion.value = '';
                    cargarBiblioteca(true);
                }
            }
        }
    } catch (err) {
        // Silencioso
    }
}

// ─── Badge de formato ────────────────────────────────
function getBadgeClass(ext) {
    const map = {
        JPG: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
        PNG: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        SVG: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400',
        WEBP: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
        AVIF: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400',
        GIF: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-400',
        MP4: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
        ICO: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
    };
    return map[ext] || 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
}

// ─── Cargar al abrir ──────────────────────────────────
watch(() => props.visible, (val) => {
    if (val) {
        medioPreview.value = null;
        editandoNombre.value = false;
        filtroTipo.value = props.filtroTipoInicial || '';
        filtroColeccion.value = '';
        cargarBiblioteca(true);
    }
});
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="(val) => emit('update:visible', val)"
        modal
        header="Biblioteca de Medios"
        :style="{ width: '65rem' }"
        :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
        :contentStyle="{ padding: 0 }"
    >
        <!-- Pestañas -->
        <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 px-5 pt-4 pb-3">
            <button
                type="button"
                :class="[
                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 cursor-pointer',
                    pestana === 'biblioteca'
                        ? 'bg-emerald-500 text-white shadow-xs'
                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                ]"
                @click="pestana = 'biblioteca'"
            >
                <i class="pi pi-images text-xs" />
                Biblioteca
                <span v-if="totalItems" class="text-[10px] px-1.5 py-0.5 rounded-full font-mono font-bold"
                    :class="pestana === 'biblioteca' ? 'bg-emerald-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700'"
                >
                    {{ totalItems }}
                </span>
            </button>
            <button
                type="button"
                :class="[
                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 cursor-pointer',
                    pestana === 'subir'
                        ? 'bg-emerald-500 text-white shadow-xs'
                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                ]"
                @click="pestana = 'subir'"
            >
                <i class="pi pi-upload text-xs" />
                Subir Nuevo
            </button>
        </div>

        <!-- ═══ Pestaña: Biblioteca ═══ -->
        <div v-if="pestana === 'biblioteca'" class="flex" style="height: 520px;">
            <!-- Grid de thumbnails -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Buscador y filtros por tipo y colección/etiqueta -->
                <div class="p-3 border-b border-zinc-100 dark:border-zinc-800 space-y-2 bg-white dark:bg-zinc-950">
                    <div class="flex items-center gap-2">
                        <IconField class="flex-1 relative">
                            <InputIcon class="pi pi-search" />
                            <InputText
                                v-model="busqueda"
                                placeholder="Buscar por nombre..."
                                class="w-full text-xs pr-7"
                                @input="buscarConDebounce"
                            />
                            <button
                                v-if="busqueda"
                                type="button"
                                @click="limpiarBusqueda"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                                title="Borrar búsqueda"
                            >
                                <i class="pi pi-times-circle text-xs" />
                            </button>
                        </IconField>
                        <Select
                            v-model="tipoSeleccionado"
                            :options="opcionesTipo"
                            optionLabel="label"
                            optionValue="value"
                            class="text-xs w-36"
                        />
                        <Select
                            v-model="coleccionSeleccionada"
                            :options="opcionesColeccion"
                            optionLabel="label"
                            optionValue="value"
                            class="text-xs w-44"
                        />
                        <Button
                            v-if="totalFiltrosActivos > 0"
                            icon="pi pi-filter-slash"
                            :label="`Limpiar (${totalFiltrosActivos})`"
                            severity="secondary"
                            variant="outlined"
                            size="small"
                            class="text-xs shrink-0"
                            v-tooltip.top="'Restablecer filtros'"
                            @click="limpiarFiltros"
                        />
                    </div>

                    <!-- Barra de píldoras / chips rápidos de colección -->
                    <div v-if="colecciones && colecciones.length > 0" class="flex items-center gap-1.5 overflow-x-auto pb-0.5 scrollbar-none pt-0.5 border-t border-zinc-100 dark:border-zinc-800/60">
                        <span class="text-[9px] uppercase font-bold tracking-wider text-zinc-400 shrink-0 mr-1 flex items-center gap-1">
                            <i class="pi pi-folder text-[9px]" />
                            Colecciones:
                        </span>
                        <button
                            type="button"
                            @click="filtroColeccion = ''"
                            :class="[
                                'px-2 py-0.5 text-[10px] rounded-full font-medium transition-all shrink-0 cursor-pointer',
                                (!filtroColeccion || filtroColeccion === 'all')
                                    ? 'bg-emerald-500 text-white shadow-xs font-semibold'
                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                            ]"
                        >
                            Todas
                        </button>
                        <button
                            v-for="col in colecciones"
                            :key="col"
                            type="button"
                            @click="filtroColeccion = (filtroColeccion === col ? '' : col)"
                            :class="[
                                'px-2 py-0.5 text-[10px] rounded-full font-medium transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                filtroColeccion === col
                                    ? 'bg-emerald-500 text-white shadow-xs font-semibold'
                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                            ]"
                        >
                            <span>{{ col }}</span>
                        </button>
                    </div>
                </div>

                <!-- Cargando -->
                <div v-if="cargando && !items.length" class="flex-1 flex items-center justify-center">
                    <div class="text-center space-y-2">
                        <i class="pi pi-spin pi-spinner text-2xl text-emerald-500" />
                        <p class="text-xs text-zinc-400">Cargando biblioteca...</p>
                    </div>
                </div>

                <!-- Vacío -->
                <div v-else-if="!items.length" class="flex-1 flex items-center justify-center">
                    <div class="text-center space-y-2">
                        <i class="pi pi-images text-3xl text-zinc-400" />
                        <p class="text-xs text-zinc-500 font-bold">No se encontraron archivos</p>
                    </div>
                </div>

                <!-- Grid -->
                <div v-else class="flex-1 overflow-y-auto p-3.5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                        <div
                            v-for="medio in items"
                            :key="medio.id"
                            @click="previewMedio(medio)"
                            @dblclick="seleccionRapida(medio)"
                            :class="[
                                'group relative rounded-xl border overflow-hidden cursor-pointer transition-all flex flex-col bg-white dark:bg-zinc-900',
                                medioPreview?.id === medio.id
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/30 shadow-md'
                                    : 'border-zinc-200 dark:border-zinc-800 hover:border-emerald-400 dark:hover:border-emerald-500 hover:shadow-sm',
                            ]"
                        >
                            <!-- Miniatura (Imagen o Fotograma Inicial de Vídeo) -->
                            <div class="aspect-4/3 bg-zinc-100 dark:bg-zinc-950 flex items-center justify-center overflow-hidden relative">
                                <!-- Badge de Colección -->
                                <span
                                    v-if="medio.collection"
                                    class="absolute top-1.5 left-1.5 z-10 text-[8px] font-medium px-1.5 py-0.5 rounded bg-zinc-900/75 backdrop-blur-md text-zinc-200 flex items-center gap-1 max-w-[65%] truncate shadow-xs"
                                    :title="'Colección: ' + medio.collection"
                                >
                                    <i class="pi pi-folder text-[7px] text-emerald-400 shrink-0" />
                                    <span class="truncate">{{ medio.collection }}</span>
                                </span>

                                <img
                                    v-if="medio.is_image"
                                    :src="medio.thumbnail_url"
                                    :alt="medio.alt_text || medio.filename"
                                    class="w-full h-full object-contain p-1.5 group-hover:scale-105 transition-transform duration-200"
                                    loading="lazy"
                                />
                                <div v-else class="relative w-full h-full flex items-center justify-center bg-zinc-950">
                                    <video
                                        :src="medio.url + '#t=0.001'"
                                        preload="metadata"
                                        muted
                                        playsinline
                                        class="w-full h-full object-cover opacity-85 group-hover:opacity-100 transition-opacity"
                                    />
                                    <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                                        <div class="w-8 h-8 rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xs flex items-center justify-center text-zinc-900 dark:text-emerald-400 shadow-md group-hover:scale-110 transition-transform">
                                            <i class="pi pi-play text-xs ml-0.5" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Badge de Extensión -->
                                <span :class="['absolute top-1.5 right-1.5 text-[8px] font-bold font-mono px-1.5 py-0.5 rounded shadow-xs', getBadgeClass(medio.extension)]">
                                    {{ medio.extension }}
                                </span>
                            </div>

                            <!-- Información y Nombre de Archivo -->
                            <div class="p-2 border-t border-zinc-100 dark:border-zinc-800 flex flex-col justify-between flex-1 bg-zinc-50/50 dark:bg-zinc-900/50">
                                <p class="text-[11px] font-semibold text-zinc-800 dark:text-zinc-200 truncate leading-tight" :title="medio.filename">
                                    {{ medio.filename }}
                                </p>
                                <div class="flex items-center justify-between text-[10px] text-zinc-400 mt-1">
                                    <span>{{ medio.formatted_size }}</span>
                                    <span v-if="medio.dimensions" class="text-[9px] font-mono">{{ medio.dimensions }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón cargar más -->
                    <div v-if="pagina < ultimaPagina" class="text-center mt-4">
                        <Button
                            label="Cargar más"
                            icon="pi pi-angle-down"
                            severity="secondary"
                            variant="outlined"
                            size="small"
                            :loading="cargando"
                            @click="cargarMas"
                        />
                    </div>
                </div>
            </div>

            <!-- ═══ Panel lateral inspector ═══ -->
            <div v-if="medioPreview" class="w-72 border-l border-zinc-200 dark:border-zinc-800 flex flex-col bg-zinc-50 dark:bg-zinc-900/50">
                <!-- Cabecera con título, paginación y navegación anterior/siguiente -->
                <div class="flex items-center justify-between px-3 py-2 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Detalles</span>
                        <span v-if="indicePreview >= 0" class="text-[9px] text-zinc-400 font-mono">
                            ({{ indicePreview + 1 }}/{{ items.length }})
                        </span>
                    </div>
                    <div class="flex items-center gap-0.5">
                        <button
                            type="button"
                            :disabled="!hayAnterior"
                            @click="irAnterior"
                            class="w-6 h-6 flex items-center justify-center rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-800 disabled:opacity-25 disabled:pointer-events-none transition-all cursor-pointer"
                            title="Anterior (Flecha Izquierda)"
                        >
                            <i class="pi pi-chevron-left text-[10px]" />
                        </button>
                        <button
                            type="button"
                            :disabled="!haySiguiente"
                            @click="irSiguiente"
                            class="w-6 h-6 flex items-center justify-center rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-800 disabled:opacity-25 disabled:pointer-events-none transition-all cursor-pointer"
                            title="Siguiente (Flecha Derecha)"
                        >
                            <i class="pi pi-chevron-right text-[10px]" />
                        </button>
                        <div class="w-px h-3.5 bg-zinc-200 dark:bg-zinc-800 mx-0.5" />
                        <button
                            type="button"
                            @click="medioPreview = null"
                            class="w-6 h-6 flex items-center justify-center rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-all cursor-pointer"
                            title="Cerrar panel (Escape)"
                        >
                            <i class="pi pi-times text-xs" />
                        </button>
                    </div>
                </div>

                <!-- Contenido scrollable -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Previsualización -->
                    <div class="p-3 flex items-center justify-center min-h-[140px] max-h-[180px]">
                        <img
                            v-if="medioPreview.is_image"
                            :src="medioPreview.url"
                            :alt="medioPreview.alt_text"
                            class="max-h-[170px] max-w-full object-contain rounded-lg shadow-sm"
                        />
                        <video v-else-if="medioPreview.is_video" :src="medioPreview.url" controls class="max-h-[170px] max-w-full rounded-lg" />
                    </div>

                    <!-- Nombre editable -->
                    <div class="px-3 py-2 border-t border-zinc-200 dark:border-zinc-800">
                        <div v-if="editandoNombre" class="flex items-center gap-1">
                            <InputText
                                v-model="nuevoNombre"
                                class="flex-1 text-[10px] font-bold"
                                @keyup.enter="renombrarArchivo"
                                @keyup.escape="editandoNombre = false"
                                autofocus
                            />
                            <span class="text-[9px] text-zinc-400 font-mono">.{{ medioPreview.extension.toLowerCase() }}</span>
                            <button type="button" @click="renombrarArchivo" class="w-5 h-5 flex items-center justify-center rounded text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer" title="Guardar">
                                <i class="pi pi-check text-[10px]" />
                            </button>
                            <button type="button" @click="editandoNombre = false" class="w-5 h-5 flex items-center justify-center rounded text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800 cursor-pointer" title="Cancelar">
                                <i class="pi pi-times text-[10px]" />
                            </button>
                        </div>
                        <div v-else class="flex items-center gap-1 group">
                            <p class="text-[10px] text-zinc-700 dark:text-zinc-300 font-bold break-all flex-1 leading-snug">{{ medioPreview.filename }}</p>
                            <button
                                type="button"
                                @click="iniciarRenombrado"
                                class="w-5 h-5 flex items-center justify-center rounded text-zinc-400 opacity-0 group-hover:opacity-100 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-all cursor-pointer shrink-0"
                                title="Renombrar"
                            >
                                <i class="pi pi-pencil text-[9px]" />
                            </button>
                        </div>
                        <div class="flex items-center gap-2 text-[9px] text-zinc-400 mt-1">
                            <span>{{ medioPreview.formatted_size }}</span>
                            <span v-if="medioPreview.dimensions">· {{ medioPreview.dimensions }}</span>
                            <span>· {{ medioPreview.extension }}</span>
                        </div>
                    </div>

                    <!-- Metadatos SEO editables -->
                    <div class="px-3 py-2 space-y-2 border-t border-zinc-200 dark:border-zinc-800">
                        <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-widest">SEO</p>
                        <div>
                            <label class="block text-[9px] font-semibold text-zinc-500 mb-0.5">Alt text</label>
                            <InputText
                                v-model="medioPreview.alt_text"
                                placeholder="Texto alternativo..."
                                class="w-full text-[10px]"
                                @change="guardarMetaInline('alt_text', medioPreview.alt_text)"
                            />
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-zinc-500 mb-0.5">Título</label>
                            <InputText
                                v-model="medioPreview.title"
                                placeholder="Título descriptivo..."
                                class="w-full text-[10px]"
                                @change="guardarMetaInline('title', medioPreview.title)"
                            />
                        </div>
                    </div>

                    <!-- Colección editable con selector inteligente -->
                    <div class="px-3 py-2 space-y-1.5 border-t border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center justify-between">
                            <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-widest">Colección</p>
                            <button
                                v-if="!mostrarNuevaColeccionInspector"
                                type="button"
                                @click="mostrarNuevaColeccionInspector = true"
                                class="text-[9px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer flex items-center gap-0.5"
                            >
                                <i class="pi pi-plus text-[8px]" />
                                <span>Nueva</span>
                            </button>
                        </div>

                        <div v-if="!mostrarNuevaColeccionInspector">
                            <Select
                                v-model="coleccionInspectorSeleccionada"
                                :options="opcionesColeccionInspector"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Sin colección (general)"
                                class="w-full text-[10px]"
                            >
                                <template #value="slotProps">
                                    <span class="text-[10px] font-medium text-zinc-700 dark:text-zinc-200 flex items-center gap-1">
                                        <i class="pi pi-folder text-[9px] text-emerald-500" />
                                        {{ opcionesColeccionInspector.find(o => o.value === slotProps.value)?.label || 'Sin colección (general)' }}
                                    </span>
                                </template>
                            </Select>
                        </div>
                        <div v-else class="flex items-center gap-1">
                            <InputText
                                v-model="nuevaColeccionInspectorInput"
                                placeholder="Nombre colección..."
                                class="flex-1 text-[10px]"
                                @keyup.enter="guardarNuevaColeccionInspector"
                                @keyup.escape="mostrarNuevaColeccionInspector = false"
                                autofocus
                            />
                            <button
                                type="button"
                                @click="guardarNuevaColeccionInspector"
                                class="w-5 h-5 flex items-center justify-center rounded text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer"
                                title="Guardar"
                            >
                                <i class="pi pi-check text-[10px]" />
                            </button>
                            <button
                                type="button"
                                @click="mostrarNuevaColeccionInspector = false; nuevaColeccionInspectorInput = ''"
                                class="w-5 h-5 flex items-center justify-center rounded text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800 cursor-pointer"
                                title="Cancelar"
                            >
                                <i class="pi pi-times text-[10px]" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Acciones fijas abajo -->
                <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 space-y-1.5">
                    <Button
                        icon="pi pi-check"
                        label="Usar Este Archivo"
                        severity="success"
                        size="small"
                        class="w-full"
                        @click="confirmarSeleccion"
                    />
                    <Button
                        icon="pi pi-trash"
                        label="Eliminar"
                        severity="danger"
                        variant="outlined"
                        size="small"
                        class="w-full"
                        @click="eliminarMedioDesdeModal"
                    />
                </div>
            </div>
        </div>

        <!-- ═══ Pestaña: Subir Nuevo ═══ -->
        <div v-else-if="pestana === 'subir'" class="flex flex-col" style="height: 520px;">
            <!-- Selector de colección al subir -->
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider shrink-0">
                        <i class="pi pi-folder text-[10px]" />
                        <span>Colección</span>
                    </div>
                    <div v-if="!mostrarNuevaColeccion" class="flex items-center gap-2 flex-1">
                        <Select
                            v-model="coleccionSubida"
                            :options="opcionesColeccionSubida"
                            optionLabel="label"
                            optionValue="value"
                            class="text-xs flex-1"
                            placeholder="Sin colección"
                        />
                        <button
                            type="button"
                            @click="mostrarNuevaColeccion = true"
                            class="px-2.5 py-1.5 bg-zinc-100 dark:bg-zinc-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-zinc-200 dark:border-zinc-700 hover:border-emerald-400 dark:hover:border-emerald-600 text-zinc-600 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer shrink-0"
                            title="Crear nueva colección"
                        >
                            <i class="pi pi-plus text-[9px]" />
                            <span>Nueva</span>
                        </button>
                    </div>
                    <div v-else class="flex items-center gap-1.5 flex-1">
                        <InputText
                            v-model="nuevaColeccionInput"
                            placeholder="Nombre de la nueva colección..."
                            class="flex-1 text-xs"
                            @keyup.enter="agregarNuevaColeccion"
                            @keyup.escape="mostrarNuevaColeccion = false"
                            autofocus
                        />
                        <button
                            type="button"
                            @click="agregarNuevaColeccion"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors cursor-pointer"
                            title="Añadir colección"
                        >
                            <i class="pi pi-check text-xs" />
                        </button>
                        <button
                            type="button"
                            @click="mostrarNuevaColeccion = false; nuevaColeccionInput = ''"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                            title="Cancelar"
                        >
                            <i class="pi pi-times text-xs" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Zona de Drag & Drop -->
            <div class="flex-1 p-6">
                <div
                    @drop="onDrop"
                    @dragover.prevent="dragActivo = true"
                    @dragleave="dragActivo = false"
                    :class="[
                        'h-full flex flex-col items-center justify-center rounded-2xl border-2 border-dashed transition-all cursor-pointer',
                        dragActivo
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                            : 'border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 hover:border-emerald-400',
                    ]"
                    @click="inputUploadRef?.click()"
                >
                    <!-- Estado de Subida con Barra de Progreso y Porcentaje -->
                    <div v-if="cargandoSubida" class="text-center space-y-5 max-w-sm mx-auto px-4 py-6">
                        <!-- Icono Núcleo Animado con Aura Pulsante -->
                        <div class="relative flex items-center justify-center mx-auto">
                            <div class="absolute w-20 h-20 rounded-full border border-emerald-500/30 animate-ping" />
                            <div class="relative w-16 h-16 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center">
                                <i class="pi pi-spin pi-spinner text-2xl text-emerald-500" />
                            </div>
                        </div>

                        <!-- Nombre del Archivo y Estado -->
                        <div class="space-y-1">
                            <p class="text-xs font-mono font-semibold text-zinc-500 dark:text-zinc-400 truncate max-w-xs mx-auto">
                                {{ progresoNombreArchivo }}
                            </p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100">
                                {{ progresoEstadoText }}
                            </p>
                        </div>

                        <!-- Barra de Progreso Interactiva + Porcentaje -->
                        <div class="space-y-1.5 w-full">
                            <div class="w-full h-3 bg-zinc-200 dark:bg-zinc-800 rounded-full overflow-hidden relative shadow-inner">
                                <div
                                    class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-500 rounded-full transition-all duration-200 relative overflow-hidden"
                                    :style="{ width: porcentajeSubida + '%' }"
                                >
                                    <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" />
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] font-mono text-zinc-400 px-1">
                                <span>0%</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">{{ porcentajeSubida }}%</span>
                                <span>100%</span>
                            </div>
                        </div>

                        <!-- Colección seleccionada durante la subida -->
                        <div v-if="coleccionSubida" class="flex items-center justify-center gap-1.5 text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">
                            <i class="pi pi-folder text-[10px]" />
                            <span>Colección: {{ coleccionSubida }}</span>
                        </div>
                    </div>

                    <!-- Estado Inicial de Arrastrar y Soltar -->
                    <div v-else class="text-center space-y-3">
                        <i class="text-5xl pi pi-cloud-upload text-zinc-400" />
                        <div>
                            <p class="text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                Arrastra archivos aquí
                            </p>
                            <p class="text-xs text-zinc-500 mt-1">o haz clic para seleccionar</p>
                        </div>
                        <p class="text-[10px] text-zinc-400 max-w-xs">
                            Formatos: JPG, PNG, WebP, AVIF, SVG, GIF, ICO, MP4, WebM · Imágenes máx 30 MB · Vídeos máx 100 MB
                        </p>
                        <!-- Indicador de colección seleccionada -->
                        <div v-if="coleccionSubida" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-full text-[10px] text-emerald-700 dark:text-emerald-300 font-bold">
                            <i class="pi pi-folder text-[9px]" />
                            <span>Se asignará a: {{ coleccionSubida }}</span>
                        </div>
                    </div>
                    <input
                        ref="inputUploadRef"
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp,image/avif,image/gif,image/svg+xml,image/x-icon,.ico,video/mp4,video/webm,video/quicktime"
                        @change="onFileUpload"
                        class="hidden"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>
