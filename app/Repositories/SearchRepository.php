<?php

namespace App\Repositories;

use App\Models\Question;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchRepository
{
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        $query = $this->buildAdvancedSearchQuery($criteria);

        $perPage = $criteria['per_page'] ?? 15;

        return $query->with([
            'difficultyLevel',
            'topics.category',
            'tags',
            'creator',
            'codeSnippets' => function ($query) {
                $query->orderBy('order');
            },
        ])->paginate($perPage);
    }

    protected function buildAdvancedSearchQuery(array $criteria): Builder
    {
        $query = Question::query();

        if (isset($criteria['query']) && ! empty($criteria['query'])) {
            $searchTerm = $criteria['query'];
            $fuzzy = $criteria['fuzzy'] ?? false;

            $query->where(function (Builder $q) use ($searchTerm, $fuzzy, $criteria) {
                if ($fuzzy) {
                    $this->applyFuzzySearch($q, $searchTerm, $criteria);
                } else {
                    $this->applyExactSearch($q, $searchTerm, $criteria);
                }
            });
        }

        if (isset($criteria['difficulty']) && ! empty($criteria['difficulty'])) {
            if (is_array($criteria['difficulty'])) {
                $query->whereIn('difficulty_level_id', $criteria['difficulty']);
            } else {
                $query->where('difficulty_level_id', $criteria['difficulty']);
            }
        }

        if (isset($criteria['categories']) && ! empty($criteria['categories'])) {
            $categories = is_array($criteria['categories']) ? $criteria['categories'] : [$criteria['categories']];
            $query->whereHas('topics.category', function (Builder $q) use ($categories) {
                $q->whereIn('categories.id', $categories);
            });
        }

        if (isset($criteria['topics']) && ! empty($criteria['topics'])) {
            $topics = is_array($criteria['topics']) ? $criteria['topics'] : [$criteria['topics']];
            $query->whereHas('topics', function (Builder $q) use ($topics) {
                $q->whereIn('topics.id', $topics);
            });
        }

        if (isset($criteria['tags']) && ! empty($criteria['tags'])) {
            $tags = is_array($criteria['tags']) ? $criteria['tags'] : [$criteria['tags']];
            $query->whereHas('tags', function (Builder $q) use ($tags) {
                $q->whereIn('tags.id', $tags)
                    ->orWhereIn('tags.slug', $tags);
            });
        }

        if (isset($criteria['published'])) {
            $criteria['published'] ? $query->published() : $query->unpublished();
        }

        if (isset($criteria['verified'])) {
            $criteria['verified'] ? $query->verified() : $query->unverified();
        }

        if (isset($criteria['creator_id'])) {
            $query->byCreator($criteria['creator_id']);
        }

        if (isset($criteria['min_points'])) {
            $query->where('points', '>=', $criteria['min_points']);
        }

        if (isset($criteria['max_points'])) {
            $query->where('points', '<=', $criteria['max_points']);
        }

        if (isset($criteria['has_code_snippets']) && $criteria['has_code_snippets']) {
            $query->has('codeSnippets');
        }

        if (isset($criteria['language'])) {
            $languages = is_array($criteria['language']) ? $criteria['language'] : [$criteria['language']];
            $query->whereHas('codeSnippets', function (Builder $q) use ($languages) {
                $q->whereIn('language', $languages);
            });
        }

        $this->applySorting($query, $criteria);

        return $query;
    }

    protected function applyExactSearch(Builder $query, string $term, array $criteria): void
    {
        $searchFields = $criteria['search_fields'] ?? ['question', 'code', 'tags'];

        if (in_array('question', $searchFields)) {
            $query->orWhereFullText(['title', 'question_text'], $term);
        }

        if (in_array('code', $searchFields)) {
            $query->orWhereHas('codeSnippets', function (Builder $q) use ($term) {
                $q->whereFullText(['title', 'description', 'code'], $term);
            });
        }

        if (in_array('tags', $searchFields)) {
            $query->orWhereHas('tags', function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }
    }

    protected function applyFuzzySearch(Builder $query, string $term, array $criteria): void
    {
        $searchFields = $criteria['search_fields'] ?? ['question', 'code', 'tags'];

        if (in_array('question', $searchFields)) {
            $query->orWhere('title', 'like', "%{$term}%")
                ->orWhere('question_text', 'like', "%{$term}%")
                ->orWhere('explanation', 'like', "%{$term}%");
        }

        if (in_array('code', $searchFields)) {
            $query->orWhereHas('codeSnippets', function (Builder $q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        if (in_array('tags', $searchFields)) {
            $query->orWhereHas('tags', function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }
    }

    protected function applySorting(Builder $query, array $criteria): void
    {
        $sortBy = $criteria['sort_by'] ?? 'relevance';
        $sortOrder = $criteria['sort_order'] ?? 'desc';

        match ($sortBy) {
            'relevance' => $this->sortByRelevance($query, $criteria),
            'newest' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'views' => $query->orderBy('view_count', $sortOrder),
            'attempts' => $query->orderBy('attempt_count', $sortOrder),
            'success_rate' => $query->orderByRaw('(success_count / NULLIF(attempt_count, 0)) '.$sortOrder),
            'difficulty' => $query->join('difficulty_levels', 'questions.difficulty_level_id', '=', 'difficulty_levels.id')
                ->orderBy('difficulty_levels.level', $sortOrder)
                ->select('questions.*'),
            'title' => $query->orderBy('title', $sortOrder),
            'points' => $query->orderBy('points', $sortOrder),
            default => $query->latest(),
        };
    }

    protected function sortByRelevance(Builder $query, array $criteria): void
    {
        if (isset($criteria['query']) && ! empty($criteria['query'])) {
            $term = $criteria['query'];

            $query->selectRaw('
                questions.*,
                (
                    CASE 
                        WHEN title LIKE ? THEN 10
                        WHEN title LIKE ? THEN 5
                        ELSE 0
                    END +
                    CASE 
                        WHEN question_text LIKE ? THEN 8
                        WHEN question_text LIKE ? THEN 3
                        ELSE 0
                    END
                ) as relevance_score
            ', [
                "%{$term}%",
                "%{$term}%",
                "{$term}%",
                "%{$term}%",
            ])->orderByDesc('relevance_score');
        } else {
            $query->latest();
        }
    }

    public function getSearchSuggestions(string $term, int $limit = 10): array
    {
        $questions = Question::where('title', 'like', "%{$term}%")
            ->orWhere('question_text', 'like', "%{$term}%")
            ->published()
            ->verified()
            ->limit($limit)
            ->get(['id', 'title'])
            ->map(fn ($q) => [
                'type' => 'question',
                'id' => $q->id,
                'text' => $q->title,
            ]);

        $tags = Tag::where('name', 'like', "%{$term}%")
            ->orWhere('slug', 'like', "%{$term}%")
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($t) => [
                'type' => 'tag',
                'id' => $t->id,
                'text' => $t->name,
                'slug' => $t->slug,
            ]);

        return [
            'questions' => $questions->toArray(),
            'tags' => $tags->toArray(),
        ];
    }

    public function highlightSearchTerms(string $text, string $term, int $contextLength = 150): array
    {
        if (empty($term)) {
            return [
                'text' => $text,
                'highlighted' => $text,
                'excerpts' => [],
            ];
        }

        $pattern = '/('.preg_quote($term, '/').')/i';
        $highlighted = preg_replace($pattern, '<mark>$1</mark>', $text);

        $excerpts = [];
        $matches = [];

        if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $position = $match[1];
                $start = max(0, $position - $contextLength);
                $length = $contextLength * 2 + strlen($match[0]);

                $excerpt = substr($text, $start, $length);
                $excerpt = ($start > 0 ? '...' : '').$excerpt;
                $excerpt = (strlen($text) > $start + $length ? $excerpt.'...' : $excerpt);

                $excerpts[] = preg_replace($pattern, '<mark>$1</mark>', $excerpt);
            }
        }

        return [
            'text' => $text,
            'highlighted' => $highlighted,
            'excerpts' => array_unique($excerpts),
        ];
    }

    public function getSearchStatistics(array $criteria): array
    {
        $query = $this->buildAdvancedSearchQuery($criteria);

        $stats = [
            'total_results' => $query->count(),
            'difficulty_distribution' => [],
            'category_distribution' => [],
            'tag_distribution' => [],
        ];

        $difficultyStats = (clone $query)
            ->join('difficulty_levels', 'questions.difficulty_level_id', '=', 'difficulty_levels.id')
            ->select('difficulty_levels.id', 'difficulty_levels.name', DB::raw('count(*) as count'))
            ->groupBy('difficulty_levels.id', 'difficulty_levels.name')
            ->get();

        $stats['difficulty_distribution'] = $difficultyStats->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'count' => $item->count,
        ])->toArray();

        return $stats;
    }
}
