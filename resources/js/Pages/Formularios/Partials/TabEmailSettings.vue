<script setup>
import { computed, ref, watch } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import ColorPicker from 'primevue/colorpicker';
import WysiwygEditor from '@/Components/Admin/WysiwygEditor.vue';
import MediaPickerModal from '@/Components/Admin/MediaPickerModal.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

// Inicializar settings si no existen
if (!props.form.settings) {
    props.form.settings = {};
}
if (!props.form.settings.email_header_color) {
    props.form.settings.email_header_color = '059669';
}

// Auto-seleccionar inteligentemente el campo de email si el usuario activa la autorespuesta
watch(() => props.form.email_user_enabled, (nuevo) => {
    if (nuevo && !props.form.email_user_field) {
        const campoEmail = (Array.isArray(props.form.fields_schema) ? props.form.fields_schema : [])
            .find(f => f.name && (f.type === 'email' || f.name.includes('email') || f.name.includes('correo')));
        if (campoEmail) {
            props.form.email_user_field = campoEmail.name;
        }
    }
});

// Variables dinámicas para emails
const variablesDisponibles = computed(() => {
    const vars = [
        { tag: '{form.name}', desc: 'Nombre del formulario' },
        { tag: '{form.submission_date}', desc: 'Fecha y hora del envío' },
        { tag: '{site.name}', desc: 'Nombre de la web' },
        { tag: '{site.url}', desc: 'URL del sitio' },
        { tag: '{submission.ip}', desc: 'Dirección IP del remitente' },
        { tag: '{all_fields}', desc: 'Todos los campos en tabla HTML estructurada' },
    ];

    if (Array.isArray(props.form.fields_schema)) {
        props.form.fields_schema.forEach(f => {
            if (f.name && !['heading', 'paragraph', 'separator'].includes(f.type)) {
                vars.push({
                    tag: `{field.${f.name}}`,
                    desc: f.label ? `Campo «${f.label}»` : `Campo ${f.name}`,
                });
            }
        });
    }

    return vars;
});

// ─── Regla PrimeVue 4: Modelos Computados Desambiguados ──────────────────────────────
const modeloEmailUserField = computed({
    get: () => props.form.email_user_field ? String(props.form.email_user_field) : 'none',
    set: (val) => {
        props.form.email_user_field = (val === 'none' || !val) ? '' : String(val);
    },
});

const opcionesEmailUser = computed(() => [
    { label: 'Ninguno (Seleccionar campo de email)', value: 'none' },
    ...(Array.isArray(props.form.fields_schema) ? props.form.fields_schema : [])
        .filter(f => f.name && (f.type === 'email' || f.name.includes('email') || f.name.includes('correo')))
        .map(f => ({ label: `${f.label || f.name} (${f.name})`, value: f.name }))
]);

function insertarVariable(variable, destino) {
    if (destino === 'admin_template') {
        props.form.email_admin_template += ` ${variable} `;
    } else if (destino === 'user_template') {
        props.form.email_user_template += ` ${variable} `;
    }
}

const pickerLogoAbierto = ref(false);

/**
 * Callback del MediaPicker: al seleccionar una imagen, almacenar la URL pública.
 */
function onLogoSeleccionado(media) {
    const url = media?.url || media?.full_url || '';
    if (url) {
        props.form.settings.email_logo_url = url;
    }
    pickerLogoAbierto.value = false;
}

function eliminarLogo() {
    props.form.settings.email_logo_url = '';
}
</script>

<template>
    <div class="space-y-8 max-w-4xl">
        <!-- 0. Personalización Visual del Email -->
        <div class="p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-800 space-y-4">
            <div class="pb-3 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="pi pi-palette text-xs text-indigo-500 mr-1.5" />
                    Personalización Visual del Email
                </h3>
                <p class="text-xs text-zinc-500">
                    Personaliza el aspecto de la cabecera del email de notificación.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                <!-- Color de cabecera -->
                <div class="space-y-2">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Color de la Cabecera
                    </label>
                    <div class="flex items-center gap-3">
                        <ColorPicker
                            v-model="form.settings.email_header_color"
                            format="hex"
                            class="shrink-0"
                        />
                        <InputText
                            :model-value="'#' + (form.settings.email_header_color || '059669')"
                            @update:model-value="(v) => form.settings.email_header_color = v.replace('#', '')"
                            class="w-28 text-xs font-mono"
                            placeholder="#059669"
                        />
                        <!-- Previsualización -->
                        <div
                            class="h-9 flex-1 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center px-3"
                            :style="{ backgroundColor: '#' + (form.settings.email_header_color || '059669') }"
                        >
                            <span class="text-white text-[11px] font-bold drop-shadow-sm">Vista previa</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-zinc-400">
                        Se aplica como fondo de la cabecera del email enviado.
                    </p>
                </div>

                <!-- Logotipo del email -->
                <div class="space-y-2">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Logotipo en la Cabecera (Opcional)
                    </label>
                    <div v-if="form.settings.email_logo_url" class="flex items-center gap-3">
                        <div
                            class="w-32 h-12 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center p-1.5 overflow-hidden"
                            :style="{ backgroundColor: '#' + (form.settings.email_header_color || '059669') }"
                        >
                            <img
                                :src="form.settings.email_logo_url"
                                alt="Logo email"
                                class="max-h-full max-w-full object-contain"
                            />
                        </div>
                        <button
                            type="button"
                            @click="eliminarLogo"
                            class="text-xs text-red-500 hover:text-red-700 transition-colors cursor-pointer"
                        >
                            <i class="pi pi-trash text-xs" /> Quitar
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="pickerLogoAbierto = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
                    >
                        <i class="pi pi-image text-xs" />
                        {{ form.settings.email_logo_url ? 'Cambiar Logo' : 'Seleccionar Logo' }}
                    </button>
                    <MediaPickerModal
                        v-model:visible="pickerLogoAbierto"
                        filtro-tipo-inicial="image"
                        @seleccionar="onLogoSeleccionado"
                    />
                    <p class="text-[11px] text-zinc-400">
                        Se mostrará en la cabecera del email. Usa una imagen PNG o JPG (los emails no soportan SVG).
                    </p>
                </div>
            </div>
        </div>

        <!-- Bloque de variables disponibles con un solo clic -->
        <div class="p-4 rounded-xl bg-emerald-50/40 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/80 space-y-2">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">
                    Variables Dinámicas (Haz clic para copiar o insertar)
                </h4>
                <span class="text-[11px] text-emerald-600 dark:text-emerald-400">Se sustituyen con los valores reales del envío</span>
            </div>
            <div class="flex flex-wrap gap-1.5 pt-1">
                <button
                    v-for="v in variablesDisponibles"
                    :key="v.tag"
                    type="button"
                    @click="insertarVariable(v.tag, 'admin_template')"
                    class="px-2 py-1 rounded bg-white dark:bg-zinc-800 border border-emerald-300 dark:border-emerald-700 text-xs font-mono text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition-colors cursor-pointer"
                    :title="v.desc"
                >
                    {{ v.tag }}
                </button>
            </div>
        </div>

        <!-- 1. Notificación al Administrador -->
        <div class="p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-800 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-700">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        Notificación por Email al Administrador
                    </h3>
                    <p class="text-xs text-zinc-500">
                        Envía un correo con los datos cada vez que un usuario completa el formulario.
                    </p>
                </div>
                <ToggleSwitch v-model="form.email_admin_enabled" />
            </div>

            <div v-if="form.email_admin_enabled" class="space-y-4 pt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Destinatario Override (Opcional)</label>
                        <InputText
                            v-model="form.email_admin_to"
                            placeholder="Dejar vacío para usar el email global del sitio"
                            class="w-full text-xs font-mono"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Asunto del Email</label>
                        <InputText v-model="form.email_admin_subject" class="w-full text-xs" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Cuerpo del Email (Editor Enriquecido HTML)</label>
                    <WysiwygEditor
                        v-model="form.email_admin_template"
                        placeholder="Diseña la plantilla del email para el administrador..."
                        height="220px"
                    />
                </div>
            </div>
        </div>

        <!-- 2. Autorespuesta al Usuario -->
        <div class="p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-800 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-700">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        Autorespuesta al Usuario Remitente
                    </h3>
                    <p class="text-xs text-zinc-500">
                        Envía un correo de confirmación instantáneo a quien completó el formulario.
                    </p>
                </div>
                <ToggleSwitch v-model="form.email_user_enabled" />
            </div>

            <div v-if="form.email_user_enabled" class="space-y-4 pt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Campo que contiene el Email del usuario *
                        </label>
                        <Select
                            v-model="modeloEmailUserField"
                            :options="opcionesEmailUser"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                        <p v-if="!form.email_user_field" class="text-[11px] text-amber-600 dark:text-amber-400">
                            ⚠️ Selecciona el campo del formulario que recoge el email del usuario.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Asunto del Email</label>
                        <InputText v-model="form.email_user_subject" class="w-full text-xs" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Cuerpo del Email de Confirmación</label>
                    <WysiwygEditor
                        v-model="form.email_user_template"
                        placeholder="Diseña la autorespuesta que recibirá el visitante..."
                        height="220px"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
