<script setup>
/**
 * Componente ColoresSection: Paleta corporativa (primario, secundario, acento), modo de tema frontend, colores de fondo/texto y enlaces (modo claro vs oscuro).
 */
import { ref } from 'vue';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import { normalizarColor, normalizarColorPicker } from '@/Utils/colorUtils';

const props = defineProps({
    form: { type: Object, required: true },
});

const hoverLight = ref(false);
const hoverDark = ref(false);

const getLinkPreviewStyle = (isDark, isHover) => {
    const color = isDark
        ? (isHover ? normalizarColor(props.form.link_hover_color_dark, '#ffffff') : normalizarColor(props.form.link_color_dark, '#d4d4d8'))
        : (isHover ? normalizarColor(props.form.link_hover_color, '#059669') : normalizarColor(props.form.link_color, '#10b981'));

    const dec = props.form.link_decoration || 'hover';
    let textDecoration = 'none';
    if (dec === 'always') textDecoration = 'underline';
    else if (dec === 'none') textDecoration = 'none';
    else if (dec === 'hover') textDecoration = isHover ? 'underline' : 'none';
    else if (dec === 'reverse_hover') textDecoration = isHover ? 'none' : 'underline';

    const weightMap = {
        inherit: 'inherit',
        normal: '400',
        medium: '500',
        semibold: '600',
        bold: '700',
    };

    return {
        color,
        textDecorationLine: textDecoration,
        textDecorationStyle: props.form.link_underline_style || 'solid',
        textUnderlineOffset: props.form.link_underline_offset === 'auto' ? 'auto' : props.form.link_underline_offset,
        fontWeight: weightMap[props.form.link_font_weight] || 'inherit',
    };
};
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
                <i class="pi pi-palette" />
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Paleta Corporativa de Colores y Modo de Tema</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Colores corporativos principales y comportamiento de tema del sitio web público</p>
            </div>
        </div>

        <!-- MODO DE TEMA PÚBLICO FRONTEND -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-3">
            <div>
                <label class="block text-xs font-bold text-zinc-900 dark:text-zinc-100 mb-0.5">Modo de Tema de la Web Pública (Frontend)</label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Configuración independiente de la web pública (no afecta al Panel de Administración)</p>
            </div>

            <Select
                v-model="form.frontend_theme_mode"
                :options="[
                    { label: 'Modo Claro Predeterminado (Siempre Claro)', value: 'light', icon: 'pi pi-sun' },
                    { label: 'Modo Oscuro Predeterminado (Siempre Oscuro)', value: 'dark', icon: 'pi pi-moon' },
                    { label: 'A Elección del Visitante (Conmutador en el Header)', value: 'user_choice', icon: 'pi pi-sliders-h' }
                ]"
                optionLabel="label"
                optionValue="value"
                class="w-full text-xs"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex items-center gap-2 font-medium">
                        <i :class="[
                            slotProps.value === 'light' ? 'pi pi-sun' :
                            slotProps.value === 'dark' ? 'pi pi-moon' :
                            'pi pi-sliders-h',
                            'text-sm text-zinc-600 dark:text-zinc-400'
                        ]" />
                        <span>{{
                            slotProps.value === 'light' ? 'Modo Claro Predeterminado (Siempre Claro)' :
                            slotProps.value === 'dark' ? 'Modo Oscuro Predeterminado (Siempre Oscuro)' :
                            'A Elección del Visitante (Conmutador en el Header)'
                        }}</span>
                    </div>
                </template>
                <template #option="slotProps">
                    <div class="flex items-center gap-2 text-xs py-0.5 font-medium">
                        <i :class="slotProps.option.icon" class="text-sm text-zinc-600 dark:text-zinc-400" />
                        <span>{{ slotProps.option.label }}</span>
                    </div>
                </template>
            </Select>

            <!-- Sub-opción: Tema por defecto en la Primera Visita -->
            <div v-if="form.frontend_theme_mode === 'user_choice'" class="pt-2 border-t border-zinc-200 dark:border-zinc-700/60 space-y-1.5">
                <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 flex items-center justify-between">
                    <span>Tema Inicial Predeterminado (Para Primeras Visitas / Sin Preferencia Guardada)</span>
                    <span class="text-[10px] text-zinc-400 font-normal">Antes de que el usuario cambie el switch</span>
                </label>
                <Select
                    v-model="form.frontend_default_theme"
                    :options="[
                        { label: 'Modo Claro Inicialmente', value: 'light', icon: 'pi pi-sun' },
                        { label: 'Modo Oscuro Inicialmente', value: 'dark', icon: 'pi pi-moon' },
                        { label: 'Detectar Automáticamente (Preferencias del S.O. del usuario)', value: 'system', icon: 'pi pi-desktop' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full text-xs"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-2 font-medium">
                            <i :class="[
                                slotProps.value === 'light' ? 'pi pi-sun' :
                                slotProps.value === 'dark' ? 'pi pi-moon' :
                                'pi pi-desktop',
                                'text-sm text-zinc-600 dark:text-zinc-400'
                            ]" />
                            <span>{{
                                slotProps.value === 'light' ? 'Modo Claro Inicialmente' :
                                slotProps.value === 'dark' ? 'Modo Oscuro Inicialmente' :
                                'Detectar Automáticamente (Preferencias del S.O. del usuario)'
                            }}</span>
                        </div>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center gap-2 text-xs py-0.5 font-medium">
                            <i :class="slotProps.option.icon" class="text-sm text-zinc-600 dark:text-zinc-400" />
                            <span>{{ slotProps.option.label }}</span>
                        </div>
                    </template>
                </Select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Color Primario (Marca Principal)</label>
                <div class="flex items-center gap-2">
                    <input
                        :value="normalizarColorPicker(form.primary_color, '#10b981')"
                        @input="e => form.primary_color = e.target.value"
                        type="color"
                        class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                    />
                    <InputText
                        v-model="form.primary_color"
                        @blur="form.primary_color = normalizarColor(form.primary_color, '#10b981')"
                        placeholder="#10b981"
                        class="w-full font-mono text-xs"
                        maxlength="7"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Color Secundario (Complementario)</label>
                <div class="flex items-center gap-2">
                    <input
                        :value="normalizarColorPicker(form.secondary_color, '#6366f1')"
                        @input="e => form.secondary_color = e.target.value"
                        type="color"
                        class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                    />
                    <InputText
                        v-model="form.secondary_color"
                        @blur="form.secondary_color = normalizarColor(form.secondary_color, '#6366f1')"
                        placeholder="#6366f1"
                        class="w-full font-mono text-xs"
                        maxlength="7"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Color Acento (Highlights / Badges)</label>
                <div class="flex items-center gap-2">
                    <input
                        :value="normalizarColorPicker(form.accent_color, '#f59e0b')"
                        @input="e => form.accent_color = e.target.value"
                        type="color"
                        class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                    />
                    <InputText
                        v-model="form.accent_color"
                        @blur="form.accent_color = normalizarColor(form.accent_color, '#f59e0b')"
                        placeholder="#f59e0b"
                        class="w-full font-mono text-xs"
                        maxlength="7"
                    />
                </div>
            </div>
        </div>

        <!-- Previsualización en Vivo de la Paleta corporativa -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-950/60 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-4">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Paleta de Colores Activa</span>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full border border-black/10 shadow-xs" :style="{ backgroundColor: normalizarColor(form.primary_color, '#10b981') }" />
                    <span class="text-[11px] font-mono text-zinc-600 dark:text-zinc-400">Primario</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full border border-black/10 shadow-xs" :style="{ backgroundColor: normalizarColor(form.secondary_color, '#6366f1') }" />
                    <span class="text-[11px] font-mono text-zinc-600 dark:text-zinc-400">Secundario</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full border border-black/10 shadow-xs" :style="{ backgroundColor: normalizarColor(form.accent_color, '#f59e0b') }" />
                    <span class="text-[11px] font-mono text-zinc-600 dark:text-zinc-400">Acento</span>
                </div>
            </div>
        </div>

        <!-- FONDO Y TEXTO GENERAL DEL SITIO (MODO CLARO VS MODO OSCURO) -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="pi pi-desktop text-zinc-500 dark:text-zinc-400 text-xs" />
                    <span>Fondo y Texto General del Sitio Web (Modo Claro vs Modo Oscuro)</span>
                </h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                    Establece los colores globales para el lienzo de fondo de las páginas públicas y el color de texto del cuerpo.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-sun text-amber-500 text-xs" />
                        <span>Fondo (Modo Claro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.bg_color_light, '#ffffff')"
                            @input="e => form.bg_color_light = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.bg_color_light"
                            @blur="form.bg_color_light = normalizarColor(form.bg_color_light, '#ffffff')"
                            placeholder="#ffffff"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-sun text-amber-500 text-xs" />
                        <span>Texto Body (Modo Claro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.text_color_light, '#18181b')"
                            @input="e => form.text_color_light = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.text_color_light"
                            @blur="form.text_color_light = normalizarColor(form.text_color_light, '#18181b')"
                            placeholder="#18181b"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-moon text-indigo-400 text-xs" />
                        <span>Fondo (Modo Oscuro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.bg_color_dark, '#09090b')"
                            @input="e => form.bg_color_dark = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.bg_color_dark"
                            @blur="form.bg_color_dark = normalizarColor(form.bg_color_dark, '#09090b')"
                            placeholder="#09090b"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-moon text-indigo-400 text-xs" />
                        <span>Texto Body (Modo Oscuro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.text_color_dark, '#f4f4f5')"
                            @input="e => form.text_color_dark = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.text_color_dark"
                            @blur="form.text_color_dark = normalizarColor(form.text_color_dark, '#f4f4f5')"
                            placeholder="#f4f4f5"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>
            </div>

            <!-- Imagen / Gradiente de Fondo (background-image) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                <!-- Modo Claro Presets -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center justify-between">
                        <span class="flex items-center gap-1">
                            <i class="pi pi-sun text-amber-500 text-xs" />
                            <span>Gradiente / Imagen de Fondo (Modo Claro)</span>
                        </span>
                        <span class="text-[10px] font-mono text-zinc-400">CSS background-image</span>
                    </label>
                    <InputText
                        v-model="form.bg_image_light"
                        placeholder="none o radial-gradient(...) o url(...)"
                        class="w-full font-mono text-xs mb-2"
                    />
                    <div class="space-y-1.5">
                        <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Presets Rápidos (Modo Claro):</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                @click="form.bg_image_light = 'none'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all border border-zinc-200 dark:border-zinc-700 font-medium cursor-pointer"
                            >
                                Sin Gradiente
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_light = 'radial-gradient(50% 50% at 50% 50%, #e2e8f0 0%, #ffffff 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-slate-100 text-slate-800 border border-slate-300 hover:bg-slate-200 transition-all font-medium cursor-pointer"
                            >
                                Glow Gris Minimal
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_light = 'radial-gradient(50% 50% at 50% 50%, #fef3c7 0%, #ffffff 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-amber-50 text-amber-900 border border-amber-200 hover:bg-amber-100 transition-all font-medium cursor-pointer"
                            >
                                Glow Ámbar Cálido
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_light = 'radial-gradient(50% 50% at 50% 50%, #d1fae5 0%, #ffffff 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-emerald-50 text-emerald-900 border border-emerald-200 hover:bg-emerald-100 transition-all font-medium cursor-pointer"
                            >
                                Glow Menta Mágico
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_light = 'radial-gradient(50% 50% at 50% 50%, #e0f2fe 0%, #ffffff 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-sky-50 text-sky-900 border border-sky-200 hover:bg-sky-100 transition-all font-medium cursor-pointer"
                            >
                                Glow Brisa Azul
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_light = 'radial-gradient(50% 50% at 50% 50%, #f3e8ff 0%, #ffffff 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-purple-50 text-purple-900 border border-purple-200 hover:bg-purple-100 transition-all font-medium cursor-pointer"
                            >
                                Glow Violeta Pastel
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_light = 'radial-gradient(at 0% 0%, #e0f2fe 0px, transparent 50%), radial-gradient(at 100% 100%, #fef3c7 0px, transparent 50%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-gradient-to-r from-sky-100 to-amber-100 text-zinc-800 border border-zinc-300 hover:opacity-90 transition-all font-medium cursor-pointer"
                            >
                                Mesh Dual (Azul & Ámbar)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modo Oscuro Presets -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center justify-between">
                        <span class="flex items-center gap-1">
                            <i class="pi pi-moon text-indigo-400 text-xs" />
                            <span>Gradiente / Imagen de Fondo (Modo Oscuro)</span>
                        </span>
                        <span class="text-[10px] font-mono text-zinc-400">CSS background-image</span>
                    </label>
                    <InputText
                        v-model="form.bg_image_dark"
                        placeholder="none o radial-gradient(...) o url(...)"
                        class="w-full font-mono text-xs mb-2"
                    />
                    <div class="space-y-1.5">
                        <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Presets Rápidos (Modo Oscuro):</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                @click="form.bg_image_dark = 'none'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all border border-zinc-200 dark:border-zinc-700 font-medium cursor-pointer"
                            >
                                Sin Gradiente
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_dark = 'radial-gradient(50% 50% at 50% 50%, #621f04b3 0%, #621f0400 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-amber-950/70 text-amber-200 border border-amber-700/50 hover:bg-amber-900/80 transition-all font-medium cursor-pointer"
                            >
                                Glow Fuego (Ámbar Cálido)
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_dark = 'radial-gradient(50% 50% at 50% 50%, #4c1d95b3 0%, #4c1d9500 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-purple-950/70 text-purple-200 border border-purple-700/50 hover:bg-purple-900/80 transition-all font-medium cursor-pointer"
                            >
                                Glow Violeta (Espacio Profundo)
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_dark = 'radial-gradient(50% 50% at 50% 50%, #065f46b3 0%, #065f4600 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-emerald-950/70 text-emerald-200 border border-emerald-700/50 hover:bg-emerald-900/80 transition-all font-medium cursor-pointer"
                            >
                                Glow Esmeralda
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_dark = 'radial-gradient(50% 50% at 50% 50%, #1e40afb3 0%, #1e40af00 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-blue-950/70 text-blue-200 border border-blue-700/50 hover:bg-blue-900/80 transition-all font-medium cursor-pointer"
                            >
                                Glow Azul Eléctrico
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_dark = 'radial-gradient(50% 50% at 50% 50%, #831843b3 0%, #83184300 90%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-pink-950/70 text-pink-200 border border-pink-700/50 hover:bg-pink-900/80 transition-all font-medium cursor-pointer"
                            >
                                Glow Magenta Neón
                            </button>
                            <button
                                type="button"
                                @click="form.bg_image_dark = 'radial-gradient(at 0% 0%, #4c1d9566 0px, transparent 50%), radial-gradient(at 100% 100%, #1e3a8a66 0px, transparent 50%)'"
                                class="px-2.5 py-1 text-[10px] rounded-lg bg-gradient-to-r from-purple-950 via-indigo-950 to-blue-950 text-indigo-200 border border-indigo-700/50 hover:opacity-90 transition-all font-medium cursor-pointer"
                            >
                                Mesh Dual (Violeta & Azul)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLORES DE ENLACES Y NAVEGACIÓN (MODO CLARO VS MODO OSCURO) -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="pi pi-link text-emerald-600 dark:text-emerald-400 text-xs" />
                    <span>Estilo de Enlaces & Navegación (Modo Claro vs Modo Oscuro)</span>
                </h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                    Personaliza los colores de los hipervínculos del contenido y de los enlaces del menú de cabecera tanto para tema claro como tema oscuro.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-sun text-amber-500 text-xs" />
                        <span>Enlace (Modo Claro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.link_color, '#10b981')"
                            @input="e => form.link_color = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.link_color"
                            @blur="form.link_color = normalizarColor(form.link_color, '#10b981')"
                            placeholder="#10b981"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-sun text-amber-500 text-xs" />
                        <span>Hover (Modo Claro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.link_hover_color, '#059669')"
                            @input="e => form.link_hover_color = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.link_hover_color"
                            @blur="form.link_hover_color = normalizarColor(form.link_hover_color, '#059669')"
                            placeholder="#059669"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-moon text-indigo-400 text-xs" />
                        <span>Enlace (Modo Oscuro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.link_color_dark, '#d4d4d8')"
                            @input="e => form.link_color_dark = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.link_color_dark"
                            @blur="form.link_color_dark = normalizarColor(form.link_color_dark, '#d4d4d8')"
                            placeholder="#d4d4d8"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1">
                        <i class="pi pi-moon text-indigo-400 text-xs" />
                        <span>Hover (Modo Oscuro)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            :value="normalizarColorPicker(form.link_hover_color_dark, '#ffffff')"
                            @input="e => form.link_hover_color_dark = e.target.value"
                            type="color"
                            class="w-10 h-10 rounded-xl cursor-pointer border border-zinc-200 dark:border-zinc-700 p-0.5 bg-transparent"
                        />
                        <InputText
                            v-model="form.link_hover_color_dark"
                            @blur="form.link_hover_color_dark = normalizarColor(form.link_hover_color_dark, '#ffffff')"
                            placeholder="#ffffff"
                            class="w-full font-mono text-xs"
                            maxlength="7"
                        />
                    </div>
                </div>
            </div>

            <!-- Controles de Decoración y Tipografía de Enlaces -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2 border-t border-zinc-200/60 dark:border-zinc-700/50">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Subrayado (Decoración)
                    </label>
                    <Select
                        v-model="form.link_decoration"
                        :options="[
                            { label: 'Subrayado solo en Hover', value: 'hover' },
                            { label: 'Siempre Subrayado', value: 'always' },
                            { label: 'Sin Subrayado', value: 'none' },
                            { label: 'Subrayado Inverso (Ocultar en Hover)', value: 'reverse_hover' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Grosor / Peso (Font Weight)
                    </label>
                    <Select
                        v-model="form.link_font_weight"
                        :options="[
                            { label: 'Heredar del texto normal', value: 'inherit' },
                            { label: 'Medio (500)', value: 'medium' },
                            { label: 'Seminegrita (600)', value: 'semibold' },
                            { label: 'Negrita (700)', value: 'bold' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Separación del Subrayado
                    </label>
                    <Select
                        v-model="form.link_underline_offset"
                        :options="[
                            { label: 'Automática (Estándar)', value: 'auto' },
                            { label: 'Separación sutil (2px)', value: '2px' },
                            { label: 'Separación amplia (4px)', value: '4px' },
                            { label: 'Separación extra (8px)', value: '8px' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Estilo de Línea
                    </label>
                    <Select
                        v-model="form.link_underline_style"
                        :options="[
                            { label: 'Continua (Solid)', value: 'solid' },
                            { label: 'Punteada (Dotted)', value: 'dotted' },
                            { label: 'Discontinua (Dashed)', value: 'dashed' },
                            { label: 'Ondulada (Wavy)', value: 'wavy' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full text-xs"
                    />
                </div>
            </div>

            <!-- Previsualización de Enlace en Texto (Claro vs Oscuro) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                <div class="p-3 bg-white text-zinc-800 rounded-xl border border-zinc-200 text-xs space-y-1">
                    <span class="font-bold block text-[10px] text-zinc-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                        <i class="pi pi-sun text-amber-500 text-[10px]" />
                        Vista Previa (Tema Claro):
                    </span>
                    Párrafo de ejemplo con un
                    <a
                        href="#"
                        @click.prevent
                        @mouseenter="hoverLight = true"
                        @mouseleave="hoverLight = false"
                        class="cursor-pointer transition-all duration-150"
                        :style="getLinkPreviewStyle(false, hoverLight)"
                    >
                        enlace interactivo en modo claro
                    </a>.
                </div>

                <div class="p-3 bg-zinc-950 text-zinc-100 rounded-xl border border-zinc-800 text-xs space-y-1">
                    <span class="font-bold block text-[10px] text-zinc-500 uppercase tracking-wider mb-1 flex items-center gap-1">
                        <i class="pi pi-moon text-indigo-400 text-[10px]" />
                        Vista Previa (Tema Oscuro):
                    </span>
                    Párrafo de ejemplo con un
                    <a
                        href="#"
                        @click.prevent
                        @mouseenter="hoverDark = true"
                        @mouseleave="hoverDark = false"
                        class="cursor-pointer transition-all duration-150"
                        :style="getLinkPreviewStyle(true, hoverDark)"
                    >
                        enlace interactivo en modo oscuro
                    </a>.
                </div>
            </div>
        </div>
    </section>
</template>
