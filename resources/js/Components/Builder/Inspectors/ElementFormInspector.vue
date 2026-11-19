<script setup>
/**
 * Inspector Atómico para el Elemento Formulario del Page Builder.
 *
 * Permite al administrador seleccionar un formulario dinámico por slug,
 * configurar estilo visual (tema, bordes, sombras), alineación del botón
 * y opciones de personalización avanzada (ID, clases CSS, CSS inline).
 */
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import AdvancedCustomizationBlock from './AdvancedCustomizationBlock.vue';
import AnimationBlock from './AnimationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

// Lista de formularios disponibles (cargados vía API ligera)
const formulariosDisponibles = ref([]);
const cargando = ref(false);

// Cargar lista de formularios activos desde el backend
async function cargarFormularios() {
    cargando.value = true;
    try {
        const { data } = await axios.get(route('panel.formularios.index'), {
            headers: { 'X-Inertia': false, Accept: 'application/json' },
            params: { _builder_list: 1 },
        });
        formulariosDisponibles.value = data || [];
    } catch {
        formulariosDisponibles.value = [];
    } finally {
        cargando.value = false;
    }
}

onMounted(cargarFormularios);

const content = computed(() => {
    if (!props.elementoSeleccionado.content) {
        props.elementoSeleccionado.content = {};
    }
    return props.elementoSeleccionado.content;
});

// Inicializar defaults si no existen
if (!content.value.form_slug) content.value.form_slug = '';
if (!content.value.theme) content.value.theme = 'default';
if (!content.value.border_radius) content.value.border_radius = 'lg';
if (!content.value.shadow) content.value.shadow = 'sm';
if (content.value.show_labels === undefined) content.value.show_labels = true;
if (content.value.compact === undefined) content.value.compact = false;

// Opciones para selects del inspector
const opcionesFormularios = computed(() => [
    { label: '— Seleccionar formulario —', value: '' },
    ...formulariosDisponibles.value.map(f => ({
        label: `${f.name} (${f.slug})`,
        value: f.slug,
    })),
]);

const opcionesTema = [
    { label: 'Por defecto (hereda del sitio)', value: 'default' },
    { label: 'Transparente (sin fondo)', value: 'transparent' },
    { label: 'Formulario con fondo', value: 'card' },
    { label: 'Glassmorphism', value: 'glass' },
];

const opcionesBorderRadius = [
    { label: 'Sin esquinas', value: 'none' },
    { label: 'Suave (sm)', value: 'sm' },
    { label: 'Medio (md)', value: 'md' },
    { label: 'Redondeado (lg)', value: 'lg' },
    { label: 'Muy redondeado (xl)', value: 'xl' },
    { label: 'Cápsula (2xl)', value: '2xl' },
];

const opcionesSombra = [
    { label: 'Sin sombra', value: 'none' },
    { label: 'Sutil (sm)', value: 'sm' },
    { label: 'Media (md)', value: 'md' },
    { label: 'Pronunciada (lg)', value: 'lg' },
    { label: 'Elevación alta (xl)', value: 'xl' },
];
</script>

<template>
    <div v-if="elementoSeleccionado" class="space-y-4">
        <!-- Selección de formulario -->
        <div>
            <label class="block text-xs font-semibold mb-1">Formulario *</label>
            <Select
                v-model="content.form_slug"
                :options="opcionesFormularios"
                optionLabel="label"
                optionValue="value"
                :loading="cargando"
                class="w-full text-xs"
                placeholder="Selecciona un formulario"
            />
            <p v-if="!content.form_slug" class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">
                ⚠️ Selecciona un formulario para que se renderice en la página.
            </p>
            <p v-else class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1">
                ✓ Se renderizará el formulario <strong>{{ content.form_slug }}</strong>
            </p>
        </div>

        <!-- Tema visual -->
        <div>
            <label class="block text-xs font-semibold mb-1">Tema Visual</label>
            <Select
                v-model="content.theme"
                :options="opcionesTema"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            />
        </div>

        <!-- Bordes y sombras -->
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-semibold mb-1">Esquinas</label>
                <Select
                    v-model="content.border_radius"
                    :options="opcionesBorderRadius"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Sombra</label>
                <Select
                    v-model="content.shadow"
                    :options="opcionesSombra"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- Opciones de UX -->
        <div class="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <label class="text-xs font-semibold">Mostrar etiquetas</label>
                <ToggleSwitch v-model="content.show_labels" />
            </div>
            <div class="flex items-center justify-between">
                <label class="text-xs font-semibold">Modo compacto</label>
                <ToggleSwitch v-model="content.compact" />
            </div>
        </div>

        <!-- Animaciones -->
        <AnimationBlock :elementoSeleccionado="elementoSeleccionado" />

        <!-- Personalización Avanzada -->
        <AdvancedCustomizationBlock :elementoSeleccionado="elementoSeleccionado" />
    </div>
</template>
