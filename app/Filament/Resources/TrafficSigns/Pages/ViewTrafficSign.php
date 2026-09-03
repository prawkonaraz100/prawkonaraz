<?php

namespace App\Filament\Resources\TrafficSigns\Pages;

use App\Filament\Resources\TrafficSigns\TrafficSignResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrafficSign extends ViewRecord
{
    protected static string $resource = TrafficSignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
