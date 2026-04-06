<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function getAllActive(): Collection
    {
        return $this->model->active()->orderBy('order')->get();
    }

    public function getRootCategories(): Collection
    {
        return $this->model->rootOnly()->active()->ordered()->get();
    }

    public function getCategoryWithChildren(int $id): ?Category
    {
        return $this->model->with(['children' => function ($query) {
            $query->active()->ordered();
        }])->find($id);
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->model->bySlug($slug)->with('children')->first();
    }

    public function getCategoryTree(): Collection
    {
        return $this->model->rootOnly()
            ->active()
            ->with('children')
            ->ordered()
            ->get();
    }

    public function searchCategories(string $term): Collection
    {
        return $this->model->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->active()
            ->ordered()
            ->get();
    }

    public function getCategoriesWithTopicCount(): Collection
    {
        return $this->model->withCount('topics')->active()->ordered()->get();
    }

    public function getCategoriesWithTag(string $tag): Collection
    {
        return $this->model->withTag($tag)->active()->ordered()->get();
    }

    public function fullTextSearch(string $term, bool $activeOnly = true): Collection
    {
        $query = $this->model->whereFullText(['name', 'description'], $term);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function fullTextSearchPaginated(string $term, bool $activeOnly = true, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->whereFullText(['name', 'description'], $term);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->paginate($perPage);
    }

    public function advancedSearch(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildAdvancedSearchQuery($criteria);

        return $query->with(['parent', 'children', 'topics', 'tags'])
            ->ordered()
            ->paginate($perPage);
    }

    protected function buildAdvancedSearchQuery(array $criteria): Builder
    {
        $query = $this->model->newQuery();

        if (isset($criteria['search'])) {
            $query->whereFullText(['name', 'description'], $criteria['search']);
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

        if (isset($criteria['has_children'])) {
            if ($criteria['has_children']) {
                $query->has('children');
            } else {
                $query->doesntHave('children');
            }
        }

        if (isset($criteria['has_topics'])) {
            if ($criteria['has_topics']) {
                $query->has('topics');
            } else {
                $query->doesntHave('topics');
            }
        }

        if (isset($criteria['tag_ids']) && is_array($criteria['tag_ids'])) {
            $query->whereHas('tags', function (Builder $q) use ($criteria) {
                $q->whereIn('tags.id', $criteria['tag_ids']);
            });
        }

        if (isset($criteria['tag_slugs']) && is_array($criteria['tag_slugs'])) {
            $query->withTags($criteria['tag_slugs']);
        }

        if (isset($criteria['min_topics'])) {
            $query->has('topics', '>=', $criteria['min_topics']);
        }

        if (isset($criteria['created_after'])) {
            $query->where('created_at', '>=', $criteria['created_after']);
        }

        if (isset($criteria['created_before'])) {
            $query->where('created_at', '<=', $criteria['created_before']);
        }

        return $query;
    }

    public function findWithEagerLoading(int $id, array $relations = []): ?Category
    {
        $defaultRelations = [
            'parent',
            'children',
            'topics',
            'tags',
        ];

        $relations = empty($relations) ? $defaultRelations : $relations;

        return $this->model->with($relations)->find($id);
    }

    public function getCategoryTreeWithDepth(?int $maxDepth = null): Collection
    {
        $query = $this->model->rootOnly()->active();

        if ($maxDepth !== null) {
            $with = ['children'];
            for ($i = 2; $i <= $maxDepth; $i++) {
                $with[] = str_repeat('children.', $i - 1).'children';
            }
            $query->with($with);
        } else {
            $query->with('children.children.children');
        }

        return $query->ordered()->get();
    }

    public function getCategoriesByParent(?int $parentId, bool $activeOnly = true): Collection
    {
        $query = $parentId === null
            ? $this->model->rootOnly()
            : $this->model->where('parent_id', $parentId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getCategoriesWithStatistics(bool $activeOnly = true): Collection
    {
        $query = $this->model->withCount(['topics', 'children']);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getCategoriesWithTopicsPaginated(bool $activeOnly = true, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['topics' => function ($query) {
            $query->active()->ordered();
        }]);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->paginate($perPage);
    }

    public function getCategoryHierarchy(int $categoryId): array
    {
        $category = $this->find($categoryId);

        if (! $category) {
            return [];
        }

        $ancestors = $category->getAncestors()->reverse()->values();
        $descendants = $category->getDescendants();

        return [
            'category' => $category,
            'ancestors' => $ancestors,
            'descendants' => $descendants,
            'parent' => $category->parent,
            'children' => $category->children,
        ];
    }

    public function getCategoriesByMultipleCriteria(
        ?bool $isActive = null,
        ?int $parentId = null,
        ?array $tagIds = null,
        bool $withChildren = false,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if ($isActive !== null) {
            $isActive ? $query->active() : $query->inactive();
        }

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        if ($tagIds) {
            $query->whereHas('tags', function (Builder $q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        if ($withChildren) {
            $query->with('children');
        }

        return $query->ordered()->paginate($perPage);
    }

    public function getLeafCategories(bool $activeOnly = true): Collection
    {
        $query = $this->model->doesntHave('children');

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getCategoriesWithMinTopics(int $minTopics, bool $activeOnly = true): Collection
    {
        $query = $this->model->has('topics', '>=', $minTopics);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function bulkUpdateActiveStatus(array $categoryIds, bool $status): int
    {
        return $this->model->whereIn('id', $categoryIds)->update(['is_active' => $status]);
    }

    public function moveToParent(int $categoryId, ?int $newParentId): bool
    {
        $category = $this->find($categoryId);

        if (! $category) {
            return false;
        }

        if ($newParentId !== null) {
            $newParent = $this->find($newParentId);
            if (! $newParent) {
                return false;
            }

            if ($categoryId === $newParentId) {
                return false;
            }

            $descendants = $category->getDescendants();
            if ($descendants->contains('id', $newParentId)) {
                return false;
            }
        }

        return $category->update(['parent_id' => $newParentId]);
    }

    public function reorderCategories(array $categoryOrders): void
    {
        foreach ($categoryOrders as $id => $order) {
            $this->model->where('id', $id)->update(['order' => $order]);
        }
    }

    public function getCategoriesOrderedByTopicCount(bool $activeOnly = true, int $limit = 10): Collection
    {
        $query = $this->model->withCount('topics');

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('topics_count', 'desc')->limit($limit)->get();
    }

    public function filterCategories(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $query->whereFullText(['name', 'description'], $filters['search']);
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

        if (isset($filters['has_children'])) {
            $filters['has_children'] ? $query->has('children') : $query->doesntHave('children');
        }

        if (isset($filters['tag_slugs']) && is_array($filters['tag_slugs'])) {
            $query->withTags($filters['tag_slugs']);
        }

        return $query->with(['parent', 'children', 'tags'])
            ->ordered()
            ->paginate($perPage);
    }
}
