<?php

namespace App\Filament\Resources\TrafficSignQueryMapEntries\Pages;

use App\Filament\Resources\TrafficSignQueryMapEntries\TrafficSignQueryMapEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrafficSignQueryMapEntry extends EditRecord
{
    protected static string $resource = TrafficSignQueryMapEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
