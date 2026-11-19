<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade control de altura en píxeles del logotipo (escritorio y móvil)
 * en la tabla site_settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('logo_height')
                ->default(36)
                ->after('logo_dark_brightness')
                ->comment('Altura en px del logotipo en escritorio');

            $table->unsignedSmallInteger('logo_height_mobile')
                ->default(32)
                ->after('logo_height')
                ->comment('Altura en px del logotipo en móvil y cajón lateral');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $cols = array_filter(['logo_height', 'logo_height_mobile'], fn ($c) => Schema::hasColumn('site_settings', $c));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
