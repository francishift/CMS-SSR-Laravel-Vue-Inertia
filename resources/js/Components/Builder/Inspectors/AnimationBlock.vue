<script setup>
/**
 * Componente de Configuración de Animación.
 *
 * Permite seleccionar tipo de animación (Desvanecer, Escala, Deslizamiento corto/mediano/100%),
 * modo de aplicación (En Cascada vs En Bloque) y retrasos (base y escalonado entre tarjetas/columnas).
 */
import { computed, watch, nextTick } from 'vue';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';

const props = defineProps({
    target: { type: Object, default: null },
    elemento: { type: Object, default: null },
    allowCascade: { type: Boolean, default: false },
});

const effectiveTarget = computed(() => {
    if (props.target) return props.target;
    if (props.elemento) {
        if (props.elemento.content && typeof props.elemento.content === 'object') {
            return props.elemento.content;
        }
        return props.elemento;
    }
    return {};
});

const OPCIONES_ANIMACION = [
    { label: 'Ninguno', value: 'none' },
    { label: 'Desvanecer', value: 'fade' },
    { label: 'Aumentar proporcionalmente', value: 'scale_up' },
    { label: 'Reducir proporcionalmente', value: 'scale_down' },
    { label: 'Deslizar Arriba Corto', value: 'slide_top_small' },
    { label: 'Deslizar Abajo Corto', value: 'slide_bottom_small' },
    { label: 'Deslizar Izquierda Corto', value: 'slide_left_small' },
    { label: 'Deslizar Derecha Corto', value: 'slide_right_small' },
    { label: 'Deslizar Arriba Mediano', value: 'slide_top_medium' },
    { label: 'Deslizar Abajo Mediano', value: 'slide_bottom_medium' },
    { label: 'Deslizar Izquierda Mediano', value: 'slide_left_medium' },
    { label: 'Deslizar Derecha Mediano', value: 'slide_right_medium' },
    { label: 'Deslizar Arriba 100%', value: 'slide_top_100' },
    { label: 'Deslizar Abajo 100%', value: 'slide_bottom_100' },
    { label: 'Deslizar Izquierda 100%', value: 'slide_left_100' },
    { label: 'Deslizar Derecha 100%', value: 'slide_right_100' },
];

const OPCIONES_MODO = [
    { label: 'En Cascada (Escalonado por ítem)', value: 'cascade' },
    { label: 'En Bloque (Todo el conjunto a la vez)', value: 'block' },
];

const animacionModel = computed({
    get() {
        const val = effectiveTarget.value?.animation;
        if (!val || val === '') return 'none';
        return val;
    },
    set(val) {
        if (effectiveTarget.value) {
            effectiveTarget.value.animation = val === 'none' ? 'none' : val;
        }
    }
});

const animModeModel = computed({
    get() {
        return effectiveTarget.value?.animation_mode || 'cascade';
    },
    set(val) {
        if (effectiveTarget.value) {
            effectiveTarget.value.animation_mode = val || 'cascade';
        }
    }
});

const delayModel = computed({
    get() {
        const val = effectiveTarget.value?.animation_delay;
        if (val === undefined || val === null || val === '') return 0;
        return Number(val);
    },
    set(val) {
        if (effectiveTarget.value) {
            effectiveTarget.value.animation_delay = val !== null ? Number(val) : 0;
        }
    }
});

const staggerModel = computed({
    get() {
        const val = effectiveTarget.value?.animation_stagger;
        if (val === undefined || val === null || val === '') return 100;
        return Number(val);
    },
    set(val) {
        if (effectiveTarget.value) {
            effectiveTarget.value.animation_stagger = val !== null ? Number(val) : 100;
        }
    }
});

let debounceTimer = null;

function probarAnimacion() {
    if (typeof window !== 'undefined') {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const targetId = props.elemento?.id || props.target?.id;
            window.dispatchEvent(new CustomEvent('cms-replay-animation', {
                detail: {
                    target: effectiveTarget.value,
                    targetId: targetId,
                    animation: animacionModel.value,
                    mode: animModeModel.value,
                    delay: delayModel.value,
                    stagger: staggerModel.value,
                }
            }));
        }, 50);
    }
}

watch([
    () => effectiveTarget.value?.animation,
    () => effectiveTarget.value?.animation_mode,
    () => effectiveTarget.value?.animation_delay,
    () => effectiveTarget.value?.animation_stagger,
], ([newAnim], [oldAnim]) => {
    if (newAnim && newAnim !== 'none') {
        probarAnimacion();
    }
});
</script>

<template>
    <div class="p-3.5 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3.5 mt-4">
        <div class="flex items-center justify-between text-xs font-bold text-zinc-900 dark:text-zinc-100">
            <div class="flex items-center gap-2">
                <i class="pi pi-sparkles text-emerald-600 dark:text-emerald-400" />
                <span>Animación al Visualizar</span>
            </div>
            <button
                v-if="animacionModel !== 'none'"
                type="button"
                @click="probarAnimacion"
                class="px-2 py-1 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/80 rounded-md text-[11px] font-semibold flex items-center gap-1 transition-all cursor-pointer shadow-2xs active:scale-95"
                title="Probar animación en la vista previa"
            >
                <i class="pi pi-play text-[10px]" />
                <span>Probar</span>
            </button>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Animación
            </label>
            <Select
                v-model="animacionModel"
                :options="OPCIONES_ANIMACION"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
                @change="probarAnimacion"
            />
            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 leading-tight">
                Efecto de transición que se activa automáticamente al entrar en la pantalla.
            </p>
        </div>

        <!-- Modo de Animación (Cascada vs Bloque) si el contenedor tiene múltiples elementos o tarjetas -->
        <div v-if="animacionModel !== 'none' && allowCascade">
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Modo de Animación
            </label>
            <Select
                v-model="animModeModel"
                :options="OPCIONES_MODO"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
                @change="probarAnimacion"
            />
            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 leading-tight">
                <strong>En Cascada:</strong> cada tarjeta o columna aparece sucesivamente sumando retardos. <strong>En Bloque:</strong> todo el contenedor entra a la vez.
            </p>
        </div>

        <div v-if="animacionModel !== 'none'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1 uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Retardo Base Inicial
                </label>
                <InputNumber
                    v-model="delayModel"
                    :min="0"
                    :max="3000"
                    :step="50"
                    suffix=" ms"
                    placeholder="0"
                    class="w-full text-xs"
                    @update:modelValue="probarAnimacion"
                />
                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 leading-tight">
                    Espera inicial antes de comenzar.
                </p>
            </div>

            <div v-if="allowCascade && animModeModel === 'cascade'">
                <label class="block text-xs font-semibold mb-1 uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Paso Cascada entre Ítems
                </label>
                <InputNumber
                    v-model="staggerModel"
                    :min="30"
                    :max="1000"
                    :step="25"
                    suffix=" ms"
                    placeholder="100"
                    class="w-full text-xs"
                    @update:modelValue="probarAnimacion"
                />
                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 leading-tight">
                    Tiempo adicional sumado por cada tarjeta sucesiva.
                </p>
            </div>
        </div>
    </div>
</template>
