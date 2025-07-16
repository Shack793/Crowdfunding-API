<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CampaignResource;
use App\Models\Campaign;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class RecentCampaignsTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Campaigns';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = true;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation
    {
        return Campaign::query()
            ->with(['user', 'category'])
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('image')
                ->label('')
                ->circular()
                ->size(40),
                
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable()
                ->limit(30)
                ->tooltip(fn ($record) => $record->title)
                ->url(fn ($record) => CampaignResource::getUrl('edit', ['record' => $record])),
                
            Tables\Columns\TextColumn::make('category.name')
                ->sortable()
                ->badge(),
                
            Tables\Columns\TextColumn::make('user.name')
                ->sortable()
                ->searchable(),
                
            Tables\Columns\TextColumn::make('current_amount')
                ->money()
                ->sortable()
                ->color(fn ($record) => $record->current_amount >= $record->goal_amount ? 'success' : 'primary'),
                
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'pending' => 'warning',
                    'active' => 'success',
                    'completed' => 'primary',
                    'cancelled' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
        ];
    }
    
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->url(fn ($record) => CampaignResource::getUrl('edit', ['record' => $record]))
                ->icon('heroicon-o-eye'),
        ];
    }
    
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-megaphone';
    }
    
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No campaigns yet';
    }
    
    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Create your first campaign to get started.';
    }
    
    protected function getTableEmptyStateActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('Create campaign')
                ->url(CampaignResource::getUrl('create'))
                ->icon('heroicon-m-plus'),
        ];
    }
}
