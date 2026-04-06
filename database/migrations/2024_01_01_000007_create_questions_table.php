<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('difficulty_level_id')->constrained('difficulty_levels')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('question_text');
            $table->text('explanation')->nullable();
            $table->json('options')->nullable();
            $table->json('correct_answer');
            $table->json('hints')->nullable();
            $table->integer('points')->default(0);
            $table->integer('time_limit')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('attempt_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('difficulty_level_id');
            $table->index('created_by');
            $table->index('is_published');
            $table->index('is_verified');
            $table->index(['is_published', 'is_verified']);
            $table->fullText(['title', 'question_text']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
