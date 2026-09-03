<?php

namespace App\Filament\Resources\QuestionTopics\Pages;

use App\Filament\Resources\QuestionTopics\QuestionTopicResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuestionTopic extends ViewRecord
{
    protected static string $resource = QuestionTopicResource::class;

    public function getHeading(): string
    {
        return 'Podgląd działu pytań';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return filled($record->key)
            ? "Klucz: {$record->key}"
            : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edytuj zdjęcie'),
        ];
    }
}
