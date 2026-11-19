<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para menús de navegación.
 *
 * Incluye los items raíz con sus hijos anidados.
 */
class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'location'  => $this->location,
            'is_active' => $this->is_active,
            'items'     => MenuItemResource::collection($this->whenLoaded('rootItems')),
            'all_items' => MenuItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
