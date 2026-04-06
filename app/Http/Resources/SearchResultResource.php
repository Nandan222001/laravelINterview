<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SearchResultResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'question_text' => $this->question_text,
            'explanation' => $this->explanation,
            'points' => $this->points,
            'time_limit' => $this->time_limit,
            'is_published' => $this->is_published,
            'is_verified' => $this->is_verified,
            'view_count' => $this->view_count,
            'attempt_count' => $this->attempt_count,
            'success_count' => $this->success_count,
            'success_rate' => $this->getSuccessRate(),
            'difficulty_level' => new DifficultyLevelResource($this->whenLoaded('difficultyLevel')),
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];

        if (isset($this->title_highlighted)) {
            $data['title_highlighted'] = $this->title_highlighted;
        }

        if (isset($this->question_text_highlighted)) {
            $data['question_text_highlighted'] = $this->question_text_highlighted;
        }

        if (isset($this->explanation_highlighted)) {
            $data['explanation_highlighted'] = $this->explanation_highlighted;
        }

        if ($this->relationLoaded('codeSnippets')) {
            $data['code_snippets'] = SearchCodeSnippetResource::collection($this->codeSnippets);
            $data['has_code_snippets'] = $this->codeSnippets->isNotEmpty();
        }

        if (isset($this->relevance_score)) {
            $data['relevance_score'] = $this->relevance_score;
        }

        return $data;
    }
}
