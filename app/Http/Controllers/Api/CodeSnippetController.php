<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeSnippet\BulkCreateCodeSnippetsRequest;
use App\Http\Requests\CodeSnippet\DetectLanguageRequest;
use App\Http\Requests\CodeSnippet\HighlightCodeRequest;
use App\Http\Requests\CodeSnippet\ReorderCodeSnippetsRequest;
use App\Http\Requests\CodeSnippet\StoreCodeSnippetRequest;
use App\Http\Requests\CodeSnippet\UpdateCodeSnippetRequest;
use App\Http\Resources\CodeSnippetCollection;
use App\Http\Resources\CodeSnippetResource;
use App\Models\CodeSnippet;
use App\Services\CodeSnippetService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CodeSnippetController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CodeSnippetService $codeSnippetService
    ) {
        $this->authorizeResource(CodeSnippet::class, 'code_snippet');
    }

    public function index(Request $request): JsonResponse
    {
        $questionId = $request->input('question_id');
        $language = $request->input('language');
        $type = $request->input('type');
        $search = $request->input('search');
        $renderHtml = $request->boolean('render_html', true);

        if ($questionId) {
            $snippets = $this->codeSnippetService->getSnippetsWithHighlighting(
                $questionId,
                $renderHtml
            );
        } elseif ($search) {
            $snippets = $this->codeSnippetService->searchSnippets($search, $language, $type);
        } elseif ($language) {
            $snippets = $this->codeSnippetService->getSnippetsByLanguage($language);
        } elseif ($type) {
            $snippets = $this->codeSnippetService->getSnippetsByType($type);
        } else {
            $snippets = $this->codeSnippetService->getAll();
        }

        return $this->successResponse(
            new CodeSnippetCollection($snippets),
            'Code snippets retrieved successfully'
        );
    }

    public function show(Request $request, CodeSnippet $codeSnippet): JsonResponse
    {
        $renderHtml = $request->boolean('render_html', true);

        $snippet = $this->codeSnippetService->getSnippetWithHighlighting(
            $codeSnippet->id,
            $renderHtml
        );

        if (! $snippet) {
            return $this->notFoundResponse('Code snippet not found');
        }

        return $this->successResponse(
            new CodeSnippetResource($snippet),
            'Code snippet retrieved successfully'
        );
    }

    public function store(StoreCodeSnippetRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $renderHtml = $request->boolean('render_html', true);

        $snippet = $this->codeSnippetService->createSnippetWithHighlighting(
            $validated,
            $renderHtml
        );

        return $this->createdResponse(
            new CodeSnippetResource($snippet),
            'Code snippet created successfully'
        );
    }

    public function update(UpdateCodeSnippetRequest $request, CodeSnippet $codeSnippet): JsonResponse
    {
        $validated = $request->validated();

        $updated = $this->codeSnippetService->updateSnippet($codeSnippet->id, $validated);

        if (! $updated) {
            return $this->serverErrorResponse('Failed to update code snippet');
        }

        $renderHtml = $request->boolean('render_html', true);

        $snippet = $this->codeSnippetService->getSnippetWithHighlighting(
            $codeSnippet->id,
            $renderHtml
        );

        return $this->successResponse(
            new CodeSnippetResource($snippet),
            'Code snippet updated successfully'
        );
    }

    public function destroy(CodeSnippet $codeSnippet): JsonResponse
    {
        $this->codeSnippetService->deleteSnippet($codeSnippet->id);

        return $this->successResponse(null, 'Code snippet deleted successfully');
    }

    public function detectLanguage(DetectLanguageRequest $request): JsonResponse
    {
        $code = $request->input('code');

        $language = $this->codeSnippetService->detectLanguage($code);

        return $this->successResponse(
            ['language' => $language],
            'Language detected successfully'
        );
    }

    public function highlightCode(HighlightCodeRequest $request): JsonResponse
    {
        $code = $request->input('code');
        $language = $request->input('language');

        $result = $this->codeSnippetService->highlightCode($code, $language);

        return $this->successResponse(
            $result,
            'Code highlighted successfully'
        );
    }

    public function supportedLanguages(): JsonResponse
    {
        $languages = $this->codeSnippetService->getSupportedLanguages();

        return $this->successResponse(
            ['languages' => $languages],
            'Supported languages retrieved successfully'
        );
    }

    public function reorder(ReorderCodeSnippetsRequest $request): JsonResponse
    {
        $questionId = $request->input('question_id');
        $snippetOrders = $request->input('snippet_orders');

        $this->codeSnippetService->reorderSnippets($questionId, $snippetOrders);

        $snippets = $this->codeSnippetService->getSnippetsByQuestion($questionId);

        return $this->successResponse(
            new CodeSnippetCollection($snippets),
            'Code snippets reordered successfully'
        );
    }

    public function bulkCreate(BulkCreateCodeSnippetsRequest $request): JsonResponse
    {
        $questionId = $request->input('question_id');
        $snippets = $request->input('snippets');

        $createdSnippets = $this->codeSnippetService->bulkCreateSnippets($questionId, $snippets);

        return $this->createdResponse(
            new CodeSnippetCollection($createdSnippets),
            'Code snippets created successfully'
        );
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $snippetIds = $request->input('snippet_ids', []);

        $this->codeSnippetService->bulkDeleteSnippets($snippetIds);

        return $this->successResponse(null, 'Code snippets deleted successfully');
    }

    public function duplicate(CodeSnippet $codeSnippet, Request $request): JsonResponse
    {
        $questionId = $request->input('question_id');

        $duplicatedSnippet = $this->codeSnippetService->duplicateSnippet(
            $codeSnippet->id,
            $questionId
        );

        if (! $duplicatedSnippet) {
            return $this->notFoundResponse('Code snippet not found');
        }

        return $this->createdResponse(
            new CodeSnippetResource($duplicatedSnippet),
            'Code snippet duplicated successfully'
        );
    }

    public function getExecutable(): JsonResponse
    {
        $snippets = $this->codeSnippetService->getExecutableSnippets();

        return $this->successResponse(
            new CodeSnippetCollection($snippets),
            'Executable code snippets retrieved successfully'
        );
    }
}
