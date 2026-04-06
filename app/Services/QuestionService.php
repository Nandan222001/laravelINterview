<?php

namespace App\Services;

use App\Models\Question;
use App\Repositories\QuestionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionService extends BaseService
{
    protected QuestionRepository $repository;

    public function __construct(QuestionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllPublished(): Collection
    {
        return $this->repository->getAllPublished();
    }

    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPublishedPaginated($perPage);
    }

    public function getQuestionById(int $id): ?Question
    {
        return $this->repository->getQuestionWithRelations($id);
    }

    public function createQuestion(array $data): Question
    {
        $question = $this->repository->create($data);

        if (isset($data['topics']) && is_array($data['topics'])) {
            $question->topics()->sync($data['topics']);
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $question->syncTags($data['tags']);
        }

        if (isset($data['code_snippets']) && is_array($data['code_snippets'])) {
            foreach ($data['code_snippets'] as $snippet) {
                $question->codeSnippets()->create($snippet);
            }
        }

        return $question->fresh();
    }

    public function updateQuestion(int $id, array $data): Question
    {
        $this->repository->update($id, $data);
        $question = $this->repository->find($id);

        if (isset($data['topics']) && is_array($data['topics'])) {
            $question->topics()->sync($data['topics']);
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $question->syncTags($data['tags']);
        }

        return $question->fresh();
    }

    public function deleteQuestion(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function publishQuestion(int $id): Question
    {
        $this->repository->update($id, ['is_published' => true]);

        return $this->repository->find($id);
    }

    public function unpublishQuestion(int $id): Question
    {
        $this->repository->update($id, ['is_published' => false]);

        return $this->repository->find($id);
    }

    public function verifyQuestion(int $id): Question
    {
        $this->repository->update($id, ['is_verified' => true]);

        return $this->repository->find($id);
    }

    public function getQuestionsByDifficulty(int|string $difficulty, bool $publishedOnly = true): Collection
    {
        return $this->repository->getQuestionsByDifficulty($difficulty, $publishedOnly);
    }

    public function getQuestionsByCategory(int|string $category, bool $publishedOnly = true): Collection
    {
        return $this->repository->getQuestionsByCategory($category, $publishedOnly);
    }

    public function getQuestionsByTopic(int|string $topic, bool $publishedOnly = true): Collection
    {
        return $this->repository->getQuestionsByTopic($topic, $publishedOnly);
    }

    public function getQuestionsWithTags(array $tags, bool $publishedOnly = true): Collection
    {
        return $this->repository->getQuestionsWithTags($tags, $publishedOnly);
    }

    public function searchQuestions(string $term, bool $publishedOnly = true): Collection
    {
        return $this->repository->searchQuestions($term, $publishedOnly);
    }

    public function filterQuestions(array $filters): LengthAwarePaginator
    {
        return $this->repository->filterQuestions($filters);
    }

    public function getMostViewed(int $limit = 10): Collection
    {
        return $this->repository->getMostViewed($limit);
    }

    public function getMostAttempted(int $limit = 10): Collection
    {
        return $this->repository->getMostAttempted($limit);
    }

    public function getHighestSuccessRate(int $limit = 10): Collection
    {
        return $this->repository->getHighestSuccessRate($limit);
    }

    public function getRandomQuestions(int $count, ?array $filters = null): Collection
    {
        return $this->repository->getRandomQuestions($count, $filters);
    }

    public function recordQuestionView(int $id): void
    {
        $question = $this->repository->find($id);
        $question?->incrementViews();
    }

    public function recordQuestionAttempt(int $id, bool $success = false): void
    {
        $question = $this->repository->find($id);
        $question?->recordAttempt($success);
    }

    public function getQuestionsByCreator(int $userId, bool $publishedOnly = false): Collection
    {
        return $this->repository->getQuestionsByCreator($userId, $publishedOnly);
    }

    public function getDraftQuestions(int $userId): Collection
    {
        return $this->repository->getDraftQuestions($userId);
    }

    public function getUnverifiedQuestions(): Collection
    {
        return $this->repository->getUnverifiedQuestions();
    }
}
