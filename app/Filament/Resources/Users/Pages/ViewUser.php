<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return 'Podgląd użytkownika';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return implode(' · ', array_filter([
            $record->email,
            $record->roleLabel(),
            $record->email_verified_at ? 'konto zweryfikowane' : 'czeka na weryfikację',
        ]));
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edytuj'),
        ];
    }
}
