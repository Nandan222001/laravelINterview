<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TopicResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'learning_objectives' => $this->learning_objectives,
            'order' => $this->whenPivotLoaded('question_topic', function () {
                return $this->pivot->order;
            }),
            'is_active' => $this->is_active,
            'depth' => $this->depth,
            'metadata' => $this->metadata,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'parent' => new TopicResource($this->whenLoaded('parent')),
            'children' => TopicResource::collection($this->whenLoaded('children')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'questions_count' => $this->when(isset($this->questions_count), $this->questions_count),
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
