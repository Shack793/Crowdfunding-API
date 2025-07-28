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
    public function index()
    {
        $userId = Auth::id();
        
        // Total campaigns
        $totalCampaigns = Campaign::count();
        // Total contributions
        $totalContributions = Contribution::sum('amount');
        
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
            'balance' => $wallet->balance,
            'total_withdrawn' => $wallet->total_withdrawn,
            'withdrawal_count' => $wallet->withdrawal_count,
            'currency' => $wallet->currency ?? 'GHS',
            'last_withdrawal_at' => $wallet->last_withdrawal_at,
            'status' => $wallet->status ?? 'active'
        ];
        
        // Get withdrawal history from wallet JSON field
        $withdrawalHistory = [];
        if ($wallet->last_withdrawal_details) {
            $withdrawals = json_decode($wallet->last_withdrawal_details, true);
            if (is_array($withdrawals)) {
                // Sort by date descending and take last 5
                usort($withdrawals, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                $withdrawalHistory = array_slice($withdrawals, 0, 5);
            }
        }
        
        // Expired campaigns
        $expiredCampaigns = Campaign::where('status', 'expired')->count();

        // Chart data: last 6 months
        $chartData = [];
        $months = collect(range(0, 5))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        })->reverse();
        
        foreach ($months as $month) {
            $donations = Contribution::whereBetween('created_at', ["$month-01", now()->parse("$month-01")->endOfMonth()])->sum('amount');
            
            // Get monthly withdrawals from wallet data for authenticated user
            $monthlyWithdrawals = 0;
            if ($wallet->last_withdrawal_details) {
                $allWithdrawals = json_decode($wallet->last_withdrawal_details, true);
                if (is_array($allWithdrawals)) {
                    foreach ($allWithdrawals as $withdrawal) {
                        $withdrawalDate = date('Y-m', strtotime($withdrawal['date']));
                        if ($withdrawalDate === $month) {
                            $monthlyWithdrawals += $withdrawal['amount'];
                        }
                    }
                }
            }
            
            $chartData[] = [
                'month' => $month,
                'donations' => $donations,
                'withdrawals' => $monthlyWithdrawals,
            ];
        }

        // Recent contributions
        $recentContributions = Contribution::with('campaign')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'contributor' => $c->name ?? ($c->user->name ?? 'Anonymous'),
                    'campaign' => $c->campaign->title ?? null,
                    'amount' => $c->amount,
                    'date' => $c->created_at->toDateString(),
                ];
            });

        return response()->json([
            'totalCampaigns' => $totalCampaigns,
            'totalContributions' => $totalContributions,
            'withdrawals' => $wallet->total_withdrawn, // From wallet model
            'expiredCampaigns' => $expiredCampaigns,
            'walletStats' => $walletStats, // New: Complete wallet information
            'withdrawalHistory' => $withdrawalHistory, // New: Recent withdrawal history from wallet
            'chartData' => $chartData,
            'recentContributions' => $recentContributions,
        ]);
    }
}
