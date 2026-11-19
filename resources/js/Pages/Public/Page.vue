<script setup>
/**
 * Vista de página estática pública ("Sobre nosotros", "Contacto", "Aviso Legal", etc.).
 * Layout público persistente para navegación SPA fluida.
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

import { computed } from 'vue';
import { useSanitize } from '@/Composables/useSanitize';
import { getHeadingClasses, getHeadingStyle } from '@/Utils/headingScale';

import PageBuilderRenderer from '@/Frontend/Builder/PageBuilderRenderer.vue';

defineOptions({ layout: PublicLayout });

const page = usePage();

const props = defineProps({
    pagina: { type: Object, required: true },
    breadcrumb: { type: Array, default: () => [] },
});

const paginaItem = computed(() => {
    const raw = props.pagina;
    return raw?.data || raw || {};
});

const tieneLayoutBuilder = computed(() => {
    return Boolean(paginaItem.value.layout_json?.sections?.length);
});

const titleSizeKey = computed(() => paginaItem.value?.title_size || 'default');
const pageTitleClasses = computed(() => getHeadingClasses(titleSizeKey.value, '', '', '', 'h1'));
const pageTitleStyle = computed(() => getHeadingStyle(titleSizeKey.value, 'h1'));

// Sanitización XSS del body
const { sanitize } = useSanitize();
const bodySeguro = computed(() => sanitize(paginaItem.value?.body || ''));

const metaDescripcion = computed(() => {
    return paginaItem.value?.seo_description
        || page.props.siteSettings?.seo_default_description
        || page.props.siteSettings?.site_description
        || 'Bienvenido';
});

const canonicalUrl = computed(() => {
    const baseUrl = page.props.appUrl || page.props.ziggy?.url || '';
    if (paginaItem.value?.is_homepage) {
        return baseUrl ? `${baseUrl}/` : '/';
    }
    return `${baseUrl}/${paginaItem.value?.slug || ''}`;
});
</script>

<template>
    <Head :title="paginaItem?.is_homepage
        ? ($page.props.siteSettings?.site_name || paginaItem?.seo_title || paginaItem?.title || 'Inicio')
        : `${paginaItem?.seo_title || paginaItem?.title || 'Página'} — ${$page.props.siteSettings?.site_name || 'CMS'}`"
    >
        <meta name="description" :content="metaDescripcion" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta name="robots" :content="paginaItem?.meta_robots || 'index, follow, max-image-preview:large'" />
    </Head>

    <!-- Si tiene maquetación visual del Page Builder, renderizar el layout completo -->
    <template v-if="tieneLayoutBuilder">
        <PageBuilderRenderer :layout="paginaItem.layout_json" />
    </template>

    <!-- Si es una página estándar de texto HTML tradicional -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Breadcrumb -->
        <nav v-if="breadcrumb.length" class="mb-6 text-sm text-zinc-500">
            <template v-for="(item, index) in breadcrumb" :key="index">
                <Link v-if="index < breadcrumb.length - 1" :href="item.url" class="hover:text-emerald-600 transition-colors">
                    {{ item.name }}
                </Link>
                <span v-else class="text-zinc-900 dark:text-white font-medium">{{ item.name }}</span>
                <span v-if="index < breadcrumb.length - 1" class="mx-2">/</span>
            </template>
        </nav>

        <!-- Título de la página -->
        <header class="mb-8 border-b border-zinc-200 dark:border-zinc-800 pb-6">
            <h1
                :class="[pageTitleClasses, 'font-bold text-zinc-900 dark:text-zinc-100']"
                :style="pageTitleStyle"
            >
                {{ paginaItem.title }}
            </h1>
        </header>

        <!-- Contenido principal HTML -->
        <article class="prose dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 leading-relaxed space-y-4">
            <div v-if="paginaItem.body" v-html="bodySeguro" />
            <div v-else class="text-center py-12 text-zinc-400 italic">
                Esta página no contiene texto disponible.
            </div>
        </article>
    </div>
</template>

