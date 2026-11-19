<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía `download_settings` con controles granulares de tipografía, proporciones
 * de cada ítem/tarjeta y soporte nativo para maquetación con Page Builder (`layout_json`).
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('downloads.tables.settings', 'download_settings');

        Schema::table($tableName, function (Blueprint $table) {
            // Integración con el Page Builder visual
            $table->boolean('use_builder_layout')->default(false)->after('public_visibility');
            $table->json('layout_json')->nullable()->after('use_builder_layout');

            // Controles de cabecera y contenedor en vista estándar
            $table->string('container_width', 25)->default('boxed')->after('header_align');
            $table->boolean('show_breadcrumb')->default(true)->after('container_width');
            $table->boolean('show_header_icon')->default(true)->after('show_breadcrumb');
            $table->boolean('show_description')->default(true)->after('show_header_icon');
            $table->string('header_title_size', 20)->default('h1')->after('show_description');
            $table->string('header_desc_size', 15)->default('lg')->after('header_title_size');

            // Controles granulares de cada ítem / tarjeta (Grid y Listado)
            $table->string('card_style', 20)->default('default')->after('per_page');
            $table->string('card_text_align', 15)->default('left')->after('card_style');
            $table->string('item_title_tag', 10)->default('h2')->after('card_text_align');
            $table->string('item_title_size', 20)->default('h4')->after('item_title_tag');
            $table->string('item_desc_size', 15)->default('sm')->after('item_title_size');
            $table->string('item_desc_lines', 10)->default('3')->after('item_desc_size');
            $table->boolean('show_item_image')->default(true)->after('item_desc_lines');
            $table->boolean('show_item_description')->default(true)->after('show_item_image');
            $table->boolean('show_item_badges')->default(true)->after('show_item_description');
            $table->boolean('show_item_meta')->default(true)->after('show_item_badges');
            $table->string('item_image_ratio', 15)->default('16:9')->after('show_item_meta');
            $table->string('item_svg_size', 10)->default('lg')->after('item_image_ratio');
            $table->string('item_image_position', 15)->default('flush')->after('item_svg_size');
            $table->string('item_button_style', 25)->default('button_primary')->after('item_image_position');
            $table->string('item_button_size', 10)->default('md')->after('item_button_style');
        });
    }

    public function down(): void
    {
        $tableName = config('downloads.tables.settings', 'download_settings');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn([
                'use_builder_layout',
                'layout_json',
                'container_width',
                'show_breadcrumb',
                'show_header_icon',
                'show_description',
                'header_title_size',
                'header_desc_size',
                'card_style',
                'card_text_align',
                'item_title_tag',
                'item_title_size',
                'item_desc_size',
                'item_desc_lines',
                'show_item_image',
                'show_item_description',
                'show_item_badges',
                'show_item_meta',
                'item_image_ratio',
                'item_svg_size',
                'item_image_position',
                'item_button_style',
                'item_button_size',
            ]);
        });
    }
};
