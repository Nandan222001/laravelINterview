<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Sanctum authentication controller with rate limiting and token abilities.
 */
class SanctumAuthController extends Controller
{
    /**
     * Register new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create token with abilities
        $token = $user->createToken(
            name: 'api-token',
            abilities: ['user:read', 'user:write', 'payment:read']
        );

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ], 201);
    }

    /**
     * Login user with rate limiting.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $key = 'login:'.$request->ip();

        // Rate limiting: 5 attempts per minute
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        // Revoke old tokens if requested
        if ($request->revoke_old_tokens) {
            $user->tokens()->delete();
        }

        // Create token with custom abilities based on user role
        $abilities = $this->getTokenAbilities($user);

        $token = $user->createToken(
            name: $request->device_name ?? 'api-token',
            abilities: $abilities,
            expiresAt: now()->addDays(30)
        );

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Logout from all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * Get current user with tokens.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'current_token' => [
                'name' => $user->currentAccessToken()->name,
                'abilities' => $user->currentAccessToken()->abilities,
                'created_at' => $user->currentAccessToken()->created_at,
                'expires_at' => $user->currentAccessToken()->expires_at,
            ],
        ]);
    }

    /**
     * List all user tokens.
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->get();

        return response()->json([
            'tokens' => $tokens->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ]),
        ]);
    }

    /**
     * Revoke specific token.
     */
    public function revokeToken(Request $request, string $tokenId): JsonResponse
    {
        $request->user()->tokens()
            ->where('id', $tokenId)
            ->delete();

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }

    /**
     * Refresh token (create new and revoke old).
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        // Create new token with same abilities
        $newToken = $user->createToken(
            name: $currentToken->name,
            abilities: $currentToken->abilities,
            expiresAt: now()->addDays(30)
        );

        // Revoke old token
        $currentToken->delete();

        return response()->json([
            'token' => $newToken->plainTextToken,
            'expires_at' => $newToken->accessToken->expires_at,
        ]);
    }

    /**
     * Get token abilities based on user role.
     */
    private function getTokenAbilities(User $user): array
    {
        return match ($user->role) {
            'admin' => ['*'], // All permissions
            'manager' => [
                'user:read',
                'user:write',
                'payment:read',
                'payment:write',
                'report:read',
            ],
            'user' => [
                'user:read',
                'payment:read',
            ],
            default => ['user:read'],
        };
    }
}
