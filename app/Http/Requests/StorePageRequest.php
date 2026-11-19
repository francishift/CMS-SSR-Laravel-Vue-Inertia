<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'title_size'      => ['nullable', 'string', 'in:default,hero_2xl,hero_xl,h1,h2,h3,h4,h5,h6,base,sm'],
            'slug'            => ['nullable', 'string', 'max:255', 'unique:pages,slug'],
            'body'            => ['nullable', 'string'],
            'is_active'       => ['boolean'],
            'order'           => ['integer', 'min:0'],
            'seo_title'       => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_og_image'    => ['nullable', 'string'],
            'meta_robots'     => ['nullable', 'string', 'max:50'],
        ];
    }
}
