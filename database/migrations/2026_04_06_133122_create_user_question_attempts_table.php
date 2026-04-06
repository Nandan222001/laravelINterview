<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_question_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['not_attempted', 'attempted', 'completed', 'skipped'])->default('not_attempted');
            $table->boolean('is_bookmarked')->default(false);
            $table->boolean('is_correct')->nullable();
            $table->integer('attempts_count')->default(0);
            $table->integer('time_spent')->nullable();
            $table->json('user_answer')->nullable();
            $table->timestamp('first_attempted_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'is_bookmarked']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_question_attempts');
    }
};
