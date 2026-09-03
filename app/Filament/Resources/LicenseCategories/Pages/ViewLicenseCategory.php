<?php

namespace App\Filament\Resources\LicenseCategories\Pages;

use App\Filament\Resources\LicenseCategories\LicenseCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLicenseCategory extends ViewRecord
{
    protected static string $resource = LicenseCategoryResource::class;

    public function getHeading(): string
    {
        return 'Podgląd kategorii';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();
        $sessionWindowDays = max((int) config('study.admin_activity_window_days', 90), 1);

        $parts = array_filter([
            filled($record->code) ? "Kod: {$record->code}" : null,
            isset($record->questions_count) ? 'Pytania: '.number_format((int) $record->questions_count, 0, ',', ' ') : null,
            isset($record->recent_study_sessions_count) ? 'Sesje '.$sessionWindowDays.' dni: '.number_format((int) $record->recent_study_sessions_count, 0, ',', ' ') : null,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edytuj'),
        ];
    }
}
