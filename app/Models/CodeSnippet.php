<?php

namespace App\Models;

use App\Traits\HasMetadata;
use App\Traits\Taggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeSnippet extends Model
{
    use HasFactory, HasMetadata, Taggable;

    protected $fillable = [
        'question_id',
        'title',
        'description',
        'code',
        'language',
        'type',
        'order',
        'is_executable',
        'expected_output',
        'metadata',
    ];

    protected $casts = [
        'is_executable' => 'boolean',
        'metadata' => 'array',
        'order' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function scopeByLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeExecutable(Builder $query): Builder
    {
        return $query->where('is_executable', true);
    }

    public function scopeNonExecutable(Builder $query): Builder
    {
        return $query->where('is_executable', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    public function scopeForQuestion(Builder $query, int $questionId): Builder
    {
        return $query->where('question_id', $questionId);
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

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%");
    }
}
