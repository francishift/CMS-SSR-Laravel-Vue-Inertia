<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para la tabla de definición de formularios dinámicos.
 *
 * Cada registro representa un formulario completo con su esquema de campos,
 * configuración de emails, scripts de eventos y políticas de seguridad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('name', 255)->comment('Nombre interno visible en el panel admin');
            $table->string('slug', 255)->unique()->comment('Identificador público para URLs y Builder');
            $table->text('description')->nullable()->comment('Descripción interna opcional');

            // Esquema de campos y configuración general
            $table->json('fields_schema')->comment('Definición ordenada de campos del formulario');
            $table->json('settings')->nullable()->comment('Config general: botón, mensajes, redirección, estilos');

            // Emails al administrador
            $table->boolean('email_admin_enabled')->default(true)->comment('Enviar notificación al admin');
            $table->text('email_admin_template')->nullable()->comment('Plantilla HTML del email al admin (con variables)');
            $table->string('email_admin_subject', 255)->nullable()->comment('Asunto del email al admin');
            $table->string('email_admin_to', 500)->nullable()->comment('Destinatario(s) override. Si null → SiteSetting::contact_email');

            // Autorespuesta al usuario
            $table->boolean('email_user_enabled')->default(false)->comment('Enviar autorespuesta al usuario');
            $table->text('email_user_template')->nullable()->comment('Plantilla HTML de autorespuesta');
            $table->string('email_user_subject', 255)->nullable()->comment('Asunto de la autorespuesta');
            $table->string('email_user_field', 100)->nullable()->comment('Nombre del campo que contiene el email del usuario');

            // Scripts de eventos (ejecutados en frontend)
            $table->text('scripts_before')->nullable()->comment('JS a ejecutar antes de mostrar el formulario');
            $table->text('scripts_on_submit')->nullable()->comment('JS a ejecutar al enviar (antes del POST)');
            $table->text('scripts_after')->nullable()->comment('JS a ejecutar después del envío exitoso');

            // Mensajes y redirección
            $table->string('success_message', 500)->default('Formulario enviado correctamente')->comment('Mensaje tras envío exitoso');
            $table->string('success_redirect_url', 2048)->nullable()->comment('URL de redirección post-envío (opcional)');

            // Seguridad y anti-spam
            $table->unsignedSmallInteger('max_submissions_per_ip')->nullable()->comment('Límite total de envíos por IP');
            $table->boolean('is_active')->default(true)->index()->comment('Formulario habilitado/deshabilitado');
            $table->string('honeypot_field', 100)->nullable()->comment('Nombre del campo trampa anti-bot');
            $table->boolean('requires_captcha')->default(false)->comment('Activar Cloudflare Turnstile');

            $table->timestamps();

            // Índices para búsquedas rápidas en el panel admin
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
