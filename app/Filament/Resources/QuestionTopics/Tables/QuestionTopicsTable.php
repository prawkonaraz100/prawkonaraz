<?php

namespace App\Filament\Resources\QuestionTopics\Tables;

use App\Models\QuestionTopic;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class QuestionTopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po kluczu lub nazwie działu')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Brak działów pytań')
            ->emptyStateDescription('Działy są tworzone przez klasyfikator pytań. Ten widok służy do zarządzania globalnymi zdjęciami banerów.')
            ->emptyStateIcon(Heroicon::OutlinedPhoto)
            ->columns([
                TextColumn::make('name')
                    ->label('Dział')
                    ->searchable(['name', 'key'])
                    ->sortable()
                    ->description(fn (QuestionTopic $record): string => $record->key)
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('hero_image_path')
                    ->label('Globalne zdjęcie')
                    ->placeholder('Brak - użyje fallbacku technicznego')
                    ->limit(46)
                    ->wrap(),
                TextColumn::make('hero_image_position')
                    ->label('Kadrowanie')
                    ->placeholder('center'),
                TextColumn::make('questions_count')
                    ->label('Pytania')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('category_heroes_count')
                    ->label('Wyjątki kategorii')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktywny')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktywny'),
                TernaryFilter::make('has_global_hero')
                    ->label('Ma globalne zdjęcie')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('hero_image_path'),
                        false: fn ($query) => $query->whereNull('hero_image_path'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj zdjęcie'),
            ])
            ->defaultSort('sort_order');
    }
}
