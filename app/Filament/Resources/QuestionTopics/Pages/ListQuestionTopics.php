<?php

namespace App\Filament\Resources\QuestionTopics\Pages;

use App\Filament\Resources\QuestionTopics\QuestionTopicResource;
use Filament\Resources\Pages\ListRecords;

class ListQuestionTopics extends ListRecords
{
    protected static string $resource = QuestionTopicResource::class;

    public function getHeading(): string
    {
        return 'Działy pytań';
    }

    public function getSubheading(): ?string
    {
        return 'Ustaw globalne zdjęcia banerów działów. Wyjątki dla konkretnych kategorii ustawiaj w zasobie Kategorie.';
    }
}

