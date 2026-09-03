<?php

namespace App\Filament\Pages;

use App\Support\BackupSettingsService;
use App\Support\DatabaseBackupService;
use App\Support\AdminBackupConsoleService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class Backups extends Page
{
    /** @var array<string, mixed> */
    public array $settingsData = [];

    /** @var array<string, mixed> */
    public array $settingsPanel = [];

    /** @var array<string, string>|null */
    public ?array $connectionProbe = null;

    /** @var array<string, mixed>|null */
    public ?array $backups = null;

    /** @var array<string, mixed>|null */
    public ?array $backupError = null;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::ArchiveBoxArrowDown;

    protected static string | \UnitEnum | null $navigationGroup = 'Operacje';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Backupy';

    protected string $view = 'filament.pages.backups';

    protected static ?string $slug = 'backupy';

    public static function getNavigationLabel(): string
    {
        return 'Backupy';
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::ArchiveBoxArrowDown;
    }

    public function getHeading(): string | Htmlable
    {
        return 'Backupy';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Dedykowany widok kopii bazy: harmonogram tygodniowy, retencja, ostatnie artefakty i stan storage backupów.';
    }

    public function mount(): void
    {
        $this->loadSettingsState();
        $this->loadBackupState();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runBackup')
                ->label('Utwórz kopię teraz')
                ->icon(Heroicon::ArrowPathRoundedSquare)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Utworzyć backup bazy teraz?')
                ->modalDescription('Ta akcja uruchomi ręczny backup bazy na aktualnie skonfigurowanym dysku i odświeży listę kopii.')
                ->action(function (DatabaseBackupService $backupService) {
                    try {
                        $manifest = $backupService->backup(label: 'manual-admin-panel');
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Nie udało się utworzyć kopii bazy')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return null;
                    }

                    Notification::make()
                        ->title('Backup został utworzony')
                        ->body('Nowa kopia trafiła na dysk '.$manifest['disk'].' i lista została właśnie odświeżona.')
                        ->success()
                        ->send();

                    $this->loadBackupState();

                    return redirect(static::getUrl(panel: 'admin').'?backup_refresh='.now()->utc()->format('YmdHis'));
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'backups' => $this->backups,
            'backupError' => $this->backupError,
            'settingsPanel' => $this->settingsPanel,
            'connectionProbe' => $this->connectionProbe,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'settingsData.disk' => 'required|in:backup_local,r2',
            'settingsData.r2.bucket' => 'nullable|string|max:255',
            'settingsData.r2.endpoint' => 'nullable|string|max:255',
            'settingsData.r2.region' => 'nullable|string|max:50',
            'settingsData.r2.url' => 'nullable|string|max:255',
            'settingsData.r2.use_path_style_endpoint' => 'boolean',
            'settingsData.schedule.frequency' => 'required|in:daily,weekly',
            'settingsData.schedule.day_of_week' => 'required|integer|between:0,6',
            'settingsData.schedule.at' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'settingsData.retention.daily' => 'required|integer|min:0|max:365',
            'settingsData.retention.weekly' => 'required|integer|min:0|max:52',
            'settingsData.retention.monthly' => 'required|integer|min:0|max:24',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'settingsData.disk' => 'tryb backupu',
            'settingsData.r2.bucket' => 'bucket R2',
            'settingsData.r2.endpoint' => 'endpoint R2',
            'settingsData.r2.region' => 'region R2',
            'settingsData.r2.url' => 'URL R2',
            'settingsData.schedule.frequency' => 'częstotliwość harmonogramu',
            'settingsData.schedule.day_of_week' => 'dzień tygodnia',
            'settingsData.schedule.at' => 'godzina harmonogramu',
            'settingsData.retention.daily' => 'retencja daily',
            'settingsData.retention.weekly' => 'retencja weekly',
            'settingsData.retention.monthly' => 'retencja monthly',
        ];
    }

    public function saveSettings(): void
    {
        $validated = $this->validate();
        $service = app(BackupSettingsService::class);

        $this->settingsData = $service->save($validated['settingsData'] ?? []);
        $this->settingsPanel = $service->panelState();
        $this->connectionProbe = null;
        $this->loadBackupState();

        Notification::make()
            ->title('Ustawienia backupów zostały zapisane')
            ->body('Nowy tryb storage, harmonogram i retencja są już aktywne dla panelu, backupów i schedulera.')
            ->success()
            ->send();
    }

    public function resetSettings(): void
    {
        $service = app(BackupSettingsService::class);

        $this->settingsData = $service->reset();
        $this->settingsPanel = $service->panelState();
        $this->connectionProbe = null;
        $this->loadBackupState();

        Notification::make()
            ->title('Przywrócono domyślną konfigurację backupów')
            ->body('Panel znowu korzysta z ustawień domyślnych z `.env` i plików konfiguracyjnych.')
            ->success()
            ->send();
    }

    public function testStorageConnection(): void
    {
        $validated = $this->validate();
        $service = app(BackupSettingsService::class);

        $this->connectionProbe = $service->probe($validated['settingsData'] ?? []);

        Notification::make()
            ->title($this->connectionProbe['headline'])
            ->body($this->connectionProbe['message'])
            ->{$this->connectionProbe['tone'] === 'success' ? 'success' : 'danger'}()
            ->send();
    }

    public function deleteBackup(string $reference): void
    {
        try {
            $result = app(DatabaseBackupService::class)->deleteBackup($reference);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Nie udało się usunąć backupu')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->loadBackupState();

        Notification::make()
            ->title('Backup został usunięty')
            ->body('Usunięto '.count($result['deleted']).' artefakty z dysku '.$result['disk'].'.')
            ->success()
            ->send();
    }

    protected function loadBackupState(): void
    {
        try {
            $this->backups = app(AdminBackupConsoleService::class)->build();
            $this->backupError = null;
        } catch (Throwable $exception) {
            report($exception);

            $this->backups = null;
            $this->backupError = [
                'headline' => 'Panel backupów jest chwilowo niedostępny',
                'message' => 'Nie udało się zbudować widoku kopii bazy. Sprawdź storage backupów, konfigurację dysku albo połączenie z bazą.',
                'details' => $exception->getMessage(),
            ];
        }
    }

    protected function loadSettingsState(): void
    {
        $service = app(BackupSettingsService::class);
        $this->settingsData = $service->current();
        $this->settingsPanel = $service->panelState();
    }
}
