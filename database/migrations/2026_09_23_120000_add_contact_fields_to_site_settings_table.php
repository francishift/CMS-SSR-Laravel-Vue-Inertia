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
            $table->string('whatsapp_phone', 50)->nullable()->after('social_links');
            $table->text('whatsapp_message')->nullable()->after('whatsapp_phone');
            $table->string('contact_email', 100)->nullable()->after('whatsapp_message');
            $table->string('contact_phone', 50)->nullable()->after('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_phone',
                'whatsapp_message',
                'contact_email',
                'contact_phone',
            ]);
        });
    }
};
