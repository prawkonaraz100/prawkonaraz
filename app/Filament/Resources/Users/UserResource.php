<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'użytkownik';

    protected static ?string $pluralModelLabel = 'użytkownicy';

    protected static ?string $navigationLabel = 'Użytkownicy';

    protected static string|UnitEnum|null $navigationGroup = 'Dostęp';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('users.*')
            ->with([
                'profile.targetCategory',
                'ipHistories' => fn ($query) => $query
                    ->where('last_seen_at', '>=', now()->subDays(30))
                    ->limit(20),
            ])
            ->withCount([
                'studySessions',
                'questionProgress',
                'auditLogs',
                'quotaCountedModeratorAccounts as moderator_accounts_used_count',
                'ipHistories as ip_histories_last_30_days_count' => fn ($query) => $query
                    ->where('last_seen_at', '>=', now()->subDays(30)),
            ])
            ->withMax('studySessions', 'completed_at')
            ->withMax('questionProgress', 'last_answered_at')
            ->selectSub(
                DB::table('user_ip_histories')
                    ->select('ip_address')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('last_seen_at')
                    ->limit(1),
                'latest_tracked_ip'
            )
            ->selectSub(
                DB::table('user_ip_histories')
                    ->select('source')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('last_seen_at')
                    ->limit(1),
                'latest_tracked_source'
            )
            ->selectSub(
                DB::table('user_ip_histories')
                    ->select('country_name')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('last_seen_at')
                    ->limit(1),
                'latest_tracked_country_name'
            )
            ->selectSub(
                DB::table('user_ip_histories')
                    ->select('city_name')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('last_seen_at')
                    ->limit(1),
                'latest_tracked_city_name'
            )
            ->selectSub(
                DB::table('user_ip_histories')
                    ->select('last_seen_at')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('last_seen_at')
                    ->limit(1),
                'latest_tracked_at'
            )
            ->selectSub(
                DB::table('sessions')
                    ->select('ip_address')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNotNull('ip_address')
                    ->orderByDesc('last_activity')
                    ->limit(1),
                'latest_session_ip'
            )
            ->selectSub(
                DB::table('sessions')
                    ->select('last_activity')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNotNull('ip_address')
                    ->orderByDesc('last_activity')
                    ->limit(1),
                'latest_session_last_activity'
            )
            ->selectSub(
                DB::table('sessions')
                    ->selectRaw('count(*)')
                    ->whereColumn('user_id', 'users.id'),
                'sessions_count'
            )
            ->selectSub(
                DB::table('audit_logs')
                    ->select('ip_address')
                    ->whereColumn('actor_user_id', 'users.id')
                    ->whereNotNull('ip_address')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'latest_audit_ip'
            )
            ->selectSub(
                DB::table('audit_logs')
                    ->select('created_at')
                    ->whereColumn('actor_user_id', 'users.id')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'latest_audit_at'
            );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
