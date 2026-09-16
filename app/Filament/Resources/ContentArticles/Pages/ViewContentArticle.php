<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Filament\Resources\ContentArticles\Pages\Concerns\InteractsWithContentArticleWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContentArticle extends ViewRecord
{
    use InteractsWithContentArticleWorkflowActions;

    protected static string $resource = ContentArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ...$this->contentArticleWorkflowActions(),
        ];
    }
}
