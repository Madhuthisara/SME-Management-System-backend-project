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
       Schema::create('material_attribute', function (Blueprint $table) {
        $table->id();
        $table->foreignUlid('material_id')->constrained('materials', 'mat_id')->onDelete('cascade');
        $table->foreignUlid('attribute_id')->constrained('attributes', 'attribute_id')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_attribute');
    }
};
