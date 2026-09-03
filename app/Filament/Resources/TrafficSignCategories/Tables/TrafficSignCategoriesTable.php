<?php

namespace App\Filament\Resources\TrafficSignCategories\Tables;

use App\Models\TrafficSignCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TrafficSignCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po nazwie lub slugu')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak kategorii znaków')
            ->emptyStateDescription('Dodaj pierwszą kategorię, żeby porządkować znaki i budować strony kategorii.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->columns([
                TextColumn::make('name')
                    ->label('Kategoria')
                    ->searchable(['name', 'slug'])
                    ->sortable()
                    ->description(fn (TrafficSignCategory $record): string => implode(' · ', array_filter([
                        filled($record->slug) ? $record->slug : null,
                        filled($record->description) ? $record->description : null,
                    ])))
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('signs_summary')
                    ->label('Znaki')
                    ->state(fn (TrafficSignCategory $record): string => 'Wszystkie: '.number_format((int) $record->traffic_signs_count, 0, ',', ' '))
                    ->description(fn (TrafficSignCategory $record): string => 'Opublikowane: '.number_format((int) $record->published_traffic_signs_count, 0, ',', ' '))
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Widoczność')
                    ->state(fn (TrafficSignCategory $record): string => static::publicationStateLabel($record))
                    ->badge()
                    ->color(fn (TrafficSignCategory $record): string => static::publicationStateColor($record)),
                TextColumn::make('sort_order')
                    ->label('Kolejność')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Opublikowana'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    protected static function publicationStateLabel(TrafficSignCategory $record): string
    {
        if (! $record->is_published) {
            return 'Szkic';
        }

        if ($record->published_at === null) {
            return 'Brak daty';
        }

        return $record->published_at->isFuture() ? 'Zaplanowana' : 'Opublikowana';
    }

    protected static function publicationStateColor(TrafficSignCategory $record): string
    {
        return match (static::publicationStateLabel($record)) {
            'Opublikowana' => 'success',
            'Zaplanowana', 'Brak daty' => 'warning',
            default => 'gray',
        };
    }
}
