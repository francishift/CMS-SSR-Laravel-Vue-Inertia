<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section class="space-y-6 w-full">
        <header class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                    <i class="pi pi-user text-emerald-500 text-sm" />
                    <span>Información del Perfil</span>
                </h2>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Actualiza tu nombre de usuario público y tu dirección de correo electrónico corporativo.
                </p>
            </div>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-5 w-full"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <InputLabel for="name" value="Nombre Completo" class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1" />
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                            <i class="pi pi-user text-xs" />
                        </span>
                        <TextInput
                            id="name"
                            type="text"
                            class="pl-9 w-full text-xs font-medium"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Tu nombre completo"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Correo Electrónico" class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1" />
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                            <i class="pi pi-envelope text-xs" />
                        </span>
                        <TextInput
                            id="email"
                            type="email"
                            class="pl-9 w-full text-xs font-medium"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="correo@ejemplo.com"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs" :message="form.errors.email" />
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200">
                <p class="text-xs">
                    Tu dirección de correo electrónico no está verificada.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline hover:text-zinc-900 dark:hover:text-white font-bold ms-1"
                    >
                        Haz clic aquí para reenviar el correo de verificación.
                    </Link>
                </p>
                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                >
                    Se ha enviado un nuevo enlace de verificación a tu correo electrónico.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all shadow-md shadow-emerald-500/20"
                >
                    <i class="pi pi-check text-xs" />
                    <span>Guardar Cambios</span>
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <i class="pi pi-check-circle text-xs" />
                        <span>Guardado correctamente</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
