<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TagResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'usage_count' => $this->usage_count,
        ];
    }
}
