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
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('total_withdrawn', 10, 2)->default(0);
            $table->decimal('pending_withdrawal', 10, 2)->default(0);
            $table->timestamp('last_withdrawal_at')->nullable();
            $table->json('last_withdrawal_details')->nullable();
            $table->integer('withdrawal_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn([
                'total_withdrawn',
                'pending_withdrawal',
                'last_withdrawal_at',
                'last_withdrawal_details',
                'withdrawal_count'
            ]);
        });
    }
};
