<script setup>
/**
 * Inspector Atómico del Elemento Paginación para el Page Builder.
 * Permite configurar:
 *  - Modo (Navegación entre artículos vs Paginación numérica de archivo)
 *  - Variantes de maquetación (Cards, Minimal, Pills, Hero, Compact, Numbers Boxed)
 *  - Textos personalizados, títulos, flechas, botón central de retorno
 *  - Alineación, márgenes y animaciones avanzadas.
 */
import { ref, inject, computed } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import DynamicTagSelector from '@/Components/Builder/DynamicTagSelector.vue';
import AnimationBlock from './AnimationBlock.vue';

const props = defineProps({
    elementoSeleccionado: { type: Object, required: true },
});

// Contexto de plantilla inyectado desde el maquetador visual
const esPlantilla = inject('esPlantilla', ref(false));
const tipoPlantilla = inject('tipoPlantilla', ref(''));

// Pestaña activa dentro del inspector
const subTabActiva = ref('general');

// Inicializar propiedades por defecto
function inicializarPaginationConfig() {
    if (!props.elementoSeleccionado.content) {
        props.elementoSeleccionado.content = {};
    }
    const c = props.elementoSeleccionado.content;
    const defaultMode = (tipoPlantilla.value === 'category_archive') ? 'archive_numeric' : 'article_nav';
    if (c.mode === undefined) c.mode = defaultMode;
    if (c.layout_variant === undefined) c.layout_variant = 'cards';
    if (c.numeric_variant === undefined) c.numeric_variant = 'numbers_boxed';
    if (c.show_labels === undefined) c.show_labels = true;
    if (c.custom_prev_label === undefined) c.custom_prev_label = 'Artículo Anterior';
    if (c.custom_next_label === undefined) c.custom_next_label = 'Artículo Siguiente';
    if (c.show_titles === undefined) c.show_titles = true;
    if (c.show_arrows === undefined) c.show_arrows = true;
    if (c.arrow_type === undefined) c.arrow_type = 'arrow';
    if (c.show_home_button === undefined) c.show_home_button = false;
    if (c.home_button_label === undefined) c.home_button_label = 'Todos los Artículos';
    if (c.home_button_url === undefined) c.home_button_url = '';
    if (c.home_button_icon === undefined) c.home_button_icon = 'pi pi-th-large';
    if (c.align === undefined) c.align = 'center';
    if (c.margin_top === undefined) c.margin_top = 'md';
    if (c.margin_bottom === undefined) c.margin_bottom = 'none';
}
inicializarPaginationConfig();

const opcionesModo = [
    { label: 'Navegación Entre Artículos (Anterior / Siguiente)', value: 'article_nav' },
    { label: 'Paginación Numérica de Listado (1, 2, 3...)', value: 'archive_numeric' },
];

const opcionesVarianteArticulo = [
    { label: 'Tarjetas Lado a Lado (2 Columnas)', value: 'cards' },
    { label: 'Enlaces Minimalistas con Flechas', value: 'minimal' },
    { label: 'Botones Cápsula / Píldoras', value: 'pills' },
    { label: 'Tarjetas Hero Grandes', value: 'hero' },
    { label: 'Barra Compacta Unificada', value: 'compact' },
];

const opcionesVarianteNumerica = [
    { label: 'Botones Cuadrados Suaves (Estándar)', value: 'numbers_boxed' },
    { label: 'Píldoras Circulares con Resplandor', value: 'pills' },
    { label: 'Anterior / Siguiente con Contador Simple', value: 'simple' },
    { label: 'Enlaces Tipográficos Minimalistas', value: 'minimal' },
];

const opcionesTipoFlecha = [
    { label: 'Flechas Clásicas (← / →)', value: 'arrow' },
    { label: 'Chevrons (< / >)', value: 'chevron' },
    { label: 'Ángulos Estilizados (‹ / ›)', value: 'angle' },
    { label: 'Círculos con Flecha', value: 'circle' },
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
                <i class="pi pi-arrows-h text-xs" />
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

        <!-- PESTAÑA 1: CONTENIDO -->
        <div v-show="subTabActiva === 'general'" class="space-y-4">
            <!-- 1. Modo de Paginación -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Modo de Paginación</label>
                <Select
                    v-model="elementoSeleccionado.content.mode"
                    :options="opcionesModo"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <!-- Ayuda contextual según el tipo de plantilla -->
            <div v-if="tipoPlantilla === 'single_article'" class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs flex items-start gap-2">
                <i class="pi pi-info-circle text-emerald-500 mt-0.5 shrink-0" />
                <span><strong>Plantilla de Detalle de Artículo:</strong> Diseñada para la navegación secuencial (Anterior / Siguiente) entre publicaciones de la misma categoría.</span>
            </div>
            <div v-else-if="tipoPlantilla === 'category_archive'" class="p-2.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-800 dark:text-blue-300 text-xs flex items-start gap-2">
                <i class="pi pi-info-circle text-blue-500 mt-0.5 shrink-0" />
                <span><strong>Plantilla de Categoría / Listado:</strong> Diseñada para la paginación numérica de archivo (1, 2, 3...) del catálogo de artículos.</span>
            </div>

            <!-- CONFIGURACIÓN ESPECÍFICA DE NAVEGACIÓN ENTRE ARTÍCULOS -->
            <div v-if="elementoSeleccionado.content.mode === 'article_nav'" class="space-y-3.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <!-- Mostrar Etiquetas -->
                <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Mostrar Etiquetas de Navegación</label>
                        <span class="text-[11px] text-zinc-400">« Artículo Anterior / Artículo Siguiente »</span>
                    </div>
                    <ToggleSwitch v-model="elementoSeleccionado.content.show_labels" />
                </div>

                <!-- Textos Personalizados de Anterior y Siguiente -->
                <div v-if="elementoSeleccionado.content.show_labels" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Texto para Anterior</label>
                        <InputText
                            v-model="elementoSeleccionado.content.custom_prev_label"
                            placeholder="Artículo Anterior"
                            class="w-full text-xs"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Texto para Siguiente</label>
                        <InputText
                            v-model="elementoSeleccionado.content.custom_next_label"
                            placeholder="Artículo Siguiente"
                            class="w-full text-xs"
                        />
                    </div>
                </div>

                <!-- Mostrar Títulos de Artículos -->
                <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Mostrar Título del Artículo</label>
                        <span class="text-[11px] text-zinc-400">Muestra el nombre del post anterior y siguiente</span>
                    </div>
                    <ToggleSwitch v-model="elementoSeleccionado.content.show_titles" />
                </div>

                <!-- Mostrar Flechas de Dirección -->
                <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Mostrar Iconos de Flecha</label>
                        <span class="text-[11px] text-zinc-400">Flechas izquierda y derecha de indicación</span>
                    </div>
                    <ToggleSwitch v-model="elementoSeleccionado.content.show_arrows" />
                </div>

                <!-- Tipo de Flecha -->
                <div v-if="elementoSeleccionado.content.show_arrows" class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Estilo de Flecha</label>
                    <Select
                        v-model="elementoSeleccionado.content.arrow_type"
                        :options="opcionesTipoFlecha"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <!-- Botón Central de Retorno (Volver a Categoría / Listado) -->
                <div class="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Botón Central de Retorno</label>
                            <span class="text-[11px] text-zinc-400">Añade enlace para volver al listado o categoría</span>
                        </div>
                        <ToggleSwitch v-model="elementoSeleccionado.content.show_home_button" />
                    </div>

                    <div v-if="elementoSeleccionado.content.show_home_button" class="space-y-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Texto del Botón Central</label>
                            <InputText
                                v-model="elementoSeleccionado.content.home_button_label"
                                placeholder="ej: Todos los Artículos"
                                class="w-full text-xs"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">URL del Botón Central</label>
                                <DynamicTagSelector
                                    tipo="link"
                                    @insertar="(tag) => elementoSeleccionado.content.home_button_url = tag"
                                />
                            </div>
                            <InputText
                                v-model="elementoSeleccionado.content.home_button_url"
                                placeholder="ej: /blog o {category.url}"
                                class="w-full text-xs"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Icono del Botón Central</label>
                            <InputText
                                v-model="elementoSeleccionado.content.home_button_icon"
                                placeholder="pi pi-th-large"
                                class="w-full text-xs font-mono"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONFIGURACIÓN ESPECÍFICA DE PAGINACIÓN NUMÉRICA -->
            <div v-else-if="elementoSeleccionado.content.mode === 'archive_numeric'" class="space-y-3.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Estilo de Paginación Numérica</label>
                    <Select
                        v-model="elementoSeleccionado.content.numeric_variant"
                        :options="opcionesVarianteNumerica"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: DISPOSICIÓN & ESTILO -->
        <div v-show="subTabActiva === 'estilo'" class="space-y-4">
            <!-- Selector de Variante Visual de Maquetación para Artículos -->
            <div v-if="elementoSeleccionado.content.mode === 'article_nav'" class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Variante de Maquetación</label>
                <Select
                    v-model="elementoSeleccionado.content.layout_variant"
                    :options="opcionesVarianteArticulo"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
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
                            (elementoSeleccionado.content.align || 'center') === op.value
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

            <!-- Márgenes Superior e Inferior -->
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

        <!-- PESTAÑA 3: ANIMACIONES Y AJUSTES AVANZADOS -->
        <div v-show="subTabActiva === 'avanzado'" class="space-y-4">
            <!-- Bloque de Animación de Entrada al Scroll -->
            <AnimationBlock :elemento="elementoSeleccionado" />

            <!-- Identificador HTML único (ID) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">ID del Elemento HTML</label>
                <InputText
                    v-model="elementoSeleccionado.content.element_id"
                    placeholder="ej: navegacion-articulos"
                    class="w-full text-xs"
                />
            </div>

            <!-- Clases CSS Personalizadas -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Clases CSS Personalizadas</label>
                <InputText
                    v-model="elementoSeleccionado.content.custom_classes"
                    placeholder="ej: border-t-2 pt-6"
                    class="w-full text-xs"
                />
            </div>

            <!-- Estilos CSS Scoped Personalizados -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">CSS Personalizado</label>
                <Textarea
                    v-model="elementoSeleccionado.content.custom_css"
                    rows="3"
                    placeholder="/* Estilos específicos para la paginación */"
                    class="w-full text-xs font-mono"
                />
            </div>
        </div>
    </div>
</template>
