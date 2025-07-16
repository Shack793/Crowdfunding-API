<?php

namespace App\Filament\Resources\BoostPlanResource\Pages;

use App\Filament\Resources\BoostPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoostPlan extends EditRecord
{
    protected static string $resource = BoostPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}