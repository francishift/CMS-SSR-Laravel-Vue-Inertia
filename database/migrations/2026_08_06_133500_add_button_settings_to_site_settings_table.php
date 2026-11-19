<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('button_primary_bg', 7)->nullable()->default('#10b981')->after('accent_color');
            $table->string('button_primary_text', 7)->nullable()->default('#ffffff')->after('button_primary_bg');
            $table->string('button_secondary_bg', 7)->nullable()->default('#6366f1')->after('button_primary_text');
            $table->string('button_secondary_text', 7)->nullable()->default('#ffffff')->after('button_secondary_bg');
            $table->string('button_border_radius', 30)->default('rounded-xl')->after('button_secondary_text');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'button_primary_bg',
                'button_primary_text',
                'button_secondary_bg',
                'button_secondary_text',
                'button_border_radius',
            ]);
        });
    }
};
