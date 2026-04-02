<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Order;
use App\Enums\PaymentStatus;
use App\Events\PaymentProcessed;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Production-ready payment service with idempotency, error handling,
 * and comprehensive logging.
 */
class PaymentService
{
    public function __construct(
        private readonly RazorpayService $razorpayService,
        private readonly StripeService $stripeService,
        private readonly IdempotencyService $idempotencyService,
    ) {}

    /**
     * Process payment with idempotency key to prevent duplicate charges.
     */
    public function processPayment(
        Order $order,
        string $paymentMethod,
        array $paymentData,
        string $idempotencyKey
    ): Payment {
        // Check idempotency to prevent duplicate processing
        $existingPayment = $this->idempotencyService->check($idempotencyKey);
        if ($existingPayment) {
            Log::info('Duplicate payment request detected', [
                'idempotency_key' => $idempotencyKey,
                'payment_id' => $existingPayment->id,
            ]);
            return $existingPayment;
        }

        return DB::transaction(function () use ($order, $paymentMethod, $paymentData, $idempotencyKey) {
            // Lock order to prevent concurrent modifications
            $order->lockForUpdate()->first();

            // Create payment record
            $payment = Payment::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'payment_method' => $paymentMethod,
                'status' => PaymentStatus::PENDING,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $paymentData,
            ]);

            try {
                // Process payment through gateway
                $gatewayResponse = $this->processGatewayPayment(
                    $paymentMethod,
                    $payment,
                    $paymentData
                );

                // Update payment with gateway response
                $payment->update([
                    'gateway_payment_id' => $gatewayResponse['id'],
                    'status' => PaymentStatus::from($gatewayResponse['status']),
                    'gateway_response' => $gatewayResponse,
                ]);

                // Store idempotency record
                $this->idempotencyService->store($idempotencyKey, $payment);

                // Dispatch event for async processing
                event(new PaymentProcessed($payment));

                Log::info('Payment processed successfully', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'amount' => $payment->amount,
                    'gateway_payment_id' => $gatewayResponse['id'],
                ]);

                return $payment;
            } catch (\Exception $e) {
                $payment->update([
                    'status' => PaymentStatus::FAILED,
                    'error_message' => $e->getMessage(),
                ]);

                Log::error('Payment processing failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new PaymentException(
                    "Payment processing failed: {$e->getMessage()}",
                    previous: $e
                );
            }
        });
    }

    /**
     * Process refund with idempotency.
     */
    public function refundPayment(
        Payment $payment,
        float $amount,
        string $reason,
        string $idempotencyKey
    ): Payment {
        $existingRefund = $this->idempotencyService->check($idempotencyKey);
        if ($existingRefund) {
            return $existingRefund;
        }

        return DB::transaction(function () use ($payment, $amount, $reason, $idempotencyKey) {
            $payment->lockForUpdate()->first();

            if ($payment->status !== PaymentStatus::COMPLETED) {
                throw new PaymentException('Can only refund completed payments');
            }

            if ($amount > $payment->amount) {
                throw new PaymentException('Refund amount exceeds payment amount');
            }

            try {
                $gatewayResponse = match ($payment->payment_method) {
                    'razorpay' => $this->razorpayService->refund(
                        $payment->gateway_payment_id,
                        $amount,
                        $reason
                    ),
                    'stripe' => $this->stripeService->refund(
                        $payment->gateway_payment_id,
                        $amount,
                        $reason
                    ),
                    default => throw new PaymentException('Unsupported payment method'),
                };

                $payment->update([
                    'status' => PaymentStatus::REFUNDED,
                    'refunded_amount' => $amount,
                    'refund_reason' => $reason,
                ]);

                $this->idempotencyService->store($idempotencyKey, $payment);

                Log::info('Payment refunded successfully', [
                    'payment_id' => $payment->id,
                    'refund_amount' => $amount,
                    'reason' => $reason,
                ]);

                return $payment;
            } catch (\Exception $e) {
                Log::error('Refund processing failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                throw new PaymentException(
                    "Refund processing failed: {$e->getMessage()}",
                    previous: $e
                );
            }
        });
    }

    /**
     * Route payment to appropriate gateway.
     */
    private function processGatewayPayment(
        string $paymentMethod,
        Payment $payment,
        array $paymentData
    ): array {
        return match ($paymentMethod) {
            'razorpay' => $this->razorpayService->processPayment($payment, $paymentData),
            'stripe' => $this->stripeService->processPayment($payment, $paymentData),
            default => throw new PaymentException("Unsupported payment method: {$paymentMethod}"),
        };
    }

    /**
     * Verify payment status from gateway.
     */
    public function verifyPayment(Payment $payment): PaymentStatus
    {
        $gatewayStatus = match ($payment->payment_method) {
            'razorpay' => $this->razorpayService->verifyPayment($payment->gateway_payment_id),
            'stripe' => $this->stripeService->verifyPayment($payment->gateway_payment_id),
            default => throw new PaymentException('Unsupported payment method'),
        };

        if ($payment->status->value !== $gatewayStatus) {
            $payment->update(['status' => PaymentStatus::from($gatewayStatus)]);
            
            Log::warning('Payment status mismatch corrected', [
                'payment_id' => $payment->id,
                'old_status' => $payment->status->value,
                'new_status' => $gatewayStatus,
            ]);
        }

        return PaymentStatus::from($gatewayStatus);
    }
}
