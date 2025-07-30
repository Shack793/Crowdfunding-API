<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\User;
use App\Models\Boost;
use App\Models\BoostPlan;
use App\Models\Contribution;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get totals
        $totalUsers = User::count();
        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::where('status', 'active')->count();
        $totalCategories = Category::count();
        $totalBoosts = Boost::count();
        $activeBoosts = Boost::where('status', 'active')
            ->where('end_date', '>', now())
            ->count();
        $totalBoostPlans = BoostPlan::where('status', 'active')->count();

        // Calculate revenue
        $totalRevenue = Contribution::where('status', 'completed')->sum('amount');
        $boostRevenue = Boost::where('status', 'active')->sum('amount_paid');

        // Get recent growth data (last 7 days)
        $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();
        $recentCampaigns = Campaign::where('created_at', '>=', now()->subDays(7))->count();
        $recentBoosts = Boost::where('created_at', '>=', now()->subDays(7))->count();

        // Chart data for trends (last 7 days)
        $userGrowthData = [];
        $campaignGrowthData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $userGrowthData[] = User::whereDate('created_at', $date)->count();
            $campaignGrowthData[] = Campaign::whereDate('created_at', $date)->count();
        }

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description($recentUsers > 0 ? "+{$recentUsers} new this week" : 'No new users this week')
                ->descriptionIcon($recentUsers > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->chart($userGrowthData)
                ->color($recentUsers > 0 ? 'success' : 'gray'),

            Stat::make('Total Campaigns', number_format($totalCampaigns))
                ->description("{$activeCampaigns} active · +{$recentCampaigns} new this week")
                ->descriptionIcon($recentCampaigns > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-chart-bar')
                ->chart($campaignGrowthData)
                ->color('primary'),

            Stat::make('Categories', number_format($totalCategories))
                ->description('Available categories')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning'),

            Stat::make('Campaign Boosts', number_format($totalBoosts))
                ->description("{$activeBoosts} currently active")
                ->descriptionIcon($activeBoosts > 0 ? 'heroicon-m-rocket-launch' : 'heroicon-m-sparkles')
                ->color('info'),

            Stat::make('Boost Plans', number_format($totalBoostPlans))
                ->description('Active boost plans')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),

           // Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
              //  ->description('From contributions')
              //  ->descriptionIcon('heroicon-m-banknotes')
               // ->color('success'),

            Stat::make('Boost Revenue', '₵' . number_format($boostRevenue, 2))
                ->description('From campaign boosts')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Recent Activity', number_format($recentUsers + $recentCampaigns + $recentBoosts))
                ->description('New items this week')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('primary'),
        ];
    }
}
