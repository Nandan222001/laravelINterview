<?php

namespace App\Models;

use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory, HasMetadata;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'usage_count',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'usage_count' => 'integer',
    ];

    public function questions(): MorphToMany
    {
        return $this->morphedByMany(Question::class, 'taggable');
    }

    public function categories(): MorphToMany
    {
        return $this->morphedByMany(Category::class, 'taggable');
    }

    public function topics(): MorphToMany
    {
        return $this->morphedByMany(Topic::class, 'taggable');
    }

    public function codeSnippets(): MorphToMany
    {
        return $this->morphedByMany(CodeSnippet::class, 'taggable');
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopePopular(Builder $query, int $minUsage = 10): Builder
    {
        return $query->where('usage_count', '>=', $minUsage);
    }

    public function scopeMostUsed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function scopeWithUsageCounts(Builder $query): Builder
    {
        return $query->withCount(['questions', 'categories', 'topics', 'codeSnippets']);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('slug', 'like', "%{$term}%");
    }

    public function scopeFullTextSearch(Builder $query, string $term): Builder
    {
        return $query->whereFullText(['name', 'slug', 'description'], $term);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }
}
