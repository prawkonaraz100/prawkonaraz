<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\AdminUserAccountService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $accountSettingsBeforeSave = [];

    public function getHeading(): string
    {
        return 'Edytuj użytkownika';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return implode(' · ', array_filter([
            $record->email,
            $record->roleLabel(),
        ]));
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord()->loadMissing('profile');

        $data['target_category_id'] = $record->profile?->target_category_id;

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->accountSettingsBeforeSave = app(AdminUserAccountService::class)
            ->adminSettingsSnapshot($this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['target_category_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $service = app(AdminUserAccountService::class);
        $actor = auth()->user();
        $record = $this->getRecord()->refresh();

        $service->recordAdminSettingsChanges($record, $this->accountSettingsBeforeSave, $actor);

        if ($targetCategoryId = $this->selectedTargetCategoryId()) {
            $service->changeTargetCategory($record, $targetCategoryId, $actor);
        }
    }

    protected function selectedTargetCategoryId(): ?int
    {
        $state = $this->form->getRawState();
        $targetCategoryId = $state['target_category_id'] ?? null;

        return filled($targetCategoryId) ? (int) $targetCategoryId : null;
    }
}
