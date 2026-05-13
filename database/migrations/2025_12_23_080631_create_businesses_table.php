<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void {
    Schema::create('businesses', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->string('business_name');
        $table->text('business_address');
        $table->string('business_email')->unique();
        $table->string('business_phone');
        $table->string('br_number')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
