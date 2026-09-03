<?php

namespace App\Filament\Pages;

use App\Support\AdminDataMonitoringService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class DataMonitoring extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBarSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Operacje';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Monitoring danych';

    protected string $view = 'filament.pages.data-monitoring';

    protected static ?string $slug = 'monitoring-danych';

    public static function getNavigationLabel(): string
    {
        return 'Monitoring danych';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::ChartBarSquare;
    }

    public function getHeading(): string|Htmlable
    {
        return 'Monitoring danych';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Operacyjny podgląd retencji, miesięcznego rollupu i zadań utrzymujących bazę w ryzach.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        try {
            $monitoring = app(AdminDataMonitoringService::class)->build();
        } catch (Throwable $exception) {
            report($exception);

            return [
                'monitoring' => null,
                'monitoringError' => [
                    'headline' => 'Monitoring jest chwilowo niedostępny',
                    'message' => 'Panel nie mógł pobrać pełnych danych operacyjnych. Reszta admina może działać normalnie, ale ten ekran wymaga sprawdzenia usług albo bazy.',
                    'details' => $exception->getMessage(),
                ],
            ];
        }

        return [
            'monitoring' => $monitoring,
            'monitoringError' => null,
        ];
    }
}
