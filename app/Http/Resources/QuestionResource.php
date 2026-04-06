<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class QuestionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'question_text' => $this->question_text,
            'explanation' => $this->explanation,
            'options' => $this->options,
            'correct_answer' => $this->when($request->user()?->id === $this->created_by, $this->correct_answer),
            'hints' => $this->hints,
            'points' => $this->points,
            'time_limit' => $this->time_limit,
            'is_published' => $this->is_published,
            'is_verified' => $this->is_verified,
            'view_count' => $this->view_count,
            'attempt_count' => $this->attempt_count,
            'success_count' => $this->success_count,
            'success_rate' => $this->getSuccessRate(),
            'metadata' => $this->metadata,
            'difficulty_level' => new DifficultyLevelResource($this->whenLoaded('difficultyLevel')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'code_snippets' => CodeSnippetResource::collection($this->whenLoaded('codeSnippets')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
