<?php

namespace App\Filament\Resources\BoostResource\Pages;

use App\Filament\Resources\BoostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListBoosts extends ListRecords
{
    protected static string $resource = BoostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Boost')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(function (Builder $query) {
                $activeTab = request('tableFilters.status.value');
                
                if ($activeTab && $activeTab !== 'all') {
                    $query->where('status', $activeTab);
                }
                
                return $query;
            });
    }
}