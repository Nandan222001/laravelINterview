<?php

namespace App\Repositories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagRepository extends BaseRepository
{
    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }

    public function getAllOrdered(): Collection
    {
        return $this->model->ordered()->get();
    }

    public function getTagBySlug(string $slug): ?Tag
    {
        return $this->model->bySlug($slug)->first();
    }

    public function getMostUsed(int $limit = 10): Collection
    {
        return $this->model->mostUsed($limit)->get();
    }

    public function getPopularTags(int $minUsage = 10): Collection
    {
        return $this->model->popular($minUsage)->ordered()->get();
    }

    public function searchTags(string $term): Collection
    {
        return $this->model->search($term)->ordered()->get();
    }

    public function getTagsWithUsageCounts(): Collection
    {
        return $this->model->withUsageCounts()->ordered()->get();
    }

    public function getOrCreateTag(string $slug, ?string $name = null): Tag
    {
        return $this->model->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name ?? ucwords(str_replace('-', ' ', $slug))]
        );
    }

    public function syncTagsForEntity($entity, array $tags): void
    {
        $tagIds = collect($tags)->map(function ($tag) {
            if ($tag instanceof Tag) {
                return $tag->id;
            }
            if (is_numeric($tag)) {
                return $tag;
            }

            return $this->getOrCreateTag($tag)->id;
        })->toArray();

        $entity->tags()->sync($tagIds);
    }

    public function getUnusedTags(): Collection
    {
        return $this->model->where('usage_count', 0)->ordered()->get();
    }
}
