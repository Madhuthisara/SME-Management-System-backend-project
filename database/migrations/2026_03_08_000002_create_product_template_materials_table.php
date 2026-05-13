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
        Schema::create('product_template_materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_template_id')->constrained('product_templates')->onDelete('cascade');
            $table->string('material_id', 26); // mat_id is a ULID string
            $table->decimal('quantity', 15, 4);
            $table->timestamps();

            $table->foreign('material_id')->references('mat_id')->on('materials')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_template_materials');
    }
};
