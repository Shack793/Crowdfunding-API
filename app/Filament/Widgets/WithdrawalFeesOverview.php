<?php

namespace App\Filament\Widgets;

use App\Models\WithdrawalFee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class WithdrawalFeesOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Get total fees available for withdrawal (calculated status)
        $availableFees = WithdrawalFee::where('status', 'calculated')->sum('fee_amount');
        
        // Get total fees collected this month
        $thisMonthFees = WithdrawalFee::where('status', 'applied')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('fee_amount');
            
        // Get total pending withdrawals count
        $pendingCount = WithdrawalFee::where('status', 'calculated')->count();
        
        // Get today's fees
        $todayFees = WithdrawalFee::where('status', 'calculated')
            ->whereDate('created_at', today())
            ->sum('fee_amount');

        return [
            Stat::make('Available for Withdrawal', 'GHS ' . number_format($availableFees, 2))
                ->description('Total fees ready to withdraw')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
                
            Stat::make('This Month Collected', 'GHS ' . number_format($thisMonthFees, 2))
                ->description('Fees withdrawn this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
                
            Stat::make(' Transactions', $pendingCount)
                ->description('Transactions awaiting withdrawal')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Today\'s Fees', 'GHS ' . number_format($todayFees, 2))
                ->description('Fees generated today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
