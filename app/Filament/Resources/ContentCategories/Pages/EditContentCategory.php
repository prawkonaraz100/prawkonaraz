<?php

namespace App\Filament\Resources\ContentCategories\Pages;

use App\Filament\Resources\ContentCategories\ContentCategoryResource;
use App\Models\ContentCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContentCategory extends EditRecord
{
    protected static string $resource = ContentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->disabled(fn (ContentCategory $record): bool => ! $record->canBeDeleted()),
        ];
    }
}
