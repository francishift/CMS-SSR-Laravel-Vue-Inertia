<?php

namespace App\Services;

use App\Models\Form;

/**
 * Servicio de Generación de Reglas de Validación Dinámicas.
 *
 * Transforma el esquema JSON de campos de un formulario en reglas
 * de validación de Laravel, garantizando que cada campo se valide
 * estrictamente según su tipo, independientemente del frontend.
 *
 * Este servicio es la pieza central de la seguridad del módulo:
 * nunca se confía en la validación del navegador.
 */
class FormValidationRuleBuilder
{
    /**
     * Tipos de campo que no contienen datos del usuario (decorativos).
     */
    private const DECORATIVE_TYPES = ['separator', 'heading', 'paragraph'];

    /**
     * Generar las reglas de validación de Laravel a partir del schema del formulario.
     *
     * @return array<string, array<string>> ['campo_name' => ['rule1', 'rule2', ...]]
     */
    public function buildRules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields_schema ?? [] as $field) {
            $type = $field['type'] ?? '';
            $name = $field['name'] ?? '';

            // Saltar campos decorativos o sin nombre
            if (in_array($type, self::DECORATIVE_TYPES, true) || empty($name)) {
                continue;
            }

            $fieldRules = $this->getRulesForType($field);
            $rules["fields.{$name}"] = $fieldRules;

            // Para multiselect y checkbox con opciones, validar cada elemento del array
            $extraRules = $this->getArrayItemRules($field);
            if ($extraRules) {
                $rules["fields.{$name}.*"] = $extraRules;
            }
        }

        return $rules;
    }

    /**
     * Generar mensajes de validación personalizados en castellano.
     *
     * @return array<string, string>
     */
    public function buildMessages(Form $form): array
    {
        $messages = [];

        foreach ($form->fields_schema ?? [] as $field) {
            $type = $field['type'] ?? '';
            $name = $field['name'] ?? '';
            $label = $field['label'] ?? $name;

            if (in_array($type, self::DECORATIVE_TYPES, true) || empty($name)) {
                continue;
            }

            $key = "fields.{$name}";
            $messages["{$key}.required"] = "El campo «{$label}» es obligatorio.";
            $messages["{$key}.email"] = "El campo «{$label}» debe ser un correo electrónico válido.";
            $messages["{$key}.max"] = "El campo «{$label}» excede el tamaño máximo permitido.";
            $messages["{$key}.min"] = "El campo «{$label}» no alcanza el mínimo requerido.";
            $messages["{$key}.in"] = "El valor seleccionado en «{$label}» no es válido.";
            $messages["{$key}.numeric"] = "El campo «{$label}» debe ser un valor numérico.";
            $messages["{$key}.url"] = "El campo «{$label}» debe ser una URL válida.";
            $messages["{$key}.date"] = "El campo «{$label}» debe ser una fecha válida.";
            $messages["{$key}.regex"] = "El formato del campo «{$label}» no es válido.";
            $messages["{$key}.file"] = "El campo «{$label}» debe ser un archivo válido.";
            $messages["{$key}.mimes"] = "El tipo de archivo en «{$label}» no está permitido.";
            $messages["{$key}.integer"] = "El campo «{$label}» debe ser un número entero.";
            $messages["{$key}.boolean"] = "El campo «{$label}» debe ser verdadero o falso.";
            $messages["{$key}.array"] = "El campo «{$label}» debe contener una selección válida.";
        }

        return $messages;
    }

    /**
     * Obtener las reglas de validación específicas según el tipo de campo.
     *
     * @return array<string>
     */
    private function getRulesForType(array $field): array
    {
        $type = $field['type'];
        $required = !empty($field['required']);
        $rules = [];

        // Regla obligatorio/opcional
        $rules[] = $required ? 'required' : 'nullable';

        // Reglas específicas por tipo
        match ($type) {
            'text' => array_push($rules, 'string', 'max:' . ($field['max_length'] ?? 255)),

            'textarea' => array_push($rules, 'string', 'max:' . ($field['max_length'] ?? 5000)),

            'email' => array_push($rules, app()->environment('production') ? 'email:rfc,dns' : 'email:rfc', 'max:255'),

            'tel' => array_push($rules, 'string', 'regex:/^[+]?[0-9\s\-()]{6,20}$/', 'max:20'),

            'url' => array_push($rules, 'url:http,https', 'max:2048'),

            'password' => array_push($rules, 'string', 'min:' . ($field['min_length'] ?? 8), 'max:128'),

            'number' => $this->addNumberRules($rules, $field),

            'select', 'radio' => $this->addInRules($rules, $field),

            'multiselect' => $this->addMultiSelectRules($rules, $field),

            'checkbox' => $this->addCheckboxRules($rules, $field),

            'toggle' => $rules[] = 'boolean',

            'date' => $this->addDateRules($rules, $field),

            'datetime' => array_push($rules, 'date_format:Y-m-d H:i'),

            'time' => array_push($rules, 'date_format:H:i'),

            'file' => $this->addFileRules($rules, $field),

            'rating' => array_push($rules, 'integer', 'min:1', 'max:' . ($field['max_rating'] ?? 5)),

            'slider' => $this->addNumberRules($rules, $field),

            'color' => array_push($rules, 'string', 'regex:/^#[0-9a-fA-F]{6}$/'),

            'hidden' => array_push($rules, 'string', 'max:1000'),

            default => $rules[] = 'string',
        };

        // Reglas personalizadas del admin (solo reglas seguras permitidas)
        if (!empty($field['validation_rules'])) {
            $customRules = array_map('trim', explode('|', $field['validation_rules']));
            $safeRules = $this->filterSafeRules($customRules);
            $rules = array_merge($rules, $safeRules);
        }

        return $rules;
    }

    /**
     * Reglas para campos numéricos (number, slider).
     */
    private function addNumberRules(array &$rules, array $field): void
    {
        $rules[] = 'numeric';
        if (isset($field['min'])) {
            $rules[] = 'min:' . $field['min'];
        }
        if (isset($field['max'])) {
            $rules[] = 'max:' . $field['max'];
        }
    }

    /**
     * Reglas para campos de selección única con opciones predefinidas.
     */
    private function addInRules(array &$rules, array $field): void
    {
        $options = $this->extractOptionValues($field);
        if ($options) {
            $rules[] = 'in:' . implode(',', $options);
        }
    }

    /**
     * Reglas para selección múltiple.
     */
    private function addMultiSelectRules(array &$rules, array $field): void
    {
        $rules[] = 'array';
        // Limitar tamaño máximo del array para evitar payload bombing
        $rules[] = 'max:' . ($field['max_selections'] ?? 50);
    }

    /**
     * Reglas para checkbox (puede ser boolean o array de opciones).
     */
    private function addCheckboxRules(array &$rules, array $field): void
    {
        $options = $this->extractOptionValues($field);
        if ($options) {
            $rules[] = 'array';
            $rules[] = 'max:' . count($options);
        } else {
            $rules[] = 'boolean';
        }
    }

    /**
     * Reglas para campos de fecha con restricciones opcionales.
     */
    private function addDateRules(array &$rules, array $field): void
    {
        $rules[] = 'date';
        if (!empty($field['min_date'])) {
            $rules[] = 'after_or_equal:' . $field['min_date'];
        }
        if (!empty($field['max_date'])) {
            $rules[] = 'before_or_equal:' . $field['max_date'];
        }
    }

    /**
     * Reglas para subida de archivos con restricciones de tipo y tamaño.
     */
    private function addFileRules(array &$rules, array $field): void
    {
        $rules[] = 'file';
        $rules[] = 'max:' . ($field['max_file_size'] ?? 10240); // 10MB por defecto
        if (!empty($field['allowed_extensions'])) {
            $mimes = is_array($field['allowed_extensions'])
                ? implode(',', $field['allowed_extensions'])
                : $field['allowed_extensions'];
            $rules[] = 'mimes:' . $mimes;
        }
    }

    /**
     * Extraer los valores de las opciones de un campo de selección.
     *
     * Soporta formatos: ['valor1', 'valor2'] o [{'value': 'v', 'label': 'l'}]
     *
     * @return array<string>
     */
    private function extractOptionValues(array $field): array
    {
        $options = $field['options'] ?? [];

        return collect($options)->map(function ($option) {
            if (is_array($option)) {
                return $option['value'] ?? $option['key'] ?? '';
            }
            return (string) $option;
        })->filter()->values()->all();
    }

    /**
     * Generar reglas de validación para cada elemento individual de un array.
     *
     * Evita que un atacante envíe valores arbitrarios dentro de arrays
     * de multiselect o checkbox.
     *
     * @return array<string>|null
     */
    private function getArrayItemRules(array $field): ?array
    {
        $type = $field['type'] ?? '';

        if (!in_array($type, ['multiselect', 'checkbox'], true)) {
            return null;
        }

        $options = $this->extractOptionValues($field);
        if (empty($options)) {
            return null;
        }

        return ['string', 'in:' . implode(',', $options)];
    }

    /**
     * Filtrar reglas personalizadas del admin contra una whitelist de reglas seguras.
     *
     * Previene que un admin comprometido inyecte reglas peligrosas como
     * 'exists:users,password' que podrían filtrar información.
     *
     * @return array<string>
     */
    private function filterSafeRules(array $rules): array
    {
        // Solo prefijos de reglas consideradas seguras
        $safePatterns = [
            'required', 'nullable', 'string', 'numeric', 'integer', 'boolean',
            'email', 'url', 'date', 'date_format', 'before', 'after',
            'before_or_equal', 'after_or_equal', 'in', 'not_in',
            'min', 'max', 'between', 'size', 'digits', 'digits_between',
            'regex', 'alpha', 'alpha_num', 'alpha_dash', 'confirmed',
            'file', 'image', 'mimes', 'mimetypes', 'dimensions',
            'accepted', 'declined', 'filled', 'present',
        ];

        return array_values(array_filter($rules, function (string $rule) use ($safePatterns) {
            $ruleName = explode(':', $rule)[0];
            return in_array($ruleName, $safePatterns, true);
        }));
    }
}
