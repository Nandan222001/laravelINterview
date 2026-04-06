<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CodeSnippetController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SyntaxHighlightingController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProgressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('users', UserController::class);
    Route::apiResource('questions', QuestionController::class);
    Route::apiResource('code-snippets', CodeSnippetController::class);

    Route::prefix('code-snippets')->group(function () {
        Route::post('/detect-language', [CodeSnippetController::class, 'detectLanguage']);
        Route::post('/highlight', [CodeSnippetController::class, 'highlightCode']);
        Route::get('/supported-languages', [CodeSnippetController::class, 'supportedLanguages']);
        Route::post('/reorder', [CodeSnippetController::class, 'reorder']);
        Route::post('/bulk-create', [CodeSnippetController::class, 'bulkCreate']);
        Route::post('/bulk-delete', [CodeSnippetController::class, 'bulkDelete']);
        Route::post('/{code_snippet}/duplicate', [CodeSnippetController::class, 'duplicate']);
        Route::get('/executable', [CodeSnippetController::class, 'getExecutable']);
    });

    Route::prefix('syntax-highlighting')->group(function () {
        Route::get('/themes', [SyntaxHighlightingController::class, 'getThemes']);
        Route::get('/client-instructions', [SyntaxHighlightingController::class, 'getClientSideInstructions']);
        Route::get('/rendering-options', [SyntaxHighlightingController::class, 'getRenderingOptions']);
        Route::get('/language-aliases', [SyntaxHighlightingController::class, 'getLanguageAliases']);
    });

    Route::prefix('search')->group(function () {
        Route::post('/', [SearchController::class, 'search']);
        Route::get('/', [SearchController::class, 'search']);
        Route::get('/suggestions', [SearchController::class, 'suggestions']);
        Route::get('/statistics', [SearchController::class, 'statistics']);
        Route::get('/filters', [SearchController::class, 'filters']);
        Route::post('/excerpt', [SearchController::class, 'excerpt']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/tree', [CategoryController::class, 'tree']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::get('/{id}/children', [CategoryController::class, 'children']);
        Route::get('/{id}/breadcrumb', [CategoryController::class, 'breadcrumb']);
    });

    Route::prefix('topics')->group(function () {
        Route::get('/', [TopicController::class, 'index']);
        Route::get('/tree', [TopicController::class, 'tree']);
        Route::get('/{id}', [TopicController::class, 'show']);
        Route::get('/{id}/questions', [TopicController::class, 'questions']);
        Route::get('/{id}/children', [TopicController::class, 'children']);
        Route::get('/{id}/breadcrumb', [TopicController::class, 'breadcrumb']);
        Route::get('/{id}/descendants', [TopicController::class, 'descendants']);
        Route::get('/{id}/ancestors', [TopicController::class, 'ancestors']);
    });

    Route::prefix('user')->group(function () {
        Route::get('/stats', [UserProgressController::class, 'statistics']);
        Route::get('/bookmarked', [UserProgressController::class, 'bookmarkedQuestions']);
        Route::get('/completed', [UserProgressController::class, 'completedQuestions']);
        Route::get('/attempted', [UserProgressController::class, 'attemptedQuestions']);
        Route::get('/recommendations', [UserProgressController::class, 'recommendations']);
        Route::get('/next-questions', [UserProgressController::class, 'nextQuestions']);
        Route::get('/progression-recommendations', [UserProgressController::class, 'progressionRecommendations']);
    });

    Route::prefix('progress')->group(function () {
        Route::post('/attempted', [UserProgressController::class, 'markAttempted']);
        Route::post('/completed', [UserProgressController::class, 'markCompleted']);
        Route::post('/bookmark/{questionId}', [UserProgressController::class, 'bookmark']);
        Route::delete('/bookmark/{questionId}', [UserProgressController::class, 'unbookmark']);
        Route::post('/toggle-bookmark/{questionId}', [UserProgressController::class, 'toggleBookmark']);
    });
});
