<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\Pages\Concerns\InteractsWithQuestionExplanationAsset;
use App\Filament\Resources\Questions\Pages\Concerns\InteractsWithQuestionExplanationSignOverrides;
use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditQuestion extends EditRecord
{
    use InteractsWithQuestionExplanationAsset;
    use InteractsWithQuestionExplanationSignOverrides;

    protected static string $resource = QuestionResource::class;

    public function getHeading(): string
    {
        return 'Edytuj pytanie';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return trim(implode(' · ', array_filter([
            filled($record->external_id) ? "ID źródła: {$record->external_id}" : null,
            $record->licenseCategory?->code ? "Kategoria {$record->licenseCategory->code}" : null,
            $record->questionTopic?->name,
        ]))) ?: 'Aktualizuj treść, odpowiedzi i ustawienia publikacji tego pytania.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('media')
                ->label('Media')
                ->icon(Heroicon::OutlinedPhoto)
                ->url(fn (): string => QuestionResource::getUrl('media', ['record' => $this->getRecord()])),
            ViewAction::make()
                ->label('Podgląd'),
            DeleteAction::make()
                ->label('Usuń'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillQuestionExplanationSignOverrideData(
            $this->fillQuestionExplanationAssetData($data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractQuestionExplanationSignOverrideData(
            $this->extractQuestionExplanationAssetData($data),
        );
    }

    protected function afterSave(): void
    {
        $this->syncQuestionExplanationAsset();
        $this->syncQuestionExplanationAnnotations();
        $this->syncQuestionExplanationSignOverrides();
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->extraAttributes([
                'data-upload-guard' => 'question-explanation-asset',
                'x-on:click' => 'if (window.isQuestionExplanationUploadInProgress?.()) { $event.preventDefault(); $event.stopImmediatePropagation(); }',
            ]);
    }
}
