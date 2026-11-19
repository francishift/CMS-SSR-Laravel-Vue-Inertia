<script setup>
/**
 * Inspector Atómico del Elemento Icono para el Page Builder.
 * Opciones alineadas con el sistema visual unificado de iconos del CMS:
 *  - Selector visual PrimeIcons y variables dinámicas ({article.icon}, {category.icon})
 *  - Estilo de Contenedor (Sin Fondo, Suave Cuadrado, Sólido Cuadrado, Suave Circular, Sólido Circular)
 *  - Tamaños predefinidos (16px a 120px) y Personalizado en px
 *  - Paletas de Color del Icono y de Fondo
 *  - Alineación horizontal, enlace opcional y animaciones al scroll.
 */
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import DynamicTagSelector from '@/Components/Builder/DynamicTagSelector.vue';
import AnimationBlock from './AnimationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

// Pestaña activa dentro del inspector
const subTabActiva = ref('general');

// Inicializar propiedades por defecto de forma segura
function inicializarIconConfig() {
    if (!props.elementoSeleccionado.content) {
        props.elementoSeleccionado.content = {};
    }
    const c = props.elementoSeleccionado.content;
    if (c.icon === undefined) c.icon = 'pi pi-star';
    if (c.text === undefined) c.text = '';
    if (c.text_size === undefined) c.text_size = 'base';
    if (c.text_size_px === undefined) c.text_size_px = 16;
    if (c.text_weight === undefined) c.text_weight = 'semibold';
    if (c.text_color === undefined) c.text_color = 'inherit';
    if (c.custom_text_color === undefined) c.custom_text_color = '';
    if (c.text_gap === undefined) c.text_gap = 'md';
    if (c.url === undefined) c.url = '';
    if (c.target === undefined) c.target = '_self';
    if (c.rel_nofollow === undefined) c.rel_nofollow = false;
    if (c.hover_effect === undefined) c.hover_effect = 'zoom_smooth';
    if (c.icon_size === undefined) c.icon_size = c.size || 'md';
    if (c.icon_size_px === undefined) c.icon_size_px = c.custom_size || 32;
    if (c.icon_style_mode === undefined) c.icon_style_mode = 'none';
    if (c.icon_color === undefined) c.icon_color = 'primary';
    if (c.icon_bg_color === undefined) c.icon_bg_color = 'primary_soft';
    if (c.custom_color === undefined) c.custom_color = '';
    if (c.custom_bg_color === undefined) c.custom_bg_color = '';
    if (c.align === undefined) c.align = 'left';
    if (c.margin_top === undefined) c.margin_top = 'none';
    if (c.margin_bottom === undefined) c.margin_bottom = 'none';
}
inicializarIconConfig();

const opcionesTamano = [
    { label: 'Pequeño (16px)', value: 'xs' },
    { label: 'Normal (24px)', value: 'sm' },
    { label: 'Mediano (32px)', value: 'md' },
    { label: 'Grande (48px)', value: 'lg' },
    { label: 'Extra Grande (64px)', value: 'xl' },
    { label: 'Gigante (80px)', value: '2xl' },
    { label: 'Enorme (96px)', value: '3xl' },
    { label: 'Máximo (120px)', value: '4xl' },
    { label: 'Personalizado (px)', value: 'custom' },
];

const opcionesEstiloContenedor = [
    { label: 'Sin Fondo (Solo Icono)', value: 'none' },
    { label: 'Fondo Suave Cuadrado', value: 'soft_bg' },
    { label: 'Fondo Sólido Cuadrado', value: 'solid_bg' },
    { label: 'Fondo Suave Circular', value: 'circle_soft' },
    { label: 'Fondo Sólido Circular', value: 'circle_solid' },
];

const opcionesColorIcono = [
    { label: 'Primario Corporativo', value: 'primary' },
    { label: 'Oscuro / Negro', value: 'dark' },
    { label: 'Blanco', value: 'light' },
    { label: 'Gris Neutro', value: 'zinc' },
    { label: 'Verde Esmeralda', value: 'emerald' },
    { label: 'Índigo / Púrpura', value: 'indigo' },
    { label: 'Azul', value: 'blue' },
    { label: 'Ámbar / Amarillo', value: 'amber' },
    { label: 'Rosa / Rojo', value: 'rose' },
    { label: 'Color Personalizado HEX', value: 'custom' },
];

const opcionesColorFondo = [
    { label: 'Primario Suave (10%)', value: 'primary_soft' },
    { label: 'Primario Sólido', value: 'primary_solid' },
    { label: 'Gris Suave', value: 'zinc_soft' },
    { label: 'Oscuro Sólido', value: 'zinc_dark' },
    { label: 'Verde Suave', value: 'emerald_soft' },
    { label: 'Verde Sólido', value: 'emerald_solid' },
    { label: 'Índigo Suave', value: 'indigo_soft' },
    { label: 'Azul Suave', value: 'blue_soft' },
    { label: 'Ámbar Suave', value: 'amber_soft' },
    { label: 'Rosa Suave', value: 'rose_soft' },
    { label: 'Color Personalizado HEX', value: 'custom' },
];

const opcionesAlineacion = [
    { label: 'Izquierda', value: 'left', icon: 'pi pi-align-left' },
    { label: 'Centro', value: 'center', icon: 'pi pi-align-center' },
    { label: 'Derecha', value: 'right', icon: 'pi pi-align-right' },
];

const opcionesMargen = [
    { label: 'Ninguno (0px)', value: 'none' },
    { label: 'Pequeño (16px)', value: 'sm' },
    { label: 'Estándar (24px)', value: 'md' },
    { label: 'Grande (32px)', value: 'lg' },
    { label: 'Muy Grande (48px)', value: 'xl' },
    { label: 'Extra Grande (64px)', value: '2xl' },
];

const opcionesTamanoTexto = [
    { label: 'Muy Pequeño (12px)', value: 'xs' },
    { label: 'Pequeño (14px)', value: 'sm' },
    { label: 'Normal / Base (16px)', value: 'base' },
    { label: 'Mediano (18px)', value: 'lg' },
    { label: 'Grande (20px)', value: 'xl' },
    { label: 'Extra Grande (24px)', value: '2xl' },
    { label: 'Personalizado (px)', value: 'custom' },
];

const opcionesPesoTexto = [
    { label: 'Normal', value: 'normal' },
    { label: 'Medio', value: 'medium' },
    { label: 'Seminegrita', value: 'semibold' },
    { label: 'Negrita', value: 'bold' },
];

const opcionesColorTextoElemento = [
    { label: 'Heredar del Icono', value: 'inherit' },
    { label: 'Primario Corporativo', value: 'primary' },
    { label: 'Oscuro / Título', value: 'dark' },
    { label: 'Secundario / Gris', value: 'muted' },
    { label: 'Blanco', value: 'light' },
    { label: 'Color Personalizado HEX', value: 'custom' },
];

const opcionesSeparacionTexto = [
    { label: 'Muy Compacto (6px)', value: 'xs' },
    { label: 'Compacto (8px)', value: 'sm' },
    { label: 'Estándar (12px)', value: 'md' },
    { label: 'Amplio (16px)', value: 'lg' },
    { label: 'Muy Amplio (24px)', value: 'xl' },
];

const opcionesEfectoHover = [
    { label: 'Zoom Progresivo Suave (Recomendado)', value: 'zoom_smooth' },
    { label: 'Zoom Sutil (2%)', value: 'zoom_subtle' },
    { label: 'Zoom Solo en el Icono', value: 'icon_only' },
    { label: 'Elevación Suave (-2px)', value: 'lift' },
    { label: 'Sin Efecto Hover', value: 'none' },
];
</script>

<template>
    <div class="space-y-4">
        <!-- Navegación de Sub-Pestañas -->
        <div class="flex items-center gap-1 border-b border-zinc-200 dark:border-zinc-800 pb-2">
            <button
                type="button"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    subTabActiva === 'general' ? 'bg-emerald-500 text-white shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                ]"
                @click="subTabActiva = 'general'"
            >
                <i class="pi pi-star text-xs" />
                <span>Contenido</span>
            </button>
            <button
                type="button"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    subTabActiva === 'estilo' ? 'bg-emerald-500 text-white shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                ]"
                @click="subTabActiva = 'estilo'"
            >
                <i class="pi pi-palette text-xs" />
                <span>Disposición & Estilo</span>
            </button>
            <button
                type="button"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    subTabActiva === 'avanzado' ? 'bg-emerald-500 text-white shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                ]"
                @click="subTabActiva = 'avanzado'"
            >
                <i class="pi pi-cog text-xs" />
                <span>Avanzado</span>
            </button>
        </div>

        <!-- PESTAÑA 1: CONTENIDO Y ENLACE -->
        <div v-show="subTabActiva === 'general'" class="space-y-4">
            <!-- Selector de Icono Visual con IconPicker y Variables Dinámicas -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Icono Seleccionado
                    </label>
                    <DynamicTagSelector
                        tipo="icon"
                        @insertar="(tag) => elementoSeleccionado.content.icon = tag"
                    />
                </div>
                <IconPicker
                    v-model="elementoSeleccionado.content.icon"
                    placeholder="Seleccionar o escribir icono (ej: pi pi-star)..."
                />
                <p class="text-[11px] text-zinc-400">
                    Puedes elegir un icono de PrimeIcons o insertar la variable dinámica <code class="text-[10px] text-emerald-500 bg-emerald-50 dark:bg-emerald-950 px-1 py-0.5 rounded">{article.icon}</code> o <code class="text-[10px] text-blue-500 bg-blue-50 dark:bg-blue-950 px-1 py-0.5 rounded">{category.icon}</code>.
                </p>
            </div>

            <!-- Texto del Icono (Opcional) -->
            <div class="space-y-1.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Texto junto al Icono (Opcional)
                    </label>
                    <DynamicTagSelector
                        tipo="text"
                        @insertar="(tag) => elementoSeleccionado.content.text = (elementoSeleccionado.content.text || '') + tag"
                    />
                </div>
                <InputText
                    v-model="elementoSeleccionado.content.text"
                    placeholder="ej: Visitar Web o {article.title}"
                    class="w-full text-xs"
                />
                <p class="text-[11px] text-zinc-400">
                    Aparece a la derecha del icono centrado verticalmente. Si se define un enlace, el texto formará parte de él.
                </p>
            </div>

            <!-- Configuración de Estilo del Texto (cuando hay texto definido) -->
            <div v-if="elementoSeleccionado.content.text" class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Tamaño del Texto</label>
                        <Select
                            v-model="elementoSeleccionado.content.text_size"
                            :options="opcionesTamanoTexto"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Grosor / Peso</label>
                        <Select
                            v-model="elementoSeleccionado.content.text_weight"
                            :options="opcionesPesoTexto"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div v-if="elementoSeleccionado.content.text_size === 'custom'" class="space-y-1">
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Tamaño Personalizado (px)</label>
                    <InputNumber
                        v-model="elementoSeleccionado.content.text_size_px"
                        :min="8"
                        :max="96"
                        suffix=" px"
                        class="w-full text-xs"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Color del Texto</label>
                        <Select
                            v-model="elementoSeleccionado.content.text_color"
                            :options="opcionesColorTextoElemento"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Separación con Icono</label>
                        <Select
                            v-model="elementoSeleccionado.content.text_gap"
                            :options="opcionesSeparacionTexto"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <div v-if="elementoSeleccionado.content.text_color === 'custom'" class="space-y-1">
                    <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Color Personalizado HEX</label>
                    <InputText
                        v-model="elementoSeleccionado.content.custom_text_color"
                        placeholder="#10b981 o rgb(...)"
                        class="w-full text-xs font-mono"
                    />
                </div>
            </div>

            <!-- URL de Destino (Enlace Opcional) -->
            <div class="space-y-1.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Enlace / URL de Destino (Opcional)
                    </label>
                    <DynamicTagSelector
                        tipo="link"
                        @insertar="(tag) => elementoSeleccionado.content.url = tag"
                    />
                </div>
                <InputText
                    v-model="elementoSeleccionado.content.url"
                    placeholder="ej: /servicios o https://... o {article.url}"
                    class="w-full text-xs"
                />
                <p class="text-[11px] text-zinc-400">
                    Si se define una URL, el icono se convertirá automáticamente en un enlace interactivo clicable.
                </p>
            </div>

            <!-- Abrir en Nueva Pestaña -->
            <div v-if="elementoSeleccionado.content.url" class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Abrir en Nueva Pestaña</label>
                    <span class="text-[11px] text-zinc-400">Añade target="_blank" al enlace</span>
                </div>
                <ToggleSwitch
                    :modelValue="elementoSeleccionado.content.target === '_blank'"
                    @update:modelValue="(val) => elementoSeleccionado.content.target = val ? '_blank' : '_self'"
                />
            </div>

            <!-- Enlace No-Follow (SEO) -->
            <div v-if="elementoSeleccionado.content.url" class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                <div>
                    <div class="flex items-center gap-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Enlace No-Follow (SEO)</label>
                        <span class="text-[10px] uppercase font-bold text-amber-600 bg-amber-50 dark:bg-amber-950/40 px-1 py-0.2 rounded">rel="nofollow"</span>
                    </div>
                    <span class="text-[11px] text-zinc-400">Evita traspasar autoridad a enlaces externos o comerciales</span>
                </div>
                <ToggleSwitch
                    :modelValue="Boolean(elementoSeleccionado.content.rel_nofollow)"
                    @update:modelValue="(val) => elementoSeleccionado.content.rel_nofollow = val"
                />
            </div>

            <!-- Efecto Hover Progresivo -->
            <div v-if="elementoSeleccionado.content.url" class="space-y-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    Efecto al Pasar el Ratón (Hover)
                </label>
                <Select
                    v-model="elementoSeleccionado.content.hover_effect"
                    :options="opcionesEfectoHover"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
                <p class="text-[11px] text-zinc-400">
                    Animación suave y progresiva al posar el cursor sobre el icono o texto enlazado.
                </p>
            </div>

            <!-- Alineación Horizontal -->
            <div class="space-y-1.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Alineación</label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="op in opcionesAlineacion"
                        :key="op.value"
                        type="button"
                        :class="[
                            'p-2 rounded-lg border text-center flex items-center justify-center gap-1.5 text-xs font-semibold transition-all cursor-pointer',
                            (elementoSeleccionado.content.align || 'left') === op.value
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                                : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300'
                        ]"
                        @click="elementoSeleccionado.content.align = op.value"
                    >
                        <i :class="[op.icon, 'text-xs']" />
                        <span>{{ op.label }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: DISPOSICIÓN & ESTILO DEL ICONO -->
        <div v-show="subTabActiva === 'estilo'" class="space-y-4">
            <div class="space-y-3.5">
                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider border-b border-zinc-200 dark:border-zinc-800 pb-2">
                    <i class="pi pi-star text-xs" />
                    <span>Disposición & Estilo del Icono</span>
                </div>

                <!-- 1. Tamaño del Icono -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Tamaño del Icono</label>
                    <Select
                        v-model="elementoSeleccionado.content.icon_size"
                        :options="opcionesTamano"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Tamaño Personalizado en Píxeles -->
                <div v-if="elementoSeleccionado.content.icon_size === 'custom'" class="space-y-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Tamaño en Píxeles (px)
                    </label>
                    <InputNumber
                        v-model="elementoSeleccionado.content.icon_size_px"
                        :min="12"
                        :max="300"
                        suffix=" px"
                        class="w-full text-xs"
                    />
                </div>

                <!-- 2. Estilo de Contenedor de Icono -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Estilo de Contenedor de Icono</label>
                    <Select
                        v-model="elementoSeleccionado.content.icon_style_mode"
                        :options="opcionesEstiloContenedor"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- 3. Colores: Color del Icono y Color de Fondo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Color del Icono</label>
                        <Select
                            v-model="elementoSeleccionado.content.icon_color"
                            :options="opcionesColorIcono"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>

                    <div v-if="(elementoSeleccionado.content.icon_style_mode || 'none') !== 'none'" class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Color de Fondo</label>
                        <Select
                            v-model="elementoSeleccionado.content.icon_bg_color"
                            :options="opcionesColorFondo"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Selector de Color Personalizado HEX para Icono -->
                <div v-if="elementoSeleccionado.content.icon_color === 'custom'" class="space-y-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Color Personalizado del Icono</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            :value="elementoSeleccionado.content.custom_color || '#10b981'"
                            @input="(e) => elementoSeleccionado.content.custom_color = e.target.value"
                            class="w-9 h-9 rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-pointer p-0.5 bg-transparent shrink-0"
                        />
                        <InputText
                            v-model="elementoSeleccionado.content.custom_color"
                            placeholder="ej: #10b981"
                            class="w-full text-xs font-mono"
                        />
                    </div>
                </div>

                <!-- Selector de Color Personalizado HEX para Fondo -->
                <div v-if="(elementoSeleccionado.content.icon_style_mode || 'none') !== 'none' && elementoSeleccionado.content.icon_bg_color === 'custom'" class="space-y-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Color Personalizado de Fondo</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            :value="elementoSeleccionado.content.custom_bg_color || '#10b981'"
                            @input="(e) => elementoSeleccionado.content.custom_bg_color = e.target.value"
                            class="w-9 h-9 rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-pointer p-0.5 bg-transparent shrink-0"
                        />
                        <InputText
                            v-model="elementoSeleccionado.content.custom_bg_color"
                            placeholder="ej: #10b981"
                            class="w-full text-xs font-mono"
                        />
                    </div>
                </div>

                <!-- 4. Márgenes Superior e Inferior -->
                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Margen Superior</label>
                        <Select
                            v-model="elementoSeleccionado.content.margin_top"
                            :options="opcionesMargen"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Margen Inferior</label>
                        <Select
                            v-model="elementoSeleccionado.content.margin_bottom"
                            :options="opcionesMargen"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full text-xs"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 3: ANIMACIONES Y AJUSTES AVANZADOS -->
        <div v-show="subTabActiva === 'avanzado'" class="space-y-4">
            <!-- Bloque de Animación de Entrada -->
            <AnimationBlock :elemento="elementoSeleccionado" />

            <!-- Identificador HTML único (ID) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">ID del Elemento HTML</label>
                <InputText
                    v-model="elementoSeleccionado.content.element_id"
                    placeholder="ej: icono-destacado"
                    class="w-full text-xs"
                />
            </div>

            <!-- Clases CSS Personalizadas -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Clases CSS Personalizadas</label>
                <InputText
                    v-model="elementoSeleccionado.content.custom_classes"
                    placeholder="ej: hover:rotate-12 filter drop-shadow-md"
                    class="w-full text-xs"
                />
            </div>

            <!-- Estilos CSS Scoped Personalizados -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">CSS Personalizado</label>
                <Textarea
                    v-model="elementoSeleccionado.content.custom_css"
                    rows="3"
                    placeholder="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));"
                    class="w-full text-xs font-mono"
                />
            </div>
        </div>
    </div>
</template>
