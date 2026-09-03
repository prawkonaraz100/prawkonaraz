<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UserAcquisitionVsActivityChart;
use App\Filament\Resources\Users\Widgets\UserGrowthChart;
use App\Filament\Resources\Users\Widgets\UserOverviewStats;
use App\Filament\Resources\Users\Widgets\UserVerificationChart;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return 'Użytkownicy';
    }

    public function getSubheading(): ?string
    {
        return 'Zarządzaj kontami, statusem weryfikacji i dostępem do panelu administracyjnego.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Dodaj użytkownika'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserOverviewStats::class,
            UserGrowthChart::class,
            UserAcquisitionVsActivityChart::class,
            UserVerificationChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 4,
            'xl' => 6,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Wszyscy')
                ->badge(number_format(User::query()->count(), 0, ',', ' ')),
            'admins' => Tab::make('Administratorzy')
                ->badge(number_format(User::query()
                    ->where(fn (Builder $query): Builder => $query
                        ->where('role', User::ROLE_ADMIN)
                        ->orWhere('is_admin', true))
                    ->count(), 0, ',', ' '))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where(fn (Builder $query): Builder => $query
                        ->where('role', User::ROLE_ADMIN)
                        ->orWhere('is_admin', true))),
            'moderators' => Tab::make('Moderatorzy')
                ->badge(number_format(User::query()->where('role', User::ROLE_MODERATOR)->count(), 0, ',', ' '))
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('role', User::ROLE_MODERATOR)),
            'test_accounts' => Tab::make('Konta testowe')
                ->badge(number_format(User::query()->where('is_test_account', true)->count(), 0, ',', ' '))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_test_account', true)),
            'banned' => Tab::make('Zablokowani')
                ->badge(number_format(User::query()->whereNotNull('banned_at')->count(), 0, ',', ' '))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('banned_at')),
            'pending' => Tab::make('Bez weryfikacji')
                ->badge(number_format(User::query()->whereNull('email_verified_at')->count(), 0, ',', ' '))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('email_verified_at')),
        ];
    }
}
