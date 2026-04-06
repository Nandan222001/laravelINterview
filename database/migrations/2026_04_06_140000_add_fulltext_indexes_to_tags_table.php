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
            Schema::table('tags', function (Blueprint $table) {
                $table->fullText(['name', 'slug', 'description'], 'tags_fulltext_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->supportsFulltextIndexes()) {
            Schema::table('tags', function (Blueprint $table) {
                $table->dropIndex('tags_fulltext_index');
            });
        }
    }

    protected function supportsFulltextIndexes(): bool
    {
        $driver = DB::connection()->getDriverName();

        return in_array($driver, ['mysql', 'pgsql']);
    }
};
