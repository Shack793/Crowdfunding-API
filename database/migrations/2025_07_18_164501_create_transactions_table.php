<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique();
            $table->enum('type', [
                'contribution', 
                'withdrawal', 
                'refund', 
                'fee', 
                'bonus',
                'transfer',
                'adjustment'
            ]);
            $table->enum('effect', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->decimal('fee', 15, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->nullableMorphs('related');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['user_id', 'type', 'status', 'created_at']);
            $table->index(['reference']);
            $table->index(['related_id', 'related_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
