<?php

namespace App\Filament\Pages;

use App\Support\AdminPjmCoverageReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class PjmCoverage extends Page
{
    public ?string $externalId = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBarSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Operacje';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Pokrycie PJM';

    protected string $view = 'filament.pages.pjm-coverage';

    protected static ?string $slug = 'pjm-coverage';

    public static function getNavigationLabel(): string
    {
        return 'Pokrycie PJM';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::ChartBarSquare;
    }

    public function mount(): void
    {
        $externalId = request()->query('external_id');
        $this->externalId = is_string($externalId) ? trim($externalId) : null;
    }

    public function getHeading(): string|Htmlable
    {
        return 'Pokrycie PJM';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Stan filmów PJM, braków w pytaniach i plików wymagających kontroli.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadJson')
                ->label('Pobierz JSON')
                ->url(fn (): string => route('admin.pjm.report.download', [
                    'format' => 'json',
                    'external_id' => $this->externalId ?: null,
                ]))
                ->openUrlInNewTab(),
            Action::make('downloadCsv')
                ->label('Pobierz CSV')
                ->url(fn (): string => route('admin.pjm.report.download', [
                    'format' => 'csv',
                    'external_id' => $this->externalId ?: null,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        try {
            $report = app(AdminPjmCoverageReportService::class)->build($this->externalId);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'report' => null,
                'reportError' => [
                    'headline' => 'Raport PJM jest chwilowo niedostępny',
                    'message' => 'Panel nie mógł pobrać stanu assetów PJM. Pozostałe części admina mogą działać normalnie, ale ten ekran wymaga sprawdzenia bazy.',
                    'details' => $exception->getMessage(),
                ],
                'externalId' => $this->externalId,
            ];
        }

        return [
            'report' => $report,
            'reportError' => null,
            'externalId' => $this->externalId,
        ];
    }
}
