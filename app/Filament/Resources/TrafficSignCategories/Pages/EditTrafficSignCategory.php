<?php

namespace App\Filament\Resources\TrafficSignCategories\Pages;

use App\Filament\Resources\TrafficSignCategories\TrafficSignCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrafficSignCategory extends EditRecord
{
    protected static string $resource = TrafficSignCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
