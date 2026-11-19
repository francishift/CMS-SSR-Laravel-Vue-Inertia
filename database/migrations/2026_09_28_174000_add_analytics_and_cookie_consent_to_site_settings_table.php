<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para añadir configuración de Analítica y CookieConsent.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Analítica Web (GA4 / GTM)
            $table->boolean('analytics_enabled')->default(false)->after('google_maps_api_key');
            $table->string('analytics_provider', 20)->default('ga4')->after('analytics_enabled');
            $table->string('analytics_measurement_id', 50)->nullable()->after('analytics_provider');
            $table->boolean('analytics_anonymize_ip')->default(true)->after('analytics_measurement_id');

            // Banner y Gestión de Consentimiento de Cookies (Orest Bida)
            $table->boolean('cookie_consent_enabled')->default(true)->after('analytics_anonymize_ip');
            $table->string('cookie_consent_layout', 20)->default('box')->after('cookie_consent_enabled');
            $table->string('cookie_consent_position', 30)->default('bottom right')->after('cookie_consent_layout');
            $table->string('cookie_consent_theme', 20)->default('auto')->after('cookie_consent_position');
            $table->unsignedInteger('cookie_consent_revision')->default(1)->after('cookie_consent_theme');
            $table->string('cookie_consent_privacy_url', 255)->nullable()->after('cookie_consent_revision');
            $table->string('cookie_consent_cookie_url', 255)->nullable()->after('cookie_consent_privacy_url');
            $table->string('cookie_consent_title', 255)->nullable()->after('cookie_consent_cookie_url');
            $table->text('cookie_consent_description')->nullable()->after('cookie_consent_title');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'analytics_enabled',
                'analytics_provider',
                'analytics_measurement_id',
                'analytics_anonymize_ip',
                'cookie_consent_enabled',
                'cookie_consent_layout',
                'cookie_consent_position',
                'cookie_consent_theme',
                'cookie_consent_revision',
                'cookie_consent_privacy_url',
                'cookie_consent_cookie_url',
                'cookie_consent_title',
                'cookie_consent_description',
            ]);
        });
    }
};
