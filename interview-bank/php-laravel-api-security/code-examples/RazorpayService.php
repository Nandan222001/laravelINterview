<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay payment gateway integration with webhook signature verification.
 */
class RazorpayService
{
    private const API_BASE_URL = 'https://api.razorpay.com/v1';

    private string $keyId;

    private string $keySecret;

    private string $webhookSecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id');
        $this->keySecret = config('services.razorpay.key_secret');
        $this->webhookSecret = config('services.razorpay.webhook_secret');
    }

    /**
     * Create and process Razorpay order with idempotency.
     */
    public function processPayment(Payment $payment, array $paymentData): array
    {
        // Create Razorpay order
        $order = $this->createOrder(
            $payment->amount,
            $payment->currency,
            $payment->idempotency_key
        );

        // Capture payment if payment_id provided
        if (isset($paymentData['razorpay_payment_id'])) {
            return $this->capturePayment(
                $paymentData['razorpay_payment_id'],
                $payment->amount,
                $payment->currency
            );
        }

        return [
            'id' => $order['id'],
            'status' => 'created',
            'order_data' => $order,
        ];
    }

    /**
     * Create Razorpay order with idempotency key.
     */
    public function createOrder(
        float $amount,
        string $currency,
        string $idempotencyKey
    ): array {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->withHeaders([
                    'X-Razorpay-Idempotency-Key' => $idempotencyKey,
                ])
                ->timeout(30)
                ->retry(3, 1000)
                ->post(self::API_BASE_URL.'/orders', [
                    'amount' => $amount * 100, // Convert to paise
                    'currency' => $currency,
                    'payment_capture' => 1, // Auto capture
                ]);

            if (! $response->successful()) {
                throw new PaymentGatewayException(
                    'Razorpay order creation failed: '.$response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ]);
            throw new PaymentGatewayException(
                'Failed to create Razorpay order',
                previous: $e
            );
        }
    }

    /**
     * Capture payment authorization.
     */
    public function capturePayment(
        string $paymentId,
        float $amount,
        string $currency
    ): array {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->timeout(30)
                ->retry(3, 1000)
                ->post(self::API_BASE_URL."/payments/{$paymentId}/capture", [
                    'amount' => $amount * 100,
                    'currency' => $currency,
                ]);

            if (! $response->successful()) {
                throw new PaymentGatewayException(
                    'Razorpay payment capture failed: '.$response->body()
                );
            }

            $data = $response->json();

            return [
                'id' => $data['id'],
                'status' => $data['status'],
                'method' => $data['method'],
                'captured_at' => $data['created_at'],
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay payment capture failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to capture Razorpay payment',
                previous: $e
            );
        }
    }

    /**
     * Refund payment.
     */
    public function refund(string $paymentId, float $amount, string $reason): array
    {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->timeout(30)
                ->retry(3, 1000)
                ->post(self::API_BASE_URL.'/refunds', [
                    'payment_id' => $paymentId,
                    'amount' => $amount * 100,
                    'notes' => ['reason' => $reason],
                ]);

            if (! $response->successful()) {
                throw new PaymentGatewayException(
                    'Razorpay refund failed: '.$response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Razorpay refund failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to process Razorpay refund',
                previous: $e
            );
        }
    }

    /**
     * Verify payment signature (for frontend verification).
     */
    public function verifyPaymentSignature(
        string $orderId,
        string $paymentId,
        string $signature
    ): bool {
        $expectedSignature = hash_hmac(
            'sha256',
            $orderId.'|'.$paymentId,
            $this->keySecret
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify webhook signature using HMAC-SHA256.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify payment status from gateway.
     */
    public function verifyPayment(string $paymentId): string
    {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->timeout(30)
                ->get(self::API_BASE_URL."/payments/{$paymentId}");

            if (! $response->successful()) {
                throw new PaymentGatewayException(
                    'Razorpay payment verification failed: '.$response->body()
                );
            }

            $data = $response->json();

            return $data['status'];
        } catch (\Exception $e) {
            Log::error('Razorpay payment verification failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to verify Razorpay payment',
                previous: $e
            );
        }
    }
}
