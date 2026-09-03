<?php

namespace App\Filament\Resources\LicenseCategories\Pages;

use App\Filament\Resources\LicenseCategories\LicenseCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLicenseCategory extends EditRecord
{
    protected static string $resource = LicenseCategoryResource::class;

    public function getHeading(): string
    {
        return 'Edytuj kategorię';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return filled($record->code)
            ? "Kod: {$record->code}"
            : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Podgląd'),
            DeleteAction::make()
                ->label('Usuń'),
        ];
    }
}
