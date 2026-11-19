<script setup>
/**
 * Constructor Visual de Páginas (Page Builder) - Panel de Administración.
 *
 * Arquitectura Clean & SOLID Desacoplada:
 *  - usePageBuilder.js: Composable de estado, mutaciones y sincronización API.
 *  - BuilderHeader.vue: Barra superior y navegación.
 *  - BuilderTree.vue: Árbol interactivo drag-and-drop.
 *  - BuilderInspector.vue: Orquestador de inspectores atómicos.
 *  - AddElementModal.vue: Modal catálogo de elementos modulares.
 */
import { provide, computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import AuthenticatedLayout from '@/Admin/Layouts/AuthenticatedLayout.vue';
import BuilderHeader from '@/Components/Builder/BuilderHeader.vue';
import BuilderTree from '@/Components/Builder/BuilderTree.vue';
import BuilderInspector from '@/Components/Builder/BuilderInspector.vue';
import AddElementModal from '@/Components/Builder/Modals/AddElementModal.vue';
import SaveToLibraryModal from '@/Components/Builder/Modals/SaveToLibraryModal.vue';
import { usePageBuilder } from '@/Composables/usePageBuilder';

import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    pagina: { type: Object, required: true },
    categorias: { type: Array, default: () => [] },
    paginasList: { type: Array, default: () => [] },
    esFooter: { type: Boolean, default: false },
    esPlantilla: { type: Boolean, default: false },
    tipoPlantilla: { type: String, default: 'single_article' },
    plantilla: { type: Object, default: null },
    categoriaMuestra: { type: Object, default: null },
    articuloMuestra: { type: Object, default: null },
    articulosMuestra: { type: Array, default: () => [] },
    descargaMuestra: { type: Object, default: null },
    descargasMuestra: { type: Array, default: () => [] },
    rutaGuardarBuilder: { type: String, default: null },
    rutaPreviewFrame: { type: String, default: null },
});

const confirm = useConfirm();
const toast = useToast();

const {
    siteSettings,
    menuHeader,
    menuFooter,
    layout,
    layoutPreview,
    tieneCambiosPendientes,
    tabActiva,
    elementoSeleccionado,
    elementoSeleccionadoTipo,
    vistaDispositivo,
    anchuraPrevisualizacion,
    form,
    modalAddElementoAbierto,
    modalBibliotecaModo,
    targetSectionIndex,
    catalogoElementos,
    resolvingPreview,
    opcionesCategoriasConTodas,
    opcionesContenedor,
    opcionesAltura,
    opcionesAlineacionVertical,
    opcionesColorTexto,
    opcionesContrasteCabecera,
    opcionesElementoHtml,
    opcionesSticky,
    opcionesPadding,
    opcionesMargin,
    opcionesFondos,
    opcionesBgSize,
    opcionesBgPosition,
    opcionesBgAttachment,
    opcionesBgRepeat,
    opcionesBgBlendMode,
    opcionesBgFilter,
    opcionesBgHideMobile,
    opcionesLayoutFila,
    getElementIcon,
    getElementLabel,
    obtenerNombreElemento,
    añadirSeccion,
    eliminarSeccion,
    añadirFila,
    eliminarFila,
    eliminarFilaSeleccionada,
    cambiarLayoutFila,
    abrirModalAñadirElemento,
    abrirModalBibliotecaSecciones,
    insertarElemento,
    insertarPresetBiblioteca,
    eliminarElemento,
    seleccionarElemento,
    buscarElementoPorId,
    guardarBuilder,
    descartarCambiosYNavegar,
    refrescarPrevisualizacion,
    interceptarClicksNavegacionPreview,
    añadirItemManualGrid,
    startDragItemManual,
    dropItemManual,
    moverItemManual,
    eliminarItemManualGrid,
    añadirItemAcordeon,
    eliminarItemAcordeon,
} = usePageBuilder(props);

// Ref al elemento iframe de previsualización aislada
const iframeRef = ref(null);
const iframeReady = ref(false);
const categoriaMuestraActiva = ref(props.categoriaMuestra || props.plantilla?.categories_as_category_template?.[0] || props.categorias?.[0] || null);
const articuloMuestraActiva = ref(props.articuloMuestra || props.articulosMuestra?.[0] || null);
const descargaMuestraActiva = ref(props.descargaMuestra || props.descargasMuestra?.[0] || null);
const vistaPanelMovil = ref('editor'); // 'editor' | 'preview'

// Sincronizar entidades de muestra e iframe cuando Inertia navega vía SPA entre páginas o plantillas del Builder
watch(
    () => [props.pagina?.id, props.rutaPreviewFrame, props.descargaMuestra, props.articuloMuestra, props.categoriaMuestra],
    ([, newPreviewSrc, newDl, newArt, newCat], [, oldPreviewSrc]) => {
        if (newPreviewSrc !== oldPreviewSrc) {
            iframeReady.value = false;
        }
        categoriaMuestraActiva.value = newCat || props.plantilla?.categories_as_category_template?.[0] || props.categorias?.[0] || null;
        articuloMuestraActiva.value = newArt || props.articulosMuestra?.[0] || null;
        descargaMuestraActiva.value = newDl || props.descargasMuestra?.[0] || null;
    }
);

const seoNoIndex = ref(Boolean(props.pagina?.meta_robots?.includes('noindex')));
const seoNoFollow = ref(Boolean(props.pagina?.meta_robots?.includes('nofollow')));

watch([seoNoIndex, seoNoFollow], ([noindex, nofollow]) => {
    if (noindex && nofollow) {
        form.meta_robots = 'noindex, nofollow';
    } else if (noindex) {
        form.meta_robots = 'noindex, follow';
    } else if (nofollow) {
        form.meta_robots = 'index, nofollow';
    } else {
        form.meta_robots = null;
    }
});

const sendToIframe = (message) => {
    if (iframeRef.value?.contentWindow) {
        try {
            const payload = {
                ...message,
            };
            if (message.type === 'CMS_BUILDER_UPDATE_LAYOUT') {
                payload.categoriaMuestra = categoriaMuestraActiva.value ? JSON.parse(JSON.stringify(categoriaMuestraActiva.value)) : null;
                payload.articuloMuestra = articuloMuestraActiva.value ? JSON.parse(JSON.stringify(articuloMuestraActiva.value)) : null;
                payload.articulosMuestra = props.articulosMuestra?.length ? JSON.parse(JSON.stringify(props.articulosMuestra)) : [];
                payload.descargaMuestra = descargaMuestraActiva.value ? JSON.parse(JSON.stringify(descargaMuestraActiva.value)) : null;
                payload.descargasMuestra = props.descargasMuestra?.length ? JSON.parse(JSON.stringify(props.descargasMuestra)) : [];
            }
            const safeClone = JSON.parse(JSON.stringify(payload));
            iframeRef.value.contentWindow.postMessage(safeClone, '*');
        } catch (err) {
            console.error('Error al sincronizar con el iframe:', err);
        }
    }
};

const notificarCambioLayout = () => {
    if (layout.value) {
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
        });
    }
};

// Sincronización continua en memoria del layout al iframe en tiempo real (< 1ms)
watch(layout, notificarCambioLayout, { deep: true });
watch(elementoSeleccionado, notificarCambioLayout, { deep: true });

// Sincronizar desplazamiento al elemento seleccionado
watch(elementoSeleccionado, (elem) => {
    if (elem?.id) {
        sendToIframe({
            type: 'CMS_BUILDER_SCROLL_TO',
            id: elem.id,
        });
    }
});

// Escuchador de prueba/cambio de animaciones en el inspector para repetición visual en tiempo real en el iframe
const handleReplayAnimation = (e) => {
    sendToIframe({
        type: 'CMS_BUILDER_REPLAY_ANIMATION',
        targetId: e?.detail?.targetId || elementoSeleccionado.value?.id,
    });
};

/**
 * Normaliza cualquier URL absoluta o relativa a su ruta interna limpia (`/ruta` sin barra final).
 */
const normalizarRutaUrl = (raw) => {
    if (!raw) return '/';
    let pathname = String(raw);
    try {
        if (pathname.startsWith('http://') || pathname.startsWith('https://')) {
            pathname = new URL(pathname).pathname;
        } else {
            pathname = pathname.split('?')[0].split('#')[0];
        }
    } catch {
        pathname = pathname.split('?')[0].split('#')[0];
    }
    if (!pathname.startsWith('/')) pathname = `/${pathname}`;
    return pathname.replace(/\/+$/, '') || '/';
};

/**
 * Resolver una URL a su modelo de página, plantilla, categoría o descarga dentro del CMS.
 */
const resolverDestinoNavegacion = (urlStr) => {
    if (!urlStr) return null;
    const path = normalizarRutaUrl(urlStr);
    const rawSlug = path.replace(/^\/+|\/+$/g, '');

    // 0. ¿Es un enlace directo a entrega binaria del módulo de Descargas (`/download-vault/{slug}/request`)?
    if (path.includes('/request') && (props.descargasMuestra || []).length) {
        const dlMatch = (props.descargasMuestra || []).find((d) => d.download_request_url && normalizarRutaUrl(d.download_request_url) === path);
        if (dlMatch) {
            return {
                tipo: 'aviso_descarga_binaria',
                descarga: dlMatch,
                title: dlMatch.title,
            };
        }
    }

    // Redirección canónica de menú `/download-vault/catalogo` -> portada de Descargas
    if (path.endsWith('/catalogo')) {
        const dlCatalog = (props.paginasList || []).find((p) => p.id === 'downloads-catalog');
        if (dlCatalog) {
            return {
                tipo: 'pagina',
                id: dlCatalog.id,
                title: dlCatalog.title,
                builder_url: dlCatalog.builder_url,
            };
        }
    }

    // 1. ¿Es una página estática, catálogo autónomo o plantilla en la lista de páginas?
    const targetPage = (props.paginasList || []).find((p) => {
        const pUrl = normalizarRutaUrl(p.url || '');
        const pSlug = `/${(p.slug || '').replace(/^\/+|\/+$/g, '')}`;
        return pUrl === path || pSlug === path || (p.is_homepage && path === '/');
    });

    if (targetPage) {
        return {
            tipo: targetPage.type === 'template' ? 'plantilla' : 'pagina',
            id: targetPage.id,
            title: targetPage.title,
            builder_url: targetPage.builder_url || (targetPage.type === 'template' ? route('plantillas.builder', targetPage.id) : route('paginas.builder', targetPage.id)),
        };
    }

    // 1b. ¿Es una ficha individual del módulo de Descargas (`/{dlSlug}/{itemSlug}`)?
    const dlItem = (props.descargasMuestra || []).find((d) => {
        const dUrl = d.url ? normalizarRutaUrl(d.url) : '';
        return (dUrl && dUrl === path) || (d.slug && path.endsWith(`/${d.slug}`));
    });
    if (dlItem) {
        if (props.esPlantilla && props.tipoPlantilla === 'single_download') {
            return {
                tipo: 'descarga_muestra',
                descarga: dlItem,
                title: dlItem.title,
            };
        }
        if (typeof route === 'function' && route().has('downloads.admin.item-builder')) {
            return {
                tipo: 'plantilla',
                id: 'downloads-item',
                title: `Ficha: ${dlItem.title}`,
                builder_url: route('downloads.admin.item-builder', { preview_download_id: dlItem.id }),
            };
        }
    }

    // 2. ¿Es una categoría activa del CMS?
    const cat = (props.categorias || []).find((c) => c.slug === rawSlug || `/${c.slug}` === path);
    if (cat) {
        let templateId = cat.category_builder_template_id;
        if (!templateId) {
            const catTemplate = (props.paginasList || []).find(p => p.type === 'template' && (p.template_type === 'category_archive' || p.title?.toLowerCase().includes('categoría') || p.title?.toLowerCase().includes('categoria')));
            if (catTemplate) {
                templateId = catTemplate.id;
            }
        }

        if (templateId) {
            const destinoEsMismaPlantilla = props.esPlantilla && props.pagina.id === templateId;
            if (destinoEsMismaPlantilla) {
                return {
                    tipo: 'categoria_muestra',
                    categoria: cat,
                    title: cat.name,
                };
            }
            return {
                tipo: 'plantilla',
                id: templateId,
                title: `Plantilla de ${cat.name}`,
                builder_url: route('plantillas.builder', { template: templateId, preview_category_id: cat.id }),
            };
        }
        return {
            tipo: 'categoria_muestra',
            categoria: cat,
            title: cat.name,
        };
    }

    // 3. ¿Es un artículo del CMS?
    const art = (props.articulosMuestra || []).find((a) => {
        const aPath = a.category?.slug ? `/${a.category.slug}/${a.slug}` : `/${a.slug}`;
        const altPath = `/articulos/${a.slug}`;
        return aPath === path || altPath === path || a.slug === rawSlug || path.endsWith(`/${a.slug}`);
    });
    if (art) {
        // Buscar plantilla asignada al artículo o a su categoría
        let templateId = art.article_builder_template_id;
        if (!templateId) {
            const artCat = (props.categorias || []).find(c => c.id === (art.category?.id || art.category_id));
            templateId = artCat?.article_builder_template_id || art.category?.article_builder_template_id;
        }
        if (!templateId) {
            const singleTemplate = (props.paginasList || []).find(p => p.type === 'template' && (p.template_type === 'single_article' || p.title?.toLowerCase().includes('artículo') || p.title?.toLowerCase().includes('articulo') || p.title?.toLowerCase().includes('single')));
            if (singleTemplate) {
                templateId = singleTemplate.id;
            }
        }

        if (templateId) {
            const destinoEsMismaPlantilla = props.esPlantilla && props.pagina.id === templateId;
            if (destinoEsMismaPlantilla) {
                return {
                    tipo: 'articulo_muestra',
                    articulo: art,
                    title: art.title,
                };
            }
            return {
                tipo: 'plantilla',
                id: templateId,
                title: art.title,
                builder_url: route('plantillas.builder', { template: templateId, preview_article_id: art.id }),
            };
        }

        return {
            tipo: 'articulo_muestra',
            articulo: art,
            title: art.title,
        };
    }

    return null;
};

/**
 * Navegar de forma sincronizada a otra página o plantilla del maquetador visual.
 */
const navegarADestino = (destino) => {
    if (!destino) return;

    if (destino.tipo === 'categoria_muestra') {
        categoriaMuestraActiva.value = destino.categoria;
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
            categoriaMuestra: destino.categoria,
        });
        toast.add({
            severity: 'success',
            summary: 'Categoría en el Visualizador',
            detail: `Mostrando datos de "${destino.categoria.name}".`,
            life: 2500,
        });
        return;
    }

    if (destino.tipo === 'articulo_muestra') {
        articuloMuestraActiva.value = destino.articulo;
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
            articuloMuestra: destino.articulo,
        });
        toast.add({
            severity: 'success',
            summary: 'Artículo en el Visualizador',
            detail: `Mostrando datos de "${destino.articulo.title}".`,
            life: 2500,
        });
        return;
    }

    if (destino.tipo === 'descarga_muestra') {
        descargaMuestraActiva.value = destino.descarga;
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
            descargaMuestra: destino.descarga,
        });
        toast.add({
            severity: 'success',
            summary: 'Descarga en el Visualizador',
            detail: `Previsualizando ficha con "${destino.descarga.title}".`,
            life: 2500,
        });
        return;
    }

    if (destino.tipo === 'aviso_descarga_binaria') {
        toast.add({
            severity: 'info',
            summary: 'Entrega Segura Activa',
            detail: `En la web pública este botón entrega el ZIP firmado de "${destino.title}". Pulsa "Detalles" para maquetar su ficha.`,
            life: 3500,
        });
        return;
    }

    const destinoEsMismaPagina = destino.id === props.pagina.id && (
        (destino.tipo === 'plantilla' && props.esPlantilla) ||
        (destino.tipo === 'pagina' && !props.esPlantilla)
    );

    if (destinoEsMismaPagina) {
        toast.add({
            severity: 'info',
            summary: 'Página activa',
            detail: `Ya estás maquetando "${destino.title}".`,
            life: 2500,
        });
        return;
    }

    const ejecutarNavegacion = () => {
        descartarCambiosYNavegar(destino.builder_url);
    };

    if (tieneCambiosPendientes.value) {
        confirm.require({
            message: `Tienes cambios sin guardar en "${props.pagina.title}". ¿Deseas descartarlos y abrir el maquetador de "${destino.title}"?`,
            header: 'Cambios sin guardar',
            icon: 'pi pi-exclamation-triangle',
            acceptProps: { label: 'Descartar y Cambiar', severity: 'warn' },
            rejectProps: { label: 'Continuar Editando', severity: 'secondary', outlined: true },
            accept: ejecutarNavegacion,
        });
    } else {
        ejecutarNavegacion();
    }
};

const onCambiarPaginaId = (targetKeyOrId) => {
    const target = (props.paginasList || []).find((p) =>
        p.unique_key === targetKeyOrId ||
        p.id === targetKeyOrId ||
        (p.type === (props.esPlantilla ? 'template' : 'page') && p.id === targetKeyOrId)
    );
    if (target) {
        navegarADestino({
            tipo: target.type === 'template' ? 'plantilla' : 'pagina',
            id: target.id,
            title: target.title,
            builder_url: target.builder_url || (target.type === 'template' ? route('plantillas.builder', target.id) : route('paginas.builder', target.id)),
        });
    }
};

const handleWindowMessage = (event) => {
    if (!event.data || typeof event.data !== 'object') return;

    if (event.data.type === 'CMS_BUILDER_FRAME_READY') {
        iframeReady.value = true;
        // Enviar de inmediato el layout actual al iframe recién montado
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
        });
    } else if (event.data.type === 'CMS_BUILDER_SELECT_ELEMENT') {
        const id = event.data.id;
        if (id) {
            const foundNode = buscarElementoPorId(id);
            if (foundNode) {
                let tipo = 'element';
                if (foundNode.rows) tipo = 'section';
                else if (foundNode.columns) tipo = 'row';
                seleccionarElemento(foundNode, tipo, true);
            }
        }
    } else if (event.data.type === 'CMS_BUILDER_NAVIGATE_TO_URL') {
        const destino = resolverDestinoNavegacion(event.data.url);
        if (destino) {
            navegarADestino(destino);
        }
    }
};

const onCambiarArticuloMuestra = (artId) => {
    const art = (props.articulosMuestra || []).find(a => a.id === Number(artId));
    if (art) {
        articuloMuestraActiva.value = art;
        if (art.category) {
            categoriaMuestraActiva.value = art.category;
        }
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
            articuloMuestra: art,
            categoriaMuestra: art.category || categoriaMuestraActiva.value,
        });
        toast.add({
            severity: 'info',
            summary: 'Artículo de Muestra',
            detail: `Previsualizando con: "${art.title}"`,
            life: 2500,
        });
    }
};

const onCambiarCategoriaMuestra = (catId) => {
    const cat = (props.categorias || []).find(c => c.id === Number(catId));
    if (cat) {
        categoriaMuestraActiva.value = cat;
        const sampleArt = (props.articulosMuestra || []).find(a => (a.category?.id || a.category_id) === cat.id) || null;
        if (sampleArt) {
            articuloMuestraActiva.value = sampleArt;
        }
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
            categoriaMuestra: cat,
            articuloMuestra: sampleArt || articuloMuestraActiva.value,
        });
        toast.add({
            severity: 'info',
            summary: 'Categoría de Muestra',
            detail: `Previsualizando con: "${cat.name}"`,
            life: 2500,
        });
    }
};

const onCambiarDescargaMuestra = (dlId) => {
    const dl = (props.descargasMuestra || []).find(d => Number(d.id) === Number(dlId));
    if (dl) {
        descargaMuestraActiva.value = dl;
        sendToIframe({
            type: 'CMS_BUILDER_UPDATE_LAYOUT',
            layout: JSON.parse(JSON.stringify(layout.value)),
            selectedId: elementoSeleccionado.value?.id,
            descargaMuestra: dl,
        });
        toast.add({
            severity: 'info',
            summary: 'Descarga de Muestra',
            detail: `Previsualizando ficha con: "${dl.title}"`,
            life: 2500,
        });
    }
};

provide('vistaDispositivo', vistaDispositivo);
provide('esPlantilla', computed(() => props.esPlantilla));
provide('tipoPlantilla', computed(() => props.tipoPlantilla || ''));
provide('articuloMuestra', computed(() => articuloMuestraActiva.value));
provide('categoriaMuestra', computed(() => categoriaMuestraActiva.value));
provide('descargaMuestra', computed(() => descargaMuestraActiva.value));
provide('categoriasLista', computed(() => props.categorias || []));
provide('opcionesCategoriasConTodas', computed(() => opcionesCategoriasConTodas.value || []));

// Estado de modal de guardado en la biblioteca
const modalSaveBibliotecaAbierto = ref(false);
const elementoAGuardarBiblioteca = ref(null);

const abrirModalGuardarBiblioteca = (elemento) => {
    elementoAGuardarBiblioteca.value = elemento;
    modalSaveBibliotecaAbierto.value = true;
};

const onBibliotecaGuardada = () => {
    toast.add({
        severity: 'success',
        summary: 'Guardado en Biblioteca',
        detail: 'El componente se ha guardado correctamente y ya está disponible.',
        life: 3000,
    });
};

onMounted(() => {
    window.addEventListener('message', handleWindowMessage);
    if (typeof window !== 'undefined') {
        window.addEventListener('cms-replay-animation', handleReplayAnimation);
    }
});

onUnmounted(() => {
    window.removeEventListener('message', handleWindowMessage);
    if (typeof window !== 'undefined') {
        window.removeEventListener('cms-replay-animation', handleReplayAnimation);
    }
});
</script>

<template>
    <div class="h-full flex flex-col bg-zinc-100 dark:bg-zinc-950 overflow-hidden font-sans">
        <!-- BARRA SUPERIOR CON SELECTOR DE PÁGINAS Y MUESTRA -->
        <BuilderHeader
            :pagina="pagina"
            :esPlantilla="esPlantilla"
            :tipoPlantilla="tipoPlantilla"
            :paginasList="paginasList"
            :articulosMuestra="articulosMuestra"
            :descargasMuestra="descargasMuestra"
            :categorias="categorias"
            :articuloMuestra="articuloMuestraActiva"
            :categoriaMuestra="categoriaMuestraActiva"
            :descargaMuestra="descargaMuestraActiva"
            :articuloMuestraId="articuloMuestraActiva?.id"
            :categoriaMuestraId="categoriaMuestraActiva?.id"
            :descargaMuestraId="descargaMuestraActiva?.id"
            v-model:vistaDispositivo="vistaDispositivo"
            v-model:vistaPanelMovil="vistaPanelMovil"
            :tieneCambiosPendientes="tieneCambiosPendientes"
            :formProcessing="form.processing"
            @guardar="guardarBuilder"
            @cambiar-pagina="onCambiarPaginaId"
            @cambiar-articulo-muestra="onCambiarArticuloMuestra"
            @cambiar-categoria-muestra="onCambiarCategoriaMuestra"
            @cambiar-descarga-muestra="onCambiarDescargaMuestra"
        />

        <!-- CUERPO PRINCIPAL DEL EDITOR -->
        <div class="flex-1 flex overflow-hidden relative">
            <!-- PANEL IZQUIERDO: INSPECTOR Y ÁRBOL DE ESTRUCTURA -->
            <aside
                :class="[
                    'w-full lg:w-[460px] bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800 flex-col shrink-0 z-20 shadow-xs builder-sidebar h-full',
                    vistaPanelMovil === 'preview' ? 'hidden lg:flex' : 'flex'
                ]"
            >
                <Tabs :value="tabActiva" @update:value="(val) => tabActiva = val" class="h-full flex flex-col">
                    <TabList class="px-3 border-b border-zinc-200 dark:border-zinc-800 shrink-0 flex items-center justify-between">
                        <div class="flex items-center">
                            <Tab value="0" class="!text-xs !py-3 font-semibold cursor-pointer">
                                <i class="pi pi-sitemap mr-1.5" />
                                <span>1. Estructura</span>
                            </Tab>
                            <Tab value="1" class="!text-xs !py-3 font-semibold cursor-pointer">
                                <i class="pi pi-sliders-h mr-1.5" />
                                <span>2. Ajustes</span>
                            </Tab>
                            <Tab value="2" class="!text-xs !py-3 font-semibold cursor-pointer">
                                <i class="pi pi-search mr-1.5" />
                                <span>3. SEO</span>
                            </Tab>
                        </div>

                        <!-- Botón Guardar en la extrema derecha de las pestañas (Mismo componente y color que Guardar Página) -->
                        <Button
                            label="Guardar"
                            icon="pi pi-check"
                            size="small"
                            :loading="form.processing"
                            @click="guardarBuilder"
                            class="ml-auto my-1 mr-1 shrink-0"
                            v-tooltip.bottom="'Guardar todos los cambios del maquetador'"
                        />
                    </TabList>

                    <TabPanels class="flex-1 overflow-y-auto p-4">
                        <!-- Pestaña 1: Árbol de Estructura -->
                        <TabPanel value="0">
                            <BuilderTree
                                :layout="layout"
                                :elementoSeleccionado="elementoSeleccionado"
                                @seleccionar="seleccionarElemento"
                                @añadir-seccion="añadirSeccion"
                                @abrir-biblioteca-secciones="abrirModalBibliotecaSecciones"
                                @guardar-biblioteca="abrirModalGuardarBiblioteca"
                                @eliminar-seccion="eliminarSeccion"
                                @añadir-fila="añadirFila"
                                @eliminar-fila="eliminarFila"
                                @abrir-modal-elemento="abrirModalAñadirElemento"
                                @eliminar-elemento="eliminarElemento"
                                @refrescar-previsualizacion="refrescarPrevisualizacion"
                            />
                        </TabPanel>

                        <!-- Pestaña 2: Inspección de Opciones -->
                        <TabPanel value="1">
                            <BuilderInspector
                                :elementoSeleccionado="elementoSeleccionado"
                                :elementoSeleccionadoTipo="elementoSeleccionadoTipo"
                                :opcionesCategoriasConTodas="opcionesCategoriasConTodas"
                                :opcionesContenedor="opcionesContenedor"
                                :opcionesAltura="opcionesAltura"
                                :opcionesAlineacionVertical="opcionesAlineacionVertical"
                                :opcionesColorTexto="opcionesColorTexto"
                                :opcionesContrasteCabecera="opcionesContrasteCabecera"
                                :opcionesElementoHtml="opcionesElementoHtml"
                                :opcionesSticky="opcionesSticky"
                                :opcionesPadding="opcionesPadding"
                                :opcionesMargin="opcionesMargin"
                                :opcionesFondos="opcionesFondos"
                                :opcionesBgSize="opcionesBgSize"
                                :opcionesBgPosition="opcionesBgPosition"
                                :opcionesBgAttachment="opcionesBgAttachment"
                                :opcionesBgRepeat="opcionesBgRepeat"
                                :opcionesBgBlendMode="opcionesBgBlendMode"
                                :opcionesBgFilter="opcionesBgFilter"
                                :opcionesBgHideMobile="opcionesBgHideMobile"
                                :opcionesLayoutFila="opcionesLayoutFila"
                                @cambiar-layout="cambiarLayoutFila"
                                @eliminar-fila-seleccionada="eliminarFilaSeleccionada"
                                @añadir-item-manual="añadirItemManualGrid"
                                @eliminar-item-manual="eliminarItemManualGrid"
                                @start-drag-item="startDragItemManual"
                                @drop-item="dropItemManual"
                                @mover-item="moverItemManual"
                                @añadir-item-acordeon="añadirItemAcordeon"
                                @eliminar-item-acordeon="eliminarItemAcordeon"
                                @refrescar-previsualizacion="refrescarPrevisualizacion"
                                @guardar-biblioteca="abrirModalGuardarBiblioteca"
                                @abrir-modal-elemento="abrirModalAñadirElemento"
                            />
                        </TabPanel>

                        <!-- Pestaña 3: Metadatos SEO -->
                        <TabPanel value="2">
                            <div class="space-y-5">
                                <!-- Cabecera de la sección -->
                                <div class="flex items-center gap-3 pb-3 border-b border-zinc-200 dark:border-zinc-800">
                                    <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                        <i class="pi pi-search text-sm" />
                                    </div>
                                    <div>
                                        <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Metadatos SEO de esta Página</h2>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Título, descripción y imagen para motores de búsqueda y redes sociales</p>
                                    </div>
                                </div>

                                <!-- Título SEO -->
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Título SEO (máx. 70)</label>
                                    <InputText v-model="form.seo_title" maxlength="70" class="w-full text-xs" placeholder="Título que aparecerá en Google" />
                                    <div class="flex items-center justify-between mt-1">
                                        <p class="text-[11px] text-zinc-500">Si está vacío, se usará el título de la página</p>
                                        <span :class="['text-[11px] font-mono font-bold px-1.5 py-0.5 rounded', (form.seo_title || '').length > 60 ? 'text-amber-600 bg-amber-50 dark:bg-amber-950/40' : 'text-zinc-400']">
                                            {{ (form.seo_title || '').length }}/70
                                        </span>
                                    </div>
                                </div>

                                <!-- Descripción SEO -->
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Descripción SEO (máx. 160)</label>
                                    <Textarea v-model="form.seo_description" rows="3" maxlength="160" class="w-full text-xs" placeholder="Descripción que aparecerá en los resultados de búsqueda" />
                                    <div class="flex items-center justify-between mt-1">
                                        <p class="text-[11px] text-zinc-500">Resumen atractivo para los resultados de búsqueda</p>
                                        <span :class="['text-[11px] font-mono font-bold px-1.5 py-0.5 rounded', (form.seo_description || '').length > 150 ? 'text-amber-600 bg-amber-50 dark:bg-amber-950/40' : 'text-zinc-400']">
                                            {{ (form.seo_description || '').length }}/160
                                        </span>
                                    </div>
                                </div>

                                <!-- Imagen Open Graph -->
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Imagen Open Graph (Compartir en Redes)</label>
                                    <ImageUploader v-model="form.seo_og_image" directorio="seo" ayuda="Imagen para redes sociales (1200×630px recomendado)" />
                                </div>

                                <!-- Directivas para Robots (Indexación) -->
                                <div class="pt-3 border-t border-zinc-200 dark:border-zinc-800 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Indexación en Buscadores</label>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="seoNoIndex ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400'">
                                            {{ seoNoIndex ? 'No indexar' : 'Indexable (estándar)' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-zinc-500">Por defecto la página se indexa en Google. Activa estas opciones solo si deseas restringirla.</p>

                                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                        <div class="text-[11px] pr-2">
                                            <label for="builder_seo_noindex" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                Bloquear Indexación (<code class="font-mono text-amber-600 dark:text-amber-400">noindex</code>)
                                            </label>
                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">Ocultar de resultados de búsqueda.</p>
                                        </div>
                                        <ToggleSwitch v-model="seoNoIndex" id="builder_seo_noindex" />
                                    </div>

                                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                        <div class="text-[11px] pr-2">
                                            <label for="builder_seo_nofollow" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                No Seguir Enlaces (<code class="font-mono text-amber-600 dark:text-amber-400">nofollow</code>)
                                            </label>
                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">No rastrear enlaces salientes.</p>
                                        </div>
                                        <ToggleSwitch v-model="seoNoFollow" id="builder_seo_nofollow" />
                                    </div>
                                </div>

                                <!-- Previsualización Google SERP -->
                                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800">
                                    <div class="flex items-center gap-2 mb-3">
                                        <i class="pi pi-google text-sm text-zinc-400" />
                                        <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Vista Previa en Google</span>
                                    </div>
                                    <div class="p-4 bg-white dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-1">
                                        <p class="text-xs text-emerald-700 dark:text-emerald-400 font-mono truncate">
                                            tusitio.com/{{ pagina.slug || '' }}
                                        </p>
                                        <h3 class="text-base font-medium text-blue-700 dark:text-blue-400 leading-snug line-clamp-1 cursor-pointer hover:underline">
                                            {{ form.seo_title || pagina.title || 'Título de la Página' }} — {{ siteSettings?.site_name || 'Mi Sitio' }}
                                        </h3>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed line-clamp-2">
                                            {{ form.seo_description || 'Añade una descripción SEO para controlar lo que Google muestra en los resultados de búsqueda.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </aside>

            <!-- PANEL DERECHO: PREVISUALIZADOR EN VIVO (IFRAME AISLADO PROFESIONAL) -->
            <main
                :class="[
                    'flex-1 bg-zinc-200/70 dark:bg-zinc-950/80 overflow-hidden h-full p-2 sm:p-4 md:p-6 flex-col items-center justify-center relative',
                    vistaPanelMovil === 'editor' ? 'hidden lg:flex' : 'flex'
                ]"
            >
                <div v-if="resolvingPreview" class="absolute top-4 right-6 z-30 px-3 py-1.5 bg-emerald-600 text-white text-[11px] font-bold rounded-full shadow-lg flex items-center gap-2 animate-pulse">
                    <i class="pi pi-spin pi-spinner text-xs" />
                    <span>Sincronizando...</span>
                </div>

                <!-- Contenedor Responsivo del Iframe -->
                <div
                    :class="[
                        anchuraPrevisualizacion,
                        'h-full max-h-full transition-all duration-300 relative flex flex-col items-center justify-center w-full'
                    ]"
                >
                    <iframe
                        ref="iframeRef"
                        :key="props.rutaPreviewFrame || `frame-${pagina.id}`"
                        :src="props.rutaPreviewFrame || route('paginas.builder.preview-frame', pagina.id)"
                        class="w-full h-full border-0 rounded-2xl shadow-2xl bg-white dark:bg-zinc-900 transition-all duration-300 ring-1 ring-black/5 dark:ring-white/10"
                        title="Previsualizador en Vivo"
                        allow="autoplay"
                    />
                </div>
            </main>
        </div>

        <!-- MODAL SELECCIÓN DE ELEMENTOS MODULARES Y BIBLIOTECA -->
        <AddElementModal
            v-model:visible="modalAddElementoAbierto"
            :catalogoElementos="catalogoElementos"
            :modo="modalBibliotecaModo"
            :targetSectionIndex="targetSectionIndex"
            @insertar-elemento="insertarElemento"
            @insertar-preset-biblioteca="insertarPresetBiblioteca"
        />

        <!-- MODAL GUARDAR EN LA BIBLIOTECA DE PRESETS -->
        <SaveToLibraryModal
            v-model:visible="modalSaveBibliotecaAbierto"
            :elemento="elementoAGuardarBiblioteca"
        />
    </div>
</template>
