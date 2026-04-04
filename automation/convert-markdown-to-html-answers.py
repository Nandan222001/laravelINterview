#!/usr/bin/env python3
"""
Convert Markdown to HTML Answers Generator for PHP Laravel API Security

This script:
1. Reads interview-bank/php-laravel-api-security/answers.md
2. Parses each numbered answer (1-1000)
3. Converts markdown code blocks to HTML <pre><code class="php"> elements with proper escaping
4. Converts markdown lists to <ul>/<li>
5. Converts bold/italic to <strong>/<em>
6. Wraps paragraphs in <p> tags
7. Generates comprehensive code examples for answers lacking detailed implementations
8. Adds production-ready PHP/Laravel code snippets
9. Includes flow diagrams where appropriate
"""

import os
import re
import sys
import html
from pathlib import Path
from typing import Dict, List, Tuple, Optional


class CodeExampleGenerator:
    """Generates comprehensive code examples for Laravel/PHP answers"""
    
    @staticmethod
    def generate_attribute_example(answer: str, context: str) -> str:
        """Generate PHP attribute code example"""
        if 'Route' in context or 'route' in answer.lower():
            return '''```php
<?php

namespace App\\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET',
        public array $middleware = []
    ) {}
}

// Usage in Controller
class UserController
{
    #[Route('/api/users', 'GET', ['auth:api'])]
    public function index()
    {
        return User::all();
    }
    
    #[Route('/api/users/{id}', 'GET')]
    public function show(int $id)
    {
        return User::findOrFail($id);
    }
}

// Middleware to process Route attribute
class AttributeRouteMiddleware
{
    public function handle($request, Closure $next)
    {
        $reflection = new ReflectionClass($request->route()->getController());
        $method = $reflection->getMethod($request->route()->getActionMethod());
        
        $attributes = $method->getAttributes(Route::class);
        foreach ($attributes as $attribute) {
            $route = $attribute->newInstance();
            // Process route permissions
            if (!empty($route->middleware)) {
                foreach ($route->middleware as $middleware) {
                    // Apply middleware logic
                }
            }
        }
        
        return $next($request);
    }
}
```'''
        elif 'Cached' in context or 'cache' in answer.lower():
            return '''```php
<?php

namespace App\\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Cached
{
    public function __construct(
        public int $ttl = 3600,
        public ?string $key = null
    ) {}
}

// Usage
class ProductService
{
    #[Cached(ttl: 7200, key: 'products.all')]
    public function getAllProducts(): array
    {
        return Product::with('category')->get()->toArray();
    }
    
    #[Cached(ttl: 3600)]
    public function getProductById(int $id): ?Product
    {
        return Product::find($id);
    }
}

// Aspect/Decorator to handle caching
class CacheAspect
{
    public function around(callable $method, object $instance, array $args)
    {
        $reflection = new ReflectionMethod($instance, $method);
        $attributes = $reflection->getAttributes(Cached::class);
        
        if (empty($attributes)) {
            return $method(...$args);
        }
        
        $cached = $attributes[0]->newInstance();
        $cacheKey = $cached->key ?? $this->generateKey($reflection, $args);
        
        return Cache::remember($cacheKey, $cached->ttl, function() use ($method, $args) {
            return $method(...$args);
        });
    }
    
    private function generateKey(ReflectionMethod $method, array $args): string
    {
        return sprintf('%s.%s.%s', 
            $method->getDeclaringClass()->getName(),
            $method->getName(),
            md5(serialize($args))
        );
    }
}
```'''
        else:
            return '''```php
<?php

namespace App\\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CustomAttribute
{
    public function __construct(
        public string $name,
        public array $options = []
    ) {}
}

// Reading attributes via Reflection
$reflection = new ReflectionClass(MyClass::class);
$attributes = $reflection->getAttributes(CustomAttribute::class);

foreach ($attributes as $attribute) {
    $instance = $attribute->newInstance();
    echo $instance->name;
    print_r($instance->options);
}
```'''
    
    @staticmethod
    def generate_enum_example(answer: str, context: str) -> str:
        """Generate PHP enum code example"""
        if 'payment' in context.lower() or 'status' in context.lower():
            return '''```php
<?php

namespace App\\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Awaiting Payment',
            self::PROCESSING => 'Processing Payment',
            self::COMPLETED => 'Payment Successful',
            self::FAILED => 'Payment Failed',
            self::REFUNDED => 'Payment Refunded',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::REFUNDED => 'orange',
        };
    }
    
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::PROCESSING, self::FAILED]),
            self::PROCESSING => in_array($newStatus, [self::COMPLETED, self::FAILED]),
            self::COMPLETED => $newStatus === self::REFUNDED,
            self::FAILED => false,
            self::REFUNDED => false,
        };
    }
    
    public function notifyUser(): void
    {
        match($this) {
            self::COMPLETED => event(new PaymentCompleted()),
            self::FAILED => event(new PaymentFailed()),
            self::REFUNDED => event(new PaymentRefunded()),
            default => null,
        };
    }
}

// Usage in model
class Payment extends Model
{
    protected $casts = [
        'status' => PaymentStatus::class,
    ];
    
    public function updateStatus(PaymentStatus $newStatus): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new InvalidStatusTransitionException();
        }
        
        $this->status = $newStatus;
        $this->save();
        
        $newStatus->notifyUser();
        
        return true;
    }
}
```'''
        elif 'http' in context.lower() or 'response' in context.lower():
            return '''```php
<?php

namespace App\\Enums;

enum HttpStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case UNPROCESSABLE_ENTITY = 422;
    case INTERNAL_SERVER_ERROR = 500;
    case SERVICE_UNAVAILABLE = 503;
    
    public function message(): string
    {
        return match($this) {
            self::OK => 'Request successful',
            self::CREATED => 'Resource created successfully',
            self::ACCEPTED => 'Request accepted for processing',
            self::NO_CONTENT => 'Request successful, no content',
            self::BAD_REQUEST => 'Invalid request parameters',
            self::UNAUTHORIZED => 'Authentication required',
            self::FORBIDDEN => 'Access denied',
            self::NOT_FOUND => 'Resource not found',
            self::UNPROCESSABLE_ENTITY => 'Validation failed',
            self::INTERNAL_SERVER_ERROR => 'Internal server error',
            self::SERVICE_UNAVAILABLE => 'Service temporarily unavailable',
        };
    }
    
    public function isSuccess(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }
    
    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }
    
    public function isServerError(): bool
    {
        return $this->value >= 500;
    }
}

// Usage in API response
return response()->json([
    'status' => HttpStatus::OK->value,
    'message' => HttpStatus::OK->message(),
    'data' => $data
], HttpStatus::OK->value);
```'''
        else:
            return '''```php
<?php

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';
    
    public function permissions(): array
    {
        return match($this) {
            self::SUPER_ADMIN => ['*'],
            self::ADMIN => ['users.manage', 'content.manage', 'reports.view'],
            self::MANAGER => ['content.manage', 'reports.view'],
            self::USER => ['content.view', 'profile.edit'],
        };
    }
    
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions();
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
}
```'''
    
    @staticmethod
    def generate_fiber_example(answer: str, context: str) -> str:
        """Generate PHP Fiber code example"""
        return '''```php
<?php

// Basic Fiber example
$fiber = new Fiber(function(): void {
    echo "Fiber started\\n";
    $value = Fiber::suspend('Hello');
    echo "Received: $value\\n";
    Fiber::suspend('World');
    echo "Fiber completed\\n";
});

// Start the fiber
$result1 = $fiber->start();
echo "First suspend returned: $result1\\n";

// Resume with value
$result2 = $fiber->resume('PHP');
echo "Second suspend returned: $result2\\n";

// Complete the fiber
$fiber->resume('8.1');

// Advanced: Task Scheduler with Fibers
class TaskScheduler
{
    private array $tasks = [];
    
    public function addTask(Closure $callback): void
    {
        $this->tasks[] = new Fiber($callback);
    }
    
    public function run(): void
    {
        // Start all fibers
        foreach ($this->tasks as $fiber) {
            if (!$fiber->isStarted()) {
                $fiber->start();
            }
        }
        
        // Keep running until all fibers complete
        while (!empty($this->tasks)) {
            foreach ($this->tasks as $key => $fiber) {
                if ($fiber->isTerminated()) {
                    unset($this->tasks[$key]);
                    continue;
                }
                
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                }
            }
            
            // Prevent busy-waiting
            usleep(1000);
        }
    }
}

// Usage: Concurrent HTTP requests
$scheduler = new TaskScheduler();

$urls = [
    'https://api.example.com/users',
    'https://api.example.com/posts',
    'https://api.example.com/comments'
];

foreach ($urls as $url) {
    $scheduler->addTask(function() use ($url) {
        echo "Fetching: $url\\n";
        $data = file_get_contents($url);
        Fiber::suspend();
        echo "Completed: $url\\n";
        return $data;
    });
}

$scheduler->run();
```'''
    
    @staticmethod
    def generate_laravel_example(answer: str, context: str) -> str:
        """Generate Laravel-specific code examples"""
        if 'middleware' in context.lower():
            return '''```php
<?php

namespace App\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 60)
    {
        $key = 'rate_limit:' . $request->ip();
        
        if (Cache::has($key) && Cache::get($key) >= $maxAttempts) {
            return response()->json([
                'error' => 'Too many requests'
            ], 429);
        }
        
        Cache::put($key, (Cache::get($key, 0) + 1), now()->addMinute());
        
        return $next($request);
    }
}

// Register in Kernel
protected $routeMiddleware = [
    'rate_limit' => \\App\\Http\\Middleware\\RateLimitMiddleware::class,
];

// Usage
Route::get('/api/data', [DataController::class, 'index'])
    ->middleware('rate_limit:100');
```'''
        elif 'service container' in context.lower() or 'dependency injection' in context.lower():
            return '''```php
<?php

namespace App\\Services;

interface PaymentGatewayInterface
{
    public function charge(float $amount): bool;
}

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private string $apiKey) {}
    
    public function charge(float $amount): bool
    {
        // Stripe-specific implementation
        return true;
    }
}

class RazorpayPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private string $apiKey, private string $apiSecret) {}
    
    public function charge(float $amount): bool
    {
        // Razorpay-specific implementation
        return true;
    }
}

// Service Provider
namespace App\\Providers;

use Illuminate\\Support\\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            $gateway = config('payment.default_gateway');
            
            return match($gateway) {
                'stripe' => new StripePaymentGateway(config('payment.stripe.key')),
                'razorpay' => new RazorpayPaymentGateway(
                    config('payment.razorpay.key'),
                    config('payment.razorpay.secret')
                ),
                default => throw new \\Exception('Invalid payment gateway'),
            };
        });
        
        // Singleton binding
        $this->app->singleton('payment.manager', function ($app) {
            return new PaymentManager($app->make(PaymentGatewayInterface::class));
        });
    }
}

// Controller usage
class PaymentController extends Controller
{
    public function __construct(
        private PaymentGatewayInterface $gateway
    ) {}
    
    public function charge(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);
        
        $success = $this->gateway->charge($validated['amount']);
        
        return response()->json(['success' => $success]);
    }
}
```'''
        elif 'queue' in context.lower() or 'job' in context.lower():
            return '''```php
<?php

namespace App\\Jobs;

use Illuminate\\Bus\\Queueable;
use Illuminate\\Contracts\\Queue\\ShouldQueue;
use Illuminate\\Foundation\\Bus\\Dispatchable;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Queue\\SerializesModels;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60;
    
    public function __construct(
        public int $orderId,
        public float $amount
    ) {}
    
    public function handle(): void
    {
        $order = Order::findOrFail($this->orderId);
        
        try {
            $payment = app(PaymentGatewayInterface::class);
            $result = $payment->charge($this->amount);
            
            if ($result) {
                $order->update(['status' => 'paid']);
                event(new PaymentSuccessful($order));
            } else {
                $this->fail(new PaymentFailedException());
            }
        } catch (\\Exception $e) {
            report($e);
            
            if ($this->attempts() >= $this->tries) {
                $order->update(['status' => 'payment_failed']);
                event(new PaymentFailed($order, $e));
            }
            
            throw $e;
        }
    }
    
    public function failed(\\Throwable $exception): void
    {
        Log::error('Payment job failed', [
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'exception' => $exception->getMessage()
        ]);
    }
}

// Dispatch
ProcessPaymentJob::dispatch($order->id, $order->total)
    ->onQueue('payments')
    ->delay(now()->addSeconds(5));

// Chain jobs
ProcessPaymentJob::withChain([
    new SendInvoiceJob($order->id),
    new SendConfirmationEmailJob($order->id)
])->dispatch($order->id, $order->total);
```'''
        else:
            return '''```php
<?php

namespace App\\Http\\Controllers\\API;

use App\\Models\\User;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with('roles')
            ->paginate(20);
            
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total()
            ]
        ]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed'
        ]);
        
        $validated['password'] = bcrypt($validated['password']);
        $user = User::create($validated);
        
        return response()->json($user, 201);
    }
}
```'''
    
    @staticmethod
    def should_add_example(answer: str) -> bool:
        """Determine if answer needs additional code example"""
        # Add example if answer is short or lacks code
        has_code = '```' in answer or 'php' in answer.lower()
        is_short = len(answer) < 200
        
        return is_short or not has_code


class SVGFlowGenerator:
    """Generates SVG flow diagrams for various concepts"""
    
    @staticmethod
    def generate_jwt_auth_flow() -> str:
        """JWT Authentication Flow"""
        return '''<div class="flow-diagram">
<svg width="800" height="600" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrow" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .box { fill: #3498db; stroke: #2980b9; stroke-width: 2; }
      .process { fill: #2ecc71; stroke: #27ae60; stroke-width: 2; }
      .storage { fill: #e74c3c; stroke: #c0392b; stroke-width: 2; }
      .text { fill: white; font-family: Arial; font-size: 13px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; marker-end: url(#arrow); fill: none; }
      .label { fill: #666; font-family: Arial; font-size: 11px; }
    </style>
  </defs>
  
  <rect class="box" x="50" y="50" width="180" height="60" rx="5"/>
  <text class="text" x="140" y="85">Client Login Request</text>
  
  <line class="arrow" x1="230" y1="80" x2="320" y2="80"/>
  
  <rect class="process" x="320" y="50" width="180" height="60" rx="5"/>
  <text class="text" x="410" y="85">Validate Credentials</text>
  
  <line class="arrow" x1="410" y1="110" x2="410" y2="180"/>
  
  <rect class="process" x="320" y="180" width="180" height="60" rx="5"/>
  <text class="text" x="410" y="215">Generate JWT Token</text>
  
  <line class="arrow" x1="320" y1="210" x2="230" y2="210"/>
  
  <rect class="storage" x="50" y="180" width="180" height="60" rx="5"/>
  <text class="text" x="140" y="215">Store in HttpOnly Cookie</text>
  
  <line class="arrow" x1="140" y1="240" x2="140" y2="310"/>
  
  <rect class="box" x="50" y="310" width="180" height="60" rx="5"/>
  <text class="text" x="140" y="345">Subsequent Requests</text>
  
  <line class="arrow" x1="230" y1="340" x2="320" y2="340"/>
  
  <rect class="process" x="320" y="310" width="180" height="60" rx="5"/>
  <text class="text" x="410" y="345">Verify JWT Signature</text>
  
  <line class="arrow" x1="500" y1="340" x2="590" y2="340"/>
  
  <rect class="storage" x="590" y="310" width="180" height="60" rx="5"/>
  <text class="text" x="680" y="345">Access Protected Resource</text>
  
  <text class="label" x="275" y="70">POST /api/login</text>
  <text class="label" x="410" y="150">Check DB</text>
  <text class="label" x="275" y="200">Return token</text>
  <text class="label" x="140" y="290">secure, httpOnly</text>
  <text class="label" x="545" y="330">Authorized</text>
</svg>
</div>'''
    
    @staticmethod
    def generate_oauth2_flow() -> str:
        """OAuth2 Authorization Flow"""
        return '''<div class="flow-diagram">
<svg width="900" height="700" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrow2" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .client { fill: #9b59b6; stroke: #8e44ad; stroke-width: 2; }
      .auth { fill: #3498db; stroke: #2980b9; stroke-width: 2; }
      .resource { fill: #2ecc71; stroke: #27ae60; stroke-width: 2; }
      .text { fill: white; font-family: Arial; font-size: 12px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; marker-end: url(#arrow2); fill: none; }
      .label { fill: #555; font-family: Arial; font-size: 10px; }
    </style>
  </defs>
  
  <text x="100" y="30" class="label" font-weight="bold" font-size="14">Client App</text>
  <text x="450" y="30" class="label" font-weight="bold" font-size="14">Auth Server</text>
  <text x="750" y="30" class="label" font-weight="bold" font-size="14">Resource Server</text>
  
  <rect class="client" x="50" y="60" width="150" height="50" rx="5"/>
  <text class="text" x="125" y="90">Request Authorization</text>
  
  <line class="arrow" x1="200" y1="85" x2="350" y2="85"/>
  <text class="label" x="275" y="80">redirect with client_id</text>
  
  <rect class="auth" x="350" y="60" width="180" height="50" rx="5"/>
  <text class="text" x="440" y="90">User Login & Consent</text>
  
  <line class="arrow" x1="440" y1="110" x2="440" y2="180"/>
  
  <rect class="auth" x="350" y="180" width="180" height="50" rx="5"/>
  <text class="text" x="440" y="210">Generate Auth Code</text>
  
  <line class="arrow" x1="350" y1="205" x2="200" y2="205"/>
  <text class="label" x="275" y="200">redirect with code</text>
  
  <rect class="client" x="50" y="180" width="150" height="50" rx="5"/>
  <text class="text" x="125" y="210">Receive Auth Code</text>
  
  <line class="arrow" x1="200" y1="230" x2="350" y2="290"/>
  <text class="label" x="275" y="260">POST /token with code</text>
  
  <rect class="auth" x="350" y="290" width="180" height="50" rx="5"/>
  <text class="text" x="440" y="320">Exchange for Tokens</text>
  
  <line class="arrow" x1="350" y1="315" x2="200" y2="315"/>
  <text class="label" x="275" y="310">access_token + refresh_token</text>
  
  <rect class="client" x="50" y="290" width="150" height="50" rx="5"/>
  <text class="text" x="125" y="320">Store Tokens</text>
  
  <line class="arrow" x1="200" y1="340" x2="680" y2="400"/>
  <text class="label" x="440" y="370">Authorization: Bearer {token}</text>
  
  <rect class="resource" x="680" y="400" width="180" height="50" rx="5"/>
  <text class="text" x="770" y="430">Validate Token</text>
  
  <line class="arrow" x1="770" y1="450" x2="770" y2="520"/>
  
  <rect class="resource" x="680" y="520" width="180" height="50" rx="5"/>
  <text class="text" x="770" y="550">Return Protected Data</text>
  
  <line class="arrow" x1="680" y1="545" x2="200" y2="490"/>
  <text class="label" x="440" y="520">JSON response</text>
  
  <rect class="client" x="50" y="460" width="150" height="50" rx="5"/>
  <text class="text" x="125" y="490">Process Data</text>
  
  <text class="label" x="450" y="650" font-weight="bold">OAuth2 Authorization Code Flow with PKCE</text>
</svg>
</div>'''
    
    @staticmethod
    def detect_diagram_need(question: str, answer: str) -> Optional[str]:
        """Detect if a flow diagram is needed"""
        content = (question + ' ' + answer).lower()
        
        if any(kw in content for kw in ['jwt', 'token authentication', 'bearer token']):
            return SVGFlowGenerator.generate_jwt_auth_flow()
        elif any(kw in content for kw in ['oauth', 'oauth2', 'authorization code']):
            return SVGFlowGenerator.generate_oauth2_flow()
        
        return None


class MarkdownToHTMLConverter:
    """Converts markdown content to properly formatted HTML"""
    
    def __init__(self):
        self.code_example_gen = CodeExampleGenerator()
        self.svg_gen = SVGFlowGenerator()
    
    def escape_html(self, text: str) -> str:
        """Escape HTML special characters"""
        return html.escape(text)
    
    def parse_code_blocks(self, text: str) -> List[Tuple[str, str, str]]:
        """Extract code blocks from markdown"""
        pattern = r'```(\w+)?\n(.*?)```'
        blocks = []
        for match in re.finditer(pattern, text, re.DOTALL):
            lang = match.group(1) or 'php'
            code = match.group(2).strip()
            original = match.group(0)
            blocks.append((lang, code, original))
        return blocks
    
    def convert_code_block(self, code: str, language: str = 'php') -> str:
        """Convert code block to HTML with proper escaping"""
        escaped_code = self.escape_html(code)
        return f'<pre><code class="{language}">{escaped_code}</code></pre>'
    
    def convert_inline_code(self, text: str) -> str:
        """Convert inline code (backticks)"""
        return re.sub(r'`([^`]+)`', lambda m: f'<code>{self.escape_html(m.group(1))}</code>', text)
    
    def convert_bold(self, text: str) -> str:
        """Convert **bold** and __bold__ to <strong>"""
        text = re.sub(r'\*\*([^*]+)\*\*', r'<strong>\1</strong>', text)
        text = re.sub(r'__([^_]+)__', r'<strong>\1</strong>', text)
        return text
    
    def convert_italic(self, text: str) -> str:
        """Convert *italic* and _italic_ to <em>"""
        # Be careful not to match ** or __ (bold)
        text = re.sub(r'(?<!\*)\*(?!\*)([^*]+)(?<!\*)\*(?!\*)', r'<em>\1</em>', text)
        text = re.sub(r'(?<!_)_(?!_)([^_]+)(?<!_)_(?!_)', r'<em>\1</em>', text)
        return text
    
    def convert_links(self, text: str) -> str:
        """Convert [text](url) to <a href="url">text</a>"""
        return re.sub(r'\[([^\]]+)\]\(([^)]+)\)', r'<a href="\2" target="_blank">\1</a>', text)
    
    def convert_lists(self, text: str) -> str:
        """Convert markdown lists to HTML <ul>/<li> or <ol>/<li>"""
        lines = text.split('\n')
        result = []
        in_ul = False
        in_ol = False
        
        i = 0
        while i < len(lines):
            line = lines[i]
            
            # Unordered list
            if re.match(r'^\s*[-*+]\s+', line):
                if not in_ul and not in_ol:
                    result.append('<ul>')
                    in_ul = True
                item_text = re.sub(r'^\s*[-*+]\s+', '', line)
                result.append(f'  <li>{item_text}</li>')
            
            # Ordered list
            elif re.match(r'^\s*\d+\.\s+', line):
                if not in_ol and not in_ul:
                    result.append('<ol>')
                    in_ol = True
                item_text = re.sub(r'^\s*\d+\.\s+', '', line)
                result.append(f'  <li>{item_text}</li>')
            
            # End of list
            else:
                if in_ul:
                    result.append('</ul>')
                    in_ul = False
                elif in_ol:
                    result.append('</ol>')
                    in_ol = False
                
                if line.strip():
                    result.append(line)
            
            i += 1
        
        # Close any open lists
        if in_ul:
            result.append('</ul>')
        if in_ol:
            result.append('</ol>')
        
        return '\n'.join(result)
    
    def wrap_paragraphs(self, text: str) -> str:
        """Wrap text paragraphs in <p> tags"""
        # Split by double newlines
        paragraphs = re.split(r'\n\s*\n', text)
        result = []
        
        for para in paragraphs:
            para = para.strip()
            if not para:
                continue
            
            # Don't wrap if already HTML tags
            if para.startswith('<') and para.endswith('>'):
                result.append(para)
            # Don't wrap code blocks
            elif '<pre>' in para or '<ul>' in para or '<ol>' in para or '<div>' in para:
                result.append(para)
            else:
                result.append(f'<p>{para}</p>')
        
        return '\n'.join(result)
    
    def convert_markdown_to_html(self, markdown_text: str, question: str = '', answer_num: int = 0) -> Tuple[str, Optional[str]]:
        """Main conversion function"""
        # Extract code blocks first
        code_blocks = self.parse_code_blocks(markdown_text)
        placeholders = {}
        
        # Replace code blocks with placeholders
        for i, (lang, code, original) in enumerate(code_blocks):
            placeholder = f'___CODE_BLOCK_{i}___'
            placeholders[placeholder] = self.convert_code_block(code, lang)
            markdown_text = markdown_text.replace(original, placeholder)
        
        # Apply inline conversions
        html = self.convert_inline_code(markdown_text)
        html = self.convert_bold(html)
        html = self.convert_italic(html)
        html = self.convert_links(html)
        html = self.convert_lists(html)
        
        # Restore code blocks
        for placeholder, code_html in placeholders.items():
            html = html.replace(placeholder, code_html)
        
        # Wrap paragraphs
        html = self.wrap_paragraphs(html)
        
        # Check if we need to add code examples
        if not code_blocks and self.code_example_gen.should_add_example(markdown_text):
            additional_example = self._generate_contextual_example(markdown_text, question, answer_num)
            if additional_example:
                html += '\n' + additional_example
        
        # Check for flow diagram
        flow_diagram = self.svg_gen.detect_diagram_need(question, markdown_text)
        
        return html, flow_diagram
    
    def _generate_contextual_example(self, answer: str, question: str, answer_num: int) -> str:
        """Generate contextual code example based on answer content"""
        context = question + ' ' + answer
        
        # Determine what kind of example to generate
        if any(kw in context.lower() for kw in ['attribute', '#[', 'reflection']):
            example = self.code_example_gen.generate_attribute_example(answer, context)
        elif any(kw in context.lower() for kw in ['enum', 'backed enum', 'enum case']):
            example = self.code_example_gen.generate_enum_example(answer, context)
        elif any(kw in context.lower() for kw in ['fiber', 'cooperative', 'suspend', 'resume']):
            example = self.code_example_gen.generate_fiber_example(answer, context)
        elif any(kw in context.lower() for kw in ['laravel', 'eloquent', 'middleware', 'service container', 'queue', 'job']):
            example = self.code_example_gen.generate_laravel_example(answer, context)
        else:
            return ''
        
        return f'\n<div class="code-example-section">\n<h4>Code Example:</h4>\n{self.convert_markdown_to_html(example, question)[0]}\n</div>'


class AnswerParser:
    """Parses answers.md file and extracts numbered answers"""
    
    def __init__(self, file_path: str):
        self.file_path = Path(file_path)
        self.answers = {}
    
    def parse(self) -> Dict[int, str]:
        """Parse the answers file and return dict of answer_num: answer_text"""
        if not self.file_path.exists():
            raise FileNotFoundError(f"File not found: {self.file_path}")
        
        with open(self.file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Split into lines
        lines = content.split('\n')
        current_num = None
        current_text = []
        
        for line in lines:
            # Match numbered answer (e.g., "1. Answer text...")
            match = re.match(r'^(\d+)\.\s+(.*)', line)
            
            if match:
                # Save previous answer if exists
                if current_num is not None and current_text:
                    self.answers[current_num] = '\n'.join(current_text).strip()
                
                # Start new answer
                current_num = int(match.group(1))
                current_text = [match.group(2)]
            
            elif current_num is not None:
                # Check if this is a section header (skip it)
                if re.match(r'^#{1,6}\s+', line):
                    # Save current answer before section header
                    if current_text:
                        self.answers[current_num] = '\n'.join(current_text).strip()
                    current_num = None
                    current_text = []
                else:
                    # Continue accumulating current answer
                    current_text.append(line)
        
        # Save last answer
        if current_num is not None and current_text:
            self.answers[current_num] = '\n'.join(current_text).strip()
        
        return self.answers
    
    def get_answer(self, num: int) -> Optional[str]:
        """Get specific answer by number"""
        return self.answers.get(num)


class HTMLGenerator:
    """Generates the final HTML output"""
    
    def __init__(self, answers_file: str):
        self.answers_file = answers_file
        self.parser = AnswerParser(answers_file)
        self.converter = MarkdownToHTMLConverter()
    
    def generate_html_page(self, output_file: str):
        """Generate complete HTML page with all answers"""
        # Parse answers
        print("Parsing answers.md file...")
        answers = self.parser.parse()
        print(f"Found {len(answers)} answers")
        
        # Generate HTML content
        print("Converting markdown to HTML...")
        html_parts = []
        
        # HTML header
        html_parts.append(self._get_html_header())
        
        # Process each answer
        for num in sorted(answers.keys()):
            if num % 50 == 0:
                print(f"  Processing answer {num}...")
            
            answer_text = answers[num]
            question = f"Question {num}"  # We don't have questions file, use generic
            
            # Convert to HTML
            answer_html, flow_diagram = self.converter.convert_markdown_to_html(
                answer_text, question, num
            )
            
            # Build answer section
            section = self._build_answer_section(num, question, answer_html, flow_diagram)
            html_parts.append(section)
        
        # HTML footer
        html_parts.append(self._get_html_footer())
        
        # Write to file
        output_path = Path(output_file)
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write('\n'.join(html_parts))
        
        print(f"\n✓ HTML file generated: {output_file}")
        print(f"✓ Total answers: {len(answers)}")
        print(f"✓ File size: {output_path.stat().st_size:,} bytes")
    
    def _build_answer_section(self, num: int, question: str, answer_html: str, flow_diagram: Optional[str]) -> str:
        """Build HTML section for a single answer"""
        section = f'''
<article id="answer-{num}" class="answer-item">
  <div class="answer-header">
    <span class="answer-number">#{num}</span>
    <span class="answer-title">{self.converter.escape_html(question)}</span>
  </div>
  <div class="answer-content">
    {answer_html}
  </div>'''
        
        if flow_diagram:
            section += f'\n  {flow_diagram}'
        
        section += '\n</article>\n'
        
        return section
    
    def _get_html_header(self) -> str:
        """Get HTML document header with CSS"""
        return '''<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHP Laravel API Security - Interview Answers (1-1000)</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
      line-height: 1.7;
      color: #2c3e50;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      padding: 40px;
    }
    
    h1 {
      text-align: center;
      color: #2c3e50;
      font-size: 2.5em;
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 4px solid #667eea;
    }
    
    .header-info {
      text-align: center;
      color: #7f8c8d;
      margin-bottom: 40px;
      font-size: 1.1em;
    }
    
    .answer-item {
      margin-bottom: 50px;
      padding: 30px;
      background: #f8f9fa;
      border-radius: 10px;
      border-left: 5px solid #667eea;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .answer-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .answer-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      gap: 15px;
    }
    
    .answer-number {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-weight: bold;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.9em;
      min-width: 60px;
      text-align: center;
    }
    
    .answer-title {
      font-size: 1.3em;
      font-weight: 600;
      color: #2c3e50;
      flex: 1;
    }
    
    .answer-content {
      color: #34495e;
      font-size: 1.05em;
    }
    
    .answer-content p {
      margin-bottom: 15px;
      line-height: 1.8;
    }
    
    .answer-content code {
      background: #f0f0f0;
      padding: 3px 8px;
      border-radius: 4px;
      font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
      font-size: 0.9em;
      color: #e74c3c;
      border: 1px solid #e0e0e0;
    }
    
    .answer-content pre {
      background: #282c34;
      color: #abb2bf;
      padding: 20px;
      border-radius: 8px;
      overflow-x: auto;
      margin: 20px 0;
      border-left: 4px solid #61dafb;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .answer-content pre code {
      background: transparent;
      color: inherit;
      padding: 0;
      border: none;
      font-size: 0.95em;
      line-height: 1.6;
    }
    
    .answer-content pre code.php::before {
      content: '<?php';
      display: block;
      color: #61dafb;
      font-weight: bold;
      margin-bottom: 10px;
    }
    
    .answer-content ul,
    .answer-content ol {
      margin-left: 30px;
      margin-bottom: 15px;
    }
    
    .answer-content li {
      margin-bottom: 10px;
      line-height: 1.7;
    }
    
    .answer-content strong {
      color: #2c3e50;
      font-weight: 600;
    }
    
    .answer-content em {
      font-style: italic;
      color: #555;
    }
    
    .answer-content a {
      color: #667eea;
      text-decoration: none;
      border-bottom: 2px solid #667eea;
      transition: color 0.2s, border-color 0.2s;
    }
    
    .answer-content a:hover {
      color: #764ba2;
      border-bottom-color: #764ba2;
    }
    
    .code-example-section {
      margin-top: 25px;
      padding: 20px;
      background: #fff;
      border-radius: 8px;
      border: 2px dashed #667eea;
    }
    
    .code-example-section h4 {
      color: #667eea;
      margin-bottom: 15px;
      font-size: 1.2em;
    }
    
    .flow-diagram {
      margin-top: 30px;
      padding: 25px;
      background: #fff;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .flow-diagram svg {
      max-width: 100%;
      height: auto;
      display: block;
      margin: 0 auto;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 20px;
      }
      
      h1 {
        font-size: 1.8em;
      }
      
      .answer-item {
        padding: 20px;
      }
      
      .answer-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .answer-content pre {
        padding: 15px;
        font-size: 0.85em;
      }
    }
    
    /* Syntax highlighting for code */
    .answer-content pre code {
      color: #abb2bf;
    }
    
    /* Back to top button */
    .back-to-top {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5em;
      text-decoration: none;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      transition: transform 0.2s;
    }
    
    .back-to-top:hover {
      transform: translateY(-3px);
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>PHP, Laravel &amp; Advanced API Security</h1>
    <div class="header-info">
      <p><strong>Complete Interview Answers Collection</strong> &bull; 1-1000 Questions</p>
      <p>Comprehensive answers with code examples, best practices, and flow diagrams</p>
    </div>
'''
    
    def _get_html_footer(self) -> str:
        """Get HTML document footer"""
        return '''
  </div>
  <a href="#" class="back-to-top">↑</a>
  
  <script>
    // Smooth scroll for back to top
    document.querySelector('.back-to-top').addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // Show/hide back to top button
    window.addEventListener('scroll', () => {
      const btn = document.querySelector('.back-to-top');
      if (window.scrollY > 300) {
        btn.style.display = 'flex';
      } else {
        btn.style.display = 'none';
      }
    });
  </script>
</body>
</html>'''


def main():
    """Main entry point"""
    # Ensure UTF-8 encoding for output (Windows compatibility)
    if sys.platform == 'win32':
        import io
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    
    print("=" * 70)
    print("  PHP Laravel API Security - Markdown to HTML Answer Converter")
    print("=" * 70)
    print()
    
    # File paths
    answers_file = 'interview-bank/php-laravel-api-security/answers.md'
    output_file = 'automation/output/php-laravel-answers.html'
    
    # Check if input file exists
    if not Path(answers_file).exists():
        print(f"[ERROR] Input file not found: {answers_file}")
        sys.exit(1)
    
    print(f"[INPUT] {answers_file}")
    print(f"[OUTPUT] {output_file}")
    print()
    
    try:
        # Generate HTML
        generator = HTMLGenerator(answers_file)
        generator.generate_html_page(output_file)
        
        print()
        print("=" * 70)
        print("[SUCCESS] Conversion completed successfully!")
        print("=" * 70)
        print()
        print(f"Open {output_file} in your browser to view the answers.")
        
    except Exception as e:
        print(f"\n[ERROR] Error during conversion: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    main()
