<?php

namespace App\Filament\Resources\TrafficSigns\Schemas;

use App\Models\TrafficSign;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficSignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość znaku')
                        ->description('Najważniejsze dane potrzebne do routingu, publikacji i relacji contentowych.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            TextEntry::make('code')
                                ->label('Kod'),
                            TextEntry::make('name')
                                ->label('Nazwa'),
                            TextEntry::make('slug')
                                ->label('Slug'),
                            TextEntry::make('category.name')
                                ->label('Kategoria'),
                            TextEntry::make('author.name')
                                ->label('Autor'),
                        ])
                        ->columns(2),
                    Section::make('Publikacja i assety')
                        ->description('Status, daty, workflow i główne ścieżki assetów dla tej strony.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            TextEntry::make('workflow_status')
                                ->label('Workflow')
                                ->state(fn (TrafficSign $record): string => $record->workflowLabel())
                                ->badge()
                                ->color(fn (TrafficSign $record): string => $record->workflowColor()),
                            IconEntry::make('is_published')
                                ->label('Opublikowany')
                                ->boolean(),
                            TextEntry::make('published_at')
                                ->label('Data publikacji')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('sort_order')
                                ->label('Kolejność')
                                ->numeric(),
                            TextEntry::make('reviewer.name')
                                ->label('Reviewer')
                                ->placeholder('-'),
                            TextEntry::make('reviewed_at')
                                ->label('Ostatni review')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('source_status')
                                ->label('Źródła')
                                ->state(fn (TrafficSign $record): string => $record->sourceVerificationLabel())
                                ->badge()
                                ->color(fn (TrafficSign $record): string => $record->sourceVerificationColor()),
                            TextEntry::make('source_checked_at')
                                ->label('Źródła potwierdzone')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('freshness_status')
                                ->label('Freshness')
                                ->state(fn (TrafficSign $record): string => $record->freshnessStateLabel())
                                ->badge()
                                ->color(fn (TrafficSign $record): string => $record->freshnessStateColor()),
                            TextEntry::make('freshness_review_due_at')
                                ->label('Kolejny przegląd')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('image_path')
                                ->label('Obraz znaku')
                                ->placeholder('-'),
                            TextEntry::make('image_alt')
                                ->label('Alt obrazu')
                                ->placeholder('-'),
                            TextEntry::make('image_dimensions')
                                ->label('Wymiary obrazu')
                                ->state(fn (TrafficSign $record): string => static::dimensionsSummary($record->image_width, $record->image_height))
                                ->placeholder('-'),
                            TextEntry::make('og_image_path')
                                ->label('Obraz OG')
                                ->placeholder('-'),
                            TextEntry::make('og_image_alt')
                                ->label('Alt obrazu OG')
                                ->placeholder('-'),
                            TextEntry::make('og_image_dimensions')
                                ->label('Wymiary obrazu OG')
                                ->state(fn (TrafficSign $record): string => static::dimensionsSummary($record->og_image_width, $record->og_image_height))
                                ->placeholder('-'),
                        ])
                        ->columns(2),
                ]),
                Section::make('Checklista publikacyjna')
                    ->description('Jedno miejsce do szybkiego sprawdzenia, czy rekord ma komplet podstaw do review i publikacji.')
                    ->schema([
                        TextEntry::make('publication_checklist')
                            ->label('Stan checklisty')
                            ->state(fn (TrafficSign $record): string => $record->publicationChecklistCompletionLabel())
                            ->badge()
                            ->color(fn (TrafficSign $record): string => $record->hasCompletePublicationChecklist() ? 'success' : 'warning'),
                        TextEntry::make('publication_checklist_missing')
                            ->label('Braki')
                            ->state(fn (TrafficSign $record): string => static::publicationChecklistMissingSummary($record))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Treść')
                    ->description('Główne bloki merytoryczne strony znaku.')
                    ->schema([
                        TextEntry::make('intro_definition')
                            ->label('Definicja otwierająca')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('meaning')
                            ->label('Znaczenie')
                            ->placeholder('-'),
                        TextEntry::make('placement')
                            ->label('Gdzie występuje')
                            ->placeholder('-'),
                        TextEntry::make('driver_behavior')
                            ->label('Jak zachować się jako kierowca')
                            ->placeholder('-'),
                        TextEntry::make('common_mistakes')
                            ->label('Najczęstsze błędy')
                            ->placeholder('-'),
                        TextEntry::make('legal_summary')
                            ->label('Podstawa prawna i kontekst')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('fine_summary')
                            ->label('Mandat / konsekwencje')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('SEO i źródła')
                    ->description('Pola meta, FAQ i odnośnik do źródła prawnego.')
                    ->schema([
                        TextEntry::make('meta_title')
                            ->label('Meta title')
                            ->placeholder('-'),
                        TextEntry::make('meta_description')
                            ->label('Meta description')
                            ->placeholder('-'),
                        TextEntry::make('legal_reference_label')
                            ->label('Etykieta źródła')
                            ->placeholder('-'),
                        TextEntry::make('legal_reference_url')
                            ->label('URL źródła')
                            ->placeholder('-')
                            ->url(fn (TrafficSign $record): ?string => $record->legal_reference_url)
                            ->openUrlInNewTab(),
                        TextEntry::make('faq_summary')
                            ->label('FAQ')
                            ->state(fn (TrafficSign $record): string => static::faqSummary($record))
                            ->columnSpanFull(),
                        TextEntry::make('source_notes')
                            ->label('Notatki o źródłach')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('editorial_notes')
                            ->label('Notatki redakcyjne')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('review_notes')
                            ->label('Notatki z review')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function faqSummary(TrafficSign $record): string
    {
        $items = collect($record->faq_items ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null))
            ->map(fn (array $item): string => '- '.($item['question'] ?? ''))
            ->all();

        if ($items === []) {
            return 'Brak pytań FAQ.';
        }

        return implode("\n", $items);
    }

    protected static function publicationChecklistMissingSummary(TrafficSign $record): string
    {
        $missing = $record->publicationChecklistMissingLabels();

        if ($missing === []) {
            return 'Brak. Rekord ma komplet podstawowych elementów do publikacji.';
        }

        return implode(', ', $missing);
    }

    protected static function dimensionsSummary(?int $width, ?int $height): string
    {
        if (! $width || ! $height) {
            return '-';
        }

        return "{$width} x {$height}px";
    }
}
