<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Resources;

use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para exponer de forma segura la configuración del catálogo de Descargas.
 *
 * @mixin DownloadSetting
 */
class DownloadSettingResource extends JsonResource
{
    /**
     * @param  array<string, mixed>|null  $resolvedLayout
     * @param  array<string, mixed>|null  $resolvedItemLayout
     */
    public function __construct(
        $resource,
        private readonly ?array $resolvedLayout = null,
        private readonly ?array $resolvedItemLayout = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $adminRole = (string) config('downloads.admin_role', 'admin');
        $isAdmin = $user !== null && method_exists($user, 'hasRole') && $user->hasRole($adminRole);

        $rawLayout = $this->resolvedLayout ?? $this->resource->layout_json;
        $hasBuilderSections = is_array($rawLayout) && !empty($rawLayout['sections']);
        $useBuilderLayout = (bool) ($this->use_builder_layout ?? false);

        $rawItemLayout = $this->resolvedItemLayout ?? $this->resource->item_layout_json;
        $hasItemBuilderSections = is_array($rawItemLayout) && !empty($rawItemLayout['sections']);
        $useItemBuilderLayout = (bool) ($this->use_item_builder_layout ?? false);

        return [
            'page_title'                  => $this->page_title,
            'slug'                        => $this->slug,
            'description'                 => $this->description,
            'icon'                        => $this->icon ?: 'pi pi-download',
            'public_visibility'           => $this->public_visibility,
            'is_strictly_public'          => $this->isStrictlyPublic(),
            'use_builder_layout'          => $useBuilderLayout,
            'has_builder_sections'        => $hasBuilderSections,
            'layout_json'                 => $this->when(
                $useBuilderLayout && $hasBuilderSections,
                fn () => $rawLayout
            ),
            'use_item_builder_layout'     => $useItemBuilderLayout,
            'has_item_builder_sections'   => $hasItemBuilderSections,
            'item_layout_json'            => $this->when(
                $useItemBuilderLayout && $hasItemBuilderSections,
                fn () => $rawItemLayout
            ),
            'default_view_mode'           => $this->default_view_mode ?: DownloadSetting::VIEW_GRID,
            'allow_view_toggle'           => (bool) ($this->allow_view_toggle ?? true),
            'cols_desktop'                => (int) ($this->cols_desktop ?: 3),
            'cols_tablet'                 => (int) ($this->cols_tablet ?: 2),
            'cols_mobile'                 => (int) ($this->cols_mobile ?: 1),
            'gap_size'                    => $this->gap_size ?: 'md',
            'header_align'                => $this->header_align ?: 'left',
            'container_width'             => $this->container_width ?: 'boxed',
            'show_breadcrumb'             => (bool) ($this->show_breadcrumb ?? true),
            'show_header_icon'            => (bool) ($this->show_header_icon ?? true),
            'show_description'            => (bool) ($this->show_description ?? true),
            'header_title_size'           => $this->header_title_size ?: 'h1',
            'header_desc_size'            => $this->header_desc_size ?: 'lg',
            'per_page'                    => (int) ($this->per_page ?: 12),
            'card_style'                  => $this->card_style ?: 'default',
            'card_text_align'             => $this->card_text_align ?: 'left',
            'item_title_tag'              => $this->item_title_tag ?: 'h2',
            'item_title_size'             => $this->item_title_size ?: 'h4',
            'item_desc_size'              => $this->item_desc_size ?: 'sm',
            'item_desc_lines'             => $this->item_desc_lines ?: '3',
            'show_item_image'             => (bool) ($this->show_item_image ?? true),
            'show_item_description'       => (bool) ($this->show_item_description ?? true),
            'show_item_badges'            => (bool) ($this->show_item_badges ?? true),
            'show_item_meta'              => (bool) ($this->show_item_meta ?? true),
            'item_image_ratio'            => $this->item_image_ratio ?: '16:9',
            'item_svg_size'               => $this->item_svg_size ?: 'lg',
            'item_image_position'         => $this->item_image_position ?: 'flush',
            'item_button_style'           => $this->item_button_style ?: 'button_primary',
            'item_button_size'            => $this->item_button_size ?: 'md',
            'show_quick_download'         => (bool) ($this->show_quick_download ?? true),
            'show_downloads_count_public' => (bool) ($this->show_downloads_count_public ?? false),
            'seo_title'                   => $this->seo_title,
            'seo_description'             => $this->seo_description,
            'seo_og_image'                => $this->seo_og_image,
            'meta_robots'                 => $this->meta_robots,
            'public_url'                  => $this->publicIndexUrl(),
            'admin_index_url'             => $isAdmin ? route('downloads.admin.index') : null,
            'admin_builder_url'           => $isAdmin && \Illuminate\Support\Facades\Route::has('downloads.admin.builder')
                ? route('downloads.admin.builder')
                : null,
            'admin_item_builder_url'      => $isAdmin && \Illuminate\Support\Facades\Route::has('downloads.admin.item-builder')
                ? route('downloads.admin.item-builder')
                : null,
        ];
    }
}
