<script setup>
/**
 * Panel de Gestión de Plantillas Dinámicas del Maquetador Visual (Theme Templates).
 * Permite diseñar y asignar plantillas para vistas de categoría y detalle de artículos.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, computed } from 'vue';

import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import ToggleSwitch from 'primevue/toggleswitch';

const confirm = useConfirm();

const props = defineProps({
    plantillas: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
});

const pestanaActiva = ref('single_article'); // 'single_article' | 'category_archive'

const plantillasFiltradas = computed(() => {
    return props.plantillas.filter(p => p.type === pestanaActiva.value);
});

// Modal de Creación / Edición
const modalVisible = ref(false)
const esModoEdicion = ref(false)
const plantillaEditandoId = ref(null)

const form = useForm({
    name: '',
    type: 'single_article',
    description: '',
    category_ids: [],
    is_active: true,
});

function abrirModalCrear(tipo) {
    esModoEdicion.value = false;
    plantillaEditandoId.value = null;
    form.reset();
    form.type = tipo || pestanaActiva.value;
    form.is_active = true;
    form.category_ids = [];
    modalVisible.value = true;
}

function abrirModalEditar(plantilla) {
    esModoEdicion.value = true;
    plantillaEditandoId.value = plantilla.id;
    form.name = plantilla.name;
    form.type = plantilla.type;
    form.description = plantilla.description || '';
    form.is_active = plantilla.is_active;

    // Categorías asignadas actualmente
    const catsAsignadas = props.categorias.filter(c => {
        return plantilla.type === 'category_archive'
            ? c.category_builder_template_id === plantilla.id
            : c.article_builder_template_id === plantilla.id;
    }).map(c => c.id);

    form.category_ids = catsAsignadas;
    modalVisible.value = true;
}

function guardarPlantilla() {
    if (esModoEdicion.value) {
        form.put(route('plantillas.update', plantillaEditandoId.value), {
            onSuccess: () => {
                modalVisible.value = false;
            },
        });
    } else {
        form.post(route('plantillas.store'), {
            onSuccess: () => {
                modalVisible.value = false;
            },
        });
    }
}

function duplicar(plantilla) {
    confirm.require({
        message: `¿Duplicar la plantilla "${plantilla.name}"?`,
        header: 'Duplicar Plantilla',
        icon: 'pi pi-copy',
        acceptClass: 'p-button-primary',
        accept: () => router.post(route('plantillas.duplicate', plantilla.id)),
    });
}

function eliminar(plantilla) {
    confirm.require({
        message: `¿Eliminar la plantilla "${plantilla.name}"? Las categorías o artículos asignados volverán a su vista clásica estándar.`,
        header: 'Eliminar Plantilla',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('plantillas.destroy', plantilla.id)),
    });
}

function irAlBuilder(plantilla) {
    router.get(route('plantillas.builder', plantilla.id));
}

function getCategoriasAsignadasNombres(plantilla) {
    if (plantilla.type === 'category_archive' && Array.isArray(plantilla.categories_as_category_template) && plantilla.categories_as_category_template.length > 0) {
        return plantilla.categories_as_category_template;
    }
    if (plantilla.type === 'single_article' && Array.isArray(plantilla.categories_as_article_template) && plantilla.categories_as_article_template.length > 0) {
        return plantilla.categories_as_article_template;
    }
    return props.categorias.filter(c => {
        return plantilla.type === 'category_archive'
            ? c.category_builder_template_id === plantilla.id
            : c.article_builder_template_id === plantilla.id;
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Plantillas Visuales (Theme Builder) — Panel" />

        <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- Cabecera -->
            <PageHeader
                title="Plantillas Visuales del CMS"
                subtitle="Diseña visualmente plantillas con el Builder para las vistas de categoría y artículos"
            >
                <template #actions>
                    <Button
                        label="Nueva Plantilla"
                        icon="pi pi-plus"
                        @click="abrirModalCrear(pestanaActiva)"
                        class="p-button-primary shadow-xs"
                    />
                </template>
            </PageHeader>

            <!-- Pestañas Conmutadoras -->
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3 gap-4 flex-wrap">
                <div class="flex items-center gap-2 p-1 bg-zinc-100 dark:bg-zinc-800/80 rounded-xl">
                    <button
                        type="button"
                        @click="pestanaActiva = 'single_article'"
                        :class="[
                            'px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 cursor-pointer',
                            pestanaActiva === 'single_article'
                                ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                        ]"
                    >
                        <i class="pi pi-file-edit" />
                        <span>Plantillas de Artículos</span>
                        <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-zinc-200 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-200 font-mono">
                            {{ props.plantillas.filter(p => p.type === 'single_article').length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="pestanaActiva = 'category_archive'"
                        :class="[
                            'px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 cursor-pointer',
                            pestanaActiva === 'category_archive'
                                ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                        ]"
                    >
                        <i class="pi pi-folder" />
                        <span>Plantillas de Categorías</span>
                        <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-zinc-200 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-200 font-mono">
                            {{ props.plantillas.filter(p => p.type === 'category_archive').length }}
                        </span>
                    </button>
                </div>

                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    <i class="pi pi-info-circle mr-1 text-emerald-600" />
                    <span>Las categorías sin plantilla asignada continúan usando la vista estándar del CMS.</span>
                </div>
            </div>

            <!-- Listado de Tarjetas de Plantillas -->
            <div v-if="plantillasFiltradas.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="plantilla in plantillasFiltradas"
                    :key="plantilla.id"
                    class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 flex flex-col justify-between shadow-xs hover:border-emerald-300 dark:hover:border-emerald-700/60 transition-all group"
                >
                    <div class="space-y-3">
                        <!-- Cabecera de la tarjeta -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg shrink-0">
                                    <i :class="plantilla.type === 'single_article' ? 'pi pi-file-edit' : 'pi pi-folder-open'" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 transition-colors">
                                        {{ plantilla.name }}
                                    </h3>
                                    <p class="text-xs text-zinc-400 font-mono">
                                        Actualizado: {{ plantilla.updated_at || 'Reciente' }}
                                    </p>
                                </div>
                            </div>

                            <Tag
                                :value="plantilla.is_active ? 'Activa' : 'Inactiva'"
                                :severity="plantilla.is_active ? 'success' : 'warn'"
                                class="text-[10px]"
                            />
                        </div>

                        <!-- Descripción -->
                        <p v-if="plantilla.description" class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 leading-relaxed">
                            {{ plantilla.description }}
                        </p>
                        <p v-else class="text-xs italic text-zinc-400 dark:text-zinc-500">
                            Sin descripción especificada.
                        </p>

                        <!-- Categorías Asignadas -->
                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800/80">
                            <div class="text-[11px] font-semibold text-zinc-500 mb-1.5 flex items-center gap-1">
                                <i class="pi pi-link text-[10px]" />
                                <span>Asignada a Categorías:</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <template v-if="getCategoriasAsignadasNombres(plantilla).length > 0">
                                    <span
                                        v-for="cat in getCategoriasAsignadasNombres(plantilla)"
                                        :key="cat.id"
                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300"
                                    >
                                        {{ cat.name }}
                                    </span>
                                </template>
                                <span v-else class="text-xs text-amber-600 dark:text-amber-400 italic">
                                    Ninguna categoría asignada (Disponible para asignar)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="pt-4 mt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-2">
                        <Button
                            label="Maquetar con Builder"
                            icon="pi pi-pencil"
                            size="small"
                            @click="irAlBuilder(plantilla)"
                            class="p-button-primary text-xs flex-1 shadow-2xs"
                        />

                        <div class="flex items-center gap-1">
                            <Button
                                icon="pi pi-cog"
                                size="small"
                                text
                                rounded
                                severity="secondary"
                                v-tooltip.top="'Editar Ajustes'"
                                @click="abrirModalEditar(plantilla)"
                            />
                            <Button
                                icon="pi pi-copy"
                                size="small"
                                text
                                rounded
                                severity="secondary"
                                v-tooltip.top="'Duplicar Plantilla'"
                                @click="duplicar(plantilla)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                text
                                rounded
                                severity="danger"
                                v-tooltip.top="'Eliminar Plantilla'"
                                @click="eliminar(plantilla)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado Vacío -->
            <div v-else class="text-center py-16 bg-zinc-50/50 dark:bg-zinc-900/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 p-8 max-w-xl mx-auto">
                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <i class="pi pi-palette text-xl" />
                </div>
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                    No hay plantillas de {{ pestanaActiva === 'single_article' ? 'Artículos' : 'Categorías' }} creadas
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                    Crea tu primera plantilla para diseñar libremente la vista pública con el Page Builder.
                </p>
                <div class="mt-4">
                    <Button
                        label="Crear Plantilla"
                        icon="pi pi-plus"
                        size="small"
                        @click="abrirModalCrear(pestanaActiva)"
                        class="p-button-primary"
                    />
                </div>
            </div>

        </div>

        <!-- Modal de Crear / Editar Plantilla -->
        <Dialog
            v-model:visible="modalVisible"
            modal
            :header="esModoEdicion ? 'Editar Ajustes de la Plantilla' : 'Nueva Plantilla Visual'"
            class="w-full max-w-md"
        >
            <form @submit.prevent="guardarPlantilla" class="space-y-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-zinc-900 dark:text-zinc-100">Nombre de la Plantilla *</label>
                    <InputText
                        v-model="form.name"
                        placeholder="ej. Plantilla Estándar de Blog"
                        class="w-full text-xs"
                        required
                    />
                </div>

                <div v-if="!esModoEdicion">
                    <label class="block text-xs font-semibold mb-1 text-zinc-900 dark:text-zinc-100">Tipo de Plantilla *</label>
                    <Select
                        v-model="form.type"
                        :options="[
                            { label: 'Detalle de Artículo (single_article)', value: 'single_article' },
                            { label: 'Vista de Categoría (category_archive)', value: 'category_archive' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1 text-zinc-900 dark:text-zinc-100">Descripción (Opcional)</label>
                    <Textarea
                        v-model="form.description"
                        rows="2"
                        placeholder="Breve explicación del propósito de esta plantilla..."
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1 text-zinc-900 dark:text-zinc-100">
                        Asignar a Categorías (Opcional)
                    </label>
                    <MultiSelect
                        v-model="form.category_ids"
                        :options="props.categorias"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Selecciona categorías..."
                        display="chip"
                        class="w-full text-xs"
                    />
                    <p class="text-[11px] text-zinc-500 mt-1">
                        Puedes asignar esta plantilla a varias categorías o dejarlo para más tarde.
                    </p>
                </div>

                <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl">
                    <label class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Plantilla Activa</label>
                    <ToggleSwitch v-model="form.is_active" />
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <Button
                        type="button"
                        label="Cancelar"
                        severity="secondary"
                        text
                        size="small"
                        @click="modalVisible = false"
                    />
                    <Button
                        type="submit"
                        :label="esModoEdicion ? 'Guardar Cambios' : 'Crear y Abrir Builder'"
                        icon="pi pi-check"
                        size="small"
                        :loading="form.processing"
                        class="p-button-primary"
                    />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
