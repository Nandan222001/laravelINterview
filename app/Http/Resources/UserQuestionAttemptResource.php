<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserQuestionAttemptResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'question_id' => $this->question_id,
            'status' => $this->status,
            'is_bookmarked' => $this->is_bookmarked,
            'is_correct' => $this->is_correct,
            'attempts_count' => $this->attempts_count,
            'time_spent' => $this->time_spent,
            'user_answer' => $this->user_answer,
            'first_attempted_at' => $this->first_attempted_at?->toIso8601String(),
            'last_attempted_at' => $this->last_attempted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'question' => new QuestionResource($this->whenLoaded('question')),
        ];
    }
}
