<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo de Configuración Global del Sitio (Singleton).
 *
 * Utiliza caché agresiva con Redis para evitar queries
 * repetitivas. El método cached() devuelve la instancia
 * desde caché, invalidándola solo cuando se actualiza.
 *
 * @property int $id
 * @property string $site_name
 * @property ?string $site_description
 * @property ?string $logo_path
 * @property ?string $favicon_path
 * @property ?string $apple_touch_icon_path
 * @property string $primary_color
 * @property ?string $secondary_color
 * @property string $heading_font
 * @property string $body_font
 * @property ?string $footer_text
 * @property ?array $social_links
 * @property ?string $seo_default_title
 * @property ?string $seo_default_description
 * @property ?string $seo_default_og_image
 * @property ?string $robots_txt_content
 * @property ?string $custom_head_scripts
 * @property bool $analytics_enabled
 * @property string $analytics_provider
 * @property ?string $analytics_measurement_id
 * @property bool $analytics_anonymize_ip
 * @property bool $cookie_consent_enabled
 * @property string $cookie_consent_layout
 * @property string $cookie_consent_position
 * @property string $cookie_consent_theme
 * @property int $cookie_consent_revision
 * @property ?string $cookie_consent_privacy_url
 * @property ?string $cookie_consent_cookie_url
 * @property ?string $cookie_consent_title
 * @property ?string $cookie_consent_description
 */
class SiteSetting extends Model
{
    use \App\Models\Concerns\RecordsCmsActivity;

    public function activityLogChannel(): string { return 'configuracion'; }
    public function activityEntityLabel(): string { return 'configuración global'; }
    protected $fillable = [
        'site_name',
        'site_description',
        'logo_path',
        'logo_invert',
        'logo_brightness',
        'logo_dark_path',
        'logo_dark_invert',
        'logo_dark_brightness',
        'logo_height',
        'logo_height_mobile',
        'favicon_path',
        'apple_touch_icon_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'link_color',
        'link_hover_color',
        'link_color_dark',
        'link_hover_color_dark',
        'link_decoration',
        'link_font_weight',
        'link_underline_offset',
        'link_underline_style',
        'button_primary_bg',
        'button_primary_text',
        'button_secondary_bg',
        'button_secondary_text',
        'button_primary_bg_dark',
        'button_primary_text_dark',
        'button_secondary_bg_dark',
        'button_secondary_text_dark',
        'button_border_radius',
        'button_font_size',
        'button_padding_x',
        'button_padding_y',
        'bg_color_light',
        'bg_color_dark',
        'bg_image_light',
        'bg_image_dark',
        'text_color_light',
        'text_color_dark',
        'container_max_width',
        'border_radius_theme',
        'header_style',
        'header_layout',
        'header_width',
        'header_padding_y',
        'header_bg_type',
        'header_shadow',
        'header_capsule_shadow',
        'header_capsule_padding',
        'header_border_radius',
        'header_nav_color_light',
        'header_nav_hover_light',
        'header_nav_active_light',
        'header_nav_color_dark',
        'header_nav_hover_dark',
        'header_nav_active_dark',
        'header_cta_enable',
        'header_cta_text',
        'header_cta_url',
        'header_cta_icon',
        'header_cta_variant',
        'header_buttons',
        'mobile_menu_style',
        'mobile_show_socials',
        'mobile_show_cta',
        'header_show_socials',
        'footer_style',
        'frontend_theme_mode',
        'frontend_default_theme',
        'heading_font',
        'body_font',
        'body_font_size',
        'body_font_weight',
        'body_line_height',
        'body_letter_spacing',
        'heading_styles',
        'footer_text',
        'footer_layout_json',
        'social_links',
        'whatsapp_phone',
        'whatsapp_message',
        'contact_email',
        'contact_phone',
        'seo_default_title',
        'seo_default_description',
        'seo_default_og_image',
        'robots_txt_content',
        'google_maps_api_key',
        'custom_head_scripts',
        'analytics_enabled',
        'analytics_provider',
        'analytics_measurement_id',
        'analytics_anonymize_ip',
        'cookie_consent_enabled',
        'cookie_consent_layout',
        'cookie_consent_position',
        'cookie_consent_theme',
        'cookie_consent_revision',
        'cookie_consent_privacy_url',
        'cookie_consent_cookie_url',
        'cookie_consent_title',
        'cookie_consent_description',
    ];

    protected function casts(): array
    {
        return [
            'logo_invert'          => 'boolean',
            'logo_brightness'      => 'integer',
            'logo_dark_invert'     => 'boolean',
            'logo_dark_brightness' => 'integer',
            'logo_height'          => 'integer',
            'logo_height_mobile'   => 'integer',
            'header_cta_enable'    => 'boolean',
            'mobile_show_socials'  => 'boolean',
            'mobile_show_cta'      => 'boolean',
            'header_show_socials'  => 'boolean',
            'social_links'         => 'array',
            'heading_styles'       => 'array',
            'footer_layout_json'   => 'array',
            'header_buttons'       => 'array',
            'google_maps_api_key'  => 'encrypted',
            'analytics_enabled'        => 'boolean',
            'analytics_anonymize_ip'   => 'boolean',
            'cookie_consent_enabled'   => 'boolean',
            'cookie_consent_revision'  => 'integer',
        ];
    }

    /**
     * Obtener la configuración global desde caché.
     *
     * Si no existe en BD, crea una fila con valores por defecto.
     * TTL configurable en config/cms.php.
     */
    public static function cached(): self
    {
        $ttl = config('cms.cache.site_settings_ttl', 86400);

        try {
            $store = Cache::supportsTags() ? Cache::tags(['site-settings']) : Cache::store();

            $result = $store->remember(
                'site_settings_global',
                $ttl,
                fn () => self::firstOrCreate(
                    ['id' => 1],
                    [
                        'site_name'     => config('app.name', 'Mi Sitio Web'),
                        'primary_color' => '#10b981',
                        'heading_font'  => 'Hanken Grotesk',
                        'body_font'     => 'Hanken Grotesk',
                    ]
                )
            );

            // Protección contra objetos corruptos en caché (ej: __PHP_Incomplete_Class)
            if (!$result instanceof self) {
                $store->forget('site_settings_global');

                return self::firstOrCreate(
                    ['id' => 1],
                    [
                        'site_name'     => config('app.name', 'Mi Sitio Web'),
                        'primary_color' => '#10b981',
                        'heading_font'  => 'Hanken Grotesk',
                        'body_font'     => 'Hanken Grotesk',
                    ]
                );
            }

            return $result;
        } catch (\Throwable) {
            // Si la tabla no existe o la caché está corrupta, devolver defaults
            return new self([
                'site_name'     => config('app.name', 'Mi Sitio Web'),
                'primary_color' => '#10b981',
                'heading_font'  => 'Hanken Grotesk',
                'body_font'     => 'Hanken Grotesk',
            ]);
        }
    }

    /**
     * Indica desde caché si hay una API Key no vacía configurada para Google Maps,
     * evitando ejecutar desencriptación AES en cada petición HTTP.
     */
    public static function hasGoogleMapsKeyConfigured(): bool
    {
        try {
            return (bool) Cache::remember(
                'cms_has_google_maps_key',
                86400,
                static fn () => trim((string) (self::cached()->google_maps_api_key ?? '')) !== ''
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Limpia la caché global de configuración del sitio.
     */
    public static function clearCache(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['site-settings'])->flush();
        }
        Cache::forget('site_settings_global');
        Cache::forget('cms_has_google_maps_key');
        Cache::forget('cms_csp_active_integrations');
    }

    /**
     * Invalidar la caché al guardar cambios.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            self::clearCache();
        });
    }
}
