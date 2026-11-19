<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla principal de ítems descargables (preparada para distribución gratuita hoy y monetización futura).
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('downloads.tables.items', 'download_items');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();

            // Identidad y presentación editorial
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('featured_image', 2048)->nullable();
            $table->string('icon', 100)->nullable();

            // Campos técnicos estructurados (plugins, elementos YOOtheme Pro, software)
            $table->string('version', 50)->nullable();
            $table->string('compatibility', 200)->nullable();
            $table->text('changelog')->nullable();
            $table->string('demo_url', 2048)->nullable();
            $table->string('documentation_url', 2048)->nullable();

            // Bóveda privada de archivo (jamás expuesta directamente al público)
            $table->string('vault_disk', 60)->nullable();
            $table->string('private_file_path', 500)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('sha256_hash', 64)->nullable();

            // Arquitectura de monetización futura (Free -> Registered -> Paid)
            $table->string('access_type', 30)->default('free');
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->unsignedBigInteger('compare_at_price_cents')->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->string('license_type', 120)->nullable();
            $table->string('external_product_id', 191)->nullable()->index();

            // Estado, ordenación manual Drag & Drop y contadores atómicos
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            // Metadatos SEO individuales
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('seo_og_image', 2048)->nullable();
            $table->string('meta_robots', 60)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices compuestos de alto rendimiento para miles de registros
            $table->index(['status', 'published_at', 'order'], 'idx_dl_items_public_catalog');
            $table->index(['access_type', 'status'], 'idx_dl_items_access_status');
            $table->index(['deleted_at', 'order'], 'idx_dl_items_admin_order');
        });
    }

    public function down(): void
    {
        $tableName = config('downloads.tables.items', 'download_items');
        Schema::dropIfExists($tableName);
    }
};
