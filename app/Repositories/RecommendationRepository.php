<?php

namespace App\Repositories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationRepository
{
    public function getRecommendedQuestions(int $userId, int $limit = 10): Collection
    {
        $userStats = $this->getUserProgressStats($userId);
        $currentDifficultyLevel = $this->determineCurrentDifficultyLevel($userStats);
        $weakCategories = $this->getWeakCategories($userId);
        $attemptedQuestionIds = $this->getAttemptedQuestionIds($userId);

        $query = Question::query()
            ->published()
            ->whereNotIn('id', $attemptedQuestionIds)
            ->with(['difficultyLevel', 'topics.category'])
            ->limit($limit);

        if ($currentDifficultyLevel !== null) {
            $query->whereHas('difficultyLevel', function ($q) use ($currentDifficultyLevel) {
                $q->whereBetween('level', [
                    max(1, $currentDifficultyLevel - 1),
                    $currentDifficultyLevel + 1,
                ]);
            });
        }

        if ($weakCategories->isNotEmpty()) {
            $weakCategoryIds = $weakCategories->pluck('category_id');
            $query->whereHas('topics.category', function ($q) use ($weakCategoryIds) {
                $q->whereIn('categories.id', $weakCategoryIds);
            });
        }

        $query->inRandomOrder();

        return $query->get();
    }

    public function getNextQuestions(int $userId, int $limit = 5): Collection
    {
        $userStats = $this->getUserProgressStats($userId);
        $currentDifficultyLevel = $this->determineCurrentDifficultyLevel($userStats);
        $attemptedQuestionIds = $this->getAttemptedQuestionIds($userId);
        $recentCategories = $this->getRecentCategories($userId);

        $query = Question::query()
            ->published()
            ->whereNotIn('id', $attemptedQuestionIds)
            ->with(['difficultyLevel', 'topics.category'])
            ->limit($limit);

        if ($currentDifficultyLevel !== null) {
            $query->whereHas('difficultyLevel', function ($q) use ($currentDifficultyLevel) {
                $q->where('level', '<=', $currentDifficultyLevel + 1);
            });
        }

        if ($recentCategories->isNotEmpty()) {
            $recentCategoryIds = $recentCategories->pluck('category_id');
            $query->whereHas('topics.category', function ($q) use ($recentCategoryIds) {
                $q->whereIn('categories.id', $recentCategoryIds);
            });
        }

        return $query->get();
    }

    public function getProgressionBasedRecommendations(int $userId, int $limit = 10): Collection
    {
        $userStats = $this->getUserProgressStats($userId);
        $attemptedQuestionIds = $this->getAttemptedQuestionIds($userId);

        if ($userStats['accuracy_rate'] >= 80) {
            $difficultyProgression = 1;
        } elseif ($userStats['accuracy_rate'] >= 60) {
            $difficultyProgression = 0;
        } else {
            $difficultyProgression = -1;
        }

        $currentDifficultyLevel = $this->determineCurrentDifficultyLevel($userStats);
        $targetDifficultyLevel = $currentDifficultyLevel !== null
            ? max(1, $currentDifficultyLevel + $difficultyProgression)
            : null;

        $query = Question::query()
            ->published()
            ->whereNotIn('id', $attemptedQuestionIds)
            ->with(['difficultyLevel', 'topics.category'])
            ->limit($limit);

        if ($targetDifficultyLevel !== null) {
            $query->whereHas('difficultyLevel', function ($q) use ($targetDifficultyLevel) {
                $q->where('level', $targetDifficultyLevel);
            });
        }

        return $query->inRandomOrder()->get();
    }

    protected function getUserProgressStats(int $userId): array
    {
        $stats = DB::table('user_question_attempts')
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_attempted,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as total_completed,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as total_correct
            ')
            ->first();

        $totalCompleted = $stats->total_completed ?? 0;
        $totalCorrect = $stats->total_correct ?? 0;

        return [
            'total_attempted' => $stats->total_attempted ?? 0,
            'total_completed' => $totalCompleted,
            'total_correct' => $totalCorrect,
            'accuracy_rate' => $totalCompleted > 0
                ? round(($totalCorrect / $totalCompleted) * 100, 2)
                : 0,
        ];
    }

    protected function determineCurrentDifficultyLevel(array $userStats): ?int
    {
        if ($userStats['total_completed'] === 0) {
            return 1;
        }

        $avgDifficulty = DB::table('user_question_attempts as uqa')
            ->join('questions as q', 'uqa.question_id', '=', 'q.id')
            ->join('difficulty_levels as dl', 'q.difficulty_level_id', '=', 'dl.id')
            ->where('uqa.status', 'completed')
            ->avg('dl.level');

        return $avgDifficulty ? (int) round($avgDifficulty) : 1;
    }

    protected function getWeakCategories(int $userId, int $limit = 3): Collection
    {
        return DB::table('user_question_attempts as uqa')
            ->join('questions as q', 'uqa.question_id', '=', 'q.id')
            ->join('question_topic as qt', 'q.id', '=', 'qt.question_id')
            ->join('topics as t', 'qt.topic_id', '=', 't.id')
            ->join('categories as c', 't.category_id', '=', 'c.id')
            ->where('uqa.user_id', $userId)
            ->where('uqa.status', 'completed')
            ->select(
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('SUM(CASE WHEN uqa.is_correct = 1 THEN 1 ELSE 0 END) as correct_count'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('(SUM(CASE WHEN uqa.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy_rate')
            )
            ->groupBy('c.id', 'c.name')
            ->having('total_count', '>=', 3)
            ->orderBy('accuracy_rate', 'asc')
            ->limit($limit)
            ->get();
    }

    protected function getRecentCategories(int $userId, int $limit = 3): Collection
    {
        return DB::table('user_question_attempts as uqa')
            ->join('questions as q', 'uqa.question_id', '=', 'q.id')
            ->join('question_topic as qt', 'q.id', '=', 'qt.question_id')
            ->join('topics as t', 'qt.topic_id', '=', 't.id')
            ->join('categories as c', 't.category_id', '=', 'c.id')
            ->where('uqa.user_id', $userId)
            ->select('c.id as category_id', 'c.name as category_name')
            ->groupBy('c.id', 'c.name')
            ->orderByRaw('MAX(uqa.last_attempted_at) DESC')
            ->limit($limit)
            ->get();
    }

    protected function getAttemptedQuestionIds(int $userId): array
    {
        return DB::table('user_question_attempts')
            ->where('user_id', $userId)
            ->pluck('question_id')
            ->toArray();
    }
}
