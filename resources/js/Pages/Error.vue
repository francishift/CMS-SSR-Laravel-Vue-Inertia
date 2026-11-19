<script setup>
/**
 * Página de Error Pública Personalizada (404, 403, 500, 503).
 * Renderiza una experiencia visual envolvente con soporte completo de modo claro/oscuro,
 * estética glassmorphism, gradientes dinámicos, adaptabilidad a la paleta del sitio, atajos rápidos y buscador integrado.
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    status: { type: Number, default: 404 },
    message: { type: String, default: '' },
});

const page = usePage();
const searchQuery = ref('');

const titulos = {
    404: 'Página no encontrada',
    403: 'Acceso Restringido',
    500: 'Error Interno del Servidor',
    503: 'Servicio en Mantenimiento',
};

const descripciones = {
    404: 'La página o publicación que estás buscando no existe, ha sido despublicada o la dirección indicada no es correcta.',
    403: 'Lo sentimos, no dispones de los permisos suficientes para acceder a este contenido o sección.',
    500: 'Ha ocurrido un problema inesperado en nuestros servidores. Ya estamos trabajando para solucionarlo.',
    503: 'Estamos realizando mejoras en el sistema. Por favor, intenta acceder nuevamente en unos minutos.',
};

const iconosEstado = {
    404: 'pi pi-compass',
    403: 'pi pi-lock',
    500: 'pi pi-server',
    503: 'pi pi-wrench',
};

const gradientesEstado = {
    404: 'from-emerald-500 via-teal-500 to-indigo-600',
    403: 'from-amber-500 via-orange-500 to-rose-600',
    500: 'from-rose-500 via-pink-500 to-purple-600',
    503: 'from-blue-500 via-indigo-500 to-teal-600',
};

const tituloError = computed(() => titulos[props.status] || 'Ha ocurrido un error');
const descripcionError = computed(() => props.message || descripciones[props.status] || 'Ocurrió un inconveniente al procesar tu solicitud.');
const iconoPrincipal = computed(() => iconosEstado[props.status] || 'pi pi-exclamation-circle');
const gradientePrincipal = computed(() => gradientesEstado[props.status] || 'from-emerald-500 to-indigo-600');

const buscar = () => {
    if (!searchQuery.value.trim()) return;
    router.get('/buscar', { q: searchQuery.value.trim() });
};

const irAtras = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    } else {
        router.get('/');
    }
};
</script>

<template>
    <Head :title="`${status} — ${tituloError} | ${$page.props.siteSettings?.site_name || 'CMS'}`" />

    <div class="relative min-h-[78vh] flex items-center justify-center px-4 py-12 sm:py-20 overflow-hidden">
        <!-- Luces y Gradientes de Fondo (Glow Orbs) -->
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-96 sm:w-[32rem] h-96 sm:h-[32rem] bg-emerald-500/15 dark:bg-emerald-500/10 rounded-full blur-3xl pointer-events-none animate-pulse" />
        <div class="absolute -bottom-32 left-1/4 w-80 sm:w-[28rem] h-80 sm:h-[28rem] bg-indigo-500/15 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />

        <div class="relative w-full max-w-2xl mx-auto z-10">
            
            <!-- Contenedor Glassmorphism Principal -->
            <div class="bg-white/75 dark:bg-zinc-900/75 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 rounded-3xl p-6 sm:p-12 shadow-xl shadow-zinc-900/5 text-center space-y-7">
                
                <!-- Badge de Estado con Icono -->
                <div class="inline-flex items-center justify-center">
                    <div class="relative flex items-center justify-center">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-emerald-500/15 to-indigo-500/15 border border-emerald-500/25 dark:border-emerald-400/25 flex items-center justify-center shadow-inner">
                            <i :class="[iconoPrincipal, 'text-2xl sm:text-3xl text-emerald-600 dark:text-emerald-400']" />
                        </div>
                    </div>
                </div>

                <!-- Código de Estado Gigante -->
                <div class="space-y-1">
                    <span
                        class="text-7xl sm:text-9xl font-black tracking-tighter bg-clip-text text-transparent bg-gradient-to-r select-none drop-shadow-xs"
                        :class="gradientePrincipal"
                        style="font-family: var(--heading-font), system-ui, sans-serif;"
                    >
                        {{ status }}
                    </span>
                    <div class="w-24 h-1 mx-auto bg-gradient-to-r from-transparent via-emerald-500 to-transparent rounded-full opacity-60" />
                </div>

                <!-- Título y Mensaje de Error -->
                <div class="space-y-2.5">
                    <h1
                        class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight"
                        style="font-family: var(--heading-font), system-ui, sans-serif;"
                    >
                        {{ tituloError }}
                    </h1>
                    <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 max-w-md mx-auto leading-relaxed">
                        {{ descripcionError }}
                    </p>
                </div>

                <!-- Buscador Integrado (Solo visible en 404) -->
                <form v-if="status === 404" @submit.prevent="buscar" class="max-w-md mx-auto relative group pt-2">
                    <div class="relative flex items-center">
                        <i class="pi pi-search absolute left-4 text-zinc-400 dark:text-zinc-500 text-base group-focus-within:text-emerald-500 transition-colors pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="¿Qué estabas buscando? Escribe aquí..."
                            class="w-full pl-11 pr-24 py-3 bg-zinc-50/80 dark:bg-zinc-800/60 rounded-2xl border border-zinc-200 dark:border-zinc-700/80 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all shadow-2xs"
                        />
                        <button
                            type="submit"
                            class="absolute right-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-semibold rounded-xl transition-all shadow-xs cursor-pointer"
                        >
                            Buscar
                        </button>
                    </div>
                </form>

                <!-- Botones de Acción Rápida -->
                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-semibold text-xs sm:text-sm rounded-xl shadow-md hover:shadow-lg transition-all cursor-pointer"
                        style="background-color: var(--button-primary-bg, #10b981); color: var(--button-primary-text, #ffffff); border-radius: var(--button-border-radius, 0.75rem);"
                    >
                        <i class="pi pi-home text-xs sm:text-sm" />
                        <span>Volver a la Portada</span>
                    </Link>

                    <button
                        type="button"
                        @click="irAtras"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700/80 text-zinc-700 dark:text-zinc-200 font-semibold text-xs sm:text-sm rounded-xl transition-all active:scale-95 cursor-pointer"
                        style="border-radius: var(--button-border-radius, 0.75rem);"
                    >
                        <i class="pi pi-arrow-left text-xs sm:text-sm" />
                        <span>Regresar Atrás</span>
                    </button>
                </div>

            </div>

        </div>
    </div>
</template>

