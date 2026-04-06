<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyntaxHighlightingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyntaxHighlightingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SyntaxHighlightingService $syntaxHighlightingService
    ) {}

    public function getThemes(): JsonResponse
    {
        $themes = $this->syntaxHighlightingService->getThemeOptions();

        return $this->successResponse(
            ['themes' => $themes],
            'Themes retrieved successfully'
        );
    }

    public function getClientSideInstructions(Request $request): JsonResponse
    {
        $language = $request->input('language');
        $library = $request->input('library', 'highlight.js');

        if ($library === 'prism.js' || $library === 'prism') {
            $instructions = $this->syntaxHighlightingService->getPrismJsInstructions($language);
        } else {
            $instructions = $this->syntaxHighlightingService->getClientSideInstructions($language);
        }

        return $this->successResponse(
            $instructions,
            'Client-side instructions retrieved successfully'
        );
    }

    public function getRenderingOptions(Request $request): JsonResponse
    {
        $language = $request->input('language');

        return $this->successResponse([
            'pre_rendered' => $this->syntaxHighlightingService->getRenderingMetadata(true, $language),
            'client_side' => $this->syntaxHighlightingService->getRenderingMetadata(false, $language),
        ], 'Rendering options retrieved successfully');
    }

    public function getLanguageAliases(Request $request): JsonResponse
    {
        $language = $request->input('language', 'javascript');
        $aliases = $this->syntaxHighlightingService->getLanguageAliases($language);

        return $this->successResponse(
            [
                'language' => $language,
                'aliases' => $aliases,
            ],
            'Language aliases retrieved successfully'
        );
    }
}
