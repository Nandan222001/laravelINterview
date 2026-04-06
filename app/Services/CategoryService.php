<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

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
}
