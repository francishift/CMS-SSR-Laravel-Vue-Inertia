<script setup>
/**
 * Componente FuentesSection: Fuentes instaladas localmente en el servidor (encabezados, cuerpo, niveles H1-H6 y vista previa interactiva).
 */
import { computed } from 'vue';
import Select from 'primevue/select';

const props = defineProps({
    form: { type: Object, required: true },
    fuentesDisponibles: { type: Array, default: () => [] },
});

// Opciones para los selectores de tipografía
const opcionesTamaño = [
    { label: '0.625rem (10px)', value: '0.625rem' },
    { label: '0.75rem (12px)',  value: '0.75rem' },
    { label: '0.875rem (14px)', value: '0.875rem' },
    { label: '1rem (16px)',     value: '1rem' },
    { label: '1.125rem (18px)', value: '1.125rem' },
    { label: '1.25rem (20px)',  value: '1.25rem' },
    { label: '1.5rem (24px)',   value: '1.5rem' },
    { label: '1.875rem (30px)', value: '1.875rem' },
    { label: '2.25rem (36px)',  value: '2.25rem' },
    { label: '3rem (48px)',     value: '3rem' },
    { label: '3.75rem (60px)',  value: '3.75rem' },
    { label: '4.5rem (72px)',   value: '4.5rem' },
    { label: '6rem (96px)',     value: '6rem' },
];

const opcionesFontWeight = [
    { label: '300 — Fino (Light)',             value: '300' },
    { label: '400 — Normal (Regular)',          value: '400' },
    { label: '500 — Medio (Medium)',           value: '500' },
    { label: '600 — Seminegrita (SemiBold)',    value: '600' },
    { label: '700 — Negrita (Bold)',           value: '700' },
    { label: '800 — Extra Negrita (ExtraBold)', value: '800' },
    { label: '900 — Ultra Negrita (Black)',     value: '900' },
];

const opcionesLetterSpacing = [
    { label: 'Muy compacto (-0.05em)',  value: '-0.05em' },
    { label: 'Compacto (-0.025em)',     value: '-0.025em' },
    { label: 'Ligeramente compacto (-0.02em)', value: '-0.02em' },
    { label: 'Normal (0)',              value: '0em' },
    { label: 'Ligeramente abierto (0.025em)', value: '0.025em' },
    { label: 'Abierto (0.05em)',        value: '0.05em' },
    { label: 'Muy abierto (0.1em)',     value: '0.1em' },
];

const opcionesLineHeight = [
    { label: 'Muy apretado (1)',    value: '1' },
    { label: 'Apretado (1.1)',      value: '1.1' },
    { label: 'Ajustado (1.2)',      value: '1.2' },
    { label: 'Compacto (1.25)',     value: '1.25' },
    { label: 'Semi-compacto (1.35)', value: '1.35' },
    { label: 'Normal (1.4)',        value: '1.4' },
    { label: 'Cómodo (1.5)',        value: '1.5' },
    { label: 'Relajado (1.625)',    value: '1.625' },
    { label: 'Holgado (1.75)',      value: '1.75' },
    { label: 'Espacioso (2)',       value: '2' },
];

// Opciones de tamaño específicas para el body (rango adecuado para texto de párrafo)
const opcionesTamañoBody = [
    { label: '0.75rem (12px)',  value: '0.75rem' },
    { label: '0.8125rem (13px)', value: '0.8125rem' },
    { label: '0.875rem (14px)', value: '0.875rem' },
    { label: '0.9375rem (15px)', value: '0.9375rem' },
    { label: '1rem (16px)',     value: '1rem' },
    { label: '1.0625rem (17px)', value: '1.0625rem' },
    { label: '1.125rem (18px)', value: '1.125rem' },
    { label: '1.1875rem (19px)', value: '1.1875rem' },
    { label: '1.25rem (20px)',  value: '1.25rem' },
    { label: '1.375rem (22px)', value: '1.375rem' },
    { label: '1.5rem (24px)',   value: '1.5rem' },
];

const nivelesEncabezados = [
    { key: 'hero_2xl', label: 'Hero 2XL (8XL Gigante / ~96px)', sub: 'Portada Gigante / Especial' },
    { key: 'hero_xl',  label: 'Hero XL (6XL Portada / ~72px)',  sub: 'Portada Estándar' },
    { key: 'h1',       label: 'H1 Grande (5XL / ~48px)',        sub: 'Título Principal' },
    { key: 'h2',       label: 'H2 Mediano (4XL / ~36px)',       sub: 'Subtítulo / Sección' },
    { key: 'h3',       label: 'H3 Estándar (3XL / ~30px)',      sub: 'Sección Estándar' },
    { key: 'h4',       label: 'H4 Pequeño (XL / ~20px)',        sub: 'Subsección / Tarjeta' },
    { key: 'h5',       label: 'H5 Mini (LG / ~18px)',           sub: 'Detalle Menor' },
    { key: 'h6',       label: 'H6 Subtítulo (SM / ~14px)',      sub: 'Subtítulo Meta' },
];

function asegurarNivel(level) {
    if (!props.form.heading_styles[level]) {
        props.form.heading_styles[level] = { size: '', font_weight: '', letter_spacing: '', line_height: '' };
    }
    return props.form.heading_styles[level];
}

const familiaHeadingSeleccionada = computed(() => {
    const f = props.fuentesDisponibles.find(x => x.nombre === props.form.heading_font);
    return f?.familia || `'${props.form.heading_font}', system-ui, sans-serif`;
});

const familiaBodySeleccionada = computed(() => {
    const f = props.fuentesDisponibles.find(x => x.nombre === props.form.body_font);
    return f?.familia || `'${props.form.body_font}', system-ui, sans-serif`;
});

const obtenerEstiloHeading = (key) => {
    const styleObj = props.form.heading_styles?.[key] || {};
    const defs = {
        hero_2xl: { size: '4.5rem',  font_weight: '800', letter_spacing: '-0.03em',  line_height: '1.05' },
        hero_xl:  { size: '3.75rem', font_weight: '800', letter_spacing: '-0.025em', line_height: '1.1' },
        h1:       { size: '3rem',    font_weight: '800', letter_spacing: '-0.025em', line_height: '1.1' },
        h2:       { size: '2.25rem', font_weight: '700', letter_spacing: '-0.02em',  line_height: '1.2' },
        h3:       { size: '1.875rem',font_weight: '600', letter_spacing: '0em',      line_height: '1.25' },
        h4:       { size: '1.25rem', font_weight: '600', letter_spacing: '0em',      line_height: '1.35' },
        h5:       { size: '1rem',    font_weight: '500', letter_spacing: '0.025em',  line_height: '1.4' },
        h6:       { size: '0.75rem', font_weight: '500', letter_spacing: '0.05em',   line_height: '1.5' },
    };
    const def = defs[key] || {};
    let sz = (styleObj.size || def.size || '').trim();
    if (sz && /^\d+(\.\d+)?$/.test(sz)) {
        sz += 'rem';
    }
    return {
        fontFamily: familiaHeadingSeleccionada.value,
        fontSize: sz || def.size,
        fontWeight: styleObj.font_weight || def.font_weight,
        letterSpacing: styleObj.letter_spacing || def.letter_spacing,
        lineHeight: styleObj.line_height || def.line_height,
    };
};
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                <i class="pi pi-align-left" />
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Tipografía</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Fuentes instaladas localmente en el servidor (cero peticiones externas bloqueantes a Google Fonts)</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Fuente de Encabezados (Headings)</label>
                <Select
                    v-model="form.heading_font"
                    :options="fuentesDisponibles"
                    optionLabel="nombre"
                    optionValue="nombre"
                    class="w-full text-xs"
                />
                <p class="text-[11px] text-zinc-500 mt-1">Se aplica a títulos H1-H6, tarjetas y antetítulos.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Fuente del Cuerpo (Body / Texto)</label>
                <Select
                    v-model="form.body_font"
                    :options="fuentesDisponibles"
                    optionLabel="nombre"
                    optionValue="nombre"
                    class="w-full text-xs"
                />
                <p class="text-[11px] text-zinc-500 mt-1">Se aplica al texto general, párrafos, listas y artículos.</p>
            </div>
        </div>

        <!-- Configuración Tipográfica del Cuerpo (Body) -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Estilo del Texto Base (Body)</label>
                <span class="text-[10px] font-mono bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 px-2 py-0.5 rounded-full font-bold">Párrafos y texto general</span>
            </div>
            <p class="text-[11px] text-zinc-500 -mt-1">Configure tamaño, grosor, interlineado y espaciado de letras para el texto base de la web.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Tamaño</label>
                    <Select
                        v-model="form.body_font_size"
                        :options="opcionesTamañoBody"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Por defecto (16px)"
                        showClear
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Grosor / Peso</label>
                    <Select
                        v-model="form.body_font_weight"
                        :options="opcionesFontWeight"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Por defecto (Regular)"
                        showClear
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Interlineado</label>
                    <Select
                        v-model="form.body_line_height"
                        :options="opcionesLineHeight"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Por defecto (1.625)"
                        showClear
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Espaciado de Letras</label>
                    <Select
                        v-model="form.body_letter_spacing"
                        :options="opcionesLetterSpacing"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Por defecto (Normal)"
                        showClear
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>
        <!-- Configuración Tipográfica por Nivel de Encabezado (Hero 2XL a H6) -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Estilos y Tamaños por Defecto de Encabezados</label>
                <span class="text-[10px] font-mono bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 px-2 py-0.5 rounded-full font-bold">Sobreescribible por elemento</span>
            </div>
            <p class="text-[11px] text-zinc-500 -mt-1">Configure tamaño, grosor (peso de fuente), espaciado de letras e interlineado para todos los niveles visuales.</p>

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="w-full text-xs">
                    <thead class="bg-zinc-100 dark:bg-zinc-800/60">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-bold text-zinc-600 dark:text-zinc-400 w-44">Nivel / Escala</th>
                            <th class="px-3 py-2.5 text-left font-bold text-zinc-600 dark:text-zinc-400">Tamaño</th>
                            <th class="px-3 py-2.5 text-left font-bold text-zinc-600 dark:text-zinc-400">Grosor / Peso</th>
                            <th class="px-3 py-2.5 text-left font-bold text-zinc-600 dark:text-zinc-400">Letter Spacing</th>
                            <th class="px-3 py-2.5 text-left font-bold text-zinc-600 dark:text-zinc-400">Line Height</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        <tr v-for="item in nivelesEncabezados" :key="item.key" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-3 py-2">
                                <div class="font-bold text-zinc-800 dark:text-zinc-200">{{ item.label }}</div>
                                <div class="text-[10px] text-zinc-400 font-normal">{{ item.sub }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <Select
                                    :modelValue="asegurarNivel(item.key).size"
                                    @update:modelValue="(v) => form.heading_styles[item.key].size = v"
                                    :options="opcionesTamaño"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </td>
                            <td class="px-3 py-2">
                                <Select
                                    :modelValue="asegurarNivel(item.key).font_weight"
                                    @update:modelValue="(v) => form.heading_styles[item.key].font_weight = v"
                                    :options="opcionesFontWeight"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </td>
                            <td class="px-3 py-2">
                                <Select
                                    :modelValue="asegurarNivel(item.key).letter_spacing"
                                    @update:modelValue="(v) => form.heading_styles[item.key].letter_spacing = v"
                                    :options="opcionesLetterSpacing"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </td>
                            <td class="px-3 py-2">
                                <Select
                                    :modelValue="asegurarNivel(item.key).line_height"
                                    @update:modelValue="(v) => form.heading_styles[item.key].line_height = v"
                                    :options="opcionesLineHeight"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-xs"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Previsualización Tipográfica Interactiva en Vivo -->
        <div class="p-5 bg-zinc-50 dark:bg-zinc-950/60 rounded-2xl border border-zinc-200 dark:border-zinc-800 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-2">
                <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Vista Previa Tipográfica en Tiempo Real</span>
                <span class="text-[10px] font-mono bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2 py-0.5 rounded-full font-bold">100% Carga Local SEO</span>
            </div>

            <div class="space-y-4 pt-2">
                <div v-for="item in nivelesEncabezados" :key="item.key" class="p-4 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200/80 dark:border-zinc-800 space-y-1.5 transition-all">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono text-zinc-500 border-b border-zinc-100 dark:border-zinc-800/60 pb-1.5">
                        <span class="font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ item.label }} — {{ item.sub }}</span>
                        <div class="flex flex-wrap items-center gap-3">
                            <span>Tam: <strong class="text-zinc-700 dark:text-zinc-300">{{ obtenerEstiloHeading(item.key).fontSize }}</strong></span>
                            <span>Peso: <strong class="text-zinc-700 dark:text-zinc-300">{{ obtenerEstiloHeading(item.key).fontWeight }}</strong></span>
                            <span>Espaciado: <strong class="text-zinc-700 dark:text-zinc-300">{{ obtenerEstiloHeading(item.key).letterSpacing }}</strong></span>
                            <span>Interlínea: <strong class="text-zinc-700 dark:text-zinc-300">{{ obtenerEstiloHeading(item.key).lineHeight }}</strong></span>
                        </div>
                    </div>
                    <component
                        :is="['hero_2xl','hero_xl','h1'].includes(item.key) ? 'h1' : item.key"
                        class="text-zinc-900 dark:text-zinc-100 transition-all duration-150"
                        :style="obtenerEstiloHeading(item.key)"
                    >
                        {{ item.label }} — {{ item.sub }}
                    </component>
                </div>
                <div class="p-4 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200/80 dark:border-zinc-800 space-y-1.5">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono text-zinc-500 border-b border-zinc-100 dark:border-zinc-800/60 pb-1.5">
                        <span class="font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Cuerpo de Texto (Body) — Párrafos y texto general</span>
                        <div class="flex flex-wrap items-center gap-3">
                            <span>Tam: <strong class="text-zinc-700 dark:text-zinc-300">{{ form.body_font_size || '1rem' }}</strong></span>
                            <span>Peso: <strong class="text-zinc-700 dark:text-zinc-300">{{ form.body_font_weight || '400' }}</strong></span>
                            <span>Espaciado: <strong class="text-zinc-700 dark:text-zinc-300">{{ form.body_letter_spacing || '0em' }}</strong></span>
                            <span>Interlínea: <strong class="text-zinc-700 dark:text-zinc-300">{{ form.body_line_height || '1.625' }}</strong></span>
                        </div>
                    </div>
                    <p class="text-zinc-600 dark:text-zinc-300 max-w-3xl transition-all duration-150" :style="{ fontFamily: familiaBodySeleccionada, fontSize: form.body_font_size || '1rem', fontWeight: form.body_font_weight || '400', lineHeight: form.body_line_height || '1.625', letterSpacing: form.body_letter_spacing || '0em' }">
                        Las fuentes tipográficas se sirven desde el servidor web sin depender de servicios de terceros. Velocidad máxima en Lighthouse y cumplimiento estricto de privacidad RGPD.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
