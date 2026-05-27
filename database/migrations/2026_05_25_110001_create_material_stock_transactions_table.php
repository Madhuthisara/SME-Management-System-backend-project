<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_stock_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('stock_id');
            $table->ulid('supplier_id')->nullable();
            $table->string('transaction_type')->default('purchase'); // purchase | adjustment | issue
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0); // quantity * unit_cost
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_id')->references('stock_id')->on('material_stocks')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_stock_transactions');
    }
};
