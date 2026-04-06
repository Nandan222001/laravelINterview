<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function questionAttempts(): HasMany
    {
        return $this->hasMany(UserQuestionAttempt::class);
    }

    public function attemptedQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'user_question_attempts')
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

    public function bookmarkedQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'user_question_attempts')
            ->wherePivot('is_bookmarked', true)
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

    public function completedQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'user_question_attempts')
            ->wherePivot('status', 'completed')
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
}
