<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('logo_invert')->default(false)->after('logo_path');
            $table->unsignedInteger('logo_brightness')->default(100)->after('logo_invert');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_invert', 'logo_brightness']);
        });
    }
};
