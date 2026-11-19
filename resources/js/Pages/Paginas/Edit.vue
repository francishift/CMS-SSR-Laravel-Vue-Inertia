<script setup>
/**
 * Formulario de edición de Páginas Estáticas con diseño 2 columnas (CMS Style) y acciones superiores.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import WysiwygEditor from '@/Components/Admin/WysiwygEditor.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import ToggleSwitch from 'primevue/toggleswitch';
import { OPCIONES_TAMANO_VISUAL } from '@/Utils/headingScale';

const props = defineProps({
    pagina: { type: Object, required: true },
});

const form = useForm({
    title: props.pagina.title,
    title_size: props.pagina.title_size || 'default',
    slug: props.pagina.slug,
    body: props.pagina.body,
    is_active: Boolean(props.pagina.is_active),
    order: props.pagina.order || 0,
    seo_title: props.pagina.seo_title || '',
    seo_description: props.pagina.seo_description || '',
    seo_og_image: props.pagina.seo_og_image || '',
    meta_robots: props.pagina.meta_robots || null,
});

const seoNoIndex = ref(Boolean(props.pagina?.meta_robots?.includes('noindex')));
const seoNoFollow = ref(Boolean(props.pagina?.meta_robots?.includes('nofollow')));

watch([seoNoIndex, seoNoFollow], ([noindex, nofollow]) => {
    if (noindex && nofollow) {
        form.meta_robots = 'noindex, nofollow';
    } else if (noindex) {
        form.meta_robots = 'noindex, follow';
    } else if (nofollow) {
        form.meta_robots = 'index, nofollow';
    } else {
        form.meta_robots = null;
    }
});

function normalizarSlug() {
    if (!form.slug || props.pagina.is_homepage) return;
    form.slug = form.slug
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function actualizar() {
    normalizarSlug();
    form.put(route('paginas.update', props.pagina.id));
}
</script>

<template>
    <Head :title="`Editar — ${pagina.title}`" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 w-full space-y-6">
            <form @submit.prevent="actualizar" class="space-y-6">
                <!-- ═══════════════════════════════════════════════ -->
                <!-- CABECERA DE PÁGINA Y BOTONES DE ACCIÓN SUPERIOR -->
                <!-- ═══════════════════════════════════════════════ -->
                <PageHeader
                    :title="`Editar Página: ${pagina.title}`"
                    description="Modifica el contenido, visibilidad y configuración SEO de esta página."
                    :breadcrumbs="[
                        { label: 'Páginas', href: route('paginas.index') },
                        { label: pagina.title }
                    ]"
                    divider
                >
                    <template #actions>
                        <Link
                            :href="route('paginas.builder', pagina.id)"
                            class="px-3.5 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs inline-flex items-center gap-1.5"
                        >
                            <i class="pi pi-objects-column text-xs" />
                            <span>Abrir Page Builder</span>
                        </Link>
                        <a
                            :href="`/${pagina.slug}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="px-3.5 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-medium rounded-lg transition-colors inline-flex items-center gap-1.5"
                        >
                            <i class="pi pi-external-link text-xs" />
                            <span>Ver en web</span>
                        </a>
                        <Link
                            :href="route('paginas.index')"
                            class="px-3.5 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-medium rounded-lg transition-colors inline-flex items-center gap-1.5"
                        >
                            <i class="pi pi-times text-xs" />
                            <span>Cancelar</span>
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs inline-flex items-center gap-1.5 cursor-pointer"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                            <i v-else class="pi pi-check text-xs" />
                            <span>Guardar Cambios</span>
                        </button>
                    </template>
                </PageHeader>

                <!-- BANNER DESTACADO DE PAGE BUILDER -->
                <div class="p-4 bg-purple-50/80 dark:bg-purple-950/30 rounded-xl border border-purple-200/80 dark:border-purple-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold shrink-0 shadow-xs">
                            <i class="pi pi-objects-column text-lg" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-purple-950 dark:text-purple-100">Maquetador Visual Interactivo (Page Builder)</h3>
                            <p class="text-xs text-purple-700 dark:text-purple-300">Diseña la estructura de esta página con secciones, héroes, columnas y artículos dinámicos en tiempo real.</p>
                        </div>
                    </div>
                    <Link
                        :href="route('paginas.builder', pagina.id)"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded-lg transition-colors shadow-xs inline-flex items-center gap-2 shrink-0"
                    >
                        <i class="pi pi-objects-column text-xs" />
                        <span>Abrir Maquetador Visual</span>
                    </Link>
                </div>

                <!-- CONTENIDO DEL FORMULARIO: ESTRUCTURA DOS COLUMNAS (MAIN + SIDEBAR) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- COLUMNA PRINCIPAL (2/3) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Sección Principal: Título y Slug -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-5">
                            <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                                    <i class="pi pi-file text-sm" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Información de la Página</h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Título, dirección URL y contenido principal</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Título de la Página *</label>
                                    <InputText v-model="form.title" class="w-full" />
                                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Slug URL *</label>
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-zinc-400 font-mono">/</span>
                                        <InputText v-model="form.slug" @blur="normalizarSlug" class="w-full font-mono text-xs" />
                                    </div>
                                    <p v-if="form.errors.slug" class="text-red-500 text-xs mt-1">{{ form.errors.slug }}</p>
                                </div>
                            </div>

                            <div class="sm:max-w-md">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Tamaño Visual del Título</label>
                                <Select
                                    v-model="form.title_size"
                                    :options="OPCIONES_TAMANO_VISUAL"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                />
                                <p v-if="pagina.layout_json?.sections?.length" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                    Esta página usa actualmente el Page Builder; este tamaño solo se aplica en modo página básica.
                                </p>
                                <p v-else class="text-xs text-zinc-400 mt-1">
                                    Conserva la etiqueta semántica <code class="font-mono">&lt;h1&gt;</code> para SEO aplicando la escala visual elegida.
                                </p>
                                <p v-if="form.errors.title_size" class="text-red-500 text-xs mt-1">{{ form.errors.title_size }}</p>
                            </div>

                            <!-- Contenido HTML / Editor WYSIWYG -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">Contenido de la Página</label>
                                <WysiwygEditor v-model="form.body" placeholder="Escribe el contenido completo de la página..." />
                                <p v-if="form.errors.body" class="text-red-500 text-xs mt-1">{{ form.errors.body }}</p>
                            </div>
                        </section>

                        <!-- Optimización SEO -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm space-y-4">
                            <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                                    <i class="pi pi-search text-sm" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Metadatos SEO</h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Optimización personalizada para motores de búsqueda</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Título SEO (opcional, máx. 70)</label>
                                    <InputText v-model="form.seo_title" maxlength="70" class="w-full" />
                                    <p class="text-xs text-zinc-400 mt-1">{{ (form.seo_title || '').length }}/70 caracteres</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Descripción SEO (opcional, máx. 160)</label>
                                    <Textarea v-model="form.seo_description" rows="2" maxlength="160" class="w-full" />
                                    <p class="text-xs text-zinc-400 mt-1">{{ (form.seo_description || '').length }}/160 caracteres</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1">Imagen Open Graph (Compartir en Redes)</label>
                                    <ImageUploader v-model="form.seo_og_image" directorio="seo" ayuda="Imagen para redes sociales (1200×630px recomendado)" />
                                </div>

                                <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Indexación en Buscadores</p>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="seoNoIndex ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400'">
                                            {{ seoNoIndex ? 'No indexar' : 'Indexable (estándar)' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-zinc-500">Por defecto la página se indexa en Google. Activa solo si deseas restringirla.</p>

                                    <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                        <div class="text-xs pr-3">
                                            <label for="page_edit_seo_noindex" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                Bloquear Indexación (<code class="font-mono text-amber-600 dark:text-amber-400">noindex</code>)
                                            </label>
                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">Indica a los buscadores que no muestren esta página en los resultados.</p>
                                        </div>
                                        <ToggleSwitch v-model="seoNoIndex" id="page_edit_seo_noindex" />
                                    </div>

                                    <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/60">
                                        <div class="text-xs pr-3">
                                            <label for="page_edit_seo_nofollow" class="font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer select-none block">
                                                No Seguir Enlaces (<code class="font-mono text-amber-600 dark:text-amber-400">nofollow</code>)
                                            </label>
                                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5">Indica a los buscadores que no rastreen los enlaces de esta página.</p>
                                        </div>
                                        <ToggleSwitch v-model="seoNoFollow" id="page_edit_seo_nofollow" />
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div>

                    <!-- ─────────────────────────────────────────── -->
                    <!-- COLUMNA DERECHA: SIDEBAR DE OPCIONES (4 cols) -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-6">

                        <!-- Panel de Estado y Publicación -->
                        <section class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm space-y-4">
                            <div class="flex items-center gap-2.5 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/60 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <i class="pi pi-cog text-xs" />
                                </div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Estado y Publicación</h3>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700/60">
                                    <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">Página Publicada</span>
                                    <ToggleSwitch v-model="form.is_active" />
                                </div>
                            </div>
                        </section>

                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════ -->
                <!-- BOTONES DE ACCIÓN INFERIOR (REPETIDOS POR COMODIDAD) -->
                <!-- ═══════════════════════════════════════════════ -->
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <Link
                        :href="route('paginas.index')"
                        class="px-5 py-2.5 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors shadow-sm inline-flex items-center gap-2 cursor-pointer"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                        <i v-else class="pi pi-check text-xs" />
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
