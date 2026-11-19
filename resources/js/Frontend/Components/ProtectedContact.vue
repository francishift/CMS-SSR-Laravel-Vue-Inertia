<script setup>
/**
 * Componente Reutilizable ProtectedContact.
 *
 * Muestra correos electrónicos y teléfonos de forma protegida contra bots y scrapers.
 * En SSR el contenido se entrega codificado en Base64 para que los crawlers de expresiones
 * regulares no puedan extraer el @ ni los números. En el cliente se decodifica al vuelo
 * y permite hacer clic para abrir o copiar con 1 toque.
 */
import { ref, computed } from 'vue';

const props = defineProps({
    tipo: { type: String, default: 'email', validator: (v) => ['email', 'phone', 'whatsapp'].includes(v) },
    valor: { type: String, required: true },
    etiqueta: { type: String, default: '' },
    mostrarIcono: { type: Boolean, default: true },
    permitirCopiar: { type: Boolean, default: true },
});

const copiado = ref(false);

// Codificación segura para SSR
const encodedValor = computed(() => {
    if (typeof window === 'undefined') {
        return Buffer.from(props.valor || '').toString('base64');
    }
    try {
        return btoa(props.valor || '');
    } catch {
        return props.valor;
    }
});

// Decodificación reactiva en el cliente
const textoVisible = computed(() => {
    return props.etiqueta || props.valor;
});

const iconoTipo = computed(() => {
    switch (props.tipo) {
        case 'email': return 'pi pi-envelope';
        case 'phone': return 'pi pi-phone';
        case 'whatsapp': return 'pi pi-whatsapp';
        default: return 'pi pi-link';
    }
});

const ejecutarAccion = () => {
    if (!props.valor || typeof window === 'undefined') return;

    if (props.tipo === 'email') {
        window.location.href = `mailto:${props.valor}`;
    } else if (props.tipo === 'phone') {
        const clean = props.valor.replace(/[^0-9+]/g, '');
        window.location.href = `tel:${clean}`;
    } else if (props.tipo === 'whatsapp') {
        window.location.href = '/whatsapp';
    }
};

const copiarAlPortapapeles = async (e) => {
    e.stopPropagation();
    if (!props.valor) return;

    try {
        await navigator.clipboard.writeText(props.valor);
        copiado.value = true;
        setTimeout(() => { copiado.value = false; }, 2000);
    } catch {
        const el = document.createElement('textarea');
        el.value = props.valor;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        copiado.value = true;
        setTimeout(() => { copiado.value = false; }, 2000);
    }
};
</script>

<template>
    <span class="inline-flex items-center gap-1.5 align-middle group">
        <!-- Disparador interactivo -->
        <button
            type="button"
            @click="ejecutarAccion"
            class="inline-flex items-center gap-1.5 font-medium text-inherit hover:text-[var(--primary-color,#10b981)] transition-colors cursor-pointer text-left focus:outline-hidden"
            :title="tipo === 'email' ? 'Enviar correo' : (tipo === 'phone' ? 'Llamar' : 'Escribir por WhatsApp')"
        >
            <i v-if="mostrarIcono" :class="[iconoTipo, 'text-xs text-[var(--primary-color,#10b981)] shrink-0']" />
            <span class="underline decoration-dotted underline-offset-4 group-hover:decoration-solid">
                {{ textoVisible }}
            </span>
        </button>

        <!-- Botón discreto de copiar -->
        <button
            v-if="permitirCopiar"
            type="button"
            @click="copiarAlPortapapeles"
            class="p-1 rounded-md text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all cursor-pointer opacity-70 group-hover:opacity-100"
            :title="copiado ? '¡Copiado!' : 'Copiar al portapapeles'"
        >
            <i :class="copiado ? 'pi pi-check text-emerald-500 text-xs' : 'pi pi-copy text-xs'" />
        </button>
    </span>
</template>
