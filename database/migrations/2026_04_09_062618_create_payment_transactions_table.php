<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks every payment attempt and its final state.
     * gateway_txn_id is unique — used for webhook idempotency checks.
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            // Nullable: some payments may be initiated before an order is created
            $table->string('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            // Which gateway processed this transaction
            $table->string('gateway_name');

            // Gateway-specific transaction ID (e.g., Stripe PaymentIntent ID, PayHere order_id)
            // Unique index — prevents processing the same webhook twice (idempotency)
            $table->string('gateway_txn_id')->unique();

            // Amount stored as decimal; currency as ISO 4217 code (LKR, USD, etc.)
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('LKR');

            // Lifecycle: pending → completed / failed / refunded
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            // Raw gateway response snapshot for debugging/auditing
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Index for fast lookups by business + status
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
