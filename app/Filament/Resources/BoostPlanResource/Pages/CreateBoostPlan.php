<?php

namespace App\Filament\Resources\BoostPlanResource\Pages;

use App\Filament\Resources\BoostPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBoostPlan extends CreateRecord
{
    protected static string $resource = BoostPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}