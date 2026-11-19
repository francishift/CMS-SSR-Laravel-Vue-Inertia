<script setup>
/**
 * Editor WYSIWYG reutilizable basado en PrimeVue Editor (Quill).
 *
 * Funcionalidades:
 * - Desplegable en español con H1, H2, H3, H4 y Párrafo Normal.
 * - Subida de imágenes directa a servidor vía AJAX (/panel/media/upload).
 * - Sanitización automática al pegar contenido externo (Word, Google Docs, etc.).
 * - Vista de código fuente HTML alternativa (toggle visual ↔ HTML).
 * - Inserción segura y sincronización v-model limpia con PrimeVue Toast.
 */
import Editor from 'primevue/editor';
import axios from 'axios';
import { ref, watch, nextTick } from 'vue';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Escribe el contenido...' },
    height: { type: String, default: '240px' },
});

const emit = defineEmits(['update:modelValue']);
const editorRef = ref(null);
const toast = useToast();

// --- Vista código fuente ---
const vistaCodigoFuente = ref(false);
const codigoHtml = ref('');

function normalizarHtml(val) {
    if (!val || typeof val !== 'string') return val;
    // Reemplazar entidades &nbsp; y caracteres \u00a0 (no-break space) por espacios normales
    return val.replace(/&nbsp;/g, ' ').replace(/\u00a0/g, ' ');
}

function toggleCodigoFuente() {
    if (!vistaCodigoFuente.value) {
        // Cambiar a vista código: copiar el HTML actual normalizado
        codigoHtml.value = normalizarHtml(props.modelValue || '');
    } else {
        // Volver a vista visual: aplicar el HTML editado
        const limpio = normalizarHtml(codigoHtml.value);
        emit('update:modelValue', limpio);
        // Esperar al siguiente tick para que Quill se actualice con el nuevo valor
        nextTick(() => {
            const quill = getQuillInstance();
            if (quill) {
                quill.root.innerHTML = limpio;
            }
        });
    }
    vistaCodigoFuente.value = !vistaCodigoFuente.value;
}

function onEditorInput(val) {
    emit('update:modelValue', normalizarHtml(val));
}

function onCodigoInput(event) {
    codigoHtml.value = event.target.value;
}

// --- Acceso a Quill ---
function getQuillInstance() {
    if (!editorRef.value) return null;
    if (typeof editorRef.value.getQuill === 'function') {
        return editorRef.value.getQuill();
    }
    return editorRef.value.quill || null;
}

// --- Sanitización al pegar ---
/**
 * Limpia HTML pegado de fuentes externas (Word, Google Docs, etc.):
 * - Elimina &nbsp; excesivos (reemplaza por espacio normal)
 * - Elimina estilos inline (style="...")
 * - Elimina clases de Word/Docs (class="Mso...", class="s1", etc.)
 * - Elimina atributos data-*
 * - Elimina tags vacíos innecesarios (<span> sin contenido útil)
 * - Limpia comentarios condicionales de MS Office (if gte mso)
 */
function sanitizarHtmlPegado(html) {
    let limpio = html;

    // Eliminar comentarios condicionales de MS Office
    limpio = limpio.replace(/<!--\[if[\s\S]*?<!\[endif\]-->/gi, '');
    limpio = limpio.replace(/<!--[\s\S]*?-->/g, '');

    // Eliminar tags de XML de Office (<o:p>, <w:sdt>, etc.)
    limpio = limpio.replace(/<\/?\w+:[^>]*>/gi, '');

    // Eliminar atributos style
    limpio = limpio.replace(/\s+style="[^"]*"/gi, '');
    limpio = limpio.replace(/\s+style='[^']*'/gi, '');

    // Eliminar atributos class (especialmente de Word/Docs)
    limpio = limpio.replace(/\s+class="[^"]*"/gi, '');
    limpio = limpio.replace(/\s+class='[^']*'/gi, '');

    // Eliminar atributos data-*
    limpio = limpio.replace(/\s+data-[a-z-]+="[^"]*"/gi, '');

    // Eliminar atributos de MS Office (lang, dir superfluos en spans)
    limpio = limpio.replace(/\s+lang="[^"]*"/gi, '');

    // Reemplazar &nbsp; por espacios normales
    limpio = limpio.replace(/&nbsp;/g, ' ');
    // También la entidad numérica
    limpio = limpio.replace(/&#160;/g, ' ');

    // Eliminar <span> vacíos que no aportan nada
    limpio = limpio.replace(/<span\s*>([\s\S]*?)<\/span>/gi, '$1');

    // Colapsar espacios múltiples (pero no dentro de <pre>)
    limpio = limpio.replace(/  +/g, ' ');

    return limpio.trim();
}

/**
 * Registrar el interceptor de pegado en la instancia de Quill.
 * Se llama una vez cuando el editor está montado.
 */
function registrarSanitizadorPegado() {
    const quill = getQuillInstance();
    if (!quill) return;

    // Interceptar el evento paste del contenedor editable
    quill.root.addEventListener('paste', (e) => {
        const clipboardHtml = e.clipboardData?.getData('text/html');
        if (!clipboardHtml) return; // Texto plano, dejar que Quill lo maneje

        // Detectar si viene de un editor externo (Word, Google Docs, etc.)
        const esContenidoExterno =
            clipboardHtml.includes('urn:schemas-microsoft-com') ||
            clipboardHtml.includes('docs-internal-guid') ||
            clipboardHtml.includes('mso-') ||
            clipboardHtml.includes('class="Mso') ||
            (clipboardHtml.match(/style="/g) || []).length > 3; // Muchos estilos inline

        if (!esContenidoExterno) return; // Contenido limpio, no sanitizar

        e.preventDefault();
        e.stopPropagation();

        const htmlLimpio = sanitizarHtmlPegado(clipboardHtml);

        // Insertar como HTML sanitizado en la posición actual
        const seleccion = quill.getSelection(true);
        const indice = seleccion ? seleccion.index : quill.getLength() - 1;

        // Borrar selección actual si hay
        if (seleccion && seleccion.length > 0) {
            quill.deleteText(indice, seleccion.length);
        }

        // Insertar el HTML limpio usando el portapapeles de Quill
        quill.clipboard.dangerouslyPasteHTML(indice, htmlLimpio);

        // Sincronizar con v-model
        nextTick(() => {
            emit('update:modelValue', quill.root.innerHTML);
        });

        toast.add({
            severity: 'info',
            summary: 'Contenido limpiado',
            detail: 'Se han eliminado los estilos de formato externo.',
            life: 2500,
        });
    });
}

// Registrar sanitizador cuando el editor se monta
watch(editorRef, (newRef) => {
    if (newRef) {
        // Esperar a que Quill esté completamente inicializado
        nextTick(() => {
            setTimeout(registrarSanitizadorPegado, 200);
        });
    }
}, { once: true });

// --- Subida de imagen ---
function subirImagenServidor() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif,image/svg+xml');

    input.onchange = async () => {
        const file = input.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await axios.post(route('media.upload'), formData);
            const imageUrl = response.data?.url;

            if (imageUrl) {
                const quill = getQuillInstance();
                if (quill) {
                    let insertIndex = 0;
                    try {
                        const sel = quill.getSelection();
                        if (sel && typeof sel.index === 'number') {
                            insertIndex = sel.index;
                        } else {
                            insertIndex = Math.max(0, quill.getLength() - 1);
                        }
                    } catch (e) {
                        insertIndex = Math.max(0, quill.getLength() - 1);
                    }

                    quill.insertEmbed(insertIndex, 'image', imageUrl);
                    quill.setSelection(insertIndex + 1);

                    // Sincronizar el HTML con el estado v-model de Vue
                    const htmlActualizado = quill.root.innerHTML;
                    emit('update:modelValue', htmlActualizado);

                    toast.add({
                        severity: 'success',
                        summary: 'Imagen insertada',
                        detail: 'La imagen se ha subido e insertado en el editor correctamente.',
                        life: 3000,
                    });
                }
            }
        } catch (error) {
            const errorDetail = error.response?.data?.errors?.image?.[0]
                || error.response?.data?.message
                || error.message
                || 'No se pudo subir o insertar la imagen en el editor.';

            toast.add({
                severity: 'error',
                summary: 'Error de subida',
                detail: errorDetail,
                life: 5000,
            });
        }
    };

    input.click();
}
</script>

<template>
    <div class="wysiwyg-wrapper">
        <!-- Vista código fuente HTML -->
        <div v-if="vistaCodigoFuente" class="wysiwyg-source-view">
            <div class="source-toolbar">
                <button
                    type="button"
                    @click="toggleCodigoFuente"
                    class="source-btn source-btn-active"
                    title="Volver al editor visual"
                >
                    <i class="pi pi-eye text-xs" />
                    <span>Editor Visual</span>
                </button>
            </div>
            <textarea
                :value="codigoHtml"
                @input="onCodigoInput"
                class="source-textarea"
                :style="`height: calc(${height} + 42px)`"
                spellcheck="false"
                placeholder="Escribe o edita el HTML directamente..."
            />
        </div>

        <!-- Vista editor visual (Quill) -->
        <div v-else>
            <Editor
                ref="editorRef"
                :modelValue="modelValue"
                @update:modelValue="onEditorInput"
                :editorStyle="`height: ${height}`"
                :placeholder="placeholder"
            >
                <template #toolbar>
                    <span class="ql-formats">
                        <select class="ql-header" title="Formato de Encabezado">
                            <option value="1">Título principal (H1)</option>
                            <option value="2">Subtítulo (H2)</option>
                            <option value="3">Sección (H3)</option>
                            <option value="4">Subsección (H4)</option>
                            <option value="" selected>Párrafo Normal (P)</option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-bold" title="Negrita"></button>
                        <button class="ql-italic" title="Cursiva"></button>
                        <button class="ql-underline" title="Subrayado"></button>
                        <button class="ql-strike" title="Tachado"></button>
                    </span>
                    <span class="ql-formats">
                        <select class="ql-color" title="Color de texto"></select>
                        <select class="ql-background" title="Color de fondo"></select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered" title="Lista numerada"></button>
                        <button class="ql-list" value="bullet" title="Lista con viñetas"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-blockquote" title="Cita"></button>
                        <button class="ql-code-block" title="Bloque de código"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-link" title="Insertar enlace"></button>
                        <button type="button" @click="subirImagenServidor" title="Subir e insertar imagen" class="p-button-icon-only">
                            <i class="pi pi-image text-sm" />
                        </button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-clean" title="Limpiar formato"></button>
                    </span>
                    <!-- Botón Vista Código Fuente -->
                    <span class="ql-formats">
                        <button
                            type="button"
                            @click="toggleCodigoFuente"
                            title="Ver código fuente HTML"
                            class="p-button-icon-only custom-source-code-btn"
                        >
                            <i class="pi pi-code text-sm" />
                        </button>
                    </span>
                </template>
            </Editor>
        </div>
    </div>
</template>

<style>
/* Ajustar estilos del selector de encabezados en la barra de Quill */
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header {
    width: 170px !important;
}
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header .ql-picker-label::before {
    content: 'Formato / Título' !important;
}
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="1"]::before {
    content: 'Título principal (H1)' !important;
    font-weight: 700;
    font-size: 1.25rem;
}
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="2"]::before {
    content: 'Subtítulo (H2)' !important;
    font-weight: 600;
    font-size: 1.1rem;
}
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="3"]::before {
    content: 'Sección (H3)' !important;
    font-weight: 600;
    font-size: 1rem;
}
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="4"]::before {
    content: 'Subsección (H4)' !important;
    font-weight: 500;
    font-size: 0.95rem;
}
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header .ql-picker-item:not([data-value])::before,
.wysiwyg-wrapper .ql-snow .ql-picker.ql-header .ql-picker-item[data-value=""]::before {
    content: 'Párrafo Normal (P)' !important;
}

/* Permitir redimensionado vertical manual por el usuario */
.wysiwyg-wrapper .ql-container {
    resize: vertical !important;
    overflow-y: auto !important;
    min-height: 140px !important;
}
.wysiwyg-wrapper .ql-editor {
    min-height: 100% !important;
}

/* Estilo del botón Vista Código Fuente en la barra de Quill */
.wysiwyg-wrapper .ql-source-code {
    color: #6b7280 !important;
}
.wysiwyg-wrapper .ql-source-code:hover {
    color: #10b981 !important;
}

/* Vista código fuente */
.wysiwyg-wrapper .wysiwyg-source-view {
    border: 1px solid var(--p-content-border-color, #d1d5db);
    border-radius: 6px;
    overflow: hidden;
}

.wysiwyg-wrapper .source-toolbar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background: var(--p-surface-100, #f3f4f6);
    border-bottom: 1px solid var(--p-content-border-color, #d1d5db);
}

.dark .wysiwyg-wrapper .source-toolbar {
    background: var(--p-surface-800, #27272a);
}

.wysiwyg-wrapper .source-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 4px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    background: transparent;
    color: #6b7280;
}

.wysiwyg-wrapper .source-btn-active {
    background: #10b981;
    color: white;
    border-color: #059669;
}

.wysiwyg-wrapper .source-btn-active:hover {
    background: #059669;
}

.wysiwyg-wrapper .source-textarea {
    display: block;
    width: 100%;
    padding: 0.75rem;
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 0.8125rem;
    line-height: 1.6;
    color: #e5e7eb;
    background: #1e1e2e;
    border: none;
    outline: none;
    resize: vertical;
    tab-size: 2;
}

.wysiwyg-wrapper .source-textarea::placeholder {
    color: #6b7280;
}
</style>
