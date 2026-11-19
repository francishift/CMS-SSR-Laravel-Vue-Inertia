<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest para validar la edición de elementos en la Biblioteca.
 */
class UpdateBuilderLibraryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:150'],
            'category'      => ['nullable', 'string', 'max:50'],
            'description'   => ['nullable', 'string', 'max:500'],
            'preview_image' => ['nullable', 'string', 'max:500'],
        ];
    }
}
