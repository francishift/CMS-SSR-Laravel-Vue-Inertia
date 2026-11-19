<?php

namespace App\Http\Middleware;

use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);

        if ($request->is(
            'panel*',
            'acceso*',
            'login*',
            'register*',
            'forgot-password*',
            'reset-password*',
            'confirm-password*',
            'password*',
            'logout*',
            'verify-email*',
            'auth/google*',
            'oauth/google*'
        )) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $esAdminOAuthRoute = $request->is(
            'acceso*',
            'login*',
            'panel*',
            'register*',
            'forgot-password*',
            'reset-password*',
            'confirm-password*',
            'password*',
            'logout*',
            'verify-email*',
            'auth/google*',
            'oauth/google*'
        );

        return [
            ...parent::share($request),
            'appName' => config('app.name'),

            // Exponer los datos del usuario autenticado únicamente en rutas de administración/autenticación
            'auth' => [
                'user' => ($esAdminOAuthRoute && $request->user())
                    ? fn () => [
                        'id'    => $request->user()->id,
                        'name'  => $request->user()->name,
                        'email' => $request->user()->email,
                        'roles' => $request->user()->getRoleNames(),
                    ]
                    : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            /*
             * Configuración global del sitio (desde caché).
             * Disponible en todas las páginas para fuentes, colores, logo, etc.
             */
            'siteSettings' => fn () => $this->getSiteSettings($esAdminOAuthRoute),

            /*
             * Menús de navegación por ubicación (desde caché).
             * Se cargan sutiles para no engordar la carga pública.
             */
            'menuHeader'        => fn () => $this->getMenu('header', $request->user()),
            'menuFooter'        => fn () => $this->getMenu('footer', $request->user()),
            'menuSidebar'       => fn () => $this->getMenu('sidebar', $request->user()),
            'menusDisponibles'  => fn () => $this->getMenusDisponibles($request->user()),

            /*
             * URL base del sitio (APP_URL) disponible en todas las páginas.
             * Necesaria para construir URLs canónicas absolutas en el frontend (SEO).
             */
            'appUrl' => rtrim((string) config('app.url'), '/'),

            /*
             * Cargar el objeto de rutas Ziggy en el panel y páginas de autenticación.
             * En la web pública se mantiene a null para no exponer endpoints ni engordar el DOM.
             */
            'ziggy' => $esAdminOAuthRoute ? fn () => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ] : null,

            /*
             * Detección automática en servidor para cabecera transparente en la portada
             */
            'cabeceraTransparente' => function () use ($request, $esAdminOAuthRoute) {
                if ($esAdminOAuthRoute) {
                    return null;
                }
                if ($request->is('/')) {
                    $homepage = \App\Models\Page::obtenerHomepage();
                    $s = $homepage?->layout_json['sections'][0]['settings'] ?? null;
                    if (!empty($s['transparent_header'])) {
                        return [
                            'active'   => true,
                            'pull'     => (bool) ($s['transparent_header_pull'] ?? false),
                            'navColor' => $s['transparent_header_nav_color'] ?? 'inherit',
                        ];
                    }
                }
                return null;
            },
        ];
    }

    /**
     * Obtener la configuración del sitio desde caché.
     */
    private function getSiteSettings(bool $incluirDatosSensibles = false): array
    {
        try {
            $settings = SiteSetting::cached();

            return [
                'site_name'           => $settings->site_name,
                'site_description'    => $settings->site_description,
                'logo_path'           => $settings->logo_path,
                'logo_invert'         => (bool) $settings->logo_invert,
                'logo_brightness'     => (int) ($settings->logo_brightness ?? 100),
                'logo_dark_path'      => $settings->logo_dark_path,
                'logo_dark_invert'    => (bool) $settings->logo_dark_invert,
                'logo_dark_brightness'=> (int) ($settings->logo_dark_brightness ?? 100),
                'logo_height'         => (int) ($settings->logo_height ?? 36),
                'logo_height_mobile'  => (int) ($settings->logo_height_mobile ?? 32),
                'favicon_path'        => $settings->favicon_path,
                'apple_touch_icon_path' => $settings->apple_touch_icon_path,
                'primary_color'       => $settings->primary_color ?? '#10b981',
                'secondary_color'     => $settings->secondary_color ?? '#6366f1',
                'accent_color'        => $settings->accent_color ?? '#f59e0b',
                'link_color'          => $settings->link_color ?? $settings->primary_color ?? '#10b981',
                'link_hover_color'    => $settings->link_hover_color ?? '#059669',
                'link_color_dark'     => $settings->link_color_dark ?? '#d4d4d8',
                'link_hover_color_dark' => $settings->link_hover_color_dark ?? '#ffffff',
                'link_decoration'     => $settings->link_decoration ?? 'hover',
                'link_font_weight'    => $settings->link_font_weight ?? 'inherit',
                'link_underline_offset' => $settings->link_underline_offset ?? 'auto',
                'link_underline_style' => $settings->link_underline_style ?? 'solid',
                'button_primary_bg'   => $settings->button_primary_bg ?? $settings->primary_color ?? '#10b981',
                'button_primary_text' => $settings->button_primary_text ?? '#ffffff',
                'button_secondary_bg' => $settings->button_secondary_bg ?? $settings->secondary_color ?? '#6366f1',
                'button_secondary_text' => $settings->button_secondary_text ?? '#ffffff',
                'button_primary_bg_dark'  => $settings->button_primary_bg_dark ?? $settings->button_primary_bg ?? '#10b981',
                'button_primary_text_dark'=> $settings->button_primary_text_dark ?? '#ffffff',
                'button_secondary_bg_dark'=> $settings->button_secondary_bg_dark ?? '#27272a',
                'button_secondary_text_dark'=> $settings->button_secondary_text_dark ?? '#ffffff',
                'button_border_radius' => $settings->button_border_radius ?? 'rounded-xl',
                'button_font_size'       => $settings->button_font_size ?? 'text-sm',
                'button_padding_x'       => $settings->button_padding_x ?? 'px-5',
                'button_padding_y'       => $settings->button_padding_y ?? 'py-2.5',
                'bg_color_light'      => $settings->bg_color_light ?? '#ffffff',
                'bg_color_dark'       => $settings->bg_color_dark ?? '#09090b',
                'bg_image_light'      => $settings->bg_image_light ?? 'none',
                'bg_image_dark'       => $settings->bg_image_dark ?? 'none',
                'text_color_light'    => $settings->text_color_light ?? '#18181b',
                'text_color_dark'     => $settings->text_color_dark ?? '#f4f4f5',
                'container_max_width' => $settings->container_max_width ?? 'max-w-7xl',
                'border_radius_theme' => $settings->border_radius_theme ?? 'rounded-xl',
                'header_style'        => $settings->header_style ?? 'sticky',
                'header_layout'       => $settings->header_layout ?? 'logo_left_menu_right',
                'header_width'        => $settings->header_width ?? 'contained',
                'header_padding_y'    => $settings->header_padding_y ?? 'standard',
                'header_bg_type'      => $settings->header_bg_type ?? 'glassmorphism',
                'header_shadow'       => $settings->header_shadow ?? 'border',
                'header_capsule_shadow'  => $settings->header_capsule_shadow ?? 'shadow-xl',
                'header_capsule_padding' => $settings->header_capsule_padding ?? 'standard',
                'header_border_radius'   => $settings->header_border_radius ?? 'rounded-2xl',
                'header_nav_color_light' => $settings->header_nav_color_light ?? '#3f3f46',
                'header_nav_hover_light' => $settings->header_nav_hover_light ?? '#18181b',
                'header_nav_active_light'=> $settings->header_nav_active_light ?? '#10b981',
                'header_nav_color_dark'  => $settings->header_nav_color_dark ?? '#d4d4d8',
                'header_nav_hover_dark'  => $settings->header_nav_hover_dark ?? '#ffffff',
                'header_nav_active_dark' => $settings->header_nav_active_dark ?? '#10b981',
                'header_cta_enable'   => (bool) ($settings->header_cta_enable ?? false),
                'header_cta_text'     => $settings->header_cta_text,
                'header_cta_url'      => $settings->header_cta_url,
                'header_cta_icon'     => $settings->header_cta_icon,
                'header_cta_variant'  => $settings->header_cta_variant ?? 'primary',
                'header_buttons'      => $settings->header_buttons ?? [],
                'mobile_menu_style'   => $settings->mobile_menu_style ?? 'drawer_right',
                'mobile_show_socials' => (bool) ($settings->mobile_show_socials ?? true),
                'mobile_show_cta'     => (bool) ($settings->mobile_show_cta ?? true),
                'header_show_socials' => (bool) ($settings->header_show_socials ?? true),
                'footer_style'        => $settings->footer_style ?? 'columns',
                'frontend_theme_mode' => $settings->frontend_theme_mode ?? 'light',
                'frontend_default_theme' => $settings->frontend_default_theme ?? 'light',
                'heading_font'        => $settings->heading_font ?? 'Hanken Grotesk',
                'body_font'           => $settings->body_font ?? 'Hanken Grotesk',
                'body_font_size'      => $settings->body_font_size,
                'body_font_weight'    => $settings->body_font_weight,
                'body_line_height'    => $settings->body_line_height,
                'body_letter_spacing' => $settings->body_letter_spacing,
                'heading_styles'      => $settings->heading_styles,
                'footer_text'         => $settings->footer_text,
                'footer_layout_json'  => $settings->footer_layout_json ? (new \App\Services\PageBuilderResolverService())->resolve($settings->footer_layout_json) : null,
                'social_links'        => $settings->social_links,
                // Los canales nativos directos solo se exponen en el panel de administración (en público se usan los endpoints puente /whatsapp, /llamar, /email contra scraping)
                'whatsapp_phone'      => $incluirDatosSensibles ? $settings->whatsapp_phone : null,
                'whatsapp_message'    => $incluirDatosSensibles ? $settings->whatsapp_message : null,
                'contact_email'       => $incluirDatosSensibles ? $settings->contact_email : null,
                'contact_phone'       => $incluirDatosSensibles ? $settings->contact_phone : null,

                // Configuración pública de CookieConsent y Analítica
                'cookie_consent_enabled'   => (bool) ($settings->cookie_consent_enabled ?? true),
                'cookie_consent_layout'    => $settings->cookie_consent_layout ?? 'box',
                'cookie_consent_position'  => $settings->cookie_consent_position ?? 'bottom right',
                'cookie_consent_theme'     => $settings->cookie_consent_theme ?? 'auto',
                'cookie_consent_revision'  => (int) ($settings->cookie_consent_revision ?? 1),
                'cookie_consent_privacy_url' => $settings->cookie_consent_privacy_url,
                'cookie_consent_cookie_url'  => $settings->cookie_consent_cookie_url,
                'cookie_consent_title'       => $settings->cookie_consent_title,
                'cookie_consent_description' => $settings->cookie_consent_description,

                'analytics_enabled'        => (bool) ($settings->analytics_enabled ?? false),
                'analytics_provider'       => $settings->analytics_provider ?? 'ga4',
                'analytics_measurement_id' => ($settings->analytics_enabled || $incluirDatosSensibles) ? $settings->analytics_measurement_id : null,
                'analytics_anonymize_ip'   => (bool) ($settings->analytics_anonymize_ip ?? true),

                // La API key de Google Maps solo se expone en rutas de administración.
                // En páginas públicas se obtiene bajo demanda vía endpoint seguro
                // para evitar filtrar la clave en el HTML de cada página.
                'google_maps_api_key' => $incluirDatosSensibles ? $settings->google_maps_api_key : null,
            ];
        } catch (\Throwable) {
            return [
                'site_name'     => config('app.name', 'CMS'),
                'primary_color' => '#10b981',
                'heading_font'  => 'Hanken Grotesk',
                'body_font'     => 'Hanken Grotesk',
            ];
        }
    }

    /**
     * Obtener menú liviano por ubicación desde caché filtrando ítems restringidos según el visitante.
     */
    private function getMenu(string $location, ?\App\Models\User $user = null): ?array
    {
        try {
            $menu = Menu::porUbicacion($location);

            if (!$menu) {
                return null;
            }

            return [
                'id'       => $menu->id,
                'name'     => $menu->name,
                'location' => $menu->location,
                'items'    => $menu->rootItems
                    ->filter(fn ($item) => $item->isPubliclyVisibleFor($user))
                    ->map(fn ($item) => [
                        'id'           => $item->id,
                        'title'        => $item->title,
                        'resolved_url' => $item->resolved_url,
                        'target'       => $item->target,
                        'icon'         => $item->icon,
                        'is_active'    => (bool) $item->is_active,
                        'is_nofollow'  => (bool) $item->is_nofollow,
                        'children'     => $item->children
                            ->filter(fn ($child) => $child->isPubliclyVisibleFor($user))
                            ->map(fn ($child) => [
                                'id'           => $child->id,
                                'title'        => $child->title,
                                'resolved_url' => $child->resolved_url,
                                'target'       => $child->target,
                                'icon'         => $child->icon,
                                'is_active'    => (bool) $child->is_active,
                                'is_nofollow'  => (bool) $child->is_nofollow,
                            ])->values()->toArray(),
                    ])->values()->toArray(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Obtener lista de todos los menús activos con sus items estructurados desde caché,
     * ocultando enlaces de módulos restringidos para visitantes sin permisos.
     */
    private function getMenusDisponibles(?\App\Models\User $user = null): array
    {
        try {
            $menus = \App\Models\Menu::activosEstructurados();
            if (!class_exists(\App\Modules\Downloads\Models\DownloadSetting::class)) {
                return $menus;
            }

            $canSeeDownloads = \App\Modules\Downloads\Models\DownloadSetting::cached()->isPubliclyAccessible($user);
            if ($canSeeDownloads) {
                return $menus;
            }

            return array_map(function (array $menu) {
                $menu['items'] = array_values(array_filter(
                    array_map(function (array $item) {
                        if (($item['route_name'] ?? null) === 'public.downloads.index') {
                            return null;
                        }
                        $item['children'] = array_values(array_filter(
                            $item['children'] ?? [],
                            fn (array $child) => ($child['route_name'] ?? null) !== 'public.downloads.index'
                        ));
                        return $item;
                    }, $menu['items'] ?? [])
                ));
                return $menu;
            }, $menus);
        } catch (\Throwable) {
            return [];
        }
    }
}
