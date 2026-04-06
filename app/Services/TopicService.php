<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Topic;
use App\Repositories\QuestionRepository;
use App\Repositories\TopicRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TopicService extends BaseService
{
    protected TopicRepository $repository;

    protected TopicHierarchyService $hierarchyService;

    protected QuestionRepository $questionRepository;

    public function __construct(
        TopicRepository $repository,
        TopicHierarchyService $hierarchyService,
        QuestionRepository $questionRepository
    ) {
        $this->repository = $repository;
        $this->hierarchyService = $hierarchyService;
        $this->questionRepository = $questionRepository;
    }

    public function getAllActive(): Collection
    {
        return $this->repository->getAllActive();
    }

    public function getTopicsByCategory(int $categoryId, bool $activeOnly = true): Collection
    {
        return $this->repository->getTopicsByCategory($categoryId, $activeOnly);
    }

    public function getTopicTree(?int $categoryId = null, ?int $maxDepth = null): Collection
    {
        return $this->repository->getTopicTree($categoryId, $maxDepth);
    }

    public function getTopicTreeWithDepth(?int $categoryId = null, ?int $maxDepth = null, bool $activeOnly = true): Collection
    {
        $query = Topic::query();

        if ($activeOnly) {
            $query->active();
        }

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        $query->rootOnly()->ordered();

        if ($maxDepth !== null && $maxDepth > 0) {
            $with = [];
            $relation = 'children';
            for ($i = 1; $i <= $maxDepth; $i++) {
                $with[] = $relation;
                $relation .= '.children';
            }
            $query->with($with);
        } else {
            $query->with('children.children.children');
        }

        return $query->get();
    }

    public function getTopicBySlug(string $slug): ?Topic
    {
        return $this->repository->getTopicBySlug($slug);
    }

    public function createTopic(array $data): Topic
    {
        $topic = $this->repository->create($data);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $topic->syncTags($data['tags']);
        }

        return $topic->fresh();
    }

    public function updateTopic(int $id, array $data): Topic
    {
        $this->repository->update($id, $data);
        $topic = $this->repository->find($id);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $topic->syncTags($data['tags']);
        }

        return $topic->fresh();
    }

    public function deleteTopic(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function moveTopic(int $topicId, ?int $newParentId): void
    {
        $topic = $this->repository->findOrFail($topicId);
        $this->hierarchyService->moveTopic($topic, $newParentId);
    }

    public function reorderTopics(array $topicOrder): void
    {
        $this->hierarchyService->reorderTopics($topicOrder);
    }

    public function duplicateTopic(int $topicId, bool $includeChildren = false): Topic
    {
        $topic = $this->repository->findOrFail($topicId);

        return $this->hierarchyService->duplicateTopic($topic, $includeChildren);
    }

    public function getTopicPath(int $topicId): Collection
    {
        $topic = $this->repository->findOrFail($topicId);

        return $this->hierarchyService->getPath($topic);
    }

    public function getTopicBreadcrumb(int $topicId): array
    {
        $topic = $this->repository->findOrFail($topicId);

        return $this->hierarchyService->getBreadcrumb($topic);
    }

    public function searchTopics(string $term, ?int $categoryId = null): Collection
    {
        return $this->repository->searchTopics($term, $categoryId);
    }

    public function filterTopics(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $filters['per_page'] = $perPage;

        return $this->repository->filterTopics($filters);
    }

    public function find(int $id): ?Topic
    {
        return $this->repository->find($id);
    }

    public function findWithRelations(int $id, array $relations = []): ?Topic
    {
        return $this->repository->findWithEagerLoading($id, $relations);
    }

    public function getTopicsByParent(?int $parentId, ?int $categoryId = null, bool $activeOnly = true): Collection
    {
        return $this->repository->getTopicsByParent($parentId, $categoryId, $activeOnly);
    }

    public function getBreadcrumb(int $topicId): array
    {
        $topic = $this->repository->find($topicId);

        if (! $topic) {
            return [];
        }

        $ancestors = $topic->getAncestorsAndSelf()->sortBy('_lft');

        $breadcrumb = [];
        foreach ($ancestors as $ancestor) {
            $breadcrumb[] = [
                'id' => $ancestor->id,
                'name' => $ancestor->name,
                'slug' => $ancestor->slug,
                'parent_id' => $ancestor->parent_id,
                'category_id' => $ancestor->category_id,
            ];
        }

        return $breadcrumb;
    }

    public function getChildren(int $topicId, bool $activeOnly = true): Collection
    {
        $topic = $this->repository->find($topicId);

        if (! $topic) {
            return new Collection;
        }

        $query = Topic::where('parent_id', $topicId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getDescendants(int $topicId, ?int $maxDepth = null): ?Collection
    {
        $topic = $this->repository->find($topicId);

        if (! $topic) {
            return null;
        }

        $query = Topic::whereBetween('_lft', [$topic->_lft + 1, $topic->_rgt - 1]);

        if ($maxDepth !== null) {
            $query->where('depth', '<=', $topic->depth + $maxDepth);
        }

        return $query->orderBy('_lft')->get();
    }

    public function getAncestors(int $topicId): ?Collection
    {
        $topic = $this->repository->find($topicId);

        if (! $topic) {
            return null;
        }

        return $topic->getAncestorsOnly();
    }

    public function getDescendantIds(int $topicId): array
    {
        return $this->repository->getDescendantIds($topicId);
    }

    public function getQuestionsByTopicWithFilters(int $topicId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Question::query();

        if (isset($filters['topic_ids'])) {
            $query->whereHas('topics', function ($q) use ($filters) {
                $q->whereIn('topics.id', $filters['topic_ids']);
            });
        } else {
            $query->byTopic($topicId);
        }

        if (isset($filters['difficulty'])) {
            $query->byDifficulty($filters['difficulty']);
        }

        if (isset($filters['published']) && $filters['published']) {
            $query->published();
        }

        if (isset($filters['verified']) && $filters['verified']) {
            $query->verified();
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $query->withTags($filters['tags']);
        }

        if (isset($filters['min_difficulty_level'])) {
            $query->minDifficulty($filters['min_difficulty_level']);
        }

        if (isset($filters['max_difficulty_level'])) {
            $query->maxDifficulty($filters['max_difficulty_level']);
        }

        return $query->with(['difficultyLevel', 'topics', 'tags', 'creator'])
            ->paginate($perPage);
    }
}
