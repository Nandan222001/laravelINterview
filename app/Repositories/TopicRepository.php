<?php

namespace App\Repositories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TopicRepository extends BaseRepository
{
    public function __construct(Topic $model)
    {
        parent::__construct($model);
    }

    public function getAllActive(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function getTopicsByCategory(int $categoryId, bool $activeOnly = true): Collection
    {
        $query = $this->model->byCategory($categoryId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getRootTopics(?int $categoryId = null): Collection
    {
        $query = $this->model->rootOnly()->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->ordered()->get();
    }

    public function getTopicBySlug(string $slug): ?Topic
    {
        return $this->model->bySlug($slug)
            ->with(['category', 'children', 'parent'])
            ->first();
    }

    public function getTopicTree(?int $categoryId = null, ?int $maxDepth = null): Collection
    {
        $query = $this->model->rootOnly()->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($maxDepth) {
            $query->withNestedChildren($maxDepth);
        } else {
            $query->with('children');
        }

        return $query->ordered()->get();
    }

    public function getTopicWithAncestors(int $id): ?Topic
    {
        return $this->model->with('parent.parent.parent.parent.parent')->find($id);
    }

    public function getTopicWithDescendants(int $id): ?Topic
    {
        $topic = $this->find($id);

        if ($topic) {
            $topic->load('children.children.children.children.children');
        }

        return $topic;
    }

    public function searchTopics(string $term, ?int $categoryId = null): Collection
    {
        $query = $this->model->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('learning_objectives', 'like', "%{$term}%");
        })->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->ordered()->get();
    }

    public function getTopicsWithQuestionCount(?int $categoryId = null): Collection
    {
        $query = $this->model->withCount('questions')->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->ordered()->get();
    }

    public function getTopicsWithTags(array $tags, ?int $categoryId = null): Collection
    {
        $query = $this->model->withTags($tags)->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->ordered()->get();
    }

    public function filterTopics(array $filters): LengthAwarePaginator
    {
        return $this->model->filter($filters)
            ->with(['category', 'parent', 'tags'])
            ->ordered()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getTopicsByDepth(int $depth, ?int $categoryId = null): Collection
    {
        $query = $this->model->byDepth($depth)->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->ordered()->get();
    }

    public function getLeafTopics(?int $categoryId = null): Collection
    {
        $query = $this->model->whereRaw('(_rgt - _lft) = 1')->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->ordered()->get();
    }

    public function fullTextSearch(string $term, ?int $categoryId = null, bool $activeOnly = true): Collection
    {
        $query = $this->model->whereFullText(['name', 'description', 'learning_objectives'], $term);

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function fullTextSearchPaginated(
        string $term,
        ?int $categoryId = null,
        bool $activeOnly = true,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->whereFullText(['name', 'description', 'learning_objectives'], $term);

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->with(['category', 'parent', 'tags'])->ordered()->paginate($perPage);
    }

    public function advancedSearch(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildAdvancedSearchQuery($criteria);

        return $query->with(['category', 'parent', 'children', 'tags', 'questions'])
            ->ordered()
            ->paginate($perPage);
    }

    protected function buildAdvancedSearchQuery(array $criteria): Builder
    {
        $query = $this->model->newQuery();

        if (isset($criteria['search'])) {
            $query->whereFullText(['name', 'description', 'learning_objectives'], $criteria['search']);
        }

        if (isset($criteria['category_id'])) {
            $query->byCategory($criteria['category_id']);
        }

        if (isset($criteria['category_ids']) && is_array($criteria['category_ids'])) {
            $query->whereIn('category_id', $criteria['category_ids']);
        }

        if (isset($criteria['is_active'])) {
            $criteria['is_active'] ? $query->active() : $query->inactive();
        }

        if (isset($criteria['parent_id'])) {
            if ($criteria['parent_id'] === null || $criteria['parent_id'] === 'null') {
                $query->rootOnly();
            } else {
                $query->where('parent_id', $criteria['parent_id']);
            }
        }

        if (isset($criteria['depth'])) {
            $query->byDepth($criteria['depth']);
        }

        if (isset($criteria['max_depth'])) {
            $query->maxDepth($criteria['max_depth']);
        }

        if (isset($criteria['min_depth'])) {
            $query->where('depth', '>=', $criteria['min_depth']);
        }

        if (isset($criteria['is_root'])) {
            if ($criteria['is_root']) {
                $query->rootOnly();
            } else {
                $query->whereNotNull('parent_id');
            }
        }

        if (isset($criteria['is_leaf'])) {
            if ($criteria['is_leaf']) {
                $query->whereRaw('(_rgt - _lft) = 1');
            } else {
                $query->whereRaw('(_rgt - _lft) > 1');
            }
        }

        if (isset($criteria['has_children'])) {
            if ($criteria['has_children']) {
                $query->has('children');
            } else {
                $query->doesntHave('children');
            }
        }

        if (isset($criteria['has_questions'])) {
            if ($criteria['has_questions']) {
                $query->has('questions');
            } else {
                $query->doesntHave('questions');
            }
        }

        if (isset($criteria['min_questions'])) {
            $query->has('questions', '>=', $criteria['min_questions']);
        }

        if (isset($criteria['tag_ids']) && is_array($criteria['tag_ids'])) {
            $query->whereHas('tags', function (Builder $q) use ($criteria) {
                $q->whereIn('tags.id', $criteria['tag_ids']);
            });
        }

        if (isset($criteria['tag_slugs']) && is_array($criteria['tag_slugs'])) {
            $query->withTags($criteria['tag_slugs']);
        }

        if (isset($criteria['created_after'])) {
            $query->where('created_at', '>=', $criteria['created_after']);
        }

        if (isset($criteria['created_before'])) {
            $query->where('created_at', '<=', $criteria['created_before']);
        }

        return $query;
    }

    public function findWithEagerLoading(int $id, array $relations = []): ?Topic
    {
        $defaultRelations = [
            'category',
            'parent',
            'children',
            'tags',
            'questions',
        ];

        $relations = empty($relations) ? $defaultRelations : $relations;

        return $this->model->with($relations)->find($id);
    }

    public function getTopicTreeWithDepth(?int $categoryId = null, ?int $maxDepth = null): Collection
    {
        $query = $this->model->rootOnly()->active();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($maxDepth !== null) {
            $query->withNestedChildren($maxDepth);
        } else {
            $query->with('children.children.children');
        }

        return $query->ordered()->get();
    }

    public function getTopicsByParent(?int $parentId, ?int $categoryId = null, bool $activeOnly = true): Collection
    {
        $query = $parentId === null
            ? $this->model->rootOnly()
            : $this->model->where('parent_id', $parentId);

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getTopicsWithStatistics(?int $categoryId = null, bool $activeOnly = true): Collection
    {
        $query = $this->model->withCount(['questions', 'children']);

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getTopicHierarchy(int $topicId): array
    {
        $topic = $this->find($topicId);

        if (! $topic) {
            return [];
        }

        return [
            'topic' => $topic,
            'ancestors' => $topic->getAncestorsOnly(),
            'descendants' => $topic->getDescendantsOnly(),
            'parent' => $topic->parent,
            'children' => $topic->children,
            'siblings' => $topic->getSiblings(),
            'root' => $topic->getRoot(),
        ];
    }

    public function getTopicsByMultipleCriteria(
        ?int $categoryId = null,
        ?bool $isActive = null,
        ?int $parentId = null,
        ?int $depth = null,
        ?array $tagIds = null,
        bool $withChildren = false,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if ($categoryId !== null) {
            $query->byCategory($categoryId);
        }

        if ($isActive !== null) {
            $isActive ? $query->active() : $query->inactive();
        }

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        if ($depth !== null) {
            $query->byDepth($depth);
        }

        if ($tagIds) {
            $query->whereHas('tags', function (Builder $q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        if ($withChildren) {
            $query->with('children');
        }

        return $query->with(['category', 'parent', 'tags'])->ordered()->paginate($perPage);
    }

    public function getTopicsWithMinQuestions(int $minQuestions, ?int $categoryId = null, bool $activeOnly = true): Collection
    {
        $query = $this->model->has('questions', '>=', $minQuestions);

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getTopicsOrderedByQuestionCount(?int $categoryId = null, bool $activeOnly = true, int $limit = 10): Collection
    {
        $query = $this->model->withCount('questions');

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('questions_count', 'desc')->limit($limit)->get();
    }

    public function bulkUpdateActiveStatus(array $topicIds, bool $status): int
    {
        return $this->model->whereIn('id', $topicIds)->update(['is_active' => $status]);
    }

    public function bulkUpdateCategory(array $topicIds, int $categoryId): int
    {
        return $this->model->whereIn('id', $topicIds)->update(['category_id' => $categoryId]);
    }

    public function moveToParent(int $topicId, ?int $newParentId, ?int $categoryId = null): bool
    {
        $topic = $this->find($topicId);

        if (! $topic) {
            return false;
        }

        if ($newParentId !== null) {
            $newParent = $this->find($newParentId);
            if (! $newParent) {
                return false;
            }

            if ($topicId === $newParentId) {
                return false;
            }

            if ($topic->isAncestorOf($newParent)) {
                return false;
            }

            $categoryId = $categoryId ?? $newParent->category_id;
        }

        $updateData = ['parent_id' => $newParentId];

        if ($categoryId !== null) {
            $updateData['category_id'] = $categoryId;
        }

        if ($newParentId === null) {
            $updateData['depth'] = 0;
        } else {
            $newParent = $this->find($newParentId);
            $updateData['depth'] = $newParent->depth + 1;
        }

        $result = $topic->update($updateData);

        if ($result) {
            $topic->rebuildNestedSet();
            $topic->rebuildClosureTable();
        }

        return $result;
    }

    public function reorderTopics(array $topicOrders): void
    {
        foreach ($topicOrders as $id => $order) {
            $this->model->where('id', $id)->update(['order' => $order]);
        }
    }

    public function getTopicWithFullRelations(int $id): ?Topic
    {
        return $this->model->with([
            'category',
            'parent',
            'children',
            'tags',
            'questions' => function ($query) {
                $query->published()->verified()->with(['difficultyLevel', 'tags']);
            },
            'ancestors',
            'descendants',
        ])->find($id);
    }

    public function getTopicsBreadcrumb(int $topicId): Collection
    {
        $topic = $this->find($topicId);

        if (! $topic) {
            return new Collection;
        }

        $breadcrumb = $topic->getAncestorsAndSelf();

        return $breadcrumb->sortBy('_lft')->values();
    }

    public function getDescendantIds(int $topicId): array
    {
        $topic = $this->find($topicId);

        if (! $topic) {
            return [];
        }

        return $topic->getDescendantsOnly()->pluck('id')->toArray();
    }

    public function getAncestorIds(int $topicId): array
    {
        $topic = $this->find($topicId);

        if (! $topic) {
            return [];
        }

        return $topic->getAncestorsOnly()->pluck('id')->toArray();
    }

    public function getSiblings(int $topicId, bool $includeSelf = false): Collection
    {
        $topic = $this->find($topicId);

        if (! $topic) {
            return new Collection;
        }

        return $topic->getSiblings($includeSelf);
    }

    public function filterTopicsAdvanced(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $query->whereFullText(['name', 'description', 'learning_objectives'], $filters['search']);
        }

        if (isset($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $filters['is_active'] ? $query->active() : $query->inactive();
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'root' || $filters['parent_id'] === null) {
                $query->rootOnly();
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['depth'])) {
            $query->byDepth($filters['depth']);
        }

        if (isset($filters['has_children'])) {
            $filters['has_children'] ? $query->has('children') : $query->doesntHave('children');
        }

        if (isset($filters['has_questions'])) {
            $filters['has_questions'] ? $query->has('questions') : $query->doesntHave('questions');
        }

        if (isset($filters['tag_slugs']) && is_array($filters['tag_slugs'])) {
            $query->withTags($filters['tag_slugs']);
        }

        return $query->with(['category', 'parent', 'children', 'tags'])
            ->ordered()
            ->paginate($perPage);
    }
}
