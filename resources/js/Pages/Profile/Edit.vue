<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const usuario = computed(() => page.props.auth.user);

const tabActiva = ref('perfil');

// Iniciales del usuario para el avatar
const inicialesUsuario = computed(() => {
    if (!usuario.value?.name) return 'U';
    const partes = usuario.value.name.trim().split(' ');
    if (partes.length >= 2) {
        return (partes[0][0] + partes[1][0]).toUpperCase();
    }
    return usuario.value.name.substring(0, 2).toUpperCase();
});

// Roles formateados del usuario
const rolUsuario = computed(() => {
    if (Array.isArray(usuario.value?.roles) && usuario.value.roles.length) {
        return usuario.value.roles.map(r => r.name || r).join(', ');
    }
    return 'Administrador';
});
</script>

<template>
    <Head title="Mi Perfil" />

    <AuthenticatedLayout>
        <!-- Topbar Header con Botón de Volver Atrás -->
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <Link
                        :href="route('dashboard')"
                        class="p-1.5 sm:p-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition-all flex items-center justify-center shrink-0"
                        title="Volver al Dashboard"
                    >
                        <i class="pi pi-arrow-left text-xs sm:text-sm" />
                    </Link>
                    <div class="min-w-0">
                        <h1 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white truncate leading-none">
                            Mi Perfil
                        </h1>
                        <p class="hidden md:block mt-1 text-xs text-zinc-500 dark:text-zinc-400 truncate">
                            Configuración de tu cuenta y ajustes de credenciales
                        </p>
                    </div>
                </div>

                <Link
                    :href="route('dashboard')"
                    class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-semibold border border-zinc-200 dark:border-zinc-700 transition-all shrink-0"
                >
                    <i class="pi pi-th-large text-xs text-emerald-500" />
                    <span>Volver al Dashboard</span>
                </Link>
            </div>
        </template>

        <!-- Contenido de la Página -->
        <div class="p-4 sm:p-6 space-y-6 w-full">

            <!-- Banner de Cabecera del Perfil con Avatar Grandioso -->
            <div class="relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6 sm:p-8">
                <!-- Fondo sutil con resplandor decorativo -->
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />
                <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />

                <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start justify-between gap-6">
                    <div class="flex flex-col sm:flex-row items-center gap-5 text-center sm:text-left">
                        <!-- Avatar circular con Iniciales y Borde Neón -->
                        <div class="relative group">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-gradient-to-tr from-emerald-600 via-teal-600 to-indigo-600 text-white font-black text-2xl sm:text-3xl flex items-center justify-center shadow-lg shadow-emerald-500/20 border-2 border-white dark:border-zinc-800 transition-transform duration-300 group-hover:scale-105">
                                {{ inicialesUsuario }}
                            </div>
                            <span class="absolute bottom-0 right-0 w-5 h-5 rounded-full bg-emerald-500 border-2 border-white dark:border-zinc-900" title="Usuario Activo" />
                        </div>

                        <!-- Detalles del Usuario -->
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <h2 class="text-xl sm:text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                    {{ usuario.name }}
                                </h2>
                                <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                    {{ rolUsuario }}
                                </span>
                            </div>
                            <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400 flex items-center justify-center sm:justify-start gap-1.5">
                                <i class="pi pi-envelope text-zinc-400 text-xs" />
                                <span>{{ usuario.email }}</span>
                            </p>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500 pt-0.5">
                                Cuenta de usuario verificada en el sistema
                            </p>
                        </div>
                    </div>

                    <!-- Botones de Navegación Rápida -->
                    <div class="flex items-center gap-2 shrink-0">
                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-xs font-bold transition-all border border-zinc-200 dark:border-zinc-700"
                        >
                            <i class="pi pi-arrow-left text-xs" />
                            <span>Volver Atrás</span>
                        </Link>
                    </div>
                </div>

                <!-- Tabs de Secciones del Perfil -->
                <div class="flex items-center gap-2 mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800/80 overflow-x-auto">
                    <button
                        type="button"
                        @click="tabActiva = 'perfil'"
                        :class="[
                            'px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer shrink-0',
                            tabActiva === 'perfil'
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                : 'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        ]"
                    >
                        <i class="pi pi-user text-xs" />
                        <span>Información Personal</span>
                    </button>

                    <button
                        type="button"
                        @click="tabActiva = 'password'"
                        :class="[
                            'px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer shrink-0',
                            tabActiva === 'password'
                                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30'
                                : 'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        ]"
                    >
                        <i class="pi pi-lock text-xs" />
                        <span>Cambiar Contraseña</span>
                    </button>

                    <button
                        type="button"
                        @click="tabActiva = 'eliminar'"
                        :class="[
                            'px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer shrink-0',
                            tabActiva === 'eliminar'
                                ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30'
                                : 'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        ]"
                    >
                        <i class="pi pi-trash text-xs" />
                        <span>Zona de Riesgo</span>
                    </button>
                </div>
            </div>

            <!-- Contenido de las Pestañas -->
            <div class="space-y-6">

                <!-- Sección: Información Personal -->
                <div v-show="tabActiva === 'perfil'" class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6 sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <!-- Sección: Cambiar Contraseña -->
                <div v-show="tabActiva === 'password'" class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6 sm:p-8">
                    <UpdatePasswordForm />
                </div>

                <!-- Sección: Eliminar Cuenta -->
                <div v-show="tabActiva === 'eliminar'" class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-white dark:bg-zinc-900 shadow-sm p-6 sm:p-8">
                    <DeleteUserForm />
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
