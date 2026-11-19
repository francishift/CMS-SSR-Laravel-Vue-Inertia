<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de menús de navegación.
 *
 * Cada menú tiene una ubicación (header, footer, sidebar)
 * y contiene items definidos en menu_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // "Menú Principal"
            $table->string('slug')->unique();               // "principal"
            $table->string('location');                     // "header", "footer", "sidebar"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
