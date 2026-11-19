<?php

namespace App\Support;

/**
 * Centraliza los fragmentos JavaScript inline críticos de arranque (tema visual y puente de contacto)
 * para garantizar que el contenido exacto renderizado en HTML coincida byte a byte con los hashes
 * criptográficos SHA-256 declarados en la cabecera Content-Security-Policy (CSP), incluso cuando
 * las páginas públicas se sirven desde la memoria caché RAM de Redis.
 */
final class CspInlineScripts
{
    /**
     * Inicializador de modo oscuro forzado en el frontend público.
     */
    public const PUBLIC_THEME_DARK = "document.documentElement.classList.add('dark');";

    /**
     * Inicializador de modo claro forzado en el frontend público.
     */
    public const PUBLIC_THEME_LIGHT = "document.documentElement.classList.remove('dark');";

    /**
     * Inicializador para modo 'user_choice' con tema inicial Claro ('light') en primeras visitas.
     */
    public const PUBLIC_THEME_USER_CHOICE_LIGHT = "(function(){try{var p=localStorage.getItem('public_theme_mode');if(p==='dark'){document.documentElement.classList.add('dark');}else{document.documentElement.classList.remove('dark');}}catch(e){document.documentElement.classList.remove('dark');}})();";

    /**
     * Inicializador para modo 'user_choice' con tema inicial Oscuro ('dark') en primeras visitas.
     */
    public const PUBLIC_THEME_USER_CHOICE_DARK = "(function(){try{var p=localStorage.getItem('public_theme_mode');if(p==='light'){document.documentElement.classList.remove('dark');}else{document.documentElement.classList.add('dark');}}catch(e){document.documentElement.classList.add('dark');}})();";

    /**
     * Inicializador para modo 'user_choice' con detección automática del S.O. ('system') en primeras visitas.
     */
    public const PUBLIC_THEME_USER_CHOICE_SYSTEM = "(function(){try{var p=localStorage.getItem('public_theme_mode');if(p==='dark'||(!p&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}else{document.documentElement.classList.remove('dark');}}catch(e){}})();";

    /**
     * Alias de compatibilidad hacia la variante automática por sistema operativo.
     */
    public const PUBLIC_THEME_USER_CHOICE = self::PUBLIC_THEME_USER_CHOICE_SYSTEM;

    /**
     * Inicializador de modo oscuro independiente para el panel de administración.
     */
    public const ADMIN_THEME = "(function(){try{var g=localStorage.getItem('admin_modo_oscuro');if(g==='true'||g===null){document.documentElement.classList.add('dark');}else{document.documentElement.classList.remove('dark');}}catch(e){}})();";

    /**
     * Script puente para canales de contacto nativos (/whatsapp, /llamar, /email).
     * Lee el destino desde el atributo HTML data-destination para mantener un hash SHA-256 invariable.
     */
    public const CONTACT_BRIDGE = "var d=document.body.getAttribute('data-destination');if(d){window.location.replace(d);}setTimeout(function(){window.history.back()},2000);";

    /**
     * Resuelve el script exacto de arranque del tema público según la configuración global.
     */
    public static function publicThemeScript(?string $mode, ?string $defaultTheme = 'light'): string
    {
        if ($mode === 'dark') {
            return self::PUBLIC_THEME_DARK;
        }

        if ($mode === 'user_choice') {
            return match ($defaultTheme) {
                'dark'   => self::PUBLIC_THEME_USER_CHOICE_DARK,
                'system' => self::PUBLIC_THEME_USER_CHOICE_SYSTEM,
                default  => self::PUBLIC_THEME_USER_CHOICE_LIGHT,
            };
        }

        return self::PUBLIC_THEME_LIGHT;
    }

    /**
     * Calcula el token CSP `'sha256-...'` para un bloque de código JavaScript inline dado.
     */
    public static function hash(string $script): string
    {
        return "'sha256-" . base64_encode(hash('sha256', $script, true)) . "'";
    }

    /**
     * Devuelve todos los hashes SHA-256 autorizados en rutas públicas y páginas puente.
     *
     * @return list<string>
     */
    public static function publicHashes(): array
    {
        return [
            self::hash(self::PUBLIC_THEME_DARK),
            self::hash(self::PUBLIC_THEME_LIGHT),
            self::hash(self::PUBLIC_THEME_USER_CHOICE_LIGHT),
            self::hash(self::PUBLIC_THEME_USER_CHOICE_DARK),
            self::hash(self::PUBLIC_THEME_USER_CHOICE_SYSTEM),
            self::hash(self::CONTACT_BRIDGE),
        ];
    }

    /**
     * Devuelve los hashes SHA-256 autorizados en el panel de administración y vistas previas.
     *
     * @return list<string>
     */
    public static function adminHashes(): array
    {
        return array_values(array_unique(array_merge(
            self::publicHashes(),
            [self::hash(self::ADMIN_THEME)]
        )));
    }
}
