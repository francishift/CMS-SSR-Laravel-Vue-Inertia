<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añadir columna de apariencia del widget Turnstile.
     *
     * Valores posibles:
     * - 'managed'         → Widget visible con sello de Cloudflare (por defecto)
     * - 'non-interactive' → Widget compacto sin interacción del usuario
     * - 'invisible'       → Completamente invisible, sin sello ni widget visible
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('captcha_appearance', 20)
                  ->default('managed')
                  ->after('requires_captcha');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('captcha_appearance');
        });
    }
};
