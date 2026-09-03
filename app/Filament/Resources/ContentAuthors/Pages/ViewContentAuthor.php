<?php

namespace App\Filament\Resources\ContentAuthors\Pages;

use App\Filament\Resources\ContentAuthors\ContentAuthorResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContentAuthor extends ViewRecord
{
    protected static string $resource = ContentAuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
