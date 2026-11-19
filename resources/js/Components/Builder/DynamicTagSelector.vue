<script setup>
/**
 * Selector desplegable de Etiquetas / Variables Dinámicas para el Page Builder.
 * Permite insertar etiquetas dinámicas estándar ({article.title}, {category.name}, etc.)
 * y campos extra personalizados ({article.extra_fields.campo}) asociados a la categoría del artículo.
 */
import { computed, inject, ref } from 'vue';
import Button from 'primevue/button';
import Popover from 'primevue/popover';

const props = defineProps({
    tipo: { type: String, default: 'text' }, // 'text' | 'image' | 'video' | 'link' | 'url' | 'icon'
    camposExtra: { type: Array, default: null },
});

const emit = defineEmits(['insertar']);

const esPlantilla = inject('esPlantilla', computed(() => false));
const tipoPlantilla = inject('tipoPlantilla', computed(() => ''));
const articuloMuestra = inject('articuloMuestra', computed(() => null));
const categoriaMuestra = inject('categoriaMuestra', computed(() => null));
const categoriasLista = inject('categoriasLista', computed(() => []));
const opcionesCategoriasConTodas = inject('opcionesCategoriasConTodas', computed(() => []));

const popoverRef = ref(null);

// Normalizar tipo de filtro (tratar 'url' como equivalente a 'link')
const tipoNormalizado = computed(() => {
    if (props.tipo === 'url') return 'link';
    return props.tipo || 'text';
});

// Grupos de etiquetas estándar del CMS
const gruposEtiquetasEstaticos = [
    {
        nombre: 'Artículo Actual',
        icono: 'pi pi-file-edit',
        color: 'text-emerald-600 dark:text-emerald-400',
        items: [
            { etiqueta: '{article.title}', label: 'Título del Artículo', tipo: 'text' },
            { etiqueta: '{article.excerpt}', label: 'Extracto / Resumen', tipo: 'text' },
            { etiqueta: '{article.url}', label: 'URL del Artículo', tipo: 'link' },
            { etiqueta: '{article.category.name}', label: 'Categoría del Artículo', tipo: 'text' },
            { etiqueta: '{article.category.url}', label: 'URL de Categoría del Artículo', tipo: 'link' },
            { etiqueta: '{article.body}', label: 'Cuerpo Completo (HTML)', tipo: 'text' },
            { etiqueta: '{article.icon}', label: 'Icono del Artículo', tipo: 'icon' },
            { etiqueta: '{article.author.name}', label: 'Nombre del Autor', tipo: 'text' },
            { etiqueta: '{article.published_at}', label: 'Fecha de Publicación', tipo: 'text' },
            { etiqueta: '{article.reading_time}', label: 'Tiempo de Lectura (min)', tipo: 'text' },
            { etiqueta: '{article.featured_image}', label: 'Imagen Destacada (URL)', tipo: 'image' },
        ]
    },
    {
        nombre: 'Categoría Actual',
        icono: 'pi pi-folder',
        color: 'text-blue-600 dark:text-blue-400',
        items: [
            { etiqueta: '{category.name}', label: 'Nombre de la Categoría', tipo: 'text' },
            { etiqueta: '{category.url}', label: 'URL de la Categoría', tipo: 'link' },
            { etiqueta: '{category.description}', label: 'Descripción de la Categoría', tipo: 'text' },
            { etiqueta: '{category.icon}', label: 'Icono de la Categoría', tipo: 'icon' },
            { etiqueta: '{category.slug}', label: 'Slug de la Categoría', tipo: 'text' },
            { etiqueta: '{category.featured_image}', label: 'Imagen de Categoría (URL)', tipo: 'image' },
        ]
    },
    {
        nombre: 'Sitio Web Global',
        icono: 'pi pi-globe',
        color: 'text-purple-600 dark:text-purple-400',
        items: [
            { etiqueta: '{site.name}', label: 'Nombre del Sitio', tipo: 'text' },
            { etiqueta: '{site.description}', label: 'Descripción del Sitio', tipo: 'text' },
            { etiqueta: '{site.url}', label: 'URL de Inicio (Portada)', tipo: 'link' },
        ]
    }
];

// Detección de contexto de artículo
const esContextoArticulo = computed(() => {
    return (esPlantilla.value && tipoPlantilla.value === 'single_article') || Boolean(articuloMuestra.value);
});

// Esquemas de campos extra disponibles (categoría del artículo, categoría activa o agregados)
const camposExtraDisponibles = computed(() => {
    if (Array.isArray(props.camposExtra)) {
        return props.camposExtra;
    }

    // 1. Esquema de campos extra de la categoría del artículo de muestra activo
    const schemaArt = articuloMuestra.value?.category?.extra_fields_schema;
    if (Array.isArray(schemaArt) && schemaArt.length > 0) {
        return schemaArt;
    }

    // 2. Esquema de la categoría de muestra activa
    const schemaCat = categoriaMuestra.value?.extra_fields_schema;
    if (Array.isArray(schemaCat) && schemaCat.length > 0) {
        return schemaCat;
    }

    // 3. Esquemas agregados de todas las categorías disponibles (sin duplicados por key)
    const lista = (opcionesCategoriasConTodas.value && opcionesCategoriasConTodas.value.length)
        ? opcionesCategoriasConTodas.value
        : categoriasLista.value;

    if (Array.isArray(lista) && lista.length > 0) {
        const clavesVistas = new Set();
        const campos = [];
        lista.forEach(cat => {
            if (!Array.isArray(cat.extra_fields_schema)) return;
            cat.extra_fields_schema.forEach(field => {
                if (!field.key || clavesVistas.has(field.key)) return;
                clavesVistas.add(field.key);
                campos.push(field);
            });
        });
        if (campos.length > 0) {
            return campos;
        }
    }

    // 4. Fallback si el artículo de muestra tiene valores en extra_fields_data
    const dataExtra = articuloMuestra.value?.extra_fields_data;
    if (dataExtra && typeof dataExtra === 'object' && !Array.isArray(dataExtra)) {
        return Object.keys(dataExtra).map(k => ({
            key: k,
            label: k,
            type: 'text'
        }));
    }

    return [];
});

// Transformar los campos extra en ítems tipados para el selector
const itemsCamposExtra = computed(() => {
    if (!esContextoArticulo.value) return [];

    return camposExtraDisponibles.value.map(field => {
        let tipoItem = 'text';
        const typeNorm = (field.type || '').toLowerCase();
        const keyNorm = (field.key || '').toLowerCase();

        if (typeNorm === 'video' || typeNorm === 'media' || keyNorm.includes('video') || keyNorm.includes('mp4') || keyNorm.includes('webm')) {
            tipoItem = 'video';
        } else if (typeNorm === 'url' || typeNorm === 'link' || keyNorm.includes('url') || keyNorm.includes('link') || keyNorm.includes('enlace') || keyNorm.includes('web')) {
            tipoItem = 'link';
        } else if (typeNorm === 'image' || keyNorm.includes('img') || keyNorm.includes('foto') || keyNorm.includes('imagen')) {
            tipoItem = 'image';
        } else if (typeNorm === 'icon' || keyNorm.includes('icon') || keyNorm.includes('icono')) {
            tipoItem = 'icon';
        }

        return {
            etiqueta: `{article.extra_fields.${field.key}}`,
            label: field.label || field.key,
            tipo: tipoItem,
            key: field.key,
        };
    });
});

const grupoDescargaActual = {
    nombre: 'Descarga Actual',
    icono: 'pi pi-download',
    color: 'text-emerald-600 dark:text-emerald-400',
    items: [
        { etiqueta: '{download.title}', label: 'Título del Plugin / Recurso', tipo: 'text' },
        { etiqueta: '{download.subtitle}', label: 'Subtítulo (Small)', tipo: 'text' },
        { etiqueta: '{download.short_description}', label: 'Descripción Corta / Resumen', tipo: 'text' },
        { etiqueta: '{download.body}', label: 'Descripción Detallada (HTML)', tipo: 'text' },
        { etiqueta: '{download.compatibility}', label: 'Compatibilidad (Joomla / YOOtheme)', tipo: 'text' },
        { etiqueta: '{download.version}', label: 'Versión Actual', tipo: 'text' },
        { etiqueta: '{download.price}', label: 'Precio / Gratis', tipo: 'text' },
        { etiqueta: '{download.file_size}', label: 'Tamaño del Paquete ZIP', tipo: 'text' },
        { etiqueta: '{download.license}', label: 'Tipo de Licencia', tipo: 'text' },
        { etiqueta: '{download.changelog}', label: 'Historial de Cambios (Changelog)', tipo: 'text' },
        { etiqueta: '{download.featured_image}', label: 'Imagen o Icono SVG (URL)', tipo: 'image' },
        { etiqueta: '{download.icon}', label: 'Icono PrimeIcons', tipo: 'icon' },
        { etiqueta: '{download.download_url}', label: 'Enlace Seguro de Descarga ZIP', tipo: 'link' },
        { etiqueta: '{download.demo_url}', label: 'URL de Demo en Vivo', tipo: 'link' },
        { etiqueta: '{download.documentation_url}', label: 'URL de Documentación', tipo: 'link' },
        { etiqueta: '{download.catalog_title}', label: 'Título del Catálogo de Descargas', tipo: 'text' },
        { etiqueta: '{download.catalog_url}', label: 'URL del Catálogo de Descargas', tipo: 'link' },
        { etiqueta: '{download.breadcrumb}', label: 'Ruta del Sitio / Migas de Pan (HTML)', tipo: 'text' },
    ],
};

// Construir todos los grupos (insertando los campos extra del artículo o de descarga si proceden)
const todosLosGrupos = computed(() => {
    if (esPlantilla.value && tipoPlantilla.value === 'single_download') {
        return [
            grupoDescargaActual,
            gruposEtiquetasEstaticos[2], // Sitio Web Global
        ];
    }

    const grupos = [
        gruposEtiquetasEstaticos[0], // Artículo Actual
    ];

    if (itemsCamposExtra.value.length > 0) {
        grupos.push({
            nombre: 'Campos Extra (Artículo)',
            icono: 'pi pi-sliders-h',
            color: 'text-teal-600 dark:text-teal-400',
            items: itemsCamposExtra.value,
        });
    }

    grupos.push(gruposEtiquetasEstaticos[1]); // Categoría Actual
    grupos.push(gruposEtiquetasEstaticos[2]); // Sitio Web Global

    return grupos;
});

const gruposFiltrados = computed(() => {
    const t = tipoNormalizado.value;

    return todosLosGrupos.value.filter((grupo) => {
        // En plantillas de categoría de archivo o páginas no aplican variables de artículo
        if (esPlantilla.value && tipoPlantilla.value === 'category_archive') {
            return grupo.nombre !== 'Artículo Actual' && grupo.nombre !== 'Campos Extra (Artículo)';
        }
        if (!esPlantilla.value && !articuloMuestra.value) {
            return grupo.nombre !== 'Artículo Actual' && grupo.nombre !== 'Campos Extra (Artículo)';
        }
        return true;
    }).map((grupo) => ({
        ...grupo,
        items: grupo.items.filter((item) => {
            if (t === 'video') return item.tipo === 'video' || item.tipo === 'link' || item.tipo === 'text';
            if (t === 'image') return item.tipo === 'image';
            if (t === 'icon') return item.tipo === 'icon' || item.tipo === 'text';
            if (t === 'link') return item.tipo === 'link';
            if (t === 'text') return item.tipo === 'text' || item.tipo === 'image' || item.tipo === 'video' || item.tipo === 'icon' || item.tipo === 'link';
            return true;
        }),
    })).filter((grupo) => grupo.items.length > 0);
});

function togglePopover(event) {
    popoverRef.value?.toggle(event);
}

function seleccionar(item) {
    emit('insertar', item.etiqueta);
    popoverRef.value?.hide();
}
</script>

<template>
    <div class="inline-flex items-center">
        <button
            type="button"
            @click="togglePopover"
            class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors py-0.5 px-1.5 rounded-md hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer"
            title="Insertar origen o variable dinámica ({article.title}, {category.name}...)"
        >
            <i class="pi pi-bolt text-[10px]" />
            <span>Variables Dinámicas</span>
        </button>

        <Popover ref="popoverRef" class="shadow-xl rounded-2xl border border-zinc-200 dark:border-zinc-800">
            <div class="w-80 p-2 space-y-3">
                <div class="flex items-center justify-between pb-1.5 border-b border-zinc-100 dark:border-zinc-800">
                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                        <i class="pi pi-bolt text-emerald-600 text-xs" />
                        <span>Variables del CMS</span>
                    </span>
                    <span class="text-[10px] text-zinc-400">Clic para insertar</span>
                </div>

                <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                    <div v-for="grupo in gruposFiltrados" :key="grupo.nombre" class="space-y-1">
                        <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                            <i :class="[grupo.icono, grupo.color, 'text-[10px]']" />
                            <span>{{ grupo.nombre }}</span>
                        </div>
                        <div class="space-y-0.5">
                            <button
                                v-for="item in grupo.items"
                                :key="item.etiqueta"
                                type="button"
                                @click="seleccionar(item)"
                                class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center justify-between group cursor-pointer gap-2"
                            >
                                <span class="text-xs text-zinc-700 dark:text-zinc-300 font-medium group-hover:text-emerald-600 dark:group-hover:text-emerald-400 truncate">
                                    {{ item.label }}
                                </span>
                                <code class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500 bg-zinc-50 dark:bg-zinc-900 px-1 py-0.5 rounded shrink-0">
                                    {{ item.etiqueta }}
                                </code>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Popover>
    </div>
</template>
