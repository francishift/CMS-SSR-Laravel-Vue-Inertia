<script setup>
/**
 * Renderizador Universal del Page Builder (SSR / Public / Preview).
 *
 * Recorre la estructura jerárquica JSON de layout_json (Secciones -> Filas -> Columnas -> Elementos Modulares)
 * produciendo semántica W3C accesible y renderizado de ultra-alta velocidad.
 */
import BuilderSection from './Structure/BuilderSection.vue';
import { computed, onMounted, onUnmounted, nextTick, watch, ref } from 'vue';
import { initScrollAnimations, cleanupScrollAnimations } from '@/Composables/useScrollAnimation';

const props = defineProps({
    layout: { type: Object, default: () => ({ sections: [] }) },
    indexOffset: { type: Number, default: 0 },
});

const rootRef = ref(null);
let mutationObserver = null;

const sections = computed(() => {
    return props.layout?.sections || [];
});

onMounted(() => {
    nextTick(() => {
        initScrollAnimations(rootRef.value);
    });

    if (typeof MutationObserver !== 'undefined' && rootRef.value) {
        mutationObserver = new MutationObserver(() => {
            initScrollAnimations(rootRef.value);
        });
        mutationObserver.observe(rootRef.value, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['data-cms-animate', 'data-cms-delay'],
        });
    }
});

onUnmounted(() => {
    if (mutationObserver) {
        mutationObserver.disconnect();
    }
    if (rootRef.value) {
        cleanupScrollAnimations(rootRef.value);
    }
});

watch(() => props.layout, () => {
    nextTick(() => {
        initScrollAnimations(rootRef.value);
    });
}, { deep: true });
</script>

<template>
    <div ref="rootRef" v-if="sections.length" class="page-builder-root space-y-0">
        <BuilderSection
            v-for="(section, index) in sections"
            :key="section.id"
            :section="section"
            :index="index + indexOffset"
        />
    </div>
</template>
