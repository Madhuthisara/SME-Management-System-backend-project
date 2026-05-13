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
        Schema::create('order_items', function (Blueprint $便利) {
            $便利->ulid('id')->primary();
            $便利->ulid('order_id');
            $便利->ulid('product_id');
            $便利->integer('quantity');
            $便利->decimal('unit_price', 15, 2);
            $便利->decimal('total_price', 15, 2);
            $便利->timestamps();

            $便利->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $便利->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
