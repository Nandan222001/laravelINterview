<?php

namespace App\Services;

use App\Models\Topic;
use App\Repositories\TopicRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TopicService extends BaseService
{
    protected TopicRepository $repository;

    protected TopicHierarchyService $hierarchyService;

    public function __construct(
        TopicRepository $repository,
        TopicHierarchyService $hierarchyService
    ) {
        $this->repository = $repository;
        $this->hierarchyService = $hierarchyService;
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

    public function filterTopics(array $filters): LengthAwarePaginator
    {
        return $this->repository->filterTopics($filters);
    }
}
