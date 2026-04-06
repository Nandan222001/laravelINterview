<?php

namespace App\Services;

use App\Repositories\SearchRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(
        protected SearchRepository $searchRepository
    ) {}

    public function search(array $criteria): LengthAwarePaginator
    {
        $this->normalizeSearchCriteria($criteria);

        return $this->searchRepository->advancedSearch($criteria);
    }

    public function searchWithHighlights(array $criteria): array
    {
        $results = $this->search($criteria);
        $searchTerm = $criteria['query'] ?? '';

        if (! empty($searchTerm)) {
            $results->getCollection()->transform(function ($question) use ($searchTerm) {
                $question->title_highlighted = $this->highlightText($question->title, $searchTerm);
                $question->question_text_highlighted = $this->highlightText($question->question_text, $searchTerm);

                if ($question->relationLoaded('codeSnippets')) {
                    $question->codeSnippets->each(function ($snippet) use ($searchTerm) {
                        $snippet->code_highlighted = $this->highlightText($snippet->code, $searchTerm);
                        $snippet->description_highlighted = $this->highlightText($snippet->description ?? '', $searchTerm);
                    });
                }

                return $question;
            });
        }

        return [
            'results' => $results,
            'search_term' => $searchTerm,
            'total' => $results->total(),
            'per_page' => $results->perPage(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
        ];
    }

    public function getSuggestions(string $term, int $limit = 10): array
    {
        if (strlen($term) < 2) {
            return [
                'questions' => [],
                'tags' => [],
            ];
        }

        return $this->searchRepository->getSearchSuggestions($term, $limit);
    }

    public function getSearchStatistics(array $criteria): array
    {
        $this->normalizeSearchCriteria($criteria);

        return $this->searchRepository->getSearchStatistics($criteria);
    }

    protected function normalizeSearchCriteria(array &$criteria): void
    {
        if (isset($criteria['difficulty']) && is_string($criteria['difficulty'])) {
            $criteria['difficulty'] = explode(',', $criteria['difficulty']);
        }

        if (isset($criteria['categories']) && is_string($criteria['categories'])) {
            $criteria['categories'] = explode(',', $criteria['categories']);
        }

        if (isset($criteria['topics']) && is_string($criteria['topics'])) {
            $criteria['topics'] = explode(',', $criteria['topics']);
        }

        if (isset($criteria['tags']) && is_string($criteria['tags'])) {
            $criteria['tags'] = explode(',', $criteria['tags']);
        }

        if (isset($criteria['language']) && is_string($criteria['language'])) {
            $criteria['language'] = explode(',', $criteria['language']);
        }

        if (isset($criteria['search_fields']) && is_string($criteria['search_fields'])) {
            $criteria['search_fields'] = explode(',', $criteria['search_fields']);
        }

        if (isset($criteria['fuzzy'])) {
            $criteria['fuzzy'] = filter_var($criteria['fuzzy'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($criteria['published'])) {
            $criteria['published'] = filter_var($criteria['published'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($criteria['verified'])) {
            $criteria['verified'] = filter_var($criteria['verified'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($criteria['has_code_snippets'])) {
            $criteria['has_code_snippets'] = filter_var($criteria['has_code_snippets'], FILTER_VALIDATE_BOOLEAN);
        }
    }

    protected function highlightText(string $text, string $term): string
    {
        if (empty($term) || empty($text)) {
            return $text;
        }

        $pattern = '/('.preg_quote($term, '/').')/i';

        return preg_replace($pattern, '<mark>$1</mark>', $text);
    }

    public function extractHighlightedExcerpts(string $text, string $term, int $contextLength = 150): array
    {
        return $this->searchRepository->highlightSearchTerms($text, $term, $contextLength);
    }
}
