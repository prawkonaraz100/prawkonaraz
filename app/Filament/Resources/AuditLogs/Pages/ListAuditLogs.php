<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    public function getHeading(): string
    {
        return 'Dziennik audytu';
    }

    public function getSubheading(): ?string
    {
        return 'Śledź działania operatorów i systemu, żeby szybko odtworzyć zmiany w panelu.';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Wszystkie')
                ->badge(number_format(AuditLog::query()->count(), 0, ',', ' ')),
            'today' => Tab::make('Dzisiaj')
                ->badge(number_format(AuditLog::query()->whereDate('created_at', today())->count(), 0, ',', ' '))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            'operators' => Tab::make('Operatorzy')
                ->badge(number_format(AuditLog::query()->whereNotNull('actor_user_id')->count(), 0, ',', ' '))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('actor_user_id')),
            'system' => Tab::make('System')
                ->badge(number_format(AuditLog::query()->whereNull('actor_user_id')->count(), 0, ',', ' '))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('actor_user_id')),
        ];
    }
}
