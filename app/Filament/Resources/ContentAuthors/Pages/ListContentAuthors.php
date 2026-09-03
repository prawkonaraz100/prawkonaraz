<?php

namespace App\Filament\Resources\ContentAuthors\Pages;

use App\Filament\Resources\ContentAuthors\ContentAuthorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentAuthors extends ListRecords
{
    protected static string $resource = ContentAuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
