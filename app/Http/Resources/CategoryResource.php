<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'topics_count' => $this->when(isset($this->topics_count), $this->topics_count),
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
