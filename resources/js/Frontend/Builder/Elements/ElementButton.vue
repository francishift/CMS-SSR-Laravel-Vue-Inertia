<script setup>
/**
 * Elemento de Botón / CTA Avanzado para el Page Builder.
 * Soporta etiquetas, URLs (internas e externas), variantes visuales (primario, secundario, contorno, enlace simple),
 * tamaños (sm, md, lg), icono acompañante en PrimeIcons (izquierda/derecha), alineación (izquierda, centro, derecha, ancho completo),
 * márgenes e ID/clases/CSS personalizado.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useSanitize, sanitizeInlineCss } from '@/Composables/useSanitize';
import { esNavegacionInertia } from '@/Composables/useUrlHelpers';
import { useElementAnimation } from '@/Composables/useElementAnimation';

const { sanitize } = useSanitize();

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

const label = computed(() => props.content?.label || 'Hacer Clic');
const url = computed(() => props.content?.url || '#');
const target = computed(() => props.content?.target || '_self');
const variant = computed(() => props.content?.variant || 'primary');
const size = computed(() => props.content?.size || 'md');
const icon = computed(() => props.content?.icon || '');
const iconPosition = computed(() => props.content?.icon_position || 'left');
const align = computed(() => props.content?.align || 'left');

const esSpa = computed(() => esNavegacionInertia(url.value, target.value));

// Alineación del contenedor
const alignClass = computed(() => {
    return {
        left: 'text-left flex justify-start',
        center: 'text-center flex justify-center',
        right: 'text-right flex justify-end',
        full: 'w-full block',
    }[align.value] || 'text-left flex justify-start';
});

// Clases de tamaño del botón
const sizeClass = computed(() => {
    if (variant.value === 'link') {
        return {
            sm: 'text-xs gap-1.5',
            md: 'text-sm gap-2',
            lg: 'text-base gap-2.5',
        }[size.value] || 'text-sm gap-2';
    }
    return {
        sm: 'px-3.5 py-1.5 text-xs rounded-lg gap-1.5',
        md: 'px-5 py-2.5 text-sm rounded-xl gap-2',
        lg: 'px-7 py-3.5 text-base rounded-xl gap-2.5',
    }[size.value] || 'px-5 py-2.5 text-sm rounded-xl gap-2';
});

// Clases de variante visual del botón
const buttonClasses = computed(() => {
    if (variant.value === 'link') {
        return [
            'inline-flex items-center font-bold hover:underline transition-colors focus:outline-none cursor-pointer',
            align.value === 'full' ? 'w-full justify-center' : '',
            sizeClass.value,
            customClasses.value
        ].filter(Boolean).join(' ');
    }

    const base = 'inline-flex items-center justify-center font-bold transition-all duration-200 shadow-2xs active:scale-95 cursor-pointer';

    return [
        base,
        align.value === 'full' ? 'w-full' : '',
        sizeClass.value,
        customClasses.value
    ].filter(Boolean).join(' ');
});

// Estilo en línea dinámico garantizado
const buttonInlineStyle = computed(() => {
    if (variant.value === 'link') {
        return {
            color: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
        };
    }
    if (variant.value === 'secondary') {
        return {
            backgroundColor: 'var(--btn-secondary-bg, var(--secondary-color, #6366f1))',
            color: 'var(--btn-secondary-text, #ffffff)',
        };
    }
    if (variant.value === 'outline' || variant.value === 'outlined') {
        return {
            backgroundColor: 'transparent',
            color: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
            borderColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
            borderWidth: '2px',
        };
    }
    // Variant 'primary' por defecto
    return {
        backgroundColor: 'var(--btn-primary-bg, var(--primary-color, #10b981))',
        color: 'var(--btn-primary-text, #ffffff)',
    };
});

// Márgenes Top y Bottom independientes
const marginTopClass = computed(() => {
    const mt = props.content?.margin_top;
    return { none: 'mt-0', sm: 'mt-3', md: 'mt-6', lg: 'mt-10', xl: 'mt-16' }[mt] || 'mt-0';
});

const marginBottomClass = computed(() => {
    const mb = props.content?.margin_bottom;
    return { none: 'mb-0', sm: 'mb-3', md: 'mb-6', lg: 'mb-10', xl: 'mb-16' }[mb] || 'mb-0';
});
</script>

<template>
    <div
        :id="customId"
        :class="['builder-element-button w-full', alignClass, marginTopClass, marginBottomClass]"
        :style="animStyle"
        v-bind="{ ...animAttrs, ...parsedCustomAttributes }"
    >
        <component :is="'style'" v-if="customCss" v-html="customCss" />

        <Link v-if="esSpa" :href="url" :class="buttonClasses" :style="buttonInlineStyle">
            <i v-if="icon && iconPosition === 'left'" :class="[icon, 'shrink-0']" />
            <span v-html="sanitize(label)" />
            <i v-if="icon && iconPosition === 'right'" :class="[icon, 'shrink-0']" />
        </Link>

        <a v-else :href="url" :target="target" :rel="target === '_blank' ? 'noopener noreferrer' : undefined" :class="buttonClasses" :style="buttonInlineStyle">
            <i v-if="icon && iconPosition === 'left'" :class="[icon, 'shrink-0']" />
            <span v-html="sanitize(label)" />
            <i v-if="icon && iconPosition === 'right'" :class="[icon, 'shrink-0']" />
        </a>
    </div>
</template>
