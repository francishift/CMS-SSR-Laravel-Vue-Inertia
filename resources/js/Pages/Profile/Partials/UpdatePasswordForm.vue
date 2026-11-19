<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="space-y-6 w-full">
        <header class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                    <i class="pi pi-lock text-indigo-500 text-sm" />
                    <span>Actualizar Contraseña</span>
                </h2>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Asegúrate de utilizar una contraseña segura de al menos 8 caracteres.
                </p>
            </div>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-5 w-full">
            <div>
                <InputLabel for="current_password" value="Contraseña Actual" class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1" />
                <PasswordInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    autocomplete="current-password"
                    placeholder="Ingresa tu clave actual"
                    class="w-full text-xs font-medium"
                />
                <InputError :message="form.errors.current_password" class="mt-1.5 text-xs" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <InputLabel for="password" value="Nueva Contraseña" class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1" />
                    <PasswordInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                        class="w-full text-xs font-medium"
                    />
                    <InputError :message="form.errors.password" class="mt-1.5 text-xs" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirmar Nueva Contraseña" class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1" />
                    <PasswordInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                        placeholder="Repite la nueva clave"
                        class="w-full text-xs font-medium"
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-1.5 text-xs" />
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all shadow-md shadow-indigo-500/20"
                >
                    <i class="pi pi-shield text-xs" />
                    <span>Actualizar Contraseña</span>
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                        <i class="pi pi-check-circle text-xs" />
                        <span>Contraseña actualizada correctamente</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
