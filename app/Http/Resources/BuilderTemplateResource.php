<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Plantillas Dinámicas del Maquetador Visual.
 */
class BuilderTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'type'                 => $this->type,
            'type_label'           => $this->type === 'single_article' ? 'Detalle de Artículo' : 'Vista de Categoría',
            'layout_json'          => $this->layout_json,
            'description'          => $this->description,
            'preview_category_id'  => $this->preview_category_id,
            'preview_article_id'   => $this->preview_article_id,
            'is_active'            => (bool) $this->is_active,
            'created_at'           => $this->created_at?->format('d/m/Y H:i'),
            'updated_at'           => $this->updated_at?->format('d/m/Y H:i'),
            'categories_count'     => $this->type === 'category_archive' 
                                        ? ($this->categories_as_category_template_count ?? $this->categoriesAsCategoryTemplate()->count()) 
                                        : ($this->categories_as_article_template_count ?? $this->categoriesAsArticleTemplate()->count()),
            'articles_count'       => $this->articles_count ?? $this->articles()->count(),
            'categories_as_category_template' => CategoryResource::collection($this->whenLoaded('categoriesAsCategoryTemplate')),
            'categories_as_article_template'  => CategoryResource::collection($this->whenLoaded('categoriesAsArticleTemplate')),
        ];
    }
}
