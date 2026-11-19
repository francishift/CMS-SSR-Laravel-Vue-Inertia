<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añadir campo is_homepage a la tabla pages.
 *
 * Permite designar una página estática como portada del sitio.
 * Solo una página puede ser homepage a la vez (exclusión mutua gestionada por el servicio).
 * Incluye migración de datos: marca la primera página activa como homepage si existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_homepage')->default(false)->after('is_active');
            $table->index('is_homepage');
        });

        // Migración de datos: marcar la primera página activa como homepage
        $primeraActiva = \App\Models\Page::where('is_active', true)
            ->orderBy('order')
            ->first();

        if ($primeraActiva) {
            $primeraActiva->update(['is_homepage' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['is_homepage']);
            $table->dropColumn('is_homepage');
        });
    }
};
