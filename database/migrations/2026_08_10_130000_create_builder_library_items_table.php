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
        Schema::create('builder_library_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 50)->index();
            $table->string('category', 50)->default('general')->index();
            $table->text('description')->nullable();
            $table->json('content_json');
            $table->string('preview_image', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('builder_library_items');
    }
};
