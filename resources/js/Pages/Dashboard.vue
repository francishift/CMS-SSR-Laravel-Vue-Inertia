<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    estadisticasEnvios: {
        type: Object,
        default: () => ({
            total: 0,
            no_leidos: 0,
            ultimo_envio: null,
        }),
    },
    estadisticasDescargas: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const usuario = computed(() => page.props.auth.user);

const enviosNoLeidos = computed(() => Number(props.estadisticasEnvios?.no_leidos ?? 0));
const totalEnvios = computed(() => Number(props.estadisticasEnvios?.total ?? 0));

const moduloDescargasActivo = computed(() => {
    try {
        return Boolean(props.estadisticasDescargas?.disponible && typeof route === 'function' && route().has('downloads.admin.index'));
    } catch {
        return false;
    }
});

const subtituloDescargas = computed(() => {
    if (!props.estadisticasDescargas) return '';
    const pub = Number(props.estadisticasDescargas.published_items ?? 0);
    const dl = Number(props.estadisticasDescargas.total_downloads ?? 0);
    if (pub === 0 && Number(props.estadisticasDescargas.total_items ?? 0) === 0) {
        return 'Sin recursos subidos';
    }
    return `${pub} activo${pub !== 1 ? 's' : ''} · ${dl} descarga${dl !== 1 ? 's' : ''}`;
});

const saludoHora = () => {
    const hora = new Date().getHours();
    if (hora < 12) return 'Buenos días';
    if (hora < 20) return 'Buenas tardes';
    return 'Buenas noches';
};

const fechaActualFormateada = computed(() => {
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fecha = new Date().toLocaleDateString('es-ES', opciones);
    return fecha.charAt(0).toUpperCase() + fecha.slice(1);
});

// Tarjetas de Módulos con Gradientes Premium, Iconos Neón y Resplandores Adaptativos
const modulosPanel = computed(() => [
    {
        id: 'envios',
        titulo: 'Bandeja de Envíos',
        subtitulo: totalEnvios.value > 0 ? `${totalEnvios.value} recibidos en total` : 'Sin mensajes recibidos',
        badge: enviosNoLeidos.value > 0
            ? `${enviosNoLeidos.value} nuevo${enviosNoLeidos.value > 1 ? 's' : ''}`
            : 'Al día',
        badgeColor: enviosNoLeidos.value > 0
            ? 'bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/40 font-extrabold shadow-xs'
            : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
        icon: 'pi pi-inbox',
        iconGradient: enviosNoLeidos.value > 0
            ? 'from-rose-500 to-amber-500 shadow-rose-500/30'
            : 'from-blue-600 to-indigo-600 shadow-blue-500/30',
        glowBg: enviosNoLeidos.value > 0
            ? 'from-rose-500/20 via-amber-500/10 to-transparent'
            : 'from-blue-500/10 via-indigo-500/5 to-transparent',
        cardGradient: enviosNoLeidos.value > 0
            ? 'border-rose-400/70 dark:border-rose-500/60 shadow-lg shadow-rose-500/10 ring-1 ring-rose-500/30'
            : 'hover:border-blue-500/50 hover:shadow-blue-500/10',
        accentText: enviosNoLeidos.value > 0
            ? 'text-rose-600 dark:text-rose-400 group-hover:text-rose-500'
            : 'group-hover:text-blue-500 dark:group-hover:text-blue-400',
        btnPrimaryBg: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-blue-500/20',
        descripcion: 'Supervisa y responde mensajes, consultas y leads comerciales recibidos desde la web.',
        rutaVer: route('panel.formularios.submissions.all'),
        avisoNuevo: enviosNoLeidos.value > 0,
        textoAccion: 'Ver Bandeja',
    },
    {
        id: 'paginas',
        titulo: 'Páginas',
        subtitulo: '',
        badge: 'Maquetador de páginas',
        badgeColor: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
        icon: 'pi pi-desktop',
        iconGradient: 'from-emerald-500 to-teal-600 shadow-emerald-500/30',
        glowBg: 'from-emerald-500/10 via-emerald-500/5 to-transparent',
        cardGradient: 'hover:border-emerald-500/50 hover:shadow-emerald-500/10',
        accentText: 'group-hover:text-emerald-500 dark:group-hover:text-emerald-400',
        btnPrimaryBg: 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-emerald-500/20',
        descripcion: 'Diseña héroes, grids, bloques interactivos y secciones SSR sin tocar código.',
        rutaVer: route('paginas.index'),
        rutaCrear: route('paginas.create'),
        textoCrear: 'Nueva Página',
    },
    {
        id: 'articulos',
        titulo: 'Artículos',
        subtitulo: '',
        badge: 'Gestión de artículos',
        badgeColor: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30',
        icon: 'pi pi-file-edit',
        iconGradient: 'from-indigo-500 to-violet-600 shadow-indigo-500/30',
        glowBg: 'from-indigo-500/10 via-indigo-500/5 to-transparent',
        cardGradient: 'hover:border-indigo-500/50 hover:shadow-indigo-500/10',
        accentText: 'group-hover:text-indigo-500 dark:group-hover:text-indigo-400',
        btnPrimaryBg: 'bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white shadow-indigo-500/20',
        descripcion: 'Publica entradas, redacta noticias y optimiza títulos, extractos e imágenes meta.',
        rutaVer: route('articulos.index'),
        rutaCrear: route('articulos.create'),
        textoCrear: 'Nuevo Artículo',
    },
    ...(moduloDescargasActivo.value ? [{
        id: 'descargas',
        titulo: 'Descargas',
        subtitulo: subtituloDescargas.value,
        badge: props.estadisticasDescargas?.visibilidad === 'public' ? 'Catálogo Público' : 'Solo Backend',
        badgeColor: props.estadisticasDescargas?.visibilidad === 'public'
            ? 'bg-lime-500/10 text-lime-700 dark:text-lime-300 border-lime-500/30'
            : 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30',
        icon: 'pi pi-download',
        iconGradient: 'from-lime-500 to-emerald-600 shadow-lime-500/30',
        glowBg: 'from-lime-500/10 via-emerald-500/5 to-transparent',
        cardGradient: 'hover:border-lime-500/50 hover:shadow-lime-500/10',
        accentText: 'group-hover:text-lime-600 dark:group-hover:text-lime-400',
        btnPrimaryBg: 'bg-gradient-to-r from-lime-600 to-emerald-600 hover:from-lime-500 hover:to-emerald-500 text-white shadow-lime-500/20',
        descripcion: 'Sube paquetes privados, ordena plugins y controla la entrega segura de descargas.',
        rutaVer: route('downloads.admin.index'),
        rutaCrear: route('downloads.admin.create'),
        textoCrear: 'Nueva Descarga',
    }] : []),
    {
        id: 'formularios',
        titulo: 'Formularios',
        subtitulo: '',
        badge: 'Formularios dinámicos',
        badgeColor: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border-cyan-500/30',
        icon: 'pi pi-file-check',
        iconGradient: 'from-cyan-500 to-blue-600 shadow-cyan-500/30',
        glowBg: 'from-cyan-500/10 via-cyan-500/5 to-transparent',
        cardGradient: 'hover:border-cyan-500/50 hover:shadow-cyan-500/10',
        accentText: 'group-hover:text-cyan-500 dark:group-hover:text-cyan-400',
        btnPrimaryBg: 'bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white shadow-cyan-500/20',
        descripcion: 'Crea formularios de contacto configurables con bandeja de envíos y antispam.',
        rutaVer: route('panel.formularios.index'),
        rutaCrear: route('panel.formularios.create'),
        textoCrear: 'Nuevo Formulario',
    },
    {
        id: 'categorias',
        titulo: 'Categorías',
        subtitulo: '',
        badge: 'Categorías y campos extras',
        badgeColor: 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30',
        icon: 'pi pi-tags',
        iconGradient: 'from-amber-500 to-orange-600 shadow-amber-500/30',
        glowBg: 'from-amber-500/10 via-amber-500/5 to-transparent',
        cardGradient: 'hover:border-amber-500/50 hover:shadow-amber-500/10',
        accentText: 'group-hover:text-amber-500 dark:group-hover:text-amber-400',
        btnPrimaryBg: 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white shadow-amber-500/20',
        descripcion: 'Clasifica artículos y contenidos en árboles organizados de alta velocidad.',
        rutaVer: route('categorias.index'),
    },
    {
        id: 'plantillas',
        titulo: 'Plantillas Visuales',
        subtitulo: '',
        badge: 'Theme Builder',
        badgeColor: 'bg-fuchsia-500/10 text-fuchsia-700 dark:text-fuchsia-300 border-fuchsia-500/30',
        icon: 'pi pi-palette',
        iconGradient: 'from-fuchsia-500 to-pink-600 shadow-fuchsia-500/30',
        glowBg: 'from-fuchsia-500/10 via-fuchsia-500/5 to-transparent',
        cardGradient: 'hover:border-fuchsia-500/50 hover:shadow-fuchsia-500/10',
        accentText: 'group-hover:text-fuchsia-500 dark:group-hover:text-fuchsia-400',
        btnPrimaryBg: 'bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-500 hover:to-pink-500 text-white shadow-fuchsia-500/20',
        descripcion: 'Diseña y asigna plantillas visuales dinámicas para categorías y detalle de artículos.',
        rutaVer: route('plantillas.index'),
    },
    {
        id: 'media',
        titulo: 'Medios',
        subtitulo: '',
        badge: 'Gestión de archivos',
        badgeColor: 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/30',
        icon: 'pi pi-images',
        iconGradient: 'from-sky-500 to-cyan-600 shadow-sky-500/30',
        glowBg: 'from-sky-500/10 via-sky-500/5 to-transparent',
        cardGradient: 'hover:border-sky-500/50 hover:shadow-sky-500/10',
        accentText: 'group-hover:text-sky-500 dark:group-hover:text-sky-400',
        btnPrimaryBg: 'bg-gradient-to-r from-sky-600 to-cyan-600 hover:from-sky-500 hover:to-cyan-500 text-white shadow-sky-500/20',
        descripcion: 'Gestor multimedia drag-and-drop con compresión inteligente de imágenes.',
        rutaVer: route('media.index'),
    },
    {
        id: 'menus',
        titulo: 'Menús y header',
        subtitulo: '',
        badge: 'Estructura Web',
        badgeColor: 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30',
        icon: 'pi pi-sitemap',
        iconGradient: 'from-purple-500 to-fuchsia-600 shadow-purple-500/30',
        glowBg: 'from-purple-500/10 via-purple-500/5 to-transparent',
        cardGradient: 'hover:border-purple-500/50 hover:shadow-purple-500/10',
        accentText: 'group-hover:text-purple-500 dark:group-hover:text-purple-400',
        btnPrimaryBg: 'bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-500 hover:to-fuchsia-500 text-white shadow-purple-500/20',
        descripcion: 'Construye barras de navegación, dropdowns multinivel y pies de página.',
        rutaVer: route('menus.index'),
    },
    {
        id: 'usuarios',
        titulo: 'Usuarios',
        subtitulo: '',
        badge: 'Control de Acceso',
        badgeColor: 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/30',
        icon: 'pi pi-users',
        iconGradient: 'from-rose-500 to-pink-600 shadow-rose-500/30',
        glowBg: 'from-rose-500/10 via-rose-500/5 to-transparent',
        cardGradient: 'hover:border-rose-500/50 hover:shadow-rose-500/10',
        accentText: 'group-hover:text-rose-500 dark:group-hover:text-rose-400',
        btnPrimaryBg: 'bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white shadow-rose-500/20',
        descripcion: 'Administra accesos del equipo, roles de edición y auditoría de perfiles.',
        rutaVer: route('usuarios.index'),
        rutaCrear: route('usuarios.create'),
        textoCrear: 'Nuevo Usuario',
    },
    {
        id: 'configuracion',
        titulo: 'Configuración',
        subtitulo: '',
        badge: 'Aspecto Global',
        badgeColor: 'bg-teal-500/10 text-teal-700 dark:text-teal-300 border-teal-500/30',
        icon: 'pi pi-sliders-h',
        iconGradient: 'from-teal-500 to-emerald-600 shadow-teal-500/30',
        glowBg: 'from-teal-500/10 via-teal-500/5 to-transparent',
        cardGradient: 'hover:border-teal-500/50 hover:shadow-teal-500/10',
        accentText: 'group-hover:text-teal-500 dark:group-hover:text-teal-400',
        btnPrimaryBg: 'bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white shadow-teal-500/20',
        descripcion: 'Personaliza colores corporativos, tipografía, gradientes de fondo y SEO.',
        rutaVer: route('configuracion.index'),
    },
]);
</script>

<template>
    <Head title="Panel de control" />

    <AuthenticatedLayout>
        <!-- Cabecera del topbar -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-zinc-900 dark:text-white leading-none">
                        Panel de Control
                    </h1>
                
                </div>
                <div class="hidden sm:flex items-center gap-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800/80 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700/60">
                    <i class="pi pi-calendar text-emerald-500 text-xs" />
                    <span>{{ fechaActualFormateada }}</span>
                </div>
            </div>
        </template>

        <!-- Contenido Principal (Pantalla Completa w-full) -->
        <div class="p-6 space-y-6 w-full">

            <!-- Banner de Bienvenida Estilizado -->
            <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 bg-gradient-to-r from-zinc-900 via-zinc-900 to-zinc-950 text-white p-6 sm:p-8 shadow-md">
                <!-- Resplandor decorativo de fondo -->
                <div class="absolute -top-24 -right-24 w-72 h-72 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none" />
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none" />

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-1.5">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                            <span>Sistema CMS Activo</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                            {{ saludoHora() }}, {{ usuario.name }}
                        </h2>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <a
                            href="/"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-800/90 hover:bg-zinc-700 text-zinc-100 border border-zinc-700/80 text-xs font-semibold transition-all shadow-xs"
                        >
                            <i class="pi pi-external-link text-xs text-emerald-400" />
                            <span>Ver Sitio Público</span>
                        </a>
                        <Link
                            :href="route('paginas.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold transition-all shadow-lg shadow-emerald-500/20"
                        >
                            <i class="pi pi-plus text-xs" />
                            <span>Crear Nueva Página</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Título de Sección -->
            <div class="flex items-center justify-between pt-1">
                <div>
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-widest flex items-center gap-2">
                        <i class="pi pi-th-large text-emerald-500 text-xs" />
                        <span>Módulos del Sistema & Accesos Directos</span>
                    </h3>
                </div>
            </div>

            <!-- Grilla de Tarjetas en 5 Columnas Centradas con Gradientes Ultra-Premium (Tarjeta Completa Clicable) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5 justify-center">
                <Link
                    v-for="modulo in modulosPanel"
                    :key="modulo.id"
                    :href="modulo.rutaVer"
                    :class="[
                        'group relative overflow-hidden flex flex-col justify-between p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800/80 bg-white dark:bg-zinc-900/95 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl cursor-pointer block',
                        modulo.cardGradient
                    ]"
                >
                    <!-- Resplandor sutil en la esquina superior derecha -->
                    <div :class="['absolute -top-12 -right-12 w-32 h-32 bg-gradient-to-bl rounded-full blur-2xl pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-500', modulo.glowBg]" />

                    <div class="relative z-10 space-y-4">
                        <!-- Cabecera de Tarjeta: Icono Neón + Badge Glassmorphic -->
                        <div class="flex items-start justify-between gap-3">
                            <div :class="['w-12 h-12 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white text-xl shadow-md transition-transform duration-300 group-hover:scale-110 relative', modulo.iconGradient]">
                                <i :class="modulo.icon" />
                                <span
                                    v-if="modulo.avisoNuevo"
                                    class="absolute -top-1 -right-1 flex h-3.5 w-3.5"
                                >
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75" />
                                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500 border-2 border-white dark:border-zinc-900" />
                                </span>
                            </div>
                            <span :class="['px-2.5 py-1 text-[10px] font-bold rounded-lg border uppercase tracking-wider backdrop-blur-xs flex items-center gap-1.5', modulo.badgeColor]">
                                <span v-if="modulo.avisoNuevo" class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping shrink-0" />
                                <span>{{ modulo.badge }}</span>
                            </span>
                        </div>

                        <!-- Títulos -->
                        <div class="space-y-1">
                            <div class="text-[11px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                {{ modulo.subtitulo }}
                            </div>
                            <h4 :class="['text-base font-extrabold text-zinc-900 dark:text-zinc-100 transition-colors', modulo.accentText]">
                                {{ modulo.titulo }}
                            </h4>
                        </div>

                        <!-- Aviso destacado de nuevo mensaje recibido -->
                        <div
                            v-if="modulo.avisoNuevo"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold"
                        >
                            <i class="pi pi-bell text-[10px] animate-bounce" />
                            <span>¡Nuevo envío recibido!</span>
                        </div>
                    </div>

                    <!-- Pie de la Tarjeta con Botones de Acción Estilizados -->
                    <div class="relative z-10 pt-4 mt-4 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-700 dark:text-zinc-300 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors"
                        >
                            <span>{{ modulo.textoAccion || 'Gestionar' }}</span>
                            <i class="pi pi-arrow-right text-[10px] transition-transform duration-200 group-hover:translate-x-1" />
                        </span>

                        <button
                            v-if="modulo.rutaCrear"
                            type="button"
                            @click.stop.prevent="router.visit(modulo.rutaCrear)"
                            :class="['inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-lg transition-all shadow-xs cursor-pointer', modulo.btnPrimaryBg]"
                        >
                            <i class="pi pi-plus text-[9px]" />
                            <span>{{ modulo.textoCrear }}</span>
                        </button>
                    </div>
                </Link>
            </div>

            <!-- Pie Informativo del Sistema -->
            <div class="p-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield text-emerald-500 text-sm" />
                    <span>Conectado como <strong class="text-zinc-800 dark:text-zinc-200 font-bold">{{ usuario.email }}</strong></span>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="route('profile.edit')" class="hover:text-zinc-900 dark:hover:text-zinc-100 font-semibold transition-colors flex items-center gap-1">
                        <i class="pi pi-user-edit text-xs text-indigo-400" />
                        <span>Editar Perfil</span>
                    </Link>
                    <span class="text-zinc-300 dark:text-zinc-700">•</span>
                    <Link :href="route('configuracion.index')" class="hover:text-zinc-900 dark:hover:text-zinc-100 font-semibold transition-colors flex items-center gap-1">
                        <i class="pi pi-palette text-xs text-emerald-400" />
                        <span>Paleta & Estilos</span>
                    </Link>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
