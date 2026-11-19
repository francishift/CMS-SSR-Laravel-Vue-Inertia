<script setup>
/**
 * Componente Modal para Guardar un Elemento o una Sección Completa en la Biblioteca de Presets.
 */
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Button from 'primevue/button';
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    visible: { type: Boolean, required: true },
    elemento: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'guardado-exitoso']);

const nombre = ref('');
const categoria = ref('services');
const descripcion = ref('');
const guardando = ref(false);

// Detección de si lo que se está guardando es una SECCIÓN COMPLETA
const esSeccion = computed(() => {
    if (!props.elemento) return false;
    return Boolean(props.elemento.rows && Array.isArray(props.elemento.rows));
});

const categoriasSeccion = [
    { label: 'Hero / Cabecera', value: 'hero' },
    { label: 'Servicios / Características', value: 'services' },
    { label: 'Contenido / Sobre Nosotros', value: 'content' },
    { label: 'Galerías y Portafolio', value: 'galleries' },
    { label: 'Llamada a la Acción (CTA)', value: 'cta' },
    { label: 'Testimonios / Reseñas', value: 'testimonials' },
    { label: 'Contacto / Formularios', value: 'contact' },
    { label: 'General / Personalizado', value: 'custom' },
];

const categoriasElemento = [
    { label: 'General', value: 'general' },
    { label: 'Encabezados y Cabeceras', value: 'headers' },
    { label: 'Grids y Cuadrículas', value: 'grids' },
    { label: 'Botones y Llamadas a la Acción (CTA)', value: 'ctas' },
    { label: 'Bloques de Contenido', value: 'content' },
    { label: 'Acordeones y FAQs', value: 'accordions' },
    { label: 'Personalizado', value: 'custom' },
];

const categoriasOpciones = computed(() => esSeccion.value ? categoriasSeccion : categoriasElemento);

watch(() => props.elemento, (elem) => {
    if (elem) {
        if (esSeccion.value) {
            nombre.value = elem.name || elem.admin_label || 'Sección Guardada';
            categoria.value = 'services';
        } else {
            nombre.value = elem.name || elem.admin_label || 'Elemento Guardado';
            categoria.value = 'general';
        }
        descripcion.value = '';
    }
}, { immediate: true });

const guardarEnBiblioteca = async () => {
    if (!props.elemento) return;
    if (!nombre.value.trim()) {
        alert('Por favor, introduce un nombre para guardar en la biblioteca.');
        return;
    }

    guardando.value = true;
    try {
        const tipoFinal = esSeccion.value ? 'section' : (props.elemento.type || 'element');
        const payload = {
            name: nombre.value.trim(),
            type: tipoFinal,
            category: categoria.value,
            description: descripcion.value,
            content_json: props.elemento,
        };

        const res = await axios.post(route('builder-library.store'), payload);

        if (res.data?.success) {
            emit('guardado-exitoso', res.data.item);
            emit('update:visible', false);
        }
    } catch (e) {
        console.error('Error al guardar en la biblioteca:', e);
        alert('Ocurrió un error al guardar en la biblioteca.');
    } finally {
        guardando.value = false;
    }
};
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="(val) => emit('update:visible', val)"
        modal
        :header="esSeccion ? 'Guardar Sección Completa en la Biblioteca' : 'Guardar Elemento en la Biblioteca'"
        :style="{ width: '32rem' }"
        :breakpoints="{ '1199px': '60vw', '575px': '90vw' }"
    >
        <div class="space-y-4 py-2">
            <!-- Mensaje informativo para secciones completas -->
            <div v-if="esSeccion" class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-start gap-2.5">
                <i class="pi pi-info-circle text-emerald-600 dark:text-emerald-400 mt-0.5 shrink-0 text-sm" />
                <p class="text-[11px] text-emerald-800 dark:text-emerald-300 leading-relaxed">
                    Se guardará la <strong>sección completa</strong> con sus fondos, espaciados y <strong>todas sus filas, columnas y elementos</strong> internos para que puedas reutilizarla intacta en cualquier página.
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-zinc-700 dark:text-zinc-300">
                    {{ esSeccion ? 'Nombre de la Sección' : 'Nombre del Preset / Elemento' }} <span class="text-red-500">*</span>
                </label>
                <InputText
                    v-model="nombre"
                    :placeholder="esSeccion ? 'ej. Sección Servicios con Formulario' : 'ej. Grid 3 Columnas Servicios'"
                    class="w-full text-xs font-medium"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-zinc-700 dark:text-zinc-300">
                    Categoría de la Biblioteca
                </label>
                <Select
                    v-model="categoria"
                    :options="categoriasOpciones"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1 text-zinc-700 dark:text-zinc-300">
                    Descripción / Notas (Opcional)
                </label>
                <Textarea
                    v-model="descripcion"
                    rows="3"
                    :placeholder="esSeccion ? 'Notas sobre el diseño de esta sección o para qué páginas está pensada...' : 'Breve nota sobre la maquetación o uso de este preset...'"
                    class="w-full text-xs"
                />
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                <Button
                    label="Cancelar"
                    severity="secondary"
                    variant="outlined"
                    size="small"
                    @click="emit('update:visible', false)"
                />
                <Button
                    :label="esSeccion ? 'Guardar Sección en Biblioteca' : 'Guardar en Biblioteca'"
                    icon="pi pi-bookmark"
                    size="small"
                    severity="success"
                    :loading="guardando"
                    @click="guardarEnBiblioteca"
                />
            </div>
        </div>
    </Dialog>
</template>
