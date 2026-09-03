<?php

namespace App\Filament\Pages;

use App\Support\QuestionRelationV2ShadowMonitor;
use App\Support\QuestionRelationV2ShadowPreviewService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class QuestionRelationV2ShadowPreview extends Page
{
    public string $externalId = '';

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

    /** @var array<string, mixed>|null */
    public ?array $previewError = null;

    /** @var array<string, mixed>|null */
    public ?array $monitoring = null;

    /** @var array<string, mixed>|null */
    public ?array $monitoringError = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Operacje';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Relacje V2 shadow';

    protected static ?string $slug = 'relacje-v2-shadow';

    protected string $view = 'filament.pages.question-relation-v2-shadow-preview';

    public static function getNavigationLabel(): string
    {
        return 'Relacje V2 shadow';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::ArrowsRightLeft;
    }

    public function getHeading(): string|Htmlable
    {
        return 'Powiązane pytania — V1 ↔ V2 shadow';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Chroniony podgląd administratora. Nie dodaje V2 do publicznego HTML, SSR ani indeksu.';
    }

    public function mount(): void
    {
        $this->externalId = trim((string) request()->query('question', ''));

        if ($this->externalId === '') {
            $this->externalId = app(QuestionRelationV2ShadowPreviewService::class)->defaultExternalId() ?? '';
        }

        $this->refreshState();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshState')
                ->label('Odśwież podgląd')
                ->icon(Heroicon::ArrowPathRoundedSquare)
                ->action('refreshState'),
        ];
    }

    public function refreshState(): void
    {
        $this->refreshMonitoring();
        $this->refreshPreview();
    }

    public function refreshMonitoring(): void
    {
        try {
            $this->monitoring = app(QuestionRelationV2ShadowMonitor::class)->inspect();
            $this->monitoringError = null;
        } catch (Throwable $exception) {
            report($exception);

            $this->monitoring = null;
            $this->monitoringError = [
                'headline' => 'Monitoring shadow jest chwilowo niedostępny',
                'message' => 'Nie udało się policzyć audytu V1 ↔ V2. Publiczny renderer nie został przez to zmieniony.',
                'details' => $exception->getMessage(),
            ];
        }
    }

    public function refreshPreview(): void
    {
        $this->externalId = trim($this->externalId);

        try {
            $this->preview = app(QuestionRelationV2ShadowPreviewService::class)->preview($this->externalId);
            $this->previewError = null;
        } catch (Throwable $exception) {
            report($exception);

            $this->preview = null;
            $this->previewError = [
                'headline' => 'Podgląd pytania jest chwilowo niedostępny',
                'message' => 'Nie udało się zbudować porównania V1 ↔ V2 dla wskazanego pytania.',
                'details' => $exception->getMessage(),
            ];
        }
    }

    public function showQuestion(): void
    {
        $this->refreshPreview();

        if ($this->previewError === null && data_get($this->preview, 'state') === 'ready') {
            Notification::make()
                ->title('Podgląd V1 ↔ V2 został odświeżony')
                ->body('Wynik jest dostępny tylko w panelu administratora; publiczny HTML nadal korzysta z V1.')
                ->success()
                ->send();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'preview' => $this->preview,
            'previewError' => $this->previewError,
            'monitoring' => $this->monitoring,
            'monitoringError' => $this->monitoringError,
        ];
    }
}
