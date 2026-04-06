<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment API resource with conditional fields and security filtering.
 */
class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'amount' => $this->formatAmount($this->amount),
            'currency' => $this->currency,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'css_class' => $this->status->cssClass(),
            ],
            'payment_method' => $this->payment_method,

            // Conditionally include sensitive data
            'gateway_payment_id' => $this->when(
                $this->canViewSensitiveData($request),
                $this->gateway_payment_id
            ),

            // Include refund information if applicable
            'refund' => $this->when(
                $this->status->canRefund() || $this->refunded_amount > 0,
                [
                    'refunded_amount' => $this->formatAmount($this->refunded_amount),
                    'refund_reason' => $this->refund_reason,
                ]
            ),

            // Include error message only for failed payments
            'error_message' => $this->when(
                $this->status->value === 'failed',
                $this->error_message
            ),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Include order data if loaded
            'order' => $this->whenLoaded('order', function () {
                return new OrderResource($this->order);
            }),

            // Include metadata for admins only
            'metadata' => $this->when(
                $request->user()?->isAdmin(),
                $this->metadata
            ),

            // Include gateway response for admins only
            'gateway_response' => $this->when(
                $request->user()?->isAdmin(),
                $this->gateway_response
            ),

            // HATEOAS links
            'links' => $this->getLinks($request),
        ];
    }

    /**
     * Format amount with proper decimal places.
     */
    private function formatAmount(?float $amount): string
    {
        return number_format($amount ?? 0, 2, '.', '');
    }

    /**
     * Check if user can view sensitive payment data.
     */
    private function canViewSensitiveData(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Allow if user owns the payment or is admin
        return $this->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Get HATEOAS links.
     */
    private function getLinks(Request $request): array
    {
        $links = [
            'self' => route('payments.show', $this->id),
        ];

        // Add action links based on payment status
        if ($this->status->canRefund()) {
            $links['refund'] = route('payments.refund', $this->id);
        }

        if ($this->status->canCapture()) {
            $links['capture'] = route('payments.capture', $this->id);
        }

        if ($this->order) {
            $links['order'] = route('orders.show', $this->order_id);
        }

        return $links;
    }

    /**
     * Customize the response for the resource.
     */
    public function withResponse(Request $request, $response): void
    {
        // Add custom headers
        $response->header('X-Resource-Type', 'Payment');
        $response->header('X-Resource-Version', 'v1');
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0.0',
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}
