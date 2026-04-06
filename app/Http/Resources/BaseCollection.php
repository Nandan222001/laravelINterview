<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'total' => $this->collection->count(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    public function withResponse($request, $response): void
    {
        $jsonResponse = json_decode($response->getContent(), true);

        if (isset($jsonResponse['meta']['total'])) {
            unset($jsonResponse['meta']['total']);
        }

        if (isset($jsonResponse['links'])) {
            $jsonResponse['meta']['pagination'] = $jsonResponse['links'];
            unset($jsonResponse['links']);
        }

        $response->setContent(json_encode($jsonResponse));
    }
}
