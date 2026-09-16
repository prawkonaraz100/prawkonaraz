<?php

namespace App\Filament\Resources\ContentCategories\Pages;

use App\Filament\Resources\ContentCategories\ContentCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContentCategory extends EditRecord
{
    protected static string $resource = ContentCategoryResource::class;

    public function getHeading(): string
    {
        return 'Edytuj kategorię newsroomu';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Podgląd'),
            DeleteAction::make()
                ->label('Usuń')
                ->disabled(fn (): bool => ! $this->getRecord()->canBeDeleted())
                ->tooltip(fn (): ?string => $this->getRecord()->canBeDeleted()
                    ? null
                    : 'Najpierw przepnij lub usuń artykuły przypisane do tej kategorii.'),
        ];
    }
}
