<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Support\AuditLogPresenter;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function getHeading(): string
    {
        return 'Szczegóły wpisu audytu';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return sprintf(
            '%s · %s · %s',
            AuditLogPresenter::actionLabel($record->action),
            AuditLogPresenter::actorLabel($record),
            $record->created_at?->format('d.m.Y H:i') ?? '-',
        );
    }
}
