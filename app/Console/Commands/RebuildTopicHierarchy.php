<?php

namespace App\Console\Commands;

use App\Models\Topic;
use Illuminate\Console\Command;

class RebuildTopicHierarchy extends Command
{
    protected $signature = 'topics:rebuild-hierarchy 
                            {--nested-set : Rebuild nested set columns (_lft, _rgt, depth)}
                            {--closure : Rebuild closure table}
                            {--all : Rebuild both nested set and closure table}';

    protected $description = 'Rebuild topic hierarchy (nested set and/or closure table)';

    public function handle(): int
    {
        $rebuildNestedSet = $this->option('nested-set') || $this->option('all');
        $rebuildClosure = $this->option('closure') || $this->option('all');

        if (! $rebuildNestedSet && ! $rebuildClosure) {
            $this->error('Please specify what to rebuild: --nested-set, --closure, or --all');

            return self::FAILURE;
        }

        if ($rebuildNestedSet) {
            $this->info('Rebuilding nested set structure...');
            $this->rebuildNestedSet();
            $this->info('✓ Nested set structure rebuilt successfully');
        }

        if ($rebuildClosure) {
            $this->info('Rebuilding closure table...');
            $this->rebuildClosureTable();
            $this->info('✓ Closure table rebuilt successfully');
        }

        $this->info('Topic hierarchy rebuild completed!');

        return self::SUCCESS;
    }

    protected function rebuildNestedSet(): void
    {
        $bar = $this->output->createProgressBar(Topic::count());
        $bar->start();

        Topic::rebuildAllNestedSets();

        $bar->finish();
        $this->newLine();
    }

    protected function rebuildClosureTable(): void
    {
        $topics = Topic::all();
        $bar = $this->output->createProgressBar($topics->count());
        $bar->start();

        Topic::rebuildAllClosureTables();

        $bar->advance($topics->count());
        $bar->finish();
        $this->newLine();
    }
}
