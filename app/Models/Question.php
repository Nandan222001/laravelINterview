<?php

namespace App\Models;

use App\Traits\HasMetadata;
use App\Traits\Taggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, HasMetadata, SoftDeletes, Taggable;

    protected $fillable = [
        'difficulty_level_id',
        'created_by',
        'title',
        'question_text',
        'explanation',
        'options',
        'correct_answer',
        'hints',
        'points',
        'time_limit',
        'is_published',
        'is_verified',
        'view_count',
        'attempt_count',
        'success_count',
        'metadata',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'hints' => 'array',
        'metadata' => 'array',
        'is_published' => 'boolean',
        'is_verified' => 'boolean',
        'points' => 'integer',
        'time_limit' => 'integer',
        'view_count' => 'integer',
        'attempt_count' => 'integer',
        'success_count' => 'integer',
    ];

    public function difficultyLevel(): BelongsTo
    {
        return $this->belongsTo(DifficultyLevel::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'question_topic')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function codeSnippets(): HasMany
    {
        return $this->hasMany(CodeSnippet::class)->orderBy('order');
    }

    public function userAttempts(): HasMany
    {
        return $this->hasMany(UserQuestionAttempt::class);
    }

    public function attemptedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_question_attempts')
            ->withPivot([
                'status',
                'is_bookmarked',
                'is_correct',
                'attempts_count',
                'time_spent',
                'user_answer',
                'first_attempted_at',
                'last_attempted_at',
                'completed_at',
            ])
            ->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeUnpublished(Builder $query): Builder
    {
        return $query->where('is_published', false);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    public function scopeByDifficulty(Builder $query, int|string $difficulty): Builder
    {
        if (is_numeric($difficulty)) {
            return $query->where('difficulty_level_id', $difficulty);
        }

        return $query->whereHas('difficultyLevel', function (Builder $q) use ($difficulty) {
            $q->where('slug', $difficulty);
        });
    }

    public function scopeByDifficultyLevel(Builder $query, int $level): Builder
    {
        return $query->whereHas('difficultyLevel', function (Builder $q) use ($level) {
            $q->where('level', $level);
        });
    }

    public function scopeMinDifficulty(Builder $query, int $level): Builder
    {
        return $query->whereHas('difficultyLevel', function (Builder $q) use ($level) {
            $q->where('level', '>=', $level);
        });
    }

    public function scopeMaxDifficulty(Builder $query, int $level): Builder
    {
        return $query->whereHas('difficultyLevel', function (Builder $q) use ($level) {
            $q->where('level', '<=', $level);
        });
    }

    public function scopeByCategory(Builder $query, int|string $category): Builder
    {
        return $query->whereHas('topics.category', function (Builder $q) use ($category) {
            if (is_numeric($category)) {
                $q->where('categories.id', $category);
            } else {
                $q->where('categories.slug', $category);
            }
        });
    }

    public function scopeByTopic(Builder $query, int|string $topic): Builder
    {
        return $query->whereHas('topics', function (Builder $q) use ($topic) {
            if (is_numeric($topic)) {
                $q->where('topics.id', $topic);
            } else {
                $q->where('topics.slug', $topic);
            }
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

    public function scopeWithAnyTag(Builder $query, array $tags): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tags) {
            $q->whereIn('tags.slug', $tags);
        });
    }

    public function scopeByCreator(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    public function scopePopular(Builder $query, int $minViews = 100): Builder
    {
        return $query->where('view_count', '>=', $minViews);
    }

    public function scopeMostViewed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('view_count', 'desc')->limit($limit);
    }

    public function scopeMostAttempted(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('attempt_count', 'desc')->limit($limit);
    }

    public function scopeHighestSuccessRate(Builder $query, int $limit = 10): Builder
    {
        return $query->selectRaw('*, (success_count / NULLIF(attempt_count, 0)) as success_rate')
            ->where('attempt_count', '>', 0)
            ->orderByRaw('(success_count / attempt_count) desc')
            ->limit($limit);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereFullText(['title', 'question_text'], $term);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['difficulty'] ?? null, fn ($q, $v) => $q->byDifficulty($v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->byCategory($v))
            ->when($filters['topic'] ?? null, fn ($q, $v) => $q->byTopic($v))
            ->when($filters['tags'] ?? null, fn ($q, $v) => $q->withTags($v))
            ->when($filters['published'] ?? null, fn ($q, $v) => $v ? $q->published() : $q->unpublished())
            ->when($filters['verified'] ?? null, fn ($q, $v) => $v ? $q->verified() : $q->unverified())
            ->when($filters['creator'] ?? null, fn ($q, $v) => $q->byCreator($v))
            ->when($filters['min_difficulty_level'] ?? null, fn ($q, $v) => $q->minDifficulty($v))
            ->when($filters['max_difficulty_level'] ?? null, fn ($q, $v) => $q->maxDifficulty($v));
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function recordAttempt(bool $success = false): void
    {
        $this->increment('attempt_count');
        if ($success) {
            $this->increment('success_count');
        }
    }

    public function getSuccessRate(): float
    {
        if ($this->attempt_count === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->attempt_count) * 100, 2);
    }

    public function isEasy(): bool
    {
        return $this->getSuccessRate() >= 70;
    }

    public function isHard(): bool
    {
        return $this->getSuccessRate() <= 30;
    }
}
