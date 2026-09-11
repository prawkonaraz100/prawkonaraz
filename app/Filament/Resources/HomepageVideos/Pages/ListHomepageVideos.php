<?php

namespace App\Filament\Resources\HomepageVideos\Pages;

use App\Filament\Resources\HomepageVideos\HomepageVideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomepageVideos extends ListRecords
{
    protected static string $resource = HomepageVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
