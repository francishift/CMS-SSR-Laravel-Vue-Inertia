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
            if (!Schema::hasColumn('site_settings', 'bg_image_light')) {
                $table->text('bg_image_light')->nullable()->after('bg_color_dark');
            }
            if (!Schema::hasColumn('site_settings', 'bg_image_dark')) {
                $table->text('bg_image_dark')->nullable()->after('bg_image_light');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['bg_image_light', 'bg_image_dark']);
        });
    }
};
