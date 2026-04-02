# PHP, Laravel & Advanced API Security - Production Code Examples

This directory contains production-ready code examples demonstrating advanced PHP 8.x features, Laravel best practices, payment gateway integration, and comprehensive security implementations.

## 📁 Directory Structure

```
code-examples/
├── PaymentService.php                 # Main payment service with idempotency
├── RazorpayService.php               # Razorpay integration with webhook verification
├── StripeService.php                 # Stripe integration with SCA support
├── IdempotencyService.php            # Idempotency key management
├── WebhookController.php             # Webhook handlers with signature verification
├── ProcessPaymentWebhook.php         # Async webhook processing job
├── PaymentStatus.php                 # PHP 8.1 enum with state transitions
├── SanctumAuthController.php         # Complete Sanctum authentication
├── SecurityHeadersMiddleware.php     # OWASP security headers implementation
├── RateLimitMiddleware.php           # Advanced rate limiting with Redis
├── PaymentRequest.php                # Comprehensive input validation
├── PaymentResource.php               # API resource with HATEOAS
├── PHP8_Features.php                 # PHP 8.x features demonstration
├── PCI_DSS_Checklist.md             # Complete PCI DSS compliance guide
├── OWASP_Top10_Mitigations.md       # OWASP Top 10 mitigation strategies
└── migrations/
    ├── create_payments_table.php
    └── create_idempotency_keys_table.php
```

## 🚀 Key Features Demonstrated

### 1. Payment Gateway Integration

#### Razorpay
- Order creation with idempotency keys
- Payment capture and verification
- Webhook signature verification (HMAC-SHA256)
- Refund processing
- Error handling and retry logic

#### Stripe
- Payment Intent creation
- Strong Customer Authentication (SCA)
- 3D Secure support
- Webhook signature verification with timestamp validation
- Customer and payment method management

### 2. Idempotency Implementation

```php
// Prevent duplicate payment processing
$payment = $paymentService->processPayment(
    order: $order,
    paymentMethod: 'razorpay',
    paymentData: $data,
    idempotencyKey: 'idem_abc123_' . time()
);
```

Features:
- Database-backed idempotency tracking
- Redis caching for performance
- Concurrent request handling with locks
- Automatic key expiration

### 3. Security Features

#### Authentication
- Laravel Sanctum with token abilities
- Token expiration and revocation
- Rate-limited login attempts
- Multi-device session management

#### Authorization
- Policy-based access control
- Role and permission management
- Token abilities/scopes

#### Security Headers
- Content Security Policy (CSP)
- HSTS, X-Frame-Options, X-Content-Type-Options
- Cross-Origin policies
- Custom security headers

#### Rate Limiting
- Sliding window algorithm
- Multi-tier limits (per minute, hour, day)
- User and IP-based limiting
- Distributed rate limiting with Redis

### 4. PHP 8.x Features

#### Enums (PHP 8.1)
```php
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    
    public function canRefund(): bool
    {
        return $this === self::COMPLETED;
    }
}
```

#### Readonly Properties (PHP 8.1)
```php
readonly class PaymentRequest
{
    public function __construct(
        public string $orderId,
        public float $amount,
        public string $currency,
    ) {}
}
```

#### Constructor Property Promotion
```php
public function __construct(
    private readonly RazorpayService $razorpayService,
    private readonly StripeService $stripeService,
    private readonly IdempotencyService $idempotencyService,
) {}
```

#### Match Expressions
```php
return match($payment->payment_method) {
    'razorpay' => $this->razorpayService->refund(...),
    'stripe' => $this->stripeService->refund(...),
    default => throw new PaymentException('Unsupported method'),
};
```

### 5. Queue Workers & Async Processing

```php
// Dispatch payment webhook processing to queue
ProcessPaymentWebhook::dispatch('razorpay', $event)
    ->onQueue('webhooks');
```

Features:
- Job chaining and batching
- Exponential backoff retry
- Job middleware
- Failed job handling
- Timeout configuration

### 6. Database Best Practices

- UUID primary keys for payments
- Composite indexes for performance
- Soft deletes
- JSON columns for metadata
- Foreign key constraints
- Optimistic locking with timestamps

### 7. Error Handling

```php
try {
    $payment = DB::transaction(function () use ($order, $data) {
        // Process payment
    });
} catch (\Exception $e) {
    Log::error('Payment failed', [
        'order_id' => $order->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    throw new PaymentException(
        "Payment failed: {$e->getMessage()}",
        previous: $e
    );
}
```

Features:
- Structured error logging
- Context-aware exceptions
- Graceful error recovery
- User-friendly error messages

## 📚 Usage Examples

### Processing a Payment

```php
use App\Services\Payment\PaymentService;
use App\Models\Order;

$paymentService = app(PaymentService::class);

$payment = $paymentService->processPayment(
    order: $order,
    paymentMethod: 'stripe',
    paymentData: [
        'payment_method' => 'pm_card_visa',
        'confirm' => true,
    ],
    idempotencyKey: request()->header('Idempotency-Key')
);
```

### Handling Webhooks

```php
// routes/api.php
Route::post('/webhooks/razorpay', [WebhookController::class, 'razorpay']);
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);
```

```php
// Webhook controller automatically:
// 1. Verifies signature
// 2. Prevents replay attacks
// 3. Checks for duplicates
// 4. Processes asynchronously
```

### Authentication with Sanctum

```php
// Login
POST /api/auth/login
{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "iPhone"
}

// Response
{
    "token": "1|abc123...",
    "abilities": ["user:read", "payment:read"],
    "expires_at": "2024-03-15T00:00:00Z"
}

// Use token
GET /api/payments
Authorization: Bearer 1|abc123...
```

### Rate Limiting

```php
// Apply rate limiting to routes
Route::middleware(['auth:sanctum', 'throttle:payment'])
    ->post('/payments', [PaymentController::class, 'store']);

// Custom rate limit tiers
Route::middleware(['throttle:strict'])  // 10/min, 100/hour
Route::middleware(['throttle:moderate']) // 60/min, 1000/hour
Route::middleware(['throttle:payment'])  // 5/min, 50/hour
```

## 🔒 Security Checklist

- [x] Input validation and sanitization
- [x] SQL injection prevention (Query Builder/Eloquent)
- [x] XSS prevention (Blade auto-escaping)
- [x] CSRF protection
- [x] Mass assignment protection
- [x] Secure password hashing (Argon2)
- [x] HTTPS enforcement
- [x] Security headers (CSP, HSTS, etc.)
- [x] Rate limiting
- [x] Authentication and authorization
- [x] Webhook signature verification
- [x] Idempotency for critical operations
- [x] Audit logging
- [x] Error handling without information disclosure
- [x] PCI DSS compliance considerations

## 🧪 Testing

```php
// Test payment processing
public function test_payment_processing_with_idempotency()
{
    $order = Order::factory()->create();
    $idempotencyKey = 'test_' . uniqid();
    
    // First request
    $payment1 = $this->paymentService->processPayment(
        $order,
        'razorpay',
        ['razorpay_payment_id' => 'pay_123'],
        $idempotencyKey
    );
    
    // Duplicate request with same key
    $payment2 = $this->paymentService->processPayment(
        $order,
        'razorpay',
        ['razorpay_payment_id' => 'pay_123'],
        $idempotencyKey
    );
    
    // Should return same payment
    $this->assertEquals($payment1->id, $payment2->id);
}

// Test webhook signature verification
public function test_webhook_signature_verification()
{
    $payload = json_encode(['event' => 'payment.captured']);
    $secret = config('services.razorpay.webhook_secret');
    $signature = hash_hmac('sha256', $payload, $secret);
    
    $response = $this->postJson('/api/webhooks/razorpay', 
        json_decode($payload, true),
        ['X-Razorpay-Signature' => $signature]
    );
    
    $response->assertOk();
}
```

## 📖 Related Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [PHP 8 Features](https://www.php.net/releases/8.0/en.php)
- [Razorpay API](https://razorpay.com/docs/api/)
- [Stripe API](https://stripe.com/docs/api)
- [PCI DSS Standards](https://www.pcisecuritystandards.org/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

## 🔧 Configuration

### Environment Variables

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

# Payment Gateways
RAZORPAY_KEY_ID=rzp_...
RAZORPAY_KEY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...

STRIPE_SECRET_KEY=sk_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

## 🚦 Performance Considerations

1. **Caching**: Use Redis for idempotency checks and rate limiting
2. **Database Indexes**: Composite indexes on frequently queried columns
3. **Queue Workers**: Process webhooks asynchronously
4. **Connection Pooling**: Reuse HTTP connections for gateway APIs
5. **Query Optimization**: Use eager loading, avoid N+1 queries

## 📊 Monitoring & Logging

```php
// Structured logging
Log::channel('payment')->info('Payment processed', [
    'payment_id' => $payment->id,
    'amount' => $payment->amount,
    'gateway' => $payment->payment_method,
    'duration_ms' => $duration,
]);

// Error tracking
try {
    // Process payment
} catch (\Exception $e) {
    report($e); // Sends to Sentry/Bugsnag
    Log::error('Payment failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

## 🤝 Contributing

When adding new examples:
1. Follow PSR-12 coding standards
2. Use PHP 8.1+ features where appropriate
3. Include comprehensive doc blocks
4. Add security considerations
5. Provide usage examples
6. Include tests

## 📝 License

These examples are provided for educational purposes. Always review and adapt to your specific security requirements before using in production.
