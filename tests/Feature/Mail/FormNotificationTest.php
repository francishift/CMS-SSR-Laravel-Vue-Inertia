<?php

namespace Tests\Feature\Mail;

use App\Mail\FormNotificationMail;
use App\Models\Form;
use App\Services\FormSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormNotificationTest extends TestCase
{
    use RefreshDatabase;

    private FormSubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FormSubmissionService::class);
    }

    public function test_despacha_email_al_admin_cuando_esta_habilitado(): void
    {
        Mail::fake();

        $form = Form::create([
            'name'                => 'Contacto Comercial',
            'slug'                => 'contacto-comercial',
            'fields_schema'       => [
                ['name' => 'nombre', 'type' => 'text', 'label' => 'Nombre', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ],
            'email_admin_enabled' => true,
            'email_admin_to'      => 'admin@ejemplo.com',
            'email_admin_subject' => 'Nuevo lead de {form.name}',
            'email_admin_template'=> '<p>Hola, {field.nombre} ha enviado el formulario.</p>{all_fields}',
            'is_active'           => true,
        ]);

        $request = Request::create('/formularios/contacto-comercial/enviar', 'POST', [
            'fields' => [
                'nombre' => 'Carlos López',
                'email'  => 'carlos@empresa.es',
            ],
        ]);

        $this->service->processSubmission($form, $request);

        Mail::assertSent(FormNotificationMail::class, function ($mail) {
            return $mail->hasTo('admin@ejemplo.com')
                && str_contains($mail->subjectText, 'Contacto Comercial')
                && str_contains($mail->htmlBody, 'Carlos López')
                && str_contains($mail->htmlBody, 'border-collapse: collapse');
        });
    }

    public function test_despacha_autorespuesta_al_usuario_cuando_esta_habilitada(): void
    {
        Mail::fake();

        $form = Form::create([
            'name'                => 'Suscripción',
            'slug'                => 'suscripcion',
            'fields_schema'       => [
                ['name' => 'nombre', 'type' => 'text', 'label' => 'Nombre'],
                ['name' => 'correo_usuario', 'type' => 'email', 'label' => 'Tu Correo'],
            ],
            'email_admin_enabled' => false,
            'email_user_enabled'  => true,
            'email_user_field'    => 'correo_usuario',
            'email_user_subject'  => 'Bienvenido a {form.name}',
            'email_user_template' => '<p>Hola {field.nombre}, gracias por escribirnos.</p>',
            'is_active'           => true,
        ]);

        $request = Request::create('/formularios/suscripcion/enviar', 'POST', [
            'fields' => [
                'nombre'         => 'Laura Gómez',
                'correo_usuario' => 'laura@dominio.com',
            ],
        ]);

        $this->service->processSubmission($form, $request);

        Mail::assertSent(FormNotificationMail::class, function ($mail) {
            return $mail->hasTo('laura@dominio.com')
                && str_contains($mail->subjectText, 'Bienvenido a Suscripción')
                && str_contains($mail->htmlBody, 'Hola Laura Gómez')
                && $mail->isAutoReply === true;
        });
    }

    public function test_no_envia_emails_si_es_detectado_como_spam(): void
    {
        Mail::fake();

        $form = Form::create([
            'name'                => 'Form Spam Test',
            'slug'                => 'form-spam-test',
            'fields_schema'       => [
                ['name' => 'nombre', 'type' => 'text'],
            ],
            'honeypot_field'      => 'website_trampa',
            'email_admin_enabled' => true,
            'email_admin_to'      => 'admin@ejemplo.com',
            'is_active'           => true,
        ]);

        $request = Request::create('/formularios/form-spam-test/enviar', 'POST', [
            'fields'         => ['nombre' => 'Bot Spam'],
            'website_trampa' => 'http://spam.org',
        ]);

        $this->service->processSubmission($form, $request);

        Mail::assertNothingSent();
    }

    public function test_despacha_autorespuesta_con_fallback_inteligente_y_actualiza_flags(): void
    {
        Mail::fake();

        // Formulario con email_user_enabled activo pero email_user_field NULL
        $form = Form::create([
            'name'                => 'Contacto Fallback',
            'slug'                => 'contacto-fallback',
            'fields_schema'       => [
                ['name' => 'nombre', 'type' => 'text', 'label' => 'Nombre'],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email'],
            ],
            'email_admin_enabled' => true,
            'email_admin_to'      => 'admin@empresa.com',
            'email_user_enabled'  => true,
            'email_user_field'    => null, // Nulo intencionado para probar fallback
            'email_user_subject'  => 'Recibido correctamente',
            'email_user_template' => '<p>Hola {field.nombre}, tu consulta ha sido recibida.</p>',
            'is_active'           => true,
        ]);

        $request = Request::create('/formularios/contacto-fallback/enviar', 'POST', [
            'fields' => [
                'nombre' => 'Ana Martínez',
                'email'  => 'ana@cliente.com',
            ],
        ]);

        $submission = $this->service->processSubmission($form, $request);

        // Verifica que se enviaron ambos correos
        Mail::assertSent(FormNotificationMail::class, function ($mail) {
            return $mail->hasTo('ana@cliente.com') && $mail->isAutoReply === true;
        });

        Mail::assertSent(FormNotificationMail::class, function ($mail) {
            return $mail->hasTo('admin@empresa.com') && $mail->isAutoReply === false;
        });

        // Verifica que se actualizaron los flags en la base de datos
        $submission->refresh();
        $this->assertTrue($submission->email_sent_admin);
        $this->assertTrue($submission->email_sent_user);
    }
}

