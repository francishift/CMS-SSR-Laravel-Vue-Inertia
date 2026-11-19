<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de auditoría y telemetría de descargas (RGPD friendly mediante hash de IP).
 */
return new class extends Migration
{
    public function up(): void
    {
        $logsTable = config('downloads.tables.logs', 'download_logs');
        $itemsTable = config('downloads.tables.items', 'download_items');

        Schema::create($logsTable, function (Blueprint $table) use ($itemsTable) {
            $table->id();
            $table->foreignId('download_item_id')
                ->constrained($itemsTable)
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('ip_hash', 64)->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('version_downloaded', 50)->nullable();
            $table->string('access_type_snapshot', 30)->default('free');
            $table->timestamp('created_at')->useCurrent()->index();

            // Índice compuesto para analíticas diferidas rápidas por ítem y rango de fechas
            $table->index(['download_item_id', 'created_at'], 'idx_dl_logs_item_date');
        });
    }

    public function down(): void
    {
        $logsTable = config('downloads.tables.logs', 'download_logs');
        Schema::dropIfExists($logsTable);
    }
};
