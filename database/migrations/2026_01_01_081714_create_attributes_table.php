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
            Schema::create('attributes', function (Blueprint $table) {
                // ULID Primary Key 'attribute_id'
                $table->ulid('attribute_id')->primary();
                
                // Foreign Key 'business_id' referencing 'businesses' table
                $table->foreignUlid('business_id')->constrained('businesses')->onDelete('cascade');
                
                // Name column with 100 char limit
                $table->string('name', 100);
                
                $table->timestamps();
                // Unique constraint (per business)
                $table->unique(['business_id', 'name']);
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
