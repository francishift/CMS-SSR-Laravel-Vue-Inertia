<script setup>
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    modo: {
        type: String,
        default: 'crear',
    },
});

const slugEditadoManualmente = ref(props.modo === 'editar');

const opcionesColorBoton = [
    { label: 'Esmeralda (Verde)', value: 'emerald' },
    { label: 'Azul', value: 'blue' },
    { label: 'Índigo', value: 'indigo' },
    { label: 'Zinc (Gris Neutro)', value: 'zinc' },
    { label: 'Negro / Blanco', value: 'dark' },
];

const opcionesAlineacionBoton = [
    { label: 'Izquierda', value: 'left' },
    { label: 'Centro', value: 'center' },
    { label: 'Derecha', value: 'right' },
    { label: 'Ancho Completo', value: 'full' },
];

const opcionesFeedbackExito = [
    { label: 'Ambos (Recomendado: Formulario + Toast Flotante)', value: 'both' },
    { label: 'Solo Toast Flotante (Limpia campos y avisa flotando)', value: 'toast' },
    { label: 'Solo en el Formulario (Pantalla completa de gracias)', value: 'inline' },
];

if (!props.form.settings) {
    props.form.settings = {};
}
if (!props.form.settings.success_feedback) {
    props.form.settings.success_feedback = 'both';
}

function onNombreInput() {
    if (props.modo === 'crear' && !slugEditadoManualmente.value) {
        props.form.slug = props.form.name
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}
</script>

<template>
    <div class="max-w-3xl space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                    Nombre del Formulario *
                </label>
                <InputText
                    v-model="form.name"
                    @input="onNombreInput"
                    placeholder="ej: Formulario de Contacto Principal"
                    class="w-full text-sm"
                />
                <span v-if="form.errors?.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
            </div>

            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                        Identificador Único (Slug) *
                    </label>
                    <span class="text-[10px] text-zinc-400">Usado en URLs y Page Builder</span>
                </div>
                <InputText
                    v-model="form.slug"
                    @input="slugEditadoManualmente = true"
                    placeholder="ej: contacto-principal"
                    class="w-full text-sm font-mono"
                />
                <span v-if="form.errors?.slug" class="text-xs text-red-500">{{ form.errors.slug }}</span>
            </div>
        </div>

        <div class="space-y-1.5">
            <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Descripción interna</label>
            <Textarea
                v-model="form.description"
                rows="2"
                placeholder="Notas sobre el propósito o uso de este formulario..."
                class="w-full text-sm"
            />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Mensaje de Éxito</label>
                <InputText
                    v-model="form.success_message"
                    placeholder="¡Gracias! Hemos recibido tu mensaje correctamente."
                    class="w-full text-sm"
                />
                <span class="text-[11px] text-zinc-400 block">Mensaje mostrado al usuario tras completar el envío con éxito.</span>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Tipo de Notificación</label>
                <Select
                    v-model="form.settings.success_feedback"
                    :options="opcionesFeedbackExito"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-sm"
                />
                <span class="text-[11px] text-zinc-400 block">Aviso flotante (Toast), en el formulario con auto-scroll o ambos.</span>
            </div>

            <div class="space-y-1.5 sm:col-span-2">
                <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">URL de Redirección (Opcional)</label>
                <InputText
                    v-model="form.success_redirect_url"
                    placeholder="https://tudominio.com/gracias"
                    class="w-full text-sm"
                />
                <span class="text-[11px] text-zinc-400 block">Si se define, redirige al usuario automáticamente a esta URL.</span>
            </div>
        </div>

        <!-- Configuración del botón de envío -->
        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                Diseño del Botón de Envío
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="space-y-1">
                    <label class="text-xs text-zinc-500">Texto del Botón</label>
                    <InputText v-model="form.settings.submit_button_text" class="w-full text-xs" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs text-zinc-500">Color / Tema</label>
                    <Select
                        v-model="form.settings.submit_button_color"
                        :options="opcionesColorBoton"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-xs text-zinc-500">Alineación</label>
                    <Select
                        v-model="form.settings.submit_button_align"
                        :options="opcionesAlineacionBoton"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
