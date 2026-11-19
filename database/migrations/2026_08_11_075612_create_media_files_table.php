<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para la tabla centralizada de archivos multimedia.
 *
 * Almacena metadatos de todos los archivos subidos (imágenes y vídeos)
 * para la Biblioteca de Medios del CMS. Diseñada para escalar a miles
 * de registros con índices optimizados para filtrado y búsqueda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Identificación del archivo
            $table->string('filename', 255)->index()->comment('Nombre normalizado SEO en disco (mi-foto.jpg)');
            $table->string('original_filename', 255)->comment('Nombre original tal cual lo subió el usuario');
            $table->string('disk', 30)->default('public')->comment('Disco de almacenamiento (public, s3, etc.)');
            $table->string('path', 500)->index()->comment('Ruta relativa en storage (media/mi-foto.jpg)');

            // Tipo y formato
            $table->string('mime_type', 100)->index();
            $table->enum('type', ['image', 'video'])->index()->comment('Tipo principal del archivo');
            $table->string('extension', 10)->comment('Extensión normalizada (jpg, png, mp4, etc.)');

            // Dimensiones y peso
            $table->unsignedBigInteger('size')->comment('Tamaño en bytes');
            $table->unsignedInteger('width')->nullable()->comment('Ancho en px (solo imágenes/vídeos)');
            $table->unsignedInteger('height')->nullable()->comment('Alto en px (solo imágenes/vídeos)');

            // SEO y metadatos
            $table->string('alt_text', 255)->nullable()->comment('Texto alternativo SEO para imágenes');
            $table->string('title', 255)->nullable()->comment('Título descriptivo del archivo');
            $table->string('collection', 100)->nullable()->index()->comment('Colección/etiqueta (logos, artículos, fondos, etc.)');

            // Auditoría
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Índices compuestos para rendimiento en listados con filtros
            $table->index(['type', 'created_at']);
            $table->index(['collection', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
