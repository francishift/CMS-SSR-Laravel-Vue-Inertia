<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para actualizar la configuración global del sitio.
 */
class UpdateSiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $colorFields = [
            'primary_color',
            'secondary_color',
            'accent_color',
            'link_color',
            'link_hover_color',
            'link_color_dark',
            'link_hover_color_dark',
            'header_nav_color_light',
            'header_nav_hover_light',
            'header_nav_active_light',
            'header_nav_color_dark',
            'header_nav_hover_dark',
            'header_nav_active_dark',
            'button_primary_bg',
            'button_primary_text',
            'button_secondary_bg',
            'button_secondary_text',
            'button_primary_bg_dark',
            'button_primary_text_dark',
            'button_secondary_bg_dark',
            'button_secondary_text_dark',
            'bg_color_light',
            'bg_color_dark',
            'text_color_light',
            'text_color_dark',
        ];

        $prepped = [];
        foreach ($colorFields as $field) {
            if ($this->has($field)) {
                $val = trim((string) $this->input($field));
                if ($val !== '') {
                    if (!str_starts_with($val, '#') && preg_match('/^[0-9A-Fa-f]{3,6}$/', $val)) {
                        $val = '#' . $val;
                    }
                    $prepped[$field] = $val;
                }
            }
        }

        if (!empty($prepped)) {
            $this->merge($prepped);
        }
    }

    public function rules(): array
    {
        return [
            'site_name'               => ['required', 'string', 'max:255'],
            'site_description'        => ['nullable', 'string', 'max:1000'],
            'logo_path'               => ['nullable', 'string', 'max:500'],
            'logo_invert'             => ['nullable', 'boolean'],
            'logo_brightness'         => ['nullable', 'integer', 'min:0', 'max:500'],
            'logo_dark_path'          => ['nullable', 'string', 'max:500'],
            'logo_dark_invert'        => ['nullable', 'boolean'],
            'logo_dark_brightness'    => ['nullable', 'integer', 'min:0', 'max:500'],
            'logo_height'             => ['nullable', 'integer', 'min:16', 'max:120'],
            'logo_height_mobile'      => ['nullable', 'integer', 'min:16', 'max:80'],
            'favicon_path'            => ['nullable', 'string', 'max:500'],
            'apple_touch_icon_path'   => ['nullable', 'string', 'max:500'],
            'primary_color'           => ['required', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'         => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'            => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'link_color'              => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'link_hover_color'        => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'link_color_dark'         => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'link_hover_color_dark'   => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'link_decoration'         => ['nullable', 'string', 'in:hover,always,none,reverse_hover'],
            'link_font_weight'        => ['nullable', 'string', 'in:inherit,normal,medium,semibold,bold'],
            'link_underline_offset'   => ['nullable', 'string', 'in:auto,2px,4px,8px'],
            'link_underline_style'    => ['nullable', 'string', 'in:solid,dotted,dashed,wavy'],
            'button_primary_bg'       => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_primary_text'     => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_secondary_bg'     => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_secondary_text'   => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_primary_bg_dark'  => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_primary_text_dark'=> ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_secondary_bg_dark'=> ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_secondary_text_dark'=> ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_border_radius'   => ['nullable', 'string', 'max:30'],
            'button_font_size'       => ['nullable', 'string', 'max:30'],
            'button_padding_x'       => ['nullable', 'string', 'max:30'],
            'button_padding_y'       => ['nullable', 'string', 'max:30'],
            'bg_color_light'          => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_color_dark'           => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_image_light'          => ['nullable', 'string', 'max:1000'],
            'bg_image_dark'           => ['nullable', 'string', 'max:1000'],
            'text_color_light'        => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color_dark'         => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'container_max_width'     => ['nullable', 'string', 'max:30'],
            'border_radius_theme'     => ['nullable', 'string', 'max:30'],
            'header_style'            => ['nullable', 'string', 'in:sticky,reveal_on_scroll_up,shrink_on_scroll,floating,transparent,static'],
            'header_layout'           => ['nullable', 'string', 'in:logo_left_menu_right,logo_left_menu_center,logo_center_menu_split,logo_top_menu_bottom'],
            'header_width'            => ['nullable', 'string', 'in:contained,full,compact,skinny,narrow,ultra_compact,fit,expand,wide_1440,wide_1600'],
            'header_padding_y'        => ['nullable', 'string', 'in:compact,standard,spacious'],
            'header_bg_type'          => ['nullable', 'string', 'in:glassmorphism,solid,transparent,gradient'],
            'header_shadow'           => ['nullable', 'string', 'in:border,shadow,pill,none'],
            'header_capsule_shadow'   => ['nullable', 'string', 'in:shadow-sm,shadow-md,shadow-lg,shadow-xl,shadow-2xl,shadow-glow,none'],
            'header_capsule_padding'  => ['nullable', 'string', 'in:compact,standard,spacious'],
            'header_border_radius'    => ['nullable', 'string', 'in:rounded-none,rounded-lg,rounded-xl,rounded-2xl,rounded-3xl,rounded-full'],
            'header_nav_color_light'  => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_nav_hover_light'  => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_nav_active_light' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_nav_color_dark'   => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_nav_hover_dark'   => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_nav_active_dark'  => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_cta_enable'       => ['nullable', 'boolean'],
            'header_cta_text'         => ['nullable', 'string', 'max:100'],
            'header_cta_url'          => ['nullable', 'string', 'max:500'],
            'header_cta_icon'         => ['nullable', 'string', 'max:100'],
            'header_cta_variant'      => ['nullable', 'string', 'in:primary,secondary,outline,glow'],
            'header_buttons'          => ['nullable', 'array'],
            'mobile_menu_style'       => ['nullable', 'string', 'in:dropdown,drawer_right,drawer_left,fullscreen'],
            'mobile_show_socials'     => ['nullable', 'boolean'],
            'mobile_show_cta'         => ['nullable', 'boolean'],
            'header_show_socials'     => ['nullable', 'boolean'],
            'footer_style'            => ['nullable', 'string', 'max:30'],
            'frontend_theme_mode'     => ['nullable', 'string', 'in:light,dark,user_choice'],
            'frontend_default_theme'  => ['nullable', 'string', 'in:light,dark,system'],
            'heading_font'            => ['required', 'string', 'max:255'],
            'body_font'               => ['required', 'string', 'max:255'],
            'body_font_size'          => ['nullable', 'string', 'max:30'],
            'body_font_weight'        => ['nullable', 'string', 'max:30'],
            'body_line_height'        => ['nullable', 'string', 'max:30'],
            'body_letter_spacing'     => ['nullable', 'string', 'max:30'],
            'heading_styles'                  => ['nullable', 'array'],
            'heading_styles.hero_2xl'         => ['nullable', 'array'],
            'heading_styles.hero_xl'          => ['nullable', 'array'],
            'heading_styles.h1'               => ['nullable', 'array'],
            'heading_styles.h2'               => ['nullable', 'array'],
            'heading_styles.h3'               => ['nullable', 'array'],
            'heading_styles.h4'               => ['nullable', 'array'],
            'heading_styles.h5'               => ['nullable', 'array'],
            'heading_styles.h6'               => ['nullable', 'array'],
            'heading_styles.*.size'           => ['nullable', 'string', 'max:30'],
            'heading_styles.*.font_weight'    => ['nullable', 'string', 'max:30'],
            'heading_styles.*.letter_spacing' => ['nullable', 'string', 'max:30'],
            'heading_styles.*.line_height'    => ['nullable', 'string', 'max:30'],
            'footer_text'             => ['nullable', 'string', 'max:2000'],
            'footer_layout_json'      => ['nullable', 'array'],
            'social_links'            => ['nullable', 'array'],
            'social_links.*'          => ['nullable', 'string', 'max:500'],
            'whatsapp_phone'          => ['nullable', 'string', 'max:50'],
            'whatsapp_message'        => ['nullable', 'string', 'max:500'],
            'contact_email'           => ['nullable', 'email', 'max:100'],
            'contact_phone'           => ['nullable', 'string', 'max:50'],
            'seo_default_title'       => ['nullable', 'string', 'max:70'],
            'seo_default_description' => ['nullable', 'string', 'max:160'],
            'seo_default_og_image'    => ['nullable', 'string', 'max:500'],
            'robots_txt_content'      => ['nullable', 'string', 'max:5000'],
            'google_maps_api_key'     => ['nullable', 'string', 'max:255'],
            'custom_head_scripts'     => ['nullable', 'string', 'max:10000'],
            'analytics_enabled'        => ['sometimes', 'boolean'],
            'analytics_provider'       => ['sometimes', 'string', 'in:ga4,gtm'],
            'analytics_measurement_id' => ['nullable', 'string', 'max:50', 'regex:/^(G-[A-Za-z0-9_]+|GTM-[A-Za-z0-9_]+)$/i'],
            'analytics_anonymize_ip'   => ['sometimes', 'boolean'],
            'cookie_consent_enabled'   => ['sometimes', 'boolean'],
            'cookie_consent_layout'    => ['sometimes', 'string', 'in:box,bar,cloud'],
            'cookie_consent_position'  => ['sometimes', 'string', 'in:bottom right,bottom left,bottom center,top right,top left,top center,bottom'],
            'cookie_consent_theme'     => ['sometimes', 'string', 'in:auto,light,dark'],
            'cookie_consent_revision'  => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'cookie_consent_privacy_url' => ['nullable', 'string', 'max:255'],
            'cookie_consent_cookie_url'  => ['nullable', 'string', 'max:255'],
            'cookie_consent_title'       => ['nullable', 'string', 'max:255'],
            'cookie_consent_description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
