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
            if (!Schema::hasColumn('site_settings', 'link_decoration')) {
                $table->string('link_decoration', 30)->default('hover')->after('link_hover_color_dark');
            }
            if (!Schema::hasColumn('site_settings', 'link_font_weight')) {
                $table->string('link_font_weight', 30)->default('inherit')->after('link_decoration');
            }
            if (!Schema::hasColumn('site_settings', 'link_underline_offset')) {
                $table->string('link_underline_offset', 30)->default('auto')->after('link_font_weight');
            }
            if (!Schema::hasColumn('site_settings', 'link_underline_style')) {
                $table->string('link_underline_style', 30)->default('solid')->after('link_underline_offset');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('site_settings', 'link_decoration')) $cols[] = 'link_decoration';
            if (Schema::hasColumn('site_settings', 'link_font_weight')) $cols[] = 'link_font_weight';
            if (Schema::hasColumn('site_settings', 'link_underline_offset')) $cols[] = 'link_underline_offset';
            if (Schema::hasColumn('site_settings', 'link_underline_style')) $cols[] = 'link_underline_style';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
