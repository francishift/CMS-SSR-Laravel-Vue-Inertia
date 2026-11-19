<script setup>
/**
 * Plantilla de categoría: Cuadrícula (Grid).
 *
 * Muestra artículos en tarjetas responsive (2-4 columnas).
 * Es la plantilla genérica por defecto del CMS.
 */
import { Link } from '@inertiajs/vue3';
import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';

defineProps({
    articulos: { type: Object, required: true },
    categoria: { type: Object, required: true },
});
</script>

<template>
    <div>
        <!-- Grid de artículos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <article
                v-for="articulo in articulos.data"
                :key="articulo.id"
                class="group rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden hover:shadow-lg hover:border-emerald-300 dark:hover:border-emerald-700 transition-all duration-200"
            >
                <!-- Imagen destacada optimizada -->
                <div v-if="articulo.featured_image" class="aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                    <ResponsiveImage
                        :src="articulo.featured_image"
                        :alt="articulo.title"
                        :width="480"
                        :height="270"
                        fit="cover"
                        :widths="[320, 480, 640]"
                        sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                        imgClass="w-full h-full object-cover"
                        containerClass="w-full h-full"
                    />
                </div>

                <!-- Contenido de la tarjeta -->
                <div class="p-5">
                    <h3 class="text-lg font-semibold leading-snug group-hover:text-emerald-600 transition-colors">
                        <Link :href="`/${categoria.slug}/${articulo.slug}`">
                            {{ articulo.title }}
                        </Link>
                    </h3>

                    <p v-if="articulo.excerpt" class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ articulo.excerpt }}
                    </p>

                    <!-- Metadatos -->
                    <div class="mt-4 flex items-center gap-3 text-xs text-zinc-400">
                        <span v-if="articulo.author">{{ articulo.author.name }}</span>
                        <span v-if="articulo.published_at">{{ articulo.published_at }}</span>
                        <span v-if="articulo.reading_time">{{ articulo.reading_time }} min</span>
                    </div>
                </div>
            </article>
        </div>

        <!-- Paginación -->
        <nav v-if="articulos.last_page > 1" class="mt-10 flex justify-center gap-1">
            <template v-for="link in articulos.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-4 py-2 text-sm rounded-lg border transition-colors"
                    :class="link.active
                        ? 'bg-emerald-600 text-white border-emerald-600'
                        : 'border-zinc-200 dark:border-zinc-700 hover:border-emerald-300 text-zinc-600 dark:text-zinc-400'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200 font-semibold select-none cursor-not-allowed"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
