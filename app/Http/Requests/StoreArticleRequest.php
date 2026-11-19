<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear artículos.
 */
class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', 'unique:articles,slug'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'body'             => ['nullable', 'string'],
            'featured_image'   => ['nullable', 'string', 'max:500'],
            'icon'             => ['nullable', 'string', 'max:100'],
            'category_id'                 => ['required', 'integer', 'exists:categories,id'],
            'article_builder_template_id' => ['nullable', 'integer', 'exists:builder_templates,id'],
            'status'                      => ['required', 'string', 'in:draft,published,scheduled'],
            'published_at'     => ['nullable', 'date'],
            'published_until'  => ['nullable', 'date', 'after_or_equal:published_at'],
            'extra_fields_data' => ['nullable', 'array'],
            'seo_title'        => ['nullable', 'string', 'max:70'],
            'seo_description'  => ['nullable', 'string', 'max:160'],
            'seo_og_image'     => ['nullable', 'string', 'max:500'],
            'meta_robots'      => ['nullable', 'string', 'max:50'],
            'is_featured'      => ['boolean'],
            'show_read_more'   => ['nullable', 'boolean'],
            'card_full_link'   => ['nullable', 'boolean'],
            'read_more_text'   => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Preparar datos antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        // Asignar el autor autenticado
        $this->merge([
            'author_id' => $this->user()->id,
        ]);
    }
}
