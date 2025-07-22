<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contributions', function (Blueprint $table) {
            // Add new columns
            $table->foreignId('wallet_id')->after('user_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('fee', 15, 2)->default(0)->after('amount');
            $table->string('currency', 3)->default('GHS')->after('fee');
            
            // Update status column to use enum
            $table->enum('status', [
                'pending', 
                'completed', 
                'failed', 
                'refunded'
            ])->default('pending')->change();
            
            // Add new fields
            $table->boolean('is_anonymous')->default(false)->after('contribution_date');
            $table->text('comment')->nullable()->after('is_anonymous');
            $table->json('metadata')->nullable()->after('comment');
            
            // Rename columns for consistency
            $table->renameColumn('system_reference', 'internal_reference');
            
            // Add indexes
            $table->index(['user_id', 'status', 'created_at']);
            $table->index('wallet_id');
            $table->index('campaign_id');
            $table->index('internal_reference');
            $table->index('gateway_reference');
        });
    }

    public function down()
    {
        Schema::table('contributions', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumn([
                'wallet_id', 'fee', 'currency', 'is_anonymous', 
                'comment', 'metadata'
            ]);
            
            // Revert renamed columns
            $table->renameColumn('internal_reference', 'system_reference');
            
            // Revert status to string
            $table->string('status')->default('pending')->change();
        });
    }
};
