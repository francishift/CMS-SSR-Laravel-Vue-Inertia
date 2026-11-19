<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade campo de imagen Open Graph a la tabla de categorías.
 * Permite definir una imagen OG personalizada para compartir en redes sociales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('seo_og_image')->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('seo_og_image');
        });
    }
};
