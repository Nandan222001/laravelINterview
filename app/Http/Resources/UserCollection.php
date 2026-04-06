<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserCollection extends BaseCollection
{
    public $collects = UserResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
