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
        Schema::table('product_stock_attribute_options', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['option_id']);
            
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
        Schema::table('product_stock_attribute_options', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['option_id']);
            
            $blueprint->foreign('option_id')
                ->references('id')
                ->on('attribute_options')
                ->onDelete('cascade');
        });
    }
};
