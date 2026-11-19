<script setup>
/**
 * Componente Frontend ElementMenu del Page Builder.
 * Renderiza un menú de navegación dinámico con soporte para rutas internas (Inertia)
 * y externas, orientación horizontal/vertical, alineación y colores.
 */
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { esNavegacionInertia, getRutaInterna } from '@/Composables/useUrlHelpers';
import { useElementAnimation } from '@/Composables/useElementAnimation';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
});

const { animAttrs, animStyle } = useElementAnimation(props);

const page = usePage();

// Resolver la lista de elementos del menú a partir del prop `content.source`
const menuItems = computed(() => {
    const source = props.content?.source || 'location_header';
    const menuHeader = page.props?.menuHeader;
    const menuSidebar = page.props?.menuSidebar;
    const menus = page.props?.menus || page.props?.menusDisponibles || [];

    if (source === 'location_header') {
        return menuHeader?.items || [];
    }
    if (source === 'location_sidebar') {
        return menuSidebar?.items || [];
    }
    if (source.startsWith('id_') || (!isNaN(Number(source)) && source !== '')) {
        const id = parseInt(String(source).replace('id_', ''), 10);
        const found = menus.find(m => m.id === id);
        return found?.items || [];
    }
    if (source.startsWith('slug_')) {
        const slug = source.replace('slug_', '');
        const found = menus.find(m => m.slug === slug);
        return found?.items || [];
    }

    return menuHeader?.items || [];
});

function getRel(item, isExternal = false) {
    const parts = [];
    if (isExternal || item?.target === '_blank') {
        parts.push('noopener', 'noreferrer');
    }
    if (item?.is_nofollow) {
        parts.push('nofollow');
    }
    return parts.length ? parts.join(' ') : undefined;
}

const isVertical = computed(() => props.content?.orientation === 'vertical');

const alignClass = computed(() => {
    const align = props.content?.align || 'left';
    if (isVertical.value) {
        if (align === 'center') return 'items-center text-center';
        if (align === 'right') return 'items-end text-right';
        return 'items-start text-left';
    } else {
        if (align === 'center') return 'justify-center';
        if (align === 'right') return 'justify-end';
        return 'justify-start';
    }
});

const sizeClass = computed(() => {
    const sz = props.content?.size || 'sm';
    return {
        xs: 'text-xs',
        sm: 'text-sm',
        base: 'text-base',
        lg: 'text-lg',
    }[sz] || 'text-sm';
});

const colorClass = computed(() => {
    const col = props.content?.color || 'default';
    return {
        default: 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
        primary: 'text-[var(--primary-color,#10b981)] hover:opacity-80 font-semibold',
        light: 'text-white/80 hover:text-white',
        dark: 'text-zinc-900 hover:text-black font-semibold',
    }[col] || 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white';
});

const gapClass = computed(() => props.content?.gap || 'gap-4');
</script>

<template>
    <nav
        :class="[
            'flex flex-wrap w-full',
            isVertical ? 'flex-col' : 'flex-row items-center',
            alignClass,
            gapClass
        ]"
        :style="animStyle"
        v-bind="animAttrs"
    >
        <template v-for="item in menuItems" :key="item.id">
            <template v-if="item.is_active !== false">
                <Link
                    v-if="esNavegacionInertia(item.resolved_url, item.target)"
                    :href="getRutaInterna(item.resolved_url)"
                    :rel="getRel(item)"
                    prefetch="hover"
                    :class="['font-medium transition-colors inline-flex items-center gap-1.5', sizeClass, colorClass]"
                >
                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                    <span>{{ item.title }}</span>
                </Link>

                <a
                    v-else
                    :href="item.resolved_url"
                    :target="item.target || '_blank'"
                    :rel="getRel(item, true)"
                    :class="['font-medium transition-colors inline-flex items-center gap-1.5', sizeClass, colorClass]"
                >
                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                    <span>{{ item.title }}</span>
                </a>
            </template>
        </template>
    </nav>
</template>
