<?php

namespace Tests\Feature\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración para el controlador público de envío de formularios.
 *
 * Verifica el endpoint POST /formularios/{slug}/enviar con todos los
 * escenarios: envío válido, validación, formulario inactivo, rate limit,
 * honeypot y formulario inexistente.
 */
class PublicFormControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function envio_valido_devuelve_json_con_exito(): void
    {
        $form = $this->crearFormularioActivo();

        $response = $this->postJson("/formularios/{$form->slug}/enviar", [
            'fields' => [
                'nombre' => 'Juan García',
                'email'  => 'juan@example.com',
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Formulario enviado correctamente',
        ]);

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
        ]);
    }

    #[Test]
    public function envio_con_campos_invalidos_devuelve_422(): void
    {
        $form = $this->crearFormularioActivo();

        $response = $this->postJson("/formularios/{$form->slug}/enviar", [
            'fields' => [
                'nombre' => '',
                'email'  => 'no-es-email',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure(['errors']);
    }

    #[Test]
    public function formulario_inexistente_devuelve_404(): void
    {
        $response = $this->postJson('/formularios/no-existe/enviar', [
            'fields' => ['nombre' => 'Test'],
        ]);

        $response->assertNotFound();
    }

    #[Test]
    public function formulario_inactivo_devuelve_404(): void
    {
        $form = $this->crearFormularioActivo();
        $form->update(['is_active' => false]);

        $response = $this->postJson("/formularios/{$form->slug}/enviar", [
            'fields' => ['nombre' => 'Test', 'email' => 'test@test.com'],
        ]);

        $response->assertNotFound();
    }

    #[Test]
    public function honeypot_relleno_guarda_como_spam(): void
    {
        $form = $this->crearFormularioActivo();
        $form->update(['honeypot_field' => 'website_hp']);

        $response = $this->postJson("/formularios/{$form->slug}/enviar", [
            'fields'     => ['nombre' => 'Bot', 'email' => 'bot@spam.com'],
            'website_hp' => 'http://spam.com',
        ]);

        $response->assertOk();

        $submission = FormSubmission::latest()->first();
        $this->assertTrue($submission->is_spam);
    }

    #[Test]
    public function limite_por_ip_bloquea_envios_excesivos(): void
    {
        $form = $this->crearFormularioActivo();
        $form->update(['max_submissions_per_ip' => 1]);

        // Primer envío: OK
        $this->postJson("/formularios/{$form->slug}/enviar", [
            'fields' => ['nombre' => 'Primero', 'email' => 'a@test.com'],
        ])->assertOk();

        // Segundo envío: bloqueado
        $response = $this->postJson("/formularios/{$form->slug}/enviar", [
            'fields' => ['nombre' => 'Segundo', 'email' => 'b@test.com'],
        ]);

        $response->assertStatus(429);
        $response->assertJson(['success' => false]);
    }

    #[Test]
    public function envio_registra_ip_y_user_agent(): void
    {
        $form = $this->crearFormularioActivo();

        $this->postJson(
            "/formularios/{$form->slug}/enviar",
            ['fields' => ['nombre' => 'Ana', 'email' => 'ana@test.com']],
            ['HTTP_USER_AGENT' => 'TestBrowser/1.0']
        )->assertOk();

        $submission = FormSubmission::latest()->first();
        $this->assertEquals('TestBrowser/1.0', $submission->user_agent);
        $this->assertNotEmpty($submission->ip_address);
    }

    #[Test]
    public function ruta_publica_no_requiere_autenticacion(): void
    {
        $form = $this->crearFormularioActivo();

        // Sin autenticar
        $response = $this->postJson("/formularios/{$form->slug}/enviar", [
            'fields' => ['nombre' => 'Anónimo', 'email' => 'anon@test.com'],
        ]);

        $response->assertOk();
    }

    #[Test]
    public function schema_incluye_link_url_y_link_target(): void
    {
        $form = Form::create([
            'name'          => 'Test Toggle Link',
            'slug'          => 'test-toggle-link-' . uniqid(),
            'fields_schema' => [
                [
                    'id'          => 'campo_toggle',
                    'type'        => 'toggle',
                    'name'        => 'politica',
                    'label'       => '¿Acepta la política de privacidad?',
                    'required'    => true,
                    'link_url'    => '/politica-de-privacidad',
                    'link_target' => 'modal',
                ],
            ],
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/formularios/{$form->slug}");

        $response->assertOk();
        $response->assertJsonPath('fields_schema.0.link_url', '/politica-de-privacidad');
        $response->assertJsonPath('fields_schema.0.link_target', 'modal');
    }

    #[Test]
    public function endpoint_pagina_modal_devuelve_contenido_limpio(): void
    {
        $page = \App\Models\Page::create([
            'title'       => 'Política de Privacidad',
            'slug'        => 'politica-de-privacidad',
            'body'        => '<p>Texto legal completo.</p>',
            'is_active'   => true,
            'is_homepage' => false,
        ]);

        $response = $this->getJson('/api/paginas/politica-de-privacidad');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'title'   => 'Política de Privacidad',
            'slug'    => 'politica-de-privacidad',
            'body'    => '<p>Texto legal completo.</p>',
        ]);
    }

    #[Test]
    public function endpoint_pagina_modal_devuelve_404_si_inactiva_o_inexistente(): void
    {
        $response = $this->getJson('/api/paginas/pagina-que-no-existe');
        $response->assertStatus(404);

        $inactiva = \App\Models\Page::create([
            'title'     => 'Inactiva',
            'slug'      => 'pagina-inactiva',
            'body'      => '<p>Texto</p>',
            'is_active' => false,
        ]);

        $responseInactiva = $this->getJson('/api/paginas/pagina-inactiva');
        $responseInactiva->assertStatus(404);
    }

    /**
     * Helper para crear un formulario de test activo.
     */
    private function crearFormularioActivo(): Form
    {
        return Form::create([
            'name'          => 'Test Contacto',
            'slug'          => 'test-contacto-' . uniqid(),
            'fields_schema' => [
                ['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
            ],
            'is_active' => true,
        ]);
    }
}
