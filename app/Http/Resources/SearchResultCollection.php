<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SearchResultCollection extends ResourceCollection
{
    public $collects = SearchResultResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        $with = [];

        if (! isset($this->additional['success'])) {
            $with['success'] = true;
        }

        if (! isset($this->additional['message'])) {
            $with['message'] = 'Search completed successfully';
        }

        return $with;
    }

    public function paginationInformation($request, $paginated, $default): array
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'from' => $paginated['from'],
                'last_page' => $paginated['last_page'],
                'per_page' => $paginated['per_page'],
                'to' => $paginated['to'],
                'total' => $paginated['total'],
                'timestamp' => now()->toIso8601String(),
            ],
            'links' => [
                'first' => $paginated['first_page_url'],
                'last' => $paginated['last_page_url'],
                'prev' => $paginated['prev_page_url'],
                'next' => $paginated['next_page_url'],
            ],
        ];
    }
}
