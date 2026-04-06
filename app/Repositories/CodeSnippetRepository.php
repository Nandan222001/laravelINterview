<?php

namespace App\Repositories;

use App\Models\CodeSnippet;
use Illuminate\Database\Eloquent\Collection;

class CodeSnippetRepository extends BaseRepository
{
    public function __construct(CodeSnippet $model)
    {
        parent::__construct($model);
    }

    public function getSnippetsByQuestion(int $questionId): Collection
    {
        return $this->model->forQuestion($questionId)->ordered()->get();
    }

    public function getSnippetsByLanguage(string $language): Collection
    {
        return $this->model->byLanguage($language)->ordered()->get();
    }

    public function getSnippetsByType(string $type): Collection
    {
        return $this->model->byType($type)->ordered()->get();
    }

    public function getExecutableSnippets(): Collection
    {
        return $this->model->executable()->ordered()->get();
    }

    public function searchSnippets(string $term): Collection
    {
        return $this->model->search($term)->ordered()->get();
    }

    public function getSnippetsWithTag(string $tag): Collection
    {
        return $this->model->withTag($tag)->ordered()->get();
    }

    public function getSnippetsByQuestionWithRelations(int $questionId): Collection
    {
        return $this->model->forQuestion($questionId)
            ->with(['question', 'tags'])
            ->ordered()
            ->get();
    }
}
