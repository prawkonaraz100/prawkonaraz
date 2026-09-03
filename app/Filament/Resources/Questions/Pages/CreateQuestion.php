<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\Pages\Concerns\InteractsWithQuestionExplanationAsset;
use App\Filament\Resources\Questions\Pages\Concerns\InteractsWithQuestionExplanationSignOverrides;
use App\Filament\Resources\Questions\QuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    use InteractsWithQuestionExplanationAsset;
    use InteractsWithQuestionExplanationSignOverrides;

    protected static string $resource = QuestionResource::class;

    public function getHeading(): string
    {
        return 'Dodaj pytanie';
    }

    public function getSubheading(): ?string
    {
        return 'Uzupełnij treść, odpowiedzi i parametry publikacji nowego pytania.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractQuestionExplanationSignOverrideData(
            $this->extractQuestionExplanationAssetData($data),
        );
    }

    protected function afterCreate(): void
    {
        $this->syncQuestionExplanationAsset();
        $this->syncQuestionExplanationAnnotations();
        $this->syncQuestionExplanationSignOverrides();
    }
}
