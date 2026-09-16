<?php

namespace App\Filament\Resources\ContentCategories\Pages;

use App\Filament\Resources\ContentCategories\ContentCategoryResource;
use App\Models\ContentCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditContentCategory extends EditRecord
{
    protected static string $resource = ContentCategoryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (
            $this->record->is_active
            && array_key_exists('is_active', $data)
            && ! (bool) $data['is_active']
            && ! $this->record->canBeDeactivated()
        ) {
            throw ValidationException::withMessages([
                'data.is_active' => 'Nie można wyłączyć kategorii, dopóki ma publiczne lub aktywnie dystrybuowane artykuły.',
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->disabled(fn (ContentCategory $record): bool => ! $record->canBeDeleted()),
        ];
    }
}
