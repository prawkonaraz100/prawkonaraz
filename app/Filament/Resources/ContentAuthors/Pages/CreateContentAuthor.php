<?php

namespace App\Filament\Resources\ContentAuthors\Pages;

use App\Filament\Resources\ContentAuthors\ContentAuthorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentAuthor extends CreateRecord
{
    protected static string $resource = ContentAuthorResource::class;
}
