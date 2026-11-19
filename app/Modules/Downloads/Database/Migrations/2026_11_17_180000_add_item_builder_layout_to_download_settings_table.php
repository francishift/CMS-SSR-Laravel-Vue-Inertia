<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade soporte para maquetar visualmente la plantilla de Ficha Individual de Descarga
     * en el Page Builder (`use_item_builder_layout` + `item_layout_json`).
     */
    public function up(): void
    {
        $table = (string) config('downloads.tables.settings', 'download_settings');

        if (Schema::hasTable($table)) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (!Schema::hasColumn($table, 'use_item_builder_layout')) {
                    $blueprint->boolean('use_item_builder_layout')->default(false)->after('layout_json');
                }
                if (!Schema::hasColumn($table, 'item_layout_json')) {
                    $blueprint->json('item_layout_json')->nullable()->after('use_item_builder_layout');
                }
            });
        }
    }

    public function down(): void
    {
        $table = (string) config('downloads.tables.settings', 'download_settings');

        if (Schema::hasTable($table)) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $cols = [];
                if (Schema::hasColumn($table, 'use_item_builder_layout')) {
                    $cols[] = 'use_item_builder_layout';
                }
                if (Schema::hasColumn($table, 'item_layout_json')) {
                    $cols[] = 'item_layout_json';
                }
                if (!empty($cols)) {
                    $blueprint->dropColumn($cols);
                }
            });
        }
    }
};
