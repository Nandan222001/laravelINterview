<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Taggable
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function scopeWithTag(Builder $query, string|int $tag): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tag) {
            if (is_numeric($tag)) {
                $q->where('tags.id', $tag);
            } else {
                $q->where('tags.slug', $tag);
            }
        });
    }

    public function scopeWithTags(Builder $query, array $tags): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tags) {
            $q->whereIn('tags.slug', $tags);
        }, '=', count($tags));
    }

    public function scopeWithAnyTag(Builder $query, array $tags): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tags) {
            $q->whereIn('tags.slug', $tags);
        });
    }

    public function attachTag(string|int|Tag $tag): void
    {
        if ($tag instanceof Tag) {
            $tagModel = $tag;
        } elseif (is_numeric($tag)) {
            $tagModel = Tag::find($tag);
        } else {
            $tagModel = Tag::firstOrCreate(
                ['slug' => $tag],
                ['name' => ucwords(str_replace('-', ' ', $tag))]
            );
        }

        if ($tagModel && ! $this->tags()->where('tags.id', $tagModel->id)->exists()) {
            $this->tags()->attach($tagModel->id);
            $tagModel->incrementUsage();
        }
    }

    public function detachTag(string|int|Tag $tag): void
    {
        if ($tag instanceof Tag) {
            $tagId = $tag->id;
        } elseif (is_numeric($tag)) {
            $tagId = $tag;
        } else {
            $tagModel = Tag::where('slug', $tag)->first();
            $tagId = $tagModel?->id;
        }

        if ($tagId && $this->tags()->where('tags.id', $tagId)->exists()) {
            $this->tags()->detach($tagId);
            Tag::find($tagId)?->decrementUsage();
        }
    }

    public function syncTags(array $tags): void
    {
        $tagIds = collect($tags)->map(function ($tag) {
            if ($tag instanceof Tag) {
                return $tag->id;
            }
            if (is_numeric($tag)) {
                return $tag;
            }

            return Tag::firstOrCreate(
                ['slug' => $tag],
                ['name' => ucwords(str_replace('-', ' ', $tag))]
            )->id;
        })->toArray();

        $this->tags()->sync($tagIds);
    }

    public function hasTag(string|int|Tag $tag): bool
    {
        if ($tag instanceof Tag) {
            $tagId = $tag->id;
        } elseif (is_numeric($tag)) {
            $tagId = $tag;
        } else {
            $tagModel = Tag::where('slug', $tag)->first();
            $tagId = $tagModel?->id;
        }

        return $tagId && $this->tags()->where('tags.id', $tagId)->exists();
    }

    public function getTagNames(): array
    {
        return $this->tags->pluck('name')->toArray();
    }

    public function getTagSlugs(): array
    {
        return $this->tags->pluck('slug')->toArray();
    }
}
