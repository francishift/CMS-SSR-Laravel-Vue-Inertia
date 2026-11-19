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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_breadcrumb')->default(true)->after('per_page');
            $table->boolean('show_pagination')->default(true)->after('show_breadcrumb');
            $table->boolean('show_author')->default(true)->after('show_pagination');
            $table->boolean('show_date')->default(true)->after('show_author');
            $table->boolean('show_reading_time')->default(true)->after('show_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'show_breadcrumb',
                'show_pagination',
                'show_author',
                'show_date',
                'show_reading_time',
            ]);
        });
    }
};
