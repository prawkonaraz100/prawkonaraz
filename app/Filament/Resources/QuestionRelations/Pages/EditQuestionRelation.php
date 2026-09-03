<?php

namespace App\Filament\Resources\QuestionRelations\Pages;

use App\Filament\Resources\QuestionRelations\QuestionRelationResource;
use Filament\Resources\Pages\EditRecord;

class EditQuestionRelation extends EditRecord
{
    protected static string $resource = QuestionRelationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['reviewed_by_user_id'] = auth()->id();
        $data['reviewed_at'] = now();

        return $data;
    }
}
