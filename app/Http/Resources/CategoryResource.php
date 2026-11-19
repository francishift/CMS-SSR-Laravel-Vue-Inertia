<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para categorías del CMS.
 *
 * Incluye esquema de campos extra, subcategorías
 * y datos SEO filtrados.
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'parent_id'                    => $this->parent_id,
            'template'                     => $this->template,
            'category_builder_template_id' => $this->category_builder_template_id,
            'article_builder_template_id'  => $this->article_builder_template_id,
            'layout_json'                  => $this->layout_json,
            'cols_mobile'                  => $this->cols_mobile ?? 1,
            'cols_tablet'         => $this->cols_tablet ?? 2,
            'cols_desktop'        => $this->cols_desktop ?? 3,
            'gap_size'            => $this->gap_size ?? 'md',
            'align_grid'          => $this->align_grid ?? 'start',
            'header_align'        => $this->header_align ?? 'left',
            'title_tag'           => ($this->title_tag && $this->title_tag !== 'h3') ? $this->title_tag : 'h2',
            'articles_order'      => $this->articles_order ?? 'published_at_desc',
            'per_page'            => $this->per_page ?? 12,
            'show_breadcrumb'     => $this->show_breadcrumb ?? true,
            'show_pagination'     => $this->show_pagination ?? true,
            'show_author'         => $this->show_author ?? true,
            'show_date'           => $this->show_date ?? true,
            'show_reading_time'   => $this->show_reading_time ?? true,
            'show_category_link'  => $this->show_category_link ?? true,
            'featured_image'      => $this->featured_image,
            'icon'                => $this->icon,
            'color'               => $this->color,
            'extra_fields_schema' => $this->extra_fields_schema,
            'seo_title'           => $this->seo_title,
            'seo_description'     => $this->seo_description,
            'seo_og_image'        => $this->seo_og_image,
            'meta_robots'         => $this->meta_robots,
            'order'               => $this->order,
            'is_active'           => $this->is_active,
            'created_at'          => $this->created_at?->format('d/m/Y H:i'),
            'articles_count'      => $this->whenCounted('articles'),
            'parent'              => new self($this->whenLoaded('parent')),
            'children'            => self::collection($this->whenLoaded('children')),
        ];
    }
}
