<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryService extends BaseService
{
    protected CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllActive(): Collection
    {
        return $this->repository->getAllActive();
    }

    public function getCategoryTree(): Collection
    {
        return $this->repository->getCategoryTree();
    }

    public function getCategoryTreeWithDepth(?int $maxDepth = null, bool $activeOnly = true): Collection
    {
        $query = Category::query();

        if ($activeOnly) {
            $query->active();
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

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->repository->getCategoryBySlug($slug);
    }

    public function createCategory(array $data): Category
    {
        $category = $this->repository->create($data);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $category->syncTags($data['tags']);
        }

        return $category->fresh();
    }

    public function updateCategory(int $id, array $data): Category
    {
        $this->repository->update($id, $data);
        $category = $this->repository->find($id);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $category->syncTags($data['tags']);
        }

        return $category->fresh();
    }

    public function deleteCategory(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function searchCategories(string $term): Collection
    {
        return $this->repository->searchCategories($term);
    }

    public function filterCategories(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->filterCategories($filters, $perPage);
    }

    public function findWithRelations(int $id, array $relations = []): ?Category
    {
        return $this->repository->findWithEagerLoading($id, $relations);
    }

    public function getCategoriesByParent(?int $parentId, bool $activeOnly = true): Collection
    {
        return $this->repository->getCategoriesByParent($parentId, $activeOnly);
    }

    public function getBreadcrumb(int $categoryId): array
    {
        $category = $this->repository->find($categoryId);

        if (! $category) {
            return [];
        }

        $breadcrumb = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
                'parent_id' => $current->parent_id,
            ]);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    public function find(int $id): ?Category
    {
        return $this->repository->find($id);
    }
}
