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
            if (!Schema::hasColumn('site_settings', 'header_capsule_shadow')) {
                $table->string('header_capsule_shadow', 30)->default('shadow-xl')->after('header_shadow');
            }
            if (!Schema::hasColumn('site_settings', 'header_capsule_padding')) {
                $table->string('header_capsule_padding', 30)->default('standard')->after('header_capsule_shadow');
            }
            if (!Schema::hasColumn('site_settings', 'header_border_radius')) {
                $table->string('header_border_radius', 30)->default('rounded-2xl')->after('header_capsule_padding');
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
            if (Schema::hasColumn('site_settings', 'header_capsule_shadow')) $cols[] = 'header_capsule_shadow';
            if (Schema::hasColumn('site_settings', 'header_capsule_padding')) $cols[] = 'header_capsule_padding';
            if (Schema::hasColumn('site_settings', 'header_border_radius')) $cols[] = 'header_border_radius';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
