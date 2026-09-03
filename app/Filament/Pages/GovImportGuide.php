<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;

class GovImportGuide extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::ArrowDownTray;

    protected static string | \UnitEnum | null $navigationGroup = 'Operacje';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Import gov.pl';

    protected string $view = 'filament.pages.gov-import-guide';

    protected static ?string $slug = 'import-gov';

    public static function getNavigationLabel(): string
    {
        return 'Import gov.pl';
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::ArrowDownTray;
    }

    public function getHeading(): string | Htmlable
    {
        return 'Import gov.pl';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Dedykowana instrukcja operacyjna do przygotowania stagingu, dry-runu i pełnego importu pytań oraz mediów.';
    }
}
