<?php

namespace App\Filament\Resources\ContentImportRuns\Pages;

use App\Filament\Pages\GovImportGuide;
use App\Filament\Resources\ContentImportRuns\ContentImportRunResource;
use App\Models\ContentImportRun;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListContentImportRuns extends ListRecords
{
    protected static string $resource = ContentImportRunResource::class;

    public function getHeading(): string
    {
        return 'Importy';
    }

    public function getSubheading(): ?string
    {
        return 'Tu sprawdzisz przebiegi importów, warningi, błędy i wynik audytu integralności bez wchodzenia w surowe raporty.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guide')
                ->label('Instrukcja gov.pl')
                ->url(GovImportGuide::getUrl(panel: 'admin')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Wszystkie')
                ->badge(number_format(ContentImportRun::query()->count(), 0, ',', ' ')),
            'errors' => Tab::make('Z błędami')
                ->badge(number_format(
                    ContentImportRun::query()
                        ->where(fn (Builder $query): Builder => $query
                            ->where('status', 'failed')
                            ->orWhere('errors_count', '>', 0))
                        ->count(),
                    0,
                    ',',
                    ' ',
                ))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where(fn (Builder $innerQuery): Builder => $innerQuery
                        ->where('status', 'failed')
                        ->orWhere('errors_count', '>', 0))),
            'warnings' => Tab::make('Z warningami')
                ->badge(number_format(
                    ContentImportRun::query()
                        ->where('warnings_count', '>', 0)
                        ->where('errors_count', 0)
                        ->count(),
                    0,
                    ',',
                    ' ',
                ))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('warnings_count', '>', 0)
                    ->where('errors_count', 0)),
            'dry' => Tab::make('Dry run')
                ->badge(number_format(ContentImportRun::query()->where('dry_run', true)->count(), 0, ',', ' '))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('dry_run', true)),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
                View::make('filament.resources.content-import-runs.pages.retention-note'),
            ]);
    }
}
