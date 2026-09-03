<?php

namespace App\Filament\Resources\LicenseCategories\Pages;

use App\Filament\Resources\LicenseCategories\LicenseCategoryResource;
use App\Models\LicenseCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListLicenseCategories extends ListRecords
{
    protected static string $resource = LicenseCategoryResource::class;

    public function getHeading(): string
    {
        return 'Kategorie';
    }

    public function getSubheading(): ?string
    {
        return 'Porządkuj kategorie prawa jazdy i ich kolejność w serwisie oraz w bazie pytań.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Dodaj kategorię'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Wszystkie')
                ->badge(number_format(LicenseCategory::query()->count(), 0, ',', ' ')),
            'active' => Tab::make('Aktywne')
                ->badge(number_format(LicenseCategory::query()->where('is_active', true)->count(), 0, ',', ' '))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', true)),
            'inactive' => Tab::make('Nieaktywne')
                ->badge(number_format(LicenseCategory::query()->where('is_active', false)->count(), 0, ',', ' '))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', false)),
            'used' => Tab::make('Używane')
                ->badge(number_format(
                    LicenseCategory::query()
                        ->where(function (Builder $query): Builder {
                            return $query
                                ->has('questions')
                                ->orHas('studySessions')
                                ->orHas('userProfiles');
                        })
                        ->count(),
                    0,
                    ',',
                    ' ',
                ))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $innerQuery): Builder {
                    return $innerQuery
                        ->has('questions')
                        ->orHas('studySessions')
                        ->orHas('userProfiles');
                })),
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
                View::make('filament.resources.license-categories.pages.retention-note'),
            ]);
    }
}
