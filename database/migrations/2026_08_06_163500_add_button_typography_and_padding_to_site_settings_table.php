<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('button_font_size', 20)->default('text-sm')->after('button_border_radius');
            $table->string('button_padding_x', 20)->default('px-5')->after('button_font_size');
            $table->string('button_padding_y', 20)->default('py-2.5')->after('button_padding_x');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'button_font_size',
                'button_padding_x',
                'button_padding_y',
            ]);
        });
    }
};
