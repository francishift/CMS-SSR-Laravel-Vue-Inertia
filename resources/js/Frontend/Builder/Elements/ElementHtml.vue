<script setup>
/**
 * Elemento de Código HTML Personalizado para el Page Builder.
 * Soporta HTML raw, SVGs, iFrames, formularios, etiquetas <style>, ID único, Clases CSS adicionales,
 * alineación horizontal, márgenes independientes y ejecución nativa de <script>.
 */
import { computed, onMounted, ref, watch, nextTick } from 'vue';
import { useElementAnimation } from '@/Composables/useElementAnimation';

import { resolverShortcodes, normalizarEventosInlineHtml, sanitizeInlineCss } from '@/Composables/useSanitize';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
});

const { animAttrs, animStyle } = useElementAnimation(props);

const containerRef = ref(null);

const htmlCode = computed(() => {
    const raw = props.content?.code ?? props.content?.html ?? '';
    return normalizarEventosInlineHtml(resolverShortcodes(raw));
});

// Personalización avanzada
const customId = computed(() => props.content?.element_id || undefined);
const customClasses = computed(() => props.content?.custom_classes || '');
const customCss = computed(() => sanitizeInlineCss(props.content?.custom_css || ''));

// Alineación horizontal
const alignClass = computed(() => {
    const align = props.content?.align || 'left';
    return {
        left: 'text-left',
        center: 'text-center',
        right: 'text-right',
        justify: 'text-justify',
    }[align] || 'text-left';
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

// Re-ejecutar scripts dinámicos insertados dentro del HTML
function executeScripts() {
    if (!containerRef.value) return;
    const scripts = containerRef.value.querySelectorAll('script');
    scripts.forEach((oldScript) => {
        const newScript = document.createElement('script');
        Array.from(oldScript.attributes).forEach((attr) => {
            newScript.setAttribute(attr.name, attr.value);
        });
        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
        if (oldScript.parentNode) {
            oldScript.parentNode.replaceChild(newScript, oldScript);
        }
    });
}

onMounted(() => {
    executeScripts();
});

watch(htmlCode, () => {
    nextTick(() => {
        executeScripts();
    });
});
</script>

<template>
    <div
        ref="containerRef"
        v-if="htmlCode"
        :id="customId"
        :class="[
            'raw-html-element w-full',
            alignClass,
            customClasses,
            marginTopClass,
            marginBottomClass
        ]"
        :style="animStyle"
        v-bind="animAttrs"
    >
        <component :is="'style'" v-if="customCss" v-html="customCss" />
        <div class="w-full" v-html="htmlCode" />
    </div>
</template>
