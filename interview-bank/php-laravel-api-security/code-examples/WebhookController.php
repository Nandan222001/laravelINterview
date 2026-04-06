<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhook;
use App\Services\Payment\RazorpayService;
use App\Services\Payment\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook controller with signature verification and async processing.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly RazorpayService $razorpayService,
        private readonly StripeService $stripeService
    ) {}

    /**
     * Handle Razorpay webhook with HMAC-SHA256 verification.
     */
    public function razorpay(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        // Verify webhook signature
        if (! $this->razorpayService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid Razorpay webhook signature', [
                'ip' => $request->ip(),
                'signature' => $signature,
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json()->all();

        // Log webhook event
        Log::info('Razorpay webhook received', [
            'event' => $event['event'] ?? 'unknown',
            'entity' => $event['entity'] ?? 'unknown',
        ]);

        // Prevent replay attacks by checking timestamp
        if ($this->isReplayAttack($event)) {
            Log::warning('Potential replay attack detected', [
                'event_id' => $event['event'] ?? null,
            ]);

            return response()->json(['error' => 'Event too old'], 400);
        }

        // Process webhook asynchronously
        ProcessPaymentWebhook::dispatch('razorpay', $event)
            ->onQueue('webhooks');

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Stripe webhook with Stripe-Signature verification.
     */
    public function stripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verify webhook signature with timestamp tolerance
        if (! $this->stripeService->verifyWebhookSignature($payload, $signature, 300)) {
            Log::warning('Invalid Stripe webhook signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json()->all();

        Log::info('Stripe webhook received', [
            'event_type' => $event['type'] ?? 'unknown',
            'event_id' => $event['id'] ?? 'unknown',
        ]);

        // Check for duplicate events
        if ($this->isDuplicateEvent($event['id'] ?? null)) {
            Log::info('Duplicate Stripe event ignored', [
                'event_id' => $event['id'],
            ]);

            return response()->json(['status' => 'duplicate']);
        }

        // Process webhook asynchronously
        ProcessPaymentWebhook::dispatch('stripe', $event)
            ->onQueue('webhooks');

        return response()->json(['status' => 'success']);
    }

    /**
     * Check if webhook is a replay attack (event older than 5 minutes).
     */
    private function isReplayAttack(array $event): bool
    {
        if (! isset($event['created_at'])) {
            return false;
        }

        $eventTime = $event['created_at'];
        $currentTime = time();
        $timeDiff = $currentTime - $eventTime;

        // Reject events older than 5 minutes
        return $timeDiff > 300;
    }

    /**
     * Check if event has already been processed.
     */
    private function isDuplicateEvent(?string $eventId): bool
    {
        if (! $eventId) {
            return false;
        }

        $cacheKey = "webhook:processed:{$eventId}";

        if (cache()->has($cacheKey)) {
            return true;
        }

        // Mark as processed for 24 hours
        cache()->put($cacheKey, true, 86400);

        return false;
    }
}
