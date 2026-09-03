<?php

namespace App\Filament\Resources\ContentAuthors\Tables;

use App\Models\ContentAuthor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContentAuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po imieniu, slugu lub roli')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak autorów')
            ->emptyStateDescription('Dodaj pierwszy profil autora, żeby powiązać go ze znakami i zbudować warstwę E-E-A-T.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->columns([
                TextColumn::make('name')
                    ->label('Autor')
                    ->searchable(['name', 'slug', 'job_title'])
                    ->sortable()
                    ->description(fn (ContentAuthor $record): string => implode(' · ', array_filter([
                        $record->job_title,
                        filled($record->slug) ? $record->slug : null,
                    ])))
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('traffic_summary')
                    ->label('Znaki')
                    ->state(fn (ContentAuthor $record): string => 'Wszystkie: '.number_format((int) $record->traffic_signs_count, 0, ',', ' '))
                    ->description(fn (ContentAuthor $record): string => 'Opublikowane: '.number_format((int) $record->published_traffic_signs_count, 0, ',', ' '))
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Widoczność')
                    ->state(fn (ContentAuthor $record): string => static::publicationStateLabel($record))
                    ->badge()
                    ->color(fn (ContentAuthor $record): string => static::publicationStateColor($record)),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Opublikowany'),
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
            ->defaultSort('updated_at', 'desc');
    }

    protected static function publicationStateLabel(ContentAuthor $record): string
    {
        if (! $record->is_published) {
            return 'Szkic';
        }

        if ($record->published_at === null) {
            return 'Brak daty';
        }

        return $record->published_at->isFuture() ? 'Zaplanowany' : 'Opublikowany';
    }

    protected static function publicationStateColor(ContentAuthor $record): string
    {
        return match (static::publicationStateLabel($record)) {
            'Opublikowany' => 'success',
            'Zaplanowany', 'Brak daty' => 'warning',
            default => 'gray',
        };
    }
}
