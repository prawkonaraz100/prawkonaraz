<?php

namespace App\Filament\Resources\FriendInvitations\Schemas;

use App\Models\FriendInvitation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FriendInvitationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Zaproszenie')
                        ->description('Stan linku/kodu i daty graniczne.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            TextEntry::make('public_id')
                                ->label('Publiczne ID')
                                ->copyable(),
                            TextEntry::make('status')
                                ->label('Status')
                                ->state(fn (FriendInvitation $record): string => static::statusLabel($record->status))
                                ->badge()
                                ->color(fn (FriendInvitation $record): string => static::statusColor($record->status)),
                            TextEntry::make('display_code_last4')
                                ->label('Końcówka kodu')
                                ->placeholder('-'),
                            TextEntry::make('revoked_reason')
                                ->label('Powód unieważnienia')
                                ->state(fn (FriendInvitation $record): string => static::revokedReasonLabel($record->revoked_reason))
                                ->placeholder('-'),
                            TextEntry::make('expires_at')
                                ->label('Link ważny do')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('accepted_at')
                                ->label('Zaakceptowano')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('converted_at')
                                ->label('Gość kupił plan')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('revoked_at')
                                ->label('Unieważniono')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                        ])
                        ->columns(2),
                    Section::make('Dostęp gościa')
                        ->description('Okres dostępu wynikający z zaproszenia oraz efekt ewentualnego refundu.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            TextEntry::make('guest_access_starts_at')
                                ->label('Start')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('guest_access_expires_at')
                                ->label('Koniec')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('guestProductAccessGrant.status')
                                ->label('Status grantu')
                                ->placeholder('-')
                                ->badge(),
                            TextEntry::make('guestProductAccessGrant.revoked_at')
                                ->label('Grant cofnięty')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('refund_processed_at')
                                ->label('Refund przetworzony')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('refund_buffer_expires_at')
                                ->label('Bufor po refundzie do')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                        ])
                        ->columns(2),
                ]),
                Section::make('Powiązania')
                    ->description('Kto wygenerował zaproszenie, kto je przyjął i z jakiego zakupu wynikało.')
                    ->schema([
                        TextEntry::make('owner.name')
                            ->label('Właściciel')
                            ->placeholder('-'),
                        TextEntry::make('owner.email')
                            ->label('E-mail właściciela')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('acceptedBy.name')
                            ->label('Gość')
                            ->placeholder('-'),
                        TextEntry::make('acceptedBy.email')
                            ->label('E-mail gościa')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('productPlan.name')
                            ->label('Plan właściciela')
                            ->placeholder('-'),
                        TextEntry::make('ownerPurchaseOrder.public_id')
                            ->label('Zamówienie właściciela')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('ownerPurchaseOrder.status')
                            ->label('Status zamówienia')
                            ->placeholder('-')
                            ->badge(),
                        TextEntry::make('ownerPurchaseOrder.refunded_at')
                            ->label('Refund zamówienia')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(4),
            ]);
    }

    protected static function statusLabel(string $status): string
    {
        return [
            FriendInvitation::STATUS_PENDING => 'Oczekujące',
            FriendInvitation::STATUS_ACCEPTED => 'Aktywne',
            FriendInvitation::STATUS_REVOKED => 'Unieważnione',
            FriendInvitation::STATUS_EXPIRED => 'Wygasłe',
            FriendInvitation::STATUS_CONVERTED => 'Gość kupił plan',
        ][$status] ?? $status;
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

    protected static function revokedReasonLabel(?string $reason): string
    {
        return match ($reason) {
            FriendInvitation::REVOKED_REASON_OWNER => 'Unieważnione przez właściciela',
            FriendInvitation::REVOKED_REASON_SLOT_TAKEN => 'Slot zajęty przez inne zaproszenie',
            FriendInvitation::REVOKED_REASON_REFUND => 'Refund planu właściciela',
            FriendInvitation::REVOKED_REASON_OWNER_ACCESS_INACTIVE => 'Dostęp właściciela nieaktywny',
            default => $reason ?: '-',
        };
    }
}
