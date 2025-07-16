<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoostPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BoostPlanController extends Controller
{
    /**
     * Get all active boost plans
     */
    public function index(): JsonResponse
    {
        Log::info('Boost plan request received');
        $plans = BoostPlan::where('status', 'active')
            ->select(['id', 'name', 'price', 'duration_days'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }
}
