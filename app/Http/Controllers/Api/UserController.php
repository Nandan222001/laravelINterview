<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserService $userService
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->getPaginated(15);

        return $this->successResponse(new UserCollection($users), 'Users retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getById($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse(new UserResource($user), 'User retrieved successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
        ]);

        $updated = $this->userService->update($id, $validated);

        if (! $updated) {
            return $this->serverErrorResponse('Failed to update user');
        }

        $user = $this->userService->getById($id);

        return $this->successResponse(new UserResource($user), 'User updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        if (! $this->userService->exists($id)) {
            return $this->notFoundResponse('User not found');
        }

        $this->userService->delete($id);

        return $this->successResponse(null, 'User deleted successfully');
    }
}
