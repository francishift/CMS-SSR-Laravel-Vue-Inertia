<script setup>
/**
 * Vista Pública SSR del Catálogo Autónomo de Descargas.
 *
 * Admite dos modos de renderizado:
 * 1. Maquetación visual completa mediante Page Builder (`PageBuilderRenderer`) cuando `use_builder_layout` está activo.
 * 2. Catálogo configurable de alta precisión (Grid / Listado) con escalas tipográficas y tamaños de tarjeta ajustables.
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import DownloadCard from '@/Frontend/Components/DownloadCard.vue';
import PageBuilderRenderer from '@/Frontend/Builder/PageBuilderRenderer.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { getRutaInterna } from '@/Composables/useUrlHelpers';
import { getHeadingClasses, getHeadingStyle } from '@/Utils/headingScale';

defineOptions({ layout: PublicLayout });

const page = usePage();

const props = defineProps({
    catalogo: { type: Object, required: true },
    descargas: { type: Object, required: true },
    breadcrumb: { type: Array, default: () => [] },
    esPrevisualizacionAdmin: { type: Boolean, default: false },
});

const vistaActual = ref(props.catalogo.default_view_mode || 'grid');

watch(
    () => props.catalogo.default_view_mode,
    (modo) => {
        if (modo) vistaActual.value = modo;
    }
);

const usaBuilderVisual = computed(() =>
    Boolean(props.catalogo.use_builder_layout && props.catalogo.layout_json?.sections?.length)
);

const currentPage = computed(() => Number(props.descargas?.meta?.current_page || 1));
const lastPage = computed(() => Number(props.descargas?.meta?.last_page || 1));

const pageTitle = computed(() => {
    const base = props.catalogo.seo_title || props.catalogo.page_title || 'Descargas';
    const siteName = page.props.siteSettings?.site_name || 'CMS';
    return currentPage.value > 1 ? `${base} — Página ${currentPage.value} — ${siteName}` : `${base} — ${siteName}`;
});

const metaDescription = computed(() => {
    const base = props.catalogo.seo_description
        || props.catalogo.description
        || page.props.siteSettings?.seo_default_description
        || `Catálogo de ${props.catalogo.page_title}`;
    return currentPage.value > 1 ? `${base} — Página ${currentPage.value}` : base;
});

const canonicalUrl = computed(() => {
    const base = props.catalogo.public_url || `${page.props.appUrl || ''}/${props.catalogo.slug}`;
    return currentPage.value > 1 ? `${base}/pagina/${currentPage.value}` : base;
});

const prevUrl = computed(() => {
    if (currentPage.value <= 1) return null;
    const base = props.catalogo.public_url || `${page.props.appUrl || ''}/${props.catalogo.slug}`;
    return currentPage.value === 2 ? base : `${base}/pagina/${currentPage.value - 1}`;
});

const nextUrl = computed(() => {
    if (currentPage.value >= lastPage.value) return null;
    const base = props.catalogo.public_url || `${page.props.appUrl || ''}/${props.catalogo.slug}`;
    return `${base}/pagina/${currentPage.value + 1}`;
});

const robotsContent = computed(() => {
    if (props.esPrevisualizacionAdmin) return 'noindex, nofollow';
    return props.catalogo.meta_robots || 'index, follow, max-image-preview:large';
});

const containerWidthClass = computed(() => {
    return {
        small: 'max-w-4xl mx-auto px-4 sm:px-6',
        boxed: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        expand: 'max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-10',
        full_margin: 'w-full px-4 sm:px-8 lg:px-12',
    }[props.catalogo.container_width || 'boxed'] || 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
});

const headerTitleClasses = computed(() =>
    getHeadingClasses(props.catalogo.header_title_size || 'h1', '', '', '', 'h1')
);

const headerTitleStyle = computed(() =>
    getHeadingStyle(props.catalogo.header_title_size || 'h1', 'h1')
);

const headerDescClass = computed(() => {
    return {
        sm: 'text-sm',
        base: 'text-base',
        lg: 'text-base sm:text-lg',
        xl: 'text-lg sm:text-xl',
    }[props.catalogo.header_desc_size || 'lg'] || 'text-base sm:text-lg';
});

const gridClasses = computed(() => {
    if (vistaActual.value === 'list') {
        return 'flex flex-col gap-5';
    }

    const mobMap = { 1: 'grid-cols-1', 2: 'grid-cols-2' };
    const tabMap = { 1: 'sm:grid-cols-1', 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-3' };
    const deskMap = { 1: 'lg:grid-cols-1', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4' };
    const gapMap = { sm: 'gap-4', md: 'gap-6', lg: 'gap-8', xl: 'gap-10' };

    return [
        'grid',
        mobMap[props.catalogo.cols_mobile] || 'grid-cols-1',
        tabMap[props.catalogo.cols_tablet] || 'sm:grid-cols-2',
        deskMap[props.catalogo.cols_desktop] || 'lg:grid-cols-3',
        gapMap[props.catalogo.gap_size] || 'gap-6',
    ];
});

const paginationLinks = computed(() => props.descargas?.meta?.links || []);
</script>

<template>
    <Head :title="pageTitle">
        <meta name="description" :content="metaDescription" />
        <link rel="canonical" :href="canonicalUrl" />
        <link v-if="prevUrl" rel="prev" :href="prevUrl" />
        <link v-if="nextUrl" rel="next" :href="nextUrl" />
        <meta name="robots" :content="robotsContent" />
    </Head>

    <!-- Aviso privado superior si está en modo Solo Backend -->
    <div v-if="esPrevisualizacionAdmin" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="rounded-2xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 px-4 py-3 text-xs sm:text-sm text-amber-900 dark:text-amber-200 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2 font-medium">
                <i class="pi pi-lock text-amber-600 dark:text-amber-400" />
                <span>Vista previa privada para Administradores — Este catálogo devuelve 404 al público general.</span>
            </div>
            <div class="flex items-center gap-3">
                <a v-if="catalogo.admin_builder_url" :href="catalogo.admin_builder_url" class="font-bold underline hover:opacity-80 text-inherit">
                    Maquetar en Builder
                </a>
                <a v-if="catalogo.admin_index_url" :href="catalogo.admin_index_url" class="font-bold underline hover:opacity-80 text-inherit">
                    Volver al Panel
                </a>
            </div>
        </div>
    </div>

    <!-- MODO 1: Maquetación Visual del Page Builder -->
    <template v-if="usaBuilderVisual">
        <PageBuilderRenderer :layout="catalogo.layout_json" :key="`dl-builder-${currentPage}`" />
    </template>

    <!-- MODO 2: Catálogo Configurable con Controles Granulares -->
    <div v-else :class="[containerWidthClass, 'py-10 sm:py-14']">
        <!-- Migas de pan -->
        <nav v-if="catalogo.show_breadcrumb !== false && breadcrumb.length" aria-label="Breadcrumb" class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
            <template v-for="(crumb, idx) in breadcrumb" :key="idx">
                <Link
                    v-if="idx < breadcrumb.length - 1"
                    :href="getRutaInterna(crumb.url)"
                    class="hover:text-[var(--primary-color,#10b981)] transition-colors no-underline text-inherit"
                >
                    {{ crumb.name }}
                </Link>
                <span v-else class="text-zinc-900 dark:text-zinc-100 font-medium">{{ crumb.name }}</span>
                <span v-if="idx < breadcrumb.length - 1" class="mx-2 opacity-60">/</span>
            </template>
        </nav>

        <!-- Cabecera del Catálogo -->
        <header
            class="mb-10 flex flex-col gap-5 border-b border-zinc-200/80 dark:border-zinc-800 pb-8"
            :class="{
                'items-center text-center': catalogo.header_align === 'center',
                'items-end text-right': catalogo.header_align === 'right',
                'items-start text-left': !catalogo.header_align || catalogo.header_align === 'left',
            }"
        >
            <div class="w-full flex flex-col sm:flex-row sm:items-end justify-between gap-4" :class="{ 'sm:flex-col sm:items-center': catalogo.header_align === 'center' }">
                <div :class="{ 'mx-auto': catalogo.header_align === 'center', 'ml-auto': catalogo.header_align === 'right' }">
                    <div
                        class="inline-flex items-center gap-3"
                        :class="{ 'justify-center': catalogo.header_align === 'center', 'justify-end': catalogo.header_align === 'right' }"
                    >
                        <div
                            v-if="catalogo.show_header_icon !== false && catalogo.icon"
                            class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs"
                            style="background-color: color-mix(in srgb, var(--primary-color, #10b981) 14%, transparent); color: var(--primary-color, #10b981);"
                        >
                            <i :class="[catalogo.icon, 'text-xl']" />
                        </div>
                        <h1
                            :class="[headerTitleClasses, 'font-bold text-zinc-900 dark:text-zinc-100']"
                            :style="headerTitleStyle"
                        >
                            {{ catalogo.page_title }}
                        </h1>
                    </div>

                    <p
                        v-if="catalogo.show_description !== false && catalogo.description"
                        :class="[headerDescClass, 'mt-3 text-zinc-600 dark:text-zinc-300 max-w-3xl leading-relaxed']"
                    >
                        {{ catalogo.description }}
                    </p>
                </div>

                <!-- Conmutador interactivo Grid / Listado (oculto en móvil donde la columna única ya es listado vertical) -->
                <div
                    v-if="catalogo.allow_view_toggle && descargas.data?.length"
                    class="hidden sm:inline-flex items-center gap-1 rounded-xl bg-zinc-100 dark:bg-zinc-800/90 p-1 border border-zinc-200/80 dark:border-zinc-700/80 shrink-0"
                    role="group"
                    aria-label="Cambiar modo de vista"
                >
                    <button
                        type="button"
                        @click="vistaActual = 'grid'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition-all cursor-pointer',
                            vistaActual === 'grid'
                                ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-xs'
                                : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200',
                        ]"
                    >
                        <i class="pi pi-th-large text-xs" />
                        <span>Grid</span>
                    </button>
                    <button
                        type="button"
                        @click="vistaActual = 'list'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition-all cursor-pointer',
                            vistaActual === 'list'
                                ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-xs'
                                : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200',
                        ]"
                    >
                        <i class="pi pi-list text-xs" />
                        <span>Listado</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Catálogo de tarjetas -->
        <div v-if="descargas.data?.length" :class="gridClasses">
            <DownloadCard
                v-for="item in descargas.data"
                :key="item.id"
                :item="item"
                :view-mode="vistaActual"
                :show-quick-download="catalogo.show_quick_download"
                :title-tag="catalogo.item_title_tag || 'h2'"
                :card-config="catalogo"
            />
        </div>

        <!-- Estado vacío -->
        <div
            v-else
            class="text-center py-16 px-6 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30 max-w-xl mx-auto"
        >
            <i :class="[catalogo.icon || 'pi pi-download', 'text-3xl text-zinc-400 mb-3']" />
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Próximamente publicaremos nuevos recursos descargables en esta sección.
            </p>
        </div>

        <!-- Paginación limpia SEO -->
        <div v-if="lastPage > 1 && paginationLinks.length" class="mt-12 flex justify-center items-center gap-1.5 flex-wrap">
            <template v-for="(link, index) in paginationLinks" :key="index">
                <span
                    v-if="link.active"
                    :style="{ backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))', color: 'var(--btn-primary-text, #ffffff)' }"
                    class="px-4 py-2 text-xs rounded-xl font-bold shadow-sm select-none"
                    v-html="link.label"
                />
                <Link
                    v-else-if="link.url"
                    :href="getRutaInterna(link.url)"
                    preserve-scroll
                    class="px-4 py-2 text-xs rounded-xl font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors no-underline"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
