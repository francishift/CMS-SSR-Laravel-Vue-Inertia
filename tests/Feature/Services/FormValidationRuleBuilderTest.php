<?php

namespace Tests\Feature\Services;

use App\Models\Form;
use App\Services\FormValidationRuleBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas unitarias para el generador de reglas de validación dinámicas.
 *
 * Verifica que cada tipo de campo genera las reglas de Laravel correctas,
 * que los campos decorativos se omiten, y que las opciones de select
 * se validan estrictamente con 'in:'.
 */
class FormValidationRuleBuilderTest extends TestCase
{
    use RefreshDatabase;

    private FormValidationRuleBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new FormValidationRuleBuilder();
    }

    #[Test]
    public function genera_reglas_para_campo_text_obligatorio(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre', 'required' => true],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertArrayHasKey('fields.nombre', $rules);
        $this->assertContains('required', $rules['fields.nombre']);
        $this->assertContains('string', $rules['fields.nombre']);
        $this->assertContains('max:255', $rules['fields.nombre']);
    }

    #[Test]
    public function genera_reglas_para_campo_email(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'email', 'name' => 'correo', 'label' => 'Email', 'required' => true],
        ]);

        $rules = $this->builder->buildRules($form);

        // En entorno de test se usa email:rfc (sin dns)
        $emailRule = collect($rules['fields.correo'])->first(fn ($r) => str_starts_with($r, 'email:'));
        $this->assertNotNull($emailRule);
        $this->assertContains('max:255', $rules['fields.correo']);
    }

    #[Test]
    public function genera_reglas_para_campo_tel_con_regex(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'tel', 'name' => 'telefono', 'label' => 'Teléfono', 'required' => false],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('nullable', $rules['fields.telefono']);
        $this->assertContains('regex:/^[+]?[0-9\s\-()]{6,20}$/', $rules['fields.telefono']);
    }

    #[Test]
    public function genera_reglas_para_select_con_opciones_in(): void
    {
        $form = $this->crearFormularioConCampos([
            [
                'type'    => 'select',
                'name'    => 'tipo',
                'label'   => 'Tipo',
                'required' => true,
                'options' => [
                    ['value' => 'consulta', 'label' => 'Consulta'],
                    ['value' => 'presupuesto', 'label' => 'Presupuesto'],
                ],
            ],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('in:consulta,presupuesto', $rules['fields.tipo']);
    }

    #[Test]
    public function genera_reglas_para_multiselect_como_array_con_validacion_por_elemento(): void
    {
        $form = $this->crearFormularioConCampos([
            [
                'type'    => 'multiselect',
                'name'    => 'servicios',
                'label'   => 'Servicios',
                'required' => false,
                'options' => ['web', 'seo', 'branding'],
            ],
        ]);

        $rules = $this->builder->buildRules($form);

        // El array padre se valida como array
        $this->assertContains('array', $rules['fields.servicios']);
        // Cada elemento individual se valida contra las opciones
        $this->assertArrayHasKey('fields.servicios.*', $rules);
        $this->assertContains('in:web,seo,branding', $rules['fields.servicios.*']);
    }

    #[Test]
    public function genera_reglas_para_rating_con_rango(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'rating', 'name' => 'valoracion', 'label' => 'Valoración', 'required' => true],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('integer', $rules['fields.valoracion']);
        $this->assertContains('min:1', $rules['fields.valoracion']);
        $this->assertContains('max:5', $rules['fields.valoracion']);
    }

    #[Test]
    public function genera_reglas_para_file_con_extensiones_permitidas(): void
    {
        $form = $this->crearFormularioConCampos([
            [
                'type'               => 'file',
                'name'               => 'cv',
                'label'              => 'Curriculum',
                'required'           => true,
                'max_file_size'      => 5120,
                'allowed_extensions' => ['pdf', 'doc', 'docx'],
            ],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('file', $rules['fields.cv']);
        $this->assertContains('max:5120', $rules['fields.cv']);
        $this->assertContains('mimes:pdf,doc,docx', $rules['fields.cv']);
    }

    #[Test]
    public function genera_reglas_para_color_con_regex_hex(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'color', 'name' => 'color_fav', 'label' => 'Color favorito', 'required' => false],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('regex:/^#[0-9a-fA-F]{6}$/', $rules['fields.color_fav']);
    }

    #[Test]
    public function omite_campos_decorativos_de_las_reglas(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'separator', 'name' => 'sep1'],
            ['type' => 'heading', 'name' => 'titulo_seccion'],
            ['type' => 'paragraph', 'name' => 'info'],
            ['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre', 'required' => true],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertArrayNotHasKey('fields.sep1', $rules);
        $this->assertArrayNotHasKey('fields.titulo_seccion', $rules);
        $this->assertArrayNotHasKey('fields.info', $rules);
        $this->assertArrayHasKey('fields.nombre', $rules);
    }

    #[Test]
    public function genera_mensajes_personalizados_en_castellano(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'email', 'name' => 'correo', 'label' => 'Correo electrónico', 'required' => true],
        ]);

        $messages = $this->builder->buildMessages($form);

        $this->assertArrayHasKey('fields.correo.required', $messages);
        $this->assertStringContainsString('Correo electrónico', $messages['fields.correo.required']);
        $this->assertStringContainsString('obligatorio', $messages['fields.correo.required']);
    }

    #[Test]
    public function genera_reglas_para_toggle_como_boolean(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'toggle', 'name' => 'newsletter', 'label' => 'Suscripción', 'required' => false],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('boolean', $rules['fields.newsletter']);
    }

    #[Test]
    public function genera_reglas_para_date_con_restricciones(): void
    {
        $form = $this->crearFormularioConCampos([
            [
                'type'     => 'date',
                'name'     => 'fecha',
                'label'    => 'Fecha',
                'required' => true,
                'min_date' => '2026-01-01',
                'max_date' => '2027-12-31',
            ],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('date', $rules['fields.fecha']);
        $this->assertContains('after_or_equal:2026-01-01', $rules['fields.fecha']);
        $this->assertContains('before_or_equal:2027-12-31', $rules['fields.fecha']);
    }

    // ──────────────────────────────────────────────
    // Tests de Seguridad
    // ──────────────────────────────────────────────

    #[Test]
    public function campo_hidden_tiene_limite_maximo_anti_payload_bombing(): void
    {
        $form = $this->crearFormularioConCampos([
            ['type' => 'hidden', 'name' => 'utm_source', 'required' => false],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('max:1000', $rules['fields.utm_source']);
    }

    #[Test]
    public function filtra_reglas_custom_peligrosas_del_admin(): void
    {
        $form = $this->crearFormularioConCampos([
            [
                'type'             => 'text',
                'name'             => 'campo_custom',
                'label'            => 'Custom',
                'required'         => true,
                'validation_rules' => 'min:3|exists:users,password|unique:forms,slug',
            ],
        ]);

        $rules = $this->builder->buildRules($form);

        // 'min:3' es segura y debe pasar
        $this->assertContains('min:3', $rules['fields.campo_custom']);
        // 'exists' y 'unique' son peligrosas (filtran info de BD) y deben ser bloqueadas
        $this->assertNotContains('exists:users,password', $rules['fields.campo_custom']);
        $this->assertNotContains('unique:forms,slug', $rules['fields.campo_custom']);
    }

    #[Test]
    public function checkbox_con_opciones_valida_cada_elemento_individual(): void
    {
        $form = $this->crearFormularioConCampos([
            [
                'type'    => 'checkbox',
                'name'    => 'intereses',
                'label'   => 'Intereses',
                'required' => false,
                'options' => ['musica', 'cine', 'tecnologia'],
            ],
        ]);

        $rules = $this->builder->buildRules($form);

        $this->assertContains('array', $rules['fields.intereses']);
        $this->assertArrayHasKey('fields.intereses.*', $rules);
        $this->assertContains('in:musica,cine,tecnologia', $rules['fields.intereses.*']);
    }

    /**
     * Helper para crear un formulario con campos de prueba.
     */
    private function crearFormularioConCampos(array $campos): Form
    {
        return Form::create([
            'name'          => 'Test Form',
            'slug'          => 'test-form-' . uniqid(),
            'fields_schema' => $campos,
        ]);
    }
}
