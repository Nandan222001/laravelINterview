<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Payment status enum with state transition validation.
 * Demonstrates PHP 8.1 backed enums with methods.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::AUTHORIZED => 'Authorized',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
        };
    }

    /**
     * Check if payment can be captured.
     */
    public function canCapture(): bool
    {
        return $this === self::AUTHORIZED;
    }

    /**
     * Check if payment can be refunded.
     */
    public function canRefund(): bool
    {
        return in_array($this, [self::COMPLETED, self::PARTIALLY_REFUNDED]);
    }

    /**
     * Check if payment is in terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
        ]);
    }

    /**
     * Validate state transition.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [
                self::AUTHORIZED,
                self::COMPLETED,
                self::FAILED,
                self::CANCELLED,
            ]),
            self::AUTHORIZED => in_array($newStatus, [
                self::COMPLETED,
                self::FAILED,
                self::CANCELLED,
            ]),
            self::COMPLETED => in_array($newStatus, [
                self::PARTIALLY_REFUNDED,
                self::REFUNDED,
            ]),
            self::PARTIALLY_REFUNDED => $newStatus === self::REFUNDED,
            default => false,
        };
    }

    /**
     * Get CSS class for UI display.
     */
    public function cssClass(): string
    {
        return match($this) {
            self::COMPLETED => 'success',
            self::PENDING, self::AUTHORIZED => 'warning',
            self::FAILED, self::CANCELLED => 'danger',
            self::REFUNDED, self::PARTIALLY_REFUNDED => 'info',
        };
    }

    /**
     * Get all statuses that allow refunds.
     */
    public static function refundableStatuses(): array
    {
        return array_filter(
            self::cases(),
            fn($status) => $status->canRefund()
        );
    }
}
