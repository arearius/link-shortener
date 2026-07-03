<?php

namespace App\Filament\Resources\LinkResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClicksRelationManager extends RelationManager
{
    protected static string $relationship = 'clicks';

    protected static ?string $title = 'Переходы';

    protected static ?string $modelLabel = 'переход';

    protected static ?string $pluralModelLabel = 'Переходы';

    /**
     * Show the total number of clicks as a badge on the relation tab.
     */
    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->clicks()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP-адрес'),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User-Agent')
                    ->limit(40)
                    ->tooltip(fn ($record): ?string => $record->user_agent)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата и время перехода')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            // Statistics are read-only: clicks are recorded by the redirect, not created here.
            ->paginated([10, 25, 50]);
    }
}
