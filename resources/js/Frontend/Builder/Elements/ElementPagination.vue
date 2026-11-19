<script setup>
/**
 * Componente Frontend ElementPagination del Page Builder.
 * Renderiza la paginación con soporte para dos modos principales:
 *  1. Modo «Navegación Entre Artículos» (Anterior / Siguiente) para vistas de detalle.
 *  2. Modo «Paginación Numérica de Archivo» (1, 2, 3...) para listados de categorías y blogs.
 * Incluye múltiples variantes visuales (Cards, Minimal, Pills, Hero, Compact Bar, Numbers Boxed)
 * y previsualización interactiva fiel dentro del editor visual.
 */
import { computed, inject, ref, watch, nextTick } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useElementAnimation } from '@/Composables/useElementAnimation';
import { desplazarSuaveConOffset } from '@/Composables/useScrollAnimation';
import { sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
    resolvedData: { type: Object, default: () => ({}) },
});

const { animAttrs, animStyle } = useElementAnimation(props);
const page = usePage();

// Personalización avanzada
const customId = computed(() => props.content?.element_id || undefined);
const customClasses = computed(() => props.content?.custom_classes || '');
const customCss = computed(() => sanitizeInlineCss(props.content?.custom_css || ''));

const parsedCustomAttributes = computed(() => {
    const raw = props.content?.custom_attributes;
    if (!raw || typeof raw !== 'string' || !raw.trim()) return {};
    const attrs = {};
    const regex = /([a-zA-Z0-9_-]+)(?:=(?:"([^"]*)"|'([^']*)'|([^\s"']+)))?/g;
    let match;
    while ((match = regex.exec(raw)) !== null) {
        const key = match[1];
        const val = match[2] ?? match[3] ?? match[4] ?? '';
        attrs[key] = val;
    }
    return attrs;
});

// Modo de Paginación ('article_nav' | 'archive_numeric')
const mode = computed(() => props.content?.mode || 'article_nav');

// Contexto de plantilla inyectado
const esPlantilla = inject('esPlantilla', computed(() => false));
const tipoPlantilla = inject('tipoPlantilla', computed(() => ''));

// Modo efectivo: respeta directamente la configuración elegida por el usuario
const modoEfectivo = computed(() => mode.value);

/* ══════════════════════════════════════════════════════════════════
   1. MODO: NAVEGACIÓN ENTRE ARTÍCULOS (ANTERIOR / SIGUIENTE)
   ══════════════════════════════════════════════════════════════════ */
const sampleArticleNav = {
    anterior: {
        id: 'mock_prev',
        title: 'Optimización SEO y Core Web Vitals en 2026',
        url: '#',
        featured_image: '',
        category: { name: 'SEO & Rendimiento', slug: 'seo' }
    },
    siguiente: {
        id: 'mock_next',
        title: 'Arquitectura Limpia con Laravel e Inertia SSR',
        url: '#',
        featured_image: '',
        category: { name: 'Desarrollo Web', slug: 'desarrollo' }
    }
};

const navegacionData = computed(() => {
    // 1. Prioridad: Datos inyectados por el servicio de resolución SSR
    if (props.resolvedData?.navegacion) {
        return props.resolvedData.navegacion;
    }
    // 2. Props globales de Inertia en la vista pública de artículo
    if (page.props?.navegacion && (page.props.navegacion.anterior || page.props.navegacion.siguiente)) {
        return page.props.navegacion;
    }
    // 3. Fallback de muestra para el editor visual
    return sampleArticleNav;
});

const prevArticle = computed(() => navegacionData.value?.anterior || null);
const nextArticle = computed(() => navegacionData.value?.siguiente || null);

// Opciones de Contenido de Navegación de Artículos
const layoutVariant = computed(() => props.content?.layout_variant || 'cards'); // 'cards' | 'minimal' | 'pills' | 'hero' | 'compact'
const showLabels = computed(() => props.content?.show_labels !== false);
const prevLabel = computed(() => props.content?.custom_prev_label || 'Artículo Anterior');
const nextLabel = computed(() => props.content?.custom_next_label || 'Artículo Siguiente');
const showTitles = computed(() => props.content?.show_titles !== false);
const showImages = computed(() => Boolean(props.content?.show_images));
const showArrows = computed(() => props.content?.show_arrows !== false);
const arrowType = computed(() => props.content?.arrow_type || 'arrow');

// Iconos de Flechas según el tipo seleccionado
const prevArrowIcon = computed(() => {
    return {
        arrow: 'pi pi-arrow-left',
        chevron: 'pi pi-chevron-left',
        angle: 'pi pi-angle-left',
        circle: 'pi pi-arrow-circle-left',
    }[arrowType.value] || 'pi pi-arrow-left';
});

const nextArrowIcon = computed(() => {
    return {
        arrow: 'pi pi-arrow-right',
        chevron: 'pi pi-chevron-right',
        angle: 'pi pi-angle-right',
        circle: 'pi pi-arrow-circle-right',
    }[arrowType.value] || 'pi pi-arrow-right';
});

// Botón Central "Volver a la Lista / Categoría"
const showHomeButton = computed(() => Boolean(props.content?.show_home_button));
const homeButtonLabel = computed(() => props.content?.home_button_label || 'Todos los Artículos');
const homeButtonUrl = computed(() => props.content?.home_button_url || '#');
const homeButtonIcon = computed(() => props.content?.home_button_icon || 'pi pi-th-large');

/* ══════════════════════════════════════════════════════════════════
   2. MODO: PAGINACIÓN NUMÉRICA DE ARCHIVO / LISTADO
   ══════════════════════════════════════════════════════════════════ */
const isChangingPage = ref(false);
const localActivePage = ref(null);

// Sincronizar y resetear página activa cuando Inertia actualice props
watch(() => props.resolvedData, () => {
    localActivePage.value = null;
});
watch(() => page.props?.articulos, () => {
    localActivePage.value = null;
});

const sampleNumericLinks = [
    { url: '#prev', label: '&laquo; Anterior', active: false },
    { url: '#page-1', label: '1', active: true, page: 1 },
    { url: '#page-2', label: '2', active: false, page: 2 },
    { url: '#page-3', label: '3', active: false, page: 3 },
    { url: null, label: '...', active: false },
    { url: '#page-8', label: '8', active: false, page: 8 },
    { url: '#next', label: 'Siguiente &raquo;', active: false },
];

const rawNumericLinks = computed(() => {
    if (props.resolvedData?.paginacion?.links?.length) {
        return props.resolvedData.paginacion.links;
    }
    if (page.props?.articulos?.meta?.links?.length) {
        return page.props.articulos.meta.links;
    }
    if (page.props?.articulos?.links?.length) {
        return page.props.articulos.links;
    }
    return sampleNumericLinks;
});

const isInsideBuilder = computed(() => typeof window !== 'undefined' && window.parent && window.parent !== window);

const currentPageNum = computed(() => {
    if (localActivePage.value !== null) return localActivePage.value;
    return props.resolvedData?.paginacion?.current_page
        || page.props?.articulos?.meta?.current_page
        || page.props?.articulos?.current_page
        || 1;
});

const lastPageNum = computed(() => {
    return props.resolvedData?.paginacion?.last_page
        || page.props?.articulos?.meta?.last_page
        || page.props?.articulos?.last_page
        || 1;
});

const currentSlug = computed(() => {
    // 1. Slug explícito en datos resueltos por el servidor (PageBuilderResolverService)
    if (props.resolvedData?.paginacion?.base_slug) {
        return String(props.resolvedData.paginacion.base_slug).replace(/^\/+|\/+$/g, '');
    }
    // 2. Slug de categoría activa en Inertia
    if (page.props?.categoria?.slug) {
        return String(page.props.categoria.slug).replace(/^\/+|\/+$/g, '');
    }
    // 3. Slug de página estática activa en Inertia (si no es portada)
    if (page.props?.pagina?.slug && !page.props?.pagina?.is_homepage) {
        return String(page.props.pagina.slug).replace(/^\/+|\/+$/g, '');
    }
    return '';
});

const isArticleSeries = computed(() => Boolean(props.resolvedData?.paginacion?.is_article_series));

const numericLinks = computed(() => {
    const raw = rawNumericLinks.value;
    const current = currentPageNum.value;
    const last = lastPageNum.value;
    const slug = currentSlug.value;
    const isSeries = isArticleSeries.value;

    return raw.map((link) => {
        const pageNum = link.page || parseInt(link.label, 10);
        const isNumeric = !isNaN(pageNum);

        let isActive = Boolean(link.active);
        if (isNumeric) {
            isActive = pageNum === current;
        }

        let linkUrl = link.url;

        // Si la URL que viene del servidor ya es válida y absoluta o relativa limpia (sin ? ni #), la usamos directamente
        const tieneUrlLimpia = linkUrl && !linkUrl.includes('?') && !linkUrl.startsWith('#');

        if (!tieneUrlLimpia && !isSeries) {
            // Detección de botón Anterior
            const isPrev = link.label && (link.label.includes('Anterior') || link.label.includes('&laquo;') || link.label.includes('Previous'));
            if (isPrev) {
                if (current <= 1) {
                    linkUrl = null;
                } else if (current === 2) {
                    linkUrl = slug ? `/${slug}` : '/';
                } else {
                    linkUrl = slug ? `/${slug}/pagina/${current - 1}` : `/pagina/${current - 1}`;
                }
            }

            // Detección de botón Siguiente
            const isNext = link.label && (link.label.includes('Siguiente') || link.label.includes('&raquo;') || link.label.includes('Next'));
            if (isNext) {
                if (current >= last) {
                    linkUrl = null;
                } else {
                    linkUrl = slug ? `/${slug}/pagina/${current + 1}` : `/pagina/${current + 1}`;
                }
            }

            // Si es numérico
            if (isNumeric) {
                linkUrl = pageNum <= 1
                    ? (slug ? `/${slug}` : '/')
                    : (slug ? `/${slug}/pagina/${pageNum}` : `/pagina/${pageNum}`);
            }
        }

        return {
            ...link,
            url: linkUrl,
            active: isActive,
            page: isNumeric ? pageNum : undefined,
        };
    });
});

const prevPageUrl = computed(() => {
    if (currentPageNum.value <= 1) return null;
    const prevLink = numericLinks.value.find(l => l.page === currentPageNum.value - 1);
    if (prevLink?.url && !prevLink.url.startsWith('#')) return prevLink.url;
    if (isArticleSeries.value) return null;
    const slug = currentSlug.value;
    return currentPageNum.value === 2 ? (slug ? `/${slug}` : '/') : (slug ? `/${slug}/pagina/${currentPageNum.value - 1}` : `/pagina/${currentPageNum.value - 1}`);
});

const nextPageUrl = computed(() => {
    if (currentPageNum.value >= lastPageNum.value) return null;
    const nextLink = numericLinks.value.find(l => l.page === currentPageNum.value + 1);
    if (nextLink?.url && !nextLink.url.startsWith('#')) return nextLink.url;
    if (isArticleSeries.value) return null;
    const slug = currentSlug.value;
    return slug ? `/${slug}/pagina/${currentPageNum.value + 1}` : `/pagina/${currentPageNum.value + 1}`;
});

const numericVariant = computed(() => props.content?.numeric_variant || 'numbers_boxed'); // 'numbers_boxed' | 'pills' | 'simple' | 'minimal'

const hacerScrollAlGrid = () => {
    nextTick(() => {
        const gridEl = document.querySelector('.builder-element-grid-container, .builder-element-grid, [data-builder-id*="grid"], main');
        desplazarSuaveConOffset(gridEl);
    });
};

const irAPagina = (linkOrPage) => {
    let targetPageNum = null;

    if (typeof linkOrPage === 'number') {
        targetPageNum = linkOrPage;
    } else if (typeof linkOrPage === 'object' && linkOrPage) {
        if (linkOrPage.page) {
            targetPageNum = linkOrPage.page;
        } else {
            const num = parseInt(linkOrPage.label, 10);
            if (!isNaN(num)) targetPageNum = num;
        }
    }

    if (targetPageNum === null || targetPageNum < 1) return;
    if (targetPageNum === currentPageNum.value) return;

    localActivePage.value = targetPageNum;
    window.dispatchEvent(new CustomEvent('cms-builder-pagination-change', {
        detail: { page: targetPageNum }
    }));
    hacerScrollAlGrid();
};

/* ══════════════════════════════════════════════════════════════════
   3. ESTILOS VISUALES, COLORES Y TAMAÑOS
   ══════════════════════════════════════════════════════════════════ */
const align = computed(() => props.content?.align || 'center');
const colorPreset = computed(() => props.content?.color_preset || 'brand');
const customColor = computed(() => props.content?.custom_color || '');
const size = computed(() => props.content?.size || 'md');

const alignClass = computed(() => {
    return {
        left: 'justify-start text-left',
        center: 'justify-center text-center',
        right: 'justify-end text-right',
        full: 'w-full',
    }[align.value] || 'justify-center';
});

// Márgenes Top y Bottom independientes
const marginTopClass = computed(() => {
    const mt = props.content?.margin_top;
    return { none: 'mt-0', sm: 'mt-4', md: 'mt-8', lg: 'mt-12', xl: 'mt-16', '2xl': 'mt-20' }[mt] || 'mt-8';
});

const marginBottomClass = computed(() => {
    const mb = props.content?.margin_bottom;
    return { none: 'mb-0', sm: 'mb-4', md: 'mb-6', lg: 'mb-8', xl: 'mb-12', '2xl': 'mb-16' }[mb] || 'mb-0';
});
</script>

<template>
    <div
        :id="customId"
        :class="['builder-element-pagination w-full', marginTopClass, marginBottomClass]"
        :style="animStyle"
        v-bind="{ ...animAttrs, ...parsedCustomAttributes }"
    >
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <!-- ═══════════════════════════════════════════════════════════
             MODO 1: NAVEGACIÓN ENTRE ARTÍCULOS (ANTERIOR / SIGUIENTE)
             ═══════════════════════════════════════════════════════════ -->
        <nav
            v-if="modoEfectivo === 'article_nav'"
            aria-label="Navegación entre artículos"
            class="w-full"
        >
            <!-- VARIANTE A: TARJETAS LADO A LADO (Cards) -->
            <div
                v-if="layoutVariant === 'cards'"
                class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full"
            >
                <!-- Artículo Anterior -->
                <div v-if="prevArticle" class="flex">
                    <Link
                        :href="prevArticle.url"
                        class="group w-full p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:border-emerald-500 dark:hover:border-emerald-500 bg-white dark:bg-zinc-900 transition-all duration-200 shadow-2xs hover:shadow-md flex items-center gap-3.5 cursor-pointer"
                    >
                        <div
                            v-if="showArrows"
                            class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 group-hover:bg-[var(--primary-color,#10b981)] group-hover:text-white transition-all flex items-center justify-center shrink-0 group-hover:scale-105"
                        >
                            <i :class="[prevArrowIcon, 'text-sm']" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <span v-if="showLabels" class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                « {{ prevLabel }}
                            </span>
                            <span v-if="showTitles" class="block text-sm font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-[var(--primary-color,#10b981)] transition-colors truncate mt-0.5">
                                {{ prevArticle.title }}
                            </span>
                        </div>
                    </Link>
                </div>
                <div v-else class="hidden sm:block" />

                <!-- Artículo Siguiente -->
                <div v-if="nextArticle" class="flex justify-end">
                    <Link
                        :href="nextArticle.url"
                        class="group w-full p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:border-emerald-500 dark:hover:border-emerald-500 bg-white dark:bg-zinc-900 transition-all duration-200 shadow-2xs hover:shadow-md flex items-center justify-end text-right gap-3.5 cursor-pointer"
                    >
                        <div class="min-w-0 flex-1">
                            <span v-if="showLabels" class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                {{ nextLabel }} »
                            </span>
                            <span v-if="showTitles" class="block text-sm font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-[var(--primary-color,#10b981)] transition-colors truncate mt-0.5">
                                {{ nextArticle.title }}
                            </span>
                        </div>
                        <div
                            v-if="showArrows"
                            class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 group-hover:bg-[var(--primary-color,#10b981)] group-hover:text-white transition-all flex items-center justify-center shrink-0 group-hover:scale-105"
                        >
                            <i :class="[nextArrowIcon, 'text-sm']" />
                        </div>
                    </Link>
                </div>
            </div>

            <!-- VARIANTE B: ENLACES MINIMALISTAS (Minimal) -->
            <div
                v-else-if="layoutVariant === 'minimal'"
                :class="['flex items-center justify-between flex-wrap gap-4 w-full py-2 border-t border-b border-zinc-200 dark:border-zinc-800']"
            >
                <div v-if="prevArticle">
                    <Link
                        :href="prevArticle.url"
                        class="inline-flex items-center gap-2 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-[var(--primary-color,#10b981)] transition-colors cursor-pointer"
                    >
                        <i v-if="showArrows" :class="prevArrowIcon" />
                        <span v-if="showLabels">{{ prevLabel }}</span>
                        <span v-if="showTitles && !showLabels" class="truncate max-w-[200px] sm:max-w-xs">{{ prevArticle.title }}</span>
                    </Link>
                </div>
                <div v-else />

                <div v-if="showHomeButton && homeButtonUrl">
                    <Link
                        :href="homeButtonUrl"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                    >
                        <i :class="homeButtonIcon" />
                        <span>{{ homeButtonLabel }}</span>
                    </Link>
                </div>

                <div v-if="nextArticle">
                    <Link
                        :href="nextArticle.url"
                        class="inline-flex items-center gap-2 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-[var(--primary-color,#10b981)] transition-colors cursor-pointer"
                    >
                        <span v-if="showLabels">{{ nextLabel }}</span>
                        <span v-if="showTitles && !showLabels" class="truncate max-w-[200px] sm:max-w-xs">{{ nextArticle.title }}</span>
                        <i v-if="showArrows" :class="nextArrowIcon" />
                    </Link>
                </div>
            </div>

            <!-- VARIANTE C: BOTONES PÍLDORA (Pills) -->
            <div
                v-else-if="layoutVariant === 'pills'"
                class="flex items-center justify-between gap-3 flex-wrap w-full"
            >
                <div v-if="prevArticle">
                    <Link
                        :href="prevArticle.url"
                        class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-full border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:border-[var(--primary-color,#10b981)] hover:text-[var(--primary-color,#10b981)] hover:shadow-xs transition-all cursor-pointer"
                    >
                        <i v-if="showArrows" :class="prevArrowIcon" />
                        <span>{{ prevLabel }}</span>
                    </Link>
                </div>
                <div v-else />

                <div v-if="showHomeButton && homeButtonUrl">
                    <Link
                        :href="homeButtonUrl"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-zinc-100 dark:bg-zinc-900 text-xs font-bold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                    >
                        <i :class="homeButtonIcon" />
                        <span>{{ homeButtonLabel }}</span>
                    </Link>
                </div>

                <div v-if="nextArticle">
                    <Link
                        :href="nextArticle.url"
                        class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-full border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:border-[var(--primary-color,#10b981)] hover:text-[var(--primary-color,#10b981)] hover:shadow-xs transition-all cursor-pointer"
                    >
                        <span>{{ nextLabel }}</span>
                        <i v-if="showArrows" :class="nextArrowIcon" />
                    </Link>
                </div>
            </div>

            <!-- VARIANTE D: TARJETAS HERO DESTACADAS (Hero) -->
            <div
                v-else-if="layoutVariant === 'hero'"
                class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full"
            >
                <div v-if="prevArticle" class="flex">
                    <Link
                        :href="prevArticle.url"
                        class="group relative w-full p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-950 hover:border-emerald-500 transition-all duration-300 overflow-hidden shadow-2xs hover:shadow-md cursor-pointer flex flex-col justify-between min-h-[110px]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                <i :class="prevArrowIcon" />
                                <span>{{ prevLabel }}</span>
                            </span>
                            <span v-if="prevArticle.category?.name" class="text-[10px] font-mono text-zinc-400 bg-white dark:bg-zinc-800 px-2 py-0.5 rounded-full">
                                {{ prevArticle.category.name }}
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors line-clamp-2">
                            {{ prevArticle.title }}
                        </h4>
                    </Link>
                </div>
                <div v-else class="hidden md:block" />

                <div v-if="nextArticle" class="flex justify-end">
                    <Link
                        :href="nextArticle.url"
                        class="group relative w-full p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-950 hover:border-emerald-500 transition-all duration-300 overflow-hidden shadow-2xs hover:shadow-md cursor-pointer flex flex-col justify-between text-right min-h-[110px]"
                    >
                        <div class="flex items-center justify-between mb-2 flex-row-reverse">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                <span>{{ nextLabel }}</span>
                                <i :class="nextArrowIcon" />
                            </span>
                            <span v-if="nextArticle.category?.name" class="text-[10px] font-mono text-zinc-400 bg-white dark:bg-zinc-800 px-2 py-0.5 rounded-full">
                                {{ nextArticle.category.name }}
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors line-clamp-2">
                            {{ nextArticle.title }}
                        </h4>
                    </Link>
                </div>
            </div>

            <!-- VARIANTE E: BARRA COMPACTA UNIFICADA (Compact) -->
            <div
                v-else-if="layoutVariant === 'compact'"
                class="flex items-center justify-between p-2 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-2xs w-full"
            >
                <Link
                    v-if="prevArticle"
                    :href="prevArticle.url"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 transition-all cursor-pointer"
                >
                    <i :class="prevArrowIcon" />
                    <span>{{ prevLabel }}</span>
                </Link>
                <div v-else class="w-16" />

                <div v-if="showHomeButton && homeButtonUrl">
                    <Link
                        :href="homeButtonUrl"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                    >
                        <i :class="homeButtonIcon" />
                        <span class="hidden sm:inline">{{ homeButtonLabel }}</span>
                    </Link>
                </div>

                <Link
                    v-if="nextArticle"
                    :href="nextArticle.url"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 transition-all cursor-pointer"
                >
                    <span>{{ nextLabel }}</span>
                    <i :class="nextArrowIcon" />
                </Link>
                <div v-else class="w-16" />
            </div>
        </nav>

        <!-- ═══════════════════════════════════════════════════════════
             MODO 2: PAGINACIÓN NUMÉRICA DE ARCHIVO / LISTADO
             ═══════════════════════════════════════════════════════════ -->
        <div
            v-else-if="modoEfectivo === 'archive_numeric'"
            aria-label="Paginación numérica de listado"
            :class="['flex items-center gap-1.5 flex-wrap w-full', alignClass]"
        >
            <!-- VARIANTE A: NÚMEROS BOXED (Estándar) -->
            <template v-if="numericVariant === 'numbers_boxed' || numericVariant === 'pills'">
                <template v-for="(link, idx) in numericLinks" :key="idx">
                    <!-- Página activa (página actual): elemento estático sin recarga redundante -->
                    <span
                        v-if="link.active"
                        :style="{ backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))', color: 'var(--btn-primary-text, #ffffff)', borderColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))' }"
                        :class="[
                            'transition-all duration-200 font-bold border shadow-md scale-105 ring-2 ring-[var(--primary-color,#10b981)]/30 flex items-center justify-center cursor-default select-none',
                            numericVariant === 'pills' ? 'w-10 h-10 rounded-full text-xs' : 'px-3.5 py-2 text-xs rounded-xl min-w-[38px]'
                        ]"
                        v-html="link.label"
                    />
                    <!-- Enlace disponible en vista pública: <Link> nativo de Inertia para SEO y SSR -->
                    <Link
                        v-else-if="link.url && !isInsideBuilder"
                        :href="link.url"
                        preserve-scroll
                        :class="[
                            'transition-all duration-200 font-bold cursor-pointer border active:scale-95 shadow-2xs flex items-center justify-center no-underline bg-zinc-100 dark:bg-zinc-800/80 text-zinc-800 dark:text-zinc-200 border-zinc-200/80 dark:border-zinc-700/80 hover:bg-zinc-200 dark:hover:bg-zinc-700',
                            numericVariant === 'pills' ? 'w-10 h-10 rounded-full text-xs' : 'px-3.5 py-2 text-xs rounded-xl min-w-[38px]'
                        ]"
                        @click="hacerScrollAlGrid"
                        v-html="link.label"
                    />
                    <!-- Enlace interactivo en vista previa del Builder: interactividad sin recarga de iframe -->
                    <button
                        v-else-if="link.url && isInsideBuilder"
                        type="button"
                        @click="irAPagina(link)"
                        :class="[
                            'transition-all duration-200 font-bold cursor-pointer border active:scale-95 shadow-2xs flex items-center justify-center bg-zinc-100 dark:bg-zinc-800/80 text-zinc-800 dark:text-zinc-200 border-zinc-200/80 dark:border-zinc-700/80 hover:bg-zinc-200 dark:hover:bg-zinc-700',
                            numericVariant === 'pills' ? 'w-10 h-10 rounded-full text-xs' : 'px-3.5 py-2 text-xs rounded-xl min-w-[38px]'
                        ]"
                        v-html="link.label"
                    />
                    <!-- Enlace deshabilitado (puntos suspensivos o límites) -->
                    <span
                        v-else
                        :class="[
                            'text-zinc-400 bg-zinc-100/60 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 cursor-not-allowed font-semibold select-none flex items-center justify-center',
                            numericVariant === 'pills' ? 'w-10 h-10 rounded-full text-xs' : 'px-3.5 py-2 text-xs rounded-xl min-w-[38px]'
                        ]"
                        v-html="link.label"
                    />
                </template>
            </template>

            <!-- VARIANTE B: SIMPLE (Anterior / Página X de Y / Siguiente) -->
            <div
                v-else-if="numericVariant === 'simple'"
                class="flex items-center gap-3"
            >
                <!-- Botón Anterior -->
                <template v-if="currentPageNum > 1">
                    <Link
                        v-if="!isInsideBuilder && prevPageUrl"
                        :href="prevPageUrl"
                        preserve-scroll
                        class="px-4 py-2 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-emerald-500 hover:text-emerald-600 transition-all cursor-pointer flex items-center gap-1.5 no-underline"
                        @click="hacerScrollAlGrid"
                    >
                        <i class="pi pi-arrow-left text-xs" />
                        <span>Anterior</span>
                    </Link>
                    <button
                        v-else
                        type="button"
                        @click="irAPagina(currentPageNum - 1)"
                        class="px-4 py-2 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-emerald-500 hover:text-emerald-600 transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <i class="pi pi-arrow-left text-xs" />
                        <span>Anterior</span>
                    </button>
                </template>
                <button
                    v-else
                    type="button"
                    disabled
                    class="px-4 py-2 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-400 opacity-50 cursor-not-allowed flex items-center gap-1.5"
                >
                    <i class="pi pi-arrow-left text-xs" />
                    <span>Anterior</span>
                </button>

                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 font-mono px-2">
                    Página {{ currentPageNum }} de {{ lastPageNum }}
                </span>

                <!-- Botón Siguiente -->
                <template v-if="currentPageNum < lastPageNum">
                    <Link
                        v-if="!isInsideBuilder && nextPageUrl"
                        :href="nextPageUrl"
                        preserve-scroll
                        class="px-4 py-2 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-emerald-500 hover:text-emerald-600 transition-all cursor-pointer flex items-center gap-1.5 no-underline"
                        @click="hacerScrollAlGrid"
                    >
                        <span>Siguiente</span>
                        <i class="pi pi-arrow-right text-xs" />
                    </Link>
                    <button
                        v-else
                        type="button"
                        @click="irAPagina(currentPageNum + 1)"
                        class="px-4 py-2 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-emerald-500 hover:text-emerald-600 transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <span>Siguiente</span>
                        <i class="pi pi-arrow-right text-xs" />
                    </button>
                </template>
                <button
                    v-else
                    type="button"
                    disabled
                    class="px-4 py-2 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-400 opacity-50 cursor-not-allowed flex items-center gap-1.5"
                >
                    <span>Siguiente</span>
                    <i class="pi pi-arrow-right text-xs" />
                </button>
            </div>

            <!-- VARIANTE C: MINIMALISTA (Minimal) -->
            <div
                v-else-if="numericVariant === 'minimal'"
                class="flex items-center gap-2"
            >
                <template v-for="(link, idx) in numericLinks" :key="idx">
                    <span
                        v-if="link.active"
                        class="px-2.5 py-1 text-xs font-extrabold text-[var(--primary-color,#10b981)] underline underline-offset-4 cursor-default select-none"
                        v-html="link.label"
                    />
                    <Link
                        v-else-if="link.url && !isInsideBuilder"
                        :href="link.url"
                        preserve-scroll
                        class="px-2.5 py-1 text-xs font-bold transition-colors cursor-pointer text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 no-underline"
                        @click="hacerScrollAlGrid"
                        v-html="link.label"
                    />
                    <button
                        v-else-if="link.url && isInsideBuilder"
                        type="button"
                        @click="irAPagina(link)"
                        class="px-2.5 py-1 text-xs font-bold transition-colors cursor-pointer text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100"
                        v-html="link.label"
                    />
                    <span v-else class="px-1.5 text-xs text-zinc-400" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
