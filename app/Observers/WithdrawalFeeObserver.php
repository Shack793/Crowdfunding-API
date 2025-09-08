<?php

namespace App\Observers;

use App\Models\WithdrawalFee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WithdrawalFeeObserver
{
    /**
     * Handle the WithdrawalFee "created" event.
     */
    public function created(WithdrawalFee $withdrawalFee): void
    {
        $this->clearCache();
        
        Log::info('New withdrawal fee created', [
            'fee_id' => $withdrawalFee->id,
            'user_id' => $withdrawalFee->user_id,
            'amount' => $withdrawalFee->fee_amount,
            'method' => $withdrawalFee->withdrawal_method,
        ]);
    }

    /**
     * Handle the WithdrawalFee "updated" event.
     */
    public function updated(WithdrawalFee $withdrawalFee): void
    {
        $this->clearCache();
        
        // Log status changes
        if ($withdrawalFee->wasChanged('status')) {
            Log::info('Withdrawal fee status updated', [
                'fee_id' => $withdrawalFee->id,
                'user_id' => $withdrawalFee->user_id,
                'old_status' => $withdrawalFee->getOriginal('status'),
                'new_status' => $withdrawalFee->status,
                'amount' => $withdrawalFee->fee_amount,
            ]);
        }
    }

    /**
     * Handle the WithdrawalFee "deleted" event.
     */
    public function deleted(WithdrawalFee $withdrawalFee): void
    {
        $this->clearCache();
        
        Log::info('Withdrawal fee deleted', [
            'fee_id' => $withdrawalFee->id,
            'user_id' => $withdrawalFee->user_id,
            'amount' => $withdrawalFee->fee_amount,
            'status' => $withdrawalFee->status,
        ]);
    }

    /**
     * Clear related caches when withdrawal fees change
     */
    private function clearCache(): void
    {
        // Clear cache keys related to withdrawal fees
        Cache::forget('withdrawal_fees_total_available');
        Cache::forget('withdrawal_fees_monthly_stats');
        Cache::forget('withdrawal_fees_pending_count');
        
        // You can add more specific cache clearing logic here
    }
}
