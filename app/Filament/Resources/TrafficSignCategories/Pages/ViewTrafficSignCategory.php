<?php

namespace App\Filament\Resources\TrafficSignCategories\Pages;

use App\Filament\Resources\TrafficSignCategories\TrafficSignCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrafficSignCategory extends ViewRecord
{
    protected static string $resource = TrafficSignCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
