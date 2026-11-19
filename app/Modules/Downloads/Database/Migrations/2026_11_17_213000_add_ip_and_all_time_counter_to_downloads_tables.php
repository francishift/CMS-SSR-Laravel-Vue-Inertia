<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Incorpora IP real de origen y referer en `download_logs` (solo visible para administradores en backend)
 * y el contador histórico global perpetuo `total_downloads_all_time` en `download_settings`
 * para que ningún purgado de logs ni borrado de ítems antiguos altere el acumulado histórico general.
 */
return new class extends Migration
{
    public function up(): void
    {
        $logsTable = (string) config('downloads.tables.logs', 'download_logs');
        $settingsTable = (string) config('downloads.tables.settings', 'download_settings');
        $itemsTable = (string) config('downloads.tables.items', 'download_items');

        Schema::table($logsTable, function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('user_id')->index();
            $table->string('referer_url', 500)->nullable()->after('user_agent');
        });

        Schema::table($settingsTable, function (Blueprint $table) {
            $table->unsignedBigInteger('total_downloads_all_time')
                ->default(0)
                ->after('show_downloads_count_public');
        });

        // Inicializar el histórico global perpetuo con la suma actual de descargas existentes
        if (Schema::hasTable($itemsTable) && Schema::hasTable($settingsTable)) {
            $sum = (int) DB::table($itemsTable)->sum('downloads_count');
            if ($sum > 0) {
                DB::table($settingsTable)->update(['total_downloads_all_time' => $sum]);
            }
        }
    }

    public function down(): void
    {
        $logsTable = (string) config('downloads.tables.logs', 'download_logs');
        $settingsTable = (string) config('downloads.tables.settings', 'download_settings');

        Schema::table($logsTable, function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropColumn(['ip_address', 'referer_url']);
        });

        Schema::table($settingsTable, function (Blueprint $table) {
            $table->dropColumn('total_downloads_all_time');
        });
    }
};
