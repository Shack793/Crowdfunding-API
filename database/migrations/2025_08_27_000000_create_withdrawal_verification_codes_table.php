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
        Schema::create('withdrawal_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code', 6); // 6-digit verification code
            $table->timestamp('expires_at'); // Code expiration time
            $table->boolean('used')->default(false); // Whether code has been used
            $table->string('ip_address')->nullable(); // IP address for security
            $table->string('user_agent')->nullable(); // User agent for security
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['user_id', 'code', 'used', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_verification_codes');
    }
};
