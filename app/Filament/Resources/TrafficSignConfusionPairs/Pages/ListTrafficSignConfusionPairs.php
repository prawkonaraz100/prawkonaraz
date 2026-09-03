<?php

namespace App\Filament\Resources\TrafficSignConfusionPairs\Pages;

use App\Filament\Resources\TrafficSignConfusionPairs\TrafficSignConfusionPairResource;
use App\Models\TrafficSignConfusionPair;
use App\Support\TrafficSignConfusionPairService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTrafficSignConfusionPairs extends ListRecords
{
    protected static string $resource = TrafficSignConfusionPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncSupportingPages')
                ->label('Synchronizuj po deployu')
                ->icon(Heroicon::ArrowPathRoundedSquare)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Odświeżyć podobne znaki ze stron porównawczych?')
                ->modalDescription('Uruchom to po deployu albo po zmianach w stronach porównawczych. Akcja usuwa tylko automatyczne pary ze źródła "Strona porównawcza", tworzy je ponownie i zostawia ręczne relacje admina bez zmian.')
                ->action(function (TrafficSignConfusionPairService $confusionPairService): void {
                    $deleted = TrafficSignConfusionPair::query()
                        ->where('source', TrafficSignConfusionPair::SOURCE_SUPPORTING_PAGE)
                        ->delete();
                    $synced = $confusionPairService->upsertFromSupportingPages();

                    Notification::make()
                        ->title('Pary podobnych znaków odświeżone')
                        ->body("Usunięto automatyczne pary: {$deleted}. Zsynchronizowano pary kierunkowe: {$synced}. Ręczne relacje zostały bez zmian.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
