<script setup>
/**
 * Componente BotonesSection: Colores de botones (modo claro y oscuro), geometría (border radius, padding, font size) y vista previa en vivo.
 */
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import { normalizarColor, normalizarColorPicker } from '@/Utils/colorUtils';

const props = defineProps({
    form: { type: Object, required: true },
});
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 flex items-center justify-center text-lg">
                <i class="pi pi-box" />
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Estilo de Botones y Controles</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Configuración global de colores de fondo, texto y geometría para botones primarios y secundarios</p>
            </div>
        </div>

        <!-- COLORES DE BOTONES: MODO CLARO Y MODO OSCURO -->
        <div class="space-y-4">
            <!-- Modo Claro -->
            <div class="p-4 bg-zinc-50/70 dark:bg-zinc-800/30 rounded-xl border border-zinc-200/80 dark:border-zinc-800 space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-200/60 dark:border-zinc-800/60 pb-2">
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2 uppercase tracking-wider">
                        <i class="pi pi-sun text-zinc-500 dark:text-zinc-400 text-xs" />
                        <span>Botones en Modo Claro</span>
                    </h3>
                    <span class="text-[10px] font-mono text-zinc-400 uppercase">Light Theme</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Primario: Fondo</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_primary_bg, normalizarColor(form.primary_color, '#10b981'))"
                                @input="e => form.button_primary_bg = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_primary_bg"
                                @blur="form.button_primary_bg = normalizarColor(form.button_primary_bg, '#10b981')"
                                placeholder="#10b981"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Primario: Texto</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_primary_text, '#ffffff')"
                                @input="e => form.button_primary_text = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_primary_text"
                                @blur="form.button_primary_text = normalizarColor(form.button_primary_text, '#ffffff')"
                                placeholder="#ffffff"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Secundario: Fondo</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_secondary_bg, normalizarColor(form.secondary_color, '#6366f1'))"
                                @input="e => form.button_secondary_bg = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_secondary_bg"
                                @blur="form.button_secondary_bg = normalizarColor(form.button_secondary_bg, '#6366f1')"
                                placeholder="#6366f1"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Secundario: Texto</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_secondary_text, '#ffffff')"
                                @input="e => form.button_secondary_text = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_secondary_text"
                                @blur="form.button_secondary_text = normalizarColor(form.button_secondary_text, '#ffffff')"
                                placeholder="#ffffff"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modo Oscuro -->
            <div class="p-4 bg-zinc-50/70 dark:bg-zinc-800/30 rounded-xl border border-zinc-200/80 dark:border-zinc-800 space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-200/60 dark:border-zinc-800/60 pb-2">
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2 uppercase tracking-wider">
                        <i class="pi pi-moon text-zinc-500 dark:text-zinc-400 text-xs" />
                        <span>Botones en Modo Oscuro</span>
                    </h3>
                    <span class="text-[10px] font-mono text-zinc-400 uppercase">Dark Theme</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Primario Oscuro: Fondo</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_primary_bg_dark, form.button_primary_bg)"
                                @input="e => form.button_primary_bg_dark = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_primary_bg_dark"
                                @blur="form.button_primary_bg_dark = normalizarColor(form.button_primary_bg_dark, form.button_primary_bg)"
                                placeholder="#10b981"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Primario Oscuro: Texto</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_primary_text_dark, '#ffffff')"
                                @input="e => form.button_primary_text_dark = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_primary_text_dark"
                                @blur="form.button_primary_text_dark = normalizarColor(form.button_primary_text_dark, '#ffffff')"
                                placeholder="#ffffff"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Secundario Oscuro: Fondo</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_secondary_bg_dark, '#27272a')"
                                @input="e => form.button_secondary_bg_dark = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_secondary_bg_dark"
                                @blur="form.button_secondary_bg_dark = normalizarColor(form.button_secondary_bg_dark, '#27272a')"
                                placeholder="#27272a"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Secundario Oscuro: Texto</label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="normalizarColorPicker(form.button_secondary_text_dark, '#ffffff')"
                                @input="e => form.button_secondary_text_dark = e.target.value"
                                type="color"
                                class="w-8 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                            />
                            <InputText
                                v-model="form.button_secondary_text_dark"
                                @blur="form.button_secondary_text_dark = normalizarColor(form.button_secondary_text_dark, '#ffffff')"
                                placeholder="#ffffff"
                                class="w-full font-mono text-xs"
                                maxlength="7"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dimensiones y Geometría de Botones -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
            <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Dimensiones y Geometría General</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Radio de Curvatura (Bordes)</label>
                    <Select
                        v-model="form.button_border_radius"
                        :options="[
                            { label: 'Sin redondeo / Recto (0px)', value: 'rounded-none' },
                            { label: 'Ligero (8px - rounded-lg)', value: 'rounded-lg' },
                            { label: 'Medio (12px - rounded-xl)', value: 'rounded-xl' },
                            { label: 'Pronunciado (16px - rounded-2xl)', value: 'rounded-2xl' },
                            { label: 'Muy redondeado (24px - rounded-3xl)', value: 'rounded-3xl' },
                            { label: 'Píldora / Redondo total', value: 'rounded-full' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Tamaño de Texto por Defecto</label>
                    <Select
                        v-model="form.button_font_size"
                        :options="[
                            { label: 'Pequeño (12px - text-xs)', value: 'text-xs' },
                            { label: 'Por defecto (14px - text-sm)', value: 'text-sm' },
                            { label: 'Grande (16px - text-base)', value: 'text-base' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Relleno Horizontal (Padding X)</label>
                    <Select
                        v-model="form.button_padding_x"
                        :options="[
                            { label: 'Compacto (px-3)', value: 'px-3' },
                            { label: 'Por defecto (px-5)', value: 'px-5' },
                            { label: 'Amplio (px-7)', value: 'px-7' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Relleno Vertical (Padding Y)</label>
                    <Select
                        v-model="form.button_padding_y"
                        :options="[
                            { label: 'Compacto (py-1.5)', value: 'py-1.5' },
                            { label: 'Por defecto (py-2.5)', value: 'py-2.5' },
                            { label: 'Amplio (py-3.5)', value: 'py-3.5' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- Previsualización en Vivo de Botones en Modo Claro vs Oscuro -->
        <div class="p-5 bg-zinc-50 dark:bg-zinc-950/60 rounded-2xl border border-zinc-200 dark:border-zinc-800 space-y-4">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider block">Vista Previa de Botones en Tiempo Real</span>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Vista Previa Modo Claro -->
                <div class="p-4 bg-white text-zinc-800 rounded-xl border border-zinc-200/80 space-y-3 shadow-2xs">
                    <span class="text-[11px] font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5 uppercase tracking-wider">
                        <i class="pi pi-sun text-zinc-400 text-xs" /> Tema Claro
                    </span>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" :style="{ backgroundColor: normalizarColor(form.button_primary_bg, '#10b981'), color: normalizarColor(form.button_primary_text, '#ffffff') }" :class="[form.button_border_radius, form.button_padding_x, form.button_padding_y, form.button_font_size, 'font-bold shadow-2xs cursor-pointer flex items-center gap-2 transition-all']">
                            <span>Primario</span>
                        </button>
                        <button type="button" :style="{ backgroundColor: normalizarColor(form.button_secondary_bg, '#6366f1'), color: normalizarColor(form.button_secondary_text, '#ffffff') }" :class="[form.button_border_radius, form.button_padding_x, form.button_padding_y, form.button_font_size, 'font-bold shadow-2xs cursor-pointer flex items-center gap-2 transition-all']">
                            <span>Secundario</span>
                        </button>
                        <button type="button" :style="{ borderColor: normalizarColor(form.button_primary_bg, '#10b981'), color: normalizarColor(form.button_primary_bg, '#10b981') }" :class="[form.button_border_radius, form.button_padding_x, form.button_padding_y, form.button_font_size, 'font-bold border-2 bg-transparent cursor-pointer flex items-center gap-2 transition-all']">
                            <span>Contorno</span>
                        </button>
                    </div>
                </div>

                <!-- Vista Previa Modo Oscuro -->
                <div class="p-4 bg-zinc-950 text-zinc-100 rounded-xl border border-zinc-800 space-y-3 shadow-2xs">
                    <span class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5 uppercase tracking-wider">
                        <i class="pi pi-moon text-zinc-500 text-xs" /> Tema Oscuro
                    </span>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" :style="{ backgroundColor: normalizarColor(form.button_primary_bg_dark, form.button_primary_bg), color: normalizarColor(form.button_primary_text_dark, '#ffffff') }" :class="[form.button_border_radius, form.button_padding_x, form.button_padding_y, form.button_font_size, 'font-bold shadow-2xs cursor-pointer flex items-center gap-2 transition-all']">
                            <span>Primario</span>
                        </button>
                        <button type="button" :style="{ backgroundColor: normalizarColor(form.button_secondary_bg_dark, '#27272a'), color: normalizarColor(form.button_secondary_text_dark, '#ffffff') }" :class="[form.button_border_radius, form.button_padding_x, form.button_padding_y, form.button_font_size, 'font-bold shadow-2xs cursor-pointer flex items-center gap-2 transition-all']">
                            <span>Secundario</span>
                        </button>
                        <button type="button" :style="{ borderColor: normalizarColor(form.button_primary_bg_dark, form.button_primary_bg), color: normalizarColor(form.button_primary_bg_dark, form.button_primary_bg) }" :class="[form.button_border_radius, form.button_padding_x, form.button_padding_y, form.button_font_size, 'font-bold border-2 bg-transparent cursor-pointer flex items-center gap-2 transition-all']">
                            <span>Contorno</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
