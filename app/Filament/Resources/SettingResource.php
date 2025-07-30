<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Filament\Resources\SettingResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 10;
    
    // Hide from navigation
    protected static bool $shouldRegisterNavigation = false;
    
    protected static ?string $modelLabel = 'Setting';
    protected static ?string $pluralModelLabel = 'Settings';
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-z0-9_]+$/')
                            ->placeholder('setting_key_name')
                            ->hint('Only lowercase letters, numbers, and underscores'),
                            
                        Forms\Components\Textarea::make('value')
                            ->required()
                            ->maxLength(65535)
                            ->placeholder('Enter the setting value')
                            ->columnSpanFull(),
                            
                        Forms\Components\TextInput::make('group')
                            ->default('general')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., general, mail, social')
                            ->helperText('Group settings together for better organization'),
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->placeholder('Describe what this setting is used for (optional)'),
                            
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Setting')
                            ->helperText('If enabled, this setting will be publicly accessible')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description ?: '')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('value')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->value)
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('group')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'mail',
                        'warning' => 'social',
                        'danger' => 'danger',
                    ])
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->sortable()
                    ->label('Public'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->label('Last Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options(fn () => \App\Models\Setting::query()
                        ->distinct()
                        ->pluck('group', 'group')
                        ->toArray()
                    ),
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Setting'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }    
}
