<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLink extends ViewRecord
{
    protected static string $resource = LinkResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('code')->label('Код'),
                TextEntry::make('short_url')
                    ->label('Короткая ссылка')
                    ->url(fn ($record) => $record->short_url, true)
                    ->copyable(),
                TextEntry::make('original_url')->label('Оригинальный URL'),
                TextEntry::make('clicks_count')->label('Всего кликов')->badge()->color('success'),
                TextEntry::make('created_at')->label('Создана')->dateTime('d.m.Y H:i'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
