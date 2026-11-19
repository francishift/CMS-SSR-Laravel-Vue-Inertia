<script setup>
/**
 * Cabecera estándar reutilizable para páginas del Panel de Administración.
 *
 * Centraliza breadcrumbs, botón de retorno opcional, icono, título, badges
 * contextuales, descripción y barra de acciones responsiva con soporte móvil/escritorio.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    // Alias de compatibilidad para vistas que usan subtitle
    subtitle: { type: String, default: '' },
    icon: { type: String, default: '' },
    backHref: { type: String, default: '' },
    backTitle: { type: String, default: 'Volver' },
    breadcrumbs: { type: Array, default: () => [] },
    divider: { type: Boolean, default: false },
});

const textoDescripcion = computed(() => props.description || props.subtitle || '');
</script>

<template>
    <div :class="['w-full', divider ? 'pb-4 border-b border-zinc-200 dark:border-zinc-800' : '']">
        <!-- Breadcrumbs responsivos -->
        <nav
            v-if="breadcrumbs.length"
            aria-label="Ruta de navegación"
            class="flex flex-wrap items-center gap-1.5 text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mb-2.5"
        >
            <Link href="/panel" class="hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors shrink-0">
                <i class="pi pi-home text-xs" />
            </Link>
            <template v-for="(crumb, i) in breadcrumbs" :key="i">
                <i class="pi pi-angle-right text-xs text-zinc-400 dark:text-zinc-600 shrink-0" />
                <Link
                    v-if="crumb.href"
                    :href="crumb.href"
                    class="hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors"
                >
                    {{ crumb.label }}
                </Link>
                <span v-else class="text-zinc-800 dark:text-zinc-200 font-medium truncate max-w-[220px] sm:max-w-none">
                    {{ crumb.label }}
                </span>
            </template>
        </nav>

        <!-- Bloque principal: Título / Contexto + Acciones responsivas -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5 sm:gap-4">
            <div class="flex items-start sm:items-center gap-3 min-w-0">
                <!-- Botón de retorno opcional -->
                <Link
                    v-if="backHref"
                    :href="backHref"
                    :title="backTitle"
                    :aria-label="backTitle"
                    class="w-9 h-9 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-colors flex items-center justify-center shrink-0 shadow-2xs"
                >
                    <i class="pi pi-arrow-left text-sm" />
                </Link>

                <!-- Icono decorativo opcional -->
                <div
                    v-if="icon"
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-500/10 dark:bg-emerald-400/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0"
                >
                    <i :class="[icon, 'text-base']" />
                </div>

                <!-- Textos y etiquetas de estado -->
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg sm:text-xl font-bold text-zinc-900 dark:text-white tracking-tight leading-snug break-words">
                            {{ title }}
                        </h2>
                        <slot name="badge" />
                    </div>
                    <p v-if="textoDescripcion" class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">
                        {{ textoDescripcion }}
                    </p>
                </div>
            </div>

            <!-- Botonera de acciones con flex-wrap garantizado para móvil y tablet -->
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:gap-2.5 shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
