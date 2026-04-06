<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

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
}
