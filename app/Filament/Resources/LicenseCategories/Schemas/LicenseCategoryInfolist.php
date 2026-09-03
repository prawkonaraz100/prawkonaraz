<?php

namespace App\Filament\Resources\LicenseCategories\Schemas;

use App\Models\LicenseCategory;
use Carbon\Carbon;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenseCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $sessionWindowDays = max((int) config('study.admin_activity_window_days', 90), 1);

        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość')
                        ->description('Podstawowe informacje identyfikujące kategorię w systemie i w interfejsie.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            TextEntry::make('code')
                                ->label('Kod'),
                            TextEntry::make('name')
                                ->label('Nazwa'),
                            TextEntry::make('slug')
                                ->label('Slug'),
                            TextEntry::make('description')
                                ->label('Opis')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Widoczność i kolejność')
                        ->description('Czy kategoria jest aktywna i gdzie wypada względem innych kategorii.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            IconEntry::make('is_active')
                                ->label('Aktywna')
                                ->boolean(),
                            TextEntry::make('sort_order')
                                ->label('Kolejność')
                                ->numeric(),
                            TextEntry::make('created_at')
                                ->label('Utworzono')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('updated_at')
                                ->label('Zaktualizowano')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                        ])
                        ->columns(2),
                ]),
                Section::make('Użycie w systemie')
                    ->description('To pokazuje, czy kategoria realnie pracuje w bazie pytań, w sesjach i w profilach kursantów.')
                    ->schema([
                        TextEntry::make('questions_count')
                            ->label('Wszystkie pytania')
                            ->numeric(),
                        TextEntry::make('active_questions_count')
                            ->label('Aktywne pytania')
                            ->numeric(),
                        TextEntry::make('ready_questions_count')
                            ->label('Gotowe do publikacji')
                            ->numeric(),
                        TextEntry::make('recent_study_sessions_count')
                            ->label("Sesje nauki ({$sessionWindowDays} dni)")
                            ->numeric(),
                        TextEntry::make('last_study_session_at')
                            ->label('Ostatnia sesja')
                            ->state(fn (LicenseCategory $record): ?string => $record->last_study_session_at
                                ? Carbon::parse((string) $record->last_study_session_at)->format('d.m.Y H:i')
                                : null)
                            ->placeholder('-'),
                        TextEntry::make('user_profiles_count')
                            ->label('Profile kursantów')
                            ->numeric(),
                        TextEntry::make('usage_summary')
                            ->label('Ocena użycia')
                            ->state(fn (LicenseCategory $record): string => static::usageSummary($record))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    protected static function usageSummary(LicenseCategory $record): string
    {
        $sessionWindowDays = max((int) config('study.admin_activity_window_days', 90), 1);

        if ((int) $record->questions_count === 0 && (int) $record->recent_study_sessions_count === 0 && (int) $record->user_profiles_count === 0) {
            return 'Ta kategoria nie jest jeszcze nigdzie używana.';
        }

        $parts = [];

        if ((int) $record->questions_count > 0) {
            $parts[] = 'jest powiązana z '.static::formatCount((int) $record->questions_count, ['pytaniem', 'pytaniami', 'pytaniami']);
        }

        if ((int) $record->recent_study_sessions_count > 0) {
            $parts[] = 'pojawiła się w '.static::formatCount((int) $record->recent_study_sessions_count, ['sesji', 'sesjach', 'sesjach']).' z ostatnich '.$sessionWindowDays.' dni';
        }

        if ((int) $record->user_profiles_count > 0) {
            $parts[] = 'jest ustawiona w '.static::formatCount((int) $record->user_profiles_count, ['profilu kursanta', 'profilach kursantów', 'profilach kursantów']);
        }

        return 'Kategoria '.implode(' · ', $parts).'.';
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $forms
     */
    protected static function formatCount(int $value, array $forms): string
    {
        $mod10 = $value % 10;
        $mod100 = $value % 100;

        $form = match (true) {
            $value === 1 => $forms[0],
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => $forms[1],
            default => $forms[2],
        };

        return number_format($value, 0, ',', ' ').' '.$form;
    }
}
