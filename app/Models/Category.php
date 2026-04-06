<?php

namespace App\Models;

use App\Traits\HasMetadata;
use App\Traits\Taggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, HasMetadata, SoftDeletes, Taggable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeRootOnly(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithChildren(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
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

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function getDescendants(): Collection
    {
        return $this->loadMissing('children.children')->children->flatMap(function ($child) {
            return collect([$child])->merge($child->getDescendants());
        });
    }
}
