<script setup>
import draggable from 'vuedraggable';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    fields: {
        type: Array,
        required: true,
    },
    selectedIndex: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['update:fields', 'select', 'duplicate', 'delete']);

function onUpdateList(newList) {
    emit('update:fields', newList);
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                Lienzo del Formulario (Arrastra para reordenar)
            </h3>
            <span class="text-xs text-zinc-400 font-mono">
                {{ fields.length }} elementos
            </span>
        </div>

        <div v-if="fields.length === 0" class="p-8 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl">
            <i class="pi pi-inbox text-3xl text-zinc-400 mb-2 block" />
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                El formulario aún no tiene campos
            </p>
            <p class="text-xs text-zinc-400 mt-1">
                Haz clic en un tipo de la columna izquierda para añadirlo.
            </p>
        </div>

        <!-- Lista interactiva vuedraggable -->
        <draggable
            :model-value="fields"
            @update:model-value="onUpdateList"
            item-key="id"
            animation="200"
            handle=".field-drag-handle"
            ghost-class="opacity-40"
            class="space-y-2.5"
        >
            <template #item="{ element: field, index }">
                <div
                    @click="emit('select', index)"
                    class="p-3.5 rounded-xl border transition-all cursor-pointer shadow-xs flex items-start justify-between gap-3"
                    :class="selectedIndex === index
                        ? 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20 ring-1 ring-emerald-500'
                        : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-800/80 hover:border-zinc-300 dark:hover:border-zinc-700'"
                >
                    <!-- Drag Handle e Información -->
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <div
                            class="field-drag-handle text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-grab active:cursor-grabbing p-1 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors shrink-0 mt-0.5"
                            title="Arrastrar para ordenar"
                        >
                            <i class="pi pi-bars text-sm" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ field.label || field.content || `[${field.type}]` }}
                                </span>
                                <span v-if="field.required" class="text-red-500 text-xs font-bold" title="Campo obligatorio">*</span>
                                <Tag :value="field.type" severity="secondary" class="!text-[10px] !py-0 !px-1.5" />
                                <span v-if="field.width && field.width !== 'full'" class="text-[10px] text-zinc-400 font-mono">
                                    ({{ field.width === 'half' ? '50%' : '33%' }})
                                </span>
                                <span v-if="field.conditional?.field" class="text-[10px] text-amber-600 dark:text-amber-400 font-mono" title="Lógica condicional activa">
                                    ⚡ condicional
                                </span>
                            </div>

                            <div class="flex items-center gap-2 mt-1">
                                <span v-if="field.name" class="text-[11px] font-mono text-zinc-500 dark:text-zinc-400 truncate">
                                    key: {{ field.name }}
                                </span>
                                <span v-if="field.placeholder" class="text-[11px] text-zinc-400 truncate">
                                    • «{{ field.placeholder }}»
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones del campo -->
                    <div class="flex items-center gap-1 shrink-0" @click.stop>
                        <Button
                            icon="pi pi-copy"
                            severity="secondary"
                            text
                            rounded
                            size="small"
                            @click="emit('duplicate', index)"
                            title="Duplicar campo"
                        />
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="emit('delete', index)"
                            title="Eliminar campo"
                        />
                    </div>
                </div>
            </template>
        </draggable>
    </div>
</template>
