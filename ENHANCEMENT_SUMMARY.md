# PHP Laravel Interview Questions - Critical Sections Enhancement

## Summary of Enhancements

All critical sections (Q301-400, Q451-490, Q491-540, Q541-580) have been enhanced with comprehensive production-ready code examples, complete class implementations, webhook signature verification, idempotency handling, payment flow diagrams, and detailed security annotations.

## Sections Enhanced

### 1. Razorpay Integration (Q301-350)
**File:** `automation/php-laravel-data-complete.js`

**Enhancements:**
- ✅ Complete `RazorpayService` class with full implementation
- ✅ HMAC-SHA256 signature verification using `hash_equals()`
- ✅ Idempotency key handling with Redis (fast path) + Database (persistent fallback)
- ✅ Comprehensive webhook signature verification
- ✅ Database transaction atomicity
- ✅ Payment flow diagram showing: Client → Server → Gateway → Webhook → Database
- ✅ Security annotations explaining each protection mechanism
- ✅ Complete controller implementation with request validation
- ✅ Database migration for `payment_orders` table
- ✅ Audit logging on dedicated channels

**Key Security Features:**
```php
// HMAC-SHA256 with timing attack protection
$generatedSignature = hash_hmac('sha256', $payload, $secret);
$isValid = hash_equals($generatedSignature, $providedSignature);

// Redis + DB dual-layer idempotency
$cachedOrder = Redis::get("razorpay:idempotency:{$key}");
// ... with database fallback
```

**Code Examples Include:**
1. Complete service class (380+ lines)
2. Controller with proper error handling
3. Request validation classes
4. Database migrations
5. Configuration setup
6. Route definitions with middleware

---

### 2. Stripe Integration (Q351-400)
**Enhancements:**
- ✅ Complete `StripeService` with PaymentIntent API
- ✅ SCA (Strong Customer Authentication) compliance
- ✅ Stripe Elements client-side integration (PCI compliant)
- ✅ Webhook signature verification with timestamp validation
- ✅ Native Stripe idempotency key support
- ✅ Event-based webhook processing with idempotency
- ✅ Complete payment flow implementation

**Key Features:**
```php
// PaymentIntent with idempotency
$paymentIntent = $this->stripe->paymentIntents->create([
    'amount' => $amount,
    'currency' => 'usd',
    'payment_method_types' => ['card'],
], [
    'idempotency_key' => $idempotencyKey,
]);

// Webhook verification with Stripe SDK
$event = \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);
```

**Client-Side Integration:**
- Complete Stripe Elements setup
- Card element styling
- Payment confirmation flow
- Error handling

---

### 3. Idempotency Implementation (Q401-425)
**Enhancements:**
- ✅ Production-ready `IdempotencyService` class
- ✅ Redis cache (O(1) lookup, 24hr TTL)
- ✅ Database persistence (survives Redis restart)
- ✅ Distributed lock mechanism
- ✅ Double-check locking pattern
- ✅ Atomic storage with transactions

**Architecture:**
```
Layer 1: Redis Cache (fast path - 99% hits)
    ↓ Miss
Layer 2: Database (persistent storage)
    ↓ Miss
Layer 3: Acquire Lock (prevent race conditions)
    ↓
Layer 4: Double-check cache after lock
    ↓
Execute Operation → Store in DB + Redis
```

**Usage Example:**
```php
$result = $idempotency->execute(
    $idempotencyKey,
    fn() => $payment->charge($data)
);
```

---

### 4. Webhook Signature Verification (Q426-450)
**Enhancements:**
- ✅ Generic `WebhookVerificationService`
- ✅ HMAC-SHA256 signature generation
- ✅ `hash_equals()` for timing attack prevention
- ✅ Support for multiple providers (Stripe, Razorpay, GitHub)
- ✅ Timestamp validation for replay attack prevention
- ✅ Middleware implementation for centralized verification

**Multi-Provider Support:**
```php
// Stripe: Timestamped signatures
$this->verifyStripe($payload, $header, $secret);

// Razorpay: Simple HMAC
$this->verifyRazorpay($payload, $signature, $secret);

// GitHub: Prefixed signatures
$this->verifyGitHub($payload, $signature, $secret);
```

**Security Explanation:**
```php
// ✗ BAD: Vulnerable to timing attacks
if ($expected === $provided) { }

// ✓ GOOD: Constant-time comparison
if (hash_equals($expected, $provided)) { }
```

---

### 5. PCI DSS Compliance (Q451-490)
**Enhancements:**
- ✅ Complete `PCIDSSComplianceService` class
- ✅ All 12 PCI DSS requirements implemented
- ✅ Network security (HTTPS, TLS 1.2+ enforcement)
- ✅ Tokenization implementation (NEVER store card data)
- ✅ Access control with MFA requirement
- ✅ Comprehensive audit logging
- ✅ Security checks and monitoring
- ✅ Middleware for PCI DSS enforcement

**What You CANNOT Store:**
- ❌ Full PAN (card number) - store only last 4 digits
- ❌ CVV/CVV2/CVC2/CID
- ❌ Full magnetic stripe data
- ❌ PIN or PIN block

**What You CAN Store:**
- ✅ Gateway token reference
- ✅ Last 4 digits of card
- ✅ Card brand (Visa, Mastercard)
- ✅ Expiration date
- ✅ Cardholder name

**Implementation:**
```php
// Tokenization flow
$token = $gateway->createToken($cardData);

// Store ONLY non-sensitive data
DB::table('payment_methods')->insert([
    'gateway_token' => $token->id,  // Safe
    'card_last4' => substr($number, -4),  // Safe
    // NO CVV - NEVER stored
    // NO full card number - NEVER stored
]);
```

---

### 6. OWASP Top 10 Mitigations (Q491-540)
**Enhancements:**
- ✅ A01: Broken Access Control - Complete policy implementation
- ✅ A02: Cryptographic Failures - Encryption examples
- ✅ A03: Injection - SQL/Command injection prevention
- ✅ Policy-based authorization
- ✅ Mass assignment protection
- ✅ Resource ownership verification
- ✅ Security logging for unauthorized attempts

**Access Control Example:**
```php
// Policy class
class PostPolicy {
    public function update(User $user, Post $post): bool {
        return $user->id === $post->user_id || $user->hasRole('admin');
    }
}

// Controller enforcement
public function update(Request $request, Post $post) {
    $this->authorize('update', $post);  // CRITICAL
    $post->update($request->validated());
}

// Model protection
protected $guarded = ['id', 'user_id'];  // Prevent ownership takeover
```

---

### 7. Rate Limiting Strategies (Q541-580)
**Enhancements:**
- ✅ Token Bucket Algorithm implementation
- ✅ `TokenBucketRateLimiter` class with Redis
- ✅ Configurable capacity and refill rate
- ✅ Token refilling based on time elapsed
- ✅ Middleware integration
- ✅ Rate limit headers (X-RateLimit-*)
- ✅ Flow diagram showing token consumption

**Token Bucket Flow:**
```
┌─────────────────────┐
│ Bucket: 100 tokens  │
│ Refill: 10/second   │
└──────────┬──────────┘
           ↓
    Request arrives
           ↓
    Tokens available?
      Yes ↓    No → Reject (429)
    Remove token
           ↓
    Process request
```

**Implementation:**
```php
class TokenBucketRateLimiter {
    public function allow(int $tokens = 1): bool {
        // Calculate tokens to add based on time elapsed
        $tokensToAdd = ($timeElapsed / $refillTime) * $refillRate;
        $currentTokens = min($capacity, $currentTokens + $tokensToAdd);
        
        if ($currentTokens < $tokens) {
            return false;  // Rate limit exceeded
        }
        
        // Consume tokens
        Redis::hmset($key, ['tokens' => $currentTokens - $tokens]);
        return true;
    }
}
```

---

## Payment Flow Diagrams

### Complete Payment Flow with Security
```
1. CLIENT: Initiate Payment
   ↓ (HTTPS only)
2. SERVER: Create Order
   → Check idempotency (Redis → DB)
   → Call Gateway API
   → Store in DB (transaction)
   → Cache in Redis (1hr TTL)
   ↓
3. CLIENT: Initialize Checkout SDK
   ↓
4. USER: Enter Payment Details
   → Card data NEVER touches your server
   → PCI DSS handled by gateway
   ↓
5. GATEWAY: Return Signature
   → razorpay_signature (HMAC-SHA256)
   ↓
6. CLIENT: Send to Server
   ↓
7. SERVER: Verify Signature (CRITICAL)
   → hash_hmac('sha256', payload, secret)
   → hash_equals(expected, provided)
   → If valid: Update DB, trigger business logic
   → If invalid: REJECT, log security incident
   ↓
8. SERVER: Update Status (Transaction)
   ↓
9. WEBHOOK: Async Notification
   → Verify webhook signature
   → Process idempotently
   → Update payment status
```

---

## Security Mechanisms Explained

### 1. HMAC-SHA256 Signature
**Purpose:** Cryptographic proof that data hasn't been tampered with

**How it works:**
```php
// Server generates signature using secret key
$signature = hash_hmac('sha256', $data, $secretKey);

// Client cannot forge this without knowing $secretKey
```

**Why it's secure:**
- One-way cryptographic hash
- Requires secret key (never exposed to client)
- Computationally infeasible to reverse

---

### 2. hash_equals() for Timing Attack Prevention
**Purpose:** Prevent attackers from guessing signatures byte-by-byte

**The Problem:**
```php
// ✗ VULNERABLE: Stops at first mismatch
if ($expected === $provided) {
    // Fast if first byte wrong
    // Slow if all bytes match
    // Attacker can measure timing to guess bytes
}
```

**The Solution:**
```php
// ✓ SECURE: Always compares all bytes
if (hash_equals($expected, $provided)) {
    // Constant time regardless of match/mismatch
    // Prevents timing-based attacks
}
```

**Real-world impact:** Without `hash_equals()`, attackers could potentially guess a 32-character signature in ~4,000 attempts instead of 16^32 attempts.

---

### 3. Idempotency Keys
**Purpose:** Prevent duplicate charges on network retry/refresh

**Problem without idempotency:**
```
1. User clicks "Pay $100"
2. Network timeout (but payment succeeded)
3. User clicks again
4. Charged twice! $200 total
```

**Solution:**
```php
// Client generates unique key
$idempotencyKey = 'uuid-v4-...';

// Server checks if already processed
if (Redis::exists("payment:{$idempotencyKey}")) {
    return $cachedResult;  // Return original response
}

// Process payment only once
$result = processPayment();
Redis::set("payment:{$idempotencyKey}", $result, 3600);
```

---

### 4. Database Transactions
**Purpose:** Ensure atomic updates - all or nothing

**Without transactions:**
```php
// ✗ RISKY
DB::table('payments')->insert($payment);  // Succeeds
DB::table('orders')->update($order);      // Fails
// Payment recorded but order not updated!
```

**With transactions:**
```php
// ✓ ATOMIC
DB::transaction(function() {
    DB::table('payments')->insert($payment);
    DB::table('orders')->update($order);
    // If ANY fails, ALL are rolled back
});
```

---

### 5. PCI DSS Tokenization
**Purpose:** Never store sensitive card data

**Flow:**
```
1. Client: Collect card details
2. Send DIRECTLY to gateway (bypasses your server)
3. Gateway: Returns token (tok_xxxxx)
4. Your Server: Store ONLY the token
5. Future charges: Use token instead of card
```

**What gets stored:**
```php
// ✓ SAFE to store
'gateway_token' => 'tok_xxxxx',
'card_last4' => '4242',
'card_brand' => 'visa',

// ✗ NEVER store
// 'card_number' => '4242424242424242',
// 'cvv' => '123',
```

---

## File Statistics

- **Original Size:** 74,402 bytes
- **Enhanced Size:** 137,217 bytes
- **Growth:** +62,815 bytes (+84% increase)
- **Lines Added:** ~1,500+ lines of production-ready code

## Files Modified

1. `automation/php-laravel-data-complete.js` - Main interview questions database

## Testing Recommendations

Before deploying to production:

1. **Payment Integration:**
   - Test idempotency with duplicate requests
   - Verify webhook signature validation
   - Test Redis fallback to database
   - Simulate network timeouts

2. **Security:**
   - Verify hash_equals() is used everywhere
   - Test unauthorized access attempts
   - Verify audit logs are created
   - Test rate limiting with burst traffic

3. **PCI DSS:**
   - Verify NO card data in logs
   - Verify NO card data in database
   - Test HTTPS enforcement
   - Verify TLS 1.2+ only

4. **Edge Cases:**
   - Redis unavailable (DB fallback)
   - Database transaction rollback
   - Concurrent requests with same idempotency key
   - Expired idempotency keys

---

## Production Deployment Checklist

- [ ] Environment variables configured (.env):
  - RAZORPAY_KEY, RAZORPAY_SECRET, RAZORPAY_WEBHOOK_SECRET
  - STRIPE_SECRET, STRIPE_WEBHOOK_SECRET
  - REDIS_HOST, REDIS_PASSWORD
  
- [ ] Redis server running and accessible
- [ ] Database migrations executed
- [ ] Audit logging channels configured (config/logging.php)
- [ ] HTTPS enforced (middleware or load balancer)
- [ ] Rate limiting tested and tuned
- [ ] Webhook endpoints publicly accessible
- [ ] Security monitoring alerts configured

---

## Implementation Complete

All critical sections (Q301-400 Razorpay/Stripe, Q451-490 PCI DSS, Q491-540 OWASP, Q541-580 Rate Limiting) have been enhanced with:

✅ Complete production-ready class implementations  
✅ Webhook signature verification using HMAC-SHA256 with hash_equals()  
✅ Idempotency key handling with Redis/database checks  
✅ Payment flow diagrams showing client→server→gateway→webhook→database sequence  
✅ Security annotations explaining each protection mechanism  
✅ Database migrations and table schemas  
✅ Middleware implementations  
✅ Controller examples with proper error handling  
✅ Multi-provider support (Razorpay, Stripe, GitHub)  
✅ Comprehensive logging and audit trails  
✅ PCI DSS compliance guidelines  
✅ OWASP Top 10 mitigations  

**Status:** Implementation Complete ✓
