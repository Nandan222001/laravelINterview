<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserQuestionAttemptCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => UserQuestionAttemptResource::collection($this->collection),
        ];
    }
}
