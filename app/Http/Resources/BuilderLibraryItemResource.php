<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de transformación segura para los elementos de la Biblioteca del Builder.
 */
class BuilderLibraryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'type'          => $this->type,
            'category'      => $this->category ?? 'general',
            'description'   => $this->description,
            'content_json'  => $this->content_json,
            'preview_image' => $this->preview_image,
            'created_by'    => $this->whenLoaded('createdBy', fn () => [
                'name' => $this->createdBy->name,
            ]),
            'created_at'    => $this->created_at?->format('d/m/Y H:i'),
            'updated_at'    => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
