<script setup>
/**
 * Componente de Barra Lateral (Sidebar) pública modular.
 *
 * Renderiza el menú configurado en la ubicación 'sidebar' del CMS.
 * Soporta anidamiento de submenús, iconos PrimeIcons y estados activos.
 */
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    menuSidebar: { type: Object, default: null },
});

const page = usePage();
const currentUrl = computed(() => page.url);

function isUrlActive(itemUrl) {
    if (!itemUrl || itemUrl === '#') return false;
    if (itemUrl === currentUrl.value) return true;
    try {
        const path = new URL(itemUrl, window.location.origin).pathname;
        return path !== '/' && currentUrl.value.startsWith(path);
    } catch {
        return false;
    }
}
</script>

<template>
    <div v-if="menuSidebar && menuSidebar.items?.length" class="space-y-6">
        <!-- Tarjeta de Menú Lateral -->
        <div class="bg-zinc-50/80 dark:bg-zinc-900/60 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 p-5 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 mb-4 border-b border-zinc-200/60 dark:border-zinc-800/80">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs font-bold">
                    <i class="pi pi-bars" />
                </div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">
                    {{ menuSidebar.name || 'Navegación' }}
                </h3>
            </div>

            <!-- Lista de items del sidebar -->
            <nav class="space-y-1">
                <div v-for="item in menuSidebar.items" :key="item.id" class="space-y-1">
                    <Link
                        :href="item.resolved_url"
                        :target="item.target || '_self'"
                        :rel="item.is_nofollow ? 'nofollow' : undefined"
                        prefetch="hover"
                        :class="[
                            'flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group',
                            isUrlActive(item.resolved_url)
                                ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 shadow-sm font-semibold'
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60 hover:text-zinc-900 dark:hover:text-white'
                        ]"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <i v-if="item.icon" :class="[item.icon, 'text-xs shrink-0', isUrlActive(item.resolved_url) ? 'text-emerald-400 dark:text-emerald-600' : 'text-zinc-400 group-hover:text-emerald-500']" />
                            <span class="truncate">{{ item.title }}</span>
                        </div>
                        <i v-if="item.children?.length" class="pi pi-chevron-right text-[10px] opacity-60" />
                    </Link>

                    <!-- Subitems anidados -->
                    <div v-if="item.children?.length" class="pl-4 space-y-1 border-l border-zinc-200 dark:border-zinc-800 ml-3 my-1">
                        <Link
                            v-for="child in item.children"
                            :key="child.id"
                            :href="child.resolved_url"
                            :target="child.target || '_self'"
                            :rel="child.is_nofollow ? 'nofollow' : undefined"
                            prefetch="hover"
                            :class="[
                                'flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                isUrlActive(child.resolved_url)
                                    ? 'text-emerald-600 dark:text-emerald-400 font-semibold'
                                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                            ]"
                        >
                            <i v-if="child.icon" :class="[child.icon, 'text-[10px]']" />
                            <span>{{ child.title }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</template>
