<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Filament\Resources\ContentArticles\ContentArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentArticles extends ListRecords
{
    protected static string $resource = ContentArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
