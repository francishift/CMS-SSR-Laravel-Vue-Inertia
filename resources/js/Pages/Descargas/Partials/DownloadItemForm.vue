<script setup>
/**
 * Formulario reutilizable de Creación y Edición de Descargas (Backend).
 *
 * Reutiliza los componentes nativos del CMS (`WysiwygEditor`, `ImageUploader`, `IconPicker`),
 * soporta subida segura de paquetes `.zip` a la bóveda privada, campos técnicos estructurados
 * para plugins (versión, compatibilidad YOOtheme Pro, demo, changelog), configuración preparada
 * para monetización futura y metadatos SEO completos.
 */
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import WysiwygEditor from '@/Components/Admin/WysiwygEditor.vue';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';
import IconPicker from '@/Components/Admin/IconPicker.vue';

import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Button from 'primevue/button';

const props = defineProps({
    modo: { type: String, default: 'create' }, // 'create' | 'edit'
    descarga: { type: Object, default: null },
    configuracion: { type: Object, required: true },
    estadosDisponibles: { type: Object, default: () => ({}) },
    tiposAccesoDisponibles: { type: Object, default: () => ({}) },
    limitesSubida: { type: Object, default: () => ({ max_mb: 100, allowed_extensions: ['zip'] }) },
    monedaPorDefecto: { type: String, default: 'EUR' },
    urlRetorno: { type: String, required: true },
});

const tabActiva = ref('contenido');
const archivoInputRef = ref(null);
const arrastrandoArchivo = ref(false);

const obtenerFechaActualIso = () => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
};

const esEdicion = computed(() => props.modo === 'edit' && Boolean(props.descarga?.id));

// Precio editable en unidades decimales (euros) convertido limpiamente a céntimos enteros
const precioEurosInput = ref(
    props.descarga?.price_cents ? (Number(props.descarga.price_cents) / 100).toFixed(2) : '0.00'
);

const form = useForm({
    ...(esEdicion.value ? { _method: 'put' } : {}),
    title: props.descarga?.title || '',
    subtitle: props.descarga?.subtitle || '',
    slug: props.descarga?.slug || '',
    short_description: props.descarga?.short_description || '',
    body: props.descarga?.body || '',
    featured_image: props.descarga?.featured_image || '',
    icon: props.descarga?.icon || 'pi pi-box',
    version: props.descarga?.version || '1.0.0',
    compatibility: props.descarga?.compatibility || 'YOOtheme Pro 4.x',
    changelog: props.descarga?.changelog || '',
    demo_url: props.descarga?.demo_url || '',
    documentation_url: props.descarga?.documentation_url || '',
    package_file: null,
    access_type: props.descarga?.access_type || 'free',
    price_cents: Number(props.descarga?.price_cents || 0),
    currency: props.descarga?.currency || props.monedaPorDefecto || 'EUR',
    license_type: props.descarga?.license_type || 'GPL-3.0-or-later',
    external_product_id: props.descarga?.external_product_id || '',
    status: props.descarga?.status || 'draft',
    published_at: props.descarga?.published_at || obtenerFechaActualIso(),
    is_featured: Boolean(props.descarga?.is_featured ?? false),
    seo_title: props.descarga?.seo_title || '',
    seo_description: props.descarga?.seo_description || '',
    seo_og_image: props.descarga?.seo_og_image || '',
    meta_robots: props.descarga?.meta_robots || null,
});

watch(precioEurosInput, (val) => {
    const normalizado = parseFloat(String(val).replace(',', '.'));
    form.price_cents = !isNaN(normalizado) && normalizado >= 0 ? Math.round(normalizado * 100) : 0;
});

const opcionesEstados = computed(() =>
    Object.entries(props.estadosDisponibles || {}).map(([value, label]) => ({ label, value }))
);

const opcionesAcceso = computed(() =>
    Object.entries(props.tiposAccesoDisponibles || {}).map(([value, label]) => ({ label, value }))
);

const opcionesRobots = [
    { label: 'Indexar y seguir (index, follow)', value: 'default_index' },
    { label: 'No indexar, seguir (noindex, follow)', value: 'noindex, follow' },
    { label: 'No indexar ni seguir (noindex, nofollow)', value: 'noindex, nofollow' },
];

const robotsSelectModel = ref(props.descarga?.meta_robots || 'default_index');
watch(robotsSelectModel, (val) => {
    form.meta_robots = val === 'default_index' ? null : val;
});

const extensionesAceptadas = computed(() =>
    (props.limitesSubida?.allowed_extensions || ['zip']).map(ext => `.${ext}`).join(',')
);

const nombreArchivoSeleccionado = computed(() => {
    if (form.package_file instanceof File) {
        const mb = (form.package_file.size / (1024 * 1024)).toFixed(2);
        return `${form.package_file.name} (${mb} MB — Listo para subir)`;
    }
    return null;
});

function seleccionarArchivoManual(event) {
    const file = event.target?.files?.[0];
    if (file) {
        form.package_file = file;
    }
}

function soltarArchivo(event) {
    arrastrandoArchivo.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        form.package_file = file;
    }
}

function limpiarArchivoPendiente() {
    form.package_file = null;
    if (archivoInputRef.value) {
        archivoInputRef.value.value = '';
    }
}

function enviarFormulario() {
    if (esEdicion.value) {
        form.post(route('downloads.admin.update', props.descarga.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                limpiarArchivoPendiente();
            },
        });
    } else {
        form.post(route('downloads.admin.store'), {
            forceFormData: true,
        });
    }
}
</script>

<template>
    <form @submit.prevent="enviarFormulario" class="w-full">
        <!-- Barra superior Sticky de acciones -->
        <div class="sticky top-0 z-20 bg-white/95 dark:bg-zinc-900/95 backdrop-blur border-b border-zinc-200 dark:border-zinc-800 px-3 sm:px-6 py-2.5 sm:py-3.5 flex flex-wrap items-center justify-between gap-2.5 sm:gap-3">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                <Link
                    :href="urlRetorno"
                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors shrink-0"
                    title="Volver al listado de descargas"
                >
                    <i class="pi pi-arrow-left text-xs sm:text-sm" />
                </Link>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 min-w-0">
                        <h1 class="text-sm sm:text-lg font-bold text-zinc-900 dark:text-white truncate">
                            {{ form.title || (esEdicion ? 'Editar Descarga' : 'Nueva Descarga') }}
                        </h1>
                        <Tag
                            :value="form.status === 'published' ? 'Publicado' : 'Borrador'"
                            :severity="form.status === 'published' ? 'success' : 'secondary'"
                            class="hidden sm:inline-flex shrink-0"
                        />
                        <Tag
                            v-if="!configuracion.is_strictly_public"
                            value="Visibilidad: Solo Backend"
                            severity="warn"
                            class="hidden md:inline-flex shrink-0"
                        />
                    </div>
                    <p v-if="esEdicion && descarga?.public_url" class="hidden sm:block text-xs text-zinc-500 dark:text-zinc-400 font-mono truncate">
                        {{ descarga.public_url }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a
                    v-if="esEdicion && descarga?.public_url"
                    :href="descarga.public_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl text-xs font-semibold border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors no-underline"
                    title="Previsualizar ficha"
                >
                    <i class="pi pi-external-link text-xs" />
                    <span class="hidden sm:inline">Previsualizar ficha</span>
                </a>

                <Button
                    type="submit"
                    :label="esEdicion ? 'Guardar' : 'Crear'"
                    icon="pi pi-check"
                    severity="emerald"
                    size="small"
                    :loading="form.processing"
                    class="sm:!hidden"
                />
                <Button
                    type="submit"
                    :label="esEdicion ? 'Guardar Cambios' : 'Crear Descarga'"
                    icon="pi pi-check"
                    severity="emerald"
                    :loading="form.processing"
                    class="!hidden sm:!inline-flex"
                />
            </div>
        </div>

        <!-- Cuerpo en 2 columnas balanceadas -->
        <div class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Columna Principal (8/12) -->
            <div class="lg:col-span-8 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 sm:p-6 shadow-sm space-y-5">
                <!-- Título y Slug -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Título del recurso / plugin *
                        </label>
                        <InputText
                            v-model="form.title"
                            required
                            class="w-full"
                            placeholder="Ej: Elemento Timeline Avanzado para YOOtheme Pro"
                        />
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Slug URL (Automático si vacío)
                        </label>
                        <InputText
                            v-model="form.slug"
                            class="w-full font-mono text-xs"
                            placeholder="timeline-yootheme-pro"
                        />
                        <p v-if="form.errors.slug" class="text-xs text-red-500 mt-1">{{ form.errors.slug }}</p>
                    </div>
                </div>

                <!-- Subtítulo (aparece debajo del título en texto small) -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                        Subtítulo del recurso <span class="font-normal normal-case text-zinc-400">(se muestra debajo del título en texto small)</span>
                    </label>
                    <InputText
                        v-model="form.subtitle"
                        class="w-full text-sm"
                        placeholder="Ej: Marquesina Infinita de Logos para YOOtheme Pro"
                    />
                    <p v-if="form.errors.subtitle" class="text-xs text-red-500 mt-1">{{ form.errors.subtitle }}</p>
                </div>

                <!-- Pestañas del recurso -->
                <Tabs v-model:value="tabActiva">
                    <TabList>
                        <Tab value="contenido">
                            <i class="pi pi-align-left mr-2 text-xs" />
                            Contenido y Maquetación
                        </Tab>
                        <Tab value="paquete">
                            <i class="pi pi-box mr-2 text-xs" />
                            Paquete ZIP y Datos Técnicos
                        </Tab>
                        <Tab value="monetizacion">
                            <i class="pi pi-wallet mr-2 text-xs" />
                            Acceso y Precio
                        </Tab>
                        <Tab value="seo">
                            <i class="pi pi-globe mr-2 text-xs" />
                            SEO y Schema.org
                        </Tab>
                    </TabList>

                    <TabPanels class="px-0 pt-5 pb-0">
                        <!-- TAB 1: Contenido y Maquetación -->
                        <TabPanel value="contenido" class="space-y-5">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Descripción corta (Tarjeta en Grid y Listado)
                                </label>
                                <Textarea
                                    v-model="form.short_description"
                                    rows="2"
                                    maxlength="500"
                                    class="w-full"
                                    placeholder="Resumen conciso de 1-2 líneas que verá el usuario en el listado..."
                                />
                                <p v-if="form.errors.short_description" class="text-xs text-red-500 mt-1">{{ form.errors.short_description }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Descripción detallada y maquetación de la ficha
                                </label>
                                <WysiwygEditor
                                    v-model="form.body"
                                    height="340px"
                                    placeholder="Describe características, instrucciones de instalación en YOOtheme Pro, capturas..."
                                />
                                <p v-if="form.errors.body" class="text-xs text-red-500 mt-1">{{ form.errors.body }}</p>
                            </div>
                        </TabPanel>

                        <!-- TAB 2: Bóveda Segura ZIP y Metadatos Técnicos -->
                        <TabPanel value="paquete" class="space-y-5">
                            <!-- Zona Drag & Drop de paquete privado -->
                            <div
                                @dragover.prevent="arrastrandoArchivo = true"
                                @dragleave.prevent="arrastrandoArchivo = false"
                                @drop.prevent="soltarArchivo"
                                :class="[
                                    'rounded-2xl border-2 border-dashed p-6 text-center transition-all',
                                    arrastrandoArchivo
                                        ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/30'
                                        : 'border-zinc-300 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-800/40',
                                ]"
                            >
                                <input
                                    ref="archivoInputRef"
                                    type="file"
                                    :accept="extensionesAceptadas"
                                    class="hidden"
                                    @change="seleccionarArchivoManual"
                                />

                                <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-3">
                                    <i class="pi pi-shield text-xl" />
                                </div>

                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    Subir archivo descargable (.ZIP protegido en servidor)
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    Arrastra aquí tu paquete <code>{{ extensionesAceptadas }}</code> (máx. {{ limitesSubida.max_mb }} MB) o haz clic para seleccionarlo.
                                </p>

                                <div class="mt-4 flex justify-center gap-2">
                                    <Button
                                        type="button"
                                        :label="descarga?.has_file ? 'Reemplazar archivo ZIP' : 'Seleccionar paquete ZIP'"
                                        icon="pi pi-upload"
                                        severity="secondary"
                                        size="small"
                                        @click="archivoInputRef?.click()"
                                    />
                                    <Button
                                        v-if="nombreArchivoSeleccionado"
                                        type="button"
                                        label="Quitar selección"
                                        icon="pi pi-times"
                                        severity="danger"
                                        text
                                        size="small"
                                        @click="limpiarArchivoPendiente"
                                    />
                                </div>

                                <div v-if="nombreArchivoSeleccionado" class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 text-xs font-medium">
                                    <i class="pi pi-check-circle" />
                                    <span>{{ nombreArchivoSeleccionado }}</span>
                                </div>

                                <p v-if="form.errors.package_file" class="text-xs text-red-500 font-medium mt-2">
                                    {{ form.errors.package_file }}
                                </p>
                            </div>

                            <!-- Resumen del archivo actualmente custodiado en la bóveda -->
                            <div
                                v-if="esEdicion && descarga?.has_file"
                                class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                            >
                                <div class="space-y-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-lock text-emerald-600 dark:text-emerald-400 text-sm" />
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                            {{ descarga.original_filename }}
                                        </span>
                                        <Tag :value="descarga.formatted_file_size" severity="info" />
                                    </div>
                                    <p v-if="descarga.sha256_hash" class="text-[11px] font-mono text-zinc-500 dark:text-zinc-400 break-all">
                                        SHA-256: {{ descarga.sha256_hash }}
                                    </p>
                                </div>

                                <a
                                    v-if="descarga.download_request_url"
                                    :href="descarga.download_request_url"
                                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shrink-0 no-underline"
                                >
                                    <i class="pi pi-download text-xs" />
                                    <span>Probar descarga firmada</span>
                                </a>
                            </div>

                            <!-- Campos técnicos estructurados -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        Versión actual
                                    </label>
                                    <InputText v-model="form.version" class="w-full font-mono text-xs" placeholder="Ej: 1.2.0" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        Compatibilidad / Plataforma
                                    </label>
                                    <InputText v-model="form.compatibility" class="w-full" placeholder="Ej: YOOtheme Pro 4.x | Joomla 5 & WP 6" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        URL de Demo en vivo (Opcional)
                                    </label>
                                    <InputText v-model="form.demo_url" class="w-full font-mono text-xs" placeholder="https://..." />
                                    <p v-if="form.errors.demo_url" class="text-xs text-red-500 mt-1">{{ form.errors.demo_url }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        URL de Documentación (Opcional)
                                    </label>
                                    <InputText v-model="form.documentation_url" class="w-full font-mono text-xs" placeholder="https://..." />
                                    <p v-if="form.errors.documentation_url" class="text-xs text-red-500 mt-1">{{ form.errors.documentation_url }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Notas de versión / Changelog
                                </label>
                                <Textarea
                                    v-model="form.changelog"
                                    rows="4"
                                    class="w-full font-mono text-xs"
                                    placeholder="v1.0.0 - Lanzamiento inicial del elemento para YOOtheme Pro..."
                                />
                            </div>
                        </TabPanel>

                        <!-- TAB 3: Acceso y Monetización Futura -->
                        <TabPanel value="monetizacion" class="space-y-4">
                            <div class="p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/60 text-xs text-blue-800 dark:text-blue-300">
                                Arquitectura preparada para pasarela comercial futura: hoy puedes distribuir tus recursos en modo <strong>Gratuito</strong> y, cuando actives cobros, cambiar cualquier ítem a <strong>De pago</strong> conservando su misma URL SEO y archivo privado.
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        Modalidad de acceso *
                                    </label>
                                    <Select
                                        v-model="form.access_type"
                                        :options="opcionesAcceso"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        Tipo de licencia
                                    </label>
                                    <InputText v-model="form.license_type" class="w-full" placeholder="Ej: GPL-3.0 / Uso Comercial" />
                                </div>
                            </div>

                            <div v-if="form.access_type === 'paid'" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        Precio ({{ form.currency }})
                                    </label>
                                    <InputText v-model="precioEurosInput" class="w-full font-mono" placeholder="19.00" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        Moneda ISO
                                    </label>
                                    <InputText v-model="form.currency" maxlength="3" class="w-full uppercase font-mono" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        ID Producto Externo (Stripe/Paddle)
                                    </label>
                                    <InputText v-model="form.external_product_id" class="w-full font-mono text-xs" placeholder="prod_..." />
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 4: SEO Individual -->
                        <TabPanel value="seo" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Meta Title SEO (Opcional)
                                </label>
                                <InputText v-model="form.seo_title" maxlength="160" class="w-full" placeholder="Por defecto utiliza el título del plugin" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Meta Description SEO (Opcional)
                                </label>
                                <Textarea v-model="form.seo_description" rows="3" maxlength="320" class="w-full" placeholder="Por defecto utiliza la descripción corta" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        Directiva Robots
                                    </label>
                                    <Select
                                        v-model="robotsSelectModel"
                                        :options="opcionesRobots"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full"
                                    />
                                </div>

                                <div>
                                    <ImageUploader
                                        v-model="form.seo_og_image"
                                        label="Imagen OpenGraph específica (Opcional)"
                                        compact
                                    />
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>

            <!-- Barra Lateral Derecha (4/12) -->
            <aside class="lg:col-span-4 space-y-5">
                <!-- Tarjeta de Publicación -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                        Publicación y Destacado
                    </h2>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Estado de la ficha
                        </label>
                        <Select
                            v-model="form.status"
                            :options="opcionesEstados"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Fecha de publicación
                        </label>
                        <input
                            v-model="form.published_at"
                            type="datetime-local"
                            class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100"
                        />
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <ToggleSwitch v-model="form.is_featured" inputId="dl_is_featured" />
                        <label for="dl_is_featured" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            Destacar elemento (Insignia Destacado)
                        </label>
                    </div>

                    <!-- Métricas de backend en modo edición -->
                    <div v-if="esEdicion" class="pt-3 border-t border-zinc-100 dark:border-zinc-800 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-2.5">
                            <span class="block text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ descarga.downloads_count }}</span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Descargas</span>
                        </div>
                        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-2.5">
                            <span class="block text-lg font-bold text-zinc-800 dark:text-zinc-200">{{ descarga.views_count }}</span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Vistas públicas</span>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Imagen Representativa e Icono -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                        Imagen Representativa e Icono
                    </h2>

                    <ImageUploader
                        v-model="form.featured_image"
                        label="Imagen de portada (Grid / Listado)"
                        help="Recomendado 16:9 o 4:3"
                    />

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Icono auxiliar
                        </label>
                        <IconPicker v-model="form.icon" placeholder="Seleccionar icono..." />
                    </div>
                </div>
            </aside>
        </div>
    </form>
</template>
