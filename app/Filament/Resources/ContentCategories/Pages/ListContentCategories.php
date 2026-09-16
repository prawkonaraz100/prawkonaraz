<?php

namespace App\Filament\Resources\ContentCategories\Pages;

use App\Filament\Resources\ContentCategories\ContentCategoryResource;
use App\Models\ContentCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContentCategories extends ListRecords
{
    protected static string $resource = ContentCategoryResource::class;

    public function getHeading(): string
    {
        return 'Kategorie newsroomu';
    }

    public function getSubheading(): ?string
    {
        return 'Zarządzaj nazwami, widocznością, kolejnością i wykorzystaniem kategorii artykułów.';
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
                ->badge(number_format(ContentCategory::query()->count(), 0, ',', ' ')),
            'active' => Tab::make('Aktywne')
                ->badge(number_format(ContentCategory::query()->active()->count(), 0, ',', ' '))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->active()),
            'inactive' => Tab::make('Nieaktywne')
                ->badge(number_format(ContentCategory::query()->where('is_active', false)->count(), 0, ',', ' '))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', false)),
            'used' => Tab::make('Używane')
                ->badge(number_format(ContentCategory::query()->has('articles')->count(), 0, ',', ' '))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->has('articles')),
        ];
    }
}
