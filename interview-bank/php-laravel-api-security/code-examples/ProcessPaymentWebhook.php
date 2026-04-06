<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for async webhook processing with retry logic.
 */
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly string $gateway,
        private readonly array $eventData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            match ($this->gateway) {
                'razorpay' => $this->handleRazorpayEvent(),
                'stripe' => $this->handleStripeEvent(),
                default => Log::error('Unknown payment gateway', ['gateway' => $this->gateway]),
            };
        });
    }

    /**
     * Handle Razorpay webhook events.
     */
    private function handleRazorpayEvent(): void
    {
        $event = $this->eventData['event'] ?? null;
        $entity = $this->eventData['payload']['payment']['entity'] ?? null;

        if (! $event || ! $entity) {
            Log::warning('Invalid Razorpay webhook data', ['event' => $event]);

            return;
        }

        $paymentId = $entity['id'] ?? null;

        $payment = Payment::where('gateway_payment_id', $paymentId)
            ->lockForUpdate()
            ->first();

        if (! $payment) {
            Log::warning('Payment not found for Razorpay webhook', [
                'gateway_payment_id' => $paymentId,
            ]);

            return;
        }

        match ($event) {
            'payment.authorized' => $this->handlePaymentAuthorized($payment, $entity),
            'payment.captured' => $this->handlePaymentCaptured($payment, $entity),
            'payment.failed' => $this->handlePaymentFailed($payment, $entity),
            'refund.created' => $this->handleRefundCreated($payment, $entity),
            default => Log::info('Unhandled Razorpay event', ['event' => $event]),
        };
    }

    /**
     * Handle Stripe webhook events.
     */
    private function handleStripeEvent(): void
    {
        $eventType = $this->eventData['type'] ?? null;
        $object = $this->eventData['data']['object'] ?? null;

        if (! $eventType || ! $object) {
            Log::warning('Invalid Stripe webhook data', ['event_type' => $eventType]);

            return;
        }

        $paymentIntentId = $object['id'] ?? null;

        $payment = Payment::where('gateway_payment_id', $paymentIntentId)
            ->lockForUpdate()
            ->first();

        if (! $payment) {
            Log::warning('Payment not found for Stripe webhook', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        match ($eventType) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($payment, $object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($payment, $object),
            'payment_intent.canceled' => $this->handlePaymentCanceled($payment, $object),
            'charge.refunded' => $this->handleRefundCreated($payment, $object),
            default => Log::info('Unhandled Stripe event', ['event_type' => $eventType]),
        };
    }

    private function handlePaymentAuthorized(Payment $payment, array $entity): void
    {
        $payment->update([
            'status' => PaymentStatus::AUTHORIZED,
            'gateway_response' => $entity,
        ]);

        Log::info('Payment authorized', ['payment_id' => $payment->id]);
    }

    private function handlePaymentCaptured(Payment $payment, array $entity): void
    {
        $payment->update([
            'status' => PaymentStatus::COMPLETED,
            'gateway_response' => $entity,
            'completed_at' => now(),
        ]);

        Log::info('Payment captured', ['payment_id' => $payment->id]);
    }

    private function handlePaymentSucceeded(Payment $payment, array $object): void
    {
        $payment->update([
            'status' => PaymentStatus::COMPLETED,
            'gateway_response' => $object,
            'completed_at' => now(),
        ]);

        Log::info('Payment succeeded', ['payment_id' => $payment->id]);
    }

    private function handlePaymentFailed(Payment $payment, array $data): void
    {
        $payment->update([
            'status' => PaymentStatus::FAILED,
            'error_message' => $data['error_description'] ?? $data['last_payment_error']['message'] ?? 'Unknown error',
            'gateway_response' => $data,
        ]);

        Log::error('Payment failed', [
            'payment_id' => $payment->id,
            'error' => $payment->error_message,
        ]);
    }

    private function handlePaymentCanceled(Payment $payment, array $object): void
    {
        $payment->update([
            'status' => PaymentStatus::CANCELLED,
            'gateway_response' => $object,
        ]);

        Log::info('Payment canceled', ['payment_id' => $payment->id]);
    }

    private function handleRefundCreated(Payment $payment, array $data): void
    {
        $refundAmount = ($data['amount'] ?? 0) / 100;

        $payment->update([
            'status' => PaymentStatus::REFUNDED,
            'refunded_amount' => $refundAmount,
            'gateway_response' => $data,
        ]);

        Log::info('Refund processed', [
            'payment_id' => $payment->id,
            'amount' => $refundAmount,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook processing failed', [
            'gateway' => $this->gateway,
            'event_data' => $this->eventData,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
