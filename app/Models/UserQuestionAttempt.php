<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuestionAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'status',
        'is_bookmarked',
        'is_correct',
        'attempts_count',
        'time_spent',
        'user_answer',
        'first_attempted_at',
        'last_attempted_at',
        'completed_at',
    ];

    protected $casts = [
        'is_bookmarked' => 'boolean',
        'is_correct' => 'boolean',
        'attempts_count' => 'integer',
        'time_spent' => 'integer',
        'user_answer' => 'array',
        'first_attempted_at' => 'datetime',
        'last_attempted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function markAsAttempted(?array $userAnswer = null, ?int $timeSpent = null): void
    {
        $this->update([
            'status' => 'attempted',
            'user_answer' => $userAnswer,
            'time_spent' => $timeSpent,
            'attempts_count' => $this->attempts_count + 1,
            'first_attempted_at' => $this->first_attempted_at ?? now(),
            'last_attempted_at' => now(),
        ]);
    }

    public function markAsCompleted(bool $isCorrect, ?array $userAnswer = null, ?int $timeSpent = null): void
    {
        $this->update([
            'status' => 'completed',
            'is_correct' => $isCorrect,
            'user_answer' => $userAnswer,
            'time_spent' => $timeSpent,
            'attempts_count' => $this->attempts_count + 1,
            'first_attempted_at' => $this->first_attempted_at ?? now(),
            'last_attempted_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function toggleBookmark(): bool
    {
        $this->is_bookmarked = ! $this->is_bookmarked;
        $this->save();

        return $this->is_bookmarked;
    }

    public function bookmark(): void
    {
        $this->update(['is_bookmarked' => true]);
    }

    public function unbookmark(): void
    {
        $this->update(['is_bookmarked' => false]);
    }
}
