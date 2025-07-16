<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentUsersTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Users';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = '1/2';
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = true;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation
    {
        return User::query()
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('avatar')
                ->label('')
                ->circular()
                ->size(40)
                ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&color=7F9CF5&background=EBF4FF'),
                
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record])),
                
            Tables\Columns\TextColumn::make('email')
                ->searchable()
                ->sortable(),
                
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(),
                
            Tables\Columns\TextColumn::make('role')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'admin' => 'danger',
                    'creator' => 'primary',
                    'supporter' => 'success',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
        ];
    }
    
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit')
                ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record]))
                ->icon('heroicon-o-pencil'),
        ];
    }
    
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-users';
    }
    
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No users yet';
    }
    
    protected function getTableEmptyStateDescription(): ?string
    {
        return 'When users register, they will appear here.';
    }
}
