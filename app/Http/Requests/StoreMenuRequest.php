<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear/actualizar menús.
 */
class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menuId = $this->route('menu')?->id;

        return [
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['nullable', 'string', 'max:255', "unique:menus,slug,{$menuId}"],
            'location'  => ['required', 'string', 'in:header,sidebar,footer,custom'],
            'is_active' => ['boolean'],
        ];
    }
}
