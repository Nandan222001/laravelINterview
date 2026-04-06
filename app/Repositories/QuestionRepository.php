<?php

namespace App\Repositories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
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

    public function fullTextSearch(string $term, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->whereFullText(['title', 'question_text'], $term);

        if (! empty($filters)) {
            $query->filter($filters);
        }

        return $query->with(['difficultyLevel', 'topics.category', 'tags', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function advancedSearch(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildAdvancedSearchQuery($criteria);

        return $query->with(['difficultyLevel', 'topics.category', 'tags', 'creator', 'codeSnippets'])
            ->latest()
            ->paginate($perPage);
    }

    protected function buildAdvancedSearchQuery(array $criteria): Builder
    {
        $query = $this->model->newQuery();

        if (isset($criteria['search'])) {
            $query->whereFullText(['title', 'question_text'], $criteria['search']);
        }

        if (isset($criteria['difficulty_ids']) && is_array($criteria['difficulty_ids'])) {
            $query->whereIn('difficulty_level_id', $criteria['difficulty_ids']);
        }

        if (isset($criteria['difficulty_level_min'])) {
            $query->minDifficulty($criteria['difficulty_level_min']);
        }

        if (isset($criteria['difficulty_level_max'])) {
            $query->maxDifficulty($criteria['difficulty_level_max']);
        }

        if (isset($criteria['category_ids']) && is_array($criteria['category_ids'])) {
            $query->whereHas('topics.category', function (Builder $q) use ($criteria) {
                $q->whereIn('categories.id', $criteria['category_ids']);
            });
        }

        if (isset($criteria['topic_ids']) && is_array($criteria['topic_ids'])) {
            $query->whereHas('topics', function (Builder $q) use ($criteria) {
                $q->whereIn('topics.id', $criteria['topic_ids']);
            });
        }

        if (isset($criteria['tag_ids']) && is_array($criteria['tag_ids'])) {
            $query->whereHas('tags', function (Builder $q) use ($criteria) {
                $q->whereIn('tags.id', $criteria['tag_ids']);
            });
        }

        if (isset($criteria['tag_slugs']) && is_array($criteria['tag_slugs'])) {
            $query->withTags($criteria['tag_slugs']);
        }

        if (isset($criteria['is_published'])) {
            $criteria['is_published'] ? $query->published() : $query->unpublished();
        }

        if (isset($criteria['is_verified'])) {
            $criteria['is_verified'] ? $query->verified() : $query->unverified();
        }

        if (isset($criteria['creator_id'])) {
            $query->byCreator($criteria['creator_id']);
        }

        if (isset($criteria['min_points'])) {
            $query->where('points', '>=', $criteria['min_points']);
        }

        if (isset($criteria['max_points'])) {
            $query->where('points', '<=', $criteria['max_points']);
        }

        if (isset($criteria['min_views'])) {
            $query->where('view_count', '>=', $criteria['min_views']);
        }

        if (isset($criteria['min_attempts'])) {
            $query->where('attempt_count', '>=', $criteria['min_attempts']);
        }

        if (isset($criteria['min_success_rate'])) {
            $rate = $criteria['min_success_rate'] / 100;
            $query->whereRaw('(success_count / NULLIF(attempt_count, 0)) >= ?', [$rate]);
        }

        if (isset($criteria['max_success_rate'])) {
            $rate = $criteria['max_success_rate'] / 100;
            $query->whereRaw('(success_count / NULLIF(attempt_count, 0)) <= ?', [$rate]);
        }

        if (isset($criteria['has_hints'])) {
            $query->whereNotNull('hints');
        }

        if (isset($criteria['has_code_snippets'])) {
            $query->has('codeSnippets');
        }

        if (isset($criteria['time_limit_min'])) {
            $query->where('time_limit', '>=', $criteria['time_limit_min']);
        }

        if (isset($criteria['time_limit_max'])) {
            $query->where('time_limit', '<=', $criteria['time_limit_max']);
        }

        if (isset($criteria['created_after'])) {
            $query->where('created_at', '>=', $criteria['created_after']);
        }

        if (isset($criteria['created_before'])) {
            $query->where('created_at', '<=', $criteria['created_before']);
        }

        if (isset($criteria['order_by'])) {
            $this->applyOrdering($query, $criteria['order_by'], $criteria['order_direction'] ?? 'asc');
        }

        return $query;
    }

    protected function applyOrdering(Builder $query, string $orderBy, string $direction = 'asc'): void
    {
        match ($orderBy) {
            'views' => $query->orderBy('view_count', $direction),
            'attempts' => $query->orderBy('attempt_count', $direction),
            'success' => $query->orderBy('success_count', $direction),
            'success_rate' => $query->orderByRaw('(success_count / NULLIF(attempt_count, 0)) '.$direction),
            'points' => $query->orderBy('points', $direction),
            'difficulty' => $query->join('difficulty_levels', 'questions.difficulty_level_id', '=', 'difficulty_levels.id')
                ->orderBy('difficulty_levels.level', $direction)
                ->select('questions.*'),
            'title' => $query->orderBy('title', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            default => $query->latest(),
        };
    }

    public function findWithEagerLoading(int $id, array $relations = []): ?Question
    {
        $defaultRelations = [
            'difficultyLevel',
            'topics.category',
            'tags',
            'codeSnippets',
            'creator',
        ];

        $relations = empty($relations) ? $defaultRelations : $relations;

        return $this->model->with($relations)->find($id);
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $query->whereFullText(['title', 'question_text'], $filters['search']);
        }

        if (! empty($filters)) {
            $query->filter($filters);
        }

        $defaultRelations = ['difficultyLevel', 'topics', 'tags', 'creator'];
        $relations = empty($relations) ? $defaultRelations : $relations;

        return $query->with($relations)
            ->latest()
            ->paginate($perPage);
    }

    public function getQuestionsByMultipleCriteria(
        ?array $difficultyIds = null,
        ?array $categoryIds = null,
        ?array $topicIds = null,
        ?array $tagIds = null,
        bool $publishedOnly = true,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if ($publishedOnly) {
            $query->published()->verified();
        }

        if ($difficultyIds) {
            $query->whereIn('difficulty_level_id', $difficultyIds);
        }

        if ($categoryIds) {
            $query->whereHas('topics.category', function (Builder $q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        if ($topicIds) {
            $query->whereHas('topics', function (Builder $q) use ($topicIds) {
                $q->whereIn('topics.id', $topicIds);
            });
        }

        if ($tagIds) {
            $query->whereHas('tags', function (Builder $q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        return $query->with(['difficultyLevel', 'topics.category', 'tags'])
            ->latest()
            ->paginate($perPage);
    }

    public function getQuestionsWithStatistics(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->selectRaw('
            questions.*,
            (success_count / NULLIF(attempt_count, 0)) as success_rate,
            (success_count / NULLIF(attempt_count, 0) * 100) as success_percentage
        ');

        if (! empty($filters)) {
            $query->filter($filters);
        }

        return $query->with(['difficultyLevel', 'topics', 'tags'])
            ->latest()
            ->paginate($perPage);
    }

    public function getPopularQuestions(int $minViews = 100, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->popular($minViews)
            ->published()
            ->verified()
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->orderBy('view_count', 'desc')
            ->paginate($perPage);
    }

    public function getTrendingQuestions(int $days = 7, int $limit = 10): Collection
    {
        return $this->model->published()
            ->verified()
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getQuestionsByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->whereBetween('created_at', [$startDate, $endDate])
            ->with(['difficultyLevel', 'topics', 'tags', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function bulkUpdatePublishStatus(array $questionIds, bool $status): int
    {
        return $this->model->whereIn('id', $questionIds)->update(['is_published' => $status]);
    }

    public function bulkUpdateVerificationStatus(array $questionIds, bool $status): int
    {
        return $this->model->whereIn('id', $questionIds)->update(['is_verified' => $status]);
    }

    public function getSimilarQuestions(int $questionId, int $limit = 5): Collection
    {
        $question = $this->find($questionId);

        if (! $question) {
            return new Collection;
        }

        return $this->model->where('id', '!=', $questionId)
            ->where('difficulty_level_id', $question->difficulty_level_id)
            ->whereHas('topics', function (Builder $q) use ($question) {
                $topicIds = $question->topics->pluck('id')->toArray();
                $q->whereIn('topics.id', $topicIds);
            })
            ->published()
            ->verified()
            ->with(['difficultyLevel', 'topics', 'tags'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
