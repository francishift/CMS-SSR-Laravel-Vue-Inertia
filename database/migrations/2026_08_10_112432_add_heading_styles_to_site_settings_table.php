<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añadir columna JSON heading_styles para configuración tipográfica
 * por nivel de encabezado (H1–H6): tamaño, letter-spacing y line-height.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('heading_styles')->nullable()->after('body_font');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('heading_styles');
        });
    }
};
