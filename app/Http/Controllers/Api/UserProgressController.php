<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkQuestionAttemptedRequest;
use App\Http\Requests\MarkQuestionCompletedRequest;
use App\Http\Resources\QuestionCollection;
use App\Http\Resources\UserQuestionAttemptCollection;
use App\Http\Resources\UserQuestionAttemptResource;
use App\Services\UserProgressService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProgressController extends Controller
{
    use ApiResponse;

    protected UserProgressService $progressService;

    public function __construct(UserProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    public function markAttempted(MarkQuestionAttemptedRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $attempt = $this->progressService->markQuestionAsAttempted(
            $userId,
            $request->input('question_id'),
            $request->input('user_answer'),
            $request->input('time_spent')
        );

        return $this->successResponse(
            new UserQuestionAttemptResource($attempt),
            'Question marked as attempted'
        );
    }

    public function markCompleted(MarkQuestionCompletedRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $attempt = $this->progressService->markQuestionAsCompleted(
            $userId,
            $request->input('question_id'),
            $request->boolean('is_correct'),
            $request->input('user_answer'),
            $request->input('time_spent')
        );

        return $this->successResponse(
            new UserQuestionAttemptResource($attempt),
            'Question marked as completed'
        );
    }

    public function toggleBookmark(Request $request, int $questionId): JsonResponse
    {
        $request->validate([
            'question_id' => 'sometimes|integer|exists:questions,id',
        ]);

        $userId = $request->user()->id;
        $actualQuestionId = $request->input('question_id', $questionId);

        $attempt = $this->progressService->toggleBookmark($userId, $actualQuestionId);

        return $this->successResponse(
            new UserQuestionAttemptResource($attempt),
            $attempt->is_bookmarked ? 'Question bookmarked' : 'Question unbookmarked'
        );
    }

    public function bookmark(Request $request, int $questionId): JsonResponse
    {
        $userId = $request->user()->id;
        $attempt = $this->progressService->bookmarkQuestion($userId, $questionId);

        return $this->successResponse(
            new UserQuestionAttemptResource($attempt),
            'Question bookmarked'
        );
    }

    public function unbookmark(Request $request, int $questionId): JsonResponse
    {
        $userId = $request->user()->id;
        $attempt = $this->progressService->unbookmarkQuestion($userId, $questionId);

        return $this->successResponse(
            new UserQuestionAttemptResource($attempt),
            'Question unbookmarked'
        );
    }

    public function statistics(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $stats = $this->progressService->getUserStatistics($userId);

        return $this->successResponse($stats, 'User statistics retrieved successfully');
    }

    public function bookmarkedQuestions(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 15);
        $attempts = $this->progressService->getBookmarkedQuestions($userId, $perPage);

        return $this->successResponse(
            new UserQuestionAttemptCollection($attempts),
            'Bookmarked questions retrieved successfully'
        );
    }

    public function completedQuestions(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 15);
        $attempts = $this->progressService->getCompletedQuestions($userId, $perPage);

        return $this->successResponse(
            new UserQuestionAttemptCollection($attempts),
            'Completed questions retrieved successfully'
        );
    }

    public function attemptedQuestions(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 15);
        $attempts = $this->progressService->getAttemptedQuestions($userId, $perPage);

        return $this->successResponse(
            new UserQuestionAttemptCollection($attempts),
            'Attempted questions retrieved successfully'
        );
    }

    public function recommendations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = $request->input('limit', 10);
        $questions = $this->progressService->getRecommendedQuestions($userId, $limit);

        return $this->successResponse(
            new QuestionCollection($questions),
            'Recommended questions retrieved successfully'
        );
    }

    public function nextQuestions(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = $request->input('limit', 5);
        $questions = $this->progressService->getNextQuestions($userId, $limit);

        return $this->successResponse(
            new QuestionCollection($questions),
            'Next questions retrieved successfully'
        );
    }

    public function progressionRecommendations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = $request->input('limit', 10);
        $questions = $this->progressService->getProgressionBasedRecommendations($userId, $limit);

        return $this->successResponse(
            new QuestionCollection($questions),
            'Progression-based recommendations retrieved successfully'
        );
    }
}
