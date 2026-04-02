# OWASP Top 10 2021 Mitigations for Laravel

## A01:2021 – Broken Access Control

### Vulnerability
Users can access resources or perform actions beyond their permissions.

### Laravel Mitigation
```php
// 1. Use Policy-Based Authorization
class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id || $user->isAdmin();
    }
    
    public function update(User $user, Payment $payment): bool
    {
        return $user->isAdmin() && $payment->status->canBeModified();
    }
}

// 2. Apply in Controllers
class PaymentController extends Controller
{
    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);
        return new PaymentResource($payment);
    }
}

// 3. Use Middleware for Route Protection
Route::middleware(['auth:sanctum', 'verified'])
    ->group(function () {
        Route::apiResource('payments', PaymentController::class);
    });

// 4. Scope Queries to Current User
class Payment extends Model
{
    public function scopeForUser(Builder $query, User $user): void
    {
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
    }
}

// Usage
$payments = Payment::forUser(auth()->user())->get();

// 5. Validate Route Model Binding
Route::bind('payment', function ($value) {
    $payment = Payment::findOrFail($value);
    
    if (auth()->user()->cannot('view', $payment)) {
        abort(403);
    }
    
    return $payment;
});
```

## A02:2021 – Cryptographic Failures

### Vulnerability
Sensitive data exposure due to weak or missing encryption.

### Laravel Mitigation
```php
// 1. Database Column Encryption
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Model
{
    protected function socialSecurityNumber(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }
}

// 2. Custom Encryption Service
class EncryptionService
{
    public function encryptSensitiveData(string $data): string
    {
        return openssl_encrypt(
            $data,
            'AES-256-CBC',
            config('app.key'),
            0,
            substr(hash('sha256', config('app.key')), 0, 16)
        );
    }
    
    public function decryptSensitiveData(string $encrypted): string
    {
        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            config('app.key'),
            0,
            substr(hash('sha256', config('app.key')), 0, 16)
        );
    }
}

// 3. Secure Password Hashing
// config/hashing.php
return [
    'driver' => 'argon2id',
    'argon' => [
        'memory' => 65536,
        'threads' => 4,
        'time' => 4,
    ],
];

// 4. TLS/SSL Enforcement
// app/Http/Middleware/ForceHttps.php
class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }
        
        return $next($request);
    }
}

// 5. Secure Cookie Settings
// config/session.php
return [
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'strict',
];
```

## A03:2021 – Injection

### Vulnerability
SQL, NoSQL, LDAP, OS command injection attacks.

### Laravel Mitigation
```php
// 1. Use Query Builder (Prevents SQL Injection)
// ✅ SAFE
$users = DB::table('users')
    ->where('email', $request->email)
    ->where('status', 'active')
    ->get();

// ❌ DANGEROUS
$users = DB::select("SELECT * FROM users WHERE email = '{$request->email}'");

// 2. Use Eloquent ORM
// ✅ SAFE
$payment = Payment::where('user_id', $userId)
    ->where('status', 'completed')
    ->first();

// 3. Parameter Binding for Raw Queries
// ✅ SAFE
$users = DB::select('SELECT * FROM users WHERE email = ? AND status = ?', [
    $request->email,
    'active'
]);

// 4. Prevent Command Injection
class FileProcessor
{
    public function processFile(string $filename): void
    {
        // ❌ DANGEROUS
        // exec("convert {$filename} output.pdf");
        
        // ✅ SAFE - Validate and escape
        $sanitizedFilename = escapeshellarg($filename);
        exec("convert {$sanitizedFilename} output.pdf");
    }
    
    public function betterApproach(string $filename): void
    {
        // Even better - use PHP libraries instead of shell commands
        // Use libraries like Imagick, GD, etc.
    }
}

// 5. NoSQL Injection Prevention (MongoDB)
// ❌ DANGEROUS
$user = User::where('email', $request->input('email'))->first();
// If input is: {"$ne": null}, it bypasses the check

// ✅ SAFE
$user = User::where('email', '=', (string) $request->input('email'))->first();

// 6. LDAP Injection Prevention
function sanitizeLDAPInput(string $input): string
{
    $sanitized = str_replace(
        ['\\', '*', '(', ')', "\x00"],
        ['\\5c', '\\2a', '\\28', '\\29', '\\00'],
        $input
    );
    
    return $sanitized;
}
```

## A04:2021 – Insecure Design

### Vulnerability
Missing or ineffective security controls in design.

### Laravel Mitigation
```php
// 1. Rate Limiting for Authentication
// app/Http/Controllers/Auth/LoginController.php
use Illuminate\Support\Facades\RateLimiter;

public function login(Request $request)
{
    $key = 'login:' . $request->ip();
    
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        return response()->json([
            'message' => "Too many attempts. Try again in {$seconds} seconds."
        ], 429);
    }
    
    // Attempt login
    if (!Auth::attempt($request->only('email', 'password'))) {
        RateLimiter::hit($key, 60);
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    
    RateLimiter::clear($key);
    return response()->json(['token' => $user->createToken('auth')->plainTextToken]);
}

// 2. Implement Account Lockout
class User extends Model
{
    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
            'failed_login_attempts' => 0,
        ]);
    }
    
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }
    
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount();
        }
    }
}

// 3. Business Logic Validation
class PaymentService
{
    public function processRefund(Payment $payment, float $amount): void
    {
        // Validate business rules
        if ($payment->status !== PaymentStatus::COMPLETED) {
            throw new BusinessLogicException('Can only refund completed payments');
        }
        
        if ($amount > $payment->amount) {
            throw new BusinessLogicException('Refund amount cannot exceed payment amount');
        }
        
        if ($payment->created_at->diffInDays(now()) > 90) {
            throw new BusinessLogicException('Refunds must be processed within 90 days');
        }
        
        // Process refund
    }
}

// 4. Secure State Transitions
class OrderStateMachine
{
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['returned'],
    ];
    
    public function transition(Order $order, string $newStatus): void
    {
        $currentStatus = $order->status;
        
        if (!in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [])) {
            throw new InvalidStateTransitionException(
                "Cannot transition from {$currentStatus} to {$newStatus}"
            );
        }
        
        $order->update(['status' => $newStatus]);
    }
}
```

## A05:2021 – Security Misconfiguration

### Vulnerability
Insecure default configurations, incomplete setups, or exposed error messages.

### Laravel Mitigation
```php
// 1. Secure .env Configuration
// .env (Production)
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:... // Generate with php artisan key:generate

LOG_LEVEL=error
LOG_STACK=daily

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

// 2. Custom Error Pages
// app/Exceptions/Handler.php
class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $this->isProduction() 
                        ? 'Server error' 
                        : $e->getMessage(),
                    'error_id' => Str::uuid(),
                ], 500);
            }
        });
    }
    
    private function isProduction(): bool
    {
        return app()->environment('production');
    }
}

// 3. Remove Sensitive Headers
// app/Http/Middleware/RemoveSensitiveHeaders.php
class RemoveSensitiveHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        
        return $response;
    }
}

// 4. Disable Directory Listing
// public/.htaccess
Options -Indexes

// 5. Secure File Permissions
chmod 644 .env
chmod 755 storage/
chmod 755 bootstrap/cache/
```

## A06:2021 – Vulnerable and Outdated Components

### Vulnerability
Using components with known vulnerabilities.

### Laravel Mitigation
```bash
# 1. Regular Dependency Updates
composer update

# 2. Security Audit
composer audit

# 3. Automated Dependency Checking
# composer.json
{
    "scripts": {
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force",
            "composer audit"
        ]
    }
}

# 4. Use Dependabot or Renovate Bot for automated PRs

# 5. Monitor Security Advisories
# - GitHub Security Advisories
# - Packagist Security Advisories
# - Laravel Security Advisories
```

```php
// Document dependency versions
// composer.lock - Always commit this file
// It ensures consistent versions across environments
```

## A07:2021 – Identification and Authentication Failures

### Vulnerability
Broken authentication, session management, or credential stuffing.

### Laravel Mitigation
```php
// 1. Implement Strong Password Policy
// app/Rules/StrongPassword.php
class StrongPassword implements Rule
{
    public function passes($attribute, $value): bool
    {
        return strlen($value) >= 12
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[a-z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[@$!%*#?&]/', $value);
    }
    
    public function message(): string
    {
        return 'Password must be at least 12 characters with uppercase, lowercase, number, and special character.';
    }
}

// 2. Multi-Factor Authentication
class TwoFactorAuthentication
{
    public function generateSecret(User $user): string
    {
        $secret = random_bytes(32);
        $user->update(['two_factor_secret' => encrypt($secret)]);
        return base32_encode($secret);
    }
    
    public function verify(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);
        $timestamp = floor(time() / 30);
        
        $validCodes = [
            $this->generateTOTP($secret, $timestamp - 1),
            $this->generateTOTP($secret, $timestamp),
            $this->generateTOTP($secret, $timestamp + 1),
        ];
        
        return in_array($code, $validCodes);
    }
    
    private function generateTOTP(string $secret, int $timestamp): string
    {
        $hash = hash_hmac('sha1', pack('N*', 0, $timestamp), $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }
}

// 3. Session Security
// config/session.php
return [
    'lifetime' => 120, // 2 hours
    'expire_on_close' => true,
    'secure' => true,
    'http_only' => true,
    'same_site' => 'strict',
];

// 4. Prevent Credential Stuffing
class LoginController
{
    public function login(Request $request)
    {
        // Check if credentials exist in breach database
        if ($this->isCompromisedPassword($request->password)) {
            return response()->json([
                'message' => 'This password has been exposed in a data breach. Please use a different password.'
            ], 422);
        }
        
        // Continue with login
    }
    
    private function isCompromisedPassword(string $password): bool
    {
        // Use haveibeenpwned.com API
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        
        $response = Http::get("https://api.pwnedpasswords.com/range/{$prefix}");
        
        return str_contains($response->body(), $suffix);
    }
}
```

## A08:2021 – Software and Data Integrity Failures

### Vulnerability
Code and infrastructure that doesn't protect against integrity violations.

### Laravel Mitigation
```php
// 1. Verify Package Integrity
// composer.json
{
    "config": {
        "secure-http": true,
        "verify-peer": true
    }
}

// 2. Implement Digital Signatures for Critical Data
class SignatureService
{
    public function sign(array $data): string
    {
        $payload = json_encode($data);
        return hash_hmac('sha256', $payload, config('app.key'));
    }
    
    public function verify(array $data, string $signature): bool
    {
        $expected = $this->sign($data);
        return hash_equals($expected, $signature);
    }
}

// 3. Validate File Uploads
class FileUploadValidator
{
    public function validateUpload(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
            throw new ValidationException('File too large');
        }
        
        // Verify MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new ValidationException('Invalid file type');
        }
        
        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($file->extension(), $allowedExtensions)) {
            throw new ValidationException('Invalid file extension');
        }
        
        // Validate file content matches extension
        $this->validateFileContent($file);
    }
    
    private function validateFileContent(UploadedFile $file): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);
        
        if ($detectedMime !== $file->getMimeType()) {
            throw new ValidationException('File content does not match declared type');
        }
    }
}

// 4. Use Subresource Integrity (SRI) for External Resources
// resources/views/layouts/app.blade.php
<script 
    src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"
    integrity="sha384-..." 
    crossorigin="anonymous">
</script>
```

## A09:2021 – Security Logging and Monitoring Failures

### Vulnerability
Insufficient logging, monitoring, or incident response.

### Laravel Mitigation
```php
// 1. Comprehensive Audit Logging
// app/Observers/PaymentObserver.php
class PaymentObserver
{
    public function created(Payment $payment): void
    {
        Log::channel('audit')->info('Payment created', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function updated(Payment $payment): void
    {
        Log::channel('audit')->info('Payment updated', [
            'payment_id' => $payment->id,
            'changes' => $payment->getChanges(),
            'user_id' => auth()->id(),
        ]);
    }
}

// 2. Security Event Monitoring
class SecurityMonitor
{
    public function logSuspiciousActivity(string $type, array $context = []): void
    {
        Log::channel('security')->warning('Suspicious activity detected', [
            'type' => $type,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'context' => $context,
            'timestamp' => now(),
        ]);
        
        // Alert security team
        if ($this->isCritical($type)) {
            $this->alertSecurityTeam($type, $context);
        }
    }
    
    private function isCritical(string $type): bool
    {
        return in_array($type, [
            'multiple_failed_logins',
            'unauthorized_access_attempt',
            'privilege_escalation_attempt',
            'sql_injection_attempt',
        ]);
    }
}

// 3. Custom Log Channels
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
    ],
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 365,
    ],
    'payment' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'level' => 'info',
    ],
],

// 4. Real-time Monitoring Integration
// config/services.php
'sentry' => [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'traces_sample_rate' => 0.2,
],
```

## A10:2021 – Server-Side Request Forgery (SSRF)

### Vulnerability
Fetching remote resources without validating user-supplied URLs.

### Laravel Mitigation
```php
// 1. Validate and Sanitize URLs
class UrlValidator
{
    private const BLOCKED_IPS = [
        '127.0.0.1',
        'localhost',
        '0.0.0.0',
        '169.254.169.254', // AWS metadata
        '::1',
    ];
    
    public function isAllowedUrl(string $url): bool
    {
        $parsed = parse_url($url);
        
        // Only allow HTTP/HTTPS
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            return false;
        }
        
        // Resolve hostname to IP
        $host = $parsed['host'] ?? '';
        $ip = gethostbyname($host);
        
        // Block private/internal IPs
        if ($this->isPrivateIp($ip) || in_array($host, self::BLOCKED_IPS)) {
            return false;
        }
        
        return true;
    }
    
    private function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}

// 2. Use Allowlist for External Requests
class ExternalApiClient
{
    private const ALLOWED_HOSTS = [
        'api.stripe.com',
        'api.razorpay.com',
        'api.paypal.com',
    ];
    
    public function fetch(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        
        if (!in_array($host, self::ALLOWED_HOSTS)) {
            throw new SecurityException('Unauthorized host');
        }
        
        return Http::timeout(5)->get($url)->body();
    }
}

// 3. Implement Request Validation Middleware
class ValidateExternalRequest
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('url')) {
            $validator = new UrlValidator();
            
            if (!$validator->isAllowedUrl($request->input('url'))) {
                return response()->json(['error' => 'Invalid URL'], 400);
            }
        }
        
        return $next($request);
    }
}
```

## Complete Security Implementation Example

```php
// app/Http/Controllers/SecurePaymentController.php
class SecurePaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private SecurityMonitor $securityMonitor,
        private AuditLogger $auditLogger
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('throttle:payment');
    }
    
    public function store(PaymentRequest $request)
    {
        // Log attempt
        $this->auditLogger->log('payment_attempt', $request->validated());
        
        try {
            DB::transaction(function () use ($request) {
                // Authorize
                $this->authorize('create', Payment::class);
                
                // Process payment
                $payment = $this->paymentService->processPayment(
                    $request->validated()
                );
                
                // Log success
                $this->auditLogger->log('payment_success', [
                    'payment_id' => $payment->id,
                ]);
                
                return new PaymentResource($payment);
            });
        } catch (\Exception $e) {
            // Log failure
            $this->securityMonitor->logSuspiciousActivity(
                'payment_failure',
                ['error' => $e->getMessage()]
            );
            
            throw $e;
        }
    }
}
```
