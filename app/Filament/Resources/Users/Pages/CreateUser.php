<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\AdminUserAccountService;
use App\Support\AuditLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return 'Dodaj użytkownika';
    }

    public function getSubheading(): ?string
    {
        return 'Utwórz nowe konto i od razu ustaw podstawowe uprawnienia do panelu.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['target_category_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $actor = auth()->user();
        $record = $this->getRecord();

        app(AuditLogService::class)->record(
            'user.created_by_admin',
            'user',
            (string) $record->getKey(),
            $actor,
            app(AdminUserAccountService::class)->adminSettingsSnapshot($record),
        );

        if ($targetCategoryId = $this->selectedTargetCategoryId()) {
            app(AdminUserAccountService::class)->changeTargetCategory($record, $targetCategoryId, $actor);
        }
    }

    protected function selectedTargetCategoryId(): ?int
    {
        $state = $this->form->getRawState();
        $targetCategoryId = $state['target_category_id'] ?? null;

        return filled($targetCategoryId) ? (int) $targetCategoryId : null;
    }
}
