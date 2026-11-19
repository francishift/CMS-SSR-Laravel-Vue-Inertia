<script setup>
/**
 * Componente Reutilizable de Personalización Avanzada y Código.
 * Proporciona ID HTML Único (`element_id`), Clases CSS (`custom_classes`),
 * Atributos HTML (`custom_attributes`) y CSS Personalizado (`custom_css`).
 */
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import AnimationBlock from './AnimationBlock.vue';

const props = defineProps({
    content: { type: Object, required: true },
    allowCascade: { type: Boolean, default: false },
});
</script>

<template>
    <!-- Bloque de Animación al Visualizar -->
    <AnimationBlock :target="content" :allowCascade="allowCascade" />

    <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5 mt-4">
        <div class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-zinc-100">
            <i class="pi pi-cog text-emerald-600 dark:text-emerald-400" />
            <span>Personalización Avanzada y Código</span>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1">ID HTML Único (`id`)</label>
            <InputText
                :modelValue="content.element_id || ''"
                @update:modelValue="(val) => content.element_id = val"
                placeholder="ej. mi-elemento-custom"
                class="w-full text-xs font-mono"
            />
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1">Clases CSS Adicionales</label>
            <InputText
                :modelValue="content.custom_classes || ''"
                @update:modelValue="(val) => content.custom_classes = val"
                placeholder="ej. shadow-xl border rounded-lg mi-clase"
                class="w-full text-xs font-mono"
            />
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1">Atributos HTML Personalizados</label>
            <InputText
                :modelValue="content.custom_attributes || ''"
                @update:modelValue="(val) => content.custom_attributes = val"
                placeholder='ej. data-aos="fade-up" aria-label="Elemento"'
                class="w-full text-xs font-mono"
            />
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1">CSS Personalizado</label>
            <Textarea
                :modelValue="content.custom_css || ''"
                @update:modelValue="(val) => content.custom_css = val"
                rows="4"
                class="w-full text-xs font-mono"
                placeholder="/* Reglas CSS específicas para este elemento */"
            />
        </div>
    </div>
</template>
