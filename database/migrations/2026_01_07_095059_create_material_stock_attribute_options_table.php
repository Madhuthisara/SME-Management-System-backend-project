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
        Schema::create('material_stock_attribute_options', function (Blueprint $table) {
            $table->ulid('stock_id');
            $table->ulid('option_id');

            $table->primary(['stock_id', 'option_id']);
            $table->foreign('stock_id')->references('stock_id')->on('material_stocks')->onDelete('cascade');
            $table->foreign('option_id')->references('option_id')->on('attribute_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_stock_attribute_options');
    }
};
