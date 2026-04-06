<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Payment details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('payment_method', 50);
            $table->enum('status', [
                'pending',
                'authorized',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'partially_refunded',
            ])->default('pending');

            // Gateway information
            $table->string('gateway_payment_id')->nullable()->index();
            $table->json('gateway_response')->nullable();

            // Refund information
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();

            // Idempotency
            $table->string('idempotency_key')->unique();

            // Error tracking
            $table->text('error_message')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            // Request tracking
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Timestamps
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('created_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
