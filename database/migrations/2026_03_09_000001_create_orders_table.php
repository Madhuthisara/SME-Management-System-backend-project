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
        Schema::create('orders', function (Blueprint $便利) {
            $便利->ulid('id')->primary();
            $便利->ulid('business_id');
            $便利->string('customer_name');
            $便利->string('phone_number');
            $便利->string('secondary_phone_number')->nullable();
            $便利->text('delivery_address');
            $便利->string('district');
            $便利->string('nearest_main_city');
            $便利->enum('source', ['whatsapp', 'messenger', 'tiktok', 'instagram', 'manual', 'other']);
            $便利->enum('payment_method', ['cod', 'bank_transfer', 'koko']);
            $便利->enum('status', ['new', 'processing', 'delivered', 'rejected', 'returned', 'exchanged'])->default('new');
            $便利->decimal('total_amount', 15, 2);
            $便利->text('notes')->nullable();
            $便利->timestamps();

            $便利->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
