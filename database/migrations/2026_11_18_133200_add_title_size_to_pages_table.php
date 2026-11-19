<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade el campo title_size a la tabla pages para permitir elegir la escala
 * tipográfica visual del encabezado <h1> en páginas básicas (sin Page Builder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_size', 20)
                ->default('default')
                ->after('title')
                ->comment('Escala visual del h1 en vista básica: default|hero_2xl|hero_xl|h1|h2|h3|h4|h5|h6|base|sm');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'title_size')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('title_size');
            });
        }
    }
};
