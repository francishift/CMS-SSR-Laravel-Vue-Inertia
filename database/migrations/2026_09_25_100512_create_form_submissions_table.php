<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para la tabla de envíos recibidos de formularios.
 *
 * Cada registro representa un envío individual con sus datos,
 * metadatos de seguridad y estado de notificaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('form_id')
                ->constrained('forms')
                ->cascadeOnDelete()
                ->comment('Relación con el formulario origen');

            // Datos del envío
            $table->json('data')->comment('Datos enviados (key → value de cada campo)');

            // Metadatos de seguridad y rastreo
            $table->string('ip_address', 45)->comment('IPv4 o IPv6 del remitente');
            $table->string('user_agent', 500)->nullable()->comment('Navegador del remitente');
            $table->string('referer_url', 2048)->nullable()->comment('Página desde la que se envió');

            // Estado administrativo
            $table->boolean('is_read')->default(false)->index()->comment('Leído por el admin');
            $table->boolean('is_spam')->default(false)->index()->comment('Marcado como spam');

            // Estado de notificaciones
            $table->boolean('email_sent_admin')->default(false)->comment('Se envió notificación al admin');
            $table->boolean('email_sent_user')->default(false)->comment('Se envió autorespuesta al usuario');

            $table->timestamps();

            // Índices para consultas frecuentes en la bandeja de envíos
            $table->index(['form_id', 'created_at']);
            $table->index(['form_id', 'is_read']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
