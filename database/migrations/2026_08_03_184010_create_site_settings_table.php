<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración global del sitio (singleton — una sola fila).
 *
 * Almacena nombre, logo, colores, fuentes, SEO por defecto,
 * texto de footer, redes sociales y scripts personalizados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // Identidad del sitio
            $table->string('site_name')->default('Mi Sitio Web');
            $table->text('site_description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            // Paleta de colores
            $table->string('primary_color', 7)->default('#10b981');
            $table->string('secondary_color', 7)->nullable();

            // Tipografía (fuentes locales)
            $table->string('heading_font')->default('Hanken Grotesk');
            $table->string('body_font')->default('Hanken Grotesk');

            // Contenido del footer
            $table->text('footer_text')->nullable();

            // Redes sociales (JSON: {twitter: "url", instagram: "url", ...})
            $table->json('social_links')->nullable();

            // SEO por defecto (fallback para páginas sin SEO propio)
            $table->string('seo_default_title')->nullable();
            $table->text('seo_default_description')->nullable();
            $table->string('seo_default_og_image')->nullable();

            // Robots.txt personalizable
            $table->text('robots_txt_content')->nullable();

            // Scripts personalizados inyectados en <head> (analytics, etc.)
            $table->text('custom_head_scripts')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
