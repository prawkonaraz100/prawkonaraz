<?php

namespace App\Filament\Resources\ContentTopics\Pages;

use App\Filament\Resources\ContentTopics\ContentTopicResource;
use App\Filament\Resources\ContentTopics\Pages\Concerns\InteractsWithContentTopicWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContentTopic extends ViewRecord
{
    use InteractsWithContentTopicWorkflowActions;

    protected static string $resource = ContentTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->contentTopicWorkflowActions(),
            EditAction::make(),
        ];
    }
}
