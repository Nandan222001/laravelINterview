<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CodeSnippetCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => CodeSnippetResource::collection($this->collection),
        ];
    }

    public function with(Request $request): array
    {
        return array_merge(parent::with($request), [
            'meta' => [
                'total' => $this->collection->count(),
                'timestamp' => now()->toIso8601String(),
                'syntax_highlighting' => [
                    'library' => 'highlight.js',
                    'version' => '11.9.0',
                    'supported_languages' => 'Use /api/code-snippets/supported-languages endpoint',
                ],
            ],
        ]);
    }
}
