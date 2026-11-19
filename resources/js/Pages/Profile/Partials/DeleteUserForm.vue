<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6 w-full">
        <header class="flex items-center justify-between pb-4 border-b border-rose-100 dark:border-rose-900/40">
            <div>
                <h2 class="text-base font-bold text-rose-600 dark:text-rose-400 flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle text-rose-500 text-sm" />
                    <span>Zona de Riesgo — Eliminar Cuenta</span>
                </h2>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Una vez eliminada la cuenta, todos tus datos y configuraciones personales se borrarán permanentemente.
                </p>
            </div>
        </header>

        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-900 dark:text-rose-200 text-xs leading-relaxed space-y-3">
            <p class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle text-amber-500 text-sm shrink-0" />
                <span><strong>Atención:</strong> Esta acción no se puede deshacer. Tu acceso al panel de administración será revocado de inmediato.</span>
            </p>
            <DangerButton
                @click="confirmUserDeletion"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition-all shadow-md shadow-rose-500/20"
            >
                <i class="pi pi-trash text-xs" />
                <span>Eliminar Mi Cuenta</span>
            </DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-3 text-rose-600 dark:text-rose-400">
                    <div class="w-10 h-10 rounded-full bg-rose-500/15 flex items-center justify-center text-lg shrink-0">
                        <i class="pi pi-exclamation-triangle" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-zinc-900 dark:text-white">
                            ¿Confirmas la eliminación de tu cuenta?
                        </h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Por favor introduce tu contraseña actual para confirmar la baja permanente.
                        </p>
                    </div>
                </div>

                <div class="pt-2">
                    <InputLabel
                        for="password"
                        value="Contraseña"
                        class="sr-only"
                    />
                    <PasswordInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        autocomplete="current-password"
                        placeholder="Introduce tu contraseña actual"
                        class="w-full text-xs"
                        @keyup.enter="deleteUser"
                    />
                    <InputError :message="form.errors.password" class="mt-1.5 text-xs" />
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800">
                    <SecondaryButton @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white"
                    >
                        Sí, Eliminar Definitivamente
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
