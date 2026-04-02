<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

/**
 * Advanced rate limiting middleware using Redis.
 * Implements sliding window algorithm with multiple time windows.
 */
class RateLimitMiddleware
{
    private const WINDOW_1MIN = 60;
    private const WINDOW_1HOUR = 3600;
    private const WINDOW_1DAY = 86400;

    /**
     * Handle an incoming request with multi-tier rate limiting.
     */
    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        $limits = $this->getLimitsForTier($tier);
        $key = $this->resolveRequestSignature($request);

        // Check all time windows
        foreach ($limits as $window => $maxAttempts) {
            $attempts = $this->getAttempts($key, $window);
            
            if ($attempts >= $maxAttempts) {
                return $this->buildThrottledResponse($key, $window, $maxAttempts);
            }
        }

        // Increment counters for all windows
        foreach (array_keys($limits) as $window) {
            $this->incrementAttempts($key, $window);
        }

        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders($response, $key, $limits);
    }

    /**
     * Get rate limits based on tier.
     */
    private function getLimitsForTier(string $tier): array
    {
        return match($tier) {
            'strict' => [
                self::WINDOW_1MIN => 10,
                self::WINDOW_1HOUR => 100,
                self::WINDOW_1DAY => 1000,
            ],
            'moderate' => [
                self::WINDOW_1MIN => 60,
                self::WINDOW_1HOUR => 1000,
                self::WINDOW_1DAY => 10000,
            ],
            'payment' => [
                self::WINDOW_1MIN => 5,
                self::WINDOW_1HOUR => 50,
                self::WINDOW_1DAY => 500,
            ],
            default => [
                self::WINDOW_1MIN => 30,
                self::WINDOW_1HOUR => 500,
                self::WINDOW_1DAY => 5000,
            ],
        };
    }

    /**
     * Get unique signature for the request.
     */
    private function resolveRequestSignature(Request $request): string
    {
        // Use user ID if authenticated, otherwise IP address
        if ($user = $request->user()) {
            return 'rate_limit:user:' . $user->id . ':' . $request->path();
        }

        return 'rate_limit:ip:' . $request->ip() . ':' . $request->path();
    }

    /**
     * Get attempt count for a time window using sliding window algorithm.
     */
    private function getAttempts(string $key, int $window): int
    {
        $redisKey = "{$key}:{$window}";
        $now = time();
        $windowStart = $now - $window;

        // Remove old entries
        Redis::zremrangebyscore($redisKey, 0, $windowStart);

        // Count entries in window
        return (int) Redis::zcard($redisKey);
    }

    /**
     * Increment attempt counter.
     */
    private function incrementAttempts(string $key, int $window): void
    {
        $redisKey = "{$key}:{$window}";
        $now = time();
        $score = $now + (microtime(true) - floor(microtime(true)));

        // Add current request with timestamp score
        Redis::zadd($redisKey, $score, $score);

        // Set expiry to window duration
        Redis::expire($redisKey, $window);
    }

    /**
     * Build throttled response.
     */
    private function buildThrottledResponse(
        string $key,
        int $window,
        int $maxAttempts
    ): Response {
        $retryAfter = $this->getRetryAfter($key, $window);

        return response()->json([
            'message' => 'Too Many Requests',
            'retry_after' => $retryAfter,
        ], 429)
        ->header('Retry-After', $retryAfter)
        ->header('X-RateLimit-Limit', $maxAttempts)
        ->header('X-RateLimit-Remaining', 0)
        ->header('X-RateLimit-Reset', time() + $retryAfter);
    }

    /**
     * Calculate retry after seconds.
     */
    private function getRetryAfter(string $key, int $window): int
    {
        $redisKey = "{$key}:{$window}";
        $now = time();
        $windowStart = $now - $window;

        // Get oldest entry in current window
        $oldest = Redis::zrangebyscore($redisKey, $windowStart, '+inf', [
            'LIMIT' => [0, 1]
        ]);

        if (empty($oldest)) {
            return $window;
        }

        return (int) ceil($oldest[0] + $window - $now);
    }

    /**
     * Add rate limit headers to response.
     */
    private function addRateLimitHeaders(
        Response $response,
        string $key,
        array $limits
    ): Response {
        // Use shortest window for headers
        $shortestWindow = min(array_keys($limits));
        $limit = $limits[$shortestWindow];
        $remaining = max(0, $limit - $this->getAttempts($key, $shortestWindow));
        
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', time() + $shortestWindow);

        return $response;
    }
}
