<script setup>
/**
 * Componente Frontend ElementIcon del Page Builder.
 * Renderiza un icono modular independiente con el sistema unificado de diseño:
 *  - Clases PrimeIcons (pi pi-star, pi pi-sparkles, etc.)
 *  - Variables dinámicas ({article.icon}, {category.icon})
 *  - Enlace interactivo opcional (<a> o <Link>) con URL dinámica
 *  - Tamaños predefinidos (16px a 120px) y personalizados en px
 *  - Estilo de Contenedor (Sin Fondo, Suave Cuadrado, Sólido Cuadrado, Suave Circular, Sólido Circular)
 *  - Paletas de Color del Icono y de Fondo
 *  - Alineación y animaciones al scroll aceleradas por GPU.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { esNavegacionInertia } from '@/Composables/useUrlHelpers';
import { useElementAnimation } from '@/Composables/useElementAnimation';
import { sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
});

const { animAttrs, animStyle } = useElementAnimation(props);

// Personalización avanzada
const customId = computed(() => props.content?.element_id || undefined);
const customClasses = computed(() => props.content?.custom_classes || '');
const customCss = computed(() => sanitizeInlineCss(props.content?.custom_css || ''));

const parsedCustomAttributes = computed(() => {
    const raw = props.content?.custom_attributes;
    if (!raw || typeof raw !== 'string' || !raw.trim()) return {};
    const attrs = {};
    const regex = /([a-zA-Z0-9_-]+)(?:=(?:"([^"]*)"|'([^']*)'|([^\s"']+)))?/g;
    let match;
    while ((match = regex.exec(raw)) !== null) {
        const key = match[1];
        const val = match[2] ?? match[3] ?? match[4] ?? '';
        attrs[key] = val;
    }
    return attrs;
});

// Icono y URL
const rawIcon = computed(() => (props.content?.icon || '').trim());
const url = computed(() => props.content?.url || '');
const target = computed(() => props.content?.target || '_self');
const relNofollow = computed(() => Boolean(props.content?.rel_nofollow));

const esSpa = computed(() => {
    return esNavegacionInertia(url.value, target.value);
});

// Atributo rel para SEO y Seguridad
const computedRel = computed(() => {
    const parts = [];
    if (relNofollow.value) {
        parts.push('nofollow');
    }
    if (!esSpa.value || target.value === '_blank') {
        parts.push('noopener', 'noreferrer');
    }
    return parts.length > 0 ? parts.join(' ') : undefined;
});

// Normalizar la clase de icono
const iconClass = computed(() => {
    const ic = rawIcon.value;
    if (!ic) return 'pi pi-star';
    if (ic.startsWith('pi ') || ic.startsWith('pi-')) {
        return ic.startsWith('pi ') ? ic : `pi ${ic}`;
    }
    return `pi pi-${ic}`;
});

// Alineación del contenedor y origen de transformación para escala natural sin saltos
const align = computed(() => props.content?.align || 'left');
const alignClass = computed(() => {
    return {
        left: 'flex justify-start',
        center: 'flex justify-center',
        right: 'flex justify-end',
    }[align.value] || 'flex justify-start';
});

const originClass = computed(() => {
    return {
        left: 'origin-left',
        center: 'origin-center',
        right: 'origin-right',
    }[align.value] || 'origin-left';
});

// Efecto interactivo al pasar el ratón (Hover)
const hoverEffect = computed(() => props.content?.hover_effect || 'zoom_smooth');

const linkEffectClasses = computed(() => {
    if (!url.value) return '';
    return `builder-icon-link align-${align.value} hover-${hoverEffect.value} cursor-pointer`;
});

// Cálculo del Tamaño en Píxeles
const iconSizePx = computed(() => {
    const raw = props.content?.icon_size || props.content?.size || 'md';
    if (raw === 'custom') {
        return Number(props.content?.icon_size_px || props.content?.custom_size) || 32;
    }
    const presetMap = {
        xs: 16,
        sm: 24,
        md: 32,
        lg: 48,
        xl: 64,
        '2xl': 80,
        '3xl': 96,
        '4xl': 120,
    };
    return presetMap[raw] || 32;
});

// Estilo del Contenedor de Icono
const styleMode = computed(() => props.content?.icon_style_mode || 'none');
const hasBg = computed(() => styleMode.value && styleMode.value !== 'none');

const iconStyle = computed(() => {
    const px = iconSizePx.value;
    if (hasBg.value) {
        const containerPx = Math.round(px * 1.75);
        return {
            boxStyle: {
                width: `${containerPx}px`,
                height: `${containerPx}px`,
                minWidth: `${containerPx}px`,
                minHeight: `${containerPx}px`,
            },
            iconStyle: {
                fontSize: `${px}px`,
            },
        };
    }

    return {
        boxStyle: {},
        iconStyle: {
            fontSize: `${px}px`,
        },
    };
});

// Color del Texto del Icono
const iconTextColorClass = computed(() => {
    const color = props.content?.icon_color || 'primary';
    return {
        primary: 'text-[var(--primary-color,#10b981)]',
        dark: 'text-zinc-900 dark:text-zinc-100',
        light: 'text-white',
        zinc: 'text-zinc-500 dark:text-zinc-400',
        emerald: 'text-emerald-500 dark:text-emerald-400',
        indigo: 'text-indigo-500 dark:text-indigo-400',
        blue: 'text-blue-500 dark:text-blue-400',
        amber: 'text-amber-500 dark:text-amber-400',
        rose: 'text-rose-500 dark:text-rose-400',
    }[color] || 'text-[var(--primary-color,#10b981)]';
});

const customTextColorStyle = computed(() => {
    if (props.content?.icon_color === 'custom' && props.content?.custom_color) {
        return { color: props.content.custom_color };
    }
    return {};
});

// Estilos de Fondo y Redondeo del Contenedor
const iconBgStyleClass = computed(() => {
    if (!hasBg.value) {
        return 'bg-transparent shadow-none';
    }

    const rounded = (styleMode.value === 'circle_soft' || styleMode.value === 'circle_solid') ? 'rounded-full' : 'rounded-2xl';
    const isSolid = styleMode.value === 'solid_bg' || styleMode.value === 'circle_solid';
    const bgColor = props.content?.icon_bg_color || (isSolid ? 'primary_solid' : 'primary_soft');

    const bgClasses = {
        primary_soft: 'bg-[var(--primary-color,#10b981)]/10',
        primary_solid: 'bg-[var(--primary-color,#10b981)] !text-white shadow-2xs',
        zinc_soft: 'bg-zinc-100 dark:bg-zinc-800/80',
        zinc_dark: 'bg-zinc-900 dark:bg-zinc-100 !text-white dark:!text-zinc-900 shadow-2xs',
        emerald_soft: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400',
        emerald_solid: 'bg-emerald-500 !text-white shadow-2xs',
        indigo_soft: 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400',
        indigo_solid: 'bg-indigo-500 !text-white shadow-2xs',
        blue_soft: 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400',
        amber_soft: 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400',
        rose_soft: 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400',
    }[bgColor] || 'bg-[var(--primary-color,#10b981)]/10';

    return `${rounded} ${bgClasses}`;
});

const customBgColorStyle = computed(() => {
    if (hasBg.value && props.content?.icon_bg_color === 'custom' && props.content?.custom_bg_color) {
        return { backgroundColor: props.content.custom_bg_color };
    }
    return {};
});

// Márgenes Top y Bottom
const marginTopClass = computed(() => {
    const mt = props.content?.margin_top;
    return { none: 'mt-0', sm: 'mt-4', md: 'mt-6', lg: 'mt-8', xl: 'mt-12', '2xl': 'mt-16' }[mt] || 'mt-0';
});

const marginBottomClass = computed(() => {
    const mb = props.content?.margin_bottom;
    return { none: 'mb-0', sm: 'mb-4', md: 'mb-6', lg: 'mb-8', xl: 'mb-12', '2xl': 'mb-16' }[mb] || 'mb-0';
});

// Configuración y computeds para el texto opcional junto al icono
const text = computed(() => (props.content?.text || '').trim());
const textSize = computed(() => props.content?.text_size || 'base');
const textSizePx = computed(() => Number(props.content?.text_size_px) || 16);
const textWeight = computed(() => props.content?.text_weight || 'semibold');
const textColor = computed(() => props.content?.text_color || 'inherit');
const customTextColor = computed(() => props.content?.custom_text_color || '');
const textGap = computed(() => props.content?.text_gap || 'md');

const textSizeClass = computed(() => {
    if (textSize.value === 'custom') return '';
    return {
        xs: 'text-xs',
        sm: 'text-sm',
        base: 'text-base',
        lg: 'text-lg',
        xl: 'text-xl',
        '2xl': 'text-2xl',
    }[textSize.value] || 'text-base';
});

const textStyle = computed(() => {
    const s = {};
    if (textSize.value === 'custom' && textSizePx.value) {
        s.fontSize = `${textSizePx.value}px`;
    }
    if (textColor.value === 'custom' && customTextColor.value) {
        s.color = customTextColor.value;
    }
    return s;
});

const textWeightClass = computed(() => {
    return {
        normal: 'font-normal',
        medium: 'font-medium',
        semibold: 'font-semibold',
        bold: 'font-bold',
    }[textWeight.value] || 'font-semibold';
});

const textColorClass = computed(() => {
    return {
        inherit: '',
        primary: 'text-[var(--primary-color,#10b981)]',
        dark: 'text-zinc-900 dark:text-white',
        muted: 'text-zinc-600 dark:text-zinc-400',
        light: 'text-white',
        custom: '',
    }[textColor.value] || '';
});

const gapClass = computed(() => {
    return {
        xs: 'gap-1.5',
        sm: 'gap-2',
        md: 'gap-3',
        lg: 'gap-4',
        xl: 'gap-6',
    }[textGap.value] || 'gap-3';
});
</script>

<template>
    <div
        :id="customId"
        :class="['builder-element-icon w-full', alignClass, marginTopClass, marginBottomClass]"
        :style="animStyle"
        v-bind="{ ...animAttrs, ...parsedCustomAttributes }"
    >
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <!-- Enlace Interno SPA con <Link> de Inertia -->
        <Link
            v-if="url && esSpa"
            :href="url"
            :rel="computedRel"
            :class="[
                'inline-flex items-center group',
                linkEffectClasses,
                gapClass,
                customClasses
            ]"
        >
            <div
                v-if="hasBg"
                :class="[
                    'builder-icon-box inline-flex items-center justify-center shrink-0',
                    iconBgStyleClass,
                    iconTextColorClass
                ]"
                :style="[iconStyle.boxStyle, customTextColorStyle, customBgColorStyle]"
            >
                <i :class="iconClass" :style="iconStyle.iconStyle" />
            </div>
            <i
                v-else
                :class="[
                    'builder-icon-box shrink-0',
                    iconClass,
                    iconTextColorClass
                ]"
                :style="[iconStyle.iconStyle, customTextColorStyle]"
            />
            <span
                v-if="text"
                :class="[textSizeClass, textWeightClass, textColorClass, 'leading-none']"
                :style="textStyle"
            >
                {{ text }}
            </span>
        </Link>

        <!-- Enlace Nativo con <a> (externo, protocolo directo o ruta nativa del servidor) -->
        <a
            v-else-if="url"
            :href="url"
            :target="target"
            :rel="computedRel"
            :class="[
                'inline-flex items-center group',
                linkEffectClasses,
                gapClass,
                customClasses
            ]"
        >
            <div
                v-if="hasBg"
                :class="[
                    'builder-icon-box inline-flex items-center justify-center shrink-0',
                    iconBgStyleClass,
                    iconTextColorClass
                ]"
                :style="[iconStyle.boxStyle, customTextColorStyle, customBgColorStyle]"
            >
                <i :class="iconClass" :style="iconStyle.iconStyle" />
            </div>
            <i
                v-else
                :class="[
                    'builder-icon-box shrink-0',
                    iconClass,
                    iconTextColorClass
                ]"
                :style="[iconStyle.iconStyle, customTextColorStyle]"
            />
            <span
                v-if="text"
                :class="[textSizeClass, textWeightClass, textColorClass, 'leading-none']"
                :style="textStyle"
            >
                {{ text }}
            </span>
        </a>

        <!-- Icono Estático sin Enlace -->
        <div
            v-else
            :class="[
                'inline-flex items-center transition-all',
                gapClass,
                customClasses
            ]"
        >
            <div
                v-if="hasBg"
                :class="['inline-flex items-center justify-center shrink-0', iconBgStyleClass, iconTextColorClass]"
                :style="[iconStyle.boxStyle, customTextColorStyle, customBgColorStyle]"
            >
                <i :class="iconClass" :style="iconStyle.iconStyle" />
            </div>
            <i
                v-else
                :class="[iconClass, iconTextColorClass, 'shrink-0']"
                :style="[iconStyle.iconStyle, customTextColorStyle]"
            />
            <span
                v-if="text"
                :class="[textSizeClass, textWeightClass, textColorClass, 'leading-none']"
                :style="textStyle"
            >
                {{ text }}
            </span>
        </div>
    </div>
</template>
