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
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUlid('category_id')->constrained('categories')->onDelete('cascade'); // Product Chain
            $table->foreignUlid('product_template_id')->nullable()->constrained('product_templates')->onDelete('set null'); // BOM
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0); // Percentage
            $table->text('thumbnail_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
