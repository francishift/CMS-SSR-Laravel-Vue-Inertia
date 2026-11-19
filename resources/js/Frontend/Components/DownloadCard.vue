<script setup>
/**
 * Tarjeta reutilizable de Descarga para la Web Pública SSR (modos Grid y Listado).
 *
 * Soporta control granular de tamaños tipográficos (`headingScale.js`), tamaño y truncado
 * de descripción, proporciones de imagen/SVG, estilos de superficie y variantes de botón.
 */
import ResponsiveImage from '@/Frontend/Components/ResponsiveImage.vue';
import { Link } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';
import { esNavegacionInertia, getRutaInterna } from '@/Composables/useUrlHelpers';
import { getHeadingClasses, getHeadingStyle } from '@/Utils/headingScale';

const props = defineProps({
    item: { type: Object, required: true },
    viewMode: { type: String, default: 'grid' }, // 'grid' | 'list'
    showQuickDownload: { type: Boolean, default: true },
    titleTag: { type: String, default: 'h2' },
    cardConfig: { type: Object, default: () => ({}) },
    extraCardClass: { type: String, default: '' },
});

const vistaDispositivo = inject('vistaDispositivo', ref(null));

const cfg = computed(() => props.cardConfig || {});
const esModoLista = computed(() => props.viewMode === 'list');
const rutaFichaInterna = computed(() => getRutaInterna(props.item.url));
// Evaluar siempre la ruta interna relativa para evitar desajustes de hidratación entre Node SSR y navegador
const usarInertiaEnFicha = computed(() => esNavegacionInertia(rutaFichaInterna.value, '_self'));

const esImagenSvg = computed(() => {
    const src = (props.item.featured_image || '').split('?')[0].split('#')[0].toLowerCase();
    return src.endsWith('.svg');
});

const mostrarTitulo = computed(() => cfg.value.show_item_title !== false);
const mostrarImagen = computed(() => cfg.value.show_item_image !== false);
const mostrarDescripcion = computed(() => cfg.value.show_item_description !== false);
const mostrarBadges = computed(() => cfg.value.show_item_badges !== false);
const mostrarMeta = computed(() => cfg.value.show_item_meta !== false);
const mostrarBoton = computed(() => cfg.value.show_item_button !== false);

const puedeDescargaRapida = computed(() =>
    Boolean(
        (cfg.value.show_quick_download ?? props.showQuickDownload) &&
        props.item.has_file &&
        props.item.download_request_url &&
        props.item.access_type === 'free'
    )
);

// Superficie de tarjeta
const cardClasses = computed(() => {
    const preset = cfg.value.card_style || 'default';
    const base = 'group transition-all duration-300 overflow-hidden';

    const surface = {
        shadow: 'bg-white dark:bg-zinc-900 rounded-2xl shadow-md hover:shadow-2xl border border-zinc-200/70 dark:border-zinc-800',
        flat: 'bg-zinc-50/70 dark:bg-zinc-900/40 rounded-2xl border border-transparent hover:bg-zinc-100/80 dark:hover:bg-zinc-800/60',
        bordered: 'bg-white dark:bg-zinc-900 rounded-2xl border-2 border-[var(--primary-color,#10b981)]/80 hover:shadow-xl',
        default: 'bg-white dark:bg-zinc-900/85 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-[var(--primary-color,#10b981)] hover:shadow-lg',
    }[preset] || 'bg-white dark:bg-zinc-900/85 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-[var(--primary-color,#10b981)] hover:shadow-lg';

    const layoutDir = esModoLista.value
        ? 'flex flex-col sm:flex-row items-stretch'
        : 'flex flex-col justify-between h-full';

    return [base, surface, layoutDir, props.extraCardClass];
});

// Alineación interna de textos
const alignClasses = computed(() => {
    if (esModoLista.value) return 'text-left items-start';
    const align = cfg.value.card_text_align || 'left';
    return {
        center: 'text-center items-center',
        right: 'text-right items-end',
        left: 'text-left items-start',
    }[align] || 'text-left items-start';
});

// Etiqueta semántica y escala visual del título (con soporte responsivo PC / Tablet / Móvil en el Builder)
const resolvedTitleTag = computed(() => {
    const tag = cfg.value.item_title_tag || props.titleTag || 'h2';
    return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'].includes(tag) ? tag : 'h2';
});

const itemTitleClasses = computed(() => {
    const sizeDesk = cfg.value.item_title_size || 'h4';
    const sizeTab = cfg.value.item_title_size_tablet || '';
    const sizeMob = cfg.value.item_title_size_mobile || '';
    return getHeadingClasses(sizeDesk, sizeTab, sizeMob, vistaDispositivo.value || '', resolvedTitleTag.value);
});

const itemTitleStyle = computed(() => {
    const sizeKey = cfg.value.item_title_size || 'h4';
    return getHeadingStyle(sizeKey, resolvedTitleTag.value);
});

// Tamaño y líneas de la descripción corta
const descSizeClass = computed(() => {
    return {
        xs: 'text-xs',
        sm: 'text-sm',
        base: 'text-base',
        lg: 'text-lg',
        xl: 'text-xl',
    }[cfg.value.item_desc_size || 'sm'] || 'text-sm';
});

const descClampClass = computed(() => {
    return {
        '2': 'line-clamp-2',
        '3': 'line-clamp-3',
        '4': 'line-clamp-4',
        'none': '',
    }[String(cfg.value.item_desc_lines || '3')] ?? 'line-clamp-3';
});

// Proporción de recuadro de imagen y tamaño del SVG interior
const imageBoxClasses = computed(() => {
    const isBoxed = cfg.value.item_image_position === 'boxed';
    if (esModoLista.value) {
        return [
            'w-full sm:w-60 md:w-72 aspect-video sm:aspect-auto shrink-0',
            isBoxed ? 'sm:m-3 sm:rounded-xl' : '',
        ];
    }

    const ratio = cfg.value.item_image_ratio || '16:9';
    const ratioClass = {
        '16:9': 'aspect-video',
        '4:3': 'aspect-[4/3]',
        '1:1': 'aspect-square',
        'compact': 'h-36',
        'auto': 'min-h-[140px]',
    }[ratio] || 'aspect-video';

    return [
        'w-full shrink-0',
        ratioClass,
        isBoxed ? 'p-3 pb-0' : '',
    ];
});

const svgSizeClass = computed(() => {
    return {
        sm: 'w-14 h-14',
        md: 'w-20 h-20',
        lg: 'w-24 h-24',
        xl: 'w-32 h-32',
    }[cfg.value.item_svg_size || 'lg'] || 'w-24 h-24';
});

// Tamaños y estilos de botones
const btnPaddingClass = computed(() => {
    return {
        sm: 'px-3 py-1.5 text-[11px]',
        md: 'px-3.5 py-2 text-xs',
        lg: 'px-4 py-2.5 text-sm',
    }[cfg.value.item_button_size || 'md'] || 'px-3.5 py-2 text-xs';
});

const mainButtonStyle = computed(() => {
    const st = cfg.value.item_button_style || 'button_primary';
    if (st === 'button_secondary') {
        return {
            backgroundColor: 'var(--btn-secondary-bg, var(--secondary-color, #6366f1))',
            color: 'var(--btn-secondary-text, #ffffff)',
        };
    }
    if (st === 'button_outline') {
        return {
            backgroundColor: 'transparent',
            color: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
            border: '2px solid var(--btn-primary-bg, var(--primary-color, #10b981))',
        };
    }
    if (st === 'link') {
        return {
            backgroundColor: 'transparent',
            color: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
            paddingLeft: '0.25rem',
            paddingRight: '0.25rem',
        };
    }
    return {
        backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
        color: 'var(--btn-primary-text, #ffffff)',
    };
});

const badgeAccentStyle = {
    backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
    color: 'var(--btn-primary-text, #ffffff)',
};
</script>

<template>
    <article :class="cardClasses">
        <!-- Imagen / Ilustración SVG representativa -->
        <component
            v-if="mostrarImagen"
            :is="usarInertiaEnFicha ? Link : 'a'"
            :href="usarInertiaEnFicha ? rutaFichaInterna : item.url"
            :class="['relative block no-underline overflow-hidden', imageBoxClasses]"
        >
            <div
                :class="[
                    'w-full h-full overflow-hidden relative bg-zinc-100 dark:bg-zinc-800/80',
                    cfg.item_image_position === 'boxed' ? 'rounded-xl' : '',
                ]"
            >
                <div
                    v-if="item.featured_image && esImagenSvg"
                    class="w-full h-full min-h-[132px] flex items-center justify-center p-5 bg-gradient-to-br from-zinc-50 via-zinc-100/80 to-zinc-200/50 dark:from-zinc-900 dark:via-zinc-800/80 dark:to-zinc-900"
                >
                    <img
                        :src="item.featured_image"
                        :alt="item.title"
                        loading="lazy"
                        decoding="async"
                        :class="[svgSizeClass, 'object-contain transition-transform duration-500 group-hover:scale-110 dark:invert dark:opacity-90']"
                    />
                </div>
                <ResponsiveImage
                    v-else-if="item.featured_image"
                    :src="item.featured_image"
                    :alt="item.title"
                    container-class="w-full h-full"
                    img-class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
                <div v-else class="w-full h-full min-h-[132px] flex items-center justify-center text-zinc-400 dark:text-zinc-500">
                    <i :class="[item.icon || 'pi pi-box', 'text-4xl']" />
                </div>

                <!-- Insignias superpuestas de Precio y Versión -->
                <div v-if="mostrarBadges" class="absolute top-3 left-3 flex items-center gap-1.5 z-10">
                    <span
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold tracking-wide shadow-sm backdrop-blur-md"
                        :style="item.is_paid ? badgeAccentStyle : { backgroundColor: 'rgba(24, 24, 27, 0.8)', color: '#ffffff' }"
                    >
                        {{ item.formatted_price }}
                    </span>
                    <span
                        v-if="item.version"
                        class="px-2 py-1 rounded-lg text-[11px] font-mono font-semibold bg-white/90 dark:bg-zinc-900/90 text-zinc-800 dark:text-zinc-200 shadow-sm backdrop-blur-md"
                    >
                        v{{ item.version }}
                    </span>
                </div>
            </div>
        </component>

        <!-- Cuerpo de información y acciones -->
        <div class="p-5 flex flex-col justify-between flex-1 gap-4">
            <div :class="['space-y-2 flex flex-col w-full', alignClasses]">
                <!-- Si la imagen está oculta pero los badges están activos, mostrarlos arriba del título -->
                <div v-if="!mostrarImagen && mostrarBadges" class="flex items-center gap-1.5 mb-1">
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900">
                        {{ item.formatted_price }}
                    </span>
                    <span v-if="item.version" class="px-2 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                        v{{ item.version }}
                    </span>
                </div>

                <div v-if="mostrarMeta && item.compatibility" class="flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400 max-w-full">
                    <i :class="[item.icon || 'pi pi-puzzle', 'text-xs text-[var(--primary-color,#10b981)] shrink-0']" />
                    <span class="truncate">{{ item.compatibility }}</span>
                </div>

                <component
                    v-if="mostrarTitulo"
                    :is="resolvedTitleTag"
                    :class="[itemTitleClasses, 'font-bold text-zinc-900 dark:text-zinc-100 leading-snug']"
                    :style="itemTitleStyle"
                >
                    <component
                        :is="usarInertiaEnFicha ? Link : 'a'"
                        :href="usarInertiaEnFicha ? rutaFichaInterna : item.url"
                        class="no-underline text-inherit hover:text-[var(--primary-color,#10b981)] transition-colors"
                    >
                        {{ item.title }}
                    </component>
                </component>

                <small
                    v-if="mostrarTitulo && item.subtitle"
                    class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 leading-snug -mt-1"
                >
                    {{ item.subtitle }}
                </small>

                <p
                    v-if="mostrarDescripcion && item.short_description"
                    :class="[descSizeClass, descClampClass, 'text-zinc-600 dark:text-zinc-300 leading-relaxed']"
                >
                    {{ item.short_description }}
                </p>
            </div>

            <!-- Pie de tarjeta: Metadatos técnicos + Botones -->
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex flex-wrap items-center justify-between gap-3 w-full">
                <div v-if="mostrarMeta" class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                    <span v-if="item.has_file" class="inline-flex items-center gap-1 font-mono">
                        <i class="pi pi-file-export text-[11px]" />
                        {{ item.formatted_file_size }}
                    </span>
                    <span v-if="item.downloads_count !== null && item.downloads_count !== undefined" class="inline-flex items-center gap-1">
                        <i class="pi pi-download text-[11px]" />
                        {{ item.downloads_count }}
                    </span>
                </div>

                <div
                    v-if="mostrarBoton"
                    :class="[
                        'flex items-center gap-2',
                        (!esModoLista && cfg.card_text_align === 'center' && !mostrarMeta) ? 'mx-auto' : 'ml-auto',
                    ]"
                >
                    <a
                        v-if="puedeDescargaRapida"
                        :href="item.download_request_url"
                        rel="nofollow"
                        data-builder-nav-direct="true"
                        :class="['inline-flex items-center gap-1.5 rounded-xl font-bold hover:opacity-90 transition-opacity no-underline', btnPaddingClass]"
                        :style="mainButtonStyle"
                        :title="`Descargar ${item.title}`"
                    >
                        <i class="pi pi-download text-[11px]" />
                        <span>Descargar</span>
                    </a>

                    <component
                        :is="usarInertiaEnFicha ? Link : 'a'"
                        :href="usarInertiaEnFicha ? rutaFichaInterna : item.url"
                        data-builder-nav-direct="true"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-xl font-semibold transition-colors no-underline',
                            btnPaddingClass,
                            puedeDescargaRapida
                                ? 'border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                : '',
                        ]"
                        :style="!puedeDescargaRapida ? mainButtonStyle : undefined"
                    >
                        <span>{{ cfg.custom_button_text || (puedeDescargaRapida ? 'Detalles' : 'Ver ficha') }}</span>
                        <i class="pi pi-arrow-right text-[11px]" />
                    </component>
                </div>
            </div>
        </div>
    </article>
</template>
