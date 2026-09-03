<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Support\AuditLogService;
use App\Support\UserAvatarService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po imieniu, nazwisku lub adresie e-mail')
            ->persistFiltersInSession()
            ->filtersFormColumns(2)
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak użytkowników')
            ->emptyStateDescription('Dodaj pierwsze konto albo zmień aktywne filtry.')
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->columns([
                TextColumn::make('name')
                    ->label('Konto')
                    ->description(function (User $record): string {
                        $parts = array_filter([
                            $record->email,
                            $record->profile?->targetCategory?->name,
                        ]);

                        return implode(' · ', $parts);
                    })
                    ->sortable()
                    ->searchable(['name', 'email'])
                    ->wrap(),
                TextColumn::make('account_status')
                    ->label('Status konta')
                    ->state(fn (User $record): string => $record->email_verified_at ? 'Zweryfikowane' : 'Czeka na weryfikację')
                    ->badge()
                    ->color(fn (User $record): string => $record->email_verified_at ? 'success' : 'warning'),
                TextColumn::make('role')
                    ->label('Rola')
                    ->state(fn (User $record): string => $record->roleLabel())
                    ->badge()
                    ->color(fn (User $record): string => $record->roleColor()),
                TextColumn::make('moderator_pool')
                    ->label('Pula moderatora')
                    ->state(fn (User $record): string => $record->isModerator()
                        ? number_format($record->moderatorAccountsUsed(), 0, ',', ' ').' / '.number_format($record->moderatorQuotaLimit(), 0, ',', ' ')
                        : '-')
                    ->description(fn (User $record): string => $record->isModerator()
                        ? 'Pozostało: '.number_format($record->moderatorQuotaRemaining(), 0, ',', ' ')
                        : 'nie dotyczy')
                    ->toggleable(),
                TextColumn::make('ban_status')
                    ->label('Blokada')
                    ->state(fn (User $record): string => $record->isBanned() ? 'Zablokowane' : 'Aktywne')
                    ->description(fn (User $record): string => $record->isBanned()
                        ? ($record->banned_at?->format('d.m.Y H:i') ?? 'blokada bez daty')
                        : 'konto aktywne')
                    ->badge()
                    ->color(fn (User $record): string => $record->isBanned() ? 'danger' : 'success'),
                TextColumn::make('study_sessions_count')
                    ->label('Nauka')
                    ->state(fn (User $record): string => number_format((int) $record->study_sessions_count, 0, ',', ' ').' sesji')
                    ->description(fn (User $record): string => number_format((int) $record->question_progress_count, 0, ',', ' ').' rekordów progresu')
                    ->sortable(),
                TextColumn::make('latest_ip')
                    ->label('Ostatnie IP')
                    ->state(fn (User $record): string => (string) ($record->latest_tracked_ip ?: $record->latest_session_ip ?: $record->latest_audit_ip ?: '-'))
                    ->description(function (User $record): string {
                        $parts = array_filter([
                            static::formatLocation($record->latest_tracked_city_name ?? null, $record->latest_tracked_country_name ?? null),
                            $record->latest_tracked_source ?: null,
                            $record->latest_session_ip && ! $record->latest_tracked_ip ? 'sesja' : null,
                            $record->latest_audit_ip && ! $record->latest_session_ip ? 'panel' : null,
                            (int) ($record->ip_histories_last_30_days_count ?? 0) > 0
                                ? number_format((int) $record->ip_histories_last_30_days_count, 0, ',', ' ').' śladów / 30 dni'
                                : null,
                        ]);

                        return $parts === [] ? 'Brak śladu IP' : implode(' · ', $parts);
                    })
                    ->toggleable(),
                TextColumn::make('email_verified_at')
                    ->label('Zweryfikowano e-mail')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Brak')
                    ->sortable(),
                TextColumn::make('last_activity')
                    ->label('Ostatnia aktywność')
                    ->state(function (User $record): string {
                        return static::formatDateTimeValue(
                            $record->study_sessions_max_completed_at ?? $record->question_progress_max_last_answered_at,
                        );
                    })
                    ->description(fn (User $record): string => number_format((int) $record->audit_logs_count, 0, ',', ' ').' akcji w panelu')
                    ->sortable(query: function ($query, string $direction) {
                        return $query
                            ->orderBy('study_sessions_max_completed_at', $direction)
                            ->orderBy('question_progress_max_last_answered_at', $direction);
                    }),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Zaktualizowano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('Administrator'),
                TernaryFilter::make('is_test_account')
                    ->label('Konto testowe'),
                TernaryFilter::make('email_verified_at')
                    ->label('Zweryfikowany e-mail')
                    ->nullable(),
                TernaryFilter::make('banned_at')
                    ->label('Zablokowany')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggleBan')
                    ->label(fn (User $record): string => $record->isBanned() ? 'Odbanuj' : 'Zablokuj')
                    ->icon(fn (User $record) => $record->isBanned() ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedNoSymbol)
                    ->color(fn (User $record): string => $record->isBanned() ? 'gray' : 'danger')
                    ->disabled(fn (User $record): bool => auth()->id() === $record->id)
                    ->form(fn (User $record): array => $record->isBanned() ? [] : [
                        Textarea::make('reason')
                            ->label('Powód blokady')
                            ->rows(4)
                            ->required()
                            ->helperText('To pole zostanie zapisane przy użytkowniku i w dzienniku audytu.'),
                    ])
                    ->requiresConfirmation(fn (User $record): bool => $record->isBanned())
                    ->modalHeading(fn (User $record): string => $record->isBanned() ? 'Odblokować konto?' : 'Zablokować konto?')
                    ->modalDescription(fn (User $record): string => $record->isBanned()
                        ? 'Użytkownik odzyska możliwość logowania i korzystania z serwisu.'
                        : 'Po blokadzie użytkownik nie zaloguje się, a aktywne sesje zostaną przerwane.')
                    ->action(function (User $record, array $data): void {
                        $actor = auth()->user();

                        if ($record->isBanned()) {
                            $record->forceFill([
                                'banned_at' => null,
                                'ban_reason' => null,
                            ])->save();

                            app(AuditLogService::class)->record(
                                'user.unbanned',
                                'user',
                                (string) $record->getKey(),
                                $actor,
                            );

                            return;
                        }

                        $record->forceFill([
                            'banned_at' => now(),
                            'ban_reason' => trim((string) ($data['reason'] ?? '')),
                        ])->save();

                        DB::table('sessions')
                            ->where('user_id', $record->getKey())
                            ->delete();

                        app(AuditLogService::class)->record(
                            'user.banned',
                            'user',
                            (string) $record->getKey(),
                            $actor,
                            ['reason' => $record->ban_reason],
                        );
                    }),
                Action::make('removeAvatar')
                    ->label('Usuń zdjęcie')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->color('warning')
                    ->visible(fn (User $record): bool => app(UserAvatarService::class)->hasVisibleAvatar($record))
                    ->requiresConfirmation()
                    ->modalHeading('Usunąć zdjęcie profilowe?')
                    ->modalDescription('Własne zdjęcie zostanie usunięte, a avatar z Google lub Facebooka przestanie być używany jako fallback. Konto pozostanie aktywne.')
                    ->action(function (User $record): void {
                        app(UserAvatarService::class)->removeAvatarByAdmin($record);

                        app(AuditLogService::class)->record(
                            'user.avatar_removed',
                            'user',
                            (string) $record->getKey(),
                            auth()->user(),
                        );

                        Notification::make()
                            ->title('Zdjęcie profilowe zostało usunięte')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function formatDateTimeValue(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('d.m.Y H:i');
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->format('d.m.Y H:i');
        }

        return '-';
    }

    protected static function formatLocation(?string $cityName, ?string $countryName): ?string
    {
        $parts = array_filter([
            filled($cityName) ? trim((string) $cityName) : null,
            filled($countryName) ? trim((string) $countryName) : null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
