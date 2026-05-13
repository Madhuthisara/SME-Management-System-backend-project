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
        Schema::create('product_stock_materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_stock_id')->constrained('product_stocks')->onDelete('cascade');
            $table->string('material_id', 26);
            $table->string('material_stock_id', 26);
            $table->decimal('quantity_used', 15, 4);
            $table->timestamps();

            $table->foreign('material_id')->references('mat_id')->on('materials')->onDelete('cascade');
            $table->foreign('material_stock_id')->references('stock_id')->on('material_stocks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock_materials');
    }
};
