<?php

namespace App\Filament\Resources\BoostPlanResource\Pages;

use App\Filament\Resources\BoostPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBoostPlans extends ListRecords
{
    protected static string $resource = BoostPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Boost Plan')
                ->icon('heroicon-o-plus'),
        ];
    }
}