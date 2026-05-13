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
        // Step 1: Try to drop the old foreign key independently
        try {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->dropForeign(['product_template_id']);
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, ignore and proceed
        }

        // Step 2: Rename the column
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->renameColumn('product_template_id', 'product_id');
        });

        // Step 3: Clean up orphaned records
        \DB::table('product_stocks')->whereNotExists(function ($query) {
            $query->select(\DB::raw(1))
                  ->from('products')
                  ->whereRaw('products.id = product_stocks.product_id');
        })->delete();

        // Step 4: Add new foreign key
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->renameColumn('product_id', 'product_template_id');
            $table->foreign('product_template_id')->references('id')->on('product_templates')->onDelete('cascade');
        });
    }
};
