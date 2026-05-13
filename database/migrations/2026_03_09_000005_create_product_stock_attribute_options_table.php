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
        Schema::create('product_stock_attribute_options', function (Blueprint $blueprint) {
            $blueprint->ulid('stock_id');
            $blueprint->ulid('option_id');

            $blueprint->primary(['stock_id', 'option_id']);

            $blueprint->foreign('stock_id')
                ->references('id')
                ->on('product_stocks')
                ->onDelete('cascade');

            $blueprint->foreign('option_id')
                ->references('option_id')
                ->on('attribute_options')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock_attribute_options');
    }
};
