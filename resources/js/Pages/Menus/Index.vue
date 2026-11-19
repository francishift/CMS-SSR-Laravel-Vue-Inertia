<script setup>
/**
 * Listado de menús de navegación con componentes PrimeVue.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

// Componentes PrimeVue
import ToggleSwitch from 'primevue/toggleswitch';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const confirm = useConfirm();

const props = defineProps({
    menus: { type: Array, default: () => [] },
    conteoPapelera: { type: Number, default: 0 },
});

const mostrarFormulario = ref(false);
const form = useForm({
    name: '',
    location: 'header',
    is_active: true,
});

const ubicaciones = [
    { label: 'Cabecera (Header)', value: 'header' },
    { label: 'Barra lateral (Sidebar)', value: 'sidebar' },
    { label: 'Sin asignar (Solo Page Builder)', value: 'custom' },
];

function crearMenu() {
    form.post(route('menus.store'), {
        onSuccess: () => {
            form.reset();
            mostrarFormulario.value = false;
        },
    });
}

function eliminarMenu(menu) {
    confirm.require({
        message: `¿Mover el menú "${menu.name}" a la papelera? Podrás restaurarlo en cualquier momento.`,
        header: 'Enviar a la papelera',
        icon: 'pi pi-trash',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Sí, enviar a papelera',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('menus.destroy', menu.id)),
    });
}

function irAEditar(menu) {
    if (!menu?.id) return;
    router.get(route('menus.edit', menu.id));
}

const ubicacionLabel = {
    header: 'Cabecera Principal',
    sidebar: 'Barra Lateral',
    footer: 'Pie de página (Legado)',
    custom: 'Sin asignar (Page Builder)',
};
</script>

<template>
    <Head title="Menús" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        Gestión de Menús
                    </h1>
                </div>

                <!-- Pestañas Activos / Papelera en el Header (Solo Desktop md+) -->
                <div class="hidden md:flex items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs shrink-0">
                    <Link
                        :href="route('menus.index')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm"
                    >
                        <i class="pi pi-bars text-xs" />
                        <span>Menús Activos</span>
                    </Link>
                    <Link
                        :href="route('menus.trashed')"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"
                    >
                        <i class="pi pi-trash text-xs" />
                        <span>Papelera</span>
                        <span v-if="conteoPapelera > 0" class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                            {{ conteoPapelera }}
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 w-full space-y-6">
            <!-- Pestañas Activos / Papelera para Móvil (< md) -->
            <div class="flex md:hidden items-center gap-1 rounded-xl bg-zinc-200 dark:bg-zinc-800/80 p-1 text-xs w-full">
                <Link
                    :href="route('menus.index')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm text-center"
                >
                    <i class="pi pi-bars text-xs" />
                    <span>Menús Activos</span>
                </Link>
                <Link
                    :href="route('menus.trashed')"
                    class="flex-1 flex items-center justify-center gap-2 rounded-lg py-2 font-medium transition-all text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 text-center"
                >
                    <i class="pi pi-trash text-xs" />
                    <span>Papelera</span>
                    <span v-if="conteoPapelera > 0" class="rounded-full bg-red-100 dark:bg-red-950/80 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">
                        {{ conteoPapelera }}
                    </span>
                </Link>
            </div>
            <div class="flex items-center justify-between gap-4 pb-2 border-b border-zinc-200 dark:border-zinc-800">
                <div>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Listado de Menús</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Estructuras de navegación configuradas</p>
                </div>
                <Button
                    :label="mostrarFormulario ? 'Cancelar' : 'Nuevo Menú'"
                    :icon="mostrarFormulario ? 'pi pi-times' : 'pi pi-plus'"
                    :severity="mostrarFormulario ? 'secondary' : 'emerald'"
                    @click="mostrarFormulario = !mostrarFormulario"
                />
            </div>

            <!-- Formulario de creación de menú -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <form
                    v-if="mostrarFormulario"
                    @submit.prevent="crearMenu"
                    class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6"
                >
                    <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="w-7 h-7 rounded-md bg-emerald-100 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <i class="pi pi-plus text-xs" />
                        </div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Crear Nuevo Menú
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                Nombre del menú *
                            </label>
                            <InputText
                                v-model="form.name"
                                required
                                class="w-full"
                                placeholder="Ej: Menú Principal"
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                Ubicación *
                            </label>
                            <Select
                                v-model="form.location"
                                :options="ubicaciones"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>

                        <div class="flex items-center gap-3 h-10 px-2">
                            <ToggleSwitch v-model="form.is_active" id="new_menu_active" />
                            <label for="new_menu_active" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                                Menú activo
                            </label>
                        </div>

                        <div>
                            <Button
                                type="submit"
                                label="Crear Menú"
                                icon="pi pi-check"
                                :loading="form.processing"
                                severity="emerald"
                                class="w-full"
                            />
                        </div>
                    </div>
                </form>
            </Transition>

            <!-- Listado de menús -->
            <div class="space-y-3">
                <div
                    v-for="menu in menus"
                    :key="menu.id"
                    @click="irAEditar(menu)"
                    class="group bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 flex items-center justify-between hover:border-zinc-300 dark:hover:border-zinc-700 transition-all shadow-sm cursor-pointer"
                >
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-400 shrink-0 group-hover:bg-emerald-50 dark:group-hover:bg-emerald-950/40 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                            <i class="pi pi-bars text-lg" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-zinc-900 dark:text-zinc-100 font-semibold text-base group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ menu.name }}</h3>
                                <Tag :value="menu.is_active ? 'Activo' : 'Inactivo'" :severity="menu.is_active ? 'success' : 'secondary'" />
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800/80 px-2 py-0.5 rounded">
                                    {{ ubicacionLabel[menu.location] || menu.location }}
                                </span>
                                <span>•</span>
                                <span>{{ menu.items_count }} elementos configurados</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2" @click.stop>
                        <Link :href="route('menus.edit', menu.id)">
                            <Button
                                label="Editar elementos"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                v-tooltip.top="'Configurar estructura de elementos'"
                            />
                        </Link>
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            @click="eliminarMenu(menu)"
                            v-tooltip.top="'Mover menú a la papelera'"
                        />
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-if="!menus.length" class="text-center py-16 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-8">
                    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-4 text-zinc-400">
                        <i class="pi pi-bars text-2xl" />
                    </div>
                    <h3 class="text-zinc-900 dark:text-zinc-100 font-semibold text-base">No hay menús creados</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 max-w-md mx-auto">
                        Crea tu primer menú para estructurar la navegación principal de tu sitio.
                    </p>
                    <Button
                        label="Crear Primer Menú"
                        icon="pi pi-plus"
                        severity="emerald"
                        class="mt-5"
                        @click="mostrarFormulario = true"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
