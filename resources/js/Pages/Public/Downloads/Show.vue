<script setup>
/**
 * Ficha Individual Pública SSR de un Recurso Descargable (`/{catalogSlug}/{itemSlug}`).
 *
 * Soporta dos modos para la columna principal (8/12):
 * 1. Plantilla Visual del Page Builder (`catalogo.item_layout_json`) con variables `{download.*}`.
 * 2. Diseño editorial limpio estándar por defecto.
 * En ambos modos mantiene el bloque fijo (Sticky a `header + 20px`) de Descarga y Ficha Técnica a la derecha.
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';
import DownloadCard from '@/Frontend/Components/DownloadCard.vue';
import PageBuilderRenderer from '@/Frontend/Builder/PageBuilderRenderer.vue';
import DownloadDetailAside from '@/Pages/Public/Downloads/Partials/DownloadDetailAside.vue';
import { Head, Link, usePage, Deferred } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSanitize } from '@/Composables/useSanitize';
import { getRutaInterna } from '@/Composables/useUrlHelpers';

defineOptions({ layout: PublicLayout });

const page = usePage();
const { sanitize } = useSanitize();

const props = defineProps({
    catalogo: { type: Object, required: true },
    descarga: { type: Object, required: true },
    breadcrumb: { type: Array, default: () => [] },
    esPrevisualizacionAdmin: { type: Boolean, default: false },
    relacionadas: { type: Array, default: undefined },
});

const usaPlantillaBuilderItem = computed(() =>
    Boolean(props.catalogo.use_item_builder_layout && props.catalogo.item_layout_json?.sections?.length)
);

// Primera sección en la columna 8/12 (junto a la caja fija); secciones siguientes a ancho completo debajo
const layoutPrincipalItem = computed(() => ({
    ...(props.catalogo.item_layout_json || {}),
    sections: (props.catalogo.item_layout_json?.sections || []).slice(0, 1),
}));

const layoutInferiorItem = computed(() => ({
    ...(props.catalogo.item_layout_json || {}),
    sections: (props.catalogo.item_layout_json?.sections || []).slice(1),
}));

const urlBuilderItemConMuestra = computed(() => {
    if (!props.catalogo.admin_item_builder_url) return null;
    return `${props.catalogo.admin_item_builder_url}?preview_download_id=${props.descarga.id}`;
});

const pageTitle = computed(() => {
    const base = props.descarga.seo_title || props.descarga.title;
    const siteName = page.props.siteSettings?.site_name || 'CMS';
    return `${base} — ${siteName}`;
});

const metaDescription = computed(() => {
    return props.descarga.seo_description
        || props.descarga.short_description
        || page.props.siteSettings?.seo_default_description
        || props.descarga.title;
});

const robotsContent = computed(() => {
    if (props.esPrevisualizacionAdmin) return 'noindex, nofollow';
    return props.descarga.meta_robots || 'index, follow, max-image-preview:large';
});

const bodySanitizado = computed(() => sanitize(props.descarga.body || ''));
const esImagenSvg = computed(() => {
    const src = (props.descarga.featured_image || '').split('?')[0].split('#')[0].toLowerCase();
    return src.endsWith('.svg');
});

// Mostrar únicamente los niveles superiores de navegación (ej. Inicio / Descargas), sin repetir el título actual
const migasPadre = computed(() => {
    if (!Array.isArray(props.breadcrumb) || props.breadcrumb.length === 0) return [];
    return props.breadcrumb.length > 1 ? props.breadcrumb.slice(0, -1) : props.breadcrumb;
});
</script>

<template>
    <Head :title="pageTitle">
        <meta name="description" :content="metaDescription" />
        <link rel="canonical" :href="descarga.url" />
        <meta name="robots" :content="robotsContent" />
    </Head>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <!-- Aviso de previsualización privada (cuando está en borrador o Solo Backend) -->
        <div
            v-if="esPrevisualizacionAdmin"
            class="mb-6 rounded-2xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 px-4 py-3 text-xs sm:text-sm text-amber-900 dark:text-amber-200 flex flex-wrap items-center justify-between gap-2"
        >
            <div class="flex items-center gap-2 font-medium">
                <i class="pi pi-lock text-amber-600 dark:text-amber-400" />
                <span>
                    Vista previa privada ({{ descarga.status === 'draft' ? 'Borrador' : 'Catálogo Solo Backend' }}) — Oculto para visitantes anónimos.
                </span>
            </div>
            <div class="flex items-center gap-3">
                <a v-if="urlBuilderItemConMuestra" :href="urlBuilderItemConMuestra" class="font-bold underline hover:opacity-80 text-inherit">
                    Maquetar en Builder
                </a>
                <a v-if="descarga.admin_edit_url" :href="descarga.admin_edit_url" class="font-bold underline hover:opacity-80 text-inherit">
                    Editar textos y ZIP
                </a>
            </div>
        </div>

        <!-- Barra rápida discreta para Administradores cuando la ficha es pública -->
        <div
            v-else-if="descarga.admin_edit_url || urlBuilderItemConMuestra"
            class="mb-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/90 dark:bg-zinc-900/80 px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-300 flex flex-wrap items-center justify-between gap-2"
        >
            <div class="flex items-center gap-2 font-medium">
                <i class="pi pi-wrench text-emerald-600 dark:text-emerald-400" />
                <span>Acciones rápidas de Administrador:</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    v-if="descarga.admin_edit_url"
                    :href="descarga.admin_edit_url"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 hover:border-emerald-500 transition-colors no-underline"
                >
                    <i class="pi pi-pencil text-[11px]" />
                    <span>Editar textos / ZIP</span>
                </a>
                <a
                    v-if="urlBuilderItemConMuestra"
                    :href="urlBuilderItemConMuestra"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-colors no-underline"
                >
                    <i class="pi pi-palette text-[11px]" />
                    <span>Maquetar Ficha en Builder</span>
                </a>
            </div>
        </div>

        <!-- Migas de pan (solo ruta padre: Inicio / Descargas, sin repetir el nombre del artículo) -->
        <nav v-if="catalogo.show_breadcrumb !== false && migasPadre.length" aria-label="Breadcrumb" class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
            <template v-for="(crumb, idx) in migasPadre" :key="idx">
                <Link
                    :href="getRutaInterna(crumb.url)"
                    :class="[
                        'hover:text-[var(--primary-color,#10b981)] transition-colors no-underline text-inherit',
                        idx === migasPadre.length - 1 ? 'font-medium text-zinc-700 dark:text-zinc-200' : '',
                    ]"
                >
                    {{ crumb.name }}
                </Link>
                <span v-if="idx < migasPadre.length - 1" class="mx-2 opacity-60">/</span>
            </template>
        </nav>

        <!-- Diseño principal de 2 columnas (Contenido Editable 8/12 + Caja Fija de Descarga 4/12) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
            <!-- Columna Principal (8/12): Primera Sección de la Plantilla Builder o Vista Editorial Estándar -->
            <div class="lg:col-span-8 space-y-8 min-w-0">
                <template v-if="usaPlantillaBuilderItem">
                    <PageBuilderRenderer :layout="layoutPrincipalItem" :key="`dl-item-builder-main-${descarga.id}`" />
                </template>

                <template v-else>
                    <header class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                v-if="descarga.compatibility"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300"
                            >
                                <i :class="[descarga.icon || 'pi pi-puzzle', 'text-xs text-[var(--primary-color,#10b981)]']" />
                                {{ descarga.compatibility }}
                            </span>
                            <span
                                v-if="descarga.version"
                                class="px-2.5 py-1 rounded-full text-xs font-mono font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300"
                            >
                                Versión {{ descarga.version }}
                            </span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight" style="font-family: var(--heading-font), system-ui, sans-serif;">
                            {{ descarga.title }}
                        </h1>

                        <small v-if="descarga.subtitle" class="block text-sm font-medium text-zinc-500 dark:text-zinc-400 leading-snug -mt-1">
                            {{ descarga.subtitle }}
                        </small>

                        <p v-if="descarga.short_description" class="text-lg text-zinc-600 dark:text-zinc-300 leading-relaxed">
                            {{ descarga.short_description }}
                        </p>
                    </header>

                    <!-- Imagen destacada representativa o ilustración vectorial SVG -->
                    <div
                        v-if="descarga.featured_image && esImagenSvg"
                        class="rounded-2xl overflow-hidden border border-zinc-200/80 dark:border-zinc-800 bg-gradient-to-br from-zinc-50 via-zinc-100/70 to-zinc-200/50 dark:from-zinc-900 dark:via-zinc-800/70 dark:to-zinc-900 py-10 px-6 flex items-center justify-center shadow-2xs"
                    >
                        <img
                            :src="descarga.featured_image"
                            :alt="descarga.title"
                            width="128"
                            height="128"
                            loading="eager"
                            decoding="async"
                            class="w-28 h-28 sm:w-32 sm:h-32 object-contain dark:invert dark:opacity-90"
                        />
                    </div>
                    <div
                        v-else-if="descarga.featured_image"
                        class="rounded-2xl overflow-hidden border border-zinc-200/80 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900 shadow-sm"
                    >
                        <ResponsiveImage
                            :src="descarga.featured_image"
                            :alt="descarga.title"
                            sizes="(max-width: 1024px) 100vw, 66vw"
                            loading="eager"
                            img-class="w-full h-auto object-cover"
                        />
                    </div>

                    <!-- Descripción detallada maquetada -->
                    <div
                        v-if="bodySanitizado"
                        class="prose prose-zinc dark:prose-invert max-w-none leading-relaxed"
                        v-html="bodySanitizado"
                    />

                    <!-- Notas de versión / Changelog (solo en vista clásica; en modo Builder se gestiona desde la plantilla) -->
                    <section
                        v-if="descarga.changelog"
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-900/60 p-6 space-y-3"
                    >
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2" style="font-family: var(--heading-font), system-ui, sans-serif;">
                            <i class="pi pi-history text-base text-[var(--primary-color,#10b981)]" />
                            <span>Historial de cambios (Changelog)</span>
                        </h2>
                        <pre class="text-xs sm:text-sm font-mono whitespace-pre-wrap text-zinc-700 dark:text-zinc-300 m-0 leading-relaxed">{{ descarga.changelog }}</pre>
                    </section>
                </template>
            </div>

            <!-- Barra Lateral Sticky de Descarga y Especificaciones (4/12 a header + 20px) -->
            <DownloadDetailAside :descarga="descarga" />
        </div>

        <!-- Otras descargas relacionadas (solo como respaldo cuando NO se usa la Plantilla del Builder) -->
        <Deferred v-if="!usaPlantillaBuilderItem" data="relacionadas">
            <template #fallback>
                <div class="mt-16" />
            </template>

            <section v-if="relacionadas && relacionadas.length" class="mt-16 pt-10 border-t border-zinc-200 dark:border-zinc-800 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100" style="font-family: var(--heading-font), system-ui, sans-serif;">
                        Más en {{ catalogo.page_title }}
                    </h2>
                    <Link
                        :href="getRutaInterna(catalogo.public_url)"
                        class="text-sm font-semibold hover:underline no-underline"
                        style="color: var(--primary-color, #10b981);"
                    >
                        Ver todo el catálogo →
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <DownloadCard
                        v-for="rel in relacionadas"
                        :key="rel.id"
                        :item="rel"
                        view-mode="grid"
                        :show-quick-download="catalogo.show_quick_download"
                    />
                </div>
            </section>
        </Deferred>
    </div>

    <!-- Secciones adicionales configuradas en el Page Builder debajo del bloque principal (ancho completo editable) -->
    <PageBuilderRenderer
        v-if="usaPlantillaBuilderItem && layoutInferiorItem.sections.length"
        :layout="layoutInferiorItem"
        :index-offset="1"
        :key="`dl-item-builder-bottom-${descarga.id}`"
    />
</template>
