<?php

namespace App\Filament\Resources\FriendInvitations\Tables;

use App\Models\FriendInvitation;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FriendInvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po ID, właścicielu, gościu albo ostatnich znakach kodu')
            ->persistFiltersInSession()
            ->filtersFormColumns(3)
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak zaproszeń')
            ->emptyStateDescription('Zaproszenia pojawią się tutaj po pierwszym wygenerowaniu linku przez właściciela planu.')
            ->emptyStateIcon(Heroicon::OutlinedTicket)
            ->columns([
                TextColumn::make('public_id')
                    ->label('Zaproszenie')
                    ->description(fn (FriendInvitation $record): string => 'Kod kończy się na: '.($record->display_code_last4 ?: '-'))
                    ->searchable()
                    ->copyable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (FriendInvitation $record): string => static::statusLabel($record->status))
                    ->badge()
                    ->color(fn (FriendInvitation $record): string => static::statusColor($record->status)),
                TextColumn::make('owner.name')
                    ->label('Właściciel')
                    ->description(fn (FriendInvitation $record): string => $record->owner?->email ?? '-')
                    ->searchable(['owner.name', 'owner.email'])
                    ->wrap(),
                TextColumn::make('acceptedBy.name')
                    ->label('Gość')
                    ->description(fn (FriendInvitation $record): string => $record->acceptedBy?->email ?? '-')
                    ->placeholder('-')
                    ->searchable(['acceptedBy.name', 'acceptedBy.email'])
                    ->wrap(),
                TextColumn::make('productPlan.name')
                    ->label('Plan')
                    ->description(fn (FriendInvitation $record): string => $record->productPlan?->code ?? '-')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label('Link ważny do')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('guest_access_expires_at')
                    ->label('Dostęp gościa do')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(static::statusOptions()),
                Filter::make('expires_at')
                    ->label('Ważność linku')
                    ->form([
                        DatePicker::make('from')
                            ->label('Od'),
                        DatePicker::make('until')
                            ->label('Do'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('expires_at', '>=', (string) $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('expires_at', '<=', (string) $data['until']),
                            );
                    }),
                Filter::make('active_guest_access')
                    ->label('Tylko aktywni goście')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', FriendInvitation::STATUS_ACCEPTED)
                        ->where('guest_access_expires_at', '>', now())),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Szczegóły'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    protected static function statusOptions(): array
    {
        return [
            FriendInvitation::STATUS_PENDING => 'Oczekujące',
            FriendInvitation::STATUS_ACCEPTED => 'Aktywne',
            FriendInvitation::STATUS_REVOKED => 'Unieważnione',
            FriendInvitation::STATUS_EXPIRED => 'Wygasłe',
            FriendInvitation::STATUS_CONVERTED => 'Gość kupił plan',
        ];
    }

    protected static function statusLabel(string $status): string
    {
        return static::statusOptions()[$status] ?? $status;
    }

    protected static function statusColor(string $status): string
    {
        return match ($status) {
            FriendInvitation::STATUS_PENDING => 'warning',
            FriendInvitation::STATUS_ACCEPTED => 'success',
            FriendInvitation::STATUS_REVOKED => 'danger',
            FriendInvitation::STATUS_EXPIRED => 'gray',
            FriendInvitation::STATUS_CONVERTED => 'info',
            default => 'gray',
        };
    }
}
