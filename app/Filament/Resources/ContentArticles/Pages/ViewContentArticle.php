<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Filament\Resources\ContentArticles\Pages\Concerns\InteractsWithContentArticleWorkflowActions;
use Filament\Actions\Action;
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
            Action::make('preview')
                ->label('Podgląd')
                ->url(fn (): string => route('admin.newsroom.articles.preview', $this->record))
                ->openUrlInNewTab(),
            ...$this->contentArticleWorkflowActions(),
        ];
    }
}
