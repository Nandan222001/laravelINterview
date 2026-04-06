<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SearchCodeSnippetResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'code' => $this->code,
            'language' => $this->language,
            'type' => $this->type,
            'order' => $this->order,
            'is_executable' => $this->is_executable,
        ];

        if (isset($this->title_highlighted)) {
            $data['title_highlighted'] = $this->title_highlighted;
        }

        if (isset($this->description_highlighted)) {
            $data['description_highlighted'] = $this->description_highlighted;
        }

        if (isset($this->code_highlighted)) {
            $data['code_highlighted'] = $this->code_highlighted;
        }

        return $data;
    }
}
