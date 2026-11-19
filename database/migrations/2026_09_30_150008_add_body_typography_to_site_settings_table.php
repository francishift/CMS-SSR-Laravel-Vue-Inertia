<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade columnas de tipografía del body (tamaño, grosor, interlineado y espaciado)
 * a la tabla site_settings para permitir personalización completa desde el panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('body_font_size', 30)->nullable()->after('body_font');
            $table->string('body_font_weight', 30)->nullable()->after('body_font_size');
            $table->string('body_line_height', 30)->nullable()->after('body_font_weight');
            $table->string('body_letter_spacing', 30)->nullable()->after('body_line_height');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'body_font_size',
                'body_font_weight',
                'body_line_height',
                'body_letter_spacing',
            ]);
        });
    }
};
