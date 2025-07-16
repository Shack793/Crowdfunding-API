<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-o-users')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('primary'),
                
            Stat::make('Active Campaigns', Campaign::where('status', 'active')->count())
                ->description('Out of ' . Campaign::count() . ' total')
                ->descriptionIcon('heroicon-o-fire')
                ->chart([1, 3, 5, 10, 15, 20, 25])
                ->color('success'),
                
            Stat::make('Categories', Category::count())
                ->description('Active categories')
                ->descriptionIcon('heroicon-o-tag')
                ->chart([10, 15, 12, 8, 15, 20, 18])
                ->color('warning'),
                
            Stat::make('Total Raised', '$' . number_format(Campaign::sum('current_amount'), 2))
                ->description('Of $' . number_format(Campaign::sum('goal_amount'), 2) . ' goal')
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart([100, 200, 300, 400, 500, 600, 700])
                ->color('success'),
        ];
    }
}
