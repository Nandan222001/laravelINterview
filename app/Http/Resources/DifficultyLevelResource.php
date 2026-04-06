<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DifficultyLevelResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'level' => $this->level,
            'color' => $this->color,
            'description' => $this->description,
        ];
    }
}
