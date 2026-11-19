<script setup>
/**
 * Componente Frontend ElementSocial del Page Builder.
 * Renderiza los iconos de redes sociales activas configuradas en la Configuración Global.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useElementAnimation } from '@/Composables/useElementAnimation';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
});

const { animAttrs, animStyle } = useElementAnimation(props);

const page = usePage();

const mapaRedes = [
    { key: 'github', nombre: 'GitHub', icono: 'pi pi-github' },
    { key: 'twitter', nombre: 'X / Twitter', icono: 'pi pi-twitter' },
    { key: 'instagram', nombre: 'Instagram', icono: 'pi pi-instagram' },
    { key: 'linkedin', nombre: 'LinkedIn', icono: 'pi pi-linkedin' },
    { key: 'youtube', nombre: 'YouTube', icono: 'pi pi-youtube' },
    { key: 'facebook', nombre: 'Facebook', icono: 'pi pi-facebook' },
    { key: 'whatsapp', nombre: 'WhatsApp', icono: 'pi pi-whatsapp' },
];

const redesSociales = computed(() => {
    const settings = page.props?.siteSettings || {};
    const links = settings.social_links || {};
    return mapaRedes
        .filter(red => Boolean(links[red.key]))
        .map(red => ({ ...red, url: links[red.key] }));
});

const alignClass = computed(() => {
    const align = props.content?.align || 'left';
    if (align === 'center') return 'justify-center';
    if (align === 'right') return 'justify-end';
    return 'justify-start';
});

const sizeClass = computed(() => {
    const sz = props.content?.size || 'md';
    return {
        sm: 'w-8 h-8 text-xs',
        md: 'w-9 h-9 text-sm',
        lg: 'w-11 h-11 text-base',
        xl: 'w-13 h-13 text-lg',
    }[sz] || 'w-9 h-9 text-sm';
});

const presetStyleClass = computed(() => {
    const preset = props.content?.style_preset || 'soft';
    const color = props.content?.color_preset || 'brand';

    const baseRadius = preset === 'circle' ? 'rounded-full' : (preset === 'ghost' ? 'rounded-lg' : 'rounded-xl');

    if (preset === 'ghost') {
        if (color === 'light') return `${baseRadius} bg-transparent text-white/80 hover:text-white hover:bg-white/10`;
        if (color === 'dark') return `${baseRadius} bg-transparent text-zinc-900 hover:text-black hover:bg-zinc-200/50`;
        return `${baseRadius} bg-transparent text-zinc-600 dark:text-zinc-400 hover:text-[var(--primary-color,#10b981)] hover:bg-zinc-100 dark:hover:bg-zinc-800`;
    }

    if (preset === 'solid') {
        return `${baseRadius} bg-[var(--primary-color,#10b981)] text-white hover:opacity-90 shadow-2xs`;
    }

    // Default 'soft' or 'circle'
    if (color === 'light') {
        return `${baseRadius} bg-white/10 text-white hover:bg-white/20`;
    }
    if (color === 'dark') {
        return `${baseRadius} bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white hover:bg-zinc-300 dark:hover:bg-zinc-700`;
    }

    return `${baseRadius} bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:bg-[var(--primary-color,#10b981)] hover:text-white dark:hover:bg-[var(--primary-color,#10b981)] dark:hover:text-white`;
});
</script>

<template>
    <div
        v-if="redesSociales.length"
        :class="['flex items-center gap-2 w-full flex-wrap', alignClass]"
        :style="animStyle"
        v-bind="animAttrs"
    >
        <a
            v-for="red in redesSociales"
            :key="red.key"
            :href="red.url"
            target="_blank"
            rel="noopener noreferrer"
            :title="red.nombre"
            :class="['flex items-center justify-center transition-all duration-200', sizeClass, presetStyleClass]"
        >
            <i :class="red.icono" />
        </a>
    </div>
    <div v-else class="text-xs text-zinc-400 italic p-2 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
        Sin redes sociales configuradas en Configuración Global.
    </div>
</template>
