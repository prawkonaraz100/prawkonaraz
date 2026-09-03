<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewQuestion extends ViewRecord
{
    protected static string $resource = QuestionResource::class;

    public function getHeading(): string
    {
        return 'Podgląd pytania';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return trim(implode(' · ', array_filter([
            filled($record->external_id) ? "ID źródła: {$record->external_id}" : null,
            $record->licenseCategory?->code ? "Kategoria {$record->licenseCategory->code}" : null,
            $record->questionTopic?->name,
        ])));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('media')
                ->label('Media')
                ->icon(Heroicon::OutlinedPhoto)
                ->url(fn (): string => QuestionResource::getUrl('media', ['record' => $this->getRecord()])),
            EditAction::make()
                ->label('Edytuj'),
        ];
    }
}
