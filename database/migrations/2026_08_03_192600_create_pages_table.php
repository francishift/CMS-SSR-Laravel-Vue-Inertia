<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Páginas estáticas del CMS.
 *
 * Son contenidos independientes (no asociados a categorías):
 * "Sobre nosotros", "Contacto", "Política de privacidad", etc.
 * Se acceden mediante la URL /{slug} (misma lógica de catch-all).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->text('body')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('order')->default(0);

            // SEO
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('seo_og_image')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
