<script setup>
/**
 * Componente Unificado para Tarjetas de Artículo (Cards) en la Web Pública y Page Builder.
 * Sincronizado 100% con los colores del Configurador Global (Primary, Secondary, Accent, Botones y Fuentes).
 */
import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    articulo: { type: Object, required: true },
    destacado: { type: Boolean, default: false },
    categoriaSlug: { type: String, default: null },
    titleTag: { type: String, default: 'h2' },
    cardStyle: { type: String, default: 'default' }, // 'default', 'shadow', 'flat', 'bordered'
    imageAspect: { type: String, default: 'video' }, // 'video', 'photo', 'square'
    imagePosition: { type: String, default: 'boxed' }, // 'boxed', 'flush'
    showBadge: { type: Boolean, default: true },
    showReadMore: { type: Boolean, default: true },
    readMoreText: { type: String, default: 'Leer más' },
    buttonStyle: { type: String, default: 'button_primary' }, // 'button_primary', 'button_secondary', 'button_outline', 'link'
    buttonSize: { type: String, default: 'md' }, // 'sm', 'md', 'lg'
    textSize: { type: String, default: 'xs' }, // 'xs', 'sm', 'base', 'lg'
});

const textSizeClass = computed(() => {
    return {
        xs: 'text-xs',
        sm: 'text-sm',
        base: 'text-base',
        lg: 'text-lg',
        xl: 'text-xl',
    }[props.textSize] || 'text-xs';
});

const textSizeCss = computed(() => {
    return {
        xs: '0.75rem',    // 12px
        sm: '0.875rem',   // 14px
        base: '1rem',     // 16px
        lg: '1.125rem',   // 18px
        xl: '1.25rem',    // 20px
    }[props.textSize] || '0.75rem';
});

const categorySlugResolved = computed(() => {
    if (props.articulo.category?.slug) {
        return props.articulo.category.slug;
    }
    if (props.categoriaSlug) {
        return props.categoriaSlug;
    }
    return 'general';
});

const articleUrl = computed(() => {
    if (props.articulo.url) return props.articulo.url;
    return `/${categorySlugResolved.value}/${props.articulo.slug}`;
});

const esCardLink = computed(() => props.articulo.card_full_link !== false);
const mostrarLeerMas = computed(() => props.showReadMore && props.articulo.show_read_more !== false);
const textoLeerMas = computed(() => props.articulo.read_more_text || props.readMoreText || 'Leer más');

const mostrarAutor = computed(() => {
    if (props.articulo.category?.show_author === false) return false;
    return true;
});

const mostrarFecha = computed(() => {
    if (props.articulo.category?.show_date === false) return false;
    return true;
});

const mostrarTiempoLectura = computed(() => {
    if (props.articulo.category?.show_reading_time === false) return false;
    return true;
});

// Preset visual de tarjeta (Estándar, Sombra Elevada, Plana, Borde Primario)
const cardContainerClasses = computed(() => {
    const base = 'group flex flex-col justify-between transition-all duration-300 relative overflow-hidden h-full';
    if (props.cardStyle === 'shadow') {
        return `${base} bg-white dark:bg-zinc-900 rounded-2xl p-0 shadow-md hover:shadow-2xl border border-zinc-200/80 dark:border-zinc-800`;
    }
    if (props.cardStyle === 'flat') {
        return `${base} bg-zinc-50 dark:bg-zinc-900/40 rounded-2xl p-0 border-0 hover:bg-zinc-100 dark:hover:bg-zinc-800/60`;
    }
    if (props.cardStyle === 'bordered') {
        return `${base} bg-white dark:bg-zinc-900 rounded-2xl p-0 border-2 border-[var(--primary-color,#10b981)] hover:shadow-xl`;
    }
    // 'default'
    return `${base} bg-white dark:bg-zinc-900/80 rounded-2xl p-0 border border-zinc-200/80 dark:border-zinc-800 hover:border-[var(--primary-color,#10b981)] hover:shadow-lg`;
});

// Proporción de aspecto de la imagen
const imageAspectClass = computed(() => {
    if (props.imageAspect === 'photo') return 'aspect-[4/3]';
    if (props.imageAspect === 'square') return 'aspect-square';
    return 'aspect-video';
});

// Estilo del botón (primario, secundario, contorno o enlace)
const buttonInlineStyle = computed(() => {
    if (props.buttonStyle === 'button_secondary') {
        return {
            backgroundColor: 'var(--btn-secondary-bg, var(--secondary-color, #6366f1))',
            color: 'var(--btn-secondary-text, #ffffff)',
        };
    }
    if (props.buttonStyle === 'button_outline') {
        return {
            backgroundColor: 'transparent',
            color: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
            borderColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
            borderWidth: '2px',
        };
    }
    if (props.buttonStyle === 'link') {
        return {
            color: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
        };
    }
    // 'button_primary' por defecto
    return {
        backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
        color: 'var(--btn-primary-text, #ffffff)',
    };
});

const cardTitleStyle = computed(() => {
    const key = props.titleTag || 'h2';
    if (['h1','h2','h3','h4','h5','h6'].includes(key)) {
        return {
            fontFamily: "var(--heading-font), system-ui, sans-serif",
            fontSize: `var(--${key}-size)`,
            fontWeight: `var(--${key}-font-weight)`,
            letterSpacing: `var(--${key}-letter-spacing)`,
            lineHeight: `var(--${key}-line-height)`,
        };
    }
    return { fontFamily: "var(--heading-font), system-ui, sans-serif" };
});
</script>

<template>
    <component
        :is="esCardLink ? (articulo._isDynamic ? Link : 'a') : 'article'"
        :href="esCardLink ? articleUrl : undefined"
        :prefetch="esCardLink && articulo._isDynamic ? 'hover' : undefined"
        :aria-label="esCardLink ? `${textoLeerMas}: ${articulo.title}` : undefined"
        :class="[
            cardContainerClasses,
            esCardLink ? 'cursor-pointer block no-underline text-inherit' : ''
        ]"
    >
        <!-- Imagen destacada responsiva -->
        <div v-if="articulo.featured_image || articulo.image" :class="['w-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden relative shrink-0', imageAspectClass, imagePosition === 'boxed' ? 'p-3 rounded-t-2xl' : '']">
            <ResponsiveImage
                :src="articulo.featured_image || articulo.image"
                :alt="articulo.title"
                :width="destacado ? 800 : 480"
                :height="destacado ? 450 : 270"
                fit="cover"
                sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px"
                :imgClass="[
                    'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300',
                    imagePosition === 'boxed' ? 'rounded-xl' : ''
                ]"
            />
        </div>

        <div class="p-5 flex flex-col justify-between space-y-3 flex-1">
            <div>
                <!-- Badge de Categoría -->
                <span
                    v-if="showBadge && (articulo.category || articulo.badge)"
                    class="inline-block text-[11px] font-bold px-2.5 py-0.5 rounded-full mb-2 tracking-wide bg-[var(--primary-color,#10b981)]/15 text-[var(--primary-color,#10b981)]"
                >
                    {{ articulo.category?.name || articulo.badge }}
                </span>

                <!-- Título dinámico -->
                <component
                    :is="titleTag"
                    :style="cardTitleStyle"
                    class="font-heading group-hover:text-[var(--primary-color,#10b981)] transition-colors text-zinc-900 dark:text-zinc-100 hyphens-none [word-break:keep-all]"
                >
                    <Link v-if="!esCardLink && articulo._isDynamic" :href="articleUrl" prefetch="hover" class="hover:underline">
                        {{ articulo.title }}
                    </Link>
                    <a v-else-if="!esCardLink" :href="articleUrl" class="hover:underline">
                        {{ articulo.title }}
                    </a>
                    <span v-else>{{ articulo.title }}</span>
                </component>

                <!-- Extracto / Descripción -->
                <p
                    v-if="articulo.excerpt || articulo.description"
                    class="mt-2 text-zinc-600 dark:text-zinc-400 leading-relaxed"
                    :style="{ fontSize: textSizeCss }"
                >
                    {{ articulo.excerpt || articulo.description }}
                </p>
            </div>

            <!-- Pie de Tarjeta (Metadatos / Botón) -->
            <div v-if="mostrarLeerMas || (mostrarFecha && articulo.published_at) || (mostrarAutor && articulo.author) || (mostrarTiempoLectura && articulo.reading_time)" class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between gap-2">
                <!-- Fecha de publicación y metadatos -->
                <div class="flex items-center flex-wrap gap-2 text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                    <span v-if="mostrarAutor && articulo.author">{{ articulo.author.name }}</span>
                    <span v-if="mostrarFecha && articulo.published_at">{{ articulo.published_at }}</span>
                    <span v-if="mostrarTiempoLectura && articulo.reading_time">{{ articulo.reading_time }} min</span>
                </div>

                <!-- Botón de acción (Configurado según el tema) -->
                <div v-if="mostrarLeerMas">
                    <span
                        :style="buttonInlineStyle"
                        class="px-4 py-2 rounded-xl text-xs font-bold shadow-xs transition-all inline-flex items-center gap-1.5 group-hover:opacity-90 cursor-pointer"
                    >
                        <span>{{ textoLeerMas }}</span>
                        <i class="pi pi-arrow-right text-[10px]" />
                    </span>
                </div>
            </div>
        </div>
    </component>
</template>
