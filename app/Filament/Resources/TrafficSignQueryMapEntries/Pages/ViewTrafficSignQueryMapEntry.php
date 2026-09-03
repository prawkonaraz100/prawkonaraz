<?php

namespace App\Filament\Resources\TrafficSignQueryMapEntries\Pages;

use App\Filament\Resources\TrafficSignQueryMapEntries\TrafficSignQueryMapEntryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrafficSignQueryMapEntry extends ViewRecord
{
    protected static string $resource = TrafficSignQueryMapEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
