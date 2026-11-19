<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

/** Mensaje flash de error (p.ej. OAuth rechazado) */
const flashError = usePage().props.flash?.error;
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Mensaje de error flash (OAuth, cuenta desactivada, etc.) -->
        <div
            v-if="flashError"
            class="mb-4 rounded-xl border border-red-200 dark:border-red-800/60 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300 flex items-start gap-2"
        >
            <i class="pi pi-exclamation-triangle mt-0.5 shrink-0" />
            <span>{{ flashError }}</span>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" />

                <PasswordInput
                    id="password"
                    v-model="form.password"
                    autocomplete="current-password"
                    required
                    class="mt-1"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-slate-600 dark:text-slate-400">Recordarme</span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white underline focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                >
                    ¿Olvidaste tu contraseña?
                </Link>

                <PrimaryButton
                    class="ms-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar sesión
                </PrimaryButton>
            </div>
        </form>

        <!-- Separador visual -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-white dark:bg-zinc-900 px-3 text-zinc-400 dark:text-zinc-500 select-none">
                    o continuar con
                </span>
            </div>
        </div>

        <!-- Botón de Google OAuth -->
        <a
            :href="route('auth.google.redirect')"
            class="google-oauth-btn flex w-full items-center justify-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
        >
            <!-- Logo oficial de Google (SVG inline) -->
            <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Acceder con Google
        </a>
    </GuestLayout>
</template>
