<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Question;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class QuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Kontekst')
                        ->description('Podstawowe informacje potrzebne do identyfikacji pytania w bazie.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            TextEntry::make('licenseCategory.name')
                                ->label('Kategoria'),
                            TextEntry::make('questionTopic.name')
                                ->label('Temat')
                                ->placeholder('-')
                                ->badge()
                                ->color('gray'),
                            TextEntry::make('external_id')
                                ->label('ID źródła')
                                ->placeholder('-'),
                            TextEntry::make('source')
                                ->label('Źródło')
                                ->placeholder('-'),
                        ])
                        ->columns(2),
                    Section::make('Publikacja')
                        ->description('Status rekordu i sygnały, czy pytanie jest gotowe do pracy w sesjach.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            IconEntry::make('is_active')
                                ->label('Aktywne')
                                ->boolean(),
                            TextEntry::make('question_type')
                                ->label('Typ pytania')
                                ->state(fn (Question $record): string => static::questionTypeLabel($record->question_type)),
                            TextEntry::make('delivery_status')
                                ->label('Publikacja')
                                ->state(fn (Question $record): string => $record->deliveryStatus())
                                ->badge()
                                ->color(fn (Question $record): string => $record->hasDeliveryIssue() ? 'danger' : 'success'),
                            TextEntry::make('delivery_issue')
                                ->label('Problem publikacji')
                                ->state(fn (Question $record): string => $record->deliveryIssueLabel() ?? 'Brak')
                                ->badge()
                                ->color(fn (Question $record): string => $record->hasDeliveryIssue() ? 'danger' : 'gray'),
                            TextEntry::make('media_count')
                                ->label('Media')
                                ->counts('media')
                                ->helperText(fn (Question $record): string => $record->expectsPrimaryMedia() ? 'Pytanie wymaga głównego medium.' : 'Pytanie może działać bez medium.'),
                            TextEntry::make('published_at')
                                ->label('Opublikowano')
                                ->dateTime()
                                ->placeholder('-'),
                            TextEntry::make('updated_at')
                                ->label('Zaktualizowano')
                                ->dateTime()
                                ->placeholder('-'),
                        ])
                        ->columns(2),
                ]),
                Section::make('Treść pytania')
                    ->description('Taką treść zobaczy kursant przed odpowiedzią.')
                    ->schema([
                        TextEntry::make('prompt')
                            ->label('Treść pytania')
                            ->columnSpanFull(),
                        TextEntry::make('explanation')
                            ->label('Wyjaśnienie')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Odpowiedzi')
                    ->description('Pełny układ wariantów odpowiedzi wraz z poprawnym wyborem.')
                    ->schema([
                        TextEntry::make('correct_answer_preview')
                            ->label('Poprawna odpowiedź')
                            ->state(fn (Question $record): string => static::answerPreview($record))
                            ->helperText('Najpierw litera wariantu, potem skrót właściwej odpowiedzi.')
                            ->columnSpanFull(),
                        Grid::make([
                            'lg' => 3,
                        ])->schema([
                            TextEntry::make('option_a')
                                ->label('Odpowiedź A')
                                ->columnSpanFull(),
                            TextEntry::make('option_b')
                                ->label('Odpowiedź B')
                                ->columnSpanFull(),
                            TextEntry::make('option_c')
                                ->label('Odpowiedź C')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ]),
                    ]),
                Section::make('Parametry')
                    ->description('Punkty i trudność pomagają szybciej ocenić wagę pytania w bazie.')
                    ->schema([
                        TextEntry::make('difficulty')
                            ->label('Trudność')
                            ->numeric(),
                        TextEntry::make('points')
                            ->label('Punkty')
                            ->numeric(),
                        TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(3),
            ]);
    }

    protected static function answerPreview(Question $record): string
    {
        $answer = strtolower((string) $record->correct_answer);

        return strtoupper($answer).' · '.Str::limit(match ($answer) {
            'a' => (string) $record->option_a,
            'b' => (string) $record->option_b,
            'c' => (string) ($record->option_c ?? ''),
            default => '-',
        }, 70);
    }

    protected static function questionTypeLabel(?string $questionType): string
    {
        return match ($questionType) {
            'boolean' => 'Tak / nie',
            'single_choice' => 'Jednokrotny wybór',
            default => filled($questionType) ? (string) $questionType : '-',
        };
    }
}
