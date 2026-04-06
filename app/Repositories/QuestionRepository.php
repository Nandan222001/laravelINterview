<?php

namespace App\Repositories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionRepository extends BaseRepository
{
    public function __construct(Question $model)
    {
        parent::__construct($model);
    }

    public function getAllPublished(): Collection
    {
        return $this->model->published()->verified()->get();
    }

    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->published()
            ->verified()
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->latest()
            ->paginate($perPage);
    }

    public function getQuestionsByDifficulty(int|string $difficulty, bool $publishedOnly = true): Collection
    {
        $query = $this->model->byDifficulty($difficulty);

        if ($publishedOnly) {
            $query->published()->verified();
        }

        return $query->with(['topics', 'tags', 'difficultyLevel'])->get();
    }

    public function getQuestionsByCategory(int|string $category, bool $publishedOnly = true): Collection
    {
        $query = $this->model->byCategory($category);

        if ($publishedOnly) {
            $query->published()->verified();
        }

        return $query->with(['topics', 'tags', 'difficultyLevel'])->get();
    }

    public function getQuestionsByTopic(int|string $topic, bool $publishedOnly = true): Collection
    {
        $query = $this->model->byTopic($topic);

        if ($publishedOnly) {
            $query->published()->verified();
        }

        return $query->with(['topics', 'tags', 'difficultyLevel'])->get();
    }

    public function getQuestionsWithTags(array $tags, bool $publishedOnly = true): Collection
    {
        $query = $this->model->withTags($tags);

        if ($publishedOnly) {
            $query->published()->verified();
        }

        return $query->with(['topics', 'tags', 'difficultyLevel'])->get();
    }

    public function searchQuestions(string $term, bool $publishedOnly = true): Collection
    {
        $query = $this->model->search($term);

        if ($publishedOnly) {
            $query->published()->verified();
        }

        return $query->with(['topics', 'tags', 'difficultyLevel'])->get();
    }

    public function filterQuestions(array $filters): LengthAwarePaginator
    {
        return $this->model->filter($filters)
            ->with(['difficultyLevel', 'topics', 'tags', 'creator'])
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getMostViewed(int $limit = 10): Collection
    {
        return $this->model->mostViewed($limit)
            ->published()
            ->verified()
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->get();
    }

    public function getMostAttempted(int $limit = 10): Collection
    {
        return $this->model->mostAttempted($limit)
            ->published()
            ->verified()
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->get();
    }

    public function getHighestSuccessRate(int $limit = 10): Collection
    {
        return $this->model->highestSuccessRate($limit)
            ->published()
            ->verified()
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->get();
    }

    public function getQuestionsByCreator(int $userId, bool $publishedOnly = false): Collection
    {
        $query = $this->model->byCreator($userId);

        if ($publishedOnly) {
            $query->published();
        }

        return $query->with(['difficultyLevel', 'topics', 'tags'])->latest()->get();
    }

    public function getQuestionWithRelations(int $id): ?Question
    {
        return $this->model->with([
            'difficultyLevel',
            'topics.category',
            'tags',
            'codeSnippets',
            'creator',
        ])->find($id);
    }

    public function getRandomQuestions(int $count, ?array $filters = null): Collection
    {
        $query = $this->model->published()->verified();

        if ($filters) {
            $query->filter($filters);
        }

        return $query->inRandomOrder()->limit($count)->get();
    }

    public function getUnverifiedQuestions(): Collection
    {
        return $this->model->unverified()
            ->with(['difficultyLevel', 'topics', 'tags', 'creator'])
            ->latest()
            ->get();
    }

    public function getDraftQuestions(int $userId): Collection
    {
        return $this->model->unpublished()
            ->byCreator($userId)
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->latest()
            ->get();
    }
}
