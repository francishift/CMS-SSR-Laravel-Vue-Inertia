<script setup>
/**
 * Layout principal del Panel de Administración (Backend).
 * 
 * Gestiona el sidebar lateral, la barra superior (topbar),
 * modo oscuro y notificaciones Flash/Toast con PrimeVue.
 * Totalmente encapsulado en el módulo Admin.
 */
import { ref, computed, onMounted, onUnmounted, watch, provide } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';

const page    = usePage();
const usuario = computed(() => page.props.auth?.user);
const esAdmin = computed(() => usuario.value?.roles?.includes('admin'));

const toast = useToast();

// Convierte los mensajes flash de Inertia en Toasts de PrimeVue
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.add({ severity: 'success', summary: 'Correcto', detail: flash.success, life: 4000 });
        }
        if (flash?.error) {
            toast.add({ severity: 'error', summary: 'Error', detail: flash.error, life: 5000 });
        }
    },
    { deep: true },
);

/* ─── Detección de vista de Builder / Maquetador ──── */
const esRutaBuilder = computed(() => {
    try {
        return route().current('paginas.builder')
            || route().current('plantillas.builder')
            || route().current('downloads.admin.builder')
            || route().current('downloads.admin.item-builder');
    } catch {
        return false;
    }
});

/* ─── Sidebar móvil ────────────────────────────────── */
const sidebarAbierto = ref(false);

function cerrarSidebar() {
    sidebarAbierto.value = false;
}

/* ─── Sidebar Desktop colapsable / expandible ──────── */
const sidebarColapsado = ref(false);

function obtenerClaveStorage() {
    return esRutaBuilder.value ? 'builder_sidebar_colapsado' : 'admin_sidebar_colapsado';
}

function inicializarEstadoSidebar() {
    const clave = obtenerClaveStorage();
    const guardado = localStorage.getItem(clave);
    if (guardado !== null) {
        sidebarColapsado.value = guardado === 'true';
    } else {
        // En el builder, si el usuario aún no ha guardado preferencia, colapsamos por defecto para maximizar espacio
        sidebarColapsado.value = Boolean(esRutaBuilder.value);
    }
}

function toggleSidebarDesktop() {
    sidebarColapsado.value = !sidebarColapsado.value;
    localStorage.setItem(obtenerClaveStorage(), String(sidebarColapsado.value));
}

// Reactividad ante navegación interna en la SPA
watch(esRutaBuilder, () => {
    inicializarEstadoSidebar();
});

// Atajo de teclado: Ctrl+B o Cmd+B
function handleKeydown(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        const tag = document.activeElement?.tagName;
        const isEditable = document.activeElement?.isContentEditable;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || isEditable) {
            return;
        }
        e.preventDefault();
        toggleSidebarDesktop();
    }
}

provide('sidebarColapsado', sidebarColapsado);
provide('toggleSidebar', toggleSidebarDesktop);

/* ─── Modo oscuro del Panel — persiste en localStorage ── */
const modoOscuro = ref(false);

onMounted(() => {
    const guardado = localStorage.getItem('admin_modo_oscuro');
    modoOscuro.value = guardado === null ? true : guardado === 'true';
    aplicarModo();

    inicializarEstadoSidebar();
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

function toggleModoOscuro() {
    modoOscuro.value = !modoOscuro.value;
    localStorage.setItem('admin_modo_oscuro', modoOscuro.value);
    aplicarModo();
}

function aplicarModo() {
    document.documentElement.classList.toggle('dark', modoOscuro.value);
}

/* ─── Navegación ────────────────────────────────────── */
const navItems = computed(() => [
    {
        label: 'Panel de control',
        icon:  'pi pi-home',
        route: 'dashboard',
        activo: route().current('dashboard'),
    },
    ...(esAdmin.value ? [
        {
            label: 'Artículos',
            icon:  'pi pi-file-edit',
            route: 'articulos.index',
            activo: route().current('articulos.*'),
        },
        {
            label: 'Páginas',
            icon:  'pi pi-file',
            route: 'paginas.index',
            activo: route().current('paginas.*'),
        },
        {
            label: 'Categorías',
            icon:  'pi pi-tags',
            route: 'categorias.index',
            activo: route().current('categorias.*'),
        },
        ...(route().has('downloads.admin.index') ? [{
            label: 'Descargas',
            icon:  'pi pi-download',
            route: 'downloads.admin.index',
            activo: route().current('downloads.admin.*'),
        }] : []),
        {
            label: 'Plantillas Visuales',
            icon:  'pi pi-palette',
            route: 'plantillas.index',
            activo: route().current('plantillas.*'),
        },
        {
            label: 'Menús',
            icon:  'pi pi-bars',
            route: 'menus.index',
            activo: route().current('menus.*'),
        },
        {
            label: 'Formularios',
            icon:  'pi pi-file-check',
            route: 'panel.formularios.index',
            activo: route().current('panel.formularios.index') || route().current('panel.formularios.create') || route().current('panel.formularios.edit'),
        },
        {
            label: 'Bandeja de Envíos',
            icon:  'pi pi-inbox',
            route: 'panel.formularios.submissions.all',
            activo: route().current('panel.formularios.submissions.*'),
        },
        {
            label: 'Biblioteca de Medios',
            icon:  'pi pi-images',
            route: 'media.index',
            activo: route().current('media.*'),
        },
        {
            label: 'Usuarios',
            icon:  'pi pi-users',
            route: 'usuarios.index',
            activo: route().current('usuarios.*'),
        },
        ...(route().has('actividad.index') ? [{
            label: 'Actividad y Logs',
            icon:  'pi pi-history',
            route: 'actividad.index',
            activo: route().current('actividad.*'),
        }] : []),
        {
            label: 'Configuración',
            icon:  'pi pi-cog',
            route: 'configuracion.index',
            activo: route().current('configuracion.*'),
        },
    ] : []),
]);

// Sección activa del menú lateral (fallback limpio cuando una vista no define slot #header)
const seccionActiva = computed(() => navItems.value.find(item => item.activo) ?? null);

/* ─── Utilidades ────────────────────────────────────── */
function getIniciales(nombre) {
    return nombre
        ?.split(' ')
        .slice(0, 2)
        .map(n => n[0])
        .join('')
        .toUpperCase() ?? 'U';
}

function cerrarSesion() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="admin-layout flex h-screen overflow-hidden bg-zinc-100 dark:bg-zinc-950 transition-colors duration-300">

        <!-- Toasts globales (mensajes flash de Inertia) -->
        <Toast position="bottom-right" />
        <!-- Diálogo de confirmación global -->
        <ConfirmDialog />
        <!-- Overlay oscuro para móvil -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarAbierto"
                class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm lg:hidden"
                @click="cerrarSidebar"
            />
        </Transition>

        <!-- ══════════════════════════════════════════════
             SIDEBAR
        ═══════════════════════════════════════════════ -->
        <!-- Trigger flotante en el borde izquierdo cuando el sidebar está colapsado (Desktop) -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-x-3"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 -translate-x-3"
        >
            <div
                v-if="sidebarColapsado"
                class="hidden lg:flex fixed left-0 top-1/2 -translate-y-1/2 z-40"
            >
                <button
                    type="button"
                    @click="toggleSidebarDesktop"
                    class="flex items-center justify-center h-14 w-4 hover:w-6 bg-zinc-900/90 hover:bg-zinc-900 text-zinc-400 hover:text-emerald-400 rounded-r-xl border-y border-r border-zinc-700/80 shadow-2xl backdrop-blur-md transition-all duration-200 cursor-pointer overflow-hidden group"
                    title="Mostrar menú lateral (Ctrl+B)"
                    v-tooltip.right="'Mostrar menú lateral (Ctrl+B)'"
                    aria-label="Mostrar menú lateral"
                >
                    <i class="pi pi-chevron-right text-[11px] group-hover:translate-x-0.5 transition-transform" />
                </button>
            </div>
        </Transition>

        <!-- ══════════════════════════════════════════════
             SIDEBAR
        ═══════════════════════════════════════════════ -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 flex flex-col bg-gradient-to-b from-zinc-900 via-zinc-950 to-zinc-900 shadow-2xl overflow-hidden',
                'transition-[width,transform,opacity,border-color] duration-300 ease-in-out',
                'lg:static lg:translate-x-0',
                sidebarAbierto ? 'translate-x-0 w-64 border-r border-zinc-800/80' : '-translate-x-full w-64',
                sidebarColapsado
                    ? 'lg:w-0 lg:border-r-0 lg:opacity-0 lg:pointer-events-none'
                    : 'lg:w-64 lg:border-r lg:border-zinc-800/80 lg:opacity-100',
            ]"
        >
            <!-- Contenedor interior rígido para evitar deformaciones o reflujo de texto durante la animación -->
            <div class="relative w-64 min-w-[16rem] h-full flex flex-col">
                <!-- Resplandores y lucernarios decorativos de fondo en el Sidebar (Estilo Banner Encabezado) -->
                <div class="absolute -top-24 -left-24 w-64 h-64 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none" />
                <div class="absolute top-1/2 -right-24 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none" />
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none" />

                <!-- Logo y nombre de la app + botón de colapso rápido -->
                <div class="relative z-10 flex h-16 items-center justify-between border-b border-zinc-800/80 px-4">
                    <Link :href="route('dashboard')" class="flex items-center gap-3 min-w-0 group/logo">
                        <!-- Contenedor con gradiente emerald y logo SVG -->
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-md shadow-emerald-500/30 group-hover/logo:scale-105 transition-transform">
                            <ApplicationLogo class="h-5 w-5 brightness-0 invert" />
                        </div>
                        <span class="truncate text-sm font-bold text-white tracking-tight">
                            {{ $page.props.appName ?? 'CMS SSR' }}
                        </span>
                    </Link>

                    <!-- Botón elegante de colapsar sidebar en desktop -->
                    <button
                        type="button"
                        @click="toggleSidebarDesktop"
                        class="hidden lg:flex items-center justify-center h-8 w-8 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800/80 transition-all duration-200 cursor-pointer group/btn"
                        title="Ocultar menú lateral (Ctrl+B)"
                        v-tooltip.bottom="'Ocultar menú lateral (Ctrl+B)'"
                        aria-label="Ocultar menú lateral"
                    >
                        <i class="pi pi-angle-left text-base group-hover/btn:-translate-x-0.5 transition-transform" />
                    </button>
                </div>

                <!-- Menú de navegación -->
                <nav class="relative z-10 flex-1 overflow-y-auto py-5 px-3 space-y-1">
                    <p class="mb-2.5 px-3 text-[11px] font-mono font-bold uppercase tracking-widest text-zinc-500">
                        Menú
                    </p>

                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        @click="cerrarSidebar"
                        :class="[
                            'group flex items-center rounded-xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200',
                            item.activo
                                ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-600/30'
                                : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-white',
                        ]"
                    >
                        <i
                            :class="[
                                item.icon,
                                'mr-3 text-base flex-shrink-0 transition-transform duration-200',
                                item.activo ? 'text-white' : 'text-zinc-400 group-hover:text-emerald-400 group-hover:scale-110',
                            ]"
                        />
                        <span class="flex-1 truncate">{{ item.label }}</span>
                    </Link>
                </nav>

                <!-- Info y acciones del usuario autenticado -->
                <div class="relative z-10 border-t border-zinc-800/80 p-3 space-y-1 bg-zinc-950/40 backdrop-blur-sm">
                    <!-- Perfil -->
                    <Link
                        :href="route('profile.edit')"
                        class="group flex items-center gap-3 rounded-xl p-2.5 text-zinc-400 hover:bg-zinc-900/80 hover:text-white transition-all duration-200 border border-transparent hover:border-zinc-800/80"
                    >
                        <!-- Avatar con iniciales -->
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500/20 to-teal-500/20 ring-1 ring-emerald-500/30 text-emerald-400 text-xs font-bold shadow-xs">
                            {{ getIniciales(usuario?.name) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-zinc-200 leading-none group-hover:text-emerald-400 transition-colors">
                                {{ usuario?.name }}
                            </p>
                            <p class="mt-1 truncate text-[11px] text-zinc-500 leading-none">
                                {{ usuario?.email }}
                            </p>
                        </div>
                        <i class="pi pi-chevron-right text-xs text-zinc-600 group-hover:text-zinc-400 flex-shrink-0 transition-transform group-hover:translate-x-0.5" />
                    </Link>

                    <!-- Cerrar sesión -->
                    <button
                        @click="cerrarSesion"
                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-zinc-500 hover:bg-rose-500/10 hover:text-rose-400 transition-all duration-200 cursor-pointer"
                    >
                        <i class="pi pi-sign-out flex-shrink-0 text-sm group-hover:rotate-12 transition-transform" />
                        <span>Cerrar sesión</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- ══════════════════════════════════════════════
             ÁREA DE CONTENIDO
        ═══════════════════════════════════════════════ -->
        <div class="flex flex-1 flex-col overflow-hidden min-w-0">

            <!-- Topbar global compacta y resiliente en móvil/escritorio -->
            <header class="flex min-h-14 sm:min-h-16 items-center gap-2 sm:gap-4 border-b border-zinc-200 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 px-3 sm:px-4 lg:px-6 py-2 flex-shrink-0 transition-colors duration-300 shadow-sm">

                <!-- Botón hamburger — solo en móvil (< lg) -->
                <button
                    type="button"
                    @click="sidebarAbierto = !sidebarAbierto"
                    class="rounded-xl p-2 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors lg:hidden shrink-0 cursor-pointer"
                    aria-label="Abrir menú"
                >
                    <i class="pi pi-bars text-lg" />
                </button>

                <!-- Botón de conmutación de Sidebar en Desktop (>= lg) -->
                <button
                    type="button"
                    @click="toggleSidebarDesktop"
                    class="hidden lg:flex items-center justify-center rounded-xl p-2 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-all duration-200 shrink-0 cursor-pointer group"
                    :title="sidebarColapsado ? 'Mostrar menú lateral (Ctrl+B)' : 'Ocultar menú lateral (Ctrl+B)'"
                    v-tooltip.bottom="sidebarColapsado ? 'Mostrar menú lateral (Ctrl+B)' : 'Ocultar menú lateral (Ctrl+B)'"
                    aria-label="Alternar menú lateral"
                >
                    <!-- Icono moderno de panel lateral con animación -->
                    <svg
                        class="w-5 h-5 transition-transform duration-200 group-hover:scale-105"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect width="18" height="18" x="3" y="3" rx="3" />
                        <path d="M9 3v18" :class="sidebarColapsado ? 'opacity-30' : 'opacity-100'" />
                        <!-- Indicador direccional de chevron -->
                        <path
                            v-if="sidebarColapsado"
                            d="M13 12h4m0 0l-2-2m2 2l-2 2"
                            stroke-width="2"
                            class="text-emerald-500 stroke-emerald-500"
                        />
                        <path
                            v-else
                            d="M15 12h-4m0 0l2-2m-2 2l2 2"
                            stroke-width="2"
                        />
                    </svg>
                </button>

                <!-- Título contextual de la página (con fallback automático a la sección activa) -->
                <div class="admin-topbar-slot flex-1 min-w-0">
                    <slot v-if="$slots.header" name="header" />
                    <div v-else-if="seccionActiva" class="flex items-center gap-2 min-w-0">
                        <i :class="[seccionActiva.icon, 'text-emerald-600 dark:text-emerald-400 text-sm shrink-0 hidden sm:inline']" />
                        <span class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                            {{ seccionActiva.label }}
                        </span>
                    </div>
                </div>

                <!-- Acciones globales lado derecho -->
                <div class="flex items-center gap-0.5 sm:gap-1 shrink-0">
                    <!-- Enlace directo al Frontend (sitio público) -->
                    <a
                        href="/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-lg p-2 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center justify-center"
                        title="Ver sitio web (abrir en nueva pestaña)"
                        aria-label="Ver sitio web (abrir en nueva pestaña)"
                    >
                        <i class="pi pi-external-link text-base sm:text-lg" />
                    </a>

                    <!-- Toggle modo oscuro / claro -->
                    <button
                        type="button"
                        @click="toggleModoOscuro"
                        class="rounded-lg p-2 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                        :title="modoOscuro ? 'Activar modo claro' : 'Activar modo oscuro'"
                        :aria-label="modoOscuro ? 'Activar modo claro' : 'Activar modo oscuro'"
                    >
                        <i :class="['text-base sm:text-lg', modoOscuro ? 'pi pi-sun' : 'pi pi-moon']" />
                    </button>
                </div>
            </header>

            <!-- Contenido de la página -->
            <main :class="['flex-1', esRutaBuilder ? 'overflow-hidden' : 'overflow-y-auto']">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Garantiza una tipografía limpia sin recorte de trazos descendentes (g, p, y) en títulos truncados y oculta subtítulos accidentales en la topbar móvil */
.admin-topbar-slot :deep(h1) {
    line-height: 1.3;
}

@media (max-width: 639px) {
    .admin-topbar-slot :deep(p) {
        display: none;
    }
}
</style>
