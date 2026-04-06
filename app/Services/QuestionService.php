<?php

namespace App\Services;

use App\Models\Question;
use App\Repositories\QuestionRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionService extends BaseService
{
    public function __construct(QuestionRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getPaginatedQuestions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters, $perPage);
    }

    public function getQuestionWithRelations(int $id): ?Question
    {
        return $this->repository->getQuestionWithRelations($id);
    }

    public function createQuestion(array $data): Question
    {
        return $this->repository->create($data);
    }

    public function updateQuestion(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    public function deleteQuestion(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function syncTopics(int $questionId, array $topicIds): void
    {
        $question = $this->repository->findOrFail($questionId);
        $syncData = [];
        foreach ($topicIds as $index => $topicId) {
            $syncData[$topicId] = ['order' => $index + 1];
        }
        $question->topics()->sync($syncData);
    }

    public function syncTags(int $questionId, array $tagIds): void
    {
        $question = $this->repository->findOrFail($questionId);
        $question->tags()->sync($tagIds);
    }

    public function incrementViews(int $id): void
    {
        $question = $this->repository->findOrFail($id);
        $question->incrementViews();
    }

    public function recordAttempt(int $id, bool $success = false): void
    {
        $question = $this->repository->findOrFail($id);
        $question->recordAttempt($success);
    }
}
