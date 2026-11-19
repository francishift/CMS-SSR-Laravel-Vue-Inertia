<script setup>
import { ref, computed } from 'vue';

const emit = defineEmits(['add-field']);

// Catálogo de los 23 tipos de campos soportados por el motor
const catalogoTipos = [
    // Texto
    { type: 'text', label: 'Texto corto', icon: 'pi pi-pencil', categoria: 'Texto', desc: 'Nombre, asunto, ciudad' },
    { type: 'textarea', label: 'Texto largo', icon: 'pi pi-align-left', categoria: 'Texto', desc: 'Mensaje, comentarios' },
    { type: 'email', label: 'Correo electrónico', icon: 'pi pi-envelope', categoria: 'Texto', desc: 'Email con validación de sintaxis' },
    { type: 'tel', label: 'Teléfono', icon: 'pi pi-phone', categoria: 'Texto', desc: 'Número de teléfono o móvil' },
    { type: 'url', label: 'Enlace Web / URL', icon: 'pi pi-link', categoria: 'Texto', desc: 'Dirección web https://' },
    { type: 'password', label: 'Contraseña', icon: 'pi pi-key', categoria: 'Texto', desc: 'Campo de texto oculto' },
    { type: 'number', label: 'Número', icon: 'pi pi-calculator', categoria: 'Texto', desc: 'Cantidad, edad, presupuesto' },

    // Selección
    { type: 'select', label: 'Desplegable (Select)', icon: 'pi pi-list', categoria: 'Selección', desc: 'Selección única de lista' },
    { type: 'multiselect', label: 'Selección Múltiple', icon: 'pi pi-check-square', categoria: 'Selección', desc: 'Múltiples opciones seleccionables' },
    { type: 'radio', label: 'Botones de opción (Radio)', icon: 'pi pi-circle', categoria: 'Selección', desc: 'Opción única visible' },
    { type: 'checkbox', label: 'Casilla de verificación', icon: 'pi pi-check', categoria: 'Selección', desc: 'Aceptación de términos o múltiple' },
    { type: 'toggle', label: 'Interruptor (Sí / No)', icon: 'pi pi-sliders-h', categoria: 'Selección', desc: 'Interruptor booleano on/off' },

    // Fecha / Hora
    { type: 'date', label: 'Fecha', icon: 'pi pi-calendar', categoria: 'Fecha / Hora', desc: 'Selector de día/mes/año' },
    { type: 'datetime', label: 'Fecha y Hora', icon: 'pi pi-calendar-plus', categoria: 'Fecha / Hora', desc: 'Cita con día y horario' },
    { type: 'time', label: 'Hora', icon: 'pi pi-clock', categoria: 'Fecha / Hora', desc: 'Horario específico' },

    // Especiales
    { type: 'file', label: 'Subida de Archivo', icon: 'pi pi-upload', categoria: 'Especiales', desc: 'Adjuntar documento o imagen' },
    { type: 'rating', label: 'Puntuación (Estrellas)', icon: 'pi pi-star', categoria: 'Especiales', desc: 'Valoración de 1 a 5' },
    { type: 'slider', label: 'Deslizador (Slider)', icon: 'pi pi-arrows-h', categoria: 'Especiales', desc: 'Rango numérico continuo' },
    { type: 'color', label: 'Selector de Color', icon: 'pi pi-palette', categoria: 'Especiales', desc: 'Código de color hexadecimal' },
    { type: 'hidden', label: 'Campo Oculto (Hidden)', icon: 'pi pi-eye-slash', categoria: 'Especiales', desc: 'UTM, parámetros internos' },

    // Estructura / Decorativos
    { type: 'heading', label: 'Título de Sección', icon: 'pi pi-bookmark', categoria: 'Estructura', desc: 'Encabezado visual' },
    { type: 'paragraph', label: 'Párrafo Informativo', icon: 'pi pi-info-circle', categoria: 'Estructura', desc: 'Texto de ayuda o legal' },
    { type: 'separator', label: 'Línea divisoria', icon: 'pi pi-minus', categoria: 'Estructura', desc: 'Separador horizontal' },
];

const categoriasPaleta = ['Todos', 'Texto', 'Selección', 'Fecha / Hora', 'Especiales', 'Estructura'];
const categoriaSeleccionada = ref('Todos');

const tiposFiltrados = computed(() => {
    if (categoriaSeleccionada.value === 'Todos') return catalogoTipos;
    return catalogoTipos.filter(t => t.categoria === categoriaSeleccionada.value);
});
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-700/80 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                    Campos Disponibles
                </h3>
                <span class="text-[10px] text-zinc-400 font-mono">23 tipos</span>
            </div>

            <!-- Filtro de categorías de la paleta -->
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="cat in categoriasPaleta"
                    :key="cat"
                    type="button"
                    @click="categoriaSeleccionada = cat"
                    class="px-2 py-0.5 text-[11px] rounded-md transition-colors"
                    :class="categoriaSeleccionada === cat
                        ? 'bg-emerald-600 text-white font-medium shadow-xs'
                        : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700 hover:border-zinc-300'"
                >
                    {{ cat }}
                </button>
            </div>

            <!-- Lista de botones de campos disponibles -->
            <div class="space-y-1.5 max-h-[500px] overflow-y-auto pr-1">
                <button
                    v-for="tipo in tiposFiltrados"
                    :key="tipo.type"
                    type="button"
                    @click="emit('add-field', tipo.type)"
                    class="w-full text-left p-2 rounded-lg border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-xs transition-all flex items-center justify-between group cursor-pointer"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <i :class="tipo.icon" class="text-sm text-zinc-400 group-hover:text-emerald-500 shrink-0" />
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200 block truncate">
                                {{ tipo.label }}
                            </span>
                            <span class="text-[10px] text-zinc-400 block truncate">
                                {{ tipo.desc }}
                            </span>
                        </div>
                    </div>
                    <i class="pi pi-plus text-xs text-zinc-400 group-hover:text-emerald-500 shrink-0" />
                </button>
            </div>
        </div>
    </div>
</template>
