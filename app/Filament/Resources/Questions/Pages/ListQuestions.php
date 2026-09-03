<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    public function getHeading(): string
    {
        return 'Baza pytań';
    }

    public function getSubheading(): ?string
    {
        return 'Pracuj na pytaniach, które faktycznie wymagają decyzji: publikacja, media, treść i poprawne odpowiedzi.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Dodaj pytanie'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Wszystkie')
                ->badge(number_format(Question::query()->count(), 0, ',', ' ')),
            'ready' => Tab::make('Do publikacji')
                ->badge(number_format(Question::query()->readyForDelivery()->count(), 0, ',', ' '))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->readyForDelivery()),
            'issue' => Tab::make('Z problemem')
                ->badge(number_format(Question::query()->whereNotNull('delivery_issue')->count(), 0, ',', ' '))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('delivery_issue')),
            'inactive' => Tab::make('Nieaktywne')
                ->badge(number_format(Question::query()->where('is_active', false)->count(), 0, ',', ' '))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', false)),
        ];
    }
}
