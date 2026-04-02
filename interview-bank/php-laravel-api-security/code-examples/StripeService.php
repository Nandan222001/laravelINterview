<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\PaymentGatewayException;

/**
 * Stripe payment gateway integration with SCA support.
 */
class StripeService
{
    private const API_BASE_URL = 'https://api.stripe.com/v1';
    
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret_key');
        $this->webhookSecret = config('services.stripe.webhook_secret');
    }

    /**
     * Create and process Stripe payment intent.
     */
    public function processPayment(Payment $payment, array $paymentData): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->timeout(30)
                ->retry(3, 1000)
                ->post(self::API_BASE_URL . '/payment_intents', [
                    'amount' => $payment->amount * 100, // Convert to cents
                    'currency' => strtolower($payment->currency),
                    'payment_method' => $paymentData['payment_method'] ?? null,
                    'confirm' => $paymentData['confirm'] ?? false,
                    'metadata' => [
                        'order_id' => $payment->order_id,
                        'payment_id' => $payment->id,
                    ],
                ]);

            if (!$response->successful()) {
                throw new PaymentGatewayException(
                    'Stripe payment intent creation failed: ' . $response->body()
                );
            }

            $data = $response->json();

            return [
                'id' => $data['id'],
                'status' => $data['status'],
                'client_secret' => $data['client_secret'],
            ];
        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
            throw new PaymentGatewayException(
                'Failed to create Stripe payment intent',
                previous: $e
            );
        }
    }

    /**
     * Confirm payment intent for 3D Secure authentication.
     */
    public function confirmPayment(string $paymentIntentId, string $paymentMethod): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->timeout(30)
                ->post(self::API_BASE_URL . "/payment_intents/{$paymentIntentId}/confirm", [
                    'payment_method' => $paymentMethod,
                ]);

            if (!$response->successful()) {
                throw new PaymentGatewayException(
                    'Stripe payment confirmation failed: ' . $response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Stripe payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to confirm Stripe payment',
                previous: $e
            );
        }
    }

    /**
     * Create refund.
     */
    public function refund(string $paymentIntentId, float $amount, string $reason): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->timeout(30)
                ->retry(3, 1000)
                ->post(self::API_BASE_URL . '/refunds', [
                    'payment_intent' => $paymentIntentId,
                    'amount' => $amount * 100,
                    'reason' => $reason,
                ]);

            if (!$response->successful()) {
                throw new PaymentGatewayException(
                    'Stripe refund failed: ' . $response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Stripe refund failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to process Stripe refund',
                previous: $e
            );
        }
    }

    /**
     * Verify webhook signature using Stripe-Signature header.
     */
    public function verifyWebhookSignature(
        string $payload,
        string $signature,
        int $tolerance = 300
    ): bool {
        $signedPayload = '';
        $expectedSignature = '';

        // Parse signature header
        foreach (explode(',', $signature) as $element) {
            [$key, $value] = explode('=', $element, 2);
            
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $expectedSignature = $value;
            }
        }

        // Check timestamp tolerance to prevent replay attacks
        if (!isset($timestamp) || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        // Construct signed payload
        $signedPayload = $timestamp . '.' . $payload;
        
        // Compute expected signature
        $computedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        // Constant-time comparison
        return hash_equals($computedSignature, $expectedSignature);
    }

    /**
     * Verify payment intent status.
     */
    public function verifyPayment(string $paymentIntentId): string
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(30)
                ->get(self::API_BASE_URL . "/payment_intents/{$paymentIntentId}");

            if (!$response->successful()) {
                throw new PaymentGatewayException(
                    'Stripe payment verification failed: ' . $response->body()
                );
            }

            $data = $response->json();
            return $data['status'];
        } catch (\Exception $e) {
            Log::error('Stripe payment verification failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to verify Stripe payment',
                previous: $e
            );
        }
    }

    /**
     * Create customer for storing payment methods.
     */
    public function createCustomer(string $email, array $metadata = []): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->post(self::API_BASE_URL . '/customers', [
                    'email' => $email,
                    'metadata' => $metadata,
                ]);

            if (!$response->successful()) {
                throw new PaymentGatewayException(
                    'Stripe customer creation failed: ' . $response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Stripe customer creation failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to create Stripe customer',
                previous: $e
            );
        }
    }

    /**
     * Create setup intent for future payments.
     */
    public function createSetupIntent(string $customerId): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->post(self::API_BASE_URL . '/setup_intents', [
                    'customer' => $customerId,
                ]);

            if (!$response->successful()) {
                throw new PaymentGatewayException(
                    'Stripe setup intent creation failed: ' . $response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Stripe setup intent creation failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentGatewayException(
                'Failed to create Stripe setup intent',
                previous: $e
            );
        }
    }
}
