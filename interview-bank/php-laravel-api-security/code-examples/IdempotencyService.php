<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\IdempotencyKey;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Idempotency service to prevent duplicate payment processing.
 */
class IdempotencyService
{
    private const CACHE_PREFIX = 'idempotency:';

    private const CACHE_TTL = 86400; // 24 hours

    private const KEY_EXPIRY_DAYS = 7;

    /**
     * Check if idempotency key exists and return associated payment.
     */
    public function check(string $key): ?Payment
    {
        // Check cache first for performance
        $cachedPaymentId = Cache::get(self::CACHE_PREFIX.$key);

        if ($cachedPaymentId) {
            return Payment::find($cachedPaymentId);
        }

        // Check database
        $idempotencyRecord = IdempotencyKey::where('key', $key)
            ->where('expires_at', '>', now())
            ->first();

        if ($idempotencyRecord) {
            // Warm cache
            Cache::put(
                self::CACHE_PREFIX.$key,
                $idempotencyRecord->payment_id,
                self::CACHE_TTL
            );

            return $idempotencyRecord->payment;
        }

        return null;
    }

    /**
     * Store idempotency key with associated payment.
     */
    public function store(string $key, Payment $payment): void
    {
        DB::transaction(function () use ($key, $payment) {
            IdempotencyKey::create([
                'key' => $key,
                'payment_id' => $payment->id,
                'expires_at' => Carbon::now()->addDays(self::KEY_EXPIRY_DAYS),
            ]);

            // Store in cache for fast lookups
            Cache::put(
                self::CACHE_PREFIX.$key,
                $payment->id,
                self::CACHE_TTL
            );
        });
    }

    /**
     * Validate idempotency key format.
     */
    public function validateKey(string $key): bool
    {
        // Idempotency keys should be UUIDs or similar unique identifiers
        return strlen($key) >= 16 && strlen($key) <= 255;
    }

    /**
     * Generate idempotency key for client use.
     */
    public function generateKey(string $prefix = 'idem'): string
    {
        return $prefix.'_'.bin2hex(random_bytes(16)).'_'.time();
    }

    /**
     * Clean up expired idempotency keys.
     */
    public function pruneExpired(): int
    {
        return IdempotencyKey::where('expires_at', '<', now())->delete();
    }

    /**
     * Check if request is in progress (handles concurrent requests).
     */
    public function acquireLock(string $key, int $timeout = 10): bool
    {
        $lockKey = self::CACHE_PREFIX.'lock:'.$key;

        return Cache::lock($lockKey, $timeout)->get(function () use ($key) {
            // Double-check if key exists
            return $this->check($key) === null;
        });
    }

    /**
     * Release lock after processing.
     */
    public function releaseLock(string $key): void
    {
        $lockKey = self::CACHE_PREFIX.'lock:'.$key;
        Cache::lock($lockKey)->forceRelease();
    }
}
