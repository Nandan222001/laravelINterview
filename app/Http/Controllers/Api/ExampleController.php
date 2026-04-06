<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    use ApiResponse;

    public function successExample(): JsonResponse
    {
        $data = [
            'id' => 1,
            'title' => 'Example Item',
            'description' => 'This is an example of a success response',
        ];

        return $this->successResponse($data, 'Data retrieved successfully');
    }

    public function createdExample(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data = array_merge(['id' => 1], $validated);

        return $this->createdResponse($data, 'Item created successfully');
    }

    public function validationErrorExample(): JsonResponse
    {
        $errors = [
            'title' => ['The title field is required.'],
            'email' => ['The email must be a valid email address.'],
        ];

        return $this->validationErrorResponse($errors);
    }

    public function notFoundExample(): JsonResponse
    {
        return $this->notFoundResponse('The requested item was not found');
    }

    public function unauthorizedExample(): JsonResponse
    {
        return $this->unauthorizedResponse('You are not authorized to access this resource');
    }

    public function forbiddenExample(): JsonResponse
    {
        return $this->forbiddenResponse('You do not have permission to perform this action');
    }

    public function serverErrorExample(): JsonResponse
    {
        return $this->serverErrorResponse('An unexpected error occurred');
    }

    public function paginatedExample(): JsonResponse
    {
        $items = collect([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ]);

        return $this->successResponse($items, 'Items retrieved successfully');
    }
}
