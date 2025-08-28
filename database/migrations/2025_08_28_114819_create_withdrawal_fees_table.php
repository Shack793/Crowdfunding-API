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
        Schema::create('withdrawal_fees', function (Blueprint $table) {
            $table->id();
            
            // User and withdrawal relationship
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('withdrawal_id')->nullable()->constrained()->onDelete('set null');
            
            // Amount details (stored in smallest currency unit - pesewas for GHS)
            $table->decimal('gross_amount', 15, 2)->comment('Original withdrawal amount requested');
            $table->decimal('fee_amount', 15, 2)->comment('Fee charged for withdrawal');
            $table->decimal('net_amount', 15, 2)->comment('Amount user actually receives');
            
            // Fee calculation details
            $table->decimal('fee_percentage', 5, 4)->comment('Fee percentage applied (e.g., 2.5000 for 2.5%)');
            $table->decimal('minimum_fee', 15, 2)->nullable()->comment('Minimum fee if applicable');
            $table->decimal('maximum_fee', 15, 2)->nullable()->comment('Maximum fee if applicable');
            
            // Transaction context
            $table->string('currency', 3)->default('GHS');
            $table->string('withdrawal_method')->comment('Method: mobile_money, bank_transfer, etc.');
            $table->string('network')->nullable()->comment('MTN, Vodafone, AirtelTigo for mobile money');
            
            // Status and tracking
            $table->enum('status', ['calculated', 'applied', 'refunded'])->default('calculated');
            $table->text('calculation_notes')->nullable()->comment('How the fee was calculated');
            
            // Audit trail
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable()->comment('Additional data: device info, location, etc.');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('withdrawal_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_fees');
    }
};
