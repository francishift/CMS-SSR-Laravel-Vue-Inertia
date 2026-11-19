<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @inertiaHead

    @php
        $siteSettingsData = \App\Models\SiteSetting::cached();
        $favPath = $siteSettingsData->favicon_path ?: '/favicon.ico';
        $appleTouchIconPath = $siteSettingsData->apple_touch_icon_path ?? null;
        $isBuilderPreview = request()->is('*builder/preview-frame*');
        $isAdminPanel = request()->is('panel*', 'acceso*', 'login*', 'register*', 'forgot-password*', 'reset-password*', 'confirm-password*', 'password*', 'logout*', 'verify-email*', 'auth/google*', 'oauth/google*') && !$isBuilderPreview;
        $cspNonce = request()->attributes->get('csp_nonce');
    @endphp
    <link rel="icon" href="{{ $favPath }}" type="image/x-icon" />
    <link rel="shortcut icon" href="{{ $favPath }}" />
    @if($appleTouchIconPath)
    <link rel="apple-touch-icon" href="{{ $appleTouchIconPath }}" />
    @endif

    {{-- Preconnect a Google Tag Manager: solo si analytics está habilitado en la configuración --}}
    {{-- (Maps y Turnstile se inyectan dinámicamente desde sus componentes Vue solo cuando se usan) --}}
    @if(!$isAdminPanel && !$isBuilderPreview && !empty($siteSettingsData->analytics_enabled) && !empty($siteSettingsData->analytics_measurement_id))
        <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
        <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    @endif

    {{-- SEO Meta Tags & Control de Indexabilidad --}}
    @if($isAdminPanel)
        <meta name="robots" content="noindex, nofollow">
        @routes(null, $cspNonce)
    @else
        @if($isBuilderPreview)
            <meta name="robots" content="noindex, nofollow">
            @routes(null, $cspNonce)
        @else
            {!! OpenGraph::generate() !!}
            {!! \Artesaos\SEOTools\Facades\TwitterCard::generate() !!}
            {!! app(\App\Services\SeoService::class)->renderSchemaJsonLd() !!}
        @endif

        {{-- Variables CSS críticas inyectadas directamente en el HTML inicial para evitar parpadeos de fondo, fuentes o logotipo --}}
        @php
            $logoFilterParts = [];
            if (!empty($siteSettingsData->logo_invert)) {
                $logoFilterParts[] = 'invert(100%)';
            }
            if (!empty($siteSettingsData->logo_brightness) && (int)$siteSettingsData->logo_brightness !== 100) {
                $logoFilterParts[] = 'brightness(' . (int)$siteSettingsData->logo_brightness . '%)';
            }
            $logoFilter = !empty($logoFilterParts) ? implode(' ', $logoFilterParts) : 'none';

            $logoDarkFilterParts = [];
            if (!empty($siteSettingsData->logo_dark_invert)) {
                $logoDarkFilterParts[] = 'invert(100%)';
            }
            if (!empty($siteSettingsData->logo_dark_brightness) && (int)$siteSettingsData->logo_dark_brightness !== 100) {
                $logoDarkFilterParts[] = 'brightness(' . (int)$siteSettingsData->logo_dark_brightness . '%)';
            }
            $logoDarkFilter = !empty($logoDarkFilterParts) ? implode(' ', $logoDarkFilterParts) : 'none';
        @endphp
        <style id="cms-ssr-critical-vars">
            :root {
                --primary-color: {{ $siteSettingsData->primary_color ?? '#10b981' }};
                --secondary-color: {{ $siteSettingsData->secondary_color ?? '#6366f1' }};
                --accent-color: {{ $siteSettingsData->accent_color ?? '#f59e0b' }};
                --link-color: {{ $siteSettingsData->link_color ?: ($siteSettingsData->primary_color ?? '#10b981') }};
                --link-hover-color: {{ $siteSettingsData->link_hover_color ?? '#059669' }};
                --link-color-dark: {{ $siteSettingsData->link_color_dark ?? '#d4d4d8' }};
                --link-hover-color-dark: {{ $siteSettingsData->link_hover_color_dark ?? '#ffffff' }};
                --bg-color-light: {{ $siteSettingsData->bg_color_light ?? '#ffffff' }};
                --text-color-light: {{ $siteSettingsData->text_color_light ?? '#18181b' }};
                --bg-color-dark: {{ $siteSettingsData->bg_color_dark ?? '#09090b' }};
                --text-color-dark: {{ $siteSettingsData->text_color_dark ?? '#ffffff' }};
                --bg-image-light: {{ rtrim($siteSettingsData->bg_image_light && $siteSettingsData->bg_image_light !== 'none' ? $siteSettingsData->bg_image_light : 'none', '; ') }};
                --bg-image-dark: {{ rtrim($siteSettingsData->bg_image_dark && $siteSettingsData->bg_image_dark !== 'none' ? $siteSettingsData->bg_image_dark : 'none', '; ') }};
                --heading-font: '{{ $siteSettingsData->heading_font ?? 'Plus Jakarta Sans' }}', system-ui, sans-serif;
                --body-font: '{{ $siteSettingsData->body_font ?? 'Open Sans' }}', system-ui, sans-serif;
                --body-font-size: {{ $siteSettingsData->body_font_size ?: '1rem' }};
                --body-font-weight: {{ $siteSettingsData->body_font_weight ?: '400' }};
                --body-line-height: {{ $siteSettingsData->body_line_height ?: '1.625' }};
                --body-letter-spacing: {{ $siteSettingsData->body_letter_spacing ?: '0em' }};
                --logo-filter: {{ $logoFilter }};
                --logo-dark-filter: {{ $logoDarkFilter }};
                --logo-height: {{ (int) ($siteSettingsData->logo_height ?? 36) }}px;
                --logo-height-mobile: {{ (int) ($siteSettingsData->logo_height_mobile ?? 32) }}px;
                @php
                    $headingDefaults = [
                        'hero_2xl' => ['size' => '4.5rem',  'font_weight' => '800', 'letter_spacing' => '-0.03em',  'line_height' => '1.05'],
                        'hero_xl'  => ['size' => '3.75rem', 'font_weight' => '800', 'letter_spacing' => '-0.025em', 'line_height' => '1.1'],
                        'h1'       => ['size' => '3rem',    'font_weight' => '800', 'letter_spacing' => '-0.025em', 'line_height' => '1.1'],
                        'h2'       => ['size' => '2.25rem', 'font_weight' => '700', 'letter_spacing' => '-0.02em',  'line_height' => '1.2'],
                        'h3'       => ['size' => '1.875rem','font_weight' => '600', 'letter_spacing' => '0em',      'line_height' => '1.25'],
                        'h4'       => ['size' => '1.25rem', 'font_weight' => '600', 'letter_spacing' => '0em',      'line_height' => '1.35'],
                        'h5'       => ['size' => '1.125rem','font_weight' => '600', 'letter_spacing' => '0em',      'line_height' => '1.4'],
                        'h6'       => ['size' => '1rem',    'font_weight' => '600', 'letter_spacing' => '0em',      'line_height' => '1.4'],
                    ];
                    $customHeadingStyles = is_array($siteSettingsData->heading_styles) ? $siteSettingsData->heading_styles : (json_decode($siteSettingsData->heading_styles ?? '[]', true) ?: []);
                @endphp
                @foreach(['hero_2xl', 'hero_xl', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $level)
                --{{ $level }}-size: {{ !empty($customHeadingStyles[$level]['size']) ? $customHeadingStyles[$level]['size'] : $headingDefaults[$level]['size'] }};
                --{{ $level }}-font-weight: {{ !empty($customHeadingStyles[$level]['font_weight']) ? $customHeadingStyles[$level]['font_weight'] : $headingDefaults[$level]['font_weight'] }};
                --{{ $level }}-letter-spacing: {{ !empty($customHeadingStyles[$level]['letter_spacing']) ? $customHeadingStyles[$level]['letter_spacing'] : $headingDefaults[$level]['letter_spacing'] }};
                --{{ $level }}-line-height: {{ !empty($customHeadingStyles[$level]['line_height']) ? $customHeadingStyles[$level]['line_height'] : $headingDefaults[$level]['line_height'] }};
                @endforeach
            }
            .site-logo {
                filter: var(--logo-filter, none);
                height: var(--logo-height-mobile, 32px);
                width: auto;
            }
            @media (min-width: 768px) {
                .site-logo {
                    height: var(--logo-height, 36px);
                }
            }
            .site-logo.site-logo-drawer {
                height: var(--logo-height-mobile, 32px);
            }
            .dark .site-logo {
                filter: var(--logo-dark-filter, none);
            }
        </style>
    @endif

    {{-- Script de inicialización de tema totalmente independiente entre Backend y Frontend (hashes SHA-256 sincronizados con CSP) --}}
    @if($isAdminPanel)
        <script>{!! \App\Support\CspInlineScripts::ADMIN_THEME !!}</script>
    @else
        <script>{!! \App\Support\CspInlineScripts::publicThemeScript($siteSettingsData->frontend_theme_mode ?? 'light', $siteSettingsData->frontend_default_theme ?? 'light') !!}</script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
