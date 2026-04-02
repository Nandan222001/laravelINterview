# PCI DSS Compliance Checklist for Laravel Applications

## Overview
This checklist covers the 12 requirements of PCI DSS (Payment Card Industry Data Security Standard) for Laravel-based payment applications.

## Requirement 1: Install and Maintain Network Security Controls

### Firewall Configuration
- [ ] Configure firewall to restrict inbound/outbound traffic
- [ ] Implement network segmentation (CDE vs non-CDE)
- [ ] Document network diagram and data flows
- [ ] Review firewall rules quarterly
- [ ] Restrict direct internet access from CDE

### Laravel Implementation
```php
// config/cors.php - Restrict CORS origins
return [
    'paths' => ['api/*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'https://app.example.com'),
    ],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'max_age' => 3600,
];
```

## Requirement 2: Apply Secure Configurations

### Server Hardening
- [ ] Remove unnecessary services and protocols
- [ ] Change default passwords and keys
- [ ] Disable unused accounts
- [ ] Implement strong system passwords
- [ ] Keep system software updated

### Laravel Configuration
```php
// config/app.php - Production settings
return [
    'debug' => false,
    'env' => 'production',
    
    // Remove version disclosure
    'asset_url' => env('ASSET_URL'),
];

// .env - Secure configuration
APP_DEBUG=false
APP_ENV=production
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

## Requirement 3: Protect Stored Account Data

### Data Storage Rules
- [ ] **NEVER store full track data, CVV2, or PIN**
- [ ] Minimize cardholder data storage
- [ ] Render PAN unreadable (encryption/tokenization)
- [ ] Use strong cryptography
- [ ] Document data retention policies

### Laravel Implementation
```php
// DO NOT store sensitive card data
// Use payment gateway tokenization instead

class PaymentService
{
    public function processPayment(Order $order, string $token): Payment
    {
        // Store only:
        // - Last 4 digits (masked)
        // - Expiry month/year
        // - Token reference from gateway
        
        return Payment::create([
            'order_id' => $order->id,
            'card_last_four' => $this->maskCardNumber($cardNumber),
            'card_type' => $cardType,
            'gateway_token' => $token, // Gateway token, not actual card
            'amount' => $order->amount,
        ]);
    }
    
    private function maskCardNumber(string $cardNumber): string
    {
        return 'xxxx-xxxx-xxxx-' . substr($cardNumber, -4);
    }
}
```

## Requirement 4: Protect Cardholder Data with Strong Cryptography

### Encryption in Transit
- [ ] Use TLS 1.2+ for all transmissions
- [ ] Never send PAN via unsecured channels
- [ ] Implement certificate management
- [ ] Use strong encryption algorithms

### Laravel Implementation
```php
// Force HTTPS in production
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}

// config/session.php
return [
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'strict',
];
```

## Requirement 5: Protect All Systems and Networks from Malicious Software

### Anti-Malware
- [ ] Install anti-malware software
- [ ] Keep malware signatures updated
- [ ] Run periodic scans
- [ ] Generate audit logs

### Laravel Implementation
```bash
# File integrity monitoring
composer require laravel/telescope

# Dependency scanning
composer audit

# Static analysis
./vendor/bin/phpstan analyse
./vendor/bin/psalm
```

## Requirement 6: Develop and Maintain Secure Systems and Software

### Secure Development
- [ ] Implement secure coding guidelines
- [ ] Review code for vulnerabilities
- [ ] Separate development and production
- [ ] Remove test accounts before production
- [ ] Change control procedures

### Laravel Secure Coding
```php
// Prevent SQL Injection - Use Query Builder/Eloquent
Payment::where('user_id', $userId)
    ->where('status', 'completed')
    ->get();

// Prevent XSS - Blade auto-escapes
{{ $payment->description }} // Safe
{!! $payment->description !!} // Dangerous - only if needed

// Prevent CSRF - Included in forms
@csrf

// Prevent Mass Assignment
class Payment extends Model
{
    protected $guarded = ['id', 'gateway_response'];
    
    // Or use fillable
    protected $fillable = ['amount', 'status', 'order_id'];
}

// Input Validation
class PaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|in:USD,EUR,GBP,INR',
            'order_id' => 'required|exists:orders,id',
        ];
    }
}
```

## Requirement 7: Restrict Access to System Components and Cardholder Data

### Access Control
- [ ] Implement role-based access control (RBAC)
- [ ] Grant least privilege
- [ ] Assign unique IDs
- [ ] Document access rights

### Laravel Implementation
```php
// Gates and Policies
// app/Policies/PaymentPolicy.php
class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id 
            || $user->hasRole('admin');
    }
    
    public function refund(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payment.refund')
            && $payment->status->canRefund();
    }
}

// Middleware for API abilities
Route::middleware(['auth:sanctum', 'ability:payment:write'])
    ->post('/payments', [PaymentController::class, 'store']);
```

## Requirement 8: Identify Users and Authenticate Access

### Strong Authentication
- [ ] Unique user IDs
- [ ] Multi-factor authentication (MFA)
- [ ] Strong password policies
- [ ] Secure password storage (bcrypt, argon2)

### Laravel Implementation
```php
// Strong password hashing (Argon2)
// config/hashing.php
return [
    'driver' => 'argon2id',
    'argon' => [
        'memory' => 65536,
        'threads' => 4,
        'time' => 4,
    ],
];

// MFA Implementation
class TwoFactorAuthController
{
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        
        $user = $request->user();
        
        if (!$user->verifyTwoFactorCode($request->code)) {
            return response()->json(['error' => 'Invalid code'], 401);
        }
        
        $user->update(['two_factor_verified_at' => now()]);
        
        return response()->json(['message' => 'Verified']);
    }
}

// Password Policy Validation
class PasswordValidationRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return strlen($value) >= 12
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[a-z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[^A-Za-z0-9]/', $value);
    }
}
```

## Requirement 9: Restrict Physical Access

### Physical Security
- [ ] Secure server rooms and data centers
- [ ] Implement access controls
- [ ] Maintain visitor logs
- [ ] Secure physical media
- [ ] Destroy media before disposal

### Implementation Notes
- Use cloud providers with PCI DSS Level 1 certification (AWS, Azure, GCP)
- Document physical security controls
- Implement cloud access logging

## Requirement 10: Log and Monitor All Access

### Audit Logging
- [ ] Log all access to cardholder data
- [ ] Log administrative actions
- [ ] Secure log files
- [ ] Review logs daily
- [ ] Retain logs for at least 1 year

### Laravel Implementation
```php
// Custom audit logging
// app/Services/AuditLogger.php
class AuditLogger
{
    public function logPaymentAccess(User $user, Payment $payment, string $action): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'resource_type' => 'payment',
            'resource_id' => $payment->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }
}

// Log all payment operations
class PaymentController
{
    public function show(Payment $payment)
    {
        $this->auditLogger->logPaymentAccess(
            auth()->user(),
            $payment,
            'view'
        );
        
        return new PaymentResource($payment);
    }
}

// Database logging channel
// config/logging.php
'channels' => [
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 365, // Retain for 1 year
    ],
],
```

## Requirement 11: Test Security Systems and Processes

### Security Testing
- [ ] Quarterly vulnerability scans (ASV)
- [ ] Annual penetration testing
- [ ] File integrity monitoring
- [ ] Intrusion detection systems

### Laravel Testing
```php
// Security test suite
class PaymentSecurityTest extends TestCase
{
    public function test_cannot_access_other_user_payments()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $payment = Payment::factory()->create(['user_id' => $user2->id]);
        
        $response = $this->actingAs($user1)
            ->getJson("/api/payments/{$payment->id}");
        
        $response->assertForbidden();
    }
    
    public function test_sql_injection_prevention()
    {
        $response = $this->getJson("/api/payments?status='; DROP TABLE payments; --");
        
        $this->assertDatabaseHas('payments', ['id' => 1]);
    }
}
```

## Requirement 12: Support Information Security with Policies and Programs

### Security Policies
- [ ] Establish security policy
- [ ] Risk assessment annually
- [ ] Security awareness training
- [ ] Incident response plan
- [ ] Vendor management

### Documentation Requirements
- [ ] Network diagram
- [ ] Data flow diagram
- [ ] Security policies
- [ ] Acceptable use policy
- [ ] Incident response procedures

## Laravel Security Best Practices Summary

```php
// .env.example - Template for secure configuration
APP_ENV=production
APP_DEBUG=false
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Payment Gateway Credentials (Store in vault/secrets manager)
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=

STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info

# Security Headers
HSTS_ENABLED=true
CSP_ENABLED=true
```

## Quarterly Review Checklist

- [ ] Review firewall configurations
- [ ] Run vulnerability scan
- [ ] Review access control lists
- [ ] Review audit logs for anomalies
- [ ] Update security patches
- [ ] Review and update policies
- [ ] Conduct security awareness training
- [ ] Test backup restoration
- [ ] Review vendor compliance
- [ ] Update risk assessment

## Annual Requirements

- [ ] Penetration testing
- [ ] Full security assessment
- [ ] Policy review and updates
- [ ] Validate PCI DSS compliance (AOC)
- [ ] Submit SAQ (Self-Assessment Questionnaire)
- [ ] ASV scanning (if applicable)

## Notes

1. **Scope Reduction**: Use tokenization to minimize PCI scope
2. **SAQ Type**: Most Laravel e-commerce apps qualify for SAQ A-EP
3. **Compliance Level**: Based on annual transaction volume
4. **Documentation**: Keep all evidence for audits
5. **Cloud Providers**: Leverage their PCI compliance certifications

## Resources

- PCI SSC Official Site: https://www.pcisecuritystandards.org/
- Laravel Security: https://laravel.com/docs/security
- OWASP Top 10: https://owasp.org/www-project-top-ten/
