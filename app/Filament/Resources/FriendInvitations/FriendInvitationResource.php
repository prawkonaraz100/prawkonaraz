<?php

namespace App\Filament\Resources\FriendInvitations;

use App\Filament\Resources\FriendInvitations\Pages\ListFriendInvitations;
use App\Filament\Resources\FriendInvitations\Pages\ViewFriendInvitation;
use App\Filament\Resources\FriendInvitations\Schemas\FriendInvitationInfolist;
use App\Filament\Resources\FriendInvitations\Tables\FriendInvitationsTable;
use App\Models\FriendInvitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FriendInvitationResource extends Resource
{
    protected static ?string $model = FriendInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $modelLabel = 'zaproszenie znajomego';

    protected static ?string $pluralModelLabel = 'zaproszenia znajomych';

    protected static ?string $navigationLabel = 'Zaproszenia znajomych';

    protected static string|UnitEnum|null $navigationGroup = 'Dostęp';

    protected static ?int $navigationSort = 30;

    public static function infolist(Schema $schema): Schema
    {
        return FriendInvitationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FriendInvitationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'acceptedBy:id,name,email',
                'guestProductAccessGrant:id,status,expires_at,revoked_at',
                'owner:id,name,email',
                'ownerPurchaseOrder:id,public_id,status,refunded_at',
                'productPlan:id,code,name',
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFriendInvitations::route('/'),
            'view' => ViewFriendInvitation::route('/{record}'),
        ];
    }
}
