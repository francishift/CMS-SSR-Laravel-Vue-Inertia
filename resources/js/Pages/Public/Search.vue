<script setup>
/**
 * Vista de Búsqueda Pública de Contenidos del CMS.
 *
 * Muestra los resultados de búsqueda de artículos con paginación SPA,
 * buscador interactivo en la cabecera y tarjetas de contenido responsivas.
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import ArticleCard from '@/Frontend/Components/ArticleCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    query: { type: String, default: '' },
    articulos: { type: Object, default: () => ({ data: [] }) },
    breadcrumb: { type: Array, default: () => [] },
});

const searchQuery = ref(props.query);

function buscar() {
    if (!searchQuery.value.trim()) return;
    router.get('/buscar', { q: searchQuery.value.trim() }, { preserveState: true });
}

function irAPagina(url) {
    if (!url) return;
    router.get(url, {}, { preserveState: true });
}
</script>

<template>
    <Head :title="`Resultados para '${query}' — ${$page.props.siteSettings?.site_name || 'CMS'}`">
        <meta name="robots" content="noindex, follow" />
    </Head>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
        <!-- Breadcrumb -->
        <nav v-if="breadcrumb.length" class="text-sm text-zinc-500">
            <template v-for="(item, index) in breadcrumb" :key="index">
                <Link v-if="index < breadcrumb.length - 1" :href="item.url" class="hover:text-emerald-600 transition-colors">
                    {{ item.name }}
                </Link>
                <span v-else class="text-zinc-900 dark:text-white font-medium">{{ item.name }}</span>
                <span v-if="index < breadcrumb.length - 1" class="mx-2">/</span>
            </template>
        </nav>

        <!-- Cabecera de búsqueda -->
        <header class="space-y-4">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight" style="font-family: var(--heading-font), system-ui, sans-serif;">
                <span v-if="query">Resultados de búsqueda para "<span class="text-emerald-600 dark:text-emerald-400">{{ query }}</span>"</span>
                <span v-else>Buscador de Contenidos</span>
            </h1>
            <p v-if="query && articulos.meta?.total !== undefined" class="text-sm text-zinc-500 dark:text-zinc-400">
                Se han encontrado {{ articulos.meta.total }} {{ articulos.meta.total === 1 ? 'resultado' : 'resultados' }}.
            </p>
        </header>

        <!-- Formulario de Búsqueda -->
        <form @submit.prevent="buscar" class="max-w-2xl relative">
            <div class="relative flex items-center">
                <i class="pi pi-search absolute left-4 text-zinc-400 dark:text-zinc-500 text-lg" />
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Escribe tu búsqueda aquí..."
                    class="w-full pl-11 pr-28 py-3.5 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all shadow-xs"
                />
                <button
                    type="submit"
                    class="absolute right-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer"
                >
                    Buscar
                </button>
            </div>
        </form>

        <!-- Listado de Artículos Encontrados -->
        <div v-if="articulos.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
            <ArticleCard
                v-for="articulo in articulos.data"
                :key="articulo.id"
                :articulo="articulo"
                :destacado="false"
                class="h-full"
            />
        </div>

        <!-- Paginación de resultados -->
        <div v-if="articulos.meta?.last_page > 1" class="pt-6 flex justify-center items-center gap-1.5 flex-wrap">
            <template v-for="(link, index) in articulos.meta.links" :key="index">
                <button
                    v-if="link.url"
                    type="button"
                    @click="irAPagina(link.url)"
                    :class="[
                        'px-4 py-2 text-xs rounded-xl transition-all font-bold cursor-pointer border',
                        link.active
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-md scale-105'
                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-4 py-2 text-sm text-zinc-500 bg-zinc-200/50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl cursor-not-allowed select-none opacity-60"
                    v-html="link.label"
                />
            </template>
        </div>

        <!-- Estado Vacío (Sin resultados) -->
        <div v-if="!articulos.data?.length" class="text-center py-16 bg-zinc-50/50 dark:bg-zinc-900/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 p-8 max-w-xl mx-auto space-y-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400">
                <i class="pi pi-search text-2xl" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">No se encontraron resultados</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Intenta buscar con otros términos o comprueba la ortografía de las palabras clave.</p>
            </div>
            <div>
                <Link href="/" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-600 hover:underline">
                    <i class="pi pi-arrow-left" />
                    <span>Volver a la página principal</span>
                </Link>
            </div>
        </div>
    </div>
</template>
