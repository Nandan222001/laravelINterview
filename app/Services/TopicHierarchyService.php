<?php

namespace App\Services;

use App\Models\Topic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopicHierarchyService
{
    public function buildTree(?int $categoryId = null, ?int $parentId = null): Collection
    {
        $query = Topic::with(['children' => function ($q) {
            $q->orderBy('order');
        }])
            ->where('parent_id', $parentId)
            ->orderBy('order');

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $topics = $query->get();

        return $topics->map(function ($topic) use ($categoryId) {
            return [
                'id' => $topic->id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'depth' => $topic->depth,
                'children' => $this->buildTree($categoryId, $topic->id),
            ];
        });
    }

    public function buildFlatTree(?int $categoryId = null): Collection
    {
        $query = Topic::with('parent')
            ->orderBy('_lft');

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->get()->map(function ($topic) {
            return [
                'id' => $topic->id,
                'name' => str_repeat('  ', $topic->depth).$topic->name,
                'slug' => $topic->slug,
                'depth' => $topic->depth,
                'parent_id' => $topic->parent_id,
            ];
        });
    }

    public function moveTopic(Topic $topic, ?int $newParentId): void
    {
        DB::transaction(function () use ($topic, $newParentId) {
            $oldParentId = $topic->parent_id;

            if ($newParentId === null) {
                $topic->makeRoot();
            } else {
                $newParent = Topic::findOrFail($newParentId);

                if ($topic->isAncestorOf($newParent)) {
                    throw new \InvalidArgumentException('Cannot move a topic to be a child of its descendant');
                }

                $topic->makeChildOf($newParent);
            }

            $this->rebuildCategoryTree($topic->category_id);
        });
    }

    public function rebuildCategoryTree(int $categoryId): void
    {
        $counter = 0;
        $this->rebuildRecursive($categoryId, null, $counter);
    }

    protected function rebuildRecursive(int $categoryId, ?int $parentId, int &$counter, int $depth = 0): void
    {
        $topics = Topic::where('category_id', $categoryId)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();

        foreach ($topics as $topic) {
            $left = ++$counter;
            $this->rebuildRecursive($categoryId, $topic->id, $counter, $depth + 1);
            $right = ++$counter;

            $topic->updateQuietly([
                '_lft' => $left,
                '_rgt' => $right,
                'depth' => $depth,
            ]);
        }
    }

    public function getPath(Topic $topic): Collection
    {
        return $topic->getAncestorsAndSelf();
    }

    public function getBreadcrumb(Topic $topic): array
    {
        return $topic->getAncestorsAndSelf()
            ->map(fn ($ancestor) => [
                'id' => $ancestor->id,
                'name' => $ancestor->name,
                'slug' => $ancestor->slug,
            ])
            ->toArray();
    }

    public function getImmediateChildren(Topic $topic): Collection
    {
        return $topic->children;
    }

    public function getAllDescendants(Topic $topic): Collection
    {
        return $topic->getDescendantsOnly();
    }

    public function getAllAncestors(Topic $topic): Collection
    {
        return $topic->getAncestorsOnly();
    }

    public function getSiblings(Topic $topic, bool $includeSelf = false): Collection
    {
        return $topic->getSiblings($includeSelf);
    }

    public function reorderTopics(array $topicOrder): void
    {
        DB::transaction(function () use ($topicOrder) {
            foreach ($topicOrder as $order => $topicId) {
                Topic::where('id', $topicId)->update(['order' => $order]);
            }
        });
    }

    public function duplicateTopic(Topic $topic, bool $includeChildren = false): Topic
    {
        return DB::transaction(function () use ($topic, $includeChildren) {
            $newTopic = $topic->replicate();
            $newTopic->slug = $topic->slug.'-copy-'.time();
            $newTopic->name = $topic->name.' (Copy)';
            $newTopic->save();

            $newTopic->tags()->sync($topic->tags->pluck('id'));

            if ($includeChildren && $topic->hasChildren()) {
                foreach ($topic->children as $child) {
                    $this->duplicateTopicRecursive($child, $newTopic);
                }
            }

            return $newTopic->fresh();
        });
    }

    protected function duplicateTopicRecursive(Topic $topic, Topic $newParent): void
    {
        $newTopic = $topic->replicate();
        $newTopic->parent_id = $newParent->id;
        $newTopic->slug = $topic->slug.'-copy-'.time().'-'.$topic->id;
        $newTopic->save();

        $newTopic->tags()->sync($topic->tags->pluck('id'));

        if ($topic->hasChildren()) {
            foreach ($topic->children as $child) {
                $this->duplicateTopicRecursive($child, $newTopic);
            }
        }
    }

    public function countDescendants(Topic $topic): int
    {
        return ($topic->_rgt - $topic->_lft - 1) / 2;
    }

    public function getMaxDepth(?int $categoryId = null): int
    {
        $query = Topic::query();

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->max('depth') ?? 0;
    }

    public function isValidMove(Topic $topic, ?int $newParentId): bool
    {
        if ($newParentId === null) {
            return true;
        }

        if ($topic->id === $newParentId) {
            return false;
        }

        $newParent = Topic::find($newParentId);

        if (! $newParent) {
            return false;
        }

        if ($newParent->category_id !== $topic->category_id) {
            return false;
        }

        return ! $topic->isAncestorOf($newParent);
    }

    public function findTopicsByPath(string $path, string $delimiter = '/'): ?Topic
    {
        $segments = explode($delimiter, trim($path, $delimiter));

        $topic = null;
        $parentId = null;

        foreach ($segments as $segment) {
            $topic = Topic::where('slug', $segment)
                ->where('parent_id', $parentId)
                ->first();

            if (! $topic) {
                return null;
            }

            $parentId = $topic->id;
        }

        return $topic;
    }
}
