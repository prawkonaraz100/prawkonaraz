<?php

namespace App\Filament\Resources\TrafficSignConfusionPairs\Tables;

use App\Models\TrafficSignConfusionPair;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrafficSignConfusionPairsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po kodzie, nazwie albo slugu źródła')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak par podobnych znaków')
            ->emptyStateDescription('Zsynchronizuj pary z istniejących stron porównawczych albo dodaj relację ręcznie.')
            ->emptyStateIcon(Heroicon::OutlinedQueueList)
            ->columns([
                TextColumn::make('trafficSign.code')
                    ->label('Znak bazowy')
                    ->searchable()
                    ->sortable()
                    ->description(fn (TrafficSignConfusionPair $record): string => $record->trafficSign?->name ?? '')
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('confusingTrafficSign.code')
                    ->label('Znak mylony')
                    ->searchable()
                    ->sortable()
                    ->description(fn (TrafficSignConfusionPair $record): string => $record->confusingTrafficSign?->name ?? '')
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('source')
                    ->label('Źródło')
                    ->state(fn (TrafficSignConfusionPair $record): string => $record->sourceLabel())
                    ->badge()
                    ->sortable(),
                TextColumn::make('source_slug')
                    ->label('Slug źródła')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('strength')
                    ->label('Siła')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label('Źródło')
                    ->options(TrafficSignConfusionPair::sourceOptions()),
                SelectFilter::make('traffic_sign_id')
                    ->label('Znak bazowy')
                    ->relationship('trafficSign', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('confusing_traffic_sign_id')
                    ->label('Znak mylony')
                    ->relationship('confusingTrafficSign', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Usuń'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
