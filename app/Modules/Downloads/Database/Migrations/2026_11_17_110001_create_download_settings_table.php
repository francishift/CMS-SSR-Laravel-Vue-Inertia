<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de configuración global del portal autónomo de Descargas.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('downloads.tables.settings', 'download_settings');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('page_title', 160)->default('Descargas');
            $table->string('slug', 120)->default('descargas')->index();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable()->default('pi pi-download');

            // Visibilidad global: 'admin_only' (solo backend / admin) | 'public' (abierto e indexable)
            $table->string('public_visibility', 30)->default('admin_only')->index();

            // Maquetación del catálogo público (Grid o Listado)
            $table->string('default_view_mode', 20)->default('grid');
            $table->boolean('allow_view_toggle')->default(true);
            $table->unsignedTinyInteger('cols_desktop')->default(3);
            $table->unsignedTinyInteger('cols_tablet')->default(2);
            $table->unsignedTinyInteger('cols_mobile')->default(1);
            $table->string('gap_size', 10)->default('md');
            $table->string('header_align', 15)->default('left');
            $table->unsignedSmallInteger('per_page')->default(12);
            $table->boolean('show_quick_download')->default(true);
            $table->boolean('show_downloads_count_public')->default(false);

            // SEO propio del catálogo
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('seo_og_image', 2048)->nullable();
            $table->string('meta_robots', 60)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tableName = config('downloads.tables.settings', 'download_settings');
        Schema::dropIfExists($tableName);
    }
};
