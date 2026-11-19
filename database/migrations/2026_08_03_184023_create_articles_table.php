<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de artículos del CMS.
 *
 * Soporta estados (borrador, publicado, programado),
 * campos SEO, imagen destacada, campos extra dinámicos
 * (definidos por la categoría asociada), contador de vistas
 * y tiempo de lectura calculado automáticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();

            // Contenido
            $table->text('excerpt')->nullable();     // Resumen para listados
            $table->longText('body');                // Contenido principal (HTML)
            $table->string('featured_image')->nullable();

            // Relaciones
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();

            $table->foreignId('author_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Estado y publicación
            $table->string('status')->default('draft'); // draft, published, scheduled
            $table->timestamp('published_at')->nullable();

            /*
             * Valores de campos extra (definidos por la categoría).
             * Formato JSON:
             * {"precio": 29.99, "color": "Rojo"}
             */
            $table->json('extra_fields_data')->nullable();

            // SEO específico del artículo
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_og_image')->nullable();

            // Métricas
            $table->unsignedInteger('reading_time')->nullable();  // Minutos (calculado auto)
            $table->unsignedBigInteger('views_count')->default(0);

            // Destacado
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Índice compuesto para listados públicos ultra-rápidos
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['status', 'published_at']);
            $table->index('is_featured');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
