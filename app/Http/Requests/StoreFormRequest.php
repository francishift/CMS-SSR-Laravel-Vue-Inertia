<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para crear y actualizar formularios dinámicos en el panel admin.
 *
 * Valida tanto los metadatos del formulario como la estructura del schema
 * de campos, emails, scripts y configuración de seguridad.
 */
class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $formId = $this->route('form')?->id;

        return [
            // Identificación
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('forms', 'slug')->ignore($formId)],
            'description' => ['nullable', 'string', 'max:2000'],

            // Esquema de campos (array de objetos)
            'fields_schema'              => ['required', 'array', 'min:1'],
            'fields_schema.*.id'         => ['required', 'string', 'max:50'],
            'fields_schema.*.type'       => ['required', 'string', 'in:text,textarea,email,tel,url,password,number,select,multiselect,radio,checkbox,toggle,date,datetime,time,file,rating,slider,color,hidden,separator,heading,paragraph'],
            'fields_schema.*.name'       => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'fields_schema.*.label'      => ['nullable', 'string', 'max:255'],
            'fields_schema.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields_schema.*.required'   => ['nullable', 'boolean'],
            'fields_schema.*.options'    => ['nullable', 'array'],
            'fields_schema.*.default_value' => ['nullable', 'string', 'max:1000'],
            'fields_schema.*.width'      => ['nullable', 'string', 'in:full,half,third'],
            'fields_schema.*.validation_rules' => ['nullable', 'string', 'max:500'],
            'fields_schema.*.conditional' => ['nullable', 'array'],
            'fields_schema.*.max_length' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'fields_schema.*.min_length' => ['nullable', 'integer', 'min:0'],
            'fields_schema.*.min'        => ['nullable', 'numeric'],
            'fields_schema.*.max'        => ['nullable', 'numeric'],
            'fields_schema.*.min_date'   => ['nullable', 'date'],
            'fields_schema.*.max_date'   => ['nullable', 'date'],
            'fields_schema.*.max_file_size'      => ['nullable', 'integer', 'min:1', 'max:51200'],
            'fields_schema.*.allowed_extensions' => ['nullable', 'array'],
            'fields_schema.*.max_rating'         => ['nullable', 'integer', 'min:1', 'max:10'],
            'fields_schema.*.max_selections'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'fields_schema.*.css_class'  => ['nullable', 'string', 'max:200'],
            'fields_schema.*.content'    => ['nullable', 'string', 'max:5000'],
            'fields_schema.*.link_url'    => ['nullable', 'string', 'max:500'],
            'fields_schema.*.link_target' => ['nullable', 'string', 'in:modal,blank'],

            // Configuración general
            'settings' => ['nullable', 'array'],

            // Emails al administrador
            'email_admin_enabled'  => ['nullable', 'boolean'],
            'email_admin_template' => ['nullable', 'string', 'max:50000'],
            'email_admin_subject'  => ['nullable', 'string', 'max:255'],
            'email_admin_to'       => ['nullable', 'string', 'max:500'],

            // Autorespuesta al usuario
            'email_user_enabled'  => ['nullable', 'boolean'],
            'email_user_template' => ['nullable', 'string', 'max:50000'],
            'email_user_subject'  => ['nullable', 'string', 'max:255'],
            'email_user_field'    => ['nullable', 'string', 'max:100'],

            // Scripts de eventos
            'scripts_before'    => ['nullable', 'string', 'max:10000'],
            'scripts_on_submit' => ['nullable', 'string', 'max:10000'],
            'scripts_after'     => ['nullable', 'string', 'max:10000'],

            // Mensajes y redirección
            'success_message'      => ['nullable', 'string', 'max:500'],
            'success_redirect_url' => ['nullable', 'url:http,https', 'max:2048'],

            // Seguridad
            'max_submissions_per_ip' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_active'              => ['nullable', 'boolean'],
            'honeypot_field'         => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'requires_captcha'       => ['nullable', 'boolean'],
        ];
    }

    /**
     * Mensajes de validación en castellano.
     */
    public function messages(): array
    {
        return [
            'name.required'           => 'El nombre del formulario es obligatorio.',
            'slug.unique'             => 'Este identificador ya está en uso por otro formulario.',
            'fields_schema.required'  => 'El formulario debe tener al menos un campo.',
            'fields_schema.min'       => 'El formulario debe tener al menos un campo.',
            'fields_schema.*.type.in' => 'El tipo de campo no es válido.',
            'fields_schema.*.name.regex' => 'El nombre del campo solo puede contener letras minúsculas, números y guiones bajos.',
        ];
    }
}
