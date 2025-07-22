<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function checkBalance()
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet->balance,
                'currency' => $wallet->currency,
                'can_withdraw' => $wallet->balance > 0
            ]
        ]);
    }

    public function updateWalletAfterWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric',
            'status' => 'required|string|in:success,failed'
        ]);

        $user = Auth::user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }

        if ($validated['status'] === 'success') {
            $wallet->balance -= $validated['amount'];
            $wallet->save();

            // Update withdrawal status
            $withdrawal = Withdrawal::where('payment_reference', $validated['transaction_id'])->first();
            if ($withdrawal) {
                $withdrawal->status = 'completed';
                $withdrawal->processed_at = now();
                $withdrawal->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet updated successfully',
            'data' => [
                'new_balance' => $wallet->balance,
                'currency' => $wallet->currency
            ]
        ]);
    }
}
