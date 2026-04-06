<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('learning_objectives')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('category_id');
            $table->index('is_active');
            $table->index(['category_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
