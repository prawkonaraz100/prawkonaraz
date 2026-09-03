<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\ContentImportRuns\ContentImportRunResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\AuditLog;
use App\Models\ContentImportRun;
use App\Models\Question;
use App\Models\User;
use App\Support\AdminDashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $latestImport = ContentImportRun::query()->latest('created_at')->first();
        $latestImportStatus = app(AdminDashboardService::class)->resolveImportStatus($latestImport);
        $missingPrimaryMediaCount = Question::query()->missingPrimaryMedia()->count();
        $inactiveQuestionsCount = Question::query()->where('is_active', false)->count();
        $auditTodayCount = AuditLog::query()->whereDate('created_at', today())->count();

        return [
            Stat::make('Braki publikacji', number_format($missingPrimaryMediaCount, 0, ',', ' '))
                ->description($missingPrimaryMediaCount > 0
                    ? 'Pytania bez głównego medium wymagają uzupełnienia.'
                    : 'Publikacja nie pokazuje teraz braków krytycznych.')
                ->descriptionIcon('heroicon-m-photo')
                ->color($missingPrimaryMediaCount > 0 ? 'danger' : 'gray')
                ->url(QuestionResource::getUrl(panel: 'admin')),
            Stat::make('Nieaktywne pytania', number_format($inactiveQuestionsCount, 0, ',', ' '))
                ->description('Sprawdź, czy to świadome wyłączenia po imporcie albo review.')
                ->descriptionIcon('heroicon-m-eye-slash')
                ->color('gray'),
            Stat::make('Ostatni import', $latestImportStatus['label'])
                ->description($latestImport === null
                    ? 'Nie ma jeszcze żadnego przebiegu importu.'
                    : trim(implode(' · ', array_filter([
                        $latestImportStatus['description'],
                        $latestImport->created_at?->format('d.m.Y H:i'),
                    ]))))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color(match ($latestImportStatus['tone']) {
                    'critical' => 'danger',
                    'warning' => 'warning',
                    'ok' => 'success',
                    default => 'gray',
                })
                ->url($latestImport
                    ? ContentImportRunResource::getUrl('view', ['record' => $latestImport], panel: 'admin')
                    : ContentImportRunResource::getUrl(panel: 'admin')),
            Stat::make('Akcje dziś', number_format($auditTodayCount, 0, ',', ' '))
                ->description(sprintf(
                    'Zalogowani operatorzy: %s.',
                    number_format(User::query()->where('is_admin', true)->count(), 0, ',', ' '),
                ))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray')
                ->url(AuditLogResource::getUrl(panel: 'admin')),
        ];
    }
}
