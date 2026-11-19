<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añadir campo meta_robots a páginas, artículos y categorías.
 *
 * Permite configurar la directiva <meta name="robots"> por entidad
 * para controlar la indexación (noindex) y el seguimiento de enlaces (nofollow).
 * Valor por defecto: null → equivale a "index, follow" (comportamiento estándar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('meta_robots', 50)->nullable()->after('seo_og_image')
                ->comment('Directiva robots: index,follow | noindex,follow | noindex,nofollow | index,nofollow');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('meta_robots', 50)->nullable()->after('seo_og_image')
                ->comment('Directiva robots: index,follow | noindex,follow | noindex,nofollow | index,nofollow');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('meta_robots', 50)->nullable()->after('seo_og_image')
                ->comment('Directiva robots: index,follow | noindex,follow | noindex,nofollow | index,nofollow');
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('meta_robots'));
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('meta_robots'));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('meta_robots'));
    }
};
