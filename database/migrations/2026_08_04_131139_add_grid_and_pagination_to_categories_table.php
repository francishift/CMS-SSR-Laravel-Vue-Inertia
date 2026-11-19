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
            $table->unsignedTinyInteger('cols_mobile')->default(1)->after('template');
            $table->unsignedTinyInteger('cols_tablet')->default(2)->after('cols_mobile');
            $table->unsignedTinyInteger('cols_desktop')->default(3)->after('cols_tablet');
            $table->string('gap_size')->default('md')->after('cols_desktop');
            $table->string('align_grid')->default('start')->after('gap_size');
            $table->unsignedSmallInteger('per_page')->default(12)->after('align_grid');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['cols_mobile', 'cols_tablet', 'cols_desktop', 'gap_size', 'align_grid', 'per_page']);
        });
    }
};
