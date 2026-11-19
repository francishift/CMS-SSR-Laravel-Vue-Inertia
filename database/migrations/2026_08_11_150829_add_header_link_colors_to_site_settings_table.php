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
        Schema::table('site_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('site_settings', 'link_color_dark')) {
                $table->string('link_color_dark', 7)->nullable()->after('link_hover_color');
            }
            if (!Schema::hasColumn('site_settings', 'link_hover_color_dark')) {
                $table->string('link_hover_color_dark', 7)->nullable()->after('link_color_dark');
            }
            if (!Schema::hasColumn('site_settings', 'header_nav_color_light')) {
                $table->string('header_nav_color_light', 7)->nullable()->after('header_border_radius');
            }
            if (!Schema::hasColumn('site_settings', 'header_nav_hover_light')) {
                $table->string('header_nav_hover_light', 7)->nullable()->after('header_nav_color_light');
            }
            if (!Schema::hasColumn('site_settings', 'header_nav_color_dark')) {
                $table->string('header_nav_color_dark', 7)->nullable()->after('header_nav_hover_light');
            }
            if (!Schema::hasColumn('site_settings', 'header_nav_hover_dark')) {
                $table->string('header_nav_hover_dark', 7)->nullable()->after('header_nav_color_dark');
            }
            if (!Schema::hasColumn('site_settings', 'header_nav_active_color')) {
                $table->string('header_nav_active_color', 7)->nullable()->after('header_nav_hover_dark');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'link_color_dark',
                'link_hover_color_dark',
                'header_nav_color_light',
                'header_nav_hover_light',
                'header_nav_color_dark',
                'header_nav_hover_dark',
                'header_nav_active_color',
            ]);
        });
    }
};
