<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionRequest;
use App\Http\Resources\QuestionCollection;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\QuestionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private QuestionService $questionService
    ) {
        $this->authorizeResource(Question::class, 'question');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $filters = $request->only([
            'search',
            'difficulty',
            'category',
            'topic',
            'tags',
            'published',
            'verified',
            'creator',
            'min_difficulty_level',
            'max_difficulty_level',
        ]);

        $questions = $this->questionService->getPaginatedQuestions($filters, $perPage);

        return $this->successResponse(
            new QuestionCollection($questions),
            'Questions retrieved successfully'
        );
    }

    public function show(Question $question): JsonResponse
    {
        $questionWithRelations = $this->questionService->getQuestionWithRelations($question->id);

        if (! $questionWithRelations) {
            return $this->notFoundResponse('Question not found');
        }

        $this->questionService->incrementViews($question->id);

        return $this->successResponse(
            new QuestionResource($questionWithRelations),
            'Question retrieved successfully'
        );
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $topicIds = $validated['topic_ids'] ?? [];
        $tagIds = $validated['tag_ids'] ?? [];

        unset($validated['topic_ids'], $validated['tag_ids']);

        $question = $this->questionService->createQuestion($validated);

        if (! empty($topicIds)) {
            $this->questionService->syncTopics($question->id, $topicIds);
        }

        if (! empty($tagIds)) {
            $this->questionService->syncTags($question->id, $tagIds);
        }

        $questionWithRelations = $this->questionService->getQuestionWithRelations($question->id);

        return $this->createdResponse(
            new QuestionResource($questionWithRelations),
            'Question created successfully'
        );
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $validated = $request->validated();

        $topicIds = $validated['topic_ids'] ?? null;
        $tagIds = $validated['tag_ids'] ?? null;

        unset($validated['topic_ids'], $validated['tag_ids']);

        $updated = $this->questionService->updateQuestion($question->id, $validated);

        if (! $updated) {
            return $this->serverErrorResponse('Failed to update question');
        }

        if ($topicIds !== null) {
            $this->questionService->syncTopics($question->id, $topicIds);
        }

        if ($tagIds !== null) {
            $this->questionService->syncTags($question->id, $tagIds);
        }

        $questionWithRelations = $this->questionService->getQuestionWithRelations($question->id);

        return $this->successResponse(
            new QuestionResource($questionWithRelations),
            'Question updated successfully'
        );
    }

    public function destroy(Question $question): JsonResponse
    {
        $this->questionService->deleteQuestion($question->id);

        return $this->successResponse(null, 'Question deleted successfully');
    }
}
