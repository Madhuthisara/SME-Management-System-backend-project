<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores payment gateway configurations per business.
     * Multiple gateways can be active simultaneously (multi-active design).
     * Credentials are stored as AES-256-CBC encrypted JSON via Laravel's 'encrypted:array' cast.
     */
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            // Supported: stripe | paypal | payhere
            $table->string('gateway_name');

            // AES-256-CBC encrypted JSON containing API keys/secrets
            // Never stored as plain text. Decrypted only in PHP via Model cast.
            $table->longText('credentials');

            // Each gateway is toggled independently — multiple can be active at once
            $table->boolean('is_active')->default(true);

            // Controls the display order on the customer checkout page
            $table->unsignedInteger('display_order')->default(0);

            // 'sandbox' for testing, 'production' for live
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');

            $table->timestamps();

            // One row per gateway per business — enforced at DB level
            $table->unique(['business_id', 'gateway_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
