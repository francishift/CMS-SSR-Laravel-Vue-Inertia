<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('show_read_more')->default(true)->after('is_featured');
            $table->boolean('card_full_link')->default(true)->after('show_read_more');
            $table->string('read_more_text')->nullable()->default('Leer más')->after('card_full_link');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['show_read_more', 'card_full_link', 'read_more_text']);
        });
    }
};
