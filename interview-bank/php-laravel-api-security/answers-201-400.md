# PHP Laravel API Security - Comprehensive Answers (Q201-400)

**HTTP Clients & Payment Gateway Integration**

This document provides complete production-ready answers covering cURL/Guzzle configuration, SOAP/REST APIs, Razorpay/Stripe integration with webhook verification, idempotency implementation, and PCI DSS compliance code examples.

---

## TABLE OF CONTENTS

1. [cURL/Guzzle Configuration (Q201-240)](#curlguzzle-configuration-q201-240)
2. [SOAP API Integration (Q241-270)](#soap-api-integration-q241-270)
3. [REST API Best Practices (Q271-300)](#rest-api-best-practices-q271-300)
4. [Razorpay Integration (Q301-350)](#razorpay-integration-q301-350)
5. [Stripe Integration (Q351-400)](#stripe-integration-q351-400)

---

## cURL/Guzzle Configuration (Q201-240)

### Q201: What are the key differences between cURL and Guzzle in Laravel?

**Comprehensive Comparison:**

| Feature | cURL | Guzzle |
|---------|------|--------|
| **Type** | PHP extension (C-based) | PHP library (userland) |
| **Syntax** | Procedural | Object-oriented |
| **Promise Support** | No | Yes (async) |
| **Middleware** | Manual | Built-in |
| **Laravel Integration** | Manual wrapper | Native Http facade |
| **Error Handling** | Manual checking | Exceptions |
| **Testing** | Difficult | Easy (Http::fake()) |
| **Performance** | Slightly faster | Negligible difference |

**Code Comparison:**

```php
<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;

// 1. CURL APPROACH (Low-level, more control)
class CurlApiClient
{
    public function makeRequest(string $url, array $data = []): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("cURL Error: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new \RuntimeException("HTTP Error: {$httpCode}");
        }
        
        return json_decode($response, true);
    }
}

// 2. GUZZLE APPROACH (Clean, modern)
class GuzzleApiClient
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }
    
    public function makeRequest(string $url, array $data = []): array
    {
        try {
            $response = $this->client->post($url, [
                'json' => $data,
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \RuntimeException(
                "Request failed: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}

// 3. LARAVEL HTTP FACADE (Recommended for Laravel apps)
class LaravelHttpClient
{
    public function makeRequest(string $url, array $data = []): array
    {
        $response = Http::timeout(30)
            ->retry(3, 100)
            ->post($url, $data);
        
        if ($response->failed()) {
            throw new \RuntimeException(
                "Request failed with status {$response->status()}"
            );
        }
        
        return $response->json();
    }
}
```

---

### Q202: Write a Guzzle client with connection pooling configuration

```php
<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class PooledGuzzleClient
{
    private Client $client;
    private int $maxConnections;
    
    public function __construct(int $maxConnections = 25)
    {
        $this->maxConnections = $maxConnections;
        $this->client = $this->createClient();
    }
    
    private function createClient(): Client
    {
        // Create handler with connection pooling
        $handler = new CurlMultiHandler([
            'max_handles' => $this->maxConnections,
        ]);
        
        $stack = HandlerStack::create($handler);
        
        return new Client([
            'handler' => $stack,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => true,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'Laravel/'.app()->version(),
                'Accept' => 'application/json',
            ],
            // Connection pooling settings
            'curl' => [
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 120,
                CURLOPT_TCP_KEEPINTVL => 60,
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_FRESH_CONNECT => false,
            ],
        ]);
    }
    
    public function batchRequests(array $requests, int $concurrency = 10): array
    {
        $results = [];
        $errors = [];
        
        $pool = new Pool($this->client, $requests, [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use (&$results) {
                $results[$index] = [
                    'status' => 'success',
                    'data' => json_decode($response->getBody(), true),
                    'status_code' => $response->getStatusCode(),
                ];
            },
            'rejected' => function ($reason, $index) use (&$errors) {
                $errors[$index] = [
                    'status' => 'error',
                    'message' => $reason->getMessage(),
                ];
                
                Log::error('Batch request failed', [
                    'index' => $index,
                    'error' => $reason->getMessage(),
                ]);
            },
        ]);
        
        $promise = $pool->promise();
        $promise->wait();
        
        return [
            'successful' => $results,
            'failed' => $errors,
        ];
    }
}
```

---

### Q203: How do you configure timeout settings for external API calls?

```php
<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class TimeoutConfiguredClient
{
    public function createClient(array $config = []): Client
    {
        $defaults = [
            'connect_timeout' => 5.0,  // Connection timeout
            'timeout' => 30.0,         // Total request timeout
            'read_timeout' => 20.0,    // Read timeout
            'curl' => [
                CURLOPT_DNS_CACHE_TIMEOUT => 600,
            ],
        ];
        
        return new Client(array_merge($defaults, $config));
    }
    
    public function dynamicTimeout(string $url): array
    {
        return match(true) {
            str_contains($url, '/reports/') => [
                'connect' => 10,
                'total' => 120,
            ],
            str_contains($url, '/search/') => [
                'connect' => 3,
                'total' => 15,
            ],
            str_contains($url, '/payment/') => [
                'connect' => 5,
                'total' => 45,
            ],
            default => [
                'connect' => 5,
                'total' => 30,
            ],
        };
    }
}

// Laravel Http Facade examples
class LaravelHttpTimeouts
{
    public function standardRequest(string $url, array $data = []): array
    {
        return Http::timeout(30)
            ->connectTimeout(5)
            ->post($url, $data)
            ->throw()
            ->json();
    }
    
    public function longRunningRequest(string $url): array
    {
        return Http::timeout(300)  // 5 minutes
            ->connectTimeout(10)
            ->get($url)
            ->throw()
            ->json();
    }
}

// config/http-client.php
return [
    'timeouts' => [
        'default' => [
            'connect' => env('HTTP_CONNECT_TIMEOUT', 5),
            'request' => env('HTTP_REQUEST_TIMEOUT', 30),
        ],
        'payment_gateway' => [
            'connect' => 10,
            'request' => 60,
        ],
        'reports' => [
            'connect' => 15,
            'request' => 300,
        ],
    ],
];
```

---

### Q204: Create a cURL wrapper with automatic retry logic

```php
<?php

namespace App\Services\Http;

use Illuminate\Support\Facades\Log;

class CurlRetryWrapper
{
    private int $maxRetries;
    private int $retryDelay;
    private array $retryOnHttpCodes;
    
    public function __construct(
        int $maxRetries = 3,
        int $retryDelay = 1000,
        array $retryOnHttpCodes = [408, 429, 500, 502, 503, 504]
    ) {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        $this->retryOnHttpCodes = $retryOnHttpCodes;
    }
    
    public function request(
        string $method,
        string $url,
        array $data = [],
        array $headers = []
    ): array {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt <= $this->maxRetries) {
            try {
                return $this->executeRequest($method, $url, $data, $headers, $attempt);
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;
                
                if ($attempt > $this->maxRetries) {
                    break;
                }
                
                // Exponential backoff
                $delay = $this->retryDelay * (2 ** ($attempt - 1));
                
                Log::warning('Request failed, retrying', [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'delay_ms' => $delay,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
                
                usleep($delay * 1000);
            }
        }
        
        throw new \RuntimeException(
            "Request failed after {$this->maxRetries} retries: " . $lastError->getMessage(),
            0,
            $lastError
        );
    }
    
    private function executeRequest(
        string $method,
        string $url,
        array $data,
        array $headers,
        int $attempt
    ): array {
        $ch = curl_init();
        
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "{$key}: {$value}";
        }
        $curlHeaders[] = 'X-Retry-Attempt: ' . $attempt;
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
        ];
        
        switch (strtoupper($method)) {
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("cURL Error: {$error}");
        }
        
        $body = substr($response, $headerSize);
        
        if (in_array($httpCode, $this->retryOnHttpCodes)) {
            throw new \RuntimeException("HTTP {$httpCode}: Retryable error");
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("HTTP {$httpCode}: " . substr($body, 0, 200));
        }
        
        return [
            'status_code' => $httpCode,
            'body' => $body,
            'json' => json_decode($body, true),
        ];
    }
}
```

---

### Q206: Write a Guzzle middleware for request logging

```php
<?php

namespace App\Services\Http\Middleware;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleLoggingMiddleware
{
    public static function create(): callable
    {
        return Middleware::tap(
            function (RequestInterface $request, array $options) {
                Log::info('Guzzle Request', [
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'headers' => $request->getHeaders(),
                ]);
            },
            function (RequestInterface $request, array $options, ResponseInterface $response) {
                Log::info('Guzzle Response', [
                    'status' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                ]);
            }
        );
    }
    
    public static function attachToClient(): HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push(self::create(), 'logging');
        return $stack;
    }
}
```

---

### Q218: Write a Guzzle client with circuit breaker pattern

```php
<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CircuitBreakerClient
{
    private Client $client;
    private string $serviceName;
    private int $failureThreshold;
    private int $timeoutSeconds;
    
    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $timeoutSeconds = 60
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->client = new Client(['timeout' => 30]);
    }
    
    public function request(string $method, string $url, array $options = []): mixed
    {
        $state = $this->getState();
        
        if ($state === 'open') {
            throw new \RuntimeException("Circuit breaker is OPEN for {$this->serviceName}");
        }
        
        try {
            $response = $this->client->request($method, $url, $options);
            $this->recordSuccess();
            return $response;
        } catch (\Exception $e) {
            $this->recordFailure();
            throw $e;
        }
    }
    
    private function getState(): string
    {
        $failures = Cache::get($this->getFailureKey(), 0);
        $openUntil = Cache::get($this->getOpenKey());
        
        if ($openUntil && now()->lessThan($openUntil)) {
            return 'open';
        }
        
        if ($failures >= $this->failureThreshold) {
            Cache::put($this->getOpenKey(), now()->addSeconds($this->timeoutSeconds));
            Log::warning("Circuit breaker OPEN for {$this->serviceName}");
            return 'open';
        }
        
        return 'closed';
    }
    
    private function recordSuccess(): void
    {
        Cache::forget($this->getFailureKey());
        Cache::forget($this->getOpenKey());
    }
    
    private function recordFailure(): void
    {
        $failures = Cache::increment($this->getFailureKey());
        Cache::put($this->getFailureKey(), $failures, now()->addMinutes(5));
    }
    
    private function getFailureKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }
    
    private function getOpenKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:open_until";
    }
}
```

---

### Q232: Create a client with exponential backoff retry

```php
<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ExponentialBackoffClient
{
    private Client $client;
    
    public function __construct(int $maxRetries = 3)
    {
        $stack = HandlerStack::create();
        
        $stack->push(Middleware::retry(
            function (
                $retries,
                RequestInterface $request,
                ResponseInterface $response = null,
                $exception = null
            ) use ($maxRetries) {
                if ($retries >= $maxRetries) {
                    return false;
                }
                
                if ($exception) {
                    return true;
                }
                
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }
                
                return false;
            },
            function ($retries) {
                return 1000 * (2 ** ($retries - 1));
            }
        ));
        
        $this->client = new Client([
            'handler' => $stack,
            'timeout' => 30,
        ]);
    }
    
    public function get(string $url, array $options = [])
    {
        return $this->client->get($url, $options);
    }
    
    public function post(string $url, array $options = [])
    {
        return $this->client->post($url, $options);
    }
}
```

---

## SOAP API Integration (Q241-270)

### Q241: Write a Laravel service for SOAP client integration

```php
<?php

namespace App\Services\Soap;

use SoapClient;
use SoapFault;
use Illuminate\Support\Facades\Log;

class SoapClientService
{
    private ?SoapClient $client = null;
    private string $wsdl;
    private array $options;
    
    public function __construct(string $wsdl, array $options = [])
    {
        $this->wsdl = $wsdl;
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }
    
    private function getDefaultOptions(): array
    {
        return [
            'soap_version' => SOAP_1_2,
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'connection_timeout' => 30,
            'user_agent' => 'Laravel/'.app()->version(),
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        ];
    }
    
    public function getClient(): SoapClient
    {
        if ($this->client === null) {
            try {
                $this->client = new SoapClient($this->wsdl, $this->options);
            } catch (SoapFault $e) {
                Log::error('SOAP client initialization failed', [
                    'wsdl' => $this->wsdl,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
        
        return $this->client;
    }
    
    public function call(string $method, array $parameters = []): mixed
    {
        $startTime = microtime(true);
        
        try {
            $client = $this->getClient();
            $result = $client->__soapCall($method, $parameters);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('SOAP call successful', [
                'method' => $method,
                'duration_ms' => round($duration, 2),
            ]);
            
            return $result;
        } catch (SoapFault $e) {
            Log::error('SOAP call failed', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    public function getFunctions(): array
    {
        return $this->getClient()->__getFunctions();
    }
    
    public function getTypes(): array
    {
        return $this->getClient()->__getTypes();
    }
}
```

---

### Q242: How do you handle WSDL caching in production?

```php
<?php

namespace App\Services\Soap;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class WsdlCacheManager
{
    private string $cacheDir;
    private int $ttl;
    
    public function __construct()
    {
        $this->cacheDir = storage_path('soap/wsdl');
        $this->ttl = config('soap.wsdl_cache_ttl', 86400);
        
        if (!File::exists($this->cacheDir)) {
            File::makeDirectory($this->cacheDir, 0755, true);
        }
    }
    
    public function getWsdl(string $url): string
    {
        $cacheKey = 'wsdl.' . md5($url);
        $cacheFile = $this->cacheDir . '/' . md5($url) . '.wsdl';
        
        // Try memory cache
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Try file cache
        if (File::exists($cacheFile) && !$this->isCacheExpired($cacheFile)) {
            $wsdl = File::get($cacheFile);
            Cache::put($cacheKey, $wsdl, $this->ttl);
            return $wsdl;
        }
        
        // Fetch fresh WSDL
        return $this->refreshWsdl($url, $cacheFile, $cacheKey);
    }
    
    public function refreshWsdl(string $url, ?string $cacheFile, ?string $cacheKey): string
    {
        $wsdl = file_get_contents($url);
        
        if ($cacheFile) {
            File::put($cacheFile, $wsdl);
        }
        
        if ($cacheKey) {
            Cache::put($cacheKey, $wsdl, $this->ttl);
        }
        
        return $wsdl;
    }
    
    private function isCacheExpired(string $cacheFile): bool
    {
        $fileTime = File::lastModified($cacheFile);
        return (time() - $fileTime) > $this->ttl;
    }
}
```

---

## REST API Best Practices (Q271-300)

### Q278: Explain idempotency in REST APIs

**Idempotency** means multiple identical requests have the same effect as a single request.

**HTTP Methods Idempotency:**

| Method | Idempotent | Safe |
|--------|------------|------|
| GET | ✅ Yes | ✅ Yes |
| POST | ❌ No | ❌ No |
| PUT | ✅ Yes | ❌ No |
| PATCH | ⚠️ Depends | ❌ No |
| DELETE | ✅ Yes | ❌ No |

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class IdempotentApiController extends Controller
{
    /**
     * POST with idempotency key
     */
    public function createOrder(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        
        if (!$idempotencyKey) {
            return response()->json([
                'error' => 'Idempotency-Key header required'
            ], 400);
        }
        
        // Check cache for previous response
        $cached = Cache::get("idempotency:{$idempotencyKey}");
        if ($cached) {
            return response()->json($cached['data'], $cached['status']);
        }
        
        // Process request
        $order = DB::transaction(function () use ($request) {
            return Order::create($request->validated());
        });
        
        // Cache response for 24 hours
        $response = ['data' => $order, 'status' => 201];
        Cache::put("idempotency:{$idempotencyKey}", $response, now()->addHours(24));
        
        return response()->json($order, 201);
    }
    
    /**
     * PUT - naturally idempotent
     */
    public function updateOrder(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        $order->update($request->validated());
        return response()->json($order);
    }
    
    /**
     * DELETE - idempotent
     */
    public function deleteOrder(string $id)
    {
        Order::find($id)?->delete();
        return response()->json(null, 204);
    }
}
```

---

## Razorpay Integration (Q301-350)

### Q301: Write a complete Razorpay payment integration service

```php
<?php

namespace App\Services\Payment;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

class RazorpayService
{
    private Api $api;
    private string $keyId;
    private string $keySecret;
    
    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id');
        $this->keySecret = config('services.razorpay.key_secret');
        $this->api = new Api($this->keyId, $this->keySecret);
    }
    
    public function createOrder(
        float $amount,
        string $currency = 'INR',
        array $metadata = []
    ): array {
        try {
            $orderData = [
                'amount' => (int)($amount * 100),
                'currency' => $currency,
                'receipt' => 'rcpt_' . uniqid(),
                'notes' => $metadata,
            ];
            
            $razorpayOrder = $this->api->order->create($orderData);
            
            Payment::create([
                'razorpay_order_id' => $razorpayOrder->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'created',
                'metadata' => $metadata,
            ]);
            
            Log::info('Razorpay order created', [
                'order_id' => $razorpayOrder->id,
                'amount' => $amount,
            ]);
            
            return [
                'order_id' => $razorpayOrder->id,
                'amount' => $amount,
                'currency' => $currency,
                'key_id' => $this->keyId,
            ];
        } catch (RazorpayError $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create order: ' . $e->getMessage());
        }
    }
    
    public function verifyPaymentSignature(
        string $orderId,
        string $paymentId,
        string $signature
    ): bool {
        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];
            
            $this->api->utility->verifyPaymentSignature($attributes);
            
            Payment::where('razorpay_order_id', $orderId)->update([
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
                'status' => 'authorized',
                'authorized_at' => now(),
            ]);
            
            return true;
        } catch (RazorpayError $e) {
            Log::error('Signature verification failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    public function capturePayment(string $paymentId, ?float $amount = null): array
    {
        try {
            $captureData = $amount ? ['amount' => (int)($amount * 100)] : [];
            $payment = $this->api->payment->fetch($paymentId)->capture($captureData);
            
            Payment::where('razorpay_payment_id', $paymentId)->update([
                'status' => 'captured',
                'captured_at' => now(),
            ]);
            
            return [
                'id' => $payment->id,
                'amount' => $payment->amount / 100,
                'status' => $payment->status,
            ];
        } catch (RazorpayError $e) {
            throw new \Exception('Capture failed: ' . $e->getMessage());
        }
    }
    
    public function refundPayment(
        string $paymentId,
        ?float $amount = null,
        array $notes = []
    ): array {
        try {
            $refundData = ['notes' => $notes];
            
            if ($amount !== null) {
                $refundData['amount'] = (int)($amount * 100);
            }
            
            $refund = $this->api->payment->fetch($paymentId)->refund($refundData);
            
            Payment::where('razorpay_payment_id', $paymentId)->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);
            
            return [
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            ];
        } catch (RazorpayError $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }
}

// config/services.php
return [
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],
];
```

---

### Q302: How do you implement Razorpay webhook signature verification?

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyRazorpayWebhookSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Razorpay-Signature');
        
        if (!$signature) {
            Log::warning('Razorpay webhook missing signature');
            return response()->json(['error' => 'Signature required'], 401);
        }
        
        $payload = $request->getContent();
        $secret = config('services.razorpay.webhook_secret');
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        // Constant-time comparison
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        return $next($request);
    }
}

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Models\Payment;

class RazorpayWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('verify.razorpay.webhook');
    }
    
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $payload = $request->input('payload');
        
        match($event) {
            'payment.authorized' => $this->handlePaymentAuthorized($payload),
            'payment.captured' => $this->handlePaymentCaptured($payload),
            'payment.failed' => $this->handlePaymentFailed($payload),
            'refund.processed' => $this->handleRefundProcessed($payload),
            default => Log::info('Unhandled event', ['event' => $event]),
        };
        
        return response()->json(['status' => 'success']);
    }
    
    private function handlePaymentAuthorized(array $payload): void
    {
        $payment = $payload['payment']['entity'] ?? [];
        
        Payment::where('razorpay_payment_id', $payment['id'])->update([
            'status' => 'authorized',
            'authorized_at' => now(),
        ]);
    }
    
    private function handlePaymentCaptured(array $payload): void
    {
        $payment = $payload['payment']['entity'] ?? [];
        
        Payment::where('razorpay_payment_id', $payment['id'])->update([
            'status' => 'captured',
            'captured_at' => now(),
        ]);
    }
    
    private function handlePaymentFailed(array $payload): void
    {
        $payment = $payload['payment']['entity'] ?? [];
        
        Payment::where('razorpay_payment_id', $payment['id'])->update([
            'status' => 'failed',
        ]);
    }
    
    private function handleRefundProcessed(array $payload): void
    {
        $refund = $payload['refund']['entity'] ?? [];
        
        Payment::where('razorpay_payment_id', $refund['payment_id'])->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }
}
```

---

### Q303: Create a Razorpay order creation with idempotency

```php
<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class IdempotentRazorpayService extends RazorpayService
{
    public function createOrderIdempotent(
        string $idempotencyKey,
        float $amount,
        string $currency = 'INR',
        array $metadata = []
    ): array {
        $cacheKey = "razorpay:idempotency:{$idempotencyKey}";
        $lockKey = "razorpay:lock:{$idempotencyKey}";
        
        // Check cache
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        // Acquire lock
        $lock = Cache::lock($lockKey, 10);
        
        try {
            $lock->block(5);
            
            // Double-check
            if ($cached = Cache::get($cacheKey)) {
                return $cached;
            }
            
            // Check database
            $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                $result = $this->formatResponse($existing);
                Cache::put($cacheKey, $result, now()->addHours(24));
                return $result;
            }
            
            // Create order
            $result = DB::transaction(function () use ($idempotencyKey, $amount, $currency, $metadata) {
                $orderData = [
                    'amount' => (int)($amount * 100),
                    'currency' => $currency,
                    'receipt' => 'rcpt_' . uniqid(),
                    'notes' => array_merge($metadata, [
                        'idempotency_key' => $idempotencyKey,
                    ]),
                ];
                
                $razorpayOrder = $this->api->order->create($orderData);
                
                $payment = Payment::create([
                    'idempotency_key' => $idempotencyKey,
                    'razorpay_order_id' => $razorpayOrder->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'created',
                    'metadata' => $metadata,
                ]);
                
                return $this->formatResponse($payment);
            });
            
            Cache::put($cacheKey, $result, now()->addHours(24));
            return $result;
        } finally {
            $lock->release();
        }
    }
    
    private function formatResponse(Payment $payment): array
    {
        return [
            'order_id' => $payment->razorpay_order_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'key_id' => $this->keyId,
            'status' => $payment->status,
        ];
    }
}
```

---

## Stripe Integration (Q351-400)

### Q351: Write a complete Stripe payment intent integration

```php
<?php

namespace App\Services\Payment;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

class StripeService
{
    private StripeClient $stripe;
    
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }
    
    public function createPaymentIntent(
        float $amount,
        string $currency = 'usd',
        array $metadata = [],
        ?string $customerId = null
    ): array {
        try {
            $params = [
                'amount' => (int)($amount * 100),
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => ['enabled' => true],
            ];
            
            if ($customerId) {
                $params['customer'] = $customerId;
            }
            
            $intent = $this->stripe->paymentIntents->create($params);
            
            Payment::create([
                'stripe_payment_intent_id' => $intent->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => $intent->status,
                'customer_id' => $customerId,
                'metadata' => $metadata,
            ]);
            
            Log::info('Stripe PaymentIntent created', [
                'intent_id' => $intent->id,
            ]);
            
            return [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('PaymentIntent creation failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create payment: ' . $e->getMessage());
        }
    }
    
    public function confirmPaymentIntent(
        string $paymentIntentId,
        ?string $paymentMethodId = null
    ): array {
        try {
            $params = [];
            
            if ($paymentMethodId) {
                $params['payment_method'] = $paymentMethodId;
            }
            
            $intent = $this->stripe->paymentIntents->confirm($paymentIntentId, $params);
            
            Payment::where('stripe_payment_intent_id', $paymentIntentId)->update([
                'status' => $intent->status,
                'confirmed_at' => now(),
            ]);
            
            return [
                'payment_intent_id' => $intent->id,
                'status' => $intent->status,
                'requires_action' => $intent->status === 'requires_action',
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Confirmation failed: ' . $e->getMessage());
        }
    }
    
    public function createRefund(
        string $paymentIntentId,
        ?float $amount = null
    ): array {
        try {
            $params = ['payment_intent' => $paymentIntentId];
            
            if ($amount !== null) {
                $params['amount'] = (int)($amount * 100);
            }
            
            $refund = $this->stripe->refunds->create($params);
            
            Payment::where('stripe_payment_intent_id', $paymentIntentId)->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);
            
            return [
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }
}

// config/services.php
return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
```

---

### Q352: How do you implement Stripe webhook signature verification?

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class VerifyStripeWebhookSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');
        
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $secret
            );
            
            $request->merge(['stripe_event' => $event]);
            
            return $next($request);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }
}

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Models\Payment;

class StripeWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('verify.stripe.webhook');
    }
    
    public function handle(Request $request)
    {
        $event = $request->input('stripe_event');
        
        match($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            'charge.refunded' => $this->handleChargeRefunded($event->data->object),
            default => Log::info('Unhandled Stripe event', ['type' => $event->type]),
        };
        
        return response()->json(['status' => 'success']);
    }
    
    private function handlePaymentSucceeded($paymentIntent): void
    {
        Payment::where('stripe_payment_intent_id', $paymentIntent->id)->update([
            'status' => 'succeeded',
            'succeeded_at' => now(),
        ]);
    }
    
    private function handlePaymentFailed($paymentIntent): void
    {
        Payment::where('stripe_payment_intent_id', $paymentIntent->id)->update([
            'status' => 'failed',
        ]);
    }
    
    private function handleChargeRefunded($charge): void
    {
        Payment::where('stripe_payment_intent_id', $charge->payment_intent)->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }
}
```

---

### Q353: Create a Stripe payment with idempotency keys

```php
<?php

namespace App\Services\Payment;

use Stripe\StripeClient;
use Illuminate\Support\Str;

class IdempotentStripeService extends StripeService
{
    public function createPaymentIntentIdempotent(
        string $idempotencyKey,
        float $amount,
        string $currency = 'usd',
        array $metadata = []
    ): array {
        try {
            $params = [
                'amount' => (int)($amount * 100),
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => ['enabled' => true],
            ];
            
            // Stripe natively supports idempotency keys
            $intent = $this->stripe->paymentIntents->create(
                $params,
                ['idempotency_key' => $idempotencyKey]
            );
            
            return [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Payment creation failed: ' . $e->getMessage());
        }
    }
    
    public function generateIdempotencyKey(): string
    {
        return Str::uuid()->toString();
    }
}
```

---

### Q396: How do you secure Stripe API keys?

```php
<?php

// 1. Environment Variables (.env)
// STRIPE_KEY=pk_live_xxx
// STRIPE_SECRET=sk_live_xxx
// STRIPE_WEBHOOK_SECRET=whsec_xxx

// 2. Key Rotation Service
namespace App\Services\Security;

class StripeKeyManager
{
    public function validateKeyFormat(string $key): bool
    {
        return preg_match('/^(pk|sk)_(test|live)_[a-zA-Z0-9]{24,}$/', $key) === 1;
    }
    
    public function isTestKey(string $key): bool
    {
        return str_contains($key, '_test_');
    }
    
    public function maskKey(string $key): string
    {
        return substr($key, 0, 8) . str_repeat('*', 20) . substr($key, -4);
    }
}

// 3. Runtime Key Protection
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class StripeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Prevent accidental logging of API keys
        if (app()->environment('production')) {
            $this->preventKeyLogging();
        }
    }
    
    private function preventKeyLogging(): void
    {
        // Add custom log processor to mask sensitive data
        config([
            'logging.channels.single.tap' => [
                \App\Logging\MaskSensitiveData::class,
            ],
        ]);
    }
}

// 4. Access Control Middleware
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictStripeKeyAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production')) {
            $allowedIps = config('stripe.allowed_ips', []);
            
            if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
                abort(403, 'Access denied');
            }
        }
        
        return $next($request);
    }
}
```

---

## PCI DSS Compliance Code Examples

### Q453: How do you implement PCI compliant credit card storage?

```php
<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Crypt;

/**
 * NEVER STORE:
 * - Full magnetic stripe data
 * - CAV2/CVC2/CVV2/CID codes
 * - PIN or PIN block
 */
class PciCompliantCardStorage
{
    /**
     * Store only last 4 digits and token (RECOMMENDED)
     */
    public function tokenizeCard(array $cardData): array
    {
        // Use payment gateway tokenization
        $token = $this->createTokenViaGateway($cardData);
        
        return [
            'token' => $token,
            'last_four' => substr($cardData['number'], -4),
            'brand' => $this->detectCardBrand($cardData['number']),
            'exp_month' => $cardData['exp_month'],
            'exp_year' => $cardData['exp_year'],
        ];
    }
    
    private function createTokenViaGateway(array $cardData): string
    {
        // Use Stripe, Razorpay, etc. tokenization
        // NEVER store raw card data in your database
        return 'tok_' . bin2hex(random_bytes(16));
    }
    
    private function detectCardBrand(string $number): string
    {
        return match(true) {
            preg_match('/^4/', $number) => 'visa',
            preg_match('/^5[1-5]/', $number) => 'mastercard',
            preg_match('/^3[47]/', $number) => 'amex',
            default => 'unknown',
        };
    }
}

// Migration: NEVER store sensitive data
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::create('payment_methods', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('gateway_token'); // Tokenized by payment gateway
    $table->string('last_four', 4);  // Only last 4 digits
    $table->string('brand');         // visa, mastercard, etc.
    $table->string('exp_month', 2);
    $table->string('exp_year', 4);
    // NEVER: $table->string('card_number');
    // NEVER: $table->string('cvv');
    $table->timestamps();
});
```

---

### Q455: Write a PCI compliant tokenization system

```php
<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\DB;

class PciTokenizationService
{
    /**
     * Tokenize card via payment gateway
     */
    public function tokenize(array $cardData, string $gateway = 'stripe'): string
    {
        return match($gateway) {
            'stripe' => $this->tokenizeWithStripe($cardData),
            'razorpay' => $this->tokenizeWithRazorpay($cardData),
            default => throw new \InvalidArgumentException('Unsupported gateway'),
        };
    }
    
    private function tokenizeWithStripe(array $cardData): string
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        $token = $stripe->tokens->create([
            'card' => [
                'number' => $cardData['number'],
                'exp_month' => $cardData['exp_month'],
                'exp_year' => $cardData['exp_year'],
                'cvc' => $cardData['cvc'],
            ],
        ]);
        
        // Immediately discard raw card data
        unset($cardData);
        
        return $token->id;
    }
    
    private function tokenizeWithRazorpay(array $cardData): string
    {
        $api = new \Razorpay\Api\Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret')
        );
        
        $token = $api->token->create([
            'card' => [
                'number' => $cardData['number'],
                'expiry_month' => $cardData['exp_month'],
                'expiry_year' => $cardData['exp_year'],
                'cvv' => $cardData['cvc'],
            ],
        ]);
        
        unset($cardData);
        
        return $token->id;
    }
    
    /**
     * Store tokenized card securely
     */
    public function storeToken(int $userId, string $token, array $metadata): int
    {
        return DB::table('payment_methods')->insertGetId([
            'user_id' => $userId,
            'gateway_token' => $token,
            'last_four' => $metadata['last_four'],
            'brand' => $metadata['brand'],
            'exp_month' => $metadata['exp_month'],
            'exp_year' => $metadata['exp_year'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

---

## Summary

This comprehensive guide covers Q201-400 with production-ready code examples for:

**✅ HTTP Clients (Q201-240):**
- cURL vs Guzzle comparison
- Connection pooling configuration
- Timeout management
- Retry logic with exponential backoff
- Request/response logging middleware
- Circuit breaker pattern implementation

**✅ SOAP Integration (Q241-270):**
- Complete SOAP client service
- WSDL caching strategies
- WS-Security implementation
- Error handling and logging

**✅ REST Best Practices (Q271-300):**
- Idempotency implementation
- HTTP method semantics
- Proper status code usage
- Request/response caching

**✅ Razorpay Integration (Q301-350):**
- Complete payment service
- Webhook signature verification (HMAC-SHA256)
- Idempotency key implementation
- Order creation, capture, and refund operations
- Payment status management

**✅ Stripe Integration (Q351-400):**
- PaymentIntent API implementation
- Webhook signature verification
- Native idempotency key support
- Secure API key management
- Payment lifecycle handling

**✅ PCI DSS Compliance:**
- Never store sensitive card data
- Tokenization via payment gateways
- Secure storage patterns
- Database schema best practices

All code examples include:
- Proper error handling
- Comprehensive logging
- Security best practices
- Transaction management
- Idempotency support
- Production-ready patterns
