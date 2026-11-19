<script setup>
/**
 * Componente Modal para la Selección e Inserción de Elementos Modulares y Presets de la Biblioteca.
 * Soporta modo 'element' (añadir a columna) y modo 'section' (añadir sección completa al layout).
 */
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    visible: { type: Boolean, required: true },
    catalogoElementos: { type: Array, default: () => [] },
    modo: { type: String, default: 'element' }, // 'element' | 'section'
    targetSectionIndex: { type: [Number, null], default: null },
});

const emit = defineEmits(['update:visible', 'insertar-elemento', 'insertar-preset-biblioteca']);

const pestanaActiva = ref('base');
const itemsBiblioteca = ref([]);
const cargandoBiblioteca = ref(false);
const busquedaBiblioteca = ref('');
const filtroTipo = ref('todos'); // 'todos' | 'section' | 'element'

const cargarBiblioteca = async () => {
    cargandoBiblioteca.value = true;
    try {
        const res = await axios.get(route('builder-library.index'), {
            params: { search: busquedaBiblioteca.value }
        });
        if (res.data?.success) {
            itemsBiblioteca.value = res.data.items || [];
        }
    } catch (e) {
        console.error('Error al cargar la biblioteca de elementos:', e);
    } finally {
        cargandoBiblioteca.value = false;
    }
};

watch(() => props.visible, (val) => {
    if (val) {
        if (props.modo === 'section') {
            pestanaActiva.value = 'biblioteca';
            filtroTipo.value = 'section';
        } else {
            pestanaActiva.value = 'base';
            filtroTipo.value = 'todos';
        }
        cargarBiblioteca();
    }
});

const getElementIcon = (type) => {
    return {
        section: 'pi pi-th-large',
        heading: 'pi pi-hashtag',
        text: 'pi pi-align-left',
        image: 'pi pi-image',
        button: 'pi pi-link',
        icon: 'pi pi-star',
        pagination: 'pi pi-arrows-h',
        grid_articles: 'pi pi-th-large',
        accordion: 'pi pi-list',
        menu: 'pi pi-compass',
        social: 'pi pi-share-alt',
        html: 'pi pi-code',
        gallery: 'pi pi-images',
        sublayout: 'pi pi-table',
        map: 'pi pi-map-marker',
    }[type] || 'pi pi-box';
};

function esSeccionItem(preset) {
    if (preset.type === 'section') return true;
    let content = preset.content_json;
    if (typeof content === 'string') {
        try { content = JSON.parse(content); } catch (e) {}
    }
    return Boolean(content && content.rows && Array.isArray(content.rows));
}

function contarFilasYElementos(preset) {
    let content = preset.content_json;
    if (typeof content === 'string') {
        try { content = JSON.parse(content); } catch (e) {}
    }
    if (!content || !content.rows) return '';
    const numFilas = content.rows.length;
    let numElem = 0;
    for (const r of content.rows) {
        for (const c of r.columns || []) {
            numElem += (c.elements || []).length;
        }
    }
    return `${numFilas} ${numFilas === 1 ? 'fila' : 'filas'}, ${numElem} ${numElem === 1 ? 'elemento' : 'elementos'}`;
}

const itemsBibliotecaFiltrados = computed(() => {
    let lista = itemsBiblioteca.value;
    if (filtroTipo.value === 'section') {
        lista = lista.filter(item => esSeccionItem(item));
    } else if (filtroTipo.value === 'element') {
        lista = lista.filter(item => !esSeccionItem(item));
    }
    return lista;
});

const eliminarItemBiblioteca = async (id, event) => {
    event.stopPropagation();
    if (!confirm('¿Estás seguro de que deseas eliminar este elemento de tu biblioteca?')) return;

    try {
        await axios.delete(route('builder-library.destroy', id));
        itemsBiblioteca.value = itemsBiblioteca.value.filter(i => i.id !== id);
    } catch (e) {
        alert('Error al eliminar el elemento de la biblioteca.');
    }
};

const insertarPreset = (item) => {
    let rawContent = item.content_json;
    if (typeof rawContent === 'string') {
        try {
            rawContent = JSON.parse(rawContent);
        } catch (e) {
            console.error('Error al parsear content_json del preset:', e);
        }
    }

    const presetClonado = JSON.parse(JSON.stringify(rawContent || {}));
    const esSeccion = Boolean(item.type === 'section' || (presetClonado.rows && Array.isArray(presetClonado.rows)));
    const esFila = Boolean(item.type === 'row' || (presetClonado.columns && Array.isArray(presetClonado.columns)));

    if (!esSeccion && !esFila) {
        presetClonado.id = 'el_' + Math.random().toString(36).substring(2, 9);
        presetClonado.type = item.type || presetClonado.type || 'text';
    }

    if (!presetClonado.admin_label) {
        presetClonado.admin_label = item.name;
    }
    presetClonado.name = item.name;

    emit('insertar-preset-biblioteca', presetClonado, props.targetSectionIndex);
    emit('update:visible', false);
};

const modalTitulo = computed(() => {
    if (props.modo === 'section') {
        return props.targetSectionIndex !== null && props.targetSectionIndex !== undefined
            ? 'Insertar Sección desde la Biblioteca'
            : 'Añadir Sección desde la Biblioteca';
    }
    return 'Añadir Elemento a la Columna';
});
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="(val) => emit('update:visible', val)"
        modal
        :header="modalTitulo"
        :style="{ width: '50rem' }"
        :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
    >
        <!-- Pestañas de Navegación del Catálogo (Solo visibles en modo elemento) -->
        <div v-if="modo === 'element'" class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-3 mb-4">
            <button
                type="button"
                :class="[
                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 cursor-pointer',
                    pestanaActiva === 'base'
                        ? 'bg-emerald-500 text-white shadow-xs'
                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                ]"
                @click="pestanaActiva = 'base'"
            >
                <i class="pi pi-th-large text-xs" />
                <span>Elementos Base</span>
            </button>

            <button
                type="button"
                :class="[
                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 cursor-pointer',
                    pestanaActiva === 'biblioteca'
                        ? 'bg-emerald-500 text-white shadow-xs'
                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                ]"
                @click="pestanaActiva = 'biblioteca'; cargarBiblioteca();"
            >
                <i class="pi pi-bookmark text-xs" />
                <span>Mi Biblioteca Guardada</span>
                <span
                    v-if="itemsBiblioteca.length"
                    :class="[
                        'text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold',
                        pestanaActiva === 'biblioteca' ? 'bg-emerald-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'
                    ]"
                >
                    {{ itemsBiblioteca.length }}
                </span>
            </button>
        </div>

        <!-- Pestaña 1: Catálogo Base de Elementos (Solo modo elemento) -->
        <div v-if="modo === 'element' && pestanaActiva === 'base'" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 py-1">
            <button
                v-for="item in catalogoElementos"
                :key="item.type"
                type="button"
                @click="emit('insertar-elemento', item.type)"
                class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all text-left group flex items-start gap-3.5 cursor-pointer"
            >
                <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform shrink-0">
                    <i :class="[item.icon, 'text-lg']" />
                </div>
                <div>
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                        {{ item.name }}
                    </h3>
                    <p class="text-[11px] text-zinc-500 mt-1 leading-normal">
                        {{ item.desc }}
                    </p>
                </div>
            </button>
        </div>

        <!-- Pestaña 2: Mi Biblioteca de Presets Guardados -->
        <div v-else class="space-y-4 py-1">
            <!-- Barra superior: Filtro y Buscador -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <!-- Filtros rápidos de tipo -->
                <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800/80 p-1 rounded-lg">
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 rounded-md text-xs font-medium transition-all cursor-pointer',
                            filtroTipo === 'todos'
                                ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-2xs font-bold'
                                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        @click="filtroTipo = 'todos'"
                    >
                        Todos ({{ itemsBiblioteca.length }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 rounded-md text-xs font-medium transition-all flex items-center gap-1.5 cursor-pointer',
                            filtroTipo === 'section'
                                ? 'bg-emerald-500 text-white shadow-2xs font-bold'
                                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        @click="filtroTipo = 'section'"
                    >
                        <i class="pi pi-th-large text-[10px]" />
                        <span>Secciones</span>
                    </button>
                    <button
                        v-if="modo === 'element'"
                        type="button"
                        :class="[
                            'px-2.5 py-1 rounded-md text-xs font-medium transition-all flex items-center gap-1.5 cursor-pointer',
                            filtroTipo === 'element'
                                ? 'bg-emerald-500 text-white shadow-2xs font-bold'
                                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'
                        ]"
                        @click="filtroTipo = 'element'"
                    >
                        <i class="pi pi-box text-[10px]" />
                        <span>Elementos</span>
                    </button>
                </div>

                <!-- Buscador de la Biblioteca -->
                <div class="flex items-center gap-2 flex-1 min-w-[200px] justify-end">
                    <IconField class="flex-1 max-w-xs">
                        <InputIcon class="pi pi-search text-xs" />
                        <InputText
                            v-model="busquedaBiblioteca"
                            placeholder="Buscar en la biblioteca..."
                            class="w-full text-xs"
                            @input="cargarBiblioteca"
                        />
                    </IconField>
                    <Button
                        icon="pi pi-refresh"
                        size="small"
                        severity="secondary"
                        variant="outlined"
                        v-tooltip.top="'Refrescar Biblioteca'"
                        @click="cargarBiblioteca"
                    />
                </div>
            </div>

            <!-- Cargando -->
            <div v-if="cargandoBiblioteca" class="py-12 text-center text-zinc-400 text-xs space-y-2">
                <i class="pi pi-spin pi-spinner text-2xl text-emerald-500 block" />
                <span>Cargando biblioteca...</span>
            </div>

            <!-- Estado Vacío -->
            <div v-else-if="!itemsBibliotecaFiltrados.length" class="py-12 px-4 text-center bg-zinc-50 dark:bg-zinc-900/40 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 space-y-2">
                <i class="pi pi-bookmark text-3xl text-zinc-400 mb-1 block opacity-60" />
                <h4 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                    {{ filtroTipo === 'section' ? 'No hay secciones guardadas aún' : 'No hay elementos guardados aún' }}
                </h4>
                <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                    {{ filtroTipo === 'section' 
                        ? 'Puedes guardar cualquier sección completa desde la cabecera de la sección (icono de marcador) o desde el panel de Ajustes.' 
                        : 'Puedes guardar cualquier elemento o sección configurada desde el editor para reutilizarla en cualquier página.' }}
                </p>
            </div>

            <!-- Listado de Presets en Cuadrícula -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[60vh] overflow-y-auto pr-1">
                <div
                    v-for="preset in itemsBibliotecaFiltrados"
                    :key="preset.id"
                    @click="insertarPreset(preset)"
                    :class="[
                        'p-4 rounded-xl border bg-white dark:bg-zinc-900 hover:shadow-md transition-all text-left group flex flex-col justify-between cursor-pointer space-y-3',
                        esSeccionItem(preset)
                            ? 'border-emerald-300 dark:border-emerald-800/80 hover:border-emerald-500 ring-1 ring-emerald-500/10'
                            : 'border-zinc-200 dark:border-zinc-800 hover:border-emerald-500'
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <div
                            :class="[
                                'w-10 h-10 rounded-lg flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform',
                                esSeccionItem(preset)
                                    ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700'
                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700'
                            ]"
                        >
                            <i :class="[esSeccionItem(preset) ? 'pi pi-th-large' : getElementIcon(preset.type), 'text-lg']" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                    {{ preset.name }}
                                </h3>
                                <button
                                    type="button"
                                    class="text-zinc-400 hover:text-red-500 p-1 rounded transition-colors shrink-0"
                                    v-tooltip.top="'Eliminar de la biblioteca'"
                                    @click="(e) => eliminarItemBiblioteca(preset.id, e)"
                                >
                                    <i class="pi pi-trash text-xs" />
                                </button>
                            </div>
                            <p v-if="preset.description" class="text-[11px] text-zinc-500 mt-0.5 line-clamp-2">
                                {{ preset.description }}
                            </p>
                            <p v-else-if="esSeccionItem(preset)" class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">
                                {{ contarFilasYElementos(preset) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800 text-[10px]">
                        <span
                            v-if="esSeccionItem(preset)"
                            class="font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded uppercase flex items-center gap-1"
                        >
                            <i class="pi pi-th-large text-[8px]" />
                            <span>Sección Completa</span>
                        </span>
                        <span
                            v-else
                            class="font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded text-zinc-600 dark:text-zinc-300 font-semibold uppercase flex items-center gap-1"
                        >
                            <i :class="[getElementIcon(preset.type), 'text-[8px]']" />
                            <span>{{ preset.type }}</span>
                        </span>
                        <span class="text-zinc-400">Guardado el {{ preset.created_at }}</span>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
