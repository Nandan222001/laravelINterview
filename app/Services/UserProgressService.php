<?php

namespace App\Services;

use App\Models\UserQuestionAttempt;
use App\Repositories\RecommendationRepository;
use App\Repositories\UserProgressRepository;
use Illuminate\Database\Eloquent\Collection;

class UserProgressService
{
    protected UserProgressRepository $progressRepository;

    protected RecommendationRepository $recommendationRepository;

    public function __construct(
        UserProgressRepository $progressRepository,
        RecommendationRepository $recommendationRepository
    ) {
        $this->progressRepository = $progressRepository;
        $this->recommendationRepository = $recommendationRepository;
    }

    public function markQuestionAsAttempted(
        int $userId,
        int $questionId,
        ?array $userAnswer = null,
        ?int $timeSpent = null
    ): UserQuestionAttempt {
        return $this->progressRepository->markAsAttempted($userId, $questionId, $userAnswer, $timeSpent);
    }

    public function markQuestionAsCompleted(
        int $userId,
        int $questionId,
        bool $isCorrect,
        ?array $userAnswer = null,
        ?int $timeSpent = null
    ): UserQuestionAttempt {
        return $this->progressRepository->markAsCompleted($userId, $questionId, $isCorrect, $userAnswer, $timeSpent);
    }

    public function toggleBookmark(int $userId, int $questionId): UserQuestionAttempt
    {
        return $this->progressRepository->toggleBookmark($userId, $questionId);
    }

    public function bookmarkQuestion(int $userId, int $questionId): UserQuestionAttempt
    {
        return $this->progressRepository->bookmark($userId, $questionId);
    }

    public function unbookmarkQuestion(int $userId, int $questionId): UserQuestionAttempt
    {
        return $this->progressRepository->unbookmark($userId, $questionId);
    }

    public function getUserStatistics(int $userId): array
    {
        $overallStats = $this->progressRepository->getUserStatistics($userId);
        $categoryStats = $this->progressRepository->getStatisticsByCategory($userId);
        $difficultyStats = $this->progressRepository->getStatisticsByDifficulty($userId);

        return [
            'overall' => $overallStats,
            'by_category' => $categoryStats,
            'by_difficulty' => $difficultyStats,
        ];
    }

    public function getBookmarkedQuestions(int $userId, int $perPage = 15)
    {
        return $this->progressRepository->getUserBookmarkedQuestions($userId, $perPage);
    }

    public function getCompletedQuestions(int $userId, int $perPage = 15)
    {
        return $this->progressRepository->getUserCompletedQuestions($userId, $perPage);
    }

    public function getAttemptedQuestions(int $userId, int $perPage = 15)
    {
        return $this->progressRepository->getUserAttemptedQuestions($userId, $perPage);
    }

    public function getRecommendedQuestions(int $userId, int $limit = 10): Collection
    {
        return $this->recommendationRepository->getRecommendedQuestions($userId, $limit);
    }

    public function getNextQuestions(int $userId, int $limit = 5): Collection
    {
        return $this->recommendationRepository->getNextQuestions($userId, $limit);
    }

    public function getProgressionBasedRecommendations(int $userId, int $limit = 10): Collection
    {
        return $this->recommendationRepository->getProgressionBasedRecommendations($userId, $limit);
    }
}
