<?php

namespace App\Filament\Resources\TrafficSigns\Pages;

use App\Filament\Resources\TrafficSigns\TrafficSignResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrafficSign extends EditRecord
{
    protected static string $resource = TrafficSignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
