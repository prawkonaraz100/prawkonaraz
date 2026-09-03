<?php

namespace App\Filament\Resources\TrafficSignConfusionPairs\Pages;

use App\Filament\Resources\TrafficSignConfusionPairs\TrafficSignConfusionPairResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrafficSignConfusionPair extends ViewRecord
{
    protected static string $resource = TrafficSignConfusionPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
