<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

// Componentes PrimeVue para el renderizado interactivo de los campos
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import ToggleSwitch from 'primevue/toggleswitch';
import DatePicker from 'primevue/datepicker';
import Rating from 'primevue/rating';
import Slider from 'primevue/slider';
import ColorPicker from 'primevue/colorpicker';
import Button from 'primevue/button';

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

const toast = useToast();

// Estado reactivo de la prueba
const previewValores = ref({});
const modoPruebaEnvio = ref('simulacion'); // 'simulacion' | 'real'
const enviandoPrueba = ref(false);
const erroresPreview = ref({});
const resultadoPrueba = ref(null);
const verJsonPayload = ref(false);

function claseAnchoColumna(width) {
    switch (width) {
        case 'half': return 'col-span-1 sm:col-span-6';
        case 'third': return 'col-span-1 sm:col-span-4';
        default: return 'col-span-1 sm:col-span-12';
    }
}

function tieneEnlace(campo) {
    if (!campo) return false;
    if (campo.link_url && campo.link_url.trim() !== '') return true;
    if (campo.label && /<a\s+[^>]*href=["']([^"']*)["'][^>]*>(.*?)<\/a>/i.test(campo.label)) return true;
    return false;
}

function obtenerTextoLimpio(texto) {
    if (!texto) return '';
    return String(texto).replace(/<[^>]*>/g, '').trim();
}

// Evaluación en tiempo real de lógica condicional en la vista previa
function cumpleCondicionPreview(campo) {
    if (!campo.conditional?.field) return true;
    const valorDependiente = previewValores.value[campo.conditional.field];
    const op = campo.conditional.operator || 'equals';
    const valEsperado = campo.conditional.value;

    switch (op) {
        case 'equals':
            return String(valorDependiente || '').toLowerCase() === String(valEsperado || '').toLowerCase();
        case 'not_equals':
            return String(valorDependiente || '').toLowerCase() !== String(valEsperado || '').toLowerCase();
        case 'contains':
            return String(valorDependiente || '').toLowerCase().includes(String(valEsperado || '').toLowerCase());
        case 'empty':
            return !valorDependiente || valorDependiente === '' || (Array.isArray(valorDependiente) && valorDependiente.length === 0);
        case 'not_empty':
            return !!valorDependiente && valorDependiente !== '' && (!Array.isArray(valorDependiente) || valorDependiente.length > 0);
        default:
            return true;
    }
}

async function probarEnvioPreview() {
    erroresPreview.value = {};
    resultadoPrueba.value = null;

    // 1. Validar campos requeridos visibles
    let hayErrores = false;
    const listaCampos = Array.isArray(props.form.fields_schema) ? props.form.fields_schema : [];

    listaCampos.forEach(campo => {
        if (!cumpleCondicionPreview(campo)) return;
        if (campo.required && !['heading', 'paragraph', 'separator', 'hidden'].includes(campo.type)) {
            const val = previewValores.value[campo.name];
            if (val === undefined || val === null || val === '' || (Array.isArray(val) && val.length === 0)) {
                erroresPreview.value[campo.name] = `El campo «${campo.label || campo.name}» es obligatorio.`;
                hayErrores = true;
            }
        }
    });

    if (hayErrores) {
        toast.add({
            severity: 'warn',
            summary: 'Campos obligatorios',
            detail: 'Por favor completa los campos requeridos antes de enviar.',
            life: 4000,
        });
        return;
    }

    enviandoPrueba.value = true;

    // 2. Modo Simulación (por defecto, no ensucia la base de datos)
    if (modoPruebaEnvio.value === 'simulacion') {
        setTimeout(() => {
            enviandoPrueba.value = false;
            resultadoPrueba.value = {
                success: true,
                message: props.form.success_message || '¡Formulario enviado correctamente!',
                data: JSON.parse(JSON.stringify(previewValores.value)),
                redirect_url: props.form.success_redirect_url,
                real: false,
            };
            toast.add({
                severity: 'success',
                summary: 'Prueba simulada con éxito',
                detail: props.form.success_message || 'Simulación completada.',
                life: 4000,
            });
        }, 500);
        return;
    }

    // 3. Modo Envío Real (vía API pública de recepción)
    if (!props.form.slug || props.modo === 'crear') {
        enviandoPrueba.value = false;
        toast.add({
            severity: 'info',
            summary: 'Formulario no guardado',
            detail: 'Guarda primero el formulario para realizar un envío real a la base de datos.',
            life: 5000,
        });
        return;
    }

    try {
        const payload = {
            fields: previewValores.value,
        };
        const response = await axios.post(`/formularios/${props.form.slug}/enviar`, payload);

        enviandoPrueba.value = false;
        resultadoPrueba.value = {
            success: true,
            message: response.data?.message || props.form.success_message || 'Envío guardado en la base de datos.',
            data: JSON.parse(JSON.stringify(previewValores.value)),
            real: true,
        };
        toast.add({
            severity: 'success',
            summary: 'Envío real registrado',
            detail: 'El mensaje ha quedado guardado en la bandeja de envíos.',
            life: 5000,
        });
    } catch (err) {
        enviandoPrueba.value = false;
        if (err.response?.status === 422) {
            const errs = err.response.data?.errors || {};
            Object.keys(errs).forEach(key => {
                const nombreCampo = key.replace(/^fields\./, '');
                erroresPreview.value[nombreCampo] = Array.isArray(errs[key]) ? errs[key][0] : errs[key];
            });
            toast.add({
                severity: 'error',
                summary: 'Validación del servidor',
                detail: err.response.data?.message || 'Por favor revisa los campos señalados.',
                life: 5000,
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error en el envío',
                detail: err.response?.data?.message || err.message || 'No se pudo procesar el envío.',
                life: 5000,
            });
        }
    }
}

function limpiarPreviewValores() {
    previewValores.value = {};
    erroresPreview.value = {};
    resultadoPrueba.value = null;
    toast.add({
        severity: 'info',
        summary: 'Formulario restablecido',
        detail: 'Campos de prueba vaciados.',
        life: 2000,
    });
}
</script>

<template>
    <div>
        <!-- Barra de Herramientas de Prueba de Envío -->
        <div class="max-w-2xl mx-auto mb-4 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700/60 space-y-2.5 text-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-zinc-600 dark:text-zinc-300">Modo de prueba:</span>
                    <div class="inline-flex rounded-lg border border-zinc-300 dark:border-zinc-700 p-0.5 bg-zinc-200/50 dark:bg-zinc-800">
                        <button
                            type="button"
                            @click="modoPruebaEnvio = 'simulacion'"
                            :class="modoPruebaEnvio === 'simulacion' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200'"
                            class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="pi pi-play text-[10px]" />
                            Simulación
                        </button>
                        <button
                            type="button"
                            @click="modoPruebaEnvio = 'real'"
                            :class="modoPruebaEnvio === 'real' ? 'bg-emerald-600 text-white shadow-sm font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200'"
                            class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1.5 cursor-pointer"
                            :title="modo === 'crear' ? 'Guarda primero el formulario para probar el envío real' : 'Guarda el envío real en la base de datos y envía emails'"
                        >
                            <i class="pi pi-database text-[10px]" />
                            Envío Real a BD
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        icon="pi pi-code"
                        :label="verJsonPayload ? 'Ocultar JSON' : 'Ver JSON'"
                        severity="secondary"
                        text
                        size="small"
                        class="text-xs !py-1 !px-2"
                        @click="verJsonPayload = !verJsonPayload"
                    />
                    <Button
                        type="button"
                        icon="pi pi-trash"
                        label="Limpiar"
                        severity="secondary"
                        text
                        size="small"
                        class="text-xs !py-1 !px-2"
                        @click="limpiarPreviewValores"
                    />
                </div>
            </div>

            <!-- Banner explicativo del modo activo -->
            <div
                class="text-[11px] p-2 rounded-lg flex items-center gap-2 border transition-all"
                :class="modoPruebaEnvio === 'real'
                    ? 'bg-emerald-50/70 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                    : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700'"
            >
                <i :class="modoPruebaEnvio === 'real' ? 'pi pi-send text-emerald-600 dark:text-emerald-400' : 'pi pi-info-circle text-zinc-500'" />
                <span v-if="modoPruebaEnvio === 'real'">
                    <strong>Modo Envío Real activo:</strong> Guarda el envío en la base de datos y despacha los correos electrónicos configurados (notificación admin y autorespuesta).
                </span>
                <span v-else>
                    <strong>Modo Simulación activo:</strong> Comprueba la validación visual y las condiciones en el navegador. <em>(No guarda en la base de datos ni envía correos)</em>. Para recibir el email, activa <strong>«Envío Real a BD»</strong>.
                </span>
            </div>
        </div>

        <!-- Inspector de JSON Payload en Vivo -->
        <div v-if="verJsonPayload" class="max-w-2xl mx-auto mb-4 p-4 rounded-xl bg-zinc-900 text-emerald-400 font-mono text-[11px] overflow-x-auto border border-zinc-800">
            <div class="flex justify-between items-center text-zinc-400 border-b border-zinc-800 pb-1 mb-2">
                <span>Payload que se enviará en { fields: ... }</span>
                <span class="text-[10px]">{{ Object.keys(previewValores).length }} campos con datos</span>
            </div>
            <pre>{{ JSON.stringify({ fields: previewValores }, null, 2) }}</pre>
        </div>

        <!-- Panel de Resultado de la Prueba -->
        <div
            v-if="resultadoPrueba"
            class="max-w-2xl mx-auto mb-4 p-4 rounded-xl border flex items-start gap-3 transition-all"
            :class="resultadoPrueba.success ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200' : 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'"
        >
            <i :class="resultadoPrueba.success ? 'pi pi-check-circle text-emerald-600 dark:text-emerald-400' : 'pi pi-times-circle text-red-600 dark:text-red-400'" class="text-xl mt-0.5 shrink-0" />
            <div class="flex-1 text-xs">
                <h4 class="font-bold mb-1">
                    {{ resultadoPrueba.success ? (resultadoPrueba.real ? '¡Envío Real Guardado y Notificaciones Despachadas!' : '¡Prueba Simulada Exitosa!') : 'Error en la prueba' }}
                </h4>
                <p>{{ resultadoPrueba.message }}</p>
                <p v-if="resultadoPrueba.real" class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1 font-medium">
                    ✓ Registro almacenado en la bandeja de «Envíos».<br />
                    ✓ Notificaciones por correo electrónico despachadas a los destinatarios configurados.
                </p>
                <p v-if="resultadoPrueba.redirect_url" class="text-[11px] text-zinc-500 mt-1">
                    Redirección configurada: <code class="underline">{{ resultadoPrueba.redirect_url }}</code>
                </p>
            </div>
            <Button icon="pi pi-times" text rounded size="small" class="!w-6 !h-6" @click="resultadoPrueba = null" />
        </div>

        <!-- Tarjeta del Formulario en Vista Previa -->
        <div class="max-w-2xl mx-auto p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-6">
            <div class="border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">
                    {{ form.name || 'Vista Previa del Formulario' }}
                </h3>
                <p v-if="form.description" class="text-xs text-zinc-500 mt-1">
                    {{ form.description }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <template v-for="campo in form.fields_schema" :key="campo.id">
                    <!-- Evaluación de Lógica Condicional en Vivo -->
                    <template v-if="cumpleCondicionPreview(campo)">
                        <!-- Estructura: Heading -->
                        <div v-if="campo.type === 'heading'" class="col-span-1 sm:col-span-12 pt-2">
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 border-b pb-1 border-zinc-200 dark:border-zinc-800">
                                {{ campo.content }}
                            </h4>
                        </div>

                        <!-- Estructura: Paragraph -->
                        <div v-else-if="campo.type === 'paragraph'" class="col-span-1 sm:col-span-12">
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                {{ campo.content }}
                            </p>
                        </div>

                        <!-- Estructura: Separator -->
                        <div v-else-if="campo.type === 'separator'" class="col-span-1 sm:col-span-12 my-2">
                            <hr class="border-zinc-200 dark:border-zinc-800" />
                        </div>

                        <!-- Campo oculto: no renderizar en preview visual -->
                        <div v-else-if="campo.type === 'hidden'" class="hidden" />

                        <!-- Campos Interactivos de entrada -->
                        <div v-else :class="claseAnchoColumna(campo.width)" class="space-y-1.5">
                            <label
                                v-if="!['toggle'].includes(campo.type) && (campo.type !== 'checkbox' || campo.options?.length)"
                                class="text-xs font-medium text-zinc-700 dark:text-zinc-300 flex items-center gap-1"
                            >
                                <span>{{ obtenerTextoLimpio(campo.label || campo.name) }}</span>
                                <span v-if="campo.required" class="text-red-500">*</span>
                            </label>

                            <!-- Text, Tel, Url, Password, Email -->
                            <template v-if="['text', 'email', 'tel', 'url', 'password'].includes(campo.type)">
                                <InputText
                                    v-model="previewValores[campo.name]"
                                    :type="campo.type"
                                    :placeholder="campo.placeholder"
                                    :invalid="!!erroresPreview[campo.name]"
                                    class="w-full text-xs"
                                />
                            </template>

                            <!-- Textarea -->
                            <template v-else-if="campo.type === 'textarea'">
                                <Textarea
                                    v-model="previewValores[campo.name]"
                                    :placeholder="campo.placeholder"
                                    :invalid="!!erroresPreview[campo.name]"
                                    rows="3"
                                    class="w-full text-xs"
                                />
                            </template>

                            <!-- Number -->
                            <template v-else-if="campo.type === 'number'">
                                <InputNumber
                                    v-model="previewValores[campo.name]"
                                    :placeholder="campo.placeholder"
                                    :invalid="!!erroresPreview[campo.name]"
                                    class="w-full text-xs"
                                />
                            </template>

                            <!-- Select -->
                            <template v-else-if="campo.type === 'select'">
                                <Select
                                    v-model="previewValores[campo.name]"
                                    :options="campo.options || []"
                                    optionLabel="label"
                                    optionValue="value"
                                    :placeholder="campo.placeholder || 'Selecciona una opción'"
                                    :invalid="!!erroresPreview[campo.name]"
                                    class="w-full text-xs"
                                />
                            </template>

                            <!-- MultiSelect -->
                            <template v-else-if="campo.type === 'multiselect'">
                                <MultiSelect
                                    v-model="previewValores[campo.name]"
                                    :options="campo.options || []"
                                    optionLabel="label"
                                    optionValue="value"
                                    :placeholder="campo.placeholder || 'Selecciona varias opciones'"
                                    :invalid="!!erroresPreview[campo.name]"
                                    class="w-full text-xs"
                                />
                            </template>

                            <!-- Radio -->
                            <template v-else-if="campo.type === 'radio'">
                                <div class="flex flex-col gap-1.5 pt-1">
                                    <div v-for="opc in campo.options" :key="opc.value" class="flex items-center gap-2">
                                        <RadioButton
                                            v-model="previewValores[campo.name]"
                                            :value="opc.value"
                                            :input-id="`${campo.name}_${opc.value}`"
                                        />
                                        <label :for="`${campo.name}_${opc.value}`" class="text-xs text-zinc-600 dark:text-zinc-400 cursor-pointer">
                                            {{ opc.label }}
                                        </label>
                                    </div>
                                </div>
                            </template>

                            <!-- Checkbox -->
                            <template v-else-if="campo.type === 'checkbox'">
                                <div v-if="campo.options?.length" class="flex flex-col gap-1.5 pt-1">
                                    <div v-for="opc in campo.options" :key="opc.value" class="flex items-center gap-2">
                                        <Checkbox
                                            v-model="previewValores[campo.name]"
                                            :value="opc.value"
                                            :input-id="`${campo.name}_${opc.value}`"
                                        />
                                        <label :for="`${campo.name}_${opc.value}`" class="text-xs text-zinc-600 dark:text-zinc-400 cursor-pointer">
                                            {{ opc.label }}
                                        </label>
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-2 pt-1">
                                    <Checkbox
                                        v-model="previewValores[campo.name]"
                                        :binary="true"
                                        :input-id="campo.name"
                                    />
                                    <label :for="campo.name" class="text-xs text-zinc-600 dark:text-zinc-400 cursor-pointer select-none">
                                        <span :class="tieneEnlace(campo) ? 'hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors' : ''">
                                            {{ obtenerTextoLimpio(campo.label || campo.placeholder || 'He leído y acepto los términos') }}
                                        </span>
                                        <span v-if="campo.required" class="text-red-500 ml-0.5">*</span>
                                    </label>
                                </div>
                            </template>

                            <!-- ToggleSwitch -->
                            <template v-else-if="campo.type === 'toggle'">
                                <div class="flex items-center gap-2.5 pt-1">
                                    <ToggleSwitch v-model="previewValores[campo.name]" />
                                    <span class="text-xs text-zinc-600 dark:text-zinc-400 select-none">
                                        <span :class="tieneEnlace(campo) ? 'hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors' : ''">
                                            {{ obtenerTextoLimpio(campo.label || campo.name || 'Interruptor') }}
                                        </span>
                                        <span v-if="campo.required" class="text-red-500 ml-0.5">*</span>
                                    </span>
                                </div>
                            </template>

                            <!-- Date / Datetime / Time -->
                            <template v-else-if="['date', 'datetime', 'time'].includes(campo.type)">
                                <DatePicker
                                    v-model="previewValores[campo.name]"
                                    :show-time="campo.type === 'datetime'"
                                    :time-only="campo.type === 'time'"
                                    :placeholder="campo.placeholder"
                                    :invalid="!!erroresPreview[campo.name]"
                                    class="w-full text-xs"
                                />
                            </template>

                            <!-- Rating -->
                            <template v-else-if="campo.type === 'rating'">
                                <div class="pt-1">
                                    <Rating v-model="previewValores[campo.name]" :stars="campo.max_rating || 5" />
                                </div>
                            </template>

                            <!-- Slider -->
                            <template v-else-if="campo.type === 'slider'">
                                <div class="pt-3 px-2">
                                    <Slider v-model="previewValores[campo.name]" :min="campo.min || 0" :max="campo.max || 100" />
                                    <div class="flex justify-between text-[10px] text-zinc-400 mt-1">
                                        <span>{{ campo.min || 0 }}</span>
                                        <span>Valor: {{ previewValores[campo.name] || (campo.min || 0) }}</span>
                                        <span>{{ campo.max || 100 }}</span>
                                    </div>
                                </div>
                            </template>

                            <!-- ColorPicker -->
                            <template v-else-if="campo.type === 'color'">
                                <div class="pt-1">
                                    <ColorPicker v-model="previewValores[campo.name]" />
                                </div>
                            </template>

                            <!-- File -->
                            <template v-else-if="campo.type === 'file'">
                                <div class="border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl p-4 text-center">
                                    <i class="pi pi-upload text-xl text-zinc-400 mb-1 block" />
                                    <span class="text-xs text-zinc-500">Arrastra archivos aquí o haz clic para subir</span>
                                    <span class="text-[10px] text-zinc-400 block mt-0.5">
                                        Máx: {{ Math.round((campo.max_file_size || 10240) / 1024) }} MB
                                    </span>
                                </div>
                            </template>

                            <!-- Mensaje de error de validación -->
                            <small v-if="erroresPreview[campo.name]" class="text-red-500 text-[11px] block mt-1">
                                {{ erroresPreview[campo.name] }}
                            </small>
                        </div>
                    </template>
                </template>
            </div>

            <!-- Botón de envío en vista previa -->
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800" :class="{
                'text-left': form.settings.submit_button_align === 'left',
                'text-center': form.settings.submit_button_align === 'center',
                'text-right': form.settings.submit_button_align === 'right',
            }">
                <Button
                    :label="form.settings.submit_button_text || 'Enviar Formulario'"
                    icon="pi pi-send"
                    :severity="form.settings.submit_button_color || 'emerald'"
                    :class="{'w-full': form.settings.submit_button_align === 'full'}"
                    class="shadow-sm cursor-pointer"
                    :loading="enviandoPrueba"
                    @click="probarEnvioPreview"
                />
            </div>
        </div>
    </div>
</template>
