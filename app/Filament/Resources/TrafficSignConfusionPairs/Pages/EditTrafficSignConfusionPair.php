<?php

namespace App\Filament\Resources\TrafficSignConfusionPairs\Pages;

use App\Filament\Resources\TrafficSignConfusionPairs\TrafficSignConfusionPairResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrafficSignConfusionPair extends EditRecord
{
    protected static string $resource = TrafficSignConfusionPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
