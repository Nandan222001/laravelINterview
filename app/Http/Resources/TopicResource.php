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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'order' => $this->whenPivotLoaded('question_topic', function () {
                return $this->pivot->order;
            }),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
