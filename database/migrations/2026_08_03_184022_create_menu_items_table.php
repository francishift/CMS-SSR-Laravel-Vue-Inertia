<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de items de menú.
 *
 * Soporta anidamiento (parent_id) para submenús,
 * enlaces a URLs externas, rutas internas o categorías.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('menu_id')
                  ->constrained('menus')
                  ->cascadeOnDelete();

            // Anidamiento: parent_id apunta a otro item del mismo menú
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('menu_items')
                  ->nullOnDelete();

            $table->string('title');                        // Texto visible del enlace
            $table->string('url')->nullable();              // URL externa (https://...)
            $table->string('route_name')->nullable();       // Ruta interna de Laravel
            $table->foreignId('category_id')                // Enlace directo a categoría
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();
            $table->string('target', 10)->default('_self'); // _self o _blank
            $table->string('icon')->nullable();             // Clase de icono (PrimeIcons)
            $table->unsignedInteger('order')->default(0);   // Orden de aparición
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índice para queries de listado ordenado
            $table->index(['menu_id', 'parent_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
