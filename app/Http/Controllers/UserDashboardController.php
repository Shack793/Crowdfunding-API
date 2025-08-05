<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); // Get authenticated user
        $userId = $user->id;
        
        // User-specific campaign count
        $totalCampaigns = Campaign::where('user_id', $userId)->count();
        
        // User-specific contributions (contributions TO the user's campaigns)
        $totalContributions = Contribution::whereHas('campaign', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();
        
        // Get user's wallet details
        $wallet = Wallet::where('user_id', $userId)->first();
        
        // If wallet doesn't exist, create one
        if (!$wallet) {
            $wallet = Wallet::create([
                'user_id' => $userId,
                'balance' => 0,
                'total_withdrawn' => 0,
                'withdrawal_count' => 0,
                'currency' => 'GHS'
            ]);
        }
        
        // Wallet statistics
        $walletStats = [
            'balance' => number_format((float)$wallet->balance, 2, '.', ''),
            'total_withdrawn' => number_format((float)$wallet->total_withdrawn, 2, '.', ''),
            'withdrawal_count' => $wallet->withdrawal_count,
            'currency' => $wallet->currency ?? 'GHS',
            'last_withdrawal_at' => $wallet->last_withdrawal_at,
            'status' => $wallet->status ?? 'active'
        ];
        
        // Get user's withdrawal history (from Withdrawal model)
        $withdrawalHistory = Withdrawal::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($withdrawal) {
                return [
                    'id' => $withdrawal->id,
                    'amount' => number_format((float)$withdrawal->amount, 2, '.', ''),
                    'date' => $withdrawal->created_at->format('Y-m-d'),
                    'status' => $withdrawal->status ?? 'completed',
                    'method' => $withdrawal->payment_method ?? 'bank_transfer'
                ];
            });
        
        // User-specific expired campaigns
        $expiredCampaigns = Campaign::where('user_id', $userId)
            ->where('end_date', '<', now())
            ->count();

        // Chart data: last 6 months for user's campaigns
        $chartData = [];
        $months = collect(range(0, 5))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        })->reverse();
        
        foreach ($months as $month) {
            // Donations to user's campaigns
            $donations = Contribution::whereHas('campaign', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })->whereBetween('created_at', ["$month-01", now()->parse("$month-01")->endOfMonth()])
            ->sum('amount');
            
            // User's withdrawals for this month
            $monthlyWithdrawals = Withdrawal::where('user_id', $userId)
                ->whereBetween('created_at', ["$month-01", now()->parse("$month-01")->endOfMonth()])
                ->sum('amount');
            
            $chartData[] = [
                'month' => $month,
                'donations' => (float)$donations,
                'withdrawals' => (float)$monthlyWithdrawals,
            ];
        }

        // Recent contributions to user's campaigns
        $recentContributions = Contribution::with(['campaign', 'user'])
            ->whereHas('campaign', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'contributor' => $c->name ?? ($c->user->name ?? 'Anonymous'),
                    'campaign' => $c->campaign->title ?? null,
                    'amount' => number_format((float)$c->amount, 2, '.', ''),
                    'date' => $c->created_at->format('Y-m-d'),
                    'campaign_slug' => $c->campaign->slug ?? null,
                ];
            });

        return response()->json([
            'totalCampaigns' => $totalCampaigns,
            'totalContributions' => $totalContributions,
            'withdrawals' => number_format((float)$wallet->total_withdrawn, 2, '.', ''),
            'expiredCampaigns' => $expiredCampaigns,
            'walletStats' => $walletStats,
            'withdrawalHistory' => $withdrawalHistory,
            'chartData' => $chartData,
            'recentContributions' => $recentContributions,
        ]);
    }
}
