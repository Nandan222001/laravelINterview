<?php

namespace App\Repositories;

use App\Models\UserQuestionAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserProgressRepository extends BaseRepository
{
    public function __construct(UserQuestionAttempt $model)
    {
        parent::__construct($model);
    }

    public function getOrCreateAttempt(int $userId, int $questionId): UserQuestionAttempt
    {
        return $this->model->firstOrCreate(
            ['user_id' => $userId, 'question_id' => $questionId],
            ['status' => 'not_attempted']
        );
    }

    public function markAsAttempted(int $userId, int $questionId, ?array $userAnswer = null, ?int $timeSpent = null): UserQuestionAttempt
    {
        $attempt = $this->getOrCreateAttempt($userId, $questionId);
        $attempt->markAsAttempted($userAnswer, $timeSpent);

        return $attempt->fresh();
    }

    public function markAsCompleted(int $userId, int $questionId, bool $isCorrect, ?array $userAnswer = null, ?int $timeSpent = null): UserQuestionAttempt
    {
        $attempt = $this->getOrCreateAttempt($userId, $questionId);
        $attempt->markAsCompleted($isCorrect, $userAnswer, $timeSpent);

        return $attempt->fresh();
    }

    public function toggleBookmark(int $userId, int $questionId): UserQuestionAttempt
    {
        $attempt = $this->getOrCreateAttempt($userId, $questionId);
        $attempt->toggleBookmark();

        return $attempt->fresh();
    }

    public function bookmark(int $userId, int $questionId): UserQuestionAttempt
    {
        $attempt = $this->getOrCreateAttempt($userId, $questionId);
        $attempt->bookmark();

        return $attempt->fresh();
    }

    public function unbookmark(int $userId, int $questionId): UserQuestionAttempt
    {
        $attempt = $this->getOrCreateAttempt($userId, $questionId);
        $attempt->unbookmark();

        return $attempt->fresh();
    }

    public function getUserStatistics(int $userId): array
    {
        $stats = $this->model
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_attempted,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as total_completed,
                SUM(CASE WHEN is_bookmarked = 1 THEN 1 ELSE 0 END) as total_bookmarked,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as total_correct,
                SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as total_incorrect,
                AVG(CASE WHEN time_spent IS NOT NULL THEN time_spent END) as avg_time_spent,
                SUM(attempts_count) as total_attempts
            ')
            ->first();

        return [
            'total_attempted' => $stats->total_attempted ?? 0,
            'total_completed' => $stats->total_completed ?? 0,
            'total_bookmarked' => $stats->total_bookmarked ?? 0,
            'total_correct' => $stats->total_correct ?? 0,
            'total_incorrect' => $stats->total_incorrect ?? 0,
            'avg_time_spent' => $stats->avg_time_spent ? round($stats->avg_time_spent, 2) : 0,
            'total_attempts' => $stats->total_attempts ?? 0,
            'completion_rate' => $stats->total_attempted > 0
                ? round(($stats->total_completed / $stats->total_attempted) * 100, 2)
                : 0,
            'accuracy_rate' => $stats->total_completed > 0
                ? round(($stats->total_correct / $stats->total_completed) * 100, 2)
                : 0,
        ];
    }

    public function getStatisticsByCategory(int $userId): Collection
    {
        return DB::table('user_question_attempts as uqa')
            ->join('questions as q', 'uqa.question_id', '=', 'q.id')
            ->join('question_topic as qt', 'q.id', '=', 'qt.question_id')
            ->join('topics as t', 'qt.topic_id', '=', 't.id')
            ->join('categories as c', 't.category_id', '=', 'c.id')
            ->where('uqa.user_id', $userId)
            ->select(
                'c.id as category_id',
                'c.name as category_name',
                'c.slug as category_slug',
                DB::raw('COUNT(DISTINCT q.id) as total_attempted'),
                DB::raw('SUM(CASE WHEN uqa.status = "completed" THEN 1 ELSE 0 END) as total_completed'),
                DB::raw('SUM(CASE WHEN uqa.is_correct = 1 THEN 1 ELSE 0 END) as total_correct'),
                DB::raw('SUM(CASE WHEN uqa.is_correct = 0 THEN 1 ELSE 0 END) as total_incorrect'),
                DB::raw('AVG(CASE WHEN uqa.time_spent IS NOT NULL THEN uqa.time_spent END) as avg_time_spent')
            )
            ->groupBy('c.id', 'c.name', 'c.slug')
            ->get()
            ->map(function ($stat) {
                return [
                    'category_id' => $stat->category_id,
                    'category_name' => $stat->category_name,
                    'category_slug' => $stat->category_slug,
                    'total_attempted' => $stat->total_attempted,
                    'total_completed' => $stat->total_completed,
                    'total_correct' => $stat->total_correct,
                    'total_incorrect' => $stat->total_incorrect,
                    'avg_time_spent' => $stat->avg_time_spent ? round($stat->avg_time_spent, 2) : 0,
                    'completion_rate' => $stat->total_attempted > 0
                        ? round(($stat->total_completed / $stat->total_attempted) * 100, 2)
                        : 0,
                    'accuracy_rate' => $stat->total_completed > 0
                        ? round(($stat->total_correct / $stat->total_completed) * 100, 2)
                        : 0,
                ];
            });
    }

    public function getStatisticsByDifficulty(int $userId): Collection
    {
        return DB::table('user_question_attempts as uqa')
            ->join('questions as q', 'uqa.question_id', '=', 'q.id')
            ->join('difficulty_levels as dl', 'q.difficulty_level_id', '=', 'dl.id')
            ->where('uqa.user_id', $userId)
            ->select(
                'dl.id as difficulty_id',
                'dl.name as difficulty_name',
                'dl.slug as difficulty_slug',
                'dl.level as difficulty_level',
                DB::raw('COUNT(*) as total_attempted'),
                DB::raw('SUM(CASE WHEN uqa.status = "completed" THEN 1 ELSE 0 END) as total_completed'),
                DB::raw('SUM(CASE WHEN uqa.is_correct = 1 THEN 1 ELSE 0 END) as total_correct'),
                DB::raw('SUM(CASE WHEN uqa.is_correct = 0 THEN 1 ELSE 0 END) as total_incorrect'),
                DB::raw('AVG(CASE WHEN uqa.time_spent IS NOT NULL THEN uqa.time_spent END) as avg_time_spent')
            )
            ->groupBy('dl.id', 'dl.name', 'dl.slug', 'dl.level')
            ->orderBy('dl.level')
            ->get()
            ->map(function ($stat) {
                return [
                    'difficulty_id' => $stat->difficulty_id,
                    'difficulty_name' => $stat->difficulty_name,
                    'difficulty_slug' => $stat->difficulty_slug,
                    'difficulty_level' => $stat->difficulty_level,
                    'total_attempted' => $stat->total_attempted,
                    'total_completed' => $stat->total_completed,
                    'total_correct' => $stat->total_correct,
                    'total_incorrect' => $stat->total_incorrect,
                    'avg_time_spent' => $stat->avg_time_spent ? round($stat->avg_time_spent, 2) : 0,
                    'completion_rate' => $stat->total_attempted > 0
                        ? round(($stat->total_completed / $stat->total_attempted) * 100, 2)
                        : 0,
                    'accuracy_rate' => $stat->total_completed > 0
                        ? round(($stat->total_correct / $stat->total_completed) * 100, 2)
                        : 0,
                ];
            });
    }

    public function getUserBookmarkedQuestions(int $userId, int $perPage = 15)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('is_bookmarked', true)
            ->with(['question.difficultyLevel', 'question.topics.category'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserCompletedQuestions(int $userId, int $perPage = 15)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->with(['question.difficultyLevel', 'question.topics.category'])
            ->orderBy('completed_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserAttemptedQuestions(int $userId, int $perPage = 15)
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereIn('status', ['attempted', 'completed'])
            ->with(['question.difficultyLevel', 'question.topics.category'])
            ->orderBy('last_attempted_at', 'desc')
            ->paginate($perPage);
    }
}
