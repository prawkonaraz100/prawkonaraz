<?php

namespace App\Filament\Pages;

use App\Models\QuestionCollection;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;

class ProfessionalCourses extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Kursy zawodowe';

    protected string $view = 'filament.pages.professional-courses';

    protected static ?string $slug = 'kursy-zawodowe';

    public static function getNavigationLabel(): string
    {
        return 'Kursy zawodowe';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::QueueList;
    }

    public function getHeading(): string|Htmlable
    {
        return 'Kursy zawodowe';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Włączaj i wyłączaj dostęp kursantów do osobnych kolekcji pytań zawodowych.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'collections' => QuestionCollection::query()
                ->where('kind', 'professional_qualification')
                ->with([
                    'licenseCategory:id,code,name',
                    'modules' => fn ($query) => $query->withCount('questions'),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ];
    }
}
