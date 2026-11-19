<script setup>
import { ref, computed, watch } from 'vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputNumber from 'primevue/inputnumber';
import Dialog from 'primevue/dialog';

const props = defineProps({
    campo: {
        type: Object,
        default: null,
    },
    todosLosCampos: {
        type: Array,
        default: () => [],
    },
});

// Modal de importación de opciones en bloque
const modalOpcionesEnBloque = ref(false);
const textoOpcionesEnBloque = ref('');

// Otros campos disponibles para definir reglas condicionales
const otrosCamposParaCondicional = computed(() => {
    if (!props.campo) return [];
    return props.todosLosCampos
        .filter(f => f.name && f.id !== props.campo.id && !['heading', 'paragraph', 'separator'].includes(f.type))
        .map(f => ({ label: `${f.label || f.name} (${f.name})`, value: f.name }));
});

// Opciones estáticas para selectores
const opcionesAncho = [
    { label: 'Ancho Completo (100%)', value: 'full' },
    { label: 'Medio Ancho (50%)', value: 'half' },
    { label: 'Un Tercio (33%)', value: 'third' },
];

const opcionesOperadorCondicional = [
    { label: 'Es igual a', value: 'equals' },
    { label: 'No es igual a', value: 'not_equals' },
    { label: 'Contiene', value: 'contains' },
    { label: 'Está vacío', value: 'empty' },
    { label: 'No está vacío', value: 'not_empty' },
];

const opcionesCondicionalFieldDisponibles = computed(() => [
    { label: 'Sin condición (Siempre visible)', value: 'none' },
    ...otrosCamposParaCondicional.value,
]);

const opcionesLinkTarget = [
    { label: 'Ventana modal emergente (Recomendado)', value: 'modal' },
    { label: 'Nueva pestaña del navegador (_blank)', value: 'blank' },
];

// ─── Regla PrimeVue 4: Modelos Computados Desambiguados ──────────────────────────────
const modeloCampoWidth = computed({
    get: () => props.campo?.width || 'full',
    set: (val) => {
        if (props.campo) {
            props.campo.width = val || 'full';
        }
    },
});

const modeloCampoLinkTarget = computed({
    get: () => props.campo?.link_target || 'modal',
    set: (val) => {
        if (props.campo) {
            props.campo.link_target = val || 'modal';
        }
    },
});

// Limpieza automática si se pega código HTML en la etiqueta
watch(() => props.campo?.label, (nuevoTexto) => {
    if (!props.campo || typeof nuevoTexto !== 'string') return;
    const match = nuevoTexto.match(/<a\s+[^>]*href=["']([^"']*)["'][^>]*>(.*?)<\/a>/i);
    if (match) {
        const urlExtraida = match[1];
        const textoLimpio = match[2].replace(/<[^>]*>/g, '').trim();
        if (!props.campo.link_url || props.campo.link_url.trim() === '') {
            props.campo.link_url = urlExtraida;
            props.campo.link_target = props.campo.link_target || 'modal';
        }
        props.campo.label = textoLimpio;
    }
}, { immediate: true });

const modeloCampoCondicionalField = computed({
    get: () => props.campo?.conditional?.field || 'none',
    set: (val) => {
        if (!props.campo) return;
        if (!props.campo.conditional) {
            props.campo.conditional = { field: null, operator: 'equals', value: '' };
        }
        props.campo.conditional.field = (val === 'none' || !val) ? null : val;
    },
});

const modeloCampoCondicionalOperator = computed({
    get: () => props.campo?.conditional?.operator || 'equals',
    set: (val) => {
        if (!props.campo) return;
        if (!props.campo.conditional) {
            props.campo.conditional = { field: null, operator: 'equals', value: '' };
        }
        props.campo.conditional.operator = val || 'equals';
    },
});

// Manipulación de opciones
function agregarOpcion() {
    if (!props.campo) return;
    if (!Array.isArray(props.campo.options)) {
        props.campo.options = [];
    }
    const num = props.campo.options.length + 1;
    props.campo.options.push({
        label: `Opción ${num}`,
        value: `opcion_${num}`,
    });
}

function eliminarOpcion(idx) {
    if (!props.campo?.options) return;
    props.campo.options.splice(idx, 1);
}

function abrirImportadorOpciones() {
    textoOpcionesEnBloque.value = (props.campo?.options || [])
        .map(o => `${o.label || ''}|${o.value || ''}`)
        .join('\n');
    modalOpcionesEnBloque.value = true;
}

function procesarImportacionEnBloque() {
    if (!props.campo) return;
    const lineas = textoOpcionesEnBloque.value
        .split('\n')
        .map(l => l.trim())
        .filter(l => l.length > 0);

    props.campo.options = lineas.map(linea => {
        const partes = linea.split('|');
        const label = partes[0].trim();
        const value = partes[1] ? partes[1].trim() : label.toLowerCase().replace(/[^a-z0-9_]+/g, '_');
        return { label, value };
    });

    modalOpcionesEnBloque.value = false;
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="campo" class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-2">
                    <i class="pi pi-sliders-h text-emerald-600 dark:text-emerald-400" />
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                        Configurar Campo
                    </h3>
                </div>
                <Tag :value="campo.type" severity="emerald" />
            </div>

            <!-- Campos decorativos (heading, paragraph, separator) -->
            <template v-if="['heading', 'paragraph', 'separator'].includes(campo.type)">
                <div v-if="campo.type === 'heading'" class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Texto del Título</label>
                    <InputText v-model="campo.content" class="w-full text-xs" />
                </div>

                <div v-if="campo.type === 'paragraph'" class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Contenido del Párrafo</label>
                    <Textarea v-model="campo.content" rows="3" class="w-full text-xs" />
                </div>

                <div v-if="campo.type === 'separator'" class="text-xs text-zinc-500">
                    Este separador renderiza una línea divisoria horizontal entre secciones de campos.
                </div>
            </template>

            <!-- Campos de entrada y selección -->
            <template v-else>
                <!-- Etiqueta visible -->
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Etiqueta del campo (Label)
                    </label>
                    <InputText v-model="campo.label" class="w-full text-xs" />
                </div>

                <!-- Identificador interno (name) -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Identificador interno (name)
                        </label>
                        <span class="text-[10px] text-zinc-400 font-mono">minúsculas y _</span>
                    </div>
                    <InputText
                        v-model="campo.name"
                        class="w-full text-xs font-mono"
                        placeholder="ej: telefono_contacto"
                    />
                </div>

                <!-- Placeholder -->
                <div v-if="!['checkbox', 'toggle', 'rating', 'file', 'color', 'hidden'].includes(campo.type)" class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Texto de ayuda (Placeholder)</label>
                    <InputText v-model="campo.placeholder" class="w-full text-xs" />
                </div>

                <!-- Obligatorio y Ancho -->
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div class="flex flex-col justify-center gap-1.5 p-2 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">¿Obligatorio?</label>
                        <div class="flex items-center gap-2">
                            <ToggleSwitch v-model="campo.required" />
                            <span class="text-xs text-zinc-500">{{ campo.required ? 'Sí' : 'No' }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Ancho en Rejilla</label>
                        <Select
                            v-model="modeloCampoWidth"
                            :options="opcionesAncho"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Enlace en la etiqueta (ideal para política de privacidad / términos) -->
                <div v-if="['checkbox', 'toggle'].includes(campo.type)" class="p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200/70 dark:border-emerald-800/50 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                            <i class="pi pi-link text-emerald-600 dark:text-emerald-400" />
                            Enlace en la etiqueta (Legal / Términos)
                        </label>
                        <span class="text-[10px] text-zinc-400">Opcional</span>
                    </div>

                    <!-- URL del enlace -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-[11px] font-medium text-zinc-700 dark:text-zinc-300">
                                URL o ruta de la página
                            </label>
                            <span class="text-[10px] text-zinc-400 font-mono">ej: /politica-de-privacidad</span>
                        </div>
                        <InputText
                            v-model="campo.link_url"
                            class="w-full text-xs font-mono"
                            placeholder="/politica-de-privacidad"
                        />
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400">
                            Al indicar una URL, todo el texto del label se convertirá en un enlace interactivo.
                        </p>
                    </div>

                    <!-- Modo de apertura: Modal emergente vs Nueva pestaña -->
                    <div v-if="campo.link_url && campo.link_url.trim() !== ''" class="space-y-1">
                        <label class="text-[11px] font-medium text-zinc-700 dark:text-zinc-300">
                            Modo de apertura del enlace
                        </label>
                        <Select
                            v-model="modeloCampoLinkTarget"
                            :options="opcionesLinkTarget"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Editor de opciones para select / radio / multiselect / checkbox -->
                <div v-if="['select', 'multiselect', 'radio', 'checkbox'].includes(campo.type)" class="space-y-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Opciones de Selección</label>
                        <div class="flex items-center gap-1">
                            <Button
                                label="Importar lista"
                                icon="pi pi-list"
                                size="small"
                                text
                                class="!text-xs !py-0.5"
                                @click="abrirImportadorOpciones"
                            />
                            <Button
                                label="Añadir"
                                icon="pi pi-plus"
                                size="small"
                                text
                                class="!text-xs !py-0.5"
                                @click="agregarOpcion"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                        <div
                            v-for="(opc, oIdx) in campo.options"
                            :key="oIdx"
                            class="flex items-center gap-1.5"
                        >
                            <InputText
                                v-model="opc.label"
                                placeholder="Etiqueta"
                                class="flex-1 text-xs"
                            />
                            <InputText
                                v-model="opc.value"
                                placeholder="Valor"
                                class="flex-1 text-xs font-mono"
                            />
                            <Button
                                icon="pi pi-times"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="eliminarOpcion(oIdx)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Opciones específicas de archivo -->
                <div v-if="campo.type === 'file'" class="space-y-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Tamaño máximo (KB)</label>
                    <InputNumber v-model="campo.max_file_size" class="w-full text-xs" :min="100" :max="51200" />
                </div>

                <!-- Valor por defecto -->
                <div v-if="!['file'].includes(campo.type)" class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Valor por defecto</label>
                    <InputText v-model="campo.default_value" class="w-full text-xs" />
                </div>

                <!-- Lógica Condicional Dinámica -->
                <div class="space-y-2 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1">
                            <i class="pi pi-bolt text-amber-500" />
                            <span>Lógica Condicional</span>
                        </label>
                    </div>
                    <p class="text-[11px] text-zinc-400">
                        Muestra u oculta este campo según la respuesta de otro campo.
                    </p>

                    <div class="space-y-2 p-2.5 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                        <div class="space-y-1">
                            <span class="text-[11px] text-zinc-500">Depende de:</span>
                            <Select
                                v-model="modeloCampoCondicionalField"
                                :options="opcionesCondicionalFieldDisponibles"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>

                        <template v-if="modeloCampoCondicionalField !== 'none'">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <span class="text-[11px] text-zinc-500">Condición:</span>
                                    <Select
                                        v-model="modeloCampoCondicionalOperator"
                                        :options="opcionesOperadorCondicional"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full text-xs"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[11px] text-zinc-500">Valor:</span>
                                    <InputText
                                        v-model="campo.conditional.value"
                                        placeholder="ej: si, particular"
                                        class="w-full text-xs"
                                    />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div v-else class="p-6 text-center text-zinc-400 text-xs border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
            Selecciona un campo de la lista para editar sus propiedades.
        </div>

        <!-- Modal de Importación de Opciones en Bloque -->
        <Dialog
            v-model:visible="modalOpcionesEnBloque"
            modal
            header="Importar Opciones en Bloque"
            :style="{ width: '90vw', maxWidth: '480px' }"
        >
            <div class="space-y-3 text-xs">
                <p class="text-zinc-500">
                    Pega una lista de opciones separadas por salto de línea. Puedes especificar etiqueta y valor separándolos con una barra vertical (|), o escribir solo el texto:
                </p>
                <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded font-mono text-[11px] text-zinc-600 dark:text-zinc-300">
                    Particular | particular<br />
                    Empresa | empresa<br />
                    Autónomo | autonomo
                </div>
                <Textarea
                    v-model="textoOpcionesEnBloque"
                    rows="6"
                    placeholder="Etiqueta | valor&#10;Otra opción | otra_opcion"
                    class="w-full font-mono text-xs"
                />
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" text @click="modalOpcionesEnBloque = false" />
                <Button label="Importar Opciones" icon="pi pi-check" @click="procesarImportacionEnBloque" />
            </template>
        </Dialog>
    </div>
</template>
