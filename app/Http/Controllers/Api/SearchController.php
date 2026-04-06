<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\SearchResultCollection;
use App\Services\SearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SearchService $searchService
    ) {}

    public function search(SearchRequest $request): JsonResponse
    {
        $criteria = $request->getSearchCriteria();
        $highlight = $request->boolean('highlight', true);

        if ($highlight && isset($criteria['query'])) {
            $searchData = $this->searchService->searchWithHighlights($criteria);

            return (new SearchResultCollection($searchData['results']))
                ->additional([
                    'success' => true,
                    'message' => 'Search completed successfully',
                ])
                ->response()
                ->setStatusCode(200);
        }

        $results = $this->searchService->search($criteria);

        return (new SearchResultCollection($results))
            ->additional([
                'success' => true,
                'message' => 'Search completed successfully',
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'term' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $term = $request->input('term');
        $limit = $request->input('limit', 10);

        $suggestions = $this->searchService->getSuggestions($term, $limit);

        return $this->successResponse(
            $suggestions,
            'Suggestions retrieved successfully'
        );
    }

    public function statistics(SearchRequest $request): JsonResponse
    {
        $criteria = $request->getSearchCriteria();

        $statistics = $this->searchService->getSearchStatistics($criteria);

        return $this->successResponse(
            $statistics,
            'Search statistics retrieved successfully'
        );
    }

    public function filters(): JsonResponse
    {
        $filters = [
            'search_fields' => [
                ['value' => 'question', 'label' => 'Question Text'],
                ['value' => 'code', 'label' => 'Code Snippets'],
                ['value' => 'tags', 'label' => 'Tags'],
            ],
            'sort_options' => [
                ['value' => 'relevance', 'label' => 'Relevance'],
                ['value' => 'newest', 'label' => 'Newest First'],
                ['value' => 'oldest', 'label' => 'Oldest First'],
                ['value' => 'views', 'label' => 'Most Viewed'],
                ['value' => 'attempts', 'label' => 'Most Attempted'],
                ['value' => 'success_rate', 'label' => 'Success Rate'],
                ['value' => 'difficulty', 'label' => 'Difficulty Level'],
                ['value' => 'title', 'label' => 'Title (A-Z)'],
                ['value' => 'points', 'label' => 'Points'],
            ],
            'sort_directions' => [
                ['value' => 'asc', 'label' => 'Ascending'],
                ['value' => 'desc', 'label' => 'Descending'],
            ],
        ];

        return $this->successResponse(
            $filters,
            'Available filters retrieved successfully'
        );
    }

    public function excerpt(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
            'term' => 'required|string',
            'context_length' => 'nullable|integer|min:50|max:500',
        ]);

        $text = $request->input('text');
        $term = $request->input('term');
        $contextLength = $request->input('context_length', 150);

        $excerpts = $this->searchService->extractHighlightedExcerpts($text, $term, $contextLength);

        return $this->successResponse(
            $excerpts,
            'Excerpts extracted successfully'
        );
    }
}
