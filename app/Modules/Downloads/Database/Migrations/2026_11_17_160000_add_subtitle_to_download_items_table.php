<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el campo `subtitle` a la tabla de elementos descargables para mostrar
     * un subtítulo descriptivo en texto pequeño debajo del título principal.
     */
    public function up(): void
    {
        $table = (string) config('downloads.tables.items', 'download_items');

        if (Schema::hasTable($table) && !Schema::hasColumn($table, 'subtitle')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('subtitle', 255)->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        $table = (string) config('downloads.tables.items', 'download_items');

        if (Schema::hasTable($table) && Schema::hasColumn($table, 'subtitle')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('subtitle');
            });
        }
    }
};
