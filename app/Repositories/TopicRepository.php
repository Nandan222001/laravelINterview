<?php

namespace App\Repositories;

use App\Models\Topic;
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
}
