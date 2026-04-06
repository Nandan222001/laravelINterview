<?php

namespace App\Models;

use App\Traits\HasMetadata;
use App\Traits\HasNestedSet;
use App\Traits\Taggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Topic extends Model
{
    use HasFactory, HasMetadata, HasNestedSet, SoftDeletes, Taggable;

    protected $fillable = [
        'category_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'learning_objectives',
        'order',
        'is_active',
        'metadata',
        '_lft',
        '_rgt',
        'depth',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'order' => 'integer',
        '_lft' => 'integer',
        '_rgt' => 'integer',
        'depth' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(function (Topic $topic) {
            $topic->rebuildClosureTable();
        });

        static::updated(function (Topic $topic) {
            if ($topic->isDirty('parent_id')) {
                $topic->rebuildClosureTable();
            }
        });

        static::deleted(function (Topic $topic) {
            DB::table('topic_closure')
                ->where('ancestor_id', $topic->id)
                ->orWhere('descendant_id', $topic->id)
                ->delete();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_topic')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function ancestors(): BelongsToMany
    {
        return $this->belongsToMany(
            Topic::class,
            'topic_closure',
            'descendant_id',
            'ancestor_id'
        )->where('ancestor_id', '!=', $this->id)
            ->orderBy('depth', 'desc');
    }

    public function descendants(): BelongsToMany
    {
        return $this->belongsToMany(
            Topic::class,
            'topic_closure',
            'ancestor_id',
            'descendant_id'
        )->where('descendant_id', '!=', $this->id)
            ->orderBy('depth');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeByCategory(Builder $query, int|string $category): Builder
    {
        if (is_numeric($category)) {
            return $query->where('category_id', $category);
        }

        return $query->whereHas('category', function (Builder $q) use ($category) {
            $q->where('slug', $category);
        });
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopeRootOnly(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithChildren(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public function scopeByDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', $depth);
    }

    public function scopeMaxDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', '<=', $depth);
    }

    public function scopeWithDifficulty(Builder $query, int|string $difficulty): Builder
    {
        return $query->whereHas('questions', function (Builder $q) use ($difficulty) {
            $q->byDifficulty($difficulty);
        });
    }

    public function scopeWithTag(Builder $query, string|int $tag): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tag) {
            if (is_numeric($tag)) {
                $q->where('tags.id', $tag);
            } else {
                $q->where('tags.slug', $tag);
            }
        });
    }

    public function scopeWithTags(Builder $query, array $tags): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tags) {
            $q->whereIn('tags.slug', $tags);
        }, '=', count($tags));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->byCategory($v))
            ->when($filters['tags'] ?? null, fn ($q, $v) => $q->withTags($v))
            ->when($filters['active'] ?? null, fn ($q, $v) => $v ? $q->active() : $q->inactive())
            ->when($filters['depth'] ?? null, fn ($q, $v) => $q->byDepth($v))
            ->when($filters['max_depth'] ?? null, fn ($q, $v) => $q->maxDepth($v))
            ->when($filters['root_only'] ?? null, fn ($q, $v) => $v ? $q->rootOnly() : $q);
    }

    public function scopeWithNestedChildren(Builder $query, ?int $depth = null): Builder
    {
        $with = ['children'];

        if ($depth === null || $depth > 1) {
            for ($i = 2; $i <= ($depth ?? 5); $i++) {
                $with[] = str_repeat('children.', $i - 1).'children';
            }
        }

        return $query->with($with);
    }

    public function getRoot(): ?self
    {
        if ($this->isRoot()) {
            return $this;
        }

        return $this->ancestors()
            ->whereNull('parent_id')
            ->first();
    }

    public function getSiblings(bool $includeSelf = false): Collection
    {
        $query = static::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id);

        if ($includeSelf) {
            $query->orWhere('id', $this->id);
        }

        return $query->orderBy('order')->get();
    }

    public function getDescendantsAndSelf(): Collection
    {
        return static::whereBetween('_lft', [$this->_lft, $this->_rgt])
            ->orderBy('_lft')
            ->get();
    }

    public function getDescendantsOnly(): Collection
    {
        return static::whereBetween('_lft', [$this->_lft + 1, $this->_rgt - 1])
            ->orderBy('_lft')
            ->get();
    }

    public function getAncestorsAndSelf(): Collection
    {
        return static::where('_lft', '<=', $this->_lft)
            ->where('_rgt', '>=', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    public function getAncestorsOnly(): Collection
    {
        return static::where('_lft', '<', $this->_lft)
            ->where('_rgt', '>', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return ($this->_rgt - $this->_lft) === 1;
    }

    public function hasChildren(): bool
    {
        return ! $this->isLeaf();
    }

    public function isDescendantOf(self $other): bool
    {
        return $this->_lft > $other->_lft && $this->_rgt < $other->_rgt;
    }

    public function isAncestorOf(self $other): bool
    {
        return $this->_lft < $other->_lft && $this->_rgt > $other->_rgt;
    }

    public function isSiblingOf(self $other): bool
    {
        return $this->parent_id === $other->parent_id && $this->id !== $other->id;
    }

    public function makeChildOf(self $parent): void
    {
        $this->parent_id = $parent->id;
        $this->category_id = $parent->category_id;
        $this->depth = $parent->depth + 1;
        $this->save();

        $this->rebuildNestedSet();
    }

    public function makeRoot(): void
    {
        $this->parent_id = null;
        $this->depth = 0;
        $this->save();

        $this->rebuildNestedSet();
    }

    public function rebuildNestedSet(): void
    {
        $counter = 0;
        $this->rebuildNestedSetRecursive($this->category_id, null, $counter);
    }

    protected function rebuildNestedSetRecursive(?int $categoryId, ?int $parentId, int &$counter, int $depth = 0): void
    {
        $topics = static::where('category_id', $categoryId)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();

        foreach ($topics as $topic) {
            $left = ++$counter;
            $this->rebuildNestedSetRecursive($categoryId, $topic->id, $counter, $depth + 1);
            $right = ++$counter;

            $topic->update([
                '_lft' => $left,
                '_rgt' => $right,
                'depth' => $depth,
            ]);
        }
    }

    public function rebuildClosureTable(): void
    {
        DB::table('topic_closure')->where('descendant_id', $this->id)->delete();

        DB::table('topic_closure')->insert([
            'ancestor_id' => $this->id,
            'descendant_id' => $this->id,
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->parent_id) {
            $ancestorPaths = DB::table('topic_closure')
                ->where('descendant_id', $this->parent_id)
                ->get();

            foreach ($ancestorPaths as $path) {
                DB::table('topic_closure')->insert([
                    'ancestor_id' => $path->ancestor_id,
                    'descendant_id' => $this->id,
                    'depth' => $path->depth + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public static function rebuildAllNestedSets(): void
    {
        $categories = Category::pluck('id');

        foreach ($categories as $categoryId) {
            $counter = 0;
            static::rebuildNestedSetRecursiveStatic($categoryId, null, $counter);
        }
    }

    protected static function rebuildNestedSetRecursiveStatic(?int $categoryId, ?int $parentId, int &$counter, int $depth = 0): void
    {
        $topics = static::where('category_id', $categoryId)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();

        foreach ($topics as $topic) {
            $left = ++$counter;
            static::rebuildNestedSetRecursiveStatic($categoryId, $topic->id, $counter, $depth + 1);
            $right = ++$counter;

            $topic->updateQuietly([
                '_lft' => $left,
                '_rgt' => $right,
                'depth' => $depth,
            ]);
        }
    }

    public static function rebuildAllClosureTables(): void
    {
        DB::table('topic_closure')->truncate();

        static::all()->each(function (Topic $topic) {
            $topic->rebuildClosureTable();
        });
    }
}
