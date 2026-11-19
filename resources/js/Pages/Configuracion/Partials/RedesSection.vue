<script setup>
/**
 * Componente RedesSection: Contacto Directo Seguro y Perfiles de Redes Sociales.
 *
 * Incluye configuración dedicada para WhatsApp con mensaje predeterminado,
 * teléfono y correo de contacto, protegidos mediante rutas de escudo internas.
 */
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
});

// Previsualización reactiva de la URL de WhatsApp resultante
const previewWhatsappUrl = computed(() => {
    const rawPhone = props.form.whatsapp_phone || '';
    const cleanPhone = rawPhone.replace(/[^0-9]/g, '');
    if (!cleanPhone) return null;

    const finalPhone = (cleanPhone.length === 9 && ['6', '7', '8', '9'].includes(cleanPhone[0]))
        ? `34${cleanPhone}`
        : cleanPhone;

    const msg = props.form.whatsapp_message || 'Hola, necesito hacer una consulta';
    return `https://api.whatsapp.com/send?phone=${finalPhone}&text=${encodeURIComponent(msg)}`;
});
</script>

<template>
    <div class="space-y-6">

        <!-- SECCIÓN 1: CANALES DE CONTACTO DIRECTO BLINDADOS (ANTI-BOT) -->
        <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-emerald-200/80 dark:border-emerald-900/40 p-6 shadow-xs space-y-6 relative overflow-hidden">
            <!-- Resplandor sutil de fondo -->
            <div class="absolute -top-16 -right-16 w-48 h-48 bg-emerald-500/5 rounded-full blur-2xl pointer-events-none" />

            <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg shadow-inner">
                    <i class="pi pi-shield" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>Contacto Directo Seguro</span>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                            Anti-Scraping Activo
                        </span>
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Configura aquí tus canales principales. Tu número y correo no se exponen a los spambots y puedes enlazarlos en toda la web usando <code class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">/whatsapp</code>, <code class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">/email</code> y <code class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">/llamar</code>.
                    </p>
                </div>
            </div>

            <!-- BLOQUE DEDICADO A WHATSAPP -->
            <div class="p-5 bg-zinc-50 dark:bg-zinc-950/50 rounded-xl border border-zinc-200 dark:border-zinc-800/80 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-whatsapp text-emerald-600 dark:text-emerald-400 text-lg" />
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                            WhatsApp Oficial & Mensaje Automático
                        </span>
                    </div>
                    <span class="text-[11px] font-mono text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 px-2 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800">
                        Atajo: <strong class="text-emerald-600">/whatsapp</strong>
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Número de Teléfono -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                            Número de WhatsApp
                        </label>
                        <InputText
                            v-model="form.whatsapp_phone"
                            class="w-full text-xs"
                            placeholder="Ej: 608894284 o 34608894284"
                        />
                        <p class="text-[11px] text-zinc-500 mt-1">
                            No es necesario poner el signo +. Si introduces 9 dígitos de España, se añade el prefijo 34 automáticamente.
                        </p>
                    </div>

                    <!-- Mensaje Predeterminado -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                            Mensaje Predeterminado de Bienvenida
                        </label>
                        <InputText
                            v-model="form.whatsapp_message"
                            class="w-full text-xs"
                            placeholder="Ej: Hola, necesito hacer una consulta"
                        />
                        <p class="text-[11px] text-zinc-500 mt-1">
                            El texto que le aparecerá precargado al usuario en su WhatsApp listo para enviar.
                        </p>
                    </div>
                </div>

                <!-- Previsualización de la URL generada -->
                <div v-if="previewWhatsappUrl" class="pt-2 border-t border-zinc-200/60 dark:border-zinc-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px]">
                    <div class="text-zinc-600 dark:text-zinc-400 flex items-center gap-1.5 min-w-0">
                        <i class="pi pi-link text-emerald-500 shrink-0" />
                        <span class="truncate font-mono select-all">{{ previewWhatsappUrl }}</span>
                    </div>
                    <a
                        :href="previewWhatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-bold inline-flex items-center gap-1 shrink-0"
                    >
                        <span>Probar apertura</span>
                        <i class="pi pi-external-link text-[10px]" />
                    </a>
                </div>
            </div>

            <!-- BLOQUE EMAIL Y TELÉFONO -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Correo Electrónico -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-950/50 rounded-xl border border-zinc-200 dark:border-zinc-800/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                            <i class="pi pi-envelope text-indigo-500" />
                            <span>Email de Contacto</span>
                        </label>
                        <span class="text-[10px] font-mono text-zinc-400">Atajo: <strong class="text-indigo-500">/email</strong></span>
                    </div>
                    <InputText
                        v-model="form.contact_email"
                        type="email"
                        class="w-full text-xs"
                        placeholder="contacto@tudominio.com"
                    />
                    <p class="text-[11px] text-zinc-500">
                        Al enlazar a <code class="text-indigo-500 font-mono">/email</code>, se abre el gestor de correo del usuario o una tarjeta protegida para copiar en 1 clic.
                    </p>
                </div>

                <!-- Teléfono de Llamadas -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-950/50 rounded-xl border border-zinc-200 dark:border-zinc-800/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                            <i class="pi pi-phone text-blue-500" />
                            <span>Teléfono para Llamadas</span>
                        </label>
                        <span class="text-[10px] font-mono text-zinc-400">Atajo: <strong class="text-blue-500">/llamar</strong></span>
                    </div>
                    <InputText
                        v-model="form.contact_phone"
                        class="w-full text-xs"
                        placeholder="+34 600 000 000"
                    />
                    <p class="text-[11px] text-zinc-500">
                        Al enlazar a <code class="text-blue-500 font-mono">/llamar</code>, en móviles se abre el marcador telefónico sin exponer el número a scrapers.
                    </p>
                </div>
            </div>
        </section>

        <!-- SECCIÓN 2: PERFILES DE REDES SOCIALES OFICIALES -->
        <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
            <div class="flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div class="w-10 h-10 rounded-xl bg-pink-100 dark:bg-pink-950/60 text-pink-600 dark:text-pink-400 flex items-center justify-center text-lg">
                    <i class="pi pi-share-alt" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Redes Sociales & Perfiles Oficiales</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">URLs hacia perfiles sociales oficiales que se muestran en la cabecera y pie de página</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">GitHub</label>
                    <InputText v-model="form.social_links.github" class="w-full text-xs" placeholder="https://github.com/usuario" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">X (Twitter)</label>
                    <InputText v-model="form.social_links.twitter" class="w-full text-xs" placeholder="https://x.com/usuario" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Instagram</label>
                    <InputText v-model="form.social_links.instagram" class="w-full text-xs" placeholder="https://instagram.com/usuario" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">LinkedIn</label>
                    <InputText v-model="form.social_links.linkedin" class="w-full text-xs" placeholder="https://linkedin.com/in/usuario" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">YouTube</label>
                    <InputText v-model="form.social_links.youtube" class="w-full text-xs" placeholder="https://youtube.com/@canal" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Facebook</label>
                    <InputText v-model="form.social_links.facebook" class="w-full text-xs" placeholder="https://facebook.com/pagina" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1.5">
                        <i class="pi pi-whatsapp text-emerald-600 dark:text-emerald-400" />
                        <span>WhatsApp (Icono Red Social)</span>
                    </label>
                    <InputText v-model="form.social_links.whatsapp" class="w-full text-xs" placeholder="/whatsapp o https://wa.me/..." />
                    <p class="text-[11px] text-zinc-500 mt-1">
                        Si rellenas este campo, se mostrará el icono de WhatsApp junto a tus demás botones de redes sociales. Puedes escribir <code class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">/whatsapp</code> para enlazar con tu canal protegido configurado arriba.
                    </p>
                </div>
            </div>
        </section>

    </div>
</template>
