<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para páginas estáticas del CMS.
 */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resolver = new \App\Services\PageBuilderResolverService();

        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'title_size'      => $this->title_size ?: 'default',
            'slug'            => $this->slug,
            'body'            => $this->body,
            'layout_json'     => $resolver->resolve($this->layout_json),
            'is_active'       => (bool) $this->is_active,
            'is_homepage'     => (bool) $this->is_homepage,
            'order'           => (int) $this->order,
            'seo_title'       => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_og_image'    => $this->seo_og_image,
            'meta_robots'     => $this->meta_robots,
            'created_at'      => $this->created_at?->format('d/m/Y H:i'),
            'updated_at'      => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
