<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear/actualizar items de menú.
 */
class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'url'         => ['nullable', 'string', 'max:500'],
            'route_name'  => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'article_id'  => ['nullable', 'integer', 'exists:articles,id'],
            'page_id'     => ['nullable', 'integer', 'exists:pages,id'],
            'parent_id'   => ['nullable', 'integer', 'exists:menu_items,id'],
            'target'      => ['string', 'in:_self,_blank'],
            'is_nofollow' => ['nullable', 'boolean'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'order'       => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ];
    }
}
