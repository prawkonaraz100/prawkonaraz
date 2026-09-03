<?php

namespace App\Filament\Resources\TrafficSignCategories\Pages;

use App\Filament\Resources\TrafficSignCategories\TrafficSignCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrafficSignCategories extends ListRecords
{
    protected static string $resource = TrafficSignCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
