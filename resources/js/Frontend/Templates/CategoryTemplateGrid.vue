<script setup>
/**
 * Plantilla de categoría en formato Cuadrícula (Grid) para el Frontend.
 * Soporta columnas para móvil, tablet y escritorio, margen entre tarjetas y alineación horizontal real (Centrado/Inicio/Fin).
 * Paginación sutil y ultra-suave SPA (sin recarga completa de página ni saltos de scroll).
 */
import ArticleCard from '@/Frontend/Components/ArticleCard.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import { desplazarSuaveConOffset } from '@/Composables/useScrollAnimation';

const props = defineProps({
    articulos: { type: Object, required: true },
    categoria: { type: Object, required: true },
});

const isChangingPage = ref(false);
const gridContainerRef = ref(null);

let removeStartHook;
let removeFinishHook;

onMounted(() => {
    removeStartHook = router.on('start', (event) => {
        if (event.detail.visit.only?.includes('articulos')) {
            isChangingPage.value = true;
        }
    });
    removeFinishHook = router.on('finish', () => {
        isChangingPage.value = false;
        nextTick(() => {
            if (gridContainerRef.value) {
                desplazarSuaveConOffset(gridContainerRef.value);
            }
        });
    });
});

onUnmounted(() => {
    if (removeStartHook) removeStartHook();
    if (removeFinishHook) removeFinishHook();
});

const mobileMap = { 1: 'grid-cols-1', 2: 'grid-cols-2' };
const tabletMap = { 1: 'sm:grid-cols-1', 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-3', 4: 'sm:grid-cols-4' };
const desktopMap = { 1: 'lg:grid-cols-1', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4', 5: 'lg:grid-cols-5', 6: 'lg:grid-cols-6' };
const gapMap = { sm: 'gap-3', md: 'gap-6', lg: 'gap-8', xl: 'gap-12' };

const isFlexLayout = computed(() => ['center', 'end'].includes(props.categoria?.align_grid));

const gapPx = computed(() => {
    const map = { sm: 12, md: 24, lg: 32, xl: 48 };
    return map[props.categoria?.gap_size] || 24;
});

const colsMob = computed(() => Number(props.categoria?.cols_mobile || 1));
const colsTab = computed(() => Number(props.categoria?.cols_tablet || 2));
const colsDesk = computed(() => Number(props.categoria?.cols_desktop || 3));

const gridContainerClasses = computed(() => {
    const gap = gapMap[props.categoria?.gap_size] || 'gap-6';

    if (isFlexLayout.value) {
        const justifyClass = props.categoria?.align_grid === 'center' ? 'justify-center' : 'justify-end';
        return ['flex flex-wrap items-stretch', gap, justifyClass];
    }

    const mob = mobileMap[colsMob.value] || 'grid-cols-1';
    const tab = tabletMap[colsTab.value] || 'sm:grid-cols-2';
    const desk = desktopMap[colsDesk.value] || 'lg:grid-cols-3';

    return ['grid', mob, tab, desk, gap];
});

const itemStyle = computed(() => {
    if (!isFlexLayout.value) return {};
    return {
        '--cols-mob': colsMob.value,
        '--cols-tab': colsTab.value,
        '--cols-desk': colsDesk.value,
        '--gap-px': `${gapPx.value}px`,
    };
});

const paginationLinks = computed(() => {
    const raw = Array.isArray(props.articulos?.meta?.links)
        ? props.articulos.meta.links
        : (Array.isArray(props.articulos?.links) ? props.articulos.links : []);

    const slug = props.categoria?.slug || '';

    return raw.map((link) => {
        let linkUrl = link.url;
        if (linkUrl && linkUrl.includes('?page=')) {
            const match = linkUrl.match(/[?&]page=(\d+)/);
            if (match) {
                const p = parseInt(match[1], 10);
                linkUrl = p <= 1 ? (slug ? `/${slug}` : '/') : (slug ? `/${slug}/pagina/${p}` : `/pagina/${p}`);
            }
        }
        return {
            ...link,
            url: linkUrl,
        };
    });
});

const mostrarPaginacion = computed(() => {
    if (props.categoria?.show_pagination === false) return false;
    const lastPage = props.articulos?.meta?.last_page || props.articulos?.last_page || 1;
    return lastPage > 1;
});

const hacerScrollAlGrid = () => {
    nextTick(() => {
        if (gridContainerRef.value) {
            desplazarSuaveConOffset(gridContainerRef.value);
        }
    });
};
</script>

<template>
    <div ref="gridContainerRef" class="space-y-8 relative scroll-mt-24 sm:scroll-mt-28">
        <!-- Listado en cuadrícula de artículos con animación de entrada elegante -->
        <div :key="articulos.current_page || 'page-1'" :class="[gridContainerClasses, 'animate-grid-fade-in']">
            <div
                v-for="(articulo, idx) in articulos.data"
                :key="articulo.id"
                :class="isFlexLayout ? 'dynamic-flex-item' : 'w-full'"
                :style="{ ...itemStyle, animationDelay: `${idx * 30}ms` }"
                class="animate-card-pop"
            >
                <ArticleCard
                    :articulo="articulo"
                    :categoria-slug="categoria.slug"
                    :title-tag="categoria.title_tag || 'h2'"
                    :destacado="true"
                    class="h-full"
                />
            </div>
        </div>

        <!-- Paginación pública SPA elegante con micro-interacciones (SEO Friendly con <Link>) -->
        <div v-if="mostrarPaginacion" class="mt-12 flex justify-center items-center gap-1.5 flex-wrap">
            <template v-for="(link, index) in paginationLinks" :key="index">
                <span
                    v-if="link.active"
                    :style="{ backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))', color: 'var(--btn-primary-text, #ffffff)', borderColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))' }"
                    class="px-4 py-2 text-xs rounded-xl font-bold border shadow-md scale-105 ring-2 ring-[var(--primary-color,#10b981)]/30 flex items-center justify-center cursor-default select-none"
                    v-html="link.label"
                />
                <Link
                    v-else-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :only="['articulos', 'categoria', 'breadcrumb']"
                    class="px-4 py-2 text-xs rounded-xl transition-all duration-200 font-bold cursor-pointer border active:scale-95 shadow-2xs no-underline flex items-center justify-center bg-zinc-100 dark:bg-zinc-800/80 text-zinc-800 dark:text-zinc-200 border-zinc-200/80 dark:border-zinc-700/80 hover:bg-zinc-200 dark:hover:bg-zinc-700"
                    @click="hacerScrollAlGrid"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-zinc-200/80 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl cursor-not-allowed font-semibold select-none opacity-80 flex items-center justify-center"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>

<style scoped>
@keyframes gridFadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-grid-fade-in {
    animation: gridFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes cardPop {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-card-pop {
    animation: cardPop 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.dynamic-flex-item {
    width: 100%;
    box-sizing: border-box;
}

@media (min-width: 640px) {
    .dynamic-flex-item {
        width: calc((100% - (var(--gap-px) * (var(--cols-tab) - 1))) / var(--cols-tab));
        max-width: calc((100% - (var(--gap-px) * (var(--cols-tab) - 1))) / var(--cols-tab));
    }
}

@media (min-width: 1024px) {
    .dynamic-flex-item {
        width: calc((100% - (var(--gap-px) * (var(--cols-desk) - 1))) / var(--cols-desk));
        max-width: calc((100% - (var(--gap-px) * (var(--cols-desk) - 1))) / var(--cols-desk));
    }
}
</style>
