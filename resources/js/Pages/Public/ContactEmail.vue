<script setup>
/**
 * Vista Pública de Contacto por Correo Seguro.
 *
 * Muestra una tarjeta elegante y protegida contra scrapers cuando
 * un usuario navega a /email o /mail. Permite abrir el cliente nativo
 * o copiar la dirección al portapapeles en 1 clic.
 */
import PublicLayout from '@/Frontend/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    email: { type: String, default: '' },
    siteName: { type: String, default: 'CMS' },
    whatsapp: { type: Boolean, default: false },
    phone: { type: String, default: '' },
});

const copiado = ref(false);

const volverAtras = () => {
    if (typeof window !== 'undefined') {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '/';
        }
    }
};

const copiarCorreo = async () => {
    if (!props.email) return;
    try {
        await navigator.clipboard.writeText(props.email);
        copiado.value = true;
        setTimeout(() => {
            copiado.value = false;
        }, 2500);
    } catch {
        // Fallback clásico
        const el = document.createElement('textarea');
        el.value = props.email;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        copiado.value = true;
        setTimeout(() => {
            copiado.value = false;
        }, 2500);
    }
};

onMounted(() => {
    // Intento suave y no bloqueante de lanzar el mailto nativo tras la carga
    if (props.email && typeof window !== 'undefined') {
        const timeout = setTimeout(() => {
            try {
                window.location.href = `mailto:${props.email}`;
            } catch (_) {}
        }, 350);
    }
});
</script>

<template>
    <Head :title="`Contacto por Correo — ${siteName}`">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div class="min-h-[70vh] flex items-center justify-center px-4 py-16 sm:py-24">
        <div class="w-full max-w-lg bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 p-6 sm:p-8 shadow-2xl shadow-zinc-950/5 relative overflow-hidden text-center space-y-6">
            
            <!-- Resplandor decorativo de fondo -->
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-72 h-72 bg-[var(--primary-color,#10b981)]/10 rounded-full blur-3xl pointer-events-none" />

            <!-- Barra Superior: Botón Volver Atrás visual -->
            <div class="flex items-center justify-start relative z-10">
                <button
                    type="button"
                    @click="volverAtras"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-semibold border border-zinc-200 dark:border-zinc-700/80 shadow-xs transition-all cursor-pointer group"
                    title="Volver a la página anterior"
                >
                    <i class="pi pi-arrow-left text-[11px] transition-transform group-hover:-translate-x-1" />
                    <span>Volver atrás</span>
                </button>
            </div>

            <!-- Icono cabecera -->
            <div class="relative mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-[var(--primary-color,#10b981)]/20 to-[var(--primary-color,#10b981)]/5 border border-[var(--primary-color,#10b981)]/30 flex items-center justify-center text-[var(--primary-color,#10b981)] text-2xl shadow-inner">
                <i class="pi pi-envelope" />
            </div>

            <!-- Títulos -->
            <div class="space-y-1.5 relative">
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight" style="font-family: var(--heading-font), system-ui, sans-serif;">
                    Contacto por Correo
                </h1>
                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 max-w-md mx-auto leading-relaxed">
                    Escríbenos directamente o copia nuestra dirección para usarla en tu servicio de correo favorito.
                </p>
            </div>

            <!-- Caja destacada con la dirección y botón copiar -->
            <div v-if="email" class="bg-zinc-50 dark:bg-zinc-950/60 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-3 min-w-0 px-2">
                    <i class="pi pi-at text-zinc-400 dark:text-zinc-500 shrink-0" />
                    <span class="font-mono text-sm sm:text-base font-semibold text-zinc-900 dark:text-zinc-100 truncate select-all">
                        {{ email }}
                    </span>
                </div>
                <button
                    type="button"
                    @click="copiarCorreo"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer shadow-xs"
                    :class="copiado
                        ? 'bg-emerald-600 text-white shadow-emerald-500/20'
                        : 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700'"
                >
                    <i :class="copiado ? 'pi pi-check' : 'pi pi-copy'" />
                    <span>{{ copiado ? '¡Copiado!' : 'Copiar' }}</span>
                </button>
            </div>

            <div v-else class="p-6 bg-amber-50 dark:bg-amber-950/30 rounded-2xl border border-amber-200 dark:border-amber-900/40 text-amber-800 dark:text-amber-300 text-sm">
                No hay una dirección de correo configurada actualmente.
            </div>

            <!-- Acciones Principales -->
            <div class="space-y-3 pt-2">
                <a
                    v-if="email"
                    :href="`mailto:${email}`"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-[var(--btn-primary-bg,var(--primary-color,#10b981))] text-[var(--btn-primary-text,#ffffff)] font-bold text-sm shadow-lg shadow-[var(--primary-color,#10b981)]/25 hover:opacity-95 transition-all cursor-pointer"
                >
                    <i class="pi pi-external-link" />
                    <span>Abrir mi aplicación de correo</span>
                </a>

                <a
                    v-if="whatsapp"
                    href="/whatsapp"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold text-sm hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all"
                >
                    <i class="pi pi-whatsapp text-lg" />
                    <span>O prefiero escribir por WhatsApp</span>
                </a>
            </div>

            <!-- Enlace Ir a inicio -->
            <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800/80">
                <Link
                    href="/"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors"
                >
                    <i class="pi pi-home text-xs" />
                    <span>Ir a inicio</span>
                </Link>
            </div>

        </div>
    </div>
</template>
