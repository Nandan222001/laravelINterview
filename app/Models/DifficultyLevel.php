<?php

namespace App\Models;

use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DifficultyLevel extends Model
{
    use HasFactory, HasMetadata;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'color',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'level' => 'integer',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopeByLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeMinLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', '>=', $level);
    }

    public function scopeMaxLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', '<=', $level);
    }

    public function scopeBetweenLevels(Builder $query, int $minLevel, int $maxLevel): Builder
    {
        return $query->whereBetween('level', [$minLevel, $maxLevel]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('level');
    }

    public function scopeWithQuestionsCount(Builder $query): Builder
    {
        return $query->withCount('questions');
    }
}
