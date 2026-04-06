<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

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

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
