<?php

use App\Http\Controllers\Api\Auth\SanctumAuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Production-Ready Configuration
|--------------------------------------------------------------------------
*/

// Public routes (no authentication)
Route::prefix('v1')->group(function () {
    
    // Authentication endpoints
    Route::prefix('auth')->group(function () {
        Route::post('/register', [SanctumAuthController::class, 'register'])
            ->middleware('throttle:register'); // 5 attempts per hour
        
        Route::post('/login', [SanctumAuthController::class, 'login'])
            ->middleware('throttle:login'); // 5 attempts per minute
    });
    
    // Webhook endpoints (signature verification in controller)
    Route::prefix('webhooks')->group(function () {
        Route::post('/razorpay', [WebhookController::class, 'razorpay']);
        Route::post('/stripe', [WebhookController::class, 'stripe']);
    });
});

// Protected routes (Sanctum authentication required)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Authentication management
    Route::prefix('auth')->group(function () {
        Route::get('/user', [SanctumAuthController::class, 'user']);
        Route::get('/tokens', [SanctumAuthController::class, 'tokens']);
        Route::post('/logout', [SanctumAuthController::class, 'logout']);
        Route::post('/logout-all', [SanctumAuthController::class, 'logoutAll']);
        Route::post('/refresh', [SanctumAuthController::class, 'refresh']);
        Route::delete('/tokens/{token}', [SanctumAuthController::class, 'revokeToken']);
    });
    
    // Payment endpoints with different rate limits
    Route::prefix('payments')->group(function () {
        
        // List payments (read-only, less strict rate limit)
        Route::get('/', [PaymentController::class, 'index'])
            ->middleware('throttle:api') // 60 per minute
            ->middleware('ability:payment:read');
        
        // View single payment
        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->middleware('throttle:api')
            ->middleware('ability:payment:read');
        
        // Create payment (write operation, strict rate limit)
        Route::post('/', [PaymentController::class, 'store'])
            ->middleware('throttle:payment') // 5 per minute
            ->middleware('ability:payment:write');
        
        // Refund payment (critical operation, very strict)
        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])
            ->middleware('throttle:payment')
            ->middleware('ability:payment:refund');
        
        // Verify payment status
        Route::post('/{payment}/verify', [PaymentController::class, 'verify'])
            ->middleware('throttle:api')
            ->middleware('ability:payment:read');
    });
    
    // Admin-only routes
    Route::prefix('admin')->middleware(['ability:admin'])->group(function () {
        
        // Payment administration
        Route::prefix('payments')->group(function () {
            Route::get('/all', [PaymentController::class, 'adminIndex']);
            Route::get('/analytics', [PaymentController::class, 'analytics']);
            Route::post('/{payment}/force-refund', [PaymentController::class, 'forceRefund']);
        });
        
        // System health and monitoring
        Route::get('/health', function () {
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'services' => [
                    'database' => DB::connection()->getPdo() ? 'up' : 'down',
                    'cache' => Cache::connection()->ping() ? 'up' : 'down',
                    'queue' => Queue::connection()->size() !== false ? 'up' : 'down',
                ],
            ]);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Rate Limiter Configuration
|--------------------------------------------------------------------------
*/

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// Standard API rate limit (60 requests per minute)
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Login attempts (5 per minute per IP)
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Registration (5 per hour per IP)
RateLimiter::for('register', function (Request $request) {
    return Limit::perHour(5)->by($request->ip());
});

// Payment operations (5 per minute, 50 per hour, 500 per day)
RateLimiter::for('payment', function (Request $request) {
    $userId = $request->user()?->id ?: $request->ip();
    
    return [
        Limit::perMinute(5)->by($userId)->response(function () {
            return response()->json([
                'message' => 'Too many payment requests. Please wait before trying again.',
            ], 429);
        }),
        Limit::perHour(50)->by($userId),
        Limit::perDay(500)->by($userId),
    ];
});

// Premium users get higher limits
RateLimiter::for('api', function (Request $request) {
    if ($request->user()?->isPremium()) {
        return Limit::perMinute(120)->by($request->user()->id);
    }
    
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

/*
|--------------------------------------------------------------------------
| API Versioning Example
|--------------------------------------------------------------------------
*/

// Version 2 routes with backward compatibility
Route::prefix('v2')->middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('payments', PaymentControllerV2::class);
});

// API version negotiation via header
Route::middleware(['auth:sanctum', 'api.version'])->group(function () {
    Route::apiResource('payments', function () {
        $version = request()->header('Accept-Version', 'v1');
        
        return match($version) {
            'v2' => app(PaymentControllerV2::class),
            default => app(PaymentController::class),
        };
    });
});

/*
|--------------------------------------------------------------------------
| Health Check & Monitoring Routes
|--------------------------------------------------------------------------
*/

// Public health check (for load balancers)
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Detailed health check (authenticated)
Route::get('/health/detailed', function () {
    return response()->json([
        'status' => 'healthy',
        'checks' => [
            'database' => [
                'status' => DB::connection()->getPdo() ? 'up' : 'down',
                'latency_ms' => DB::connection()->selectOne('SELECT 1'),
            ],
            'redis' => [
                'status' => Redis::connection()->ping() ? 'up' : 'down',
            ],
            'queue' => [
                'status' => Queue::size() !== false ? 'up' : 'down',
                'jobs_pending' => Queue::size(),
            ],
        ],
        'timestamp' => now()->toISOString(),
    ]);
})->middleware(['auth:sanctum', 'ability:admin']);

/*
|--------------------------------------------------------------------------
| API Documentation Routes
|--------------------------------------------------------------------------
*/

// OpenAPI/Swagger documentation
Route::get('/docs', function () {
    return view('api-docs');
});

// API schema endpoint
Route::get('/schema', function () {
    return response()->json([
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Payment API',
            'version' => '1.0.0',
            'description' => 'Secure payment processing API',
        ],
        'servers' => [
            ['url' => config('app.url') . '/api/v1'],
        ],
        // ... full OpenAPI schema
    ]);
});
