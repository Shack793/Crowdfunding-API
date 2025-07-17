<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Boost;
use App\Models\Campaign;
use App\Models\BoostPlan;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Validator;

class BoostController extends Controller
{
    /**
     * Boost a campaign
     */
    public function boostCampaign(Request $request, $campaignId)
    {
        Log::info('Boost campaign request received', [
            'campaign_id' => $campaignId,
            'user_id' => Auth::id(),
            'request_headers' => $request->headers->all()
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|integer|exists:boost_plans,id',
                'payment_method_id' => 'required|integer|exists:payment_methods,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            
                $user = Auth::user();
                
                if (!$user) {
                    throw new Exception('User not authenticated');
                }

                $campaign = Campaign::findOrFail($campaignId);
                if (!$campaign) {
                    throw new Exception('Campaign not found');
                }

                $plan = BoostPlan::findOrFail($request->plan_id);
                if (!$plan) {
                    throw new Exception('Boost plan not found');
                }

                $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
                if (!$paymentMethod) {
                    throw new Exception('Payment method not found');
                }

                // Ensure campaign exists and belongs to user
                if ($campaign->user_id !== $user->id) {
                    throw new Exception('Campaign does not belong to authenticated user');
                }

                // Create boost record
                $boost = Boost::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'campaign_id' => $campaign->id,
                    'amount_paid' => $plan->price,
                    'start_date' => now(),
                    'end_date' => now()->addDays($plan->duration_days),
                    'payment_method_id' => $paymentMethod->id,
                    'status' => 'pending', // Will be updated on payment success
                ]);

                // Here you would integrate with your payment provider (e.g., MoMo)
                // For now, we'll simulate a successful payment
                
                // Update campaign boost status and end date
                $campaign->update([
                    'is_boosted' => true,
                    'boost_ends_at' => $boost->end_date
                ]);

                // Update boost status to active
                $boost->update(['status' => 'active']);

                return response()->json([
                    'success' => true,
                    'message' => 'Campaign boosted successfully!',
                    'data' => $boost->load('plan', 'paymentMethod')
                ], 201);
            
        } catch (Exception $e) {
            Log::error('Boost campaign error: ' . $e->getMessage(), [
                'campaign_id' => $campaignId,
                'user_id' => Auth::id(),
                'plan_id' => $request->plan_id ?? null,
                'payment_method_id' => $request->payment_method_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to boost campaign',
                'error' => $e->getMessage(),
                'code' => $e->getCode() ?: 500
            ], 400)
            ->header('Content-Type', 'application/json');
        }
    }
    
    /**
     * Get user's boost history
     */
    public function userBoosts(): JsonResponse
    {
        $boosts = Auth::user()
            ->boosts()
            ->with(['plan', 'campaign'])
            ->latest()
            ->paginate(10);
            
        return response()->json([
            'success' => true,
            'data' => $boosts
        ]);
    }
    
    /**
     * Get all boosts (admin only)
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Boost::class);
        
        $boosts = Boost::with(['user', 'campaign', 'plan'])
            ->latest()
            ->paginate(15);
            
        return response()->json([
            'success' => true,
            'data' => $boosts
        ]);
    }
    
    /**
     * Get boost statistics (admin only)
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Boost::class);
        
        $stats = [
            'total_revenue' => Boost::where('status', 'active')->sum('amount_paid'),
            'active_boosts' => Boost::where('status', 'active')
                ->where('end_date', '>', now())
                ->count(),
            'expiring_soon' => Boost::where('status', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(3)])
                ->count(),
            'plans_usage' => Boost::selectRaw('plan_id, count(*) as count')
                ->with('plan:id,name')
                ->groupBy('plan_id')
                ->get()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
