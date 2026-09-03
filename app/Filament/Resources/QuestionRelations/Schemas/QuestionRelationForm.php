<?php

namespace App\Filament\Resources\QuestionRelations\Schemas;

use App\Models\QuestionRelation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionRelationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Decyzja redakcyjna')
                ->description('Zweryfikowane i automatyczne relacje są publiczne. Kandydaci oraz odrzucone relacje pozostają niewidoczne.')
                ->columns(3)
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            QuestionRelation::STATUS_VERIFIED => 'Zweryfikowana',
                            QuestionRelation::STATUS_AUTOMATIC => 'Automatycznie opublikowana',
                            QuestionRelation::STATUS_CANDIDATE => 'Kandydat',
                            QuestionRelation::STATUS_REJECTED => 'Odrzucona',
                        ])
                        ->native(false)
                        ->required(),
                    Select::make('relation_type')
                        ->label('Typ relacji')
                        ->options([
                            'blizniacze' => 'Bliźniacze',
                            'ta_sama_zasada' => 'Ta sama zasada',
                            'wariant' => 'Wariant',
                            'kontrast' => 'Kontrast',
                            'nie_pomyl_z' => 'Nie pomyl z',
                            'rozszerzenie' => 'Rozszerzenie',
                            'tematyczne' => 'Tematyczne',
                        ])
                        ->native(false)
                        ->required(),
                    TextInput::make('display_order')
                        ->label('Kolejność')
                        ->numeric()
                        ->minValue(0),
                ]),
            Section::make('Ocena relacji')
                ->columns(2)
                ->schema([
                    TextInput::make('score')
                        ->label('Wynik grafu')
                        ->disabled(),
                    TextInput::make('source')
                        ->label('Źródło')
                        ->disabled(),
                    Textarea::make('reason')
                        ->label('Wspólna zasada / powód')
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('difference')
                        ->label('Najważniejsza różnica')
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('anchor_left_to_right')
                        ->label('Opis A → B')
                        ->rows(3),
                    Textarea::make('anchor_right_to_left')
                        ->label('Opis B → A')
                        ->rows(3),
                ]),
        ]);
    }
}
