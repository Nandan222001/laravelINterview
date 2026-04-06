<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->supportsFulltextIndexes()) {
            Schema::table('categories', function (Blueprint $table) {
                $table->fullText(['name', 'description'], 'categories_fulltext_index');
            });

            Schema::table('topics', function (Blueprint $table) {
                $table->fullText(['name', 'description', 'learning_objectives'], 'topics_fulltext_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->supportsFulltextIndexes()) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('categories_fulltext_index');
            });

            Schema::table('topics', function (Blueprint $table) {
                $table->dropIndex('topics_fulltext_index');
            });
        }
    }

    protected function supportsFulltextIndexes(): bool
    {
        $driver = DB::connection()->getDriverName();

        return in_array($driver, ['mysql', 'pgsql']);
    }
};
