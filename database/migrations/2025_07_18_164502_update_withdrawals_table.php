<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            // Add new columns
            $table->foreignId('wallet_id')->after('user_id')->constrained()->onDelete('cascade');
            $table->decimal('fee', 15, 2)->default(0)->after('amount');
            $table->string('currency', 3)->default('GHS')->after('fee');
            $table->enum('status', [
                'pending', 
                'processing', 
                'completed', 
                'failed', 
                'cancelled'
            ])->default('pending')->change();
            
            $table->string('payment_method')->nullable()->after('status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('account_number')->nullable()->after('payment_reference');
            $table->string('account_name')->nullable()->after('account_number');
            $table->string('bank_name')->nullable()->after('account_name');
            $table->string('bank_code')->nullable()->after('bank_name');
            $table->text('narration')->nullable()->after('bank_code');
            $table->text('admin_notes')->nullable()->after('narration');
            $table->foreignId('processed_by')->nullable()->after('admin_notes')->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable()->after('processed_by');
            $table->text('rejection_reason')->nullable()->after('processed_at');
            $table->json('metadata')->nullable()->after('rejection_reason');
            
            // Rename columns to match new schema
            $table->renameColumn('internal_reference', 'system_reference');
            $table->renameColumn('transaction_reference', 'gateway_reference');
            $table->timestamp('requested_at')->nullable()->after('created_at');
            
            // Drop unused columns
            $table->dropColumn('charge');
            $table->dropColumn('external_reference');
            
            // Add indexes
            $table->index(['user_id', 'status', 'created_at']);
            $table->index('wallet_id');
            $table->index('system_reference');
            $table->index('gateway_reference');
        });
    }

    public function down()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            // Revert column changes
            $table->dropColumn([
                'wallet_id', 'fee', 'currency', 'payment_method', 
                'payment_reference', 'account_number', 'account_name',
                'bank_name', 'bank_code', 'narration', 'admin_notes',
                'processed_by', 'processed_at', 'rejection_reason', 'metadata'
            ]);
            
            $table->renameColumn('system_reference', 'internal_reference');
            $table->renameColumn('gateway_reference', 'transaction_reference');
            $table->dropColumn('requested_at');
            
            // Re-add dropped columns
            $table->decimal('charge', 15, 2)->default(0);
            $table->string('external_reference')->nullable();
            
            // Revert to original status column
            $table->string('status')->default('pending')->change();
        });
    }
};
