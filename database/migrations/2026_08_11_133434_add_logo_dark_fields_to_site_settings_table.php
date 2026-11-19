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
            $table->string('logo_dark_path', 500)->nullable()->after('logo_brightness');
            $table->boolean('logo_dark_invert')->default(false)->after('logo_dark_path');
            $table->integer('logo_dark_brightness')->default(100)->after('logo_dark_invert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_dark_path', 'logo_dark_invert', 'logo_dark_brightness']);
        });
    }
};
