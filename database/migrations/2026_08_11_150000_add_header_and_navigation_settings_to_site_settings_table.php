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
            $table->string('header_layout', 40)->default('logo_left_menu_right')->after('header_style');
            $table->string('header_width', 30)->default('contained')->after('header_layout');
            $table->string('header_padding_y', 30)->default('standard')->after('header_width');
            $table->string('header_bg_type', 30)->default('glassmorphism')->after('header_padding_y');
            $table->string('header_shadow', 30)->default('border')->after('header_bg_type');
            $table->boolean('header_cta_enable')->default(false)->after('header_shadow');
            $table->string('header_cta_text', 100)->nullable()->after('header_cta_enable');
            $table->string('header_cta_url', 500)->nullable()->after('header_cta_text');
            $table->string('header_cta_icon', 100)->nullable()->after('header_cta_url');
            $table->string('header_cta_variant', 30)->default('primary')->after('header_cta_icon');
            $table->string('mobile_menu_style', 30)->default('drawer_right')->after('header_cta_variant');
            $table->boolean('mobile_show_socials')->default(true)->after('mobile_menu_style');
            $table->boolean('mobile_show_cta')->default(true)->after('mobile_show_socials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'header_layout',
                'header_width',
                'header_padding_y',
                'header_bg_type',
                'header_shadow',
                'header_cta_enable',
                'header_cta_text',
                'header_cta_url',
                'header_cta_icon',
                'header_cta_variant',
                'mobile_menu_style',
                'mobile_show_socials',
                'mobile_show_cta',
            ]);
        });
    }
};
