<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optimiza los índices de `download_items` para consultas de miles de registros sin `filesort`
 * y habilita búsqueda FULLTEXT nativa en MySQL/MariaDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('downloads.tables.items', 'download_items');

        Schema::table($tableName, function (Blueprint $table) {
            // Índice compuesto de igualdad + ordenación para evitar Using filesort en ORDER BY `order` ASC
            $table->index(['status', 'deleted_at', 'order'], 'idx_dl_items_status_del_order');
        });

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->fullText(['title', 'subtitle', 'short_description'], 'ft_dl_items_search');
            });
        }
    }

    public function down(): void
    {
        $tableName = (string) config('downloads.tables.items', 'download_items');
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropFullText('ft_dl_items_search');
            });
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropIndex('idx_dl_items_status_del_order');
        });
    }
};
