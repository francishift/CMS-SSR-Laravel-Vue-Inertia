<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('site_settings', 'button_primary_bg_dark')) {
                $table->string('button_primary_bg_dark', 7)->nullable()->after('button_secondary_text');
            }
            if (!Schema::hasColumn('site_settings', 'button_primary_text_dark')) {
                $table->string('button_primary_text_dark', 7)->nullable()->after('button_primary_bg_dark');
            }
            if (!Schema::hasColumn('site_settings', 'button_secondary_bg_dark')) {
                $table->string('button_secondary_bg_dark', 7)->nullable()->after('button_primary_text_dark');
            }
            if (!Schema::hasColumn('site_settings', 'button_secondary_text_dark')) {
                $table->string('button_secondary_text_dark', 7)->nullable()->after('button_secondary_bg_dark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach ([
                'button_primary_bg_dark',
                'button_primary_text_dark',
                'button_secondary_bg_dark',
                'button_secondary_text_dark',
            ] as $col) {
                if (Schema::hasColumn('site_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
