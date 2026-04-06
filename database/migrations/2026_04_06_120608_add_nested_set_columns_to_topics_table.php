<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('category_id')->constrained('topics')->onDelete('cascade');
            $table->integer('_lft')->default(0)->after('parent_id');
            $table->integer('_rgt')->default(0)->after('_lft');
            $table->integer('depth')->default(0)->after('_rgt');

            $table->index('parent_id');
            $table->index('_lft');
            $table->index('_rgt');
            $table->index(['_lft', '_rgt']);
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['_lft']);
            $table->dropIndex(['_rgt']);
            $table->dropIndex(['_lft', '_rgt']);
            $table->dropColumn(['parent_id', '_lft', '_rgt', 'depth']);
        });
    }
};
