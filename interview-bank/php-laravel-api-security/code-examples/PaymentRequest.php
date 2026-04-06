<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Payment request validation with security best practices.
 */
class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can make payments
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'uuid',
                Rule::exists('orders', 'id')->where(function ($query) {
                    // Ensure order belongs to authenticated user
                    $query->where('user_id', $this->user()->id)
                        ->where('status', 'pending');
                }),
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['razorpay', 'stripe', 'paypal']),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
                'decimal:0,2',
            ],
            'currency' => [
                'required',
                'string',
                'size:3',
                'uppercase',
                Rule::in(['USD', 'EUR', 'GBP', 'INR']),
            ],
            'idempotency_key' => [
                'required',
                'string',
                'min:16',
                'max:255',
                'regex:/^[a-zA-Z0-9_\-]+$/',
            ],

            // Razorpay specific fields
            'razorpay_payment_id' => [
                'required_if:payment_method,razorpay',
                'string',
                'regex:/^pay_[a-zA-Z0-9]+$/',
            ],
            'razorpay_order_id' => [
                'required_if:payment_method,razorpay',
                'string',
                'regex:/^order_[a-zA-Z0-9]+$/',
            ],
            'razorpay_signature' => [
                'required_if:payment_method,razorpay',
                'string',
            ],

            // Stripe specific fields
            'stripe_payment_method' => [
                'required_if:payment_method,stripe',
                'string',
                'regex:/^pm_[a-zA-Z0-9]+$/',
            ],

            // Optional metadata
            'metadata' => [
                'sometimes',
                'array',
                'max:10',
            ],
            'metadata.*' => [
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'razorpay_payment_id' => 'Razorpay payment ID',
            'razorpay_order_id' => 'Razorpay order ID',
            'stripe_payment_method' => 'Stripe payment method',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_id.exists' => 'The selected order is invalid or does not belong to you.',
            'amount.decimal' => 'The amount must have at most 2 decimal places.',
            'idempotency_key.regex' => 'The idempotency key must contain only alphanumeric characters, hyphens, and underscores.',
            'razorpay_payment_id.regex' => 'Invalid Razorpay payment ID format.',
            'stripe_payment_method.regex' => 'Invalid Stripe payment method format.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize and normalize inputs
        $this->merge([
            'currency' => strtoupper($this->currency ?? ''),
            'amount' => $this->sanitizeAmount($this->amount),
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Sanitize amount input.
     */
    private function sanitizeAmount($amount): ?float
    {
        if ($amount === null) {
            return null;
        }

        // Remove any non-numeric characters except decimal point
        $sanitized = preg_replace('/[^0-9.]/', '', (string) $amount);

        // Ensure only one decimal point
        $parts = explode('.', $sanitized);
        if (count($parts) > 2) {
            $sanitized = $parts[0].'.'.implode('', array_slice($parts, 1));
        }

        return (float) $sanitized;
    }

    /**
     * Get validated data with additional computed fields.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Add computed fields
        $validated['user_id'] = $this->user()->id;
        $validated['ip_address'] = $this->ip();
        $validated['user_agent'] = $this->userAgent();

        return $validated;
    }
}
