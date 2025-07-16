<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function index()
    {
        // Total campaigns
        $totalCampaigns = Campaign::count();
        // Total contributions
        $totalContributions = Contribution::sum('amount');
        // Withdrawals
        $withdrawals = Withdrawal::sum('amount');
        // Expired campaigns
        $expiredCampaigns = Campaign::where('status', 'expired')->count();

        // Chart data: last 6 months
        $chartData = [];
        $months = collect(range(0, 5))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        })->reverse();
        foreach ($months as $month) {
            $donations = Contribution::whereBetween('created_at', ["$month-01", now()->parse("$month-01")->endOfMonth()])->sum('amount');
            $withdrawal = Withdrawal::whereBetween('created_at', ["$month-01", now()->parse("$month-01")->endOfMonth()])->sum('amount');
            $chartData[] = [
                'month' => $month,
                'donations' => $donations,
                'withdrawals' => $withdrawal,
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
            'withdrawals' => $withdrawals,
            'expiredCampaigns' => $expiredCampaigns,
            'chartData' => $chartData,
            'recentContributions' => $recentContributions,
        ]);
    }
}
