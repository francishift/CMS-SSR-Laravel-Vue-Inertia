<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\CspInlineScripts;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Redirección y Escudo Anti-Bot para Canales de Contacto Directo.
 *
 * Permite usar URLs internas limpias (/whatsapp, /llamar, /email) en botones,
 * iconos y enlaces de toda la web, evitando exponer números de teléfono
 * o correos en texto plano indexable por scrapers y bots de spam.
 *
 * Todas las rutas devuelven una página HTML puente con JavaScript que lanza
 * el protocolo nativo (wa.me, tel:, mailto:) y redirige de vuelta al sitio,
 * evitando pantallas en blanco y dificultando la extracción automatizada.
 */
class PublicContactRedirectController extends Controller
{
    /**
     * Redirigir hacia WhatsApp de forma inteligente según el dispositivo.
     *
     * URL: /whatsapp
     */
    public function whatsapp(Request $request): Response
    {
        $settings = SiteSetting::cached();

        // Obtener teléfono desde el campo dedicado o desde el array social_links
        $rawPhone = $settings->whatsapp_phone ?: ($settings->social_links['whatsapp'] ?? null);

        if (empty($rawPhone)) {
            return redirect('/')->with('info', 'El canal de WhatsApp no está configurado actualmente.');
        }

        // Limpiar para dejar solo dígitos
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $rawPhone);

        // Si es un número español estándar de 9 dígitos sin prefijo internacional, anteponer 34
        if (strlen($cleanPhone) === 9 && in_array($cleanPhone[0], ['6', '7', '8', '9'])) {
            $cleanPhone = '34' . $cleanPhone;
        }

        // Mensaje predeterminado con soporte para sobreescritura segura vía query param ?text=
        $defaultMsg = $settings->whatsapp_message ?: 'Hola, necesito hacer una consulta';
        $message = trim((string) $request->input('text', $defaultMsg));
        $encodedMsg = rawurlencode($message);

        $destination = "https://wa.me/{$cleanPhone}?text={$encodedMsg}";

        return $this->renderBridgePage($settings, $destination, $request, 'Abriendo WhatsApp…');
    }

    /**
     * Iniciar llamada telefónica directa de forma segura.
     *
     * URL: /llamar, /telefono
     */
    public function phone(Request $request): Response
    {
        $settings = SiteSetting::cached();
        $rawPhone = $settings->contact_phone ?: $settings->whatsapp_phone;

        if (empty($rawPhone)) {
            return redirect('/')->with('info', 'El teléfono de contacto no está configurado.');
        }

        $cleanPhone = preg_replace('/[^0-9+]/', '', (string) $rawPhone);
        $destination = "tel:{$cleanPhone}";

        return $this->renderBridgePage($settings, $destination, $request, 'Iniciando llamada…');
    }

    /**
     * Iniciar redacción de correo electrónico directamente en la aplicación del usuario.
     *
     * URL: /email, /mail
     */
    public function email(Request $request): Response
    {
        $settings = SiteSetting::cached();
        $email = $settings->contact_email;

        if (empty($email)) {
            return redirect('/')->with('info', 'El correo de contacto no está configurado.');
        }

        $destination = "mailto:{$email}";

        return $this->renderBridgePage($settings, $destination, $request, 'Abriendo correo…');
    }

    /**
     * Generar la página HTML puente con los colores del tema del sitio.
     *
     * La página muestra un spinner con un mensaje mientras JavaScript lanza
     * el protocolo nativo (wa.me, tel:, mailto:) mediante window.location.replace().
     * Tras 3 segundos, redirige de vuelta al sitio para evitar pantallas en blanco
     * cuando el usuario vuelve al navegador desde la aplicación nativa.
     */
    private function renderBridgePage(SiteSetting $settings, string $destination, Request $request, string $message = 'Redirigiendo…'): Response
    {
        // Colores del tema según el modo claro/oscuro configurado
        $isDark = ($settings->frontend_default_theme ?? 'light') === 'dark';
        $bgColor = $isDark
            ? ($settings->bg_color_dark ?? '#09090b')
            : ($settings->bg_color_light ?? '#ffffff');
        $textColor = $isDark ? 'rgba(255,255,255,.5)' : 'rgba(0,0,0,.35)';
        $primaryColor = $settings->primary_color ?? '#10b981';
        $mutedColor = $isDark ? '#3f3f46' : '#e4e4e7';

        $escapedDestination = e($destination);
        $escapedBg = e($bgColor);
        $escapedText = e($textColor);
        $escapedPrimary = e($primaryColor);
        $escapedMuted = e($mutedColor);
        $escapedMessage = e($message);
        $bridgeScript = CspInlineScripts::CONTACT_BRIDGE;

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <meta name="robots" content="noindex,nofollow">
            <title>{$escapedMessage}</title>
            <style>
                body{margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:{$escapedBg}}
                .wrap{text-align:center;animation:fadeIn .3s ease}
                .spinner{width:28px;height:28px;border:2.5px solid {$escapedMuted};border-top-color:{$escapedPrimary};border-radius:50%;animation:spin .6s linear infinite;margin:0 auto 14px}
                @keyframes spin{to{transform:rotate(360deg)}}
                @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
                .msg{font-size:13px;letter-spacing:.01em;color:{$escapedText};margin:0}
            </style>
        </head>
        <body data-destination="{$escapedDestination}">
            <div class="wrap">
                <div class="spinner"></div>
                <p class="msg">{$escapedMessage}</p>
            </div>
            <script>{$bridgeScript}</script>
        </body>
        </html>
        HTML;

        return response($html, 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'X-Robots-Tag'  => 'noindex, nofollow',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
