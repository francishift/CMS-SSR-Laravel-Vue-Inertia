<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Admin/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';

// PrimeVue 4 Components
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Button from 'primevue/button';

// Módulos especializados del constructor de formularios
import FieldPalette from './Partials/FieldPalette.vue';
import FieldCanvas from './Partials/FieldCanvas.vue';
import FieldInspector from './Partials/FieldInspector.vue';
import TabGeneralSettings from './Partials/TabGeneralSettings.vue';
import TabEmailSettings from './Partials/TabEmailSettings.vue';
import TabSecuritySettings from './Partials/TabSecuritySettings.vue';
import TabScriptsSettings from './Partials/TabScriptsSettings.vue';
import TabLivePreview from './Partials/TabLivePreview.vue';

const props = defineProps({
    formulario:          { type: Object, default: null },
    modo:                { type: String, default: 'crear' },
    turnstileConfigured: { type: Boolean, default: false },
});

const tabActiva = ref('campos');
const campoSeleccionadoIndex = ref(0);

// Normalización de datos recibidos
const fData = props.formulario?.data || props.formulario || {};
const formularioId = computed(() => fData.id || props.formulario?.id || null);

// ─── Inicialización Reactiva del Formulario con Inertia useForm ───────────────────────
const form = useForm({
    name: fData.name || '',
    slug: fData.slug || '',
    description: fData.description || '',
    fields_schema: fData.fields_schema ? JSON.parse(JSON.stringify(fData.fields_schema)) : [
        {
            id: 'campo_' + Math.random().toString(36).substring(2, 7),
            type: 'text',
            name: 'nombre_completo',
            label: 'Nombre completo',
            placeholder: 'Escribe tu nombre y apellidos',
            required: true,
            width: 'half',
            options: [],
        },
        {
            id: 'campo_' + Math.random().toString(36).substring(2, 7),
            type: 'email',
            name: 'email',
            label: 'Correo electrónico',
            placeholder: 'tunombre@ejemplo.com',
            required: true,
            width: 'half',
            options: [],
        },
        {
            id: 'campo_' + Math.random().toString(36).substring(2, 7),
            type: 'textarea',
            name: 'mensaje',
            label: 'Mensaje o consulta',
            placeholder: '¿En qué podemos ayudarte?',
            required: true,
            width: 'full',
            options: [],
        },
    ],
    settings: fData.settings || {
        submit_button_text: 'Enviar Formulario',
        submit_button_color: 'emerald',
        submit_button_align: 'left',
    },
    email_admin_enabled: fData.email_admin_enabled ?? true,
    email_admin_to: fData.email_admin_to || '',
    email_admin_subject: fData.email_admin_subject || 'Nuevo envío en {form.name}',
    email_admin_template: fData.email_admin_template || '<p>Has recibido un nuevo mensaje a través del formulario <strong>{form.name}</strong>:</p>{all_fields}',
    email_user_enabled: fData.email_user_enabled ?? false,
    email_user_field: fData.email_user_field || '',
    email_user_subject: fData.email_user_subject || 'Hemos recibido tu solicitud',
    email_user_template: fData.email_user_template || '<p>Hola {field.nombre_completo},</p><p>Hemos recibido tu consulta y nos pondremos en contacto contigo lo antes posible.</p><p>Atentamente,<br>{site.name}</p>',
    scripts_before: fData.scripts_before || '',
    scripts_on_submit: fData.scripts_on_submit || '',
    scripts_after: fData.scripts_after || '',
    success_message: fData.success_message || 'Formulario enviado correctamente.',
    success_redirect_url: fData.success_redirect_url || '',
    max_submissions_per_ip: fData.max_submissions_per_ip ?? 20,
    is_active: fData.is_active ?? true,
    honeypot_field: fData.honeypot_field || 'website_url',
    requires_captcha: fData.requires_captcha ?? false,
});

// Campo actualmente inspeccionado
const campoActual = computed(() => {
    if (!form.fields_schema || form.fields_schema.length === 0) return null;
    if (campoSeleccionadoIndex.value >= form.fields_schema.length) {
        campoSeleccionadoIndex.value = Math.max(0, form.fields_schema.length - 1);
    }
    return form.fields_schema[campoSeleccionadoIndex.value] || null;
});

// ─── Funciones de Gestión de Campos ──────────────────────────────────────────────────
function agregarCampo(tipo) {
    const sufijo = Math.random().toString(36).substring(2, 7);
    const esEstructura = ['heading', 'paragraph', 'separator'].includes(tipo);
    const nombreLimpio = esEstructura ? '' : `${tipo}_${sufijo}`;

    const nuevoCampo = {
        id: 'campo_' + sufijo,
        type: tipo,
        name: nombreLimpio,
        label: esEstructura ? '' : `Campo ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`,
        placeholder: '',
        required: false,
        width: 'full',
        default_value: '',
        css_class: '',
        options: ['select', 'multiselect', 'radio', 'checkbox'].includes(tipo)
            ? [
                { label: 'Opción 1', value: 'opcion_1' },
                { label: 'Opción 2', value: 'opcion_2' },
              ]
            : [],
        content: tipo === 'heading' ? 'Título de Sección' : (tipo === 'paragraph' ? 'Texto descriptivo...' : ''),
        max_file_size: tipo === 'file' ? 10240 : null,
        allowed_extensions: tipo === 'file' ? ['pdf', 'doc', 'docx', 'jpg', 'png'] : null,
        max_rating: tipo === 'rating' ? 5 : null,
        min: tipo === 'slider' ? 0 : null,
        max: tipo === 'slider' ? 100 : null,
        conditional: { field: null, operator: 'equals', value: '' },
    };

    form.fields_schema.push(nuevoCampo);
    campoSeleccionadoIndex.value = form.fields_schema.length - 1;
}

function duplicarCampo(index) {
    const original = form.fields_schema[index];
    if (!original) return;
    const clon = JSON.parse(JSON.stringify(original));
    const sufijo = Math.random().toString(36).substring(2, 7);
    clon.id = 'campo_' + sufijo;
    if (clon.name) {
        clon.name = clon.name + '_copia';
    }
    if (clon.label) {
        clon.label = clon.label + ' (Copia)';
    }
    form.fields_schema.splice(index + 1, 0, clon);
    campoSeleccionadoIndex.value = index + 1;
}

function eliminarCampo(index) {
    form.fields_schema.splice(index, 1);
    if (campoSeleccionadoIndex.value >= form.fields_schema.length) {
        campoSeleccionadoIndex.value = Math.max(0, form.fields_schema.length - 1);
    }
}

function guardar() {
    if (props.modo === 'crear') {
        form.post(route('panel.formularios.store'));
    } else {
        form.put(route('panel.formularios.update', formularioId.value));
    }
}
</script>

<template>
    <Head :title="modo === 'crear' ? 'Nuevo Formulario' : `Editar: ${form.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-2.5 sm:gap-4">
                <div class="flex items-center gap-2.5 min-w-0">
                    <Link
                        :href="route('panel.formularios.index')"
                        class="p-1.5 sm:p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors shrink-0 flex items-center justify-center"
                        title="Volver al listado"
                    >
                        <i class="pi pi-arrow-left text-xs sm:text-sm" />
                    </Link>
                    <h1 class="text-sm sm:text-lg font-semibold text-zinc-900 dark:text-white truncate leading-none">
                        {{ modo === 'crear' ? 'Nuevo Formulario' : `Editar: ${form.name || 'Formulario'}` }}
                    </h1>
                </div>

                <button
                    type="button"
                    @click="guardar"
                    :disabled="form.processing"
                    class="px-3 sm:px-4 py-1.5 sm:py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition-colors shadow-sm cursor-pointer inline-flex items-center gap-1.5 shrink-0"
                >
                    <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs" />
                    <i v-else class="pi pi-check text-xs" />
                    <span class="hidden sm:inline">{{ form.processing ? 'Guardando...' : (modo === 'crear' ? 'Crear Formulario' : 'Guardar Cambios') }}</span>
                    <span class="sm:hidden">{{ form.processing ? '...' : 'Guardar' }}</span>
                </button>
            </div>
        </template>

        <div class="p-4 sm:p-6 w-full space-y-6">
            <PageHeader
                :title="modo === 'crear' ? 'Crear Nuevo Formulario' : `Editar: ${form.name || 'Formulario'}`"
                description="Diseña los campos, configura correos de notificación y reglas de seguridad"
                divider
            >
                <template v-if="form.slug" #badge>
                    <span class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-xs font-mono text-zinc-500 dark:text-zinc-400">
                        /{{ form.slug }}
                    </span>
                </template>

                <template #actions>
                    <Link
                        v-if="modo === 'editar' && formularioId"
                        :href="route('panel.formularios.submissions', formularioId)"
                    >
                        <Button
                            label="Bandeja de Envíos"
                            icon="pi pi-inbox"
                            severity="secondary"
                            outlined
                            size="small"
                        />
                    </Link>

                    <Button
                        :label="form.processing ? 'Guardando...' : (modo === 'crear' ? 'Crear Formulario' : 'Guardar Cambios')"
                        :icon="form.processing ? 'pi pi-spin pi-spinner' : 'pi pi-check'"
                        severity="emerald"
                        size="small"
                        :disabled="form.processing"
                        @click="guardar"
                        class="shadow-sm font-medium cursor-pointer"
                    />
                </template>
            </PageHeader>
            <!-- Errores de validación generales si existen -->
            <div
                v-if="Object.keys(form.errors).length > 0"
                class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 text-red-700 dark:text-red-400 text-sm space-y-1 shadow-xs"
            >
                <div class="flex items-center gap-2 font-semibold">
                    <i class="pi pi-exclamation-circle text-base" />
                    <span>Por favor corrige los siguientes errores:</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-0.5 ml-4">
                    <li v-for="(error, campo) in form.errors" :key="campo">{{ error }}</li>
                </ul>
            </div>

            <!-- Contenedor principal con Tabs PrimeVue 4 -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xs overflow-hidden">
                <Tabs :value="tabActiva" @update:value="(val) => tabActiva = val">
                    <TabList class="px-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/60 flex items-center overflow-x-auto">
                        <Tab value="campos" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                            <i class="pi pi-th-large mr-2 text-emerald-600 dark:text-emerald-400" />
                            <span>1. Constructor de Campos ({{ form.fields_schema.length }})</span>
                        </Tab>
                        <Tab value="configuracion" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                            <i class="pi pi-cog mr-2 text-blue-600 dark:text-blue-400" />
                            <span>2. Configuración General</span>
                        </Tab>
                        <Tab value="emails" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                            <i class="pi pi-envelope mr-2 text-amber-600 dark:text-amber-400" />
                            <span>3. Correos y Notificaciones</span>
                        </Tab>
                        <Tab value="seguridad" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                            <i class="pi pi-shield mr-2 text-purple-600 dark:text-purple-400" />
                            <span>4. Seguridad y Anti-Spam</span>
                        </Tab>
                        <Tab value="scripts" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                            <i class="pi pi-code mr-2 text-rose-600 dark:text-rose-400" />
                            <span>5. Scripts y Eventos</span>
                        </Tab>
                        <Tab value="preview" class="!text-xs !py-3.5 font-semibold cursor-pointer">
                            <i class="pi pi-eye mr-2 text-cyan-600 dark:text-cyan-400" />
                            <span>6. Vista Previa en Vivo</span>
                        </Tab>
                    </TabList>

                    <TabPanels class="p-4 sm:p-6">
                        <!-- PESTAÑA 1: CONSTRUCTOR DE CAMPOS (DRAG & DROP) -->
                        <TabPanel value="campos">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                <!-- Paleta Izquierda: Catálogo de 23 tipos -->
                                <div class="lg:col-span-3">
                                    <FieldPalette @add-field="agregarCampo" />
                                </div>

                                <!-- Lienzo Central: Drag and drop -->
                                <div class="lg:col-span-5">
                                    <FieldCanvas
                                        :fields="form.fields_schema"
                                        :selected-index="campoSeleccionadoIndex"
                                        @update:fields="(val) => form.fields_schema = val"
                                        @select="(idx) => campoSeleccionadoIndex = idx"
                                        @duplicate="duplicarCampo"
                                        @delete="eliminarCampo"
                                    />
                                </div>

                                <!-- Inspector Derecho: Propiedades del campo seleccionado -->
                                <div class="lg:col-span-4">
                                    <FieldInspector
                                        :campo="campoActual"
                                        :todos-los-campos="form.fields_schema"
                                    />
                                </div>
                            </div>
                        </TabPanel>

                        <!-- PESTAÑA 2: CONFIGURACIÓN GENERAL -->
                        <TabPanel value="configuracion">
                            <TabGeneralSettings :form="form" :modo="modo" />
                        </TabPanel>

                        <!-- PESTAÑA 3: EMAILS Y NOTIFICACIONES -->
                        <TabPanel value="emails">
                            <TabEmailSettings :form="form" />
                        </TabPanel>

                        <!-- PESTAÑA 4: SEGURIDAD Y ANTI-SPAM -->
                        <TabPanel value="seguridad">
                            <TabSecuritySettings :form="form" :turnstile-configured="props.turnstileConfigured" />
                        </TabPanel>

                        <!-- PESTAÑA 5: SCRIPTS Y EVENTOS -->
                        <TabPanel value="scripts">
                            <TabScriptsSettings :form="form" />
                        </TabPanel>

                        <!-- PESTAÑA 6: VISTA PREVIA EN VIVO -->
                        <TabPanel value="preview">
                            <TabLivePreview :form="form" :modo="modo" />
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
