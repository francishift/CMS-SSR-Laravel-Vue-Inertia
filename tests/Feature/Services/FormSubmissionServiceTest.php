<?php

namespace Tests\Feature\Services;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\FormSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración para el servicio de procesamiento de envíos.
 *
 * Verifica el flujo completo: validación, sanitización, honeypot,
 * límite por IP y persistencia en base de datos.
 */
class FormSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private FormSubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FormSubmissionService::class);
    }

    #[Test]
    public function procesa_envio_valido_y_persiste_en_base_de_datos(): void
    {
        $form = $this->crearFormularioContacto();

        $request = Request::create('/api/forms/contacto/submit', 'POST', [
            'fields' => [
                'nombre' => 'Juan García',
                'email'  => 'juan@example.com',
                'mensaje' => 'Hola, quiero información sobre sus servicios.',
            ],
        ]);

        $submission = $this->service->processSubmission($form, $request);

        $this->assertInstanceOf(FormSubmission::class, $submission);
        $this->assertTrue($submission->exists);
        $this->assertEquals('Juan García', $submission->data['nombre']);
        $this->assertEquals('juan@example.com', $submission->data['email']);
        $this->assertFalse($submission->is_spam);
        $this->assertFalse($submission->is_read);
    }

    #[Test]
    public function rechaza_envio_con_campos_obligatorios_vacios(): void
    {
        $form = $this->crearFormularioContacto();

        $request = Request::create('/api/forms/contacto/submit', 'POST', [
            'fields' => [
                'nombre' => '',
                'email'  => 'correo_invalido',
            ],
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->processSubmission($form, $request);
    }

    #[Test]
    public function detecta_spam_mediante_honeypot(): void
    {
        $form = $this->crearFormularioContacto();
        $form->update(['honeypot_field' => 'website_hp']);

        $request = Request::create('/api/forms/contacto/submit', 'POST', [
            'fields' => [
                'nombre'  => 'Bot Spammer',
                'email'   => 'bot@spam.com',
                'mensaje' => 'Compra viagra barato',
            ],
            'website_hp' => 'http://spam-link.com', // Bot rellenó el honeypot
        ]);

        $submission = $this->service->processSubmission($form, $request);

        $this->assertTrue($submission->is_spam);
    }

    #[Test]
    public function honeypot_vacio_no_marca_como_spam(): void
    {
        $form = $this->crearFormularioContacto();
        $form->update(['honeypot_field' => 'website_hp']);

        $request = Request::create('/api/forms/contacto/submit', 'POST', [
            'fields' => [
                'nombre'  => 'María López',
                'email'   => 'maria@example.com',
                'mensaje' => 'Consulta legítima',
            ],
            'website_hp' => '', // Humano: campo vacío
        ]);

        $submission = $this->service->processSubmission($form, $request);

        $this->assertFalse($submission->is_spam);
    }

    #[Test]
    public function bloquea_envio_si_formulario_inactivo(): void
    {
        $form = $this->crearFormularioContacto();
        $form->update(['is_active' => false]);

        $request = Request::create('/api/forms/contacto/submit', 'POST', [
            'fields' => ['nombre' => 'Test', 'email' => 'test@test.com'],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no está aceptando envíos');
        $this->service->processSubmission($form, $request);
    }

    #[Test]
    public function bloquea_envio_si_se_excede_limite_por_ip(): void
    {
        $form = $this->crearFormularioContacto();
        $form->update(['max_submissions_per_ip' => 2]);

        // Crear 2 envíos previos desde la misma IP
        for ($i = 0; $i < 2; $i++) {
            $form->submissions()->create([
                'data'       => ['nombre' => "Test {$i}"],
                'ip_address' => '127.0.0.1',
            ]);
        }

        $request = Request::create('/api/forms/contacto/submit', 'POST', [
            'fields' => ['nombre' => 'Tercero', 'email' => 'tres@test.com', 'mensaje' => 'X'],
        ], server: ['REMOTE_ADDR' => '127.0.0.1']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('límite de envíos');
        $this->service->processSubmission($form, $request);
    }

    #[Test]
    public function sanitiza_html_malicioso_en_campos_de_texto(): void
    {
        $form = $this->crearFormularioContacto();

        $request = Request::create('/api/forms/contacto/submit', 'POST', [
            'fields' => [
                'nombre'  => 'Test<script>alert("xss")</script>',
                'email'   => 'safe@example.com',
                'mensaje' => '<p>Texto válido</p><script>document.cookie</script>',
            ],
        ]);

        $submission = $this->service->processSubmission($form, $request);

        // El script debe haber sido eliminado por el sanitizador
        $this->assertStringNotContainsString('<script>', $submission->data['nombre']);
        $this->assertStringNotContainsString('<script>', $submission->data['mensaje']);
    }

    #[Test]
    public function registra_metadatos_de_seguridad_del_envio(): void
    {
        $form = $this->crearFormularioContacto();

        $request = Request::create(
            uri: '/api/forms/contacto/submit',
            method: 'POST',
            parameters: [
                'fields' => [
                    'nombre'  => 'Ana',
                    'email'   => 'ana@example.com',
                    'mensaje' => 'Test',
                ],
            ],
            server: [
                'REMOTE_ADDR'     => '192.168.1.100',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
                'HTTP_REFERER'    => 'https://tudominio.com/contacto',
            ],
        );

        $submission = $this->service->processSubmission($form, $request);

        $this->assertEquals('192.168.1.100', $submission->ip_address);
        $this->assertEquals('Mozilla/5.0 Test Browser', $submission->user_agent);
        $this->assertEquals('https://tudominio.com/contacto', $submission->referer_url);
    }

    #[Test]
    public function rechaza_valores_invalidos_en_multiselect(): void
    {
        $form = Form::create([
            'name'          => 'Test Select',
            'slug'          => 'test-select-' . uniqid(),
            'fields_schema' => [
                [
                    'type'    => 'multiselect',
                    'name'    => 'servicios',
                    'label'   => 'Servicios',
                    'required' => true,
                    'options' => ['web', 'seo', 'branding'],
                ],
            ],
            'is_active' => true,
        ]);

        $request = Request::create('/api/forms/test/submit', 'POST', [
            'fields' => [
                'servicios' => ['web', '<script>alert(1)</script>'], // Valor inyectado
            ],
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->processSubmission($form, $request);
    }

    /**
     * Helper para crear un formulario de contacto estándar para tests.
     */
    private function crearFormularioContacto(): Form
    {
        return Form::create([
            'name'          => 'Contacto',
            'slug'          => 'contacto-' . uniqid(),
            'fields_schema' => [
                ['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                ['type' => 'textarea', 'name' => 'mensaje', 'label' => 'Mensaje', 'required' => false],
            ],
            'is_active' => true,
        ]);
    }
}
