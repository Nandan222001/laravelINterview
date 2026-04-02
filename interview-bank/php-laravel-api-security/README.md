# PHP, Laravel & Advanced API Security

Comprehensive interview questions and production-ready code covering PHP 8.x features, Laravel best practices, payment gateway integration (Razorpay/Stripe), advanced API security, PCI DSS compliance, and OWASP Top 10 mitigations.

## 📚 Contents

### 📖 Questions (1000 Total)
**[questions.md](questions.md)** - Comprehensive interview questions organized in 8 sections:

1. **PHP 8.x Features (100 questions)**
   - Attributes, Enums, JIT Compiler, Fibers
   - Union Types, Intersection Types, DNF Types
   - Readonly Properties & Classes
   - Constructor Property Promotion
   - Match Expressions, Named Arguments

2. **Laravel Request Lifecycle & Architecture (100 questions)**
   - Request Lifecycle Deep-Dive
   - Middleware Pipeline & Execution Order
   - Service Container & Dependency Injection
   - Service Providers & Bootstrapping

3. **HTTP Clients & External APIs (100 questions)**
   - cURL/Guzzle Configuration & Optimization
   - SOAP API Integration
   - REST API Best Practices
   - HTTP/2 Support, Connection Pooling

4. **Payment Gateway Integration (150 questions)**
   - Razorpay: Orders, Captures, Webhooks, Refunds
   - Stripe: Payment Intents, SCA, 3D Secure
   - Idempotency Key Implementation
   - Webhook Signature Verification (HMAC-SHA256)

5. **Security & Compliance (150 questions)**
   - PCI DSS Compliance (12 Requirements)
   - OWASP Top 10 2021 Mitigations
   - Rate Limiting Strategies (Token Bucket, Sliding Window)
   - Authentication & Authorization Patterns

6. **Production-Ready Laravel Code (200 questions)**
   - Laravel Sanctum Deep-Dive
   - Queue Workers & Async Processing
   - Error Handling & Logging
   - Database Transactions & Locking
   - API Versioning & Documentation

7. **Advanced Security Patterns (100 questions)**
   - Encryption & Cryptography (AES-256, RSA)
   - API Security Headers (CSP, HSTS, CORS)
   - Input Validation & Sanitization
   - Security Testing & Penetration Testing

8. **Performance & Scalability (100 questions)**
   - Caching Strategies (Redis, Multi-layer)
   - Database Optimization (Indexing, N+1 Prevention)
   - API Performance Optimization
   - Monitoring & Observability

### 💻 Production-Ready Code Examples
**[code-examples/](code-examples/)** - Complete, tested implementations:

#### Core Services
- **PaymentService.php** - Main payment processing with idempotency and error handling
- **RazorpayService.php** - Complete Razorpay integration with webhook verification
- **StripeService.php** - Stripe Payment Intents with SCA support
- **IdempotencyService.php** - Idempotency key management with Redis caching

#### Controllers & Middleware
- **WebhookController.php** - Webhook handlers with HMAC-SHA256 verification
- **SanctumAuthController.php** - Complete authentication system with token abilities
- **SecurityHeadersMiddleware.php** - OWASP security headers implementation
- **RateLimitMiddleware.php** - Advanced rate limiting (sliding window algorithm)

#### Queue Jobs
- **ProcessPaymentWebhook.php** - Async webhook processing with retry logic

#### Request/Response
- **PaymentRequest.php** - Comprehensive input validation with security
- **PaymentResource.php** - API resource transformation with HATEOAS

#### Models & Enums
- **PaymentStatus.php** - PHP 8.1 enum with state transition validation
- **migrations/** - Database schema with proper indexes and constraints

#### Configuration & Routes
- **routes_api.php** - Complete API routes with versioning and rate limiting
- **config_services.php** - Third-party service configuration
- **.env.example** - Comprehensive environment configuration

#### Documentation
- **PHP8_Features.php** - Complete PHP 8.x features demonstration
- **PCI_DSS_Checklist.md** - Full PCI DSS compliance guide with code
- **OWASP_Top10_Mitigations.md** - Complete OWASP Top 10 mitigation strategies
- **README.md** - Detailed usage guide and examples

## 🎯 Topics Covered

### PHP 8.x Advanced Features
- ✅ Attributes (custom annotations)
- ✅ Enums (backed & pure with methods)
- ✅ JIT Compiler configuration
- ✅ Fibers for cooperative multitasking
- ✅ Union & Intersection Types
- ✅ Readonly Properties & Classes
- ✅ Constructor Property Promotion
- ✅ Match Expressions
- ✅ Named Arguments
- ✅ Nullsafe Operator

### Laravel Architecture
- ✅ Request Lifecycle Deep-Dive
- ✅ Middleware Pipeline & Execution
- ✅ Service Container Bindings
- ✅ Dependency Injection Patterns
- ✅ Service Provider Registration
- ✅ Route Model Binding
- ✅ Policy-Based Authorization

### Payment Gateway Integration
- ✅ **Razorpay**: Order creation, payment capture, webhooks, refunds
- ✅ **Stripe**: Payment Intents, SCA, 3D Secure, subscriptions
- ✅ Idempotency key implementation
- ✅ Webhook signature verification (HMAC-SHA256)
- ✅ Concurrent request handling
- ✅ Payment state machines
- ✅ Retry logic with exponential backoff

### API Security
- ✅ Laravel Sanctum authentication
- ✅ Token abilities & scopes
- ✅ Rate limiting (token bucket, sliding window)
- ✅ Security headers (CSP, HSTS, X-Frame-Options)
- ✅ CORS configuration
- ✅ CSRF protection
- ✅ XSS prevention
- ✅ SQL injection prevention
- ✅ Input validation & sanitization

### PCI DSS Compliance
- ✅ 12 PCI DSS requirements
- ✅ Tokenization strategies
- ✅ Secure card data handling
- ✅ Encryption at rest & in transit
- ✅ Access control implementation
- ✅ Audit logging requirements
- ✅ Network segmentation
- ✅ Vulnerability scanning

### OWASP Top 10 Mitigations
- ✅ A01: Broken Access Control
- ✅ A02: Cryptographic Failures
- ✅ A03: Injection
- ✅ A04: Insecure Design
- ✅ A05: Security Misconfiguration
- ✅ A06: Vulnerable Components
- ✅ A07: Authentication Failures
- ✅ A08: Software Integrity Failures
- ✅ A09: Security Logging Failures
- ✅ A10: SSRF

### Queue & Async Processing
- ✅ Job chaining & batching
- ✅ Job middleware
- ✅ Exponential backoff retry
- ✅ Failed job handling
- ✅ Queue worker configuration
- ✅ Horizon integration
- ✅ Job encryption

### Database & Performance
- ✅ Optimistic & pessimistic locking
- ✅ Transaction isolation levels
- ✅ Database query optimization
- ✅ N+1 query prevention
- ✅ Index strategies
- ✅ Connection pooling
- ✅ Redis caching patterns

## 🚀 Quick Start

### Using the Questions
```bash
# Navigate to questions
cd interview-bank/php-laravel-api-security
cat questions.md
```

### Using the Code Examples
```bash
# Navigate to code examples
cd interview-bank/php-laravel-api-security/code-examples

# Review specific implementations
cat PaymentService.php
cat RazorpayService.php
cat StripeService.php

# Check security guides
cat PCI_DSS_Checklist.md
cat OWASP_Top10_Mitigations.md

# View PHP 8.x features
cat PHP8_Features.php
```

### Implementation in Your Project
```php
// 1. Process payment with idempotency
use App\Services\Payment\PaymentService;

$paymentService = app(PaymentService::class);

$payment = $paymentService->processPayment(
    order: $order,
    paymentMethod: 'razorpay',
    paymentData: [
        'razorpay_payment_id' => 'pay_123',
        'razorpay_order_id' => 'order_456',
        'razorpay_signature' => 'signature_789',
    ],
    idempotencyKey: request()->header('Idempotency-Key')
);

// 2. Handle webhooks securely
Route::post('/webhooks/razorpay', [WebhookController::class, 'razorpay']);

// 3. Protect routes with Sanctum + abilities
Route::middleware(['auth:sanctum', 'ability:payment:write'])
    ->post('/payments', [PaymentController::class, 'store']);

// 4. Apply rate limiting
Route::middleware(['throttle:payment'])
    ->post('/payments', [PaymentController::class, 'store']);
```

## 📖 Key Features

### Idempotency Implementation
```php
// Automatic duplicate detection
$existingPayment = $idempotencyService->check($idempotencyKey);
if ($existingPayment) {
    return $existingPayment; // Return cached result
}

// Process payment only once
$payment = $paymentService->processPayment(...);

// Store for future duplicate requests
$idempotencyService->store($idempotencyKey, $payment);
```

### Webhook Signature Verification
```php
// Razorpay HMAC-SHA256 verification
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
if (!hash_equals($expectedSignature, $signature)) {
    throw new SecurityException('Invalid signature');
}

// Stripe signature with timestamp validation
$signedPayload = $timestamp . '.' . $payload;
$computedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);
if (!hash_equals($computedSignature, $expectedSignature)) {
    throw new SecurityException('Invalid signature');
}
```

### Rate Limiting with Multiple Windows
```php
// Token bucket with multiple time windows
$limits = [
    60 => 5,    // 5 per minute
    3600 => 50,  // 50 per hour
    86400 => 500, // 500 per day
];

// Sliding window algorithm with Redis
foreach ($limits as $window => $maxAttempts) {
    $attempts = $this->getAttempts($key, $window);
    if ($attempts >= $maxAttempts) {
        throw new ThrottleException();
    }
}
```

### PHP 8.1 Enums with State Transitions
```php
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::COMPLETED, self::FAILED]),
            self::COMPLETED => $newStatus === self::REFUNDED,
            default => false,
        };
    }
}
```

## 🔒 Security Best Practices

- ✅ **Never store full card data** - Use gateway tokenization
- ✅ **Always verify webhook signatures** - HMAC-SHA256 with constant-time comparison
- ✅ **Implement idempotency** - Prevent duplicate charges
- ✅ **Use rate limiting** - Protect against abuse
- ✅ **Enforce HTTPS** - TLS 1.2+ for all communications
- ✅ **Apply security headers** - CSP, HSTS, X-Frame-Options
- ✅ **Validate all inputs** - Never trust user input
- ✅ **Use prepared statements** - Prevent SQL injection
- ✅ **Log security events** - Audit trails for compliance
- ✅ **Keep dependencies updated** - Regular security patches

## 📊 What Makes This Resource Unique

1. **1000 Real-World Questions**: Not generic theory, but practical scenarios you'll face in production
2. **Production-Ready Code**: Tested, secure implementations you can use immediately
3. **Complete Payment Integration**: Full Razorpay and Stripe implementations with all edge cases
4. **Security-First Approach**: Every example follows OWASP and PCI DSS guidelines
5. **Modern PHP Features**: Extensive use of PHP 8.x features (enums, attributes, readonly, etc.)
6. **Comprehensive Documentation**: PCI DSS checklist and OWASP mitigation guides
7. **Performance Optimized**: Redis caching, connection pooling, async processing
8. **Error Handling**: Graceful degradation and comprehensive logging

## 🎓 Interview Preparation Tips

1. **Understand the Flow**: Study the complete payment lifecycle from request to webhook
2. **Practice Code**: Don't just read, implement the examples in your own projects
3. **Know the Why**: Understand why each security measure is necessary
4. **Stay Updated**: PHP and Laravel evolve; keep learning new features
5. **Security Focus**: Many senior roles focus heavily on security implementation

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [PHP 8 Release Notes](https://www.php.net/releases/8.0/en.php)
- [Razorpay API Docs](https://razorpay.com/docs/api/)
- [Stripe API Docs](https://stripe.com/docs/api)
- [PCI Security Standards](https://www.pcisecuritystandards.org/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

## 🤝 Contributing

This resource is continuously updated with new questions and examples. Suggestions for improvements are welcome!

## 📝 License

Educational use. Review and adapt security implementations for your specific requirements before production use.
