<script setup>
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';

defineProps({
    form: {
        type: Object,
        required: true,
    },
    turnstileConfigured: {
        type: Boolean,
        default: false,
    },
});

</script>

<template>
    <div class="max-w-3xl space-y-6">
        <!-- Estado activo/inactivo -->
        <div class="flex items-center justify-between p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800">
            <div>
                <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100 block">Formulario Activo</span>
                <span class="text-xs text-zinc-500">Permite recibir nuevos envíos públicos a través de la web</span>
            </div>
            <ToggleSwitch v-model="form.is_active" />
        </div>

        <!-- Honeypot invisible -->
        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 space-y-3">
            <div class="flex items-center gap-2">
                <i class="pi pi-shield text-emerald-600 dark:text-emerald-400" />
                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                    Honeypot Anti-Spam (Campo Trampa Invisible)
                </h4>
            </div>
            <p class="text-xs text-zinc-500">
                Crea un campo invisible para los usuarios humanos. Los bots de spam lo rellenan automáticamente y el sistema rechaza su envío en silencio sin molestar al visitante legítimo.
            </p>
            <div class="space-y-1 max-w-sm">
                <label class="text-xs text-zinc-500 font-medium">Nombre del campo trampa</label>
                <InputText v-model="form.honeypot_field" placeholder="website_url" class="w-full text-xs font-mono" />
            </div>
        </div>

        <!-- Cloudflare Turnstile -->
        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 space-y-4">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-lock text-blue-600 dark:text-blue-400" />
                        <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Cloudflare Turnstile (CAPTCHA)</span>
                    </div>
                    <p class="text-xs text-zinc-500 max-w-lg">
                        Verificación inteligente que detiene ataques automatizados sin obligar al usuario a resolver puzzles molestos.
                    </p>
                </div>
                <ToggleSwitch v-model="form.requires_captcha" />
            </div>

            <!-- Aviso de configuración .env (solo visible si faltan las claves) -->
            <div v-if="!turnstileConfigured" class="flex items-start gap-2.5 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50">
                <i class="pi pi-info-circle text-amber-600 dark:text-amber-400 mt-0.5 shrink-0" />
                <div class="text-xs text-amber-800 dark:text-amber-300 space-y-1">
                    <p class="font-semibold">Requiere configuración en el servidor</p>
                    <p>
                        Para que Turnstile funcione, debes añadir estas dos variables en el archivo
                        <code class="bg-amber-100 dark:bg-amber-900/40 px-1 py-0.5 rounded font-mono text-[11px]">.env</code>
                        del proyecto:
                    </p>
                    <div class="bg-amber-100/80 dark:bg-amber-900/40 rounded p-2 font-mono text-[11px] leading-5 mt-1">
                        TURNSTILE_SITE_KEY=tu_clave_de_sitio<br>
                        TURNSTILE_SECRET_KEY=tu_clave_secreta
                    </div>
                    <p>
                        Obtén las claves en
                        <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" class="underline font-medium hover:text-amber-900 dark:hover:text-amber-200">
                            dash.cloudflare.com → Turnstile
                        </a>.
                    </p>
                </div>
            </div>

            <!-- Badge de estado de configuración -->
            <div v-if="turnstileConfigured" class="flex items-center gap-2 p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50">
                <i class="pi pi-check-circle text-emerald-600 dark:text-emerald-400" />
                <span class="text-xs text-emerald-800 dark:text-emerald-300 font-medium">
                    Claves de Turnstile configuradas correctamente en <code class="bg-emerald-100 dark:bg-emerald-900/40 px-1 py-0.5 rounded font-mono text-[11px]">.env</code>
                </span>
            </div>

            <!-- Info sobre configuración de modo en Cloudflare -->
            <div v-if="form.requires_captcha" class="pt-1">
                <p class="text-[11px] text-zinc-400 dark:text-zinc-500 italic">
                    <i class="pi pi-info-circle text-[10px] mr-1" />
                    El modo del widget (visible/invisible) se configura desde el
                    <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" class="text-blue-500 hover:text-blue-400 underline">dashboard de Cloudflare → Turnstile</a>.
                </p>
            </div>
        </div>

        <!-- Límite de envíos por IP -->
        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                Límite de Envíos por Dirección IP (Rate Limiting)
            </h4>
            <p class="text-xs text-zinc-500">
                Número máximo de envíos permitidos desde la misma dirección IP por minuto para prevenir ataques de denegación o spam masivo.
            </p>
            <div class="max-w-xs space-y-1">
                <label class="text-xs text-zinc-500 font-medium">Máximo envíos por IP / minuto</label>
                <InputNumber v-model="form.max_submissions_per_ip" :min="1" :max="1000" class="w-full text-xs" />
            </div>
        </div>
    </div>
</template>
