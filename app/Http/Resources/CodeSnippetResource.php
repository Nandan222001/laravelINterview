<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CodeSnippetResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'title' => $this->title,
            'description' => $this->description,
            'code' => $this->code,
            'language' => $this->language,
            'type' => $this->type,
            'order' => $this->order,
            'is_executable' => $this->is_executable,
            'expected_output' => $this->expected_output,
            'metadata' => $this->metadata,
        ];
    }
}
