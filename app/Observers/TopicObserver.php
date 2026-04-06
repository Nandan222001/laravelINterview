<?php

namespace App\Observers;

use App\Models\Topic;
use Illuminate\Support\Str;

class TopicObserver
{
    public function creating(Topic $topic): void
    {
        if (empty($topic->slug)) {
            $topic->slug = Str::slug($topic->name);
        }

        if ($topic->parent_id) {
            $parent = Topic::find($topic->parent_id);
            if ($parent) {
                $topic->depth = $parent->depth + 1;
                $topic->category_id = $parent->category_id;
            }
        } else {
            $topic->depth = 0;
        }
    }

    public function created(Topic $topic): void
    {
        $topic->rebuildClosureTable();
    }

    public function updating(Topic $topic): void
    {
        if ($topic->isDirty('name') && ! $topic->isDirty('slug')) {
            $topic->slug = Str::slug($topic->name);
        }
    }

    public function updated(Topic $topic): void
    {
        if ($topic->isDirty('parent_id')) {
            if ($topic->parent_id) {
                $parent = Topic::find($topic->parent_id);
                if ($parent) {
                    $topic->updateQuietly([
                        'depth' => $parent->depth + 1,
                        'category_id' => $parent->category_id,
                    ]);
                }
            } else {
                $topic->updateQuietly(['depth' => 0]);
            }

            $topic->rebuildClosureTable();

            foreach ($topic->children as $child) {
                $child->touch();
            }
        }
    }

    public function deleted(Topic $topic): void
    {
        //
    }

    public function restored(Topic $topic): void
    {
        $topic->rebuildClosureTable();
    }

    public function forceDeleted(Topic $topic): void
    {
        //
    }
}
