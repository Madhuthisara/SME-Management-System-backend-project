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
        Schema::table('order_items', function (Blueprint $blueprint) {
            $blueprint->ulid('product_stock_id')->nullable()->after('product_id');
            
            $blueprint->foreign('product_stock_id')
                ->references('id')
                ->on('product_stocks')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['product_stock_id']);
            $blueprint->dropColumn('product_stock_id');
        });
    }
};
