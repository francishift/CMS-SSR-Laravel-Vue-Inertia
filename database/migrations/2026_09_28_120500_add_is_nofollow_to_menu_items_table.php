<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añadir campo is_nofollow a los items de menú.
 *
 * Permite marcar enlaces del menú con rel="nofollow" para SEO,
 * indicando a los motores de búsqueda que no traspasen autoridad (PageRank).
 * Por defecto es false (enlace estándar dofollow).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->boolean('is_nofollow')->default(false)->after('target')
                ->comment('Indica si el enlace debe incluir rel="nofollow"');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('is_nofollow');
        });
    }
};
