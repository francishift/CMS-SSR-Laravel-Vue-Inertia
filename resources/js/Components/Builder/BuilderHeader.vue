<script setup>
/**
 * Componente de Cabecera y Barra Superior para el Page Builder.
 * Incluye título de la página, enlace a listado, selector de vistas responsive (Escritorio, Tablet, Móvil),
 * indicador de cambios sin guardar y botones de Guardar (tanto en lado izquierdo como en el lado derecho) y Previsualización.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';

const props = defineProps({
    pagina: { type: Object, required: true },
    esPlantilla: { type: Boolean, default: false },
    tipoPlantilla: { type: String, default: 'single_article' },
    paginasList: { type: Array, default: () => [] },
    articulosMuestra: { type: Array, default: () => [] },
    descargasMuestra: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    articuloMuestra: { type: Object, default: null },
    categoriaMuestra: { type: Object, default: null },
    descargaMuestra: { type: Object, default: null },
    articuloMuestraId: { type: [Number, String], default: null },
    categoriaMuestraId: { type: [Number, String], default: null },
    descargaMuestraId: { type: [Number, String], default: null },
    vistaDispositivo: { type: String, required: true },
    vistaPanelMovil: { type: String, default: 'editor' },
    tieneCambiosPendientes: { type: Boolean, default: false },
    formProcessing: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:vistaDispositivo',
    'update:vistaPanelMovil',
    'guardar',
    'cambiar-pagina',
    'cambiar-articulo-muestra',
    'cambiar-categoria-muestra',
    'cambiar-descarga-muestra',
]);

const currentUniqueKey = computed(() => {
    if (props.esPlantilla || props.pagina?.type === 'category_archive' || props.pagina?.type === 'single_article' || props.pagina?.type === 'single_download' || props.pagina?.template_type) {
        return `template_${props.pagina.id}`;
    }
    return `page_${props.pagina.id}`;
});

const currentOptionTitle = computed(() => {
    const found = props.paginasList.find(p => p.unique_key === currentUniqueKey.value || (p.type === (props.esPlantilla ? 'template' : 'page') && p.id === props.pagina.id));
    return found?.title || props.pagina.title || props.pagina.name;
});

const opcionesArticulosMuestra = computed(() => {
    return (props.articulosMuestra || []).map(a => ({
        id: a.id,
        label: `${a.title} (${a.category?.name || 'Sin categoría'})`,
        title: a.title,
        category: a.category?.name || '',
    }));
});

// Entidades de muestra activas para plantillas
const categoriaActiva = computed(() => {
    if (props.categoriaMuestra) return props.categoriaMuestra;
    if (props.categoriaMuestraId) {
        return (props.categorias || []).find(c => c.id === Number(props.categoriaMuestraId)) || null;
    }
    return (props.categorias || [])[0] || null;
});

const articuloActivo = computed(() => {
    if (props.articuloMuestra) return props.articuloMuestra;
    if (props.articuloMuestraId) {
        return (props.articulosMuestra || []).find(a => a.id === Number(props.articuloMuestraId)) || null;
    }
    return (props.articulosMuestra || [])[0] || null;
});

const descargaActiva = computed(() => {
    if (props.descargaMuestra) return props.descargaMuestra;
    if (props.descargaMuestraId) {
        return (props.descargasMuestra || []).find(d => Number(d.id) === Number(props.descargaMuestraId)) || null;
    }
    return (props.descargasMuestra || [])[0] || null;
});

// URL pública contextual (Página estática vs Plantilla de Categoría vs Plantilla de Artículo vs Ficha de Descarga)
const urlWebPublica = computed(() => {
    if (!props.esPlantilla) {
        if (!props.pagina) return '/';
        return props.pagina.is_homepage ? '/' : `/${String(props.pagina.slug || '').replace(/^\/+/, '')}`;
    }

    if (props.tipoPlantilla === 'single_download') {
        return descargaActiva.value?.url || null;
    }

    if (props.tipoPlantilla === 'category_archive') {
        const cat = categoriaActiva.value;
        if (!cat || !cat.slug) return null;
        return `/${String(cat.slug).replace(/^\/+/, '')}`;
    }

    if (props.tipoPlantilla === 'single_article') {
        const art = articuloActivo.value;
        if (!art || !art.slug) return null;
        const catSlug = art.category?.slug || categoriaActiva.value?.slug;
        if (catSlug) {
            return `/${String(catSlug).replace(/^\/+/, '')}/${String(art.slug).replace(/^\/+/, '')}`;
        }
        return `/articulos/${String(art.slug).replace(/^\/+/, '')}`;
    }

    return null;
});

const tooltipWebPublica = computed(() => {
    if (!props.esPlantilla) {
        return props.pagina?.is_homepage ? 'Ver página de inicio' : `Ver página en la web pública (/${props.pagina?.slug || ''})`;
    }

    if (props.tipoPlantilla === 'category_archive') {
        const cat = categoriaActiva.value;
        if (cat?.slug) {
            return `Ver categoría en la web pública (/${cat.slug})`;
        }
        return 'No hay ninguna categoría de muestra seleccionada';
    }

    if (props.tipoPlantilla === 'single_article') {
        const art = articuloActivo.value;
        if (art?.slug) {
            const catSlug = art.category?.slug || categoriaActiva.value?.slug;
            const path = catSlug ? `/${catSlug}/${art.slug}` : `/articulos/${art.slug}`;
            return `Ver artículo en la web pública (${path})`;
        }
        return 'No hay ningún artículo de muestra seleccionado';
    }

    if (props.tipoPlantilla === 'single_download') {
        const dl = descargaActiva.value;
        return dl ? `Ver ficha pública (${dl.title})` : 'Ver ficha pública de descarga';
    }

    return 'Ver en la web pública';
});

const textoBotonWebPublica = computed(() => {
    if (!props.esPlantilla) return 'Ver Web Pública';
    if (props.tipoPlantilla === 'single_download') {
        return descargaActiva.value ? 'Ver Ficha Pública' : 'Ver Web Pública';
    }
    if (props.tipoPlantilla === 'category_archive') {
        return categoriaActiva.value ? 'Ver Categoría Pública' : 'Ver Web Pública';
    }
    if (props.tipoPlantilla === 'single_article') {
        return articuloActivo.value ? 'Ver Artículo Público' : 'Ver Web Pública';
    }
    return 'Ver Web Pública';
});
</script>

<template>
    <header class="h-14 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 px-2 sm:px-4 flex items-center justify-between shrink-0 shadow-2xs gap-2">
        <!-- Título, Selector de Página y Botón de Volver -->
        <div class="flex items-center gap-1.5 sm:gap-3 min-w-0">
            <Link
                :href="pagina.back_url || (pagina.type ? route('plantillas.index') : route('paginas.index'))"
                class="p-1.5 sm:p-2 rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors shrink-0"
                :title="pagina.back_url ? 'Volver a Descargas' : (pagina.type ? 'Volver al Listado de Plantillas' : 'Volver al Listado de Páginas')"
            >
                <i class="pi pi-arrow-left text-xs sm:text-sm" />
            </Link>
            <div class="flex items-center gap-1.5 sm:gap-2.5 min-w-0">
                <!-- Selector rápido de página o plantilla activa -->
                <Select
                    v-if="paginasList && paginasList.length"
                    :modelValue="currentUniqueKey"
                    @update:modelValue="(val) => emit('cambiar-pagina', val)"
                    :options="paginasList"
                    optionLabel="title"
                    optionValue="unique_key"
                    class="text-xs h-8 sm:h-8.5 min-w-0 flex-1 max-w-[135px] sm:min-w-[210px] sm:max-w-[280px]"
                    placeholder="Página o Plantilla..."
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-1.5 truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">
                            <i :class="[esPlantilla ? 'pi pi-palette text-amber-500' : 'pi pi-file text-emerald-600', 'text-xs shrink-0 hidden sm:inline']" />
                            <span class="truncate">{{ currentOptionTitle }}</span>
                        </div>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center justify-between gap-2 w-full text-xs py-0.5">
                            <div class="flex items-center gap-1.5 truncate">
                                <i :class="[slotProps.option.type === 'template' ? 'pi pi-palette text-amber-500' : 'pi pi-file text-emerald-500', 'text-[11px] shrink-0']" />
                                <span class="font-medium truncate">{{ slotProps.option.title }}</span>
                            </div>
                            <span v-if="slotProps.option.is_homepage" class="text-[9px] uppercase px-1.5 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 rounded font-bold shrink-0">Inicio</span>
                            <span v-else-if="slotProps.option.type === 'template'" class="text-[9px] uppercase px-1.5 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 rounded font-bold shrink-0">Plantilla</span>
                        </div>
                    </template>
                </Select>

                <h1 v-else class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate max-w-[140px] sm:max-w-md">
                    {{ pagina.title || pagina.name }}
                </h1>

                <!-- Selector de Muestra en Tiempo Real para Plantillas -->
                <div v-if="esPlantilla" class="hidden xl:flex items-center gap-1.5 border-l border-zinc-200 dark:border-zinc-800 pl-2.5">
                    <span class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Muestra:</span>

                    <!-- Selector de Artículo de Muestra -->
                    <Select
                        v-if="tipoPlantilla === 'single_article' && opcionesArticulosMuestra.length"
                        :modelValue="articuloMuestraId"
                        @update:modelValue="(val) => emit('cambiar-articulo-muestra', val)"
                        :options="opcionesArticulosMuestra"
                        optionLabel="label"
                        optionValue="id"
                        :filter="true"
                        filterPlaceholder="Buscar artículo..."
                        class="text-xs h-8 min-w-[180px] max-w-[240px]"
                        placeholder="Artículo muestra..."
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-1.5 truncate text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                <i class="pi pi-file-o text-xs shrink-0" />
                                <span class="truncate">{{ (opcionesArticulosMuestra.find(a => a.id === slotProps.value))?.title || 'Artículo de muestra' }}</span>
                            </div>
                        </template>
                    </Select>

                    <!-- Selector de Categoría de Muestra -->
                    <Select
                        v-else-if="tipoPlantilla === 'category_archive' && categorias.length"
                        :modelValue="categoriaMuestraId"
                        @update:modelValue="(val) => emit('cambiar-categoria-muestra', val)"
                        :options="categorias"
                        optionLabel="name"
                        optionValue="id"
                        :filter="true"
                        filterPlaceholder="Buscar categoría..."
                        class="text-xs h-8 min-w-[160px] max-w-[220px]"
                        placeholder="Categoría muestra..."
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-1.5 truncate text-xs font-medium text-purple-700 dark:text-purple-300">
                                <i class="pi pi-folder text-xs shrink-0" />
                                <span class="truncate">{{ (categorias.find(c => c.id === slotProps.value))?.name || 'Categoría de muestra' }}</span>
                            </div>
                        </template>
                    </Select>

                    <!-- Selector de Descarga de Muestra + Acceso Directo a Editar Datos/ZIP -->
                    <template v-else-if="tipoPlantilla === 'single_download' && descargasMuestra.length">
                        <Select
                            :modelValue="descargaActiva?.id"
                            @update:modelValue="(val) => emit('cambiar-descarga-muestra', val)"
                            :options="descargasMuestra"
                            optionLabel="title"
                            optionValue="id"
                            :filter="true"
                            filterPlaceholder="Buscar descarga..."
                            class="text-xs h-8 min-w-[180px] max-w-[240px]"
                            placeholder="Descarga muestra..."
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-1.5 truncate text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                    <i class="pi pi-download text-xs shrink-0" />
                                    <span class="truncate">{{ (descargasMuestra.find(d => Number(d.id) === Number(slotProps.value)))?.title || 'Descarga de muestra' }}</span>
                                </div>
                            </template>
                        </Select>

                        <Link
                            v-if="descargaActiva?.admin_edit_url"
                            :href="descargaActiva.admin_edit_url"
                            class="px-2.5 py-1 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-colors inline-flex items-center gap-1 no-underline shrink-0"
                            title="Editar textos, subtítulo y archivo ZIP de este ítem"
                        >
                            <i class="pi pi-pencil text-[10px]" />
                            <span>Editar textos / ZIP</span>
                        </Link>
                    </template>
                </div>

                <span
                    v-if="tieneCambiosPendientes"
                    class="px-1.5 sm:px-2 py-0.5 text-[9px] sm:text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 rounded-full animate-pulse shrink-0"
                >
                    <span class="hidden sm:inline">Cambios sin guardar</span>
                    <span class="sm:hidden">•</span>
                </span>
            </div>
        </div>

        <!-- Conmutador Móvil/Tablet (< lg): Alternar entre Panel Editor y Lienzo de Previsualización -->
        <div class="flex lg:hidden items-center bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-lg border border-zinc-200 dark:border-zinc-700 shrink-0">
            <button
                type="button"
                @click="emit('update:vistaPanelMovil', 'editor')"
                :class="[
                    'px-2 py-1 text-xs rounded-md font-semibold transition-all flex items-center gap-1 cursor-pointer',
                    vistaPanelMovil === 'editor'
                        ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold'
                        : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'
                ]"
            >
                <i class="pi pi-sliders-h text-xs" />
                <span>Editor</span>
            </button>
            <button
                type="button"
                @click="emit('update:vistaPanelMovil', 'preview')"
                :class="[
                    'px-2 py-1 text-xs rounded-md font-semibold transition-all flex items-center gap-1 cursor-pointer',
                    vistaPanelMovil === 'preview'
                        ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold'
                        : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'
                ]"
            >
                <i class="pi pi-eye text-xs" />
                <span>Lienzo</span>
            </button>
        </div>

        <!-- Selector de Vista Responsiva (Escritorio / Tablet / Móvil) -->
        <div class="hidden md:flex items-center bg-zinc-100 dark:bg-zinc-800/80 p-1 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80">
            <button
                type="button"
                @click="emit('update:vistaDispositivo', 'desktop')"
                :class="['px-3 py-1 text-xs rounded-lg font-medium transition-all cursor-pointer flex items-center gap-1.5', vistaDispositivo === 'desktop' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100']"
            >
                <i class="pi pi-desktop text-xs" />
                <span>Escritorio</span>
            </button>
            <button
                type="button"
                @click="emit('update:vistaDispositivo', 'tablet')"
                :class="['px-3 py-1 text-xs rounded-lg font-medium transition-all cursor-pointer flex items-center gap-1.5', vistaDispositivo === 'tablet' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100']"
            >
                <i class="pi pi-tablet text-xs" />
                <span>Tablet</span>
            </button>
            <button
                type="button"
                @click="emit('update:vistaDispositivo', 'mobile')"
                :class="['px-3 py-1 text-xs rounded-lg font-medium transition-all cursor-pointer flex items-center gap-1.5', vistaDispositivo === 'mobile' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100']"
            >
                <i class="pi pi-mobile text-xs" />
                <span>Móvil</span>
            </button>
        </div>

        <!-- Botones de Acción Lado Derecho -->
        <div class="flex items-center gap-2">
            <a
                v-if="urlWebPublica"
                :href="urlWebPublica"
                target="_blank"
                rel="noopener noreferrer"
                class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center gap-1.5 cursor-pointer"
                v-tooltip.bottom="tooltipWebPublica"
            >
                <i class="pi pi-external-link text-xs" />
                <span class="hidden sm:inline">{{ textoBotonWebPublica }}</span>
            </a>
            <span
                v-else
                class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-400 dark:text-zinc-600 flex items-center gap-1.5 cursor-not-allowed select-none opacity-60"
                v-tooltip.bottom="tooltipWebPublica"
            >
                <i class="pi pi-external-link text-xs" />
                <span class="hidden sm:inline">{{ textoBotonWebPublica }}</span>
            </span>
        </div>
    </header>
</template>
