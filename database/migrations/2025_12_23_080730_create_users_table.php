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
    Schema::create('users', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->string('full_name');
        $table->string('email')->unique();
        $table->string('mobile_number');
        $table->string('password');
        $table->foreignUlid('business_id')->constrained('businesses')->onDelete('cascade');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
