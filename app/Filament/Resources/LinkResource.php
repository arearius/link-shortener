<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LinkResource\Pages;
use App\Filament\Resources\LinkResource\RelationManagers\ClicksRelationManager;
use App\Models\Link;
use App\Rules\ExternalUrl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $modelLabel = 'ссылка';

    protected static ?string $pluralModelLabel = 'Мои ссылки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('original_url')
                    ->label('Оригинальный URL')
                    ->placeholder('https://example.com/page')
                    ->url()
                    ->required()
                    ->maxLength(2048)
                    ->rule(new ExternalUrl())
                    ->helperText('Только внешние http/https-адреса.')
                    ->columnSpanFull(),

                // Shown when editing an existing link; the code is generated on create.
                Forms\Components\TextInput::make('code')
                    ->label('Короткий код')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('short_url')
                    ->label('Короткая ссылка')
                    ->url(fn (Link $record): string => $record->short_url)
                    ->openUrlInNewTab()
                    ->copyable()
                    ->copyMessage('Скопировано')
                    ->icon('heroicon-m-clipboard'),

                Tables\Columns\TextColumn::make('original_url')
                    ->label('Оригинальный URL')
                    ->limit(50)
                    ->tooltip(fn (Link $record): string => $record->original_url)
                    ->searchable(),

                Tables\Columns\TextColumn::make('clicks_count')
                    ->label('Клики')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Поиск по коду или URL')
            ->persistSearchInSession()
            ->persistSortInSession()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Пока нет ссылок')
            ->emptyStateDescription('Создайте первую короткую ссылку.');
    }

    public static function getRelations(): array
    {
        return [
            ClicksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLinks::route('/'),
            'create' => Pages\CreateLink::route('/create'),
            'view' => Pages\ViewLink::route('/{record}'),
            'edit' => Pages\EditLink::route('/{record}/edit'),
        ];
    }

    /**
     * Scope every query to the currently authenticated user's links only.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
}
