<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade la columna published_until para soportar fecha de finalización / expiración en artículos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->timestamp('published_until')->nullable()->after('published_at');
            $table->index(['status', 'published_at', 'published_until']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at', 'published_until']);
            $table->dropColumn('published_until');
        });
    }
};
