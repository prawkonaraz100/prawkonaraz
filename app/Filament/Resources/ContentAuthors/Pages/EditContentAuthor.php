<?php

namespace App\Filament\Resources\ContentAuthors\Pages;

use App\Filament\Resources\ContentAuthors\ContentAuthorResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContentAuthor extends EditRecord
{
    protected static string $resource = ContentAuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
