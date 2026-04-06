<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('users', UserController::class);
    Route::apiResource('questions', QuestionController::class);

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
});
