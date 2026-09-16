<?php

namespace App\Filament\Resources\ContentCategories\Tables;

use App\Models\ContentCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContentCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po nazwie lub slugu')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak kategorii aktualności')
            ->emptyStateDescription('Dodaj kategorię, aby porządkować artykuły newsroomu.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->columns([
                TextColumn::make('name')
                    ->label('Kategoria')
                    ->searchable(['name', 'slug'])
                    ->sortable()
                    ->description(fn (ContentCategory $record): string => $record->slug)
                    ->weight('semibold'),
                TextColumn::make('articles_summary')
                    ->label('Artykuły')
                    ->state(fn (ContentCategory $record): string => 'Wszystkie: '.number_format((int) $record->articles_count, 0, ',', ' '))
                    ->description(fn (ContentCategory $record): string => implode(' · ', [
                        'Publiczne: '.number_format((int) $record->publicly_visible_articles_count, 0, ',', ' '),
                        'Aktywne: '.number_format((int) $record->actively_distributed_articles_count, 0, ',', ' '),
                    ]))
                    ->wrap(),
                IconColumn::make('is_active')
                    ->label('Aktywna')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Kolejność')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktywna'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
                DeleteAction::make()
                    ->label('Usuń')
                    ->disabled(fn (ContentCategory $record): bool => ! $record->canBeDeleted()),
            ])
            ->reorderable('position')
            ->defaultSort('position');
    }
}
