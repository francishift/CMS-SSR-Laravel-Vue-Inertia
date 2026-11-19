<script setup>
/**
 * Vista de categoría pública.
 *
 * Carga la plantilla dinámica según category.template.
 * Muestra breadcrumb, descripción y artículos paginados sin parpadeo.
 * Layout público persistente para navegación SPA fluida.
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import CategoryTemplateGrid from '@/Frontend/Templates/CategoryTemplateGrid.vue';
import PageBuilderRenderer from '@/Frontend/Builder/PageBuilderRenderer.vue';
import { useListAutoExpire } from '@/Composables/useAutoExpire';

defineOptions({ layout: PublicLayout });

const page = usePage();

// Mapa de plantillas disponibles
const templateComponents = {
    grid: CategoryTemplateGrid,
};

const props = defineProps({
    categoria: { type: Object, required: true },
    articulos: { type: Object, required: true },
    breadcrumb: { type: Array, default: () => [] },
});

// Auto-recarga reactiva y suave cuando caduca o se publica el artículo más cercano
useListAutoExpire(
    () => props.articulos?.data || [],
    () => {
        router.reload({ preserveScroll: true, only: ['articulos'] });
    }
);

// Referencias reactivas que se sincronizan con las props de Inertia (incluyendo cambios de página)
const categoryRef = ref(props.categoria);
const breadcrumbRef = ref(props.breadcrumb);

watch(() => props.categoria, (newCat) => {
    if (newCat) {
        categoryRef.value = newCat;
    }
}, { deep: true });

watch(() => props.breadcrumb, (newBread) => {
    if (newBread) {
        breadcrumbRef.value = newBread;
    }
});

const currentPage = computed(() => {
    return props.articulos?.meta?.current_page || props.articulos?.current_page || 1;
});

const pageTitle = computed(() => {
    const baseTitle = categoryRef.value?.seo_title || categoryRef.value?.name || 'Categoría';
    const siteName = page.props.siteSettings?.site_name || 'CMS';
    if (currentPage.value > 1) {
        return `${baseTitle} — Página ${currentPage.value} — ${siteName}`;
    }
    return `${baseTitle} — ${siteName}`;
});

const metaDescCat = computed(() => {
    const baseDesc = categoryRef.value?.seo_description
        || categoryRef.value?.description
        || page.props.siteSettings?.seo_default_description
        || page.props.siteSettings?.site_description
        || `Categoría: ${categoryRef.value?.name || ''}`;

    if (currentPage.value > 1) {
        return `${baseDesc} — Página ${currentPage.value}`;
    }
    return baseDesc;
});

const canonicalUrl = computed(() => {
    const baseUrl = `${page.props.appUrl || page.props.ziggy?.url || ''}/${categoryRef.value?.slug || ''}`;
    if (currentPage.value > 1) {
        return `${baseUrl}/pagina/${currentPage.value}`;
    }
    return baseUrl;
});

const lastPage = computed(() => {
    return props.articulos?.meta?.last_page || props.articulos?.last_page || 1;
});

const prevUrl = computed(() => {
    if (currentPage.value <= 1) return null;
    const baseUrl = `${page.props.appUrl || page.props.ziggy?.url || ''}/${categoryRef.value?.slug || ''}`;
    return currentPage.value === 2 ? baseUrl : `${baseUrl}/pagina/${currentPage.value - 1}`;
});

const nextUrl = computed(() => {
    if (currentPage.value >= lastPage.value) return null;
    const baseUrl = `${page.props.appUrl || page.props.ziggy?.url || ''}/${categoryRef.value?.slug || ''}`;
    return `${baseUrl}/pagina/${currentPage.value + 1}`;
});

const TemplateComponent = computed(() => {
    return templateComponents[categoryRef.value?.template || 'grid'] || CategoryTemplateGrid;
});
</script>

<template>
    <Head :title="pageTitle">
        <meta name="description" :content="metaDescCat" />
        <link rel="canonical" :href="canonicalUrl" />
        <link v-if="prevUrl" rel="prev" :href="prevUrl" />
        <link v-if="nextUrl" rel="next" :href="nextUrl" />
        <meta name="robots" :content="categoryRef?.meta_robots || 'index, follow, max-image-preview:large'" />
    </Head>

    <!-- Vista Maquetada con Page Builder (Si tiene plantilla activa asignada) -->
    <template v-if="categoryRef.layout_json?.sections?.length">
        <PageBuilderRenderer :layout="categoryRef.layout_json" :key="`cat-page-${currentPage}`" />
    </template>

    <!-- Vista Estándar Clásica del Sistema (Si no tiene plantilla asignada) -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Breadcrumb (Estable sin parpadeos, según configuración de categoría) -->
        <nav v-if="categoryRef.show_breadcrumb !== false && breadcrumbRef.length" class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
            <template v-for="(item, index) in breadcrumbRef" :key="index">
                <Link v-if="index < breadcrumbRef.length - 1" :href="item.url" class="hover:text-emerald-600 transition-colors">
                    {{ item.name }}
                </Link>
                <span v-else class="text-zinc-900 dark:text-white font-medium">{{ item.name }}</span>
                <span v-if="index < breadcrumbRef.length - 1" class="mx-2">/</span>
            </template>
        </nav>

        <!-- Cabecera de categoría (Estable sin parpadeos) -->
        <header
            class="mb-10"
            :class="{
                'text-center': categoryRef.header_align === 'center',
                'text-right': categoryRef.header_align === 'right',
                'text-left': !categoryRef.header_align || categoryRef.header_align === 'left'
            }"
        >
            <h1 class="font-bold text-zinc-900 dark:text-zinc-100" style="font-family: var(--heading-font), system-ui, sans-serif;">
                {{ categoryRef.name }}
            </h1>
            <p v-if="categoryRef.description" class="mt-2 text-lg text-zinc-600 dark:text-zinc-300 max-w-3xl" :class="{ 'mx-auto': categoryRef.header_align === 'center', 'ml-auto': categoryRef.header_align === 'right' }">
                {{ categoryRef.description }}
            </p>
        </header>

        <!-- Plantilla dinámica de artículos -->
        <component :is="TemplateComponent" :articulos="articulos" :categoria="categoria" />

        <!-- Estado vacío -->
        <div v-if="!articulos.data?.length" class="text-center py-16 bg-zinc-50/50 dark:bg-zinc-900/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 p-8 max-w-xl mx-auto">
            <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400">
                <i class="pi pi-folder-open text-xl" />
            </div>
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Aún no hay artículos en esta categoría.</p>
        </div>
    </div>
</template>

