<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de categorías del CMS.
 *
 * Soporta subcategorías (parent_id), plantillas de layout,
 * campos SEO y un esquema de campos extra (JSON) que define
 * qué campos adicionales tendrán los artículos de esta categoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            // Subcategorías (auto-referencia)
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();

            // Plantilla de layout para la vista pública
            $table->string('template')->default('grid');

            // Imagen, icono y color representativos
            $table->string('featured_image')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();

            /*
             * Esquema de campos extra para los artículos de esta categoría.
             * Formato JSON:
             * [
             *   {"key": "precio", "label": "Precio", "type": "number", "required": false},
             *   {"key": "color", "label": "Color", "type": "select", "options": ["Rojo","Azul"], "required": true}
             * ]
             */
            $table->json('extra_fields_schema')->nullable();

            // SEO específico de la categoría
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            // Orden de aparición en listados
            $table->unsignedInteger('order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índices para queries frecuentes
            $table->index(['is_active', 'order']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
