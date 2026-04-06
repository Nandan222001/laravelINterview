<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_snippets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('code');
            $table->string('language', 50);
            $table->string('type')->default('example');
            $table->integer('order')->default(0);
            $table->boolean('is_executable')->default(false);
            $table->text('expected_output')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('question_id');
            $table->index('language');
            $table->index('type');
            $table->index(['question_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_snippets');
    }
};
