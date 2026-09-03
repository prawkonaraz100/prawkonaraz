<?php

namespace App\Filament\Resources\LicenseCategories\Tables;

use App\Models\LicenseCategory;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicenseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        $sessionWindowDays = max((int) config('study.admin_activity_window_days', 90), 1);

        return $table
            ->searchPlaceholder('Szukaj po kodzie, nazwie lub slugu')
            ->persistFiltersInSession()
            ->filtersFormColumns(2)
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak kategorii')
            ->emptyStateDescription('Dodaj kategorię, żeby powiązać z nią pytania, sesje i preferencje kursantów.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->columns([
                TextColumn::make('code')
                    ->label('Kategoria')
                    ->sortable()
                    ->searchable(['code', 'name', 'slug'])
                    ->description(fn (LicenseCategory $record): string => implode(' · ', array_filter([
                        $record->name,
                        filled($record->slug) ? $record->slug : null,
                    ])))
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('usage_summary')
                    ->label('Użycie w bazie')
                    ->state(fn (LicenseCategory $record): string => 'Pytania: '.number_format((int) $record->questions_count, 0, ',', ' '))
                    ->description(fn (LicenseCategory $record): string => implode(' · ', [
                        'Aktywne: '.number_format((int) $record->active_questions_count, 0, ',', ' '),
                        'Gotowe: '.number_format((int) $record->ready_questions_count, 0, ',', ' '),
                    ]))
                    ->wrap(),
                TextColumn::make('traffic_summary')
                    ->label('Ruch i preferencje')
                    ->state(fn (LicenseCategory $record): string => 'Sesje '.$sessionWindowDays.' dni: '.number_format((int) $record->recent_study_sessions_count, 0, ',', ' '))
                    ->description(fn (LicenseCategory $record): string => implode(' · ', array_filter([
                        $record->last_study_session_at
                            ? 'Ostatnia sesja: '.Carbon::parse((string) $record->last_study_session_at)->format('d.m.Y H:i')
                            : null,
                        'Profile: '.number_format((int) $record->user_profiles_count, 0, ',', ' '),
                    ])))
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Widoczność')
                    ->state(fn (LicenseCategory $record): string => $record->is_active ? 'Aktywna' : 'Nieaktywna')
                    ->badge()
                    ->color(fn (LicenseCategory $record): string => $record->is_active ? 'success' : 'danger')
                    ->description(fn (LicenseCategory $record): string => $record->questions_count > 0 ? 'Kategoria używana w bazie' : 'Brak powiązanych pytań'),
                TextColumn::make('sort_order')
                    ->label('Kolejność')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktywna'),
                Filter::make('used_in_system')
                    ->label('Używana w systemie')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $innerQuery): Builder {
                        return $innerQuery
                            ->has('questions')
                            ->orHas('studySessions')
                            ->orHas('userProfiles');
                    })),
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
}
