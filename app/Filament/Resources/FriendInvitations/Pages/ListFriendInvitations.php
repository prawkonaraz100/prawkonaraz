<?php

namespace App\Filament\Resources\FriendInvitations\Pages;

use App\Filament\Resources\FriendInvitations\FriendInvitationResource;
use App\Models\FriendInvitation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFriendInvitations extends ListRecords
{
    protected static string $resource = FriendInvitationResource::class;

    public function getHeading(): string
    {
        return 'Zaproszenia znajomych';
    }

    public function getSubheading(): ?string
    {
        return 'Podgląd slotów właścicieli, oczekujących linków i aktywnych dostępów gości.';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Wszystkie')
                ->badge(number_format(FriendInvitation::query()->count(), 0, ',', ' ')),
            FriendInvitation::STATUS_PENDING => Tab::make('Oczekujące')
                ->badge(number_format(FriendInvitation::query()->where('status', FriendInvitation::STATUS_PENDING)->count(), 0, ',', ' '))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', FriendInvitation::STATUS_PENDING)),
            FriendInvitation::STATUS_ACCEPTED => Tab::make('Aktywne')
                ->badge(number_format(FriendInvitation::query()->where('status', FriendInvitation::STATUS_ACCEPTED)->count(), 0, ',', ' '))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', FriendInvitation::STATUS_ACCEPTED)),
            FriendInvitation::STATUS_REVOKED => Tab::make('Unieważnione')
                ->badge(number_format(FriendInvitation::query()->where('status', FriendInvitation::STATUS_REVOKED)->count(), 0, ',', ' '))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', FriendInvitation::STATUS_REVOKED)),
            FriendInvitation::STATUS_EXPIRED => Tab::make('Wygasłe')
                ->badge(number_format(FriendInvitation::query()->where('status', FriendInvitation::STATUS_EXPIRED)->count(), 0, ',', ' '))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', FriendInvitation::STATUS_EXPIRED)),
            FriendInvitation::STATUS_CONVERTED => Tab::make('Po zakupie gościa')
                ->badge(number_format(FriendInvitation::query()->where('status', FriendInvitation::STATUS_CONVERTED)->count(), 0, ',', ' '))
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', FriendInvitation::STATUS_CONVERTED)),
        ];
    }
}
