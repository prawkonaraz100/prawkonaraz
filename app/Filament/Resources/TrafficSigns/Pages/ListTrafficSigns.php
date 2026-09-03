<?php

namespace App\Filament\Resources\TrafficSigns\Pages;

use App\Filament\Resources\TrafficSigns\TrafficSignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrafficSigns extends ListRecords
{
    protected static string $resource = TrafficSignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
