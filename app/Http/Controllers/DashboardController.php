<?php
namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Analytic;
use App\Models\Contribution;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function campaigns()
    {
        $campaigns = Campaign::where('user_id', Auth::id())->get();
        return response()->json($campaigns);
    }

    public function campaignAnalytics($id)
    {
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
        $analytics = $campaign->analytics;
        return response()->json($analytics);
    }

    public function contributions()
    {
        $campaignIds = Campaign::where('user_id', Auth::id())->pluck('id');
        $contributions = Contribution::with('user', 'campaign')
            ->whereIn('campaign_id', $campaignIds)
            ->get();
        return response()->json($contributions);
    }

    public function withdrawals()
    {
        $withdrawals = Withdrawal::where('user_id', Auth::id())->get();
        return response()->json($withdrawals);
    }
}
