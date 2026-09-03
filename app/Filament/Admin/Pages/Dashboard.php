<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Widgets\LatestQuestionIntegrityAuditOverview;
use App\Filament\Widgets\PlatformOverview;
use App\Support\AdminDashboardService;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Panel';

    protected static ?string $navigationLabel = 'Panel';

    protected string $view = 'filament.pages.dashboard';

    public function getHeading(): string
    {
        return 'Panel';
    }

    public function getSubheading(): ?string
    {
        return 'Start pracy z importami, publikacją pytań i ostatnimi działaniami w panelu.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlatformOverview::class,
            LatestQuestionIntegrityAuditOverview::class,
            AccountWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'dashboard' => app(AdminDashboardService::class)->build(),
        ];
    }
}
