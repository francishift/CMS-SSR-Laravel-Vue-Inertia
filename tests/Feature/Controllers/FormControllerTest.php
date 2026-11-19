<?php

namespace Tests\Feature\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pruebas de integración para el controlador CRUD de formularios admin.
 *
 * Verifica autenticación, CRUD básico, duplicación, toggle activo,
 * bandeja de envíos y exportación CSV.
 */
class FormControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['active' => true]);
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function listado_requiere_autenticacion_admin(): void
    {
        $response = $this->get('/panel/formularios');
        $response->assertRedirect('/acceso');
    }

    #[Test]
    public function listado_muestra_formularios_paginados(): void
    {
        Form::create([
            'name' => 'Contacto', 'slug' => 'contacto',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);

        $response = $this->actingAs($this->admin)->get('/panel/formularios');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Formularios/Index')
                 ->has('formularios.data', 1)
        );
    }

    #[Test]
    public function crear_formulario_guarda_en_base_de_datos(): void
    {
        $data = [
            'name'          => 'Nuevo Formulario',
            'slug'          => 'nuevo-formulario',
            'fields_schema' => [
                ['id' => 'f1', 'type' => 'text', 'name' => 'nombre', 'label' => 'Nombre', 'required' => true],
                ['id' => 'f2', 'type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
            ],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post('/panel/formularios', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('forms', ['name' => 'Nuevo Formulario', 'slug' => 'nuevo-formulario']);
    }

    #[Test]
    public function editar_formulario_actualiza_datos(): void
    {
        $form = Form::create([
            'name' => 'Original', 'slug' => 'original',
            'fields_schema' => [['id' => 'f1', 'type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);

        $response = $this->actingAs($this->admin)->put("/panel/formularios/{$form->id}", [
            'name'          => 'Actualizado',
            'slug'          => 'original',
            'fields_schema' => [['id' => 'f1', 'type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('forms', ['id' => $form->id, 'name' => 'Actualizado']);
    }

    #[Test]
    public function duplicar_crea_copia_con_nuevo_slug(): void
    {
        $form = Form::create([
            'name' => 'Original', 'slug' => 'original',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);

        $response = $this->actingAs($this->admin)->post("/panel/formularios/{$form->id}/duplicar");

        $response->assertRedirect();
        $this->assertDatabaseCount('forms', 2);
        $this->assertDatabaseHas('forms', ['name' => 'Original (Copia)']);
    }

    #[Test]
    public function toggle_activo_cambia_estado(): void
    {
        $form = Form::create([
            'name' => 'Test', 'slug' => 'test-toggle',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->patch("/panel/formularios/{$form->id}/activar");

        $form->refresh();
        $this->assertFalse($form->is_active);
    }

    #[Test]
    public function eliminar_borra_formulario_y_envios_en_cascada(): void
    {
        $form = Form::create([
            'name' => 'Borrar', 'slug' => 'borrar',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);
        $form->submissions()->create([
            'data'       => ['x' => 'test'],
            'ip_address' => '1.2.3.4',
        ]);

        $this->actingAs($this->admin)->delete("/panel/formularios/{$form->id}");

        $this->assertDatabaseMissing('forms', ['id' => $form->id]);
        $this->assertDatabaseCount('form_submissions', 0);
    }

    #[Test]
    public function bandeja_envios_muestra_datos_paginados(): void
    {
        $form = Form::create([
            'name' => 'Envíos', 'slug' => 'envios-test',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);
        $form->submissions()->create([
            'data'       => ['x' => 'Hola'],
            'ip_address' => '1.2.3.4',
        ]);

        $response = $this->actingAs($this->admin)->get("/panel/formularios/{$form->id}/envios");

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Formularios/Submissions')
                 ->has('envios.data', 1)
        );
    }

    #[Test]
    public function exportar_csv_descarga_archivo(): void
    {
        $form = Form::create([
            'name' => 'CSV Test', 'slug' => 'csv-test',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);
        $form->submissions()->create([
            'data'       => ['nombre' => 'Juan'],
            'ip_address' => '1.2.3.4',
        ]);

        $response = $this->actingAs($this->admin)->get("/panel/formularios/{$form->id}/exportar-csv");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    #[Test]
    public function validacion_rechaza_formulario_sin_campos(): void
    {
        $response = $this->actingAs($this->admin)->post('/panel/formularios', [
            'name'          => 'Sin campos',
            'fields_schema' => [],
        ]);

        $response->assertSessionHasErrors('fields_schema');
    }

    #[Test]
    public function bandeja_global_envios_requiere_autenticacion(): void
    {
        $response = $this->get('/panel/formularios/envios');
        $response->assertRedirect('/acceso');
    }

    #[Test]
    public function bandeja_global_envios_muestra_envios_de_multiples_formularios(): void
    {
        $form1 = Form::create([
            'name' => 'Formulario Uno', 'slug' => 'form-uno',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);
        $form2 = Form::create([
            'name' => 'Formulario Dos', 'slug' => 'form-dos',
            'fields_schema' => [['type' => 'email', 'name' => 'email', 'label' => 'Email']],
        ]);

        $form1->submissions()->create([
            'data' => ['nombre' => 'Remitente Uno'], 'ip_address' => '1.1.1.1', 'is_read' => false,
        ]);
        $form2->submissions()->create([
            'data' => ['email' => 'dos@ejemplo.com'], 'ip_address' => '2.2.2.2', 'is_read' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/panel/formularios/envios');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Formularios/Submissions')
                 ->where('formulario', null)
                 ->has('envios.data', 2)
                 ->has('formulariosDisponibles', 2)
        );
    }

    #[Test]
    public function bandeja_global_envios_filtra_por_formulario(): void
    {
        $form1 = Form::create([
            'name' => 'Formulario Uno', 'slug' => 'form-uno',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);
        $form2 = Form::create([
            'name' => 'Formulario Dos', 'slug' => 'form-dos',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);

        $form1->submissions()->create(['data' => ['nombre' => 'A'], 'ip_address' => '1.1.1.1']);
        $form2->submissions()->create(['data' => ['nombre' => 'B'], 'ip_address' => '2.2.2.2']);

        $response = $this->actingAs($this->admin)->get("/panel/formularios/envios?form_id={$form1->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Formularios/Submissions')
                 ->has('envios.data', 1)
                 ->where('envios.data.0.form_id', $form1->id)
        );
    }

    #[Test]
    public function bandeja_global_envios_filtra_por_estado_no_leidos(): void
    {
        $form = Form::create([
            'name' => 'Contacto', 'slug' => 'contacto',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);

        $form->submissions()->create(['data' => ['nombre' => 'Leido'], 'ip_address' => '1.1.1.1', 'is_read' => true]);
        $form->submissions()->create(['data' => ['nombre' => 'No Leido'], 'ip_address' => '1.1.1.2', 'is_read' => false]);

        $response = $this->actingAs($this->admin)->get('/panel/formularios/envios?estado=no_leidos');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Formularios/Submissions')
                 ->has('envios.data', 1)
                 ->where('envios.data.0.is_read', false)
        );
    }

    #[Test]
    public function bandeja_global_envios_busca_por_texto_en_datos(): void
    {
        $form = Form::create([
            'name' => 'Contacto', 'slug' => 'contacto',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);

        $form->submissions()->create(['data' => ['nombre' => 'Carlos Perez', 'mensaje' => 'Presupuesto urgente'], 'ip_address' => '1.1.1.1']);
        $form->submissions()->create(['data' => ['nombre' => 'Ana Gomez', 'mensaje' => 'Consulta general'], 'ip_address' => '2.2.2.2']);

        $response = $this->actingAs($this->admin)->get('/panel/formularios/envios?buscar=urgente');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Formularios/Submissions')
                 ->has('envios.data', 1)
                 ->where('envios.data.0.data.nombre', 'Carlos Perez')
        );
    }

    #[Test]
    public function marcar_envio_leido_directamente(): void
    {
        $form = Form::create([
            'name' => 'Contacto', 'slug' => 'contacto',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);
        $sub = $form->submissions()->create(['data' => ['x' => '1'], 'ip_address' => '1.1.1.1', 'is_read' => false]);

        $response = $this->actingAs($this->admin)->patchJson("/panel/formularios/envios/{$sub->id}/leer");

        $response->assertOk();
        $sub->refresh();
        $this->assertTrue($sub->is_read);
    }

    #[Test]
    public function alternar_spam_directamente(): void
    {
        $form = Form::create([
            'name' => 'Contacto', 'slug' => 'contacto',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);
        $sub = $form->submissions()->create(['data' => ['x' => '1'], 'ip_address' => '1.1.1.1', 'is_spam' => false]);

        $this->actingAs($this->admin)->patch("/panel/formularios/envios/{$sub->id}/spam");
        $sub->refresh();
        $this->assertTrue($sub->is_spam);

        $this->actingAs($this->admin)->patch("/panel/formularios/envios/{$sub->id}/spam");
        $sub->refresh();
        $this->assertFalse($sub->is_spam);
    }

    #[Test]
    public function eliminar_envio_directamente(): void
    {
        $form = Form::create([
            'name' => 'Contacto', 'slug' => 'contacto',
            'fields_schema' => [['type' => 'text', 'name' => 'x', 'label' => 'X']],
        ]);
        $sub = $form->submissions()->create(['data' => ['x' => '1'], 'ip_address' => '1.1.1.1']);

        $response = $this->actingAs($this->admin)->delete("/panel/formularios/envios/{$sub->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('form_submissions', ['id' => $sub->id]);
    }

    #[Test]
    public function exportar_csv_global_descarga_archivo(): void
    {
        $form = Form::create([
            'name' => 'Formulario Global', 'slug' => 'form-global',
            'fields_schema' => [['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre']],
        ]);
        $form->submissions()->create(['data' => ['nombre' => 'Test'], 'ip_address' => '1.1.1.1', 'is_spam' => false]);

        $response = $this->actingAs($this->admin)->get('/panel/formularios/envios/exportar-csv');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }
}
