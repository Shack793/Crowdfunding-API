<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    /**
     * Get all boosted campaigns
     */
    public function boosted(): JsonResponse
    {
        $campaigns = Campaign::with(['user', 'category'])->
            whereIsBoosted(true)
            ->whereStatus('active')    
            ->paginate(12);
            
        return response()->json([
            'success' => true,
            'data' => $campaigns
        ],200);
    }
}
