<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('accent_color', 7)->nullable()->default('#f59e0b')->after('secondary_color');
            $table->string('bg_color_light', 7)->nullable()->default('#ffffff')->after('accent_color');
            $table->string('bg_color_dark', 7)->nullable()->default('#09090b')->after('bg_color_light');
            $table->string('text_color_light', 7)->nullable()->default('#18181b')->after('bg_color_dark');
            $table->string('text_color_dark', 7)->nullable()->default('#f4f4f5')->after('text_color_light');
            $table->string('container_max_width', 30)->default('max-w-7xl')->after('text_color_dark');
            $table->string('border_radius_theme', 30)->default('rounded-xl')->after('container_max_width');
            $table->string('header_style', 30)->default('sticky')->after('border_radius_theme');
            $table->string('footer_style', 30)->default('columns')->after('header_style');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'accent_color',
                'bg_color_light',
                'bg_color_dark',
                'text_color_light',
                'text_color_dark',
                'container_max_width',
                'border_radius_theme',
                'header_style',
                'footer_style',
            ]);
        });
    }
};
