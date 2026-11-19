<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Requests;

use App\Models\Category;
use App\Models\Page;
use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validación para la configuración general y diseño de la portada de Descargas.
 *
 * Evita colisiones del slug del catálogo con rutas reservadas del sistema,
 * páginas estáticas o categorías existentes.
 */
class UpdateDownloadSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $adminRole = (string) config('downloads.admin_role', 'admin');

        return $user !== null && method_exists($user, 'hasRole') && $user->hasRole($adminRole);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => Str::slug(trim((string) $this->input('slug'), " \t\n\r\0\x0B/")),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reserved = array_map('strtolower', (array) config('downloads.reserved_slugs', []));
        $deliveryPrefix = strtolower(trim((string) config('downloads.delivery_prefix', 'download-vault'), '/'));
        $reserved[] = $deliveryPrefix;

        return [
            'page_title' => ['required', 'string', 'max:160'],
            'slug'       => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9\-]+$/',
                Rule::notIn($reserved),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $slug = (string) $value;
                    if (Page::where('slug', $slug)->exists()) {
                        $fail("El slug «{$slug}» ya está siendo utilizado por una página estática.");
                    }
                    if (Category::where('slug', $slug)->exists()) {
                        $fail("El slug «{$slug}» ya está siendo utilizado por una categoría.");
                    }
                },
            ],
            'description'                 => ['nullable', 'string', 'max:2000'],
            'icon'                        => ['nullable', 'string', 'max:100'],
            'public_visibility'           => ['required', 'string', Rule::in([DownloadSetting::VISIBILITY_ADMIN_ONLY, DownloadSetting::VISIBILITY_PUBLIC])],
            'use_builder_layout'          => ['sometimes', 'boolean'],
            'use_item_builder_layout'     => ['sometimes', 'boolean'],
            'default_view_mode'           => ['required', 'string', Rule::in([DownloadSetting::VIEW_GRID, DownloadSetting::VIEW_LIST])],
            'allow_view_toggle'           => ['boolean'],
            'cols_desktop'                => ['required', 'integer', 'min:1', 'max:4'],
            'cols_tablet'                 => ['required', 'integer', 'min:1', 'max:3'],
            'cols_mobile'                 => ['required', 'integer', 'min:1', 'max:2'],
            'gap_size'                    => ['required', 'string', Rule::in(['sm', 'md', 'lg', 'xl'])],
            'header_align'                => ['required', 'string', Rule::in(['left', 'center', 'right'])],
            'container_width'             => ['sometimes', 'string', Rule::in(['boxed', 'expand', 'small', 'full_margin'])],
            'show_breadcrumb'             => ['sometimes', 'boolean'],
            'show_header_icon'            => ['sometimes', 'boolean'],
            'show_description'            => ['sometimes', 'boolean'],
            'header_title_size'           => ['sometimes', 'string', 'max:20'],
            'header_desc_size'            => ['sometimes', 'string', Rule::in(['sm', 'base', 'lg', 'xl'])],
            'per_page'                    => ['required', 'integer', 'min:4', 'max:48'],
            'card_style'                  => ['sometimes', 'string', Rule::in(['default', 'shadow', 'flat', 'bordered'])],
            'card_text_align'             => ['sometimes', 'string', Rule::in(['left', 'center', 'right'])],
            'item_title_tag'              => ['sometimes', 'string', Rule::in(['h2', 'h3', 'h4', 'div'])],
            'item_title_size'             => ['sometimes', 'string', 'max:20'],
            'item_desc_size'              => ['sometimes', 'string', Rule::in(['xs', 'sm', 'base', 'lg'])],
            'item_desc_lines'             => ['sometimes', 'string', Rule::in(['2', '3', '4', 'none'])],
            'show_item_image'             => ['sometimes', 'boolean'],
            'show_item_description'       => ['sometimes', 'boolean'],
            'show_item_badges'            => ['sometimes', 'boolean'],
            'show_item_meta'              => ['sometimes', 'boolean'],
            'item_image_ratio'            => ['sometimes', 'string', Rule::in(['16:9', '4:3', '1:1', 'compact', 'auto'])],
            'item_svg_size'               => ['sometimes', 'string', Rule::in(['sm', 'md', 'lg', 'xl'])],
            'item_image_position'         => ['sometimes', 'string', Rule::in(['flush', 'boxed'])],
            'item_button_style'           => ['sometimes', 'string', Rule::in(['button_primary', 'button_secondary', 'button_outline', 'link'])],
            'item_button_size'            => ['sometimes', 'string', Rule::in(['sm', 'md', 'lg'])],
            'show_quick_download'         => ['boolean'],
            'show_downloads_count_public' => ['boolean'],
            'seo_title'                   => ['nullable', 'string', 'max:160'],
            'seo_description'             => ['nullable', 'string', 'max:320'],
            'seo_og_image'                => ['nullable', 'string', 'max:2048'],
            'meta_robots'                 => ['nullable', 'string', 'max:60'],
        ];
    }
}
