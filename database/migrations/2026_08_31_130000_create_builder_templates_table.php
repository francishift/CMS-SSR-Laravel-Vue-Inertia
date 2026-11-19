<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('builder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 40)->index(); // 'single_article', 'category_archive'
            $table->json('layout_json')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('preview_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('preview_article_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('category_builder_template_id')->nullable()->after('template')->constrained('builder_templates')->nullOnDelete();
            $table->foreignId('article_builder_template_id')->nullable()->after('category_builder_template_id')->constrained('builder_templates')->nullOnDelete();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('article_builder_template_id')->nullable()->after('category_id')->constrained('builder_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_builder_template_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_builder_template_id');
            $table->dropConstrainedForeignId('category_builder_template_id');
        });

        Schema::dropIfExists('builder_templates');
    }
};
