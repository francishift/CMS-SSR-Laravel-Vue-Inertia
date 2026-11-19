<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest para validar la creación de elementos en la Biblioteca.
 */
class StoreBuilderLibraryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:150'],
            'type'          => ['required', 'string', 'max:50'],
            'category'      => ['nullable', 'string', 'max:50'],
            'description'   => ['nullable', 'string', 'max:500'],
            'content_json'  => ['required', 'array'],
            'preview_image' => ['nullable', 'string', 'max:500'],
        ];
    }
}
