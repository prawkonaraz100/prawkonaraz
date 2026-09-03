<?php

namespace App\Filament\Resources\LicenseCategories\Pages;

use App\Filament\Resources\LicenseCategories\LicenseCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLicenseCategory extends CreateRecord
{
    protected static string $resource = LicenseCategoryResource::class;

    public function getHeading(): string
    {
        return 'Dodaj kategorię';
    }

    public function getSubheading(): ?string
    {
        return 'Utwórz kategorię prawa jazdy i ustaw jej podstawowe parametry oraz kolejność.';
    }
}
