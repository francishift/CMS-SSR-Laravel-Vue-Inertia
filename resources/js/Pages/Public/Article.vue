<script setup>
/**
 * Vista de detalle de artículo público.
 *
 * Muestra breadcrumb, contenido completo, campos extra formateados elegantemente,
 * metadatos (autor, fecha, tiempo de lectura) y artículos relacionados.
 * Layout público persistente para navegación SPA fluida (sin recargar header/footer).
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

import { computed } from 'vue';
import { useSanitize } from '@/Composables/useSanitize';
import { useSingleItemExpire } from '@/Composables/useAutoExpire';
import { imageUrl } from '@/Composables/useImage';

import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';
import PageBuilderRenderer from '@/Frontend/Builder/PageBuilderRenderer.vue';

defineOptions({ layout: PublicLayout });

const page = usePage();

const props = defineProps({
    articulo: { type: Object, required: true },
    relacionados: { type: Object, default: () => ({ data: [] }) },
    navegacion: { type: Object, default: () => ({ anterior: null, siguiente: null }) },
    breadcrumb: { type: Array, default: () => [] },
});

const articuloItem = computed(() => {
    const raw = props.articulo;
    return raw?.data || raw || {};
});

// Detección reactiva de expiración de publicación en tiempo real (Zero Polling)
const { isExpired } = useSingleItemExpire(() => articuloItem.value?.published_until_iso);

// Sanitización XSS del cuerpo del artículo
const { sanitize } = useSanitize();
const bodySeguro = computed(() => sanitize(articuloItem.value.body || ''));

function getLabelCampo(key) {
    if (!articuloItem.value?.category?.extra_fields_schema) {
        return key.replace(/_/g, ' ');
    }
    const schema = articuloItem.value.category.extra_fields_schema;
    const item = Array.isArray(schema) ? schema.find(s => s.key === key) : null;
    return item?.label || key.replace(/_/g, ' ');
}

function getTipoCampo(key) {
    if (!articuloItem.value?.category?.extra_fields_schema) return 'text';
    const schema = articuloItem.value.category.extra_fields_schema;
    const item = Array.isArray(schema) ? schema.find(s => s.key === key) : null;
    return item?.type || 'text';
}

function esUrl(val) {
    if (typeof val !== 'string') return false;
    return val.startsWith('http://') || val.startsWith('https://');
}

function esBooleano(val) {
    return typeof val === 'boolean' || val === 'true' || val === 'false';
}

function esImagen(val, key) {
    if (typeof val !== 'string' || !val) return false;
    const tipo = getTipoCampo(key);
    if (tipo === 'image') return true;
    const ext = val.split('.').pop()?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'webp', 'avif', 'svg', 'gif'].includes(ext) || val.includes('/storage/') || val.includes('/media/');
}

function esColor(val, key) {
    if (typeof val !== 'string' || !val) return false;
    const tipo = getTipoCampo(key);
    return tipo === 'color' || /^#?(?:[0-9a-fA-F]{3}){1,2}$/.test(val.trim());
}

function normalizarHexColor(val) {
    if (!val || typeof val !== 'string') return '#000000';
    const clean = val.trim();
    if (clean.startsWith('#')) return clean;
    if (/^(?:[0-9a-fA-F]{3}){1,2}$/.test(clean)) {
        return '#' + clean;
    }
    return clean;
}

function esFecha(val, key) {
    if (!val) return false;
    const tipo = getTipoCampo(key);
    if (['date', 'datetime', 'time'].includes(tipo)) return true;
    if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}/.test(val)) return true;
    return false;
}

function formatearFechaEspanol(val, key) {
    if (!val) return '';
    const tipo = getTipoCampo(key);

    // 1. Caso 'time': Solo hora (HH:mm)
    if (tipo === 'time') {
        if (typeof val === 'string' && val.includes('T')) {
            const d = new Date(val);
            if (!isNaN(d.getTime())) {
                return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            }
        }
        return val;
    }

    try {
        const dateObj = new Date(val);
        if (isNaN(dateObj.getTime())) return val;

        // 2. Caso 'date': Solo fecha (DD/MM/YYYY) — NUNCA mostrar la hora
        if (tipo === 'date') {
            return new Intl.DateTimeFormat('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            }).format(dateObj);
        }

        // 3. Caso 'datetime': Fecha y Hora (DD/MM/YYYY, HH:mm)
        if (tipo === 'datetime') {
            return new Intl.DateTimeFormat('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }).format(dateObj);
        }

        // Fallback para fechas genéricas en formato YYYY-MM-DD
        if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
            return new Intl.DateTimeFormat('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                timeZone: 'UTC',
            }).format(dateObj);
        }

        return new Intl.DateTimeFormat('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(dateObj);
    } catch (e) {
        return val;
    }
}

const camposExtraFiltrados = computed(() => {
    const data = articuloItem.value?.extra_fields_data;
    if (!data || typeof data !== 'object') return {};

    const schema = articuloItem.value?.category?.extra_fields_schema;
    if (!Array.isArray(schema) || !schema.length) return {};

    const allowedKeys = new Set(schema.map(s => s.key).filter(Boolean));
    const result = {};
    for (const [key, val] of Object.entries(data)) {
        if (allowedKeys.has(key) && val !== null && val !== undefined && val !== '') {
            result[key] = val;
        }
    }
    return result;
});

function esIcono(val, key) {
    if (typeof val !== 'string') return false;
    const tipo = getTipoCampo(key);
    return tipo === 'icon' || val.startsWith('pi pi-');
}
const metaDescArt = computed(() => {
    return articuloItem.value?.seo_description
        || articuloItem.value?.excerpt
        || page.props.siteSettings?.seo_default_description
        || page.props.siteSettings?.site_description
        || articuloItem.value?.title;
});

const canonicalUrl = computed(() => {
    const baseUrl = page.props.appUrl || page.props.ziggy?.url || '';
    const catSlug = articuloItem.value?.category?.slug;
    const artSlug = articuloItem.value?.slug;
    if (catSlug && artSlug) {
        return `${baseUrl}/${catSlug}/${artSlug}`;
    }
    return artSlug ? `${baseUrl}/${artSlug}` : baseUrl;
});
</script>

<template>
    <Head :title="`${articuloItem?.seo_title || articuloItem?.title || 'Artículo'} — ${$page.props.siteSettings?.site_name || 'CMS'}`">
        <meta name="description" :content="metaDescArt" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta name="robots" :content="articuloItem?.meta_robots || 'index, follow, max-image-preview:large'" />
    </Head>

    <!-- Vista Maquetada con Page Builder (Si tiene plantilla activa asignada, con key por artículo para cancelar descargas multimedia previas en SPA) -->
    <PageBuilderRenderer
        v-if="articuloItem.layout_json?.sections?.length"
        :key="`art-builder-${articuloItem.id || articuloItem.slug}`"
        :layout="articuloItem.layout_json"
    />

    <!-- Vista Estándar Clásica del Sistema (Si no tiene plantilla asignada) -->
    <template v-else>
        <article class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Banner de Aviso Reactivo si el Artículo Expira en Tiempo Real -->
        <transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="transform -translate-y-4 opacity-0 scale-95"
            enter-to-class="transform translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 scale-100"
            leave-to-class="transform -translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="isExpired"
                class="mb-8 p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 dark:bg-amber-950/40 dark:border-amber-700/50 backdrop-blur-md shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 dark:bg-amber-500/30 flex items-center justify-center shrink-0 text-amber-600 dark:text-amber-400 text-lg shadow-2xs">
                        <i class="pi pi-clock" />
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-amber-900 dark:text-amber-200">
                            Esta publicación ha finalizado su período de disponibilidad
                        </h3>
                        <p class="text-xs sm:text-sm text-amber-800/80 dark:text-amber-300/80 mt-0.5">
                            El contenido ya no se encuentra accesible públicamente para nuevos visitantes.
                        </p>
                    </div>
                </div>
                <Link
                    :href="articuloItem.category ? `/${articuloItem.category.slug}` : '/'"
                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 text-white font-semibold text-xs transition-all shadow-xs cursor-pointer"
                >
                    <i class="pi pi-arrow-left text-xs" />
                    <span>{{ articuloItem.category ? `Ver más en ${articuloItem.category.name}` : 'Volver al Inicio' }}</span>
                </Link>
            </div>
        </transition>

        <!-- Breadcrumb (Según configuración de categoría) -->
        <nav v-if="(articuloItem.category?.show_breadcrumb !== false) && breadcrumb.length" class="mb-6 text-sm text-zinc-500">
            <template v-for="(item, index) in breadcrumb" :key="index">
                <Link v-if="index < breadcrumb.length - 1" :href="item.url" class="hover:text-emerald-600 transition-colors">
                    {{ item.name }}
                </Link>
                <span v-else class="text-zinc-900 dark:text-white font-medium">{{ item.name }}</span>
                <span v-if="index < breadcrumb.length - 1" class="mx-2">/</span>
            </template>
        </nav>

        <!-- Cabecera del artículo -->
        <header class="mb-8 space-y-4">
            <!-- Insignia / Enlace a la categoría estilo Editorial Premium -->
            <div v-if="articuloItem.category && (articuloItem.category.show_category_link !== false)">
                <Link
                    :href="`/${articuloItem.category.slug}`"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold tracking-wider uppercase bg-emerald-500/10 dark:bg-emerald-400/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 dark:border-emerald-400/20 hover:bg-emerald-500/20 hover:border-emerald-500/40 backdrop-blur-xs transition-all shadow-xs group cursor-pointer"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse" />
                    <i v-if="articuloItem.category.icon" :class="[articuloItem.category.icon, 'text-xs']" />
                    <span>{{ articuloItem.category.name }}</span>
                    <i class="pi pi-arrow-right text-[9px] opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" />
                </Link>
            </div>

            <!-- Título principal -->
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-zinc-900 dark:text-zinc-100 tracking-tight leading-tight hyphens-none [word-break:keep-all]" style="font-family: var(--heading-font), system-ui, sans-serif;">
                {{ articuloItem.title }}
            </h1>

            <!-- Barra de Metadatos -->
            <div class="pt-2 flex flex-wrap items-center gap-y-2 gap-x-4 text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                <span v-if="(articuloItem.category?.show_author !== false) && articuloItem.author" class="flex items-center gap-1.5">
                    <i class="pi pi-user text-xs text-emerald-600 dark:text-emerald-400" />
                    <span>{{ articuloItem.author.name }}</span>
                </span>
                <span v-if="(articuloItem.category?.show_author !== false) && articuloItem.author && (articuloItem.category?.show_date !== false && articuloItem.published_at)" class="text-zinc-300 dark:text-zinc-700">•</span>

                <span v-if="(articuloItem.category?.show_date !== false) && articuloItem.published_at" class="flex items-center gap-1.5">
                    <i class="pi pi-calendar text-xs text-emerald-600 dark:text-emerald-400" />
                    <span>{{ articuloItem.published_at }}</span>
                </span>
                <span v-if="(articuloItem.category?.show_date !== false) && articuloItem.published_at && (articuloItem.category?.show_reading_time !== false && articuloItem.reading_time)" class="text-zinc-300 dark:text-zinc-700">•</span>

                <span v-if="(articuloItem.category?.show_reading_time !== false) && articuloItem.reading_time" class="flex items-center gap-1.5">
                    <i class="pi pi-clock text-xs text-emerald-600 dark:text-emerald-400" />
                    <span>{{ articuloItem.reading_time }} min de lectura</span>
                </span>
            </div>
        </header>

        <!-- Imagen destacada responsiva y optimizada (Elemento LCP prioritario) -->
        <div v-if="articuloItem.featured_image" class="mb-8 rounded-xl overflow-hidden">
            <ResponsiveImage
                :src="articuloItem.featured_image"
                :alt="articuloItem.title"
                :width="1600"
                fit="scale"
                loading="eager"
                fetchpriority="high"
                sizes="(max-width: 768px) 100vw, (max-width: 1280px) 90vw, 1200px"
                imgClass="w-full h-auto object-cover max-h-[520px] rounded-xl"
            />
        </div>

        <!-- Contenido del artículo -->
        <div
            v-if="bodySeguro"
            class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-a:text-emerald-600 prose-img:rounded-lg"
            v-html="bodySeguro"
        />

        <!-- Campos extra dinámicos -->
        <section v-if="Object.keys(camposExtraFiltrados).length" class="mt-12 p-6 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800 space-y-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="pi pi-list text-emerald-600 dark:text-emerald-400" />
                <span>Información adicional</span>
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="(value, key) in camposExtraFiltrados"
                    :key="key"
                    class="p-3.5 bg-white dark:bg-zinc-800/80 rounded-lg border border-zinc-200/80 dark:border-zinc-700/60 shadow-2xs"
                >
                    <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                        {{ getLabelCampo(key) }}
                    </dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100 break-words mt-1">
                        <template v-if="esImagen(value, key)">
                            <img :src="imageUrl(value, { w: 400, q: 85, f: 'webp' })" :alt="getLabelCampo(key)" class="max-h-36 rounded-lg border border-zinc-200 dark:border-zinc-700 object-cover shadow-2xs" loading="lazy" />
                        </template>
                        <template v-else-if="esColor(value, key)">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full border border-zinc-300 dark:border-zinc-600 inline-block shadow-2xs shrink-0" :style="{ backgroundColor: normalizarHexColor(value) }" />
                                <span class="font-mono text-xs">{{ normalizarHexColor(value) }}</span>
                            </div>
                        </template>
                        <template v-else-if="esIcono(value, key)">
                            <div class="flex items-center gap-2">
                                <i :class="[value, 'text-lg text-emerald-600 dark:text-emerald-400']" />
                                <span class="text-xs font-mono text-zinc-500">{{ value }}</span>
                            </div>
                        </template>
                        <template v-else-if="getTipoCampo(key) === 'wysiwyg'">
                            <div class="prose prose-sm dark:prose-invert max-w-none text-xs" v-html="sanitize(value)" />
                        </template>
                        <template v-else-if="esFecha(value, key)">
                            <div class="flex items-center gap-1.5 font-medium text-zinc-800 dark:text-zinc-200">
                                <i class="pi pi-calendar text-xs text-emerald-600 dark:text-emerald-400" />
                                <span>{{ formatearFechaEspanol(value, key) }}</span>
                            </div>
                        </template>
                        <template v-else-if="esUrl(value)">
                            <a :href="value" target="_blank" rel="noopener noreferrer" class="text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1">
                                <span class="truncate max-w-[220px]">{{ value }}</span>
                                <i class="pi pi-external-link text-xs shrink-0" />
                            </a>
                        </template>
                        <template v-else-if="esBooleano(value)">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold',
                                    (value === true || value === 'true')
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                ]"
                            >
                                {{ (value === true || value === 'true') ? 'Sí' : 'No' }}
                            </span>
                        </template>
                        <template v-else>
                            {{ value }}
                        </template>
                    </dd>
                </div>
            </dl>
        </section>

        <!-- Navegación entre Artículos (Anterior / Siguiente) -->
        <nav v-if="navegacion && (navegacion.anterior || navegacion.siguiente)" class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Artículo Anterior -->
            <div v-if="navegacion.anterior" class="flex">
                <Link
                    :href="navegacion.anterior.url"
                    class="group w-full p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 hover:border-emerald-500 dark:hover:border-emerald-500 bg-white dark:bg-zinc-900 transition-all duration-200 shadow-2xs hover:shadow-md flex items-center gap-3"
                >
                    <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 group-hover:bg-emerald-600 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                        <i class="pi pi-arrow-left text-sm" />
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">« Artículo Anterior</span>
                        <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors truncate">
                            {{ navegacion.anterior.title }}
                        </span>
                    </div>
                </Link>
            </div>
            <div v-else class="hidden sm:block" />

            <!-- Artículo Siguiente -->
            <div v-if="navegacion.siguiente" class="flex justify-end">
                <Link
                    :href="navegacion.siguiente.url"
                    class="group w-full p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 hover:border-emerald-500 dark:hover:border-emerald-500 bg-white dark:bg-zinc-900 transition-all duration-200 shadow-2xs hover:shadow-md flex items-center justify-end text-right gap-3"
                >
                    <div class="min-w-0">
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Artículo Siguiente »</span>
                        <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors truncate">
                            {{ navegacion.siguiente.title }}
                        </span>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 group-hover:bg-emerald-600 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                        <i class="pi pi-arrow-right text-sm" />
                    </div>
                </Link>
            </div>
        </nav>
    </article>

    <!-- Artículos Relacionados -->
    <section v-if="relacionados.data?.length" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 pt-8 border-t border-zinc-200 dark:border-zinc-800">
        <h2 class="font-bold mb-6 text-zinc-900 dark:text-zinc-100" style="font-family: var(--heading-font), system-ui, sans-serif;">
            Artículos Relacionados
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <article
                v-for="rel in relacionados.data"
                :key="rel.id"
                class="group rounded-xl border border-zinc-200 dark:border-zinc-800 p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors bg-white dark:bg-zinc-900 shadow-2xs"
            >
                <h3 class="font-medium group-hover:text-emerald-600 transition-colors text-zinc-900 dark:text-zinc-100">
                    <Link :href="`/${articuloItem.category?.slug}/${rel.slug}`">{{ rel.title }}</Link>
                </h3>
                <p v-if="rel.excerpt" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 leading-relaxed">
                    {{ rel.excerpt }}
                </p>
                <div class="mt-3 text-xs font-mono text-zinc-400">
                    {{ rel.published_at }}
                </div>
            </article>
        </div>
    </section>
    </template>
</template>

