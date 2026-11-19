<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añadir article_id y page_id a menu_items para poder
 * enlazar directamente a artículos y páginas estáticas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->after('category_id')
                ->constrained('articles')->nullOnDelete();
            $table->foreignId('page_id')->nullable()->after('article_id')
                ->constrained('pages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
            $table->dropConstrainedForeignId('page_id');
        });
    }
};
