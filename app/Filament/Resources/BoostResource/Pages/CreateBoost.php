<?php

namespace App\Filament\Resources\BoostResource\Pages;

use App\Filament\Resources\BoostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBoost extends CreateRecord
{
    protected static string $resource = BoostResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}