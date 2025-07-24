<?php
namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Analytic;
use App\Models\Contribution;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function getDashboardStats()
    {
        try {
            $userId = Auth::id();
            
            // Get active campaigns count
            $activeCampaigns = Campaign::where('user_id', $userId)
                ->where('status', 'active')
                ->count();

            // Get total donations
            $totalDonations = Contribution::whereHas('campaign', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('status', 'completed')
            ->sum('amount');

            // Get total withdrawals (completed only)
            $totalWithdrawals = Withdrawal::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('amount');

            // Get upcoming and expired campaigns
            $now = now();
            $upcomingCampaigns = Campaign::where('user_id', $userId)
                ->where('start_date', '>', $now)
                ->count();

            $expiredCampaigns = Campaign::where('user_id', $userId)
                ->where('end_date', '<', $now)
                ->where('status', '!=', 'cancelled')
                ->count();

            // Get monthly stats for chart
            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            
            // Get monthly donations
            $monthlyDonations = Contribution::whereHas('campaign', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('status', 'completed')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as donations')
            ->groupBy('month')
            ->get();

            // Get monthly withdrawals
            $monthlyWithdrawals = Withdrawal::where('user_id', $userId)
                ->where('status', 'completed')
                ->where('created_at', '>=', $sixMonthsAgo)
                ->selectRaw('MONTH(created_at) as month, SUM(amount) as withdrawals')
                ->groupBy('month')
                ->get();

            // Get recent donations
            $recentDonations = Contribution::with(['user', 'campaign'])
                ->whereHas('campaign', function($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->where('status', 'completed')
                ->latest()
                ->take(3)
                ->get()
                ->map(function($donation) {
                    return [
                        'id' => $donation->id,
                        'donorName' => $donation->is_anonymous ? 'Anonymous Donor' : $donation->user->name,
                        'campaignTitle' => $donation->campaign->title,
                        'amount' => number_format($donation->amount, 2),
                        'date' => $donation->created_at->diffForHumans(),
                        'status' => $donation->status,
                        'avatar' => $donation->user->profile_image ?? '/placeholder-user.jpg'
                    ];
                });

            // Prepare chart data
            $chartData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthName = $month->format('M');
                $monthNumber = $month->format('n');
                
                $donations = $monthlyDonations->firstWhere('month', $monthNumber);
                $withdrawals = $monthlyWithdrawals->firstWhere('month', $monthNumber);
                
                $chartData[] = [
                    'month' => $monthName,
                    'donations' => $donations ? round($donations->donations, 2) : 0,
                    'withdrawals' => $withdrawals ? round($withdrawals->withdrawals, 2) : 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'activeCampaigns' => $activeCampaigns,
                        'totalDonations' => round($totalDonations, 2),
                        'withdrawals' => round($totalWithdrawals, 2)
                    ],
                    'campaignStats' => [
                        ['title' => 'Upcoming Campaigns', 'value' => $upcomingCampaigns, 'color' => 'bg-purple-500'],
                        ['title' => 'Expired Campaigns', 'value' => $expiredCampaigns, 'color' => 'bg-red-500']
                    ],
                    'chartData' => $chartData,
                    'recentDonations' => $recentDonations
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
