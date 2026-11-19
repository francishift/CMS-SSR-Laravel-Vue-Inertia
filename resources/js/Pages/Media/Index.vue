<script setup>
/**
 * Página principal de la Biblioteca de Medios del CMS.
 *
 * Gestión centralizada de archivos multimedia (imágenes y vídeos)
 * con vista de cuadrícula, búsqueda, filtros por tipo/colección,
 * panel lateral inspector, subida drag & drop y eliminación masiva.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';

const props = defineProps({
    medios: Object,
    colecciones: Array,
    filtros: Object,
    totalMedios: Number,
    totalImagenes: Number,
    totalVideos: Number,
});

const toast = useToast();
const confirm = useConfirm();

// ─── Control de densidad de cuadrícula ─────────────────────
const densidadCuadricula = ref('normal'); // 'compact' | 'normal' | 'large'

onMounted(() => {
    const saved = localStorage.getItem('cms_media_grid_density');
    if (['compact', 'normal', 'large'].includes(saved)) {
        densidadCuadricula.value = saved;
    }
});

function cambiarDensidad(val) {
    densidadCuadricula.value = val;
    localStorage.setItem('cms_media_grid_density', val);
}

// ─── Estado de filtros ──────────────────────────────────
const busqueda = ref(props.filtros?.search || '');
const filtroTipo = ref(props.filtros?.type || '');
const filtroColeccion = ref(props.filtros?.collection || '');

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
    return base.concat(
        (props.colecciones || []).map(c => ({ label: c, value: c }))
    );
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
    aplicarFiltros();
}

function limpiarBusqueda() {
    busqueda.value = '';
    aplicarFiltros();
}

let debounceTimer = null;
function buscarConDebounce() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => aplicarFiltros(), 400);
}

function aplicarFiltros() {
    const params = {};
    if (busqueda.value) params.search = busqueda.value;
    if (filtroTipo.value) params.type = filtroTipo.value;
    if (filtroColeccion.value) params.collection = filtroColeccion.value;

    router.get(route('media.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

watch([filtroTipo, filtroColeccion], () => aplicarFiltros());

// Si el usuario llega a una URL con una colección que ya no existe, limpiar filtro automáticamente
onMounted(() => {
    if (filtroColeccion.value && !(props.colecciones || []).includes(filtroColeccion.value)) {
        filtroColeccion.value = '';
    }
});

// ─── Subida de archivos y gestión de colecciones ─────────────
const cargandoSubida = ref(false);
const dragActivo = ref(false);
const dragActivoModal = ref(false);
const mostrarModalSubida = ref(false);
const coleccionSubida = ref('none');
const nuevaColeccionInput = ref('');
const mostrarNuevaColeccion = ref(false);
const porcentajeSubida = ref(0);
const progresoNombreArchivo = ref('');
const progresoEstadoText = ref('');
const inputFileModalRef = ref(null);

const listaColecciones = ref([...(props.colecciones || [])]);
watch(() => props.colecciones, (nuevas) => {
    const lista = nuevas || [];
    listaColecciones.value = [...lista];

    // Blindaje de usabilidad: Si la colección activa en el filtro se eliminó o quedó huérfana,
    // limpiar el filtro de inmediato para mostrar todos los elementos y evitar pantalla vacía.
    if (filtroColeccion.value && !lista.includes(filtroColeccion.value)) {
        filtroColeccion.value = '';
    }
}, { deep: true });

/**
 * Opciones de colección para el selector del modal de subida.
 * Sigue la regla de PrimeVue 4 utilizando 'none' para la opción por defecto.
 */
const opcionesColeccionSubida = computed(() => {
    const base = [{ label: 'Sin colección (general)', value: 'none' }];
    return base.concat(listaColecciones.value.map(c => ({ label: c, value: c })));
});

function abrirModalSubida() {
    coleccionSubida.value = (filtroColeccion.value && filtroColeccion.value !== '') ? filtroColeccion.value : 'none';
    mostrarNuevaColeccion.value = false;
    nuevaColeccionInput.value = '';
    porcentajeSubida.value = 0;
    progresoNombreArchivo.value = '';
    progresoEstadoText.value = '';
    mostrarModalSubida.value = true;
}

function agregarNuevaColeccion() {
    const nombre = nuevaColeccionInput.value.trim();
    if (!nombre) return;
    if (!listaColecciones.value.includes(nombre)) {
        listaColecciones.value.push(nombre);
    }
    coleccionSubida.value = nombre;
    nuevaColeccionInput.value = '';
    mostrarNuevaColeccion.value = false;
}

async function subirArchivos(archivos) {
    if (!archivos || !archivos.length) return;
    cargandoSubida.value = true;
    porcentajeSubida.value = 0;

    let subidos = 0;
    let errores = 0;
    const coleccionDestino = (coleccionSubida.value && coleccionSubida.value !== 'none')
        ? coleccionSubida.value
        : null;

    for (let i = 0; i < archivos.length; i++) {
        const file = archivos[i];
        const esVideo = file.type.startsWith('video/');
        progresoNombreArchivo.value = file.name;
        progresoEstadoText.value = `Iniciando (${i + 1}/${archivos.length})...`;

        const formData = new FormData();
        if (esVideo) {
            formData.append('video', file);
        } else {
            formData.append('image', file);
        }

        if (coleccionDestino) {
            formData.append('collection', coleccionDestino);
        }

        try {
            await axios.post(route('media.upload'), formData, {
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
            subidos++;
        } catch (err) {
            errores++;
            const msg = err.response?.data?.message || 'Error al subir archivo';
            toast.add({ severity: 'error', summary: 'Error', detail: `${file.name}: ${msg}`, life: 5000 });
        }
    }

    cargandoSubida.value = false;
    porcentajeSubida.value = 0;
    progresoNombreArchivo.value = '';
    progresoEstadoText.value = '';

    if (subidos > 0) {
        toast.add({
            severity: 'success',
            summary: 'Archivos Subidos',
            detail: `${subidos} archivo(s) subido(s) correctamente${coleccionDestino ? ` en "${coleccionDestino}"` : ''}.`,
            life: 3000,
        });
        mostrarModalSubida.value = false;
        router.reload({ only: ['medios', 'colecciones', 'totalMedios', 'totalImagenes', 'totalVideos'] });
    }
}

function onDrop(e) {
    e.preventDefault();
    dragActivo.value = false;
    abrirModalSubida();
    subirArchivos(e.dataTransfer?.files);
}

function onDropModal(e) {
    dragActivoModal.value = false;
    subirArchivos(e.dataTransfer?.files);
}

function onDragOver(e) {
    e.preventDefault();
    dragActivo.value = true;
}

function onDragLeave() {
    dragActivo.value = false;
}

function onFileModalSelected(e) {
    subirArchivos(e.target.files);
    if (e.target) e.target.value = '';
}

// ─── Selección y panel inspector ──────────────────────────
const seleccionado = ref(null);
const seleccionMultiple = ref(new Set());
const modoSeleccionMultiple = ref(false);

function seleccionarMedio(medio) {
    if (modoSeleccionMultiple.value) {
        toggleSeleccionMultiple(medio);
    } else {
        seleccionado.value = seleccionado.value?.id === medio.id ? null : medio;
    }
}

function toggleSeleccionMultiple(medio) {
    const set = new Set(seleccionMultiple.value);
    if (set.has(medio.id)) {
        set.delete(medio.id);
    } else {
        set.add(medio.id);
    }
    seleccionMultiple.value = set;
}

function seleccionarTodos() {
    const set = new Set();
    props.medios.data.forEach(m => set.add(m.id));
    seleccionMultiple.value = set;
}

function deseleccionarTodos() {
    seleccionMultiple.value = new Set();
    modoSeleccionMultiple.value = false;
}

// ─── Edición de metadatos ──────────────────────────────────
const editandoMeta = ref(false);
const metaForm = ref({ alt_text: '', title: '', collection: '' });

function abrirEdicion() {
    if (!seleccionado.value) return;
    metaForm.value = {
        alt_text: seleccionado.value.alt_text || '',
        title: seleccionado.value.title || '',
        collection: seleccionado.value.collection || '',
    };
    editandoMeta.value = true;
}

async function guardarMeta() {
    if (!seleccionado.value) return;
    try {
        const res = await axios.put(route('media.update', seleccionado.value.id), metaForm.value);
        if (res.data?.success) {
            toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Metadatos guardados.', life: 3000 });
            editandoMeta.value = false;

            if (res.data.media) {
                Object.assign(seleccionado.value, res.data.media);
                const idx = props.medios?.data?.findIndex(m => m.id === seleccionado.value.id);
                if (idx !== -1 && props.medios?.data) {
                    Object.assign(props.medios.data[idx], res.data.media);
                }
            }

            const coleccionesRestantes = res.data?.colecciones || [];
            if (filtroColeccion.value && !coleccionesRestantes.includes(filtroColeccion.value)) {
                filtroColeccion.value = '';
            } else {
                router.reload({ only: ['medios', 'colecciones'] });
            }
        }
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron guardar los metadatos.', life: 5000 });
    }
}

// ─── Eliminación ──────────────────────────────────────────
function eliminarMedio() {
    if (!seleccionado.value) return;
    confirm.require({
        message: `¿Eliminar "${seleccionado.value.filename}" definitivamente? Esta acción no se puede deshacer.`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: async () => {
            try {
                const res = await axios.delete(route('media.destroy', seleccionado.value.id));
                if (res.data?.success) {
                    toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Archivo eliminado del servidor.', life: 3000 });
                    seleccionado.value = null;

                    const coleccionesRestantes = res.data?.colecciones || [];
                    if (filtroColeccion.value && !coleccionesRestantes.includes(filtroColeccion.value)) {
                        filtroColeccion.value = '';
                    } else {
                        router.reload({ only: ['medios', 'colecciones', 'totalMedios', 'totalImagenes', 'totalVideos'] });
                    }
                }
            } catch (err) {
                console.error('Error al eliminar medio:', err);
                const msg = err.response?.data?.message || 'No se pudo eliminar el archivo.';
                toast.add({ severity: 'error', summary: 'Error al eliminar', detail: msg, life: 5000 });
            }
        },
    });
}

function eliminarSeleccionados() {
    const ids = Array.from(seleccionMultiple.value);
    if (!ids.length) return;
    confirm.require({
        message: `¿Eliminar ${ids.length} archivos definitivamente? Esta acción no se puede deshacer.`,
        header: 'Eliminar selección',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar todos',
        rejectLabel: 'Cancelar',
        accept: async () => {
            try {
                const res = await axios.post(route('media.bulk-delete'), { ids });
                if (res.data?.success) {
                    toast.add({ severity: 'success', summary: 'Eliminados', detail: `${ids.length} archivos eliminados.`, life: 3000 });
                    seleccionMultiple.value = new Set();
                    modoSeleccionMultiple.value = false;

                    const coleccionesRestantes = res.data?.colecciones || [];
                    if (filtroColeccion.value && !coleccionesRestantes.includes(filtroColeccion.value)) {
                        filtroColeccion.value = '';
                    } else {
                        router.reload({ only: ['medios', 'colecciones', 'totalMedios', 'totalImagenes', 'totalVideos'] });
                    }
                }
            } catch (err) {
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron eliminar.', life: 5000 });
            }
        },
    });
}

// ─── Copiar URL al portapapeles ─────────────────────────
const urlCopiada = ref(false);
let timerCopiada = null;

function copiarUrl() {
    if (!seleccionado.value?.url) return;
    navigator.clipboard.writeText(seleccionado.value.url);
    urlCopiada.value = true;
    toast.add({ severity: 'info', summary: 'Copiado', detail: 'URL copiada al portapapeles.', life: 2000 });
    clearTimeout(timerCopiada);
    timerCopiada = setTimeout(() => {
        urlCopiada.value = false;
    }, 2000);
}

// ─── Navegación entre medios en el Inspector ───────────
const indiceSeleccionado = computed(() => {
    if (!seleccionado.value || !props.medios?.data) return -1;
    return props.medios.data.findIndex(m => m.id === seleccionado.value.id);
});

const hayAnterior = computed(() => indiceSeleccionado.value > 0);
const haySiguiente = computed(() => {
    if (!props.medios?.data) return false;
    return indiceSeleccionado.value >= 0 && indiceSeleccionado.value < props.medios.data.length - 1;
});

function irAnterior() {
    if (hayAnterior.value && props.medios?.data) {
        seleccionado.value = props.medios.data[indiceSeleccionado.value - 1];
    }
}

function irSiguiente() {
    if (haySiguiente.value && props.medios?.data) {
        seleccionado.value = props.medios.data[indiceSeleccionado.value + 1];
    }
}

// ─── Selector inteligente de colección en el Inspector ─
const mostrarNuevaColeccionInspector = ref(false);
const nuevaColeccionInspectorInput = ref('');

const opcionesColeccionInspector = computed(() => {
    const base = [
        { label: 'Sin colección (general)', value: 'none' },
    ];
    const colList = listaColecciones.value.map(c => ({ label: c, value: c }));
    return base.concat(colList, [{ label: '+ Nueva colección...', value: '__new__' }]);
});

const coleccionInspectorSeleccionada = computed({
    get: () => seleccionado.value?.collection || 'none',
    set: (val) => {
        if (val === '__new__') {
            mostrarNuevaColeccionInspector.value = true;
            nuevaColeccionInspectorInput.value = '';
        } else {
            const valorFinal = (val === 'none' || !val) ? null : val;
            if (seleccionado.value) {
                seleccionado.value.collection = valorFinal;
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
    if (!listaColecciones.value.includes(nombre)) {
        listaColecciones.value.push(nombre);
    }
    if (seleccionado.value) {
        seleccionado.value.collection = nombre;
    }
    guardarMetaInline('collection', nombre);
    nuevaColeccionInspectorInput.value = '';
    mostrarNuevaColeccionInspector.value = false;
}

// ─── Atajos de teclado para navegación y selección ──────
function onKeyDown(e) {
    // Si el foco está en un input, textarea o contenido editable, no capturar atajos
    if (['INPUT', 'TEXTAREA'].includes(e.target?.tagName) || e.target?.isContentEditable) {
        return;
    }

    if (e.key === 'Escape') {
        if (editandoNombre.value) {
            cancelarRenombrado();
        } else if (mostrarNuevaColeccionInspector.value) {
            mostrarNuevaColeccionInspector.value = false;
        } else if (seleccionado.value) {
            seleccionado.value = null;
        } else if (modoSeleccionMultiple.value) {
            deseleccionarTodos();
        }
    } else if (e.key === 'ArrowLeft' && seleccionado.value) {
        irAnterior();
    } else if (e.key === 'ArrowRight' && seleccionado.value) {
        irSiguiente();
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a' && modoSeleccionMultiple.value) {
        e.preventDefault();
        seleccionarTodos();
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
    clearTimeout(timerCopiada);
});

// ─── Formato de badge para extensión ────────────────────
function getBadgeClass(ext) {
    const map = {
        JPG: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
        PNG: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        SVG: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400',
        WEBP: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
        AVIF: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400',
        GIF: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-400',
        MP4: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
        WEBM: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
        MOV: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400',
    };
    return map[ext] || 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
}

/**
 * Guardar un metadato SEO individual (inline) al cambiar el valor en el inspector.
 */
async function guardarMetaInline(campo, valor) {
    if (!seleccionado.value) return;
    try {
        const res = await axios.put(route('media.update', seleccionado.value.id), {
            [campo]: valor || null,
        });
        if (res.data?.success) {
            if (res.data.media) {
                Object.assign(seleccionado.value, res.data.media);
                const idx = props.medios?.data?.findIndex(m => m.id === seleccionado.value.id);
                if (idx !== -1 && props.medios?.data) {
                    Object.assign(props.medios.data[idx], res.data.media);
                }
            }

            if (campo === 'collection') {
                const coleccionesRestantes = res.data?.colecciones || [];
                // Si la colección que teníamos activa en el filtro ya no existe en el sistema
                if (filtroColeccion.value && !coleccionesRestantes.includes(filtroColeccion.value)) {
                    filtroColeccion.value = '';
                    return;
                }
                // Si teníamos un filtro activo y el archivo ya no coincide con el filtro
                if (filtroColeccion.value && (valor || '') !== filtroColeccion.value) {
                    router.reload({ only: ['medios', 'colecciones'] });
                    return;
                }
            }

            if (res.data?.colecciones) {
                router.reload({ only: ['colecciones'] });
            }
        }
    } catch (err) {
        // Silencioso — el error se notifica si es grave
    }
}

// ─── Renombrado de archivo ────────────────────────────
const editandoNombre = ref(false);
const nuevoNombre = ref('');

function iniciarRenombrado() {
    if (!seleccionado.value) return;
    // Quitamos la extensión para que edite solo el nombre base
    const sinExt = seleccionado.value.filename.replace(/\.[^.]+$/, '');
    nuevoNombre.value = sinExt;
    editandoNombre.value = true;
}

async function renombrarArchivo() {
    if (!seleccionado.value || !nuevoNombre.value.trim()) {
        editandoNombre.value = false;
        return;
    }

    const nombreFinal = nuevoNombre.value.trim() + '.' + seleccionado.value.extension.toLowerCase();

    // Si no cambió, cancelar
    if (nombreFinal === seleccionado.value.filename) {
        editandoNombre.value = false;
        return;
    }

    try {
        const res = await axios.put(route('media.update', seleccionado.value.id), {
            filename: nombreFinal,
        });

        if (res.data?.success && res.data?.media) {
            // Actualizar datos del seleccionado con la respuesta del servidor
            Object.assign(seleccionado.value, res.data.media);
            toast.add({ severity: 'success', summary: 'Renombrado', detail: `Archivo renombrado a "${res.data.media.filename}"`, life: 3000 });
            // Recargar la lista para reflejar el cambio
            router.reload({ only: ['medios'] });
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'No se pudo renombrar el archivo.';
        toast.add({ severity: 'error', summary: 'Error al renombrar', detail: msg, life: 5000 });
    } finally {
        editandoNombre.value = false;
    }
}

function cancelarRenombrado() {
    editandoNombre.value = false;
}
</script>

<template>
    <Head title="Biblioteca de Medios" />
    <AuthenticatedLayout>
        <!-- Header estándar del layout con estadísticas de medios -->
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Medios
                    </h1>
                    <!-- Resumen / Estadísticas de Medios -->
                    <div class="hidden sm:flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                        <span class="px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-semibold text-[11px]">
                            {{ totalMedios || (medios?.total || 0) }} archivos
                        </span>
                        <span v-if="totalImagenes !== undefined" class="flex items-center gap-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                            <i class="pi pi-image text-[10px] text-emerald-500" />
                            {{ totalImagenes }}
                        </span>
                        <span v-if="totalVideos !== undefined" class="flex items-center gap-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                            <i class="pi pi-video text-[10px] text-red-500" />
                            {{ totalVideos }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    <Button
                        v-if="modoSeleccionMultiple && seleccionMultiple.size > 0"
                        icon="pi pi-trash"
                        :label="`(${seleccionMultiple.size})`"
                        severity="danger"
                        size="small"
                        @click="eliminarSeleccionados"
                    />
                    <Button
                        :icon="modoSeleccionMultiple ? 'pi pi-times' : 'pi pi-check-square'"
                        :label="modoSeleccionMultiple ? 'Cancelar' : 'Seleccionar'"
                        :aria-label="modoSeleccionMultiple ? 'Cancelar selección' : 'Seleccionar archivos'"
                        :pt="{ label: { class: 'hidden sm:inline' } }"
                        severity="secondary"
                        variant="outlined"
                        size="small"
                        @click="modoSeleccionMultiple = !modoSeleccionMultiple; deseleccionarTodos()"
                    />
                    <Button
                        icon="pi pi-upload"
                        label="Subir"
                        severity="success"
                        size="small"
                        :loading="cargandoSubida"
                        @click="abrirModalSubida"
                    />
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-8rem)]">
            <!-- ══════════════════════════════════════════
                 COLUMNA PRINCIPAL — Grid de Medios
            ═══════════════════════════════════════════ -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Barra de filtros -->
                <div class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 sm:px-6 py-3 space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-1 items-center gap-2.5 min-w-[260px] max-w-2xl">
                            <!-- Buscador con botón de borrado rápido -->
                            <IconField class="flex-1 relative">
                                <InputIcon class="pi pi-search" />
                                <InputText
                                    v-model="busqueda"
                                    placeholder="Buscar por nombre de archivo..."
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

                            <!-- Selector de tipo -->
                            <Select
                                v-model="tipoSeleccionado"
                                :options="opcionesTipo"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todos los tipos"
                                class="text-xs w-40"
                            >
                                <template #value="slotProps">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-200">
                                        {{ opcionesTipo.find(o => o.value === slotProps.value)?.label || 'Todos los tipos' }}
                                    </span>
                                </template>
                            </Select>

                            <!-- Selector de colección (dropdown) -->
                            <Select
                                v-model="coleccionSeleccionada"
                                :options="opcionesColeccion"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todas las colecciones"
                                class="text-xs w-48"
                            >
                                <template #value="slotProps">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-200">
                                        {{ opcionesColeccion.find(o => o.value === slotProps.value)?.label || 'Todas las colecciones' }}
                                    </span>
                                </template>
                            </Select>

                            <!-- Botón limpiar filtros cuando hay alguno activo -->
                            <Button
                                v-if="totalFiltrosActivos > 0"
                                icon="pi pi-filter-slash"
                                :label="`Limpiar (${totalFiltrosActivos})`"
                                severity="secondary"
                                variant="outlined"
                                size="small"
                                class="text-xs shrink-0"
                                v-tooltip.top="'Restablecer todos los filtros'"
                                @click="limpiarFiltros"
                            />
                        </div>

                        <!-- Selector de densidad de cuadrícula -->
                        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-900 p-0.5 rounded-lg border border-zinc-200 dark:border-zinc-800 shrink-0">
                            <button
                                type="button"
                                @click="cambiarDensidad('large')"
                                :class="[
                                    'w-7 h-7 flex items-center justify-center rounded-md text-xs transition-all cursor-pointer',
                                    densidadCuadricula === 'large'
                                        ? 'bg-white dark:bg-zinc-800 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold'
                                        : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'
                                ]"
                                title="Vista detallada (tarjetas grandes)"
                            >
                                <i class="pi pi-th-large text-xs" />
                            </button>
                            <button
                                type="button"
                                @click="cambiarDensidad('normal')"
                                :class="[
                                    'w-7 h-7 flex items-center justify-center rounded-md text-xs transition-all cursor-pointer',
                                    densidadCuadricula === 'normal'
                                        ? 'bg-white dark:bg-zinc-800 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold'
                                        : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'
                                ]"
                                title="Vista normal"
                            >
                                <i class="pi pi-table text-xs" />
                            </button>
                            <button
                                type="button"
                                @click="cambiarDensidad('compact')"
                                :class="[
                                    'w-7 h-7 flex items-center justify-center rounded-md text-xs transition-all cursor-pointer',
                                    densidadCuadricula === 'compact'
                                        ? 'bg-white dark:bg-zinc-800 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold'
                                        : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'
                                ]"
                                title="Vista compacta (alta densidad)"
                            >
                                <i class="pi pi-objects-column text-xs" />
                            </button>
                        </div>
                    </div>

                    <!-- Barra de píldoras / chips de colecciones rápidas -->
                    <div v-if="props.colecciones && props.colecciones.length > 0" class="flex items-center gap-1.5 overflow-x-auto pb-0.5 scrollbar-none pt-1 border-t border-zinc-100 dark:border-zinc-800/60">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400 shrink-0 mr-1 flex items-center gap-1">
                            <i class="pi pi-folder text-[10px]" />
                            Colecciones:
                        </span>
                        <button
                            type="button"
                            @click="filtroColeccion = ''"
                            :class="[
                                'px-2.5 py-0.5 text-[11px] rounded-full font-medium transition-all shrink-0 cursor-pointer',
                                (!filtroColeccion || filtroColeccion === 'all')
                                    ? 'bg-emerald-500 text-white shadow-xs font-semibold'
                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                            ]"
                        >
                            Todas
                        </button>
                        <button
                            v-for="col in props.colecciones"
                            :key="col"
                            type="button"
                            @click="filtroColeccion = (filtroColeccion === col ? '' : col)"
                            :class="[
                                'px-2.5 py-0.5 text-[11px] rounded-full font-medium transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                filtroColeccion === col
                                    ? 'bg-emerald-500 text-white shadow-xs font-semibold'
                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                            ]"
                        >
                            <span>{{ col }}</span>
                        </button>
                    </div>
                </div>

                <!-- Drop Zone + Grid -->
                <div
                    class="flex-1 overflow-y-auto p-6"
                    @drop="onDrop"
                    @dragover="onDragOver"
                    @dragleave="onDragLeave"
                >
                    <!-- Overlay de drag & drop -->
                    <Transition name="fade">
                        <div
                            v-if="dragActivo"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-emerald-600/20 backdrop-blur-sm pointer-events-none"
                        >
                            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl p-12 text-center space-y-3 border-2 border-dashed border-emerald-500">
                                <i class="pi pi-cloud-upload text-5xl text-emerald-500" />
                                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Suelta los archivos aquí</p>
                                <p class="text-xs text-zinc-500">Imágenes (JPG, PNG, WebP, AVIF, SVG, GIF) y Vídeos (MP4, WebM)</p>
                            </div>
                        </div>
                    </Transition>

                    <!-- Estado vacío -->
                    <div v-if="!medios.data || !medios.data.length" class="flex flex-col items-center justify-center py-20 space-y-4">
                        <div class="w-20 h-20 rounded-full bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center">
                            <i class="pi pi-images text-3xl text-zinc-400" />
                        </div>
                        <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300">No hay archivos multimedia</h3>
                        <p class="text-xs text-zinc-500 max-w-sm text-center">
                            Sube imágenes o vídeos arrastrándolos aquí o usando el botón "Subir Archivos".
                        </p>
                        <Button icon="pi pi-upload" label="Subir Primer Archivo" severity="success" size="small" @click="abrirModalSubida" />
                    </div>

                    <!-- Grid de thumbnails con soporte de densidad -->
                    <div
                        v-else
                        :class="[
                            'grid gap-3',
                            densidadCuadricula === 'compact'
                                ? 'grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 2xl:grid-cols-12 gap-2'
                                : densidadCuadricula === 'large'
                                    ? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4'
                                    : 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3'
                        ]"
                    >
                        <div
                            v-for="medio in medios.data"
                            :key="medio.id"
                            @click="seleccionarMedio(medio)"
                            :class="[
                                'group relative rounded-xl border overflow-hidden cursor-pointer transition-all duration-200',
                                (seleccionado?.id === medio.id && !modoSeleccionMultiple)
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/30 shadow-lg'
                                    : (modoSeleccionMultiple && seleccionMultiple.has(medio.id))
                                        ? 'border-blue-500 ring-2 ring-blue-500/30'
                                        : 'border-zinc-200 dark:border-zinc-800 hover:border-emerald-400 dark:hover:border-emerald-600 hover:shadow-md',
                            ]"
                        >
                            <!-- Checkbox modo selección múltiple -->
                            <div
                                v-if="modoSeleccionMultiple"
                                :class="[
                                    'absolute top-2 left-2 z-10 w-5 h-5 rounded border-2 flex items-center justify-center transition-all',
                                    seleccionMultiple.has(medio.id)
                                        ? 'bg-blue-500 border-blue-500 text-white'
                                        : 'bg-white/80 border-zinc-300 dark:bg-zinc-900/80 dark:border-zinc-600',
                                ]"
                            >
                                <i v-if="seleccionMultiple.has(medio.id)" class="pi pi-check text-[10px]" />
                            </div>

                            <!-- Badge de colección -->
                            <span
                                v-if="medio.collection"
                                :class="[
                                    'absolute z-10 text-[9px] font-medium px-1.5 py-0.5 rounded bg-zinc-900/75 backdrop-blur-md text-zinc-200 flex items-center gap-1 max-w-[70%] truncate shadow-xs',
                                    modoSeleccionMultiple ? 'top-8 left-2' : 'top-2 left-2'
                                ]"
                                :title="`Colección: ${medio.collection}`"
                            >
                                <i class="pi pi-folder text-[8px] text-emerald-400 shrink-0" />
                                <span class="truncate">{{ medio.collection }}</span>
                            </span>

                            <!-- Thumbnail -->
                            <div class="aspect-square bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden p-1.5">
                                <img
                                    v-if="medio.is_image"
                                    :src="medio.thumbnail_url"
                                    :alt="medio.alt_text || medio.filename"
                                    class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />
                                <div v-else-if="medio.is_video" class="w-full h-full flex items-center justify-center bg-zinc-900">
                                    <div class="text-center space-y-1">
                                        <i class="pi pi-play-circle text-3xl text-white/60" />
                                        <p class="text-[10px] text-white/40 font-mono">{{ medio.extension }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Badge de formato -->
                            <span
                                :class="[
                                    'absolute top-2 right-2 text-[9px] font-bold font-mono px-1.5 py-0.5 rounded-md',
                                    getBadgeClass(medio.extension),
                                ]"
                            >
                                {{ medio.extension }}
                            </span>

                            <!-- Nombre del archivo -->
                            <div class="px-2 py-1.5 bg-white dark:bg-zinc-950">
                                <p class="text-[10px] text-zinc-700 dark:text-zinc-300 truncate font-medium" :title="medio.filename">
                                    {{ medio.filename }}
                                </p>
                                <p class="text-[9px] text-zinc-400 mt-0.5">{{ medio.formatted_size }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="medios.links && medios.links.length > 3" class="flex items-center justify-center gap-1 mt-8">
                        <template v-for="(link, idx) in medios.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-state
                                preserve-scroll
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-lg border transition-all',
                                    link.active
                                        ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm'
                                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:border-emerald-400',
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 text-xs text-zinc-400 cursor-default"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 PANEL LATERAL — Inspector del archivo seleccionado
            ═══════════════════════════════════════════ -->
            <Transition name="slide-right">
                <aside
                    v-if="seleccionado && !modoSeleccionMultiple"
                    class="w-80 border-l border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 flex flex-col shrink-0"
                >
                    <!-- Cabecera con título, paginación y navegación anterior/siguiente -->
                    <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Detalles</h3>
                            <span v-if="indiceSeleccionado >= 0" class="text-[10px] text-zinc-400 font-mono">
                                ({{ indiceSeleccionado + 1 }}/{{ props.medios?.data?.length || 0 }})
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="!hayAnterior"
                                @click="irAnterior"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 disabled:opacity-25 disabled:pointer-events-none transition-all cursor-pointer"
                                title="Archivo anterior (Flecha Izquierda)"
                            >
                                <i class="pi pi-chevron-left text-xs" />
                            </button>
                            <button
                                type="button"
                                :disabled="!haySiguiente"
                                @click="irSiguiente"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 disabled:opacity-25 disabled:pointer-events-none transition-all cursor-pointer"
                                title="Archivo siguiente (Flecha Derecha)"
                            >
                                <i class="pi pi-chevron-right text-xs" />
                            </button>
                            <div class="w-px h-4 bg-zinc-200 dark:bg-zinc-800 mx-0.5" />
                            <button
                                type="button"
                                @click="seleccionado = null"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all cursor-pointer"
                                title="Cerrar panel (Escape)"
                            >
                                <i class="pi pi-times text-sm" />
                            </button>
                        </div>
                    </div>

                    <!-- Contenido scrollable -->
                    <div class="flex-1 overflow-y-auto">
                        <!-- Previsualización -->
                        <div class="p-4 border-b border-zinc-200 dark:border-zinc-800">
                            <div class="rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center min-h-[160px] max-h-[240px]">
                                <img
                                    v-if="seleccionado.is_image"
                                    :src="seleccionado.url"
                                    :alt="seleccionado.alt_text || seleccionado.filename"
                                    class="max-h-[240px] max-w-full object-contain"
                                />
                                <video
                                    v-else-if="seleccionado.is_video"
                                    :src="seleccionado.url"
                                    controls
                                    class="max-h-[240px] max-w-full"
                                />
                            </div>
                        </div>

                        <!-- Información del archivo -->
                        <div class="p-4 space-y-4">
                            <div>
                                <!-- Nombre editable -->
                                <div v-if="editandoNombre" class="flex items-center gap-1.5">
                                    <InputText
                                        v-model="nuevoNombre"
                                        class="flex-1 text-[11px] font-bold"
                                        @keyup.enter="renombrarArchivo"
                                        @keyup.escape="cancelarRenombrado"
                                        autofocus
                                    />
                                    <span class="text-[10px] text-zinc-400 font-mono">.{{ seleccionado.extension.toLowerCase() }}</span>
                                    <button
                                        type="button"
                                        @click="renombrarArchivo"
                                        class="w-6 h-6 flex items-center justify-center rounded text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors cursor-pointer"
                                        title="Guardar nombre"
                                    >
                                        <i class="pi pi-check text-xs" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelarRenombrado"
                                        class="w-6 h-6 flex items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                        title="Cancelar"
                                    >
                                        <i class="pi pi-times text-xs" />
                                    </button>
                                </div>
                                <div v-else class="flex items-center gap-1.5 group">
                                    <p class="text-[11px] font-bold text-zinc-900 dark:text-zinc-100 break-all leading-snug flex-1">{{ seleccionado.filename }}</p>
                                    <button
                                        type="button"
                                        @click="iniciarRenombrado"
                                        class="w-6 h-6 flex items-center justify-center rounded text-zinc-400 opacity-0 group-hover:opacity-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all cursor-pointer shrink-0"
                                        title="Renombrar archivo"
                                    >
                                        <i class="pi pi-pencil text-[10px]" />
                                    </button>
                                </div>
                                <p class="text-[10px] text-zinc-400 mt-1">Subido {{ seleccionado.created_at_human }}</p>
                            </div>

                            <!-- Metadatos técnicos -->
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-2">
                                    <p class="text-zinc-400 font-semibold uppercase tracking-wider">Formato</p>
                                    <p class="text-zinc-700 dark:text-zinc-300 font-bold mt-0.5">{{ seleccionado.extension }}</p>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-2">
                                    <p class="text-zinc-400 font-semibold uppercase tracking-wider">Tamaño</p>
                                    <p class="text-zinc-700 dark:text-zinc-300 font-bold mt-0.5">{{ seleccionado.formatted_size }}</p>
                                </div>
                                <div v-if="seleccionado.dimensions" class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-2 col-span-2">
                                    <p class="text-zinc-400 font-semibold uppercase tracking-wider">Dimensiones</p>
                                    <p class="text-zinc-700 dark:text-zinc-300 font-bold mt-0.5">{{ seleccionado.dimensions }}</p>
                                </div>
                            </div>

                            <!-- Metadatos SEO editables -->
                            <div class="space-y-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                <h4 class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Metadatos SEO</h4>

                                <div>
                                    <label class="block text-[10px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Texto Alternativo (alt)</label>
                                    <InputText
                                        v-model="seleccionado.alt_text"
                                        placeholder="Describe la imagen para SEO..."
                                        class="w-full text-xs"
                                        @change="guardarMetaInline('alt_text', seleccionado.alt_text)"
                                    />
                                </div>

                                <div>
                                    <label class="block text-[10px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Título</label>
                                    <InputText
                                        v-model="seleccionado.title"
                                        placeholder="Título descriptivo..."
                                        class="w-full text-xs"
                                        @change="guardarMetaInline('title', seleccionado.title)"
                                    />
                                </div>

                                <!-- Selector Inteligente de Colección (evita typos) -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-[10px] font-semibold text-zinc-600 dark:text-zinc-400">Colección</label>
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
                                            class="w-full text-xs"
                                        >
                                            <template #value="slotProps">
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200 flex items-center gap-1.5">
                                                    <i class="pi pi-folder text-[10px] text-emerald-500" />
                                                    {{ opcionesColeccionInspector.find(o => o.value === slotProps.value)?.label || 'Sin colección (general)' }}
                                                </span>
                                            </template>
                                        </Select>
                                    </div>
                                    <div v-else class="flex items-center gap-1.5">
                                        <InputText
                                            v-model="nuevaColeccionInspectorInput"
                                            placeholder="Nombre de la nueva colección..."
                                            class="flex-1 text-xs"
                                            @keyup.enter="guardarNuevaColeccionInspector"
                                            @keyup.escape="mostrarNuevaColeccionInspector = false"
                                            autofocus
                                        />
                                        <Button
                                            icon="pi pi-check"
                                            size="small"
                                            severity="success"
                                            @click="guardarNuevaColeccionInspector"
                                            v-tooltip.top="'Guardar colección'"
                                        />
                                        <Button
                                            icon="pi pi-times"
                                            size="small"
                                            severity="secondary"
                                            variant="outlined"
                                            @click="mostrarNuevaColeccionInspector = false; nuevaColeccionInspectorInput = ''"
                                            v-tooltip.top="'Cancelar'"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- URL copiable con microinteracción de checkmark -->
                            <div class="space-y-1.5 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                <label class="block text-[10px] font-semibold text-zinc-600 dark:text-zinc-400">URL del archivo</label>
                                <div class="flex items-center gap-1.5">
                                    <InputText
                                        :model-value="seleccionado.url"
                                        readonly
                                        class="w-full text-[10px] font-mono"
                                    />
                                    <Button
                                        :icon="urlCopiada ? 'pi pi-check' : 'pi pi-copy'"
                                        :severity="urlCopiada ? 'success' : 'secondary'"
                                        variant="outlined"
                                        size="small"
                                        :class="urlCopiada ? 'text-emerald-500 border-emerald-500' : ''"
                                        v-tooltip.top="urlCopiada ? '¡URL Copiada!' : 'Copiar URL'"
                                        @click="copiarUrl"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones fijas abajo (siempre visibles) -->
                    <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 space-y-2 bg-white dark:bg-zinc-950">
                        <a
                            :href="seleccionado.url"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all"
                        >
                            <i class="pi pi-external-link text-[10px]" />
                            Ver original en pestaña nueva
                        </a>
                        <Button
                            icon="pi pi-trash"
                            label="Eliminar archivo"
                            severity="danger"
                            variant="outlined"
                            size="small"
                            class="w-full"
                            @click="eliminarMedio"
                        />
                    </div>
                </aside>
            </Transition>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             MODAL DE SUBIDA CON SELECCIÓN/CREACIÓN DE COLECCIONES
        ════════════════════════════════════════════════════════ -->
        <Dialog
            v-model:visible="mostrarModalSubida"
            modal
            header="Subir a la Biblioteca de Medios"
            :style="{ width: '36rem' }"
            :breakpoints="{ '1199px': '65vw', '575px': '95vw' }"
        >
            <div class="space-y-4 py-1">
                <!-- Selector / Creador de Colección -->
                <div class="bg-zinc-50 dark:bg-zinc-900/60 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <i class="pi pi-folder text-emerald-500 text-xs" />
                            <span>Colección de destino:</span>
                        </label>
                        <button
                            v-if="!mostrarNuevaColeccion"
                            type="button"
                            @click="mostrarNuevaColeccion = true"
                            class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 cursor-pointer"
                        >
                            <i class="pi pi-plus text-[9px]" />
                            <span>Nueva colección</span>
                        </button>
                    </div>

                    <div v-if="!mostrarNuevaColeccion" class="flex items-center gap-2">
                        <Select
                            v-model="coleccionSubida"
                            :options="opcionesColeccionSubida"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Sin colección (general)"
                            class="w-full text-xs"
                        />
                    </div>
                    <div v-else class="flex items-center gap-1.5">
                        <InputText
                            v-model="nuevaColeccionInput"
                            placeholder="Nombre de la nueva colección (ej: productos, blog)..."
                            class="flex-1 text-xs"
                            @keyup.enter="agregarNuevaColeccion"
                            @keyup.escape="mostrarNuevaColeccion = false"
                            autofocus
                        />
                        <Button
                            icon="pi pi-check"
                            size="small"
                            severity="success"
                            @click="agregarNuevaColeccion"
                            v-tooltip.top="'Guardar colección'"
                        />
                        <Button
                            icon="pi pi-times"
                            size="small"
                            severity="secondary"
                            variant="outlined"
                            @click="mostrarNuevaColeccion = false; nuevaColeccionInput = ''"
                            v-tooltip.top="'Cancelar'"
                        />
                    </div>
                </div>

                <!-- Dropzone / Selector de archivos dentro del modal -->
                <div
                    @drop.prevent="onDropModal"
                    @dragover.prevent="dragActivoModal = true"
                    @dragleave="dragActivoModal = false"
                    :class="[
                        'flex flex-col items-center justify-center p-8 rounded-2xl border-2 border-dashed transition-all cursor-pointer text-center select-none',
                        dragActivoModal
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                            : 'border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/40 hover:border-emerald-400',
                    ]"
                    @click="inputFileModalRef?.click()"
                >
                    <!-- Estado de subida con progreso animado -->
                    <div v-if="cargandoSubida" class="w-full max-w-sm space-y-4 py-2">
                        <div class="relative flex items-center justify-center mx-auto">
                            <div class="w-14 h-14 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center animate-pulse">
                                <i class="pi pi-spin pi-spinner text-2xl text-emerald-500" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <p class="text-xs font-mono font-medium text-zinc-500 truncate max-w-xs mx-auto">
                                {{ progresoNombreArchivo }}
                            </p>
                            <p class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                {{ progresoEstadoText }}
                            </p>
                        </div>

                        <div class="space-y-1.5 w-full">
                            <div class="w-full h-2.5 bg-zinc-200 dark:bg-zinc-800 rounded-full overflow-hidden shadow-inner">
                                <div
                                    class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-200"
                                    :style="{ width: porcentajeSubida + '%' }"
                                />
                            </div>
                            <div class="flex justify-between text-[10px] font-mono text-zinc-400">
                                <span>Progreso</span>
                                <span>{{ porcentajeSubida }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Estado normal listo para soltar o explorar -->
                    <div v-else class="space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-center mx-auto shadow-sm">
                            <i class="pi pi-cloud-upload text-2xl text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200">
                                Arrastra archivos aquí o <span class="text-emerald-600 dark:text-emerald-400 underline">haz clic para explorar</span>
                            </p>
                            <p class="text-xs text-zinc-400 mt-1">
                                Imágenes (JPG, PNG, WebP, AVIF, SVG, GIF) y Vídeos (MP4, WebM)
                            </p>
                        </div>
                    </div>

                    <input
                        ref="inputFileModalRef"
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp,image/avif,image/gif,image/svg+xml,video/mp4,video/webm,video/quicktime"
                        @change="onFileModalSelected"
                        class="hidden"
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        label="Cerrar"
                        severity="secondary"
                        variant="outlined"
                        size="small"
                        :disabled="cargandoSubida"
                        @click="mostrarModalSubida = false"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Barra de acciones flotante para selección múltiple -->
        <Transition name="fade-slide-up">
            <div
                v-if="modoSeleccionMultiple && seleccionMultiple.size > 0"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-zinc-900/95 dark:bg-zinc-800/95 text-white px-5 py-2.5 rounded-2xl shadow-2xl border border-zinc-700/60 backdrop-blur-md flex items-center gap-3.5"
            >
                <div class="flex items-center gap-2 pr-3 border-r border-zinc-700">
                    <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 font-bold text-xs flex items-center justify-center">
                        {{ seleccionMultiple.size }}
                    </span>
                    <span class="text-xs font-medium text-zinc-200">seleccionado(s)</span>
                </div>

                <button
                    type="button"
                    @click="seleccionarTodos"
                    class="text-xs text-zinc-300 hover:text-white hover:underline transition-colors px-1 cursor-pointer"
                >
                    Seleccionar todos ({{ props.medios?.data?.length || 0 }})
                </button>

                <button
                    type="button"
                    @click="deseleccionarTodos"
                    class="text-xs text-zinc-400 hover:text-zinc-200 transition-colors px-1 cursor-pointer"
                >
                    Deseleccionar
                </button>

                <Button
                    icon="pi pi-trash"
                    label="Eliminar selección"
                    severity="danger"
                    size="small"
                    class="text-xs shadow-sm"
                    @click="eliminarSeleccionados"
                />
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Transición del panel lateral */
.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.25s ease, opacity 0.25s ease;
}
.slide-right-enter-from,
.slide-right-leave-to {
    transform: translateX(100%);
    opacity: 0;
}

/* Transición del overlay de drag */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Transición de barra flotante */
.fade-slide-up-enter-active,
.fade-slide-up-leave-active {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-slide-up-enter-from,
.fade-slide-up-leave-to {
    opacity: 0;
    transform: translate(-50%, 1.25rem);
}
</style>
