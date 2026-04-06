<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => 'nullable|string|max:500',
            'search_fields' => 'nullable|array',
            'search_fields.*' => 'string|in:question,code,tags',
            'fuzzy' => 'nullable|boolean',
            'difficulty' => 'nullable|array',
            'difficulty.*' => 'integer|exists:difficulty_levels,id',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'topics' => 'nullable|array',
            'topics.*' => 'integer|exists:topics,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'language' => 'nullable|array',
            'language.*' => 'string|max:50',
            'published' => 'nullable|boolean',
            'verified' => 'nullable|boolean',
            'creator_id' => 'nullable|integer|exists:users,id',
            'min_points' => 'nullable|integer|min:0',
            'max_points' => 'nullable|integer|min:0',
            'has_code_snippets' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:relevance,newest,oldest,views,attempts,success_rate,difficulty,title,points',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'highlight' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'query.max' => 'Search query cannot exceed 500 characters.',
            'search_fields.*.in' => 'Search field must be one of: question, code, tags.',
            'difficulty.*.exists' => 'One or more difficulty levels do not exist.',
            'categories.*.exists' => 'One or more categories do not exist.',
            'topics.*.exists' => 'One or more topics do not exist.',
            'language.*.max' => 'Language name cannot exceed 50 characters.',
            'sort_by.in' => 'Invalid sort option. Must be one of: relevance, newest, oldest, views, attempts, success_rate, difficulty, title, points.',
            'sort_order.in' => 'Sort order must be either asc or desc.',
            'per_page.max' => 'Cannot request more than 100 results per page.',
        ];
    }

    public function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('search_fields') && is_string($this->search_fields)) {
            $data['search_fields'] = explode(',', $this->search_fields);
        }

        if ($this->has('difficulty') && is_string($this->difficulty)) {
            $data['difficulty'] = array_map('intval', explode(',', $this->difficulty));
        }

        if ($this->has('categories') && is_string($this->categories)) {
            $data['categories'] = array_map('intval', explode(',', $this->categories));
        }

        if ($this->has('topics') && is_string($this->topics)) {
            $data['topics'] = array_map('intval', explode(',', $this->topics));
        }

        if ($this->has('tags') && is_string($this->tags)) {
            $data['tags'] = explode(',', $this->tags);
        }

        if ($this->has('language') && is_string($this->language)) {
            $data['language'] = explode(',', $this->language);
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    public function getSearchCriteria(): array
    {
        return array_filter([
            'query' => $this->input('query'),
            'search_fields' => $this->input('search_fields', ['question', 'code', 'tags']),
            'fuzzy' => $this->boolean('fuzzy', false),
            'difficulty' => $this->input('difficulty'),
            'categories' => $this->input('categories'),
            'topics' => $this->input('topics'),
            'tags' => $this->input('tags'),
            'language' => $this->input('language'),
            'published' => $this->has('published') ? $this->boolean('published') : null,
            'verified' => $this->has('verified') ? $this->boolean('verified') : null,
            'creator_id' => $this->input('creator_id'),
            'min_points' => $this->input('min_points'),
            'max_points' => $this->input('max_points'),
            'has_code_snippets' => $this->boolean('has_code_snippets', false),
            'sort_by' => $this->input('sort_by', 'relevance'),
            'sort_order' => $this->input('sort_order', 'desc'),
            'per_page' => $this->input('per_page', 15),
        ], function ($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
    }
}
