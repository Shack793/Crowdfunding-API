<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalFee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 🎓 WithdrawalFeeController - Junior Developer Learning Guide
 * 
 * This controller handles:
 * 1. Fee calculation before withdrawal
 * 2. Fee logging when withdrawal is processed
 * 3. Fee history for users and admins
 * 
 * API Design Pattern: RESTful with clear response structure
 */
class WithdrawalFeeController extends Controller
{
    /**
     * 📝 Calculate withdrawal fee (GET)
     * 
     * This endpoint calculates fees WITHOUT storing them.
     * Perfect for frontend to show fees before user confirms withdrawal.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateFee(Request $request): JsonResponse
    {
        try {
            // 📚 Learning: Always validate input data
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1|max:10000',
                'method' => 'required|string|in:mobile_money,bank_transfer',
                'network' => 'nullable|string|in:MTN,Vodafone,AirtelTigo',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $amount = $request->input('amount');
            $method = $request->input('method');
            $network = $request->input('network');

            // Log the calculation request for debugging
            Log::info('Fee calculation requested', [
                'user_id' => Auth::id(),
                'amount' => $amount,
                'method' => $method,
                'network' => $network,
                'ip' => $request->ip(),
            ]);

            // 📚 Learning: Use static methods for utility functions
            $calculation = WithdrawalFee::calculateFee($amount, $method, $network);

            return response()->json([
                'success' => true,
                'message' => 'Fee calculated successfully',
                'data' => [
                    'gross_amount' => $calculation['gross_amount'],
                    'fee_amount' => $calculation['fee_amount'],
                    'net_amount' => $calculation['net_amount'],
                    'fee_percentage' => $calculation['fee_percentage'],
                    'currency' => 'GHS',
                    'breakdown' => [
                        'description' => $calculation['calculation_notes'],
                        'method' => $method,
                        'network' => $network,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Fee calculation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate fee. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * 💾 Record withdrawal fee (POST)
     * 
     * This endpoint stores fee information when a withdrawal is actually processed.
     * Called by your withdrawal processing logic.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function recordFee(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'withdrawal_id' => 'nullable|exists:withdrawals,id',
                'amount' => 'required|numeric|min:1|max:10000',
                'method' => 'required|string|in:mobile_money,bank_transfer',
                'network' => 'nullable|string|in:MTN,Vodafone,AirtelTigo',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $amount = $request->input('amount');
            $method = $request->input('method');
            $network = $request->input('network');
            $withdrawalId = $request->input('withdrawal_id');
            $metadata = $request->input('metadata', []);

            // Add request context to metadata
            $metadata = array_merge($metadata, [
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'timestamp' => now()->toISOString(),
            ]);

            // 📚 Learning: Use model methods for complex operations
            $feeRecord = WithdrawalFee::createWithCalculation(
                $user->id,
                $amount,
                $method,
                $network,
                $metadata
            );

            // Update withdrawal_id if provided
            if ($withdrawalId) {
                $feeRecord->update(['withdrawal_id' => $withdrawalId]);
            }

            Log::info('Withdrawal fee recorded', [
                'fee_id' => $feeRecord->id,
                'user_id' => $user->id,
                'withdrawal_id' => $withdrawalId,
                'amount' => $amount,
                'fee_amount' => $feeRecord->fee_amount,
                'method' => $method,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fee recorded successfully',
                'data' => [
                    'fee_id' => $feeRecord->id,
                    'gross_amount' => $feeRecord->gross_amount,
                    'fee_amount' => $feeRecord->fee_amount,
                    'net_amount' => $feeRecord->net_amount,
                    'currency' => $feeRecord->currency,
                    'status' => $feeRecord->status,
                    'recorded_at' => $feeRecord->created_at->toISOString(),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Fee recording failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record fee. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * 📊 Get user's fee history (GET)
     * 
     * Returns paginated list of fees for the authenticated user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserFees(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 15);
            $method = $request->input('method'); // Filter by method if provided

            // 📚 Learning: Use Eloquent query builder for complex queries
            $query = WithdrawalFee::where('user_id', $user->id)
                ->with('withdrawal') // Eager load withdrawal data
                ->orderBy('created_at', 'desc');

            // Apply method filter if provided
            if ($method) {
                $query->byMethod($method);
            }

            $fees = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Fee history retrieved successfully',
                'data' => $fees->items(),
                'pagination' => [
                    'current_page' => $fees->currentPage(),
                    'per_page' => $fees->perPage(),
                    'total' => $fees->total(),
                    'last_page' => $fees->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user fees', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fee history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * 📈 Get fee statistics (GET)
     * 
     * Returns summary statistics for the authenticated user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $startDate = $request->input('start_date', now()->subDays(30));
            $endDate = $request->input('end_date', now());

            // 📚 Learning: Use raw SQL for aggregations when needed
            $stats = WithdrawalFee::where('user_id', $user->id)
                ->dateRange($startDate, $endDate)
                ->selectRaw('
                    COUNT(*) as total_transactions,
                    SUM(gross_amount) as total_gross_amount,
                    SUM(fee_amount) as total_fees_paid,
                    SUM(net_amount) as total_net_amount,
                    AVG(fee_amount) as average_fee,
                    MIN(fee_amount) as minimum_fee,
                    MAX(fee_amount) as maximum_fee
                ')
                ->first();

            // Get breakdown by method
            $methodBreakdown = WithdrawalFee::where('user_id', $user->id)
                ->dateRange($startDate, $endDate)
                ->selectRaw('
                    withdrawal_method,
                    COUNT(*) as transaction_count,
                    SUM(fee_amount) as total_fees
                ')
                ->groupBy('withdrawal_method')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'summary' => [
                        'total_transactions' => (int) $stats->total_transactions,
                        'total_gross_amount' => (float) $stats->total_gross_amount,
                        'total_fees_paid' => (float) $stats->total_fees_paid,
                        'total_net_amount' => (float) $stats->total_net_amount,
                        'average_fee' => round((float) $stats->average_fee, 2),
                        'minimum_fee' => (float) $stats->minimum_fee,
                        'maximum_fee' => (float) $stats->maximum_fee,
                    ],
                    'by_method' => $methodBreakdown->map(function ($item) {
                        return [
                            'method' => $item->withdrawal_method,
                            'transaction_count' => (int) $item->transaction_count,
                            'total_fees' => (float) $item->total_fees,
                        ];
                    }),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get fee statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
