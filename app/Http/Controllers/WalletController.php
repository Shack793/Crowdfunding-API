<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Check user's wallet balance
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWallet()
    {
        try {
            Log::info('Getting wallet info', [
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            $user = Auth::user();
            if (!$user) {
                Log::error('User not authenticated in getWallet');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                // Create wallet if it doesn't exist
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'total_withdrawn' => 0,
                    'withdrawal_count' => 0,
                    'currency' => 'GHS'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $wallet->id,
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency ?? 'GHS',
                    'total_withdrawn' => $wallet->total_withdrawn,
                    'withdrawal_count' => $wallet->withdrawal_count,
                    'last_withdrawal_at' => $wallet->last_withdrawal_at,
                    'last_withdrawal_details' => $wallet->last_withdrawal_details,
                    'updated_at' => $wallet->updated_at
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error getting wallet info', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error getting wallet information'
            ], 500);
        }
    }

    public function checkBalance()
    {
        try {
            Log::info('Checking wallet balance', [
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            $user = Auth::user();
            if (!$user) {
                Log::error('User not authenticated in checkBalance');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                // Create wallet if it doesn't exist
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $wallet->balance,
                    'currency' => 'GHS', // Assuming Ghanaian Cedis
                    'last_updated' => $wallet->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking wallet balance', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking wallet balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update wallet balance after a withdrawal
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get detailed wallet statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWalletStats()
    {
        try {
            Log::info('Getting wallet statistics', [
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            $user = Auth::user();
            if (!$user) {
                Log::error('User not authenticated in getWalletStats');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'available_balance' => $wallet->balance,
                    'total_withdrawn' => $wallet->total_withdrawn,
                    'total_withdrawals' => $wallet->withdrawal_count,
                    'currency' => $wallet->currency ?? 'GHS',
                    'last_withdrawal' => [
                        'date' => $wallet->last_withdrawal_at,
                        'details' => $wallet->last_withdrawal_details
                    ],
                    'status' => $wallet->status,
                    'updated_at' => $wallet->updated_at
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error getting wallet statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error getting wallet statistics'
            ], 500);
        }
    }

    public function updateWalletAfterWithdrawal(Request $request)
    {
        try {
            // Log incoming request
            Log::info('Withdrawal update request received', [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['apiKey', 'apiSecret']),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            $validated = $request->validate([
                'amount' => 'required|string|regex:/^\d*\.?\d+$/', // Amount as string to match FalconPay's format
                'transaction_id' => 'required|string',
                'status' => 'required|string|in:success,failed,pending'
            ]);

            $user = Auth::user();
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                Log::error('Wallet not found for user', [
                    'user_id' => $user->id,
                    'transaction_id' => $validated['transaction_id'] ?? null,
                    'timestamp' => now()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            // Only process if status is success
            if ($validated['status'] !== 'success') {
                Log::warning('Non-successful transaction status', [
                    'user_id' => $user->id,
                    'transaction_id' => $validated['transaction_id'],
                    'status' => $validated['status'],
                    'amount' => $validated['amount'],
                    'timestamp' => now()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not successful',
                    'status' => $validated['status']
                ], 400);
            }

            // Convert amount string to decimal for comparison
            $amount = (float) $validated['amount'];

            // Check if balance is sufficient
            if ($wallet->balance < $amount) {
                Log::warning('Insufficient balance for withdrawal', [
                    'user_id' => $user->id,
                    'transaction_id' => $validated['transaction_id'],
                    'requested_amount' => $amount,
                    'current_balance' => $wallet->balance,
                    'timestamp' => now()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 400);
            }

            // Update wallet balance and withdrawal amount
            \DB::transaction(function () use ($wallet, $amount) {
                // Subtract from wallet balance
                $wallet->balance -= $amount;
                
                // Add to total withdrawn
                $wallet->total_withdrawn += $amount;
                
                $wallet->save();
            });

            // Log the transaction
            Log::info('Wallet updated after withdrawal', [
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'transaction_id' => $validated['transaction_id'],
                'status' => $validated['status'],
                'new_balance' => $wallet->balance
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wallet updated successfully',
                'data' => [
                    'new_balance' => $wallet->balance,
                    'transaction_id' => $validated['transaction_id']
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in wallet update', [
                'error' => $e->getMessage(),
                'validation_errors' => $e->errors(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['apiKey', 'apiSecret']),
                'timestamp' => now()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating wallet after withdrawal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'transaction_id' => $request->input('transaction_id'),
                'amount' => $request->input('amount'),
                'status' => $request->input('status'),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating wallet',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
