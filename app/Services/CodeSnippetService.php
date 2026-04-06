<?php

namespace App\Services;

use App\Models\CodeSnippet;
use App\Repositories\CodeSnippetRepository;
use Highlight\Highlighter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CodeSnippetService extends BaseService
{
    private Highlighter $highlighter;

    public function __construct(CodeSnippetRepository $repository)
    {
        parent::__construct($repository);
        $this->highlighter = new Highlighter;
    }

    public function getSnippetsByQuestion(int $questionId): Collection
    {
        return $this->repository->getSnippetsByQuestion($questionId);
    }

    public function getSnippetsWithHighlighting(int $questionId, bool $renderHtml = true): Collection
    {
        $snippets = $this->repository->getSnippetsByQuestion($questionId);

        if ($renderHtml) {
            $snippets->transform(function ($snippet) {
                return $this->addHighlighting($snippet);
            });
        }

        return $snippets;
    }

    public function createSnippet(array $data): Model
    {
        if (empty($data['language'])) {
            $data['language'] = $this->detectLanguage($data['code']);
        }

        if (! isset($data['order'])) {
            $data['order'] = $this->getNextOrder($data['question_id']);
        }

        return $this->repository->create($data);
    }

    public function createSnippetWithHighlighting(array $data, bool $renderHtml = true): Model
    {
        $snippet = $this->createSnippet($data);

        if ($renderHtml) {
            $snippet = $this->addHighlighting($snippet);
        }

        return $snippet;
    }

    public function updateSnippet(int|string $id, array $data): bool
    {
        if (isset($data['code']) && empty($data['language'])) {
            $data['language'] = $this->detectLanguage($data['code']);
        }

        return $this->repository->update($id, $data);
    }

    public function deleteSnippet(int|string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function reorderSnippets(int $questionId, array $snippetOrders): bool
    {
        foreach ($snippetOrders as $order => $snippetId) {
            $this->repository->update($snippetId, ['order' => $order]);
        }

        return true;
    }

    public function detectLanguage(string $code): string
    {
        try {
            $result = $this->highlighter->highlightAuto($code);

            return $result->language;
        } catch (\Exception $e) {
            return 'plaintext';
        }
    }

    public function highlightCode(string $code, ?string $language = null): array
    {
        try {
            if ($language && $language !== 'auto' && $language !== 'plaintext') {
                $result = $this->highlighter->highlight($language, $code);
            } else {
                $result = $this->highlighter->highlightAuto($code);
            }

            return [
                'highlighted' => $result->value,
                'language' => $result->language,
                'relevance' => $result->relevance ?? 0,
                'second_best' => $result->secondBest->language ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'highlighted' => htmlspecialchars($code),
                'language' => $language ?? 'plaintext',
                'relevance' => 0,
                'second_best' => null,
            ];
        }
    }

    public function getSupportedLanguages(): array
    {
        return $this->highlighter->listLanguages();
    }

    public function getSnippetWithHighlighting(int|string $id, bool $renderHtml = true): ?Model
    {
        $snippet = $this->repository->find($id);

        if (! $snippet) {
            return null;
        }

        if ($renderHtml) {
            $snippet = $this->addHighlighting($snippet);
        }

        return $snippet;
    }

    public function searchSnippets(string $term, ?string $language = null, ?string $type = null): Collection
    {
        $snippets = $this->repository->searchSnippets($term);

        if ($language) {
            $snippets = $snippets->where('language', $language);
        }

        if ($type) {
            $snippets = $snippets->where('type', $type);
        }

        return $snippets;
    }

    public function getSnippetsByLanguage(string $language): Collection
    {
        return $this->repository->getSnippetsByLanguage($language);
    }

    public function getSnippetsByType(string $type): Collection
    {
        return $this->repository->getSnippetsByType($type);
    }

    public function getExecutableSnippets(): Collection
    {
        return $this->repository->getExecutableSnippets();
    }

    public function bulkCreateSnippets(int $questionId, array $snippets): Collection
    {
        $createdSnippets = collect();

        foreach ($snippets as $index => $snippetData) {
            $snippetData['question_id'] = $questionId;
            $snippetData['order'] = $snippetData['order'] ?? $index;

            $createdSnippets->push($this->createSnippet($snippetData));
        }

        return $createdSnippets;
    }

    public function bulkDeleteSnippets(array $snippetIds): bool
    {
        foreach ($snippetIds as $snippetId) {
            $this->repository->delete($snippetId);
        }

        return true;
    }

    public function duplicateSnippet(int|string $id, ?int $questionId = null): ?Model
    {
        $snippet = $this->repository->find($id);

        if (! $snippet) {
            return null;
        }

        $data = $snippet->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);

        if ($questionId) {
            $data['question_id'] = $questionId;
        }

        $data['order'] = $this->getNextOrder($data['question_id']);

        return $this->repository->create($data);
    }

    private function addHighlighting(CodeSnippet $snippet): CodeSnippet
    {
        $highlightResult = $this->highlightCode($snippet->code, $snippet->language);

        $snippet->highlighted_code = $highlightResult['highlighted'];
        $snippet->detected_language = $highlightResult['language'];
        $snippet->relevance = $highlightResult['relevance'];
        $snippet->second_best_language = $highlightResult['second_best'];

        return $snippet;
    }

    private function getNextOrder(int $questionId): int
    {
        $maxOrder = $this->repository->getSnippetsByQuestion($questionId)->max('order');

        return ($maxOrder ?? -1) + 1;
    }
}
