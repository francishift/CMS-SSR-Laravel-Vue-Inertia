<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para items de menú.
 *
 * Incluye la URL resuelta y los hijos (submenús) recursivamente.
 */
class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'menu_id'      => $this->menu_id,
            'parent_id'    => $this->parent_id,
            'title'        => $this->title,
            'url'          => $this->url,
            'route_name'   => $this->route_name,
            'category_id'  => $this->category_id,
            'article_id'   => $this->article_id,
            'page_id'      => $this->page_id,
            'resolved_url' => $this->resolved_url,
            'target'       => $this->target,
            'is_nofollow'  => (bool) $this->is_nofollow,
            'icon'         => $this->icon,
            'order'        => $this->order,
            'is_active'    => $this->is_active,
            'children'     => self::collection($this->whenLoaded('children')),
            'category'     => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
