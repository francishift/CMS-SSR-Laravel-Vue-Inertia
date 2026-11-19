<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para actualizar categorías.
 */
class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id;

        return [
            'name'                           => ['required', 'string', 'max:255'],
            'slug'                           => ['nullable', 'string', 'max:255', "unique:categories,slug,{$categoryId}"],
            'description'                    => ['nullable', 'string', 'max:2000'],
            'parent_id'                      => ['nullable', 'integer', 'exists:categories,id', "not_in:{$categoryId}"],
            'template'                       => ['string', 'max:50'],
            'category_builder_template_id'   => ['nullable', 'integer', 'exists:builder_templates,id'],
            'article_builder_template_id'    => ['nullable', 'integer', 'exists:builder_templates,id'],
            'cols_mobile'                    => ['nullable', 'integer', 'in:1,2'],
            'cols_tablet'                    => ['nullable', 'integer', 'in:1,2,3,4'],
            'cols_desktop'                   => ['nullable', 'integer', 'in:1,2,3,4,5,6'],
            'gap_size'                       => ['nullable', 'string', 'in:sm,md,lg,xl'],
            'align_grid'                     => ['nullable', 'string', 'in:start,center,end'],
            'header_align'                   => ['nullable', 'string', 'in:left,center,right'],
            'title_tag'                      => ['nullable', 'string', 'in:h1,h2,h3,h4'],
            'articles_order'                 => ['nullable', 'string', 'in:published_at_desc,published_at_asc,title_asc,title_desc,manual'],
            'per_page'                       => ['nullable', 'integer', 'min:1', 'max:100'],
            'show_breadcrumb'                => ['nullable', 'boolean'],
            'show_pagination'                => ['nullable', 'boolean'],
            'show_author'                    => ['nullable', 'boolean'],
            'show_date'                      => ['nullable', 'boolean'],
            'show_reading_time'              => ['nullable', 'boolean'],
            'show_category_link'             => ['nullable', 'boolean'],
            'featured_image'                 => ['nullable', 'string', 'max:500'],
            'icon'                           => ['nullable', 'string', 'max:100'],
            'color'                          => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'extra_fields_schema'            => ['nullable', 'array'],
            'extra_fields_schema.*.key'      => ['required_with:extra_fields_schema', 'string', 'max:100'],
            'extra_fields_schema.*.label'    => ['required_with:extra_fields_schema', 'string', 'max:255'],
            'extra_fields_schema.*.type'     => ['required_with:extra_fields_schema', 'string', 'in:text,textarea,wysiwyg,number,select,boolean,date,datetime,time,image,video,url,email,color,icon'],
            'extra_fields_schema.*.options'  => ['nullable', 'array'],
            'extra_fields_schema.*.required' => ['nullable', 'boolean'],
            'seo_title'                      => ['nullable', 'string', 'max:70'],
            'seo_description'                => ['nullable', 'string', 'max:160'],
            'seo_og_image'                   => ['nullable', 'string', 'max:2048'],
            'meta_robots'                    => ['nullable', 'string', 'max:50'],
            'order'                          => ['nullable', 'integer', 'min:0'],
            'is_active'                      => ['boolean'],
        ];
    }
}
