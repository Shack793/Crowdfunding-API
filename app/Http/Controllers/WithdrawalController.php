<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'charge' => 'nullable|numeric',
                'internal_reference' => 'nullable|string',
                'client_reference' => 'nullable|string',
                'external_reference' => 'nullable|string',
                'transaction_reference' => 'nullable|string',
            ]);

            $user = Auth::user();
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            $withdrawal = Withdrawal::create(array_merge($validated, [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'status' => 'pending',
                'requested_at' => now(),
            ]));

            return response()->json([
                'success' => true,
                'data' => $withdrawal
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create withdrawal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
