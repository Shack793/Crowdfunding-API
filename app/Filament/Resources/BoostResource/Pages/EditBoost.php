<?php

namespace App\Filament\Resources\BoostResource\Pages;

use App\Filament\Resources\BoostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoost extends EditRecord
{
    protected static string $resource = BoostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}