<?php

namespace App\Filament\Resources\TrafficSignQueryMapEntries\Pages;

use App\Filament\Resources\TrafficSignQueryMapEntries\TrafficSignQueryMapEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrafficSignQueryMapEntries extends ListRecords
{
    protected static string $resource = TrafficSignQueryMapEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
