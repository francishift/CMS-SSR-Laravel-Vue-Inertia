<script setup>
/**
 * Componente IdentidadSection: Nombre del sitio, descripción, logotipos (modo claro y oscuro) con filtros de brillo/inversión y favicons.
 */
import { computed } from 'vue';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import ImageUploader from '@/Components/Admin/ImageUploader.vue';

const props = defineProps({
    form: { type: Object, required: true },
});

// Previsualizaciones de filtro y altura de logo en vivo
const estiloFiltroLogoClaro = computed(() => {
    const filtros = [];
    if (props.form.logo_invert) filtros.push('invert(100%)');
    if (props.form.logo_brightness && Number(props.form.logo_brightness) !== 100) {
        filtros.push(`brightness(${props.form.logo_brightness}%)`);
    }
    return {
        height: `${Number(props.form.logo_height) || 36}px`,
        ...(filtros.length ? { filter: filtros.join(' ') } : {}),
    };
});

const estiloFiltroLogoOscuro = computed(() => {
    const filtros = [];
    if (props.form.logo_dark_invert) filtros.push('invert(100%)');
    if (props.form.logo_dark_brightness && Number(props.form.logo_dark_brightness) !== 100) {
        filtros.push(`brightness(${props.form.logo_dark_brightness}%)`);
    }
    return {
        height: `${Number(props.form.logo_height) || 36}px`,
        ...(filtros.length ? { filter: filtros.join(' ') } : {}),
    };
});
</script>

<template>
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                <i class="pi pi-globe" />
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Identidad Corporativa & Marca</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre público, descripción del sitio y gestión de logotipos</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Nombre del sitio *</label>
                <InputText v-model="form.site_name" class="w-full text-xs" placeholder="ej. CMS SSR" />
                <p v-if="form.errors.site_name" class="text-red-500 text-xs mt-1">{{ form.errors.site_name }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Descripción corta corporativa</label>
                <InputText v-model="form.site_description" class="w-full text-xs" placeholder="ej. Plataforma de contenidos optimizada para SEO" />
            </div>
        </div>

        <!-- Gestión de Logotipos (Modo Claro & Modo Oscuro) en 2 Columnas -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
            <div>
                <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Logotipos de la Cabecera (Modo Claro vs Modo Oscuro)</h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Configure el tamaño, la imagen, inversión a blanco y brillo según el tema que el visitante esté usando.</p>
            </div>

            <!-- Control de Tamaño del Logotipo (Escritorio y Móvil) -->
            <div class="p-4 bg-zinc-50/70 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <i class="pi pi-desktop text-xs text-zinc-500 dark:text-zinc-400" />
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Altura del Logo en Escritorio</span>
                        </div>
                        <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-bold px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/70">{{ form.logo_height || 36 }}px</span>
                    </div>
                    <input
                        type="range"
                        min="16"
                        max="100"
                        step="2"
                        v-model.number="form.logo_height"
                        class="w-full accent-emerald-600 dark:accent-emerald-500 cursor-pointer h-1.5"
                    />
                    <div class="flex justify-between text-[10px] text-zinc-400 mt-1">
                        <span>16px</span>
                        <span>Estándar: 36px</span>
                        <span>100px</span>
                    </div>
                    <p v-if="form.errors.logo_height" class="text-red-500 text-xs mt-1">{{ form.errors.logo_height }}</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <i class="pi pi-mobile text-xs text-zinc-500 dark:text-zinc-400" />
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Altura del Logo en Móvil</span>
                        </div>
                        <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-bold px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/70">{{ form.logo_height_mobile || 32 }}px</span>
                    </div>
                    <input
                        type="range"
                        min="16"
                        max="64"
                        step="2"
                        v-model.number="form.logo_height_mobile"
                        class="w-full accent-emerald-600 dark:accent-emerald-500 cursor-pointer h-1.5"
                    />
                    <div class="flex justify-between text-[10px] text-zinc-400 mt-1">
                        <span>16px</span>
                        <span>Estándar: 32px</span>
                        <span>64px</span>
                    </div>
                    <p v-if="form.errors.logo_height_mobile" class="text-red-500 text-xs mt-1">{{ form.errors.logo_height_mobile }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- BLOQUE 1: LOGO MODO CLARO -->
                <div class="p-4 bg-zinc-50/70 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60 space-y-3 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 border-b border-zinc-200/80 dark:border-zinc-700/60 pb-2">
                            <i class="pi pi-sun text-zinc-600 dark:text-zinc-400 text-sm" />
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Logo en Modo Claro</span>
                        </div>

                        <ImageUploader
                            v-model="form.logo_path"
                            label="Imagen del Logo (Fondo Claro)"
                            help="Se mostrará cuando la web esté en tema claro."
                            :show-preview="false"
                        />
                    </div>

                    <!-- Previsualización y Filtros en Fondo Claro -->
                    <div class="p-3 bg-white rounded-lg border border-zinc-200 space-y-3 mt-2">
                        <div class="flex items-center justify-start py-1.5 min-h-[48px] bg-zinc-50/50 rounded p-2 overflow-hidden">
                            <img v-if="form.logo_path || form.logo_dark_path" :src="form.logo_path || form.logo_dark_path" :alt="form.site_name" :style="estiloFiltroLogoClaro" class="w-auto object-contain transition-all" />
                            <span v-else class="text-xs text-zinc-400 italic">Sin logotipo seleccionado</span>
                        </div>

                        <div class="space-y-2.5 pt-2 border-t border-zinc-100">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="block text-[11px] font-bold text-zinc-800">Convertir a Blanco Puro</span>
                                    <span class="block text-[10px] text-zinc-400">Inversión de color en tema claro</span>
                                </div>
                                <ToggleSwitch v-model="form.logo_invert" />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[11px] font-bold text-zinc-800">Brillo del Logo</span>
                                    <span class="text-[10px] font-mono text-emerald-600 font-bold px-1.5 py-0.5 rounded bg-emerald-50">{{ form.logo_brightness }}%</span>
                                </div>
                                <input type="range" min="0" max="200" step="5" v-model.number="form.logo_brightness" class="w-full accent-emerald-600 cursor-pointer h-1.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BLOQUE 2: LOGO MODO OSCURO -->
                <div class="p-4 bg-zinc-900 rounded-xl border border-zinc-800 space-y-3 text-zinc-100 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 border-b border-zinc-800 pb-2">
                            <i class="pi pi-moon text-zinc-400 text-sm" />
                            <span class="text-xs font-bold text-zinc-100">Logo en Modo Oscuro</span>
                        </div>

                        <ImageUploader
                            v-model="form.logo_dark_path"
                            label="Imagen del Logo (Fondo Oscuro)"
                            help="Opcional. Si no se sube, usará el logo de modo claro."
                            label-class="text-zinc-200"
                            help-class="text-zinc-400"
                            :show-preview="false"
                        />
                    </div>

                    <!-- Previsualización y Filtros en Fondo Oscuro -->
                    <div class="p-3 bg-zinc-950 rounded-lg border border-zinc-800 space-y-3 mt-2">
                        <div class="flex items-center justify-start py-1.5 min-h-[48px] bg-zinc-900/50 rounded p-2 overflow-hidden">
                            <img v-if="form.logo_dark_path || form.logo_path" :src="form.logo_dark_path || form.logo_path" :alt="form.site_name" :style="estiloFiltroLogoOscuro" class="w-auto object-contain transition-all" />
                            <span v-else class="text-xs text-zinc-500 italic">Sin logotipo seleccionado</span>
                        </div>

                        <div class="space-y-2.5 pt-2 border-t border-zinc-800">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="block text-[11px] font-bold text-zinc-200">Convertir a Blanco Puro</span>
                                    <span class="block text-[10px] text-zinc-400">Recomendado para logos oscuros en fondos negros</span>
                                </div>
                                <ToggleSwitch v-model="form.logo_dark_invert" />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[11px] font-bold text-zinc-200">Brillo del Logo</span>
                                    <span class="text-[10px] font-mono text-emerald-400 font-bold px-1.5 py-0.5 rounded bg-emerald-950/80">{{ form.logo_dark_brightness }}%</span>
                                </div>
                                <input type="range" min="0" max="200" step="5" v-model.number="form.logo_dark_brightness" class="w-full accent-emerald-500 cursor-pointer h-1.5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Favicon y Apple Touch Icon del Sitio con Biblioteca de Medios -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-1 md:grid-cols-2 gap-6">
            <ImageUploader
                v-model="form.favicon_path"
                label="Favicon"
                help="ICO, PNG o SVG (32×32 o 64×64 px)"
            />
            <ImageUploader
                v-model="form.apple_touch_icon_path"
                label="Apple Touch Icon"
                help="PNG o SVG (180×180 px recomendado para accesos directos de iOS/Safari)"
            />
        </div>
    </section>
</template>
