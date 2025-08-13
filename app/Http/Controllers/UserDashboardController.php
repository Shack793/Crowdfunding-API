<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // User's total campaigns
        $totalCampaigns = Campaign::where('user_id', $user->id)->count();
        
        // Total contributions received on user's campaigns (donations TO their campaigns)
        $totalContributions = Contribution::whereHas('campaign', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('status', ['completed', 'successful', 'pending'])->sum('amount');
        
        // Total contribution count (number of contributions)
        $totalContributionCount = Contribution::whereHas('campaign', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereIn('status', ['completed', 'successful', 'pending'])->count();
        
        // User's withdrawals
        $withdrawals = Withdrawal::where('user_id', $user->id)->sum('amount');
        
        // User's expired campaigns
        $expiredCampaigns = Campaign::where('user_id', $user->id)
            ->where('status', 'expired')
            ->count();

        // Get or create user's wallet
        $wallet = $user->wallet ?? $user->wallet()->create([
            'balance' => 0,
            'currency' => 'GHS',
            'status' => 'active',
            'total_withdrawn' => 0,
            'withdrawal_count' => 0
        ]);

        // Wallet stats
        $walletStats = [
            'balance' => number_format($wallet->balance ?? 0, 2),
            'total_withdrawn' => number_format($wallet->total_withdrawn ?? 0, 2),
            'withdrawal_count' => $wallet->withdrawal_count ?? 0,
            'currency' => $wallet->currency ?? 'GHS',
            'last_withdrawal_at' => $wallet->last_withdrawal_at,
            'status' => $wallet->status ?? 'active'
        ];

        // Chart data: last 6 months for user's campaigns
        $chartData = [];
        $months = collect(range(0, 5))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        })->reverse();
        
        foreach ($months as $month) {
            $monthStart = "$month-01";
            $monthEnd = now()->parse("$month-01")->endOfMonth()->format('Y-m-d');
            
            // Donations received on user's campaigns
            $donations = Contribution::whereHas('campaign', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->whereIn('status', ['completed', 'successful', 'pending'])
              ->whereBetween('created_at', [$monthStart, $monthEnd])
              ->sum('amount');
            
            // User's withdrawals
            $withdrawal = Withdrawal::where('user_id', $user->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');
            
            $chartData[] = [
                'month' => $month,
                'donations' => floatval($donations),
                'withdrawals' => floatval($withdrawal),
            ];
        }

        // Recent contributions to user's campaigns
        $recentContributions = Contribution::with(['campaign', 'user'])
            ->whereHas('campaign', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereIn('status', ['completed', 'successful', 'pending'])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'contributor' => $c->name ?? ($c->user->name ?? 'Anonymous'),
                    'campaign' => $c->campaign->title ?? null,
                    'amount' => number_format($c->amount, 2),
                    'date' => $c->created_at->toDateString(),
                    'status' => $c->status, // Added status to show pending/successful/completed
                ];
            });

        // Withdrawal history
        $withdrawalHistory = Withdrawal::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'amount' => number_format($w->amount, 2),
                    'status' => $w->status,
                    'date' => $w->created_at->toDateString(),
                    'created_at' => $w->created_at
                ];
            });

        return response()->json([
            'user_id' => $user->id,
            'totalCampaigns' => $totalCampaigns,
            'totalContributions' => number_format($totalContributions, 2),
            'totalContributionCount' => $totalContributionCount,
            'withdrawals' => number_format($withdrawals, 2),
            'expiredCampaigns' => $expiredCampaigns,
            'walletStats' => $walletStats,
            'withdrawalHistory' => $withdrawalHistory,
            'chartData' => $chartData,
            'recentContributions' => $recentContributions,
        ]);
    }
}
