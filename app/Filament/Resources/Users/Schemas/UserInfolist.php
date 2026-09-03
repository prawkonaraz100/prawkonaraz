<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Konto')
                        ->description('Najważniejsze dane użytkownika i stan konta.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            TextEntry::make('name')
                                ->label('Imię i nazwisko'),
                            TextEntry::make('email')
                                ->label('Adres e-mail'),
                            TextEntry::make('account_status')
                                ->label('Status konta')
                                ->state(fn (User $record): string => $record->email_verified_at ? 'Zweryfikowane' : 'Czeka na weryfikację')
                                ->badge()
                                ->color(fn (User $record): string => $record->email_verified_at ? 'success' : 'warning'),
                            TextEntry::make('role')
                                ->label('Rola')
                                ->state(fn (User $record): string => $record->roleLabel())
                                ->badge()
                                ->color(fn (User $record): string => $record->roleColor()),
                            TextEntry::make('moderator_quota')
                                ->label('Pula moderatora')
                                ->state(fn (User $record): string => number_format($record->moderatorQuotaLimit(), 0, ',', ' ')),
                            TextEntry::make('moderator_pool_usage')
                                ->label('Wykorzystanie puli')
                                ->state(fn (User $record): string => $record->isModerator()
                                    ? number_format($record->moderatorAccountsUsed(), 0, ',', ' ').' / '.number_format($record->moderatorQuotaLimit(), 0, ',', ' ')
                                    : '-')
                                ->helperText('Dotyczy wyłącznie indywidualnej puli kont moderatora.'),
                            TextEntry::make('email_verified_at')
                                ->label('Zweryfikowano e-mail')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
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
                    Section::make('Aktywność')
                        ->description('To pokazuje, czy konto żyje tylko w systemie, czy faktycznie korzysta z nauki i panelu.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            TextEntry::make('study_sessions_count')
                                ->label('Sesje nauki')
                                ->numeric(),
                            TextEntry::make('question_progress_count')
                                ->label('Rekordy progresu')
                                ->numeric(),
                            TextEntry::make('audit_logs_count')
                                ->label('Akcje w panelu')
                                ->numeric(),
                            TextEntry::make('last_learning_activity')
                                ->label('Ostatnia nauka')
                                ->state(fn (User $record): string => static::formatDateTimeValue(
                                    $record->study_sessions_max_completed_at ?? $record->question_progress_max_last_answered_at,
                                ))
                                ->placeholder('-'),
                            IconEntry::make('is_admin')
                                ->label('Dostęp do admina')
                                ->boolean(),
                            IconEntry::make('is_test_account')
                                ->label('Konto testowe')
                                ->boolean(),
                        ])
                        ->columns(2),
                    Section::make('Bezpieczeństwo i dostęp')
                        ->description('Blokada konta, ślad IP i ostatni ruch użytkownika w systemie.')
                        ->columnSpan([
                            'lg' => 12,
                        ])
                        ->schema([
                            TextEntry::make('ban_status')
                                ->label('Status blokady')
                                ->state(fn (User $record): string => $record->isBanned() ? 'Zablokowane' : 'Aktywne')
                                ->badge()
                                ->color(fn (User $record): string => $record->isBanned() ? 'danger' : 'success'),
                            TextEntry::make('banned_at')
                                ->label('Zablokowano')
                                ->state(fn (User $record): string => static::formatDateTimeValue($record->banned_at))
                                ->placeholder('-'),
                            TextEntry::make('ban_reason')
                                ->label('Powód blokady')
                                ->placeholder('-')
                                ->columnSpan(2),
                            IconEntry::make('requires_password_change')
                                ->label('Wymuszona zmiana hasła')
                                ->boolean(),
                            IconEntry::make('is_temporary_account')
                                ->label('Konto tymczasowe')
                                ->boolean(),
                            TextEntry::make('temporary_account_expires_at')
                                ->label('Ważne do')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('claimed_at')
                                ->label('Przejęte przez użytkownika')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('latest_session_ip')
                                ->label('Ostatnie IP sesji')
                                ->placeholder('-'),
                            TextEntry::make('latest_session_last_activity')
                                ->label('Ostatnia aktywność sesji')
                                ->state(fn (User $record): string => static::formatDateTimeValue($record->latest_session_last_activity, timestamp: true))
                                ->placeholder('-'),
                            TextEntry::make('latest_location')
                                ->label('Ostatnia lokalizacja')
                                ->state(fn (User $record): string => static::formatLocation(
                                    $record->latest_tracked_city_name ?? null,
                                    $record->latest_tracked_country_name ?? null,
                                ))
                                ->placeholder('-'),
                            TextEntry::make('sessions_count')
                                ->label('Aktywne rekordy sesji')
                                ->numeric()
                                ->placeholder('0'),
                            TextEntry::make('latest_audit_ip')
                                ->label('Ostatnie IP w panelu')
                                ->placeholder('-'),
                            TextEntry::make('latest_audit_at')
                                ->label('Ostatnia akcja w panelu')
                                ->state(fn (User $record): string => static::formatDateTimeValue($record->latest_audit_at))
                                ->placeholder('-'),
                            TextEntry::make('ip_history_last_30_days')
                                ->label('Historia IP z ostatnich 30 dni')
                                ->state(fn (User $record): string => static::renderIpHistory($record))
                                ->html()
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ]),
                Section::make('Profil kursanta')
                    ->description('Dodatkowe dane onboardingowe i cel nauki, jeśli profil został już uzupełniony.')
                    ->schema([
                        TextEntry::make('profile.display_name')
                            ->label('Nazwa wyświetlana')
                            ->placeholder('-'),
                        TextEntry::make('profile.targetCategory.name')
                            ->label('Docelowa kategoria')
                            ->placeholder('-'),
                        TextEntry::make('profile.study_streak')
                            ->label('Seria nauki')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('profile.last_study_date')
                            ->label('Ostatni dzień nauki')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                        TextEntry::make('profile.exam_date')
                            ->label('Data egzaminu')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                        TextEntry::make('profile.tier')
                            ->label('Plan')
                            ->placeholder('-'),
                        TextEntry::make('profile.onboarding_step')
                            ->label('Etap onboardingu')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
            ]);
    }

    protected static function formatDateTimeValue(mixed $value, bool $timestamp = false): string
    {
        if ($value instanceof Carbon) {
            return $value->format('d.m.Y H:i');
        }

        if ($timestamp && is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value)->format('d.m.Y H:i');
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->format('d.m.Y H:i');
        }

        return '-';
    }

    protected static function renderIpHistory(User $record): string
    {
        if (! $record->relationLoaded('ipHistories') || $record->ipHistories->isEmpty()) {
            return '<span class="text-sm text-gray-600">Brak śladów IP z ostatnich 30 dni.</span>';
        }

        $rows = $record->ipHistories
            ->sortByDesc('last_seen_at')
            ->take(20)
            ->map(function ($history): string {
                $seenAt = static::formatDateTimeValue($history->last_seen_at);
                $source = e((string) ($history->source ?? 'www'));
                $ipAddress = e((string) ($history->ip_address ?? '-'));
                $path = filled($history->path) ? e((string) $history->path) : 'brak ścieżki';
                $hits = number_format((int) ($history->hit_count ?? 1), 0, ',', ' ');
                $location = e(static::formatLocation($history->city_name ?? null, $history->country_name ?? null));

                return sprintf(
                    '<div class="flex flex-col gap-1 border-b border-gray-200 py-2 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">'.
                    '<div class="min-w-0"><div class="text-sm font-medium text-gray-950">%s</div><div class="text-xs text-gray-600">%s · %s · %s</div></div>'.
                    '<div class="text-xs text-gray-700">%s · %s zdarzeń</div></div>',
                    $ipAddress,
                    $source,
                    $path,
                    $location,
                    e($seenAt),
                    $hits,
                );
            })
            ->implode('');

        return '<div class="rounded-lg border border-gray-200 bg-white px-4 py-2">'.$rows.'</div>';
    }

    protected static function formatLocation(?string $cityName, ?string $countryName): string
    {
        $parts = array_filter([
            filled($cityName) ? trim((string) $cityName) : null,
            filled($countryName) ? trim((string) $countryName) : null,
        ]);

        return $parts === [] ? '-' : implode(', ', $parts);
    }
}
