<script setup>
/**
 * Componente CabeceraSection: Personalización de disposición del menú, efectos de scroll, botón CTA y cajón móvil.
 */
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import IconPicker from '@/Components/Admin/IconPicker.vue';
import { normalizarColor, normalizarColorPicker } from '@/Utils/colorUtils';

const props = defineProps({
    form: { type: Object, required: true },
});

function agregarBotonHeader() {
    if (!Array.isArray(props.form.header_buttons)) {
        props.form.header_buttons = [];
    }
    props.form.header_buttons.push({
        id: 'btn_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
        text: 'Nuevo Botón',
        url: '#',
        target: '_self',
        icon: 'pi pi-star',
        variant: 'primary',
        is_active: true,
        show_on_desktop: true,
        show_on_mobile_header: false,
        show_on_mobile_menu: true,
        show_on_mobile: true,
    });
}

function eliminarBotonHeader(index) {
    if (Array.isArray(props.form.header_buttons)) {
        props.form.header_buttons.splice(index, 1);
    }
}
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg">
                <i class="pi pi-bars" />
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Cabecera & Navegación Principal (Header)</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Personalización de disposición del menú, efectos de scroll, botón CTA y cajón móvil</p>
            </div>
        </div>

        <!-- 1. DISPOSICIÓN & LAYOUT DE CABECERA (DESKTOP) -->
        <div class="space-y-3">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Disposición del Menú y Logotipo (Escritorio)</h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Seleccione la distribución visual entre el logotipo, los enlaces y la acción principal.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Opción 1: Logo Izquierda - Menú Derecha -->
                <div
                    @click="form.header_layout = 'logo_left_menu_right'"
                    :class="[
                        'p-3.5 rounded-xl border transition-all cursor-pointer space-y-2',
                        form.header_layout === 'logo_left_menu_right'
                            ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-100 ring-2 ring-emerald-500/20'
                            : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 bg-white dark:bg-zinc-950'
                    ]"
                >
                    <div class="h-10 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-2 flex items-center justify-between">
                        <div class="w-8 h-3 rounded bg-zinc-400 dark:bg-zinc-600" />
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-1.5 rounded bg-zinc-300 dark:bg-zinc-700" />
                            <div class="w-4 h-1.5 rounded bg-zinc-300 dark:bg-zinc-700" />
                            <div class="w-5 h-2.5 rounded bg-emerald-500" />
                        </div>
                    </div>
                    <span class="block text-xs font-bold leading-tight">Clásico (Logo Izq — Menú Der)</span>
                    <span class="block text-[10px] text-zinc-500 dark:text-zinc-400">Distribución tradicional de alta conversión</span>
                </div>

                <!-- Opción 2: Logo Izquierda - Menú Centrado -->
                <div
                    @click="form.header_layout = 'logo_left_menu_center'"
                    :class="[
                        'p-3.5 rounded-xl border transition-all cursor-pointer space-y-2',
                        form.header_layout === 'logo_left_menu_center'
                            ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-100 ring-2 ring-emerald-500/20'
                            : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 bg-white dark:bg-zinc-950'
                    ]"
                >
                    <div class="h-10 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-2 flex items-center justify-between">
                        <div class="w-8 h-3 rounded bg-zinc-400 dark:bg-zinc-600" />
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-1.5 rounded bg-zinc-300 dark:bg-zinc-700" />
                            <div class="w-4 h-1.5 rounded bg-zinc-300 dark:bg-zinc-700" />
                        </div>
                        <div class="w-5 h-2.5 rounded bg-emerald-500" />
                    </div>
                    <span class="block text-xs font-bold leading-tight">Moderno (Menú Centrado)</span>
                    <span class="block text-[10px] text-zinc-500 dark:text-zinc-400">Navegación equilibrada al centro</span>
                </div>

                <!-- Opción 3: Logo Centrado Split -->
                <div
                    @click="form.header_layout = 'logo_center_menu_split'"
                    :class="[
                        'p-3.5 rounded-xl border transition-all cursor-pointer space-y-2',
                        form.header_layout === 'logo_center_menu_split'
                            ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-100 ring-2 ring-emerald-500/20'
                            : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 bg-white dark:bg-zinc-950'
                    ]"
                >
                    <div class="h-10 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-2 flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-1.5 rounded bg-zinc-300 dark:bg-zinc-700" />
                        </div>
                        <div class="w-8 h-3 rounded bg-zinc-500 dark:bg-zinc-500" />
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-1.5 rounded bg-zinc-300 dark:bg-zinc-700" />
                            <div class="w-5 h-2.5 rounded bg-emerald-500" />
                        </div>
                    </div>
                    <span class="block text-xs font-bold leading-tight">Split Premium (Logo al Centro)</span>
                    <span class="block text-[10px] text-zinc-500 dark:text-zinc-400">Menú dividido a izquierda y derecha</span>
                </div>

                <!-- Opción 4: Logo Arriba - Menú Abajo (Editorial) -->
                <div
                    @click="form.header_layout = 'logo_top_menu_bottom'"
                    :class="[
                        'p-3.5 rounded-xl border transition-all cursor-pointer space-y-2',
                        form.header_layout === 'logo_top_menu_bottom'
                            ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-100 ring-2 ring-emerald-500/20'
                            : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 bg-white dark:bg-zinc-950'
                    ]"
                >
                    <div class="h-10 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-1.5 flex flex-col justify-between items-center">
                        <div class="w-10 h-2.5 rounded bg-zinc-500 dark:bg-zinc-500" />
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-1 rounded bg-zinc-300 dark:bg-zinc-700" />
                            <div class="w-3 h-1 rounded bg-zinc-300 dark:bg-zinc-700" />
                            <div class="w-3 h-1 rounded bg-zinc-300 dark:bg-zinc-700" />
                        </div>
                    </div>
                    <span class="block text-xs font-bold leading-tight">Editorial (Doble Barra)</span>
                    <span class="block text-[10px] text-zinc-500 dark:text-zinc-400">Logotipo arriba y menú inferior plano</span>
                </div>
            </div>
        </div>

        <!-- 2. COMPORTAMIENTO & ANCHO Y SOMBRA -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3 border-t border-zinc-200 dark:border-zinc-800">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Comportamiento de Desplazamiento</label>
                <Select
                    v-model="form.header_style"
                    :options="[
                        { label: 'Sticky Fijo (Siempre visible con Blur)', value: 'sticky' },
                        { label: 'Inteligente (Ocultar al bajar / Mostrar al subir)', value: 'reveal_on_scroll_up' },
                        { label: 'Compacto Dinámico (Reducir altura al hacer scroll)', value: 'shrink_on_scroll' },
                        { label: 'Cápsula Flotante Suspendida', value: 'floating' },
                        { label: 'Transparente en Top / Cristal al scroll', value: 'transparent' },
                        { label: 'Estático Tradicional (Se desplaza con la página)', value: 'static' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Efecto de Fondo / Cristal</label>
                <Select
                    v-model="form.header_bg_type"
                    :options="[
                        { label: 'Glassmorphic (Translúcido + Blur)', value: 'glassmorphism' },
                        { label: 'Fondo Sólido Corporativo', value: 'solid' },
                        { label: 'Transparente Puro', value: 'transparent' },
                        { label: 'Degradado Sutil (Gradient)', value: 'gradient' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Borde Inferior y Sombra</label>
                <Select
                    v-model="form.header_shadow"
                    :options="[
                        { label: 'Borde Inferior Fino (Border-B)', value: 'border' },
                        { label: 'Sombra Suave Elevada (Shadow-SM)', value: 'shadow' },
                        { label: 'Estilo Cápsula Redondeada (Pill)', value: 'pill' },
                        { label: 'Plano (Sin borde ni sombra)', value: 'none' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                />
            </div>
        </div>

        <!-- 3. PERSONALIZACIÓN ESPECÍFICA DE MODO CÁPSULA (Visible si style === 'floating' o shadow === 'pill') -->
        <div v-if="form.header_style === 'floating' || form.header_shadow === 'pill'" class="p-4 bg-emerald-50/60 dark:bg-emerald-950/30 rounded-xl border border-emerald-200 dark:border-emerald-800/60 space-y-4">
            <div>
                <h3 class="text-xs font-bold text-emerald-900 dark:text-emerald-200 flex items-center gap-2">
                    <i class="pi pi-box text-emerald-600 dark:text-emerald-400 text-xs" />
                    <span>Ajustes Avanzados de Cápsula Flotante (Floating Capsule)</span>
                </h3>
                <p class="text-[11px] text-emerald-700 dark:text-emerald-400">
                    Personalice la anchura del marco flotante, su elevación, sombra y radio de curvatura.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Ancho del Contenedor de Cápsula</label>
                    <Select
                        v-model="form.header_width"
                        :options="[
                            { label: '100% Pantalla (w-full)', value: 'full' },
                            { label: 'Max-w-[1600px] (1600px — Ultra Amplio)', value: 'wide_1600' },
                            { label: 'Max-w-screen-2xl (1536px — Expandido Grande)', value: 'expand' },
                            { label: 'Max-w-[1440px] (1440px — Panorámico)', value: 'wide_1440' },
                            { label: 'Max-w-7xl (1280px — Ancho Web General / Boxed)', value: 'contained' },
                            { label: 'Max-w-6xl (1152px — Compacto)', value: 'compact' },
                            { label: 'Max-w-5xl (1024px — Estrecho)', value: 'skinny' },
                            { label: 'Max-w-4xl (896px — Cápsula Media)', value: 'narrow' },
                            { label: 'Max-w-3xl (768px — Cápsula Mini / Pequeño)', value: 'ultra_compact' },
                            { label: 'Ajustado al Contenido (w-fit — Auto)', value: 'fit' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Redondeado de Esquinas (Border Radius)</label>
                    <Select
                        v-model="form.header_border_radius"
                        :options="[
                            { label: 'Cápsula Redonda 100% (rounded-full)', value: 'rounded-full' },
                            { label: 'Cápsula Extra Redondeada (rounded-3xl)', value: 'rounded-3xl' },
                            { label: 'Redondeado Amplio (rounded-2xl)', value: 'rounded-2xl' },
                            { label: 'Redondeado Medio (rounded-xl)', value: 'rounded-xl' },
                            { label: 'Redondeado Suave (rounded-lg)', value: 'rounded-lg' },
                            { label: 'Cuadrado / Recto (rounded-none)', value: 'rounded-none' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Padding Interno (Altura / Espacio)</label>
                    <Select
                        v-model="form.header_capsule_padding"
                        :options="[
                            { label: 'Compacto (Ajustado py-2 px-4)', value: 'compact' },
                            { label: 'Estándar (Equilibrado py-3 px-6)', value: 'standard' },
                            { label: 'Espacioso (Holgado py-4 px-8)', value: 'spacious' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Sombra y Elevación de la Cápsula</label>
                    <Select
                        v-model="form.header_capsule_shadow"
                        :options="[
                            { label: 'Sombra Flotante Elevada (shadow-2xl)', value: 'shadow-2xl' },
                            { label: 'Sombra Premium (shadow-xl)', value: 'shadow-xl' },
                            { label: 'Sombra Moderada (shadow-md)', value: 'shadow-md' },
                            { label: 'Sombra Suave (shadow-sm)', value: 'shadow-sm' },
                            { label: 'Resplandor Verde Corporativo (shadow-glow)', value: 'shadow-glow' },
                            { label: 'Sin Sombra (none)', value: 'none' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- 3. GESTOR DE BOTONES E ICONOS DE ACCIÓN EN CABECERA -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-xs font-bold text-zinc-900 dark:text-zinc-100">Botones e Iconos de Acción en Cabecera (Acciones Múltiples)</span>
                    <span class="block text-[11px] text-zinc-500 dark:text-zinc-400">Configure múltiples botones de conversión o botones de solo icono (ej. "Pedir Cita", "Llamar", WhatsApp, Teléfono)</span>
                </div>
                <button
                    type="button"
                    @click="agregarBotonHeader"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                    <i class="pi pi-plus text-xs" />
                    <span>Añadir Botón / Icono</span>
                </button>
            </div>

            <div v-if="!form.header_buttons || !form.header_buttons.length" class="text-center py-6 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">No hay botones de acción configurados en la cabecera.</p>
                <button
                    type="button"
                    @click="agregarBotonHeader"
                    class="mt-2 px-3 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer inline-flex items-center gap-1"
                >
                    <i class="pi pi-plus text-xs" />
                    <span>Añadir el primer botón o icono</span>
                </button>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(btn, index) in form.header_buttons"
                    :key="btn.id || index"
                    class="p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xs space-y-3"
                >
                    <div class="flex items-center justify-between pb-2 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-[10px] font-bold text-zinc-600 dark:text-zinc-400 flex items-center justify-center">
                                {{ index + 1 }}
                            </span>
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                {{ btn.text || (btn.variant === 'icon_only' ? 'Icono: ' + (btn.icon || 'Sin icono') : 'Botón sin título') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] text-zinc-500">Activo</span>
                                <ToggleSwitch v-model="btn.is_active" />
                            </div>

                            <button
                                type="button"
                                @click="eliminarBotonHeader(index)"
                                class="p-1 text-zinc-400 hover:text-red-500 transition-colors cursor-pointer"
                                title="Eliminar botón"
                            >
                                <i class="pi pi-trash text-xs" />
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Texto (dejar vacío para solo icono)</label>
                            <InputText v-model="btn.text" placeholder="ej. Pedir Cita" class="w-full text-xs" />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">URL / Enlace / Teléfono</label>
                            <InputText v-model="btn.url" placeholder="ej. /contacto o tel:+34..." class="w-full text-xs" />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Icono</label>
                            <IconPicker v-model="btn.icon" />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Estilo / Variante</label>
                            <Select
                                v-model="btn.variant"
                                :options="[
                                    { label: 'Primario (Relleno Marca)', value: 'primary' },
                                    { label: 'Secundario (Neutro / Oscuro)', value: 'secondary' },
                                    { label: 'Contorno (Outline)', value: 'outline' },
                                    { label: 'Resplandor (Glow)', value: 'glow' },
                                    { label: 'Solo Icono (Sutil)', value: 'icon_only' },
                                    { label: 'Enlace (Texto Simple)', value: 'link' }
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5 pt-1 border-t border-zinc-100 dark:border-zinc-800/80">
                        <span class="block text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Visibilidad (Opcional por ubicación):</span>
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[11px] text-zinc-600 dark:text-zinc-400">
                            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                <input type="checkbox" v-model="btn.show_on_desktop" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">Cabecera Escritorio</span>
                            </label>

                            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                <input type="checkbox" v-model="btn.show_on_mobile_header" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">Cabecera Móvil (Barra superior)</span>
                            </label>

                            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    :checked="btn.show_on_mobile_menu ?? btn.show_on_mobile ?? false"
                                    @change="e => { btn.show_on_mobile_menu = e.target.checked; btn.show_on_mobile = e.target.checked; }"
                                    class="rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">Menú Emergente Móvil</span>
                            </label>

                            <label class="flex items-center gap-1.5 cursor-pointer select-none ms-auto">
                                <span>Abrir en nueva pestaña</span>
                                <input type="checkbox" :checked="btn.target === '_blank'" @change="e => btn.target = e.target.checked ? '_blank' : '_self'" class="rounded text-emerald-600 focus:ring-emerald-500" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. MENÚ MÓVIL Y CAJÓN DESLIZANTE (OFF-CANVAS) -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-4">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="pi pi-mobile text-emerald-600 dark:text-emerald-400 text-xs" />
                    <span>Navegación Móvil & Cajón Lateral (Mobile Menu & Off-Canvas)</span>
                </h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                    Personalice el estilo de apertura en pantallas táctiles y dispositivos móviles.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Estilo de Menú Móvil</label>
                    <Select
                        v-model="form.mobile_menu_style"
                        :options="[
                            { label: 'Cajón Lateral Derecha (Drawer)', value: 'drawer_right' },
                            { label: 'Cajón Lateral Izquierda (Drawer)', value: 'drawer_left' },
                            { label: 'Pantalla Completa (Fullscreen)', value: 'fullscreen' },
                            { label: 'Desplegable Superior (Dropdown)', value: 'dropdown' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div class="flex items-center justify-between p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 gap-3 min-w-0">
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-zinc-800 dark:text-zinc-200 truncate">Botón CTA en Móvil</span>
                        <span class="block text-[10px] text-zinc-400 truncate">Acción principal en menú móvil</span>
                    </div>
                    <ToggleSwitch v-model="form.mobile_show_cta" class="shrink-0 ms-2" />
                </div>

                <div class="flex items-center justify-between p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 gap-3 min-w-0">
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-zinc-800 dark:text-zinc-200 truncate">Redes Sociales Cabecera</span>
                        <span class="block text-[10px] text-zinc-400 truncate">Iconos en escritorio y tablet</span>
                    </div>
                    <ToggleSwitch v-model="form.header_show_socials" class="shrink-0 ms-2" />
                </div>

                <div class="flex items-center justify-between p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 gap-3 min-w-0">
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-zinc-800 dark:text-zinc-200 truncate">Redes Sociales Móvil</span>
                        <span class="block text-[10px] text-zinc-400 truncate">Iconos sociales al pie del menú</span>
                    </div>
                    <ToggleSwitch v-model="form.mobile_show_socials" class="shrink-0 ms-2" />
                </div>
            </div>
        </div>

        <!-- 5. ESTILO Y COLORES DE NAVEGACIÓN DEL MENÚ DE CABECERA (INDEPENDIENTE) -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-4">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="pi pi-palette text-emerald-600 dark:text-emerald-400 text-xs" />
                    <span>Colores del Menú de Navegación (Modo Claro vs Modo Oscuro)</span>
                </h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                    Personaliza los colores del menú principal de la cabecera (reposo, hover y página activa) de forma 100% independiente de los enlaces generales del sitio.
                </p>
            </div>

            <!-- MODO CLARO -->
            <div class="space-y-2">
                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                    <i class="pi pi-sun text-amber-500 text-xs" />
                    <span>Navegación en Modo Claro</span>
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Normal (Reposo)</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.header_nav_color_light, '#3f3f46')"
                                @input="e => form.header_nav_color_light = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.header_nav_color_light"
                                @blur="form.header_nav_color_light = normalizarColor(form.header_nav_color_light, '#3f3f46')"
                                placeholder="#3f3f46"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Hover (Ratón encima)</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.header_nav_hover_light, '#18181b')"
                                @input="e => form.header_nav_hover_light = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.header_nav_hover_light"
                                @blur="form.header_nav_hover_light = normalizarColor(form.header_nav_hover_light, '#18181b')"
                                placeholder="#18181b"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Activo (Página actual)</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.header_nav_active_light, form.primary_color)"
                                @input="e => form.header_nav_active_light = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.header_nav_active_light"
                                @blur="form.header_nav_active_light = normalizarColor(form.header_nav_active_light, form.primary_color)"
                                placeholder="#10b981"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODO OSCURO -->
            <div class="space-y-2 pt-2 border-t border-zinc-200/60 dark:border-zinc-700/60">
                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                    <i class="pi pi-moon text-indigo-400 text-xs" />
                    <span>Navegación en Modo Oscuro</span>
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Normal (Reposo)</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.header_nav_color_dark, '#d4d4d8')"
                                @input="e => form.header_nav_color_dark = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.header_nav_color_dark"
                                @blur="form.header_nav_color_dark = normalizarColor(form.header_nav_color_dark, '#d4d4d8')"
                                placeholder="#d4d4d8"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Hover (Ratón encima)</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.header_nav_hover_dark, '#ffffff')"
                                @input="e => form.header_nav_hover_dark = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.header_nav_hover_dark"
                                @blur="form.header_nav_hover_dark = normalizarColor(form.header_nav_hover_dark, '#ffffff')"
                                placeholder="#ffffff"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Activo (Página actual)</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.header_nav_active_dark, form.primary_color)"
                                @input="e => form.header_nav_active_dark = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.header_nav_active_dark"
                                @blur="form.header_nav_active_dark = normalizarColor(form.header_nav_active_dark, form.primary_color)"
                                placeholder="#10b981"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
