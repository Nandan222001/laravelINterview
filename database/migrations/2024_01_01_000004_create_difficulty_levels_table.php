<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('difficulty_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('level')->unique();
            $table->string('color')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('difficulty_levels');
    }
};
