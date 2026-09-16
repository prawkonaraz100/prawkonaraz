<?php

namespace App\Filament\Resources\ContentTopics\Schemas;

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentTopic;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ContentTopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Tożsamość topicu')
                        ->description('Topic/dossier jest ręcznie zarządzanym hubem redakcyjnym. Nie powstaje automatycznie z taga.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextInput::make('title')
                                ->label('Tytuł')
                                ->required()
                                ->maxLength(180)
                                ->columnSpanFull(),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->regex('/\\A[a-z0-9-]+\\z/')
                                ->maxLength(200)
                                ->disabled(fn (?ContentTopic $record): bool => $record?->published_at !== null)
                                ->helperText('Slug można zmieniać tylko przed pierwszą publikacją. V1 nie ma historii redirectów topicu.'),
                            Textarea::make('description')
                                ->label('Opis redakcyjny')
                                ->rows(7)
                                ->dehydrateStateUsing(fn (mixed $state): string => trim((string) $state))
                                ->helperText('Draft może mieć pusty opis, ale publikacja i ponowna publikacja wymagają własnego niepustego opisu.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Stan publikacji')
                        ->description('Status jest read-only. Publish / Archive / Republish przechodzą przez kontrolowany service boundary.')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Placeholder::make('status_display')
                                ->label('Status')
                                ->content(fn (?ContentTopic $record): string => static::statusLabel($record?->status)),
                            Placeholder::make('published_at_display')
                                ->label('Pierwsza publikacja')
                                ->content(fn (?ContentTopic $record): string => $record?->published_at?->timezone('Europe/Warsaw')->format('d.m.Y H:i') ?? '-'),
                            Placeholder::make('corpus_health_display')
                                ->label('Stan corpus')
                                ->content(fn (?ContentTopic $record): string => static::corpusHealthLabel($record)),
                        ]),
                    Section::make('Corpus artykułów')
                        ->description('Corpus nie ma ręcznego rankingu. Poza pojedynczym featured article publiczna kolejność będzie chronologiczna.')
                        ->columnSpanFull()
                        ->schema([
                            static::articlesSelect(),
                            Select::make('featured_article_id')
                                ->label('Featured article')
                                ->helperText('Featured musi należeć do corpus. Przy publikacji backend dodatkowo wymaga actively-distributed + indexable.')
                                ->searchable()
                                ->options(function (Get $get): array {
                                    $ids = collect($get('article_ids') ?? [])
                                        ->filter(fn (mixed $id): bool => is_numeric($id))
                                        ->map(fn (mixed $id): int => (int) $id)
                                        ->unique()
                                        ->values()
                                        ->all();

                                    if ($ids === []) {
                                        return [];
                                    }

                                    return ContentArticle::query()
                                        ->whereIn('id', $ids)
                                        ->orderByDesc('first_published_at')
                                        ->orderByDesc('id')
                                        ->limit(100)
                                        ->get()
                                        ->mapWithKeys(fn (ContentArticle $article): array => [
                                            $article->id => static::articleLabel($article),
                                        ])
                                        ->all();
                                })
                                ->placeholder('Bez featured article'),
                        ]),
                    Section::make('SEO')
                        ->description('SEO metadata nie zmienia warunków publikacji topicu.')
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('seo_title')
                                ->label('SEO title')
                                ->maxLength(255),
                            Textarea::make('seo_description')
                                ->label('SEO description')
                                ->rows(3)
                                ->maxLength(320),
                        ])
                        ->columns(2),
                ]),
            ]);
    }

    protected static function articlesSelect(): Select
    {
        return Select::make('article_ids')
            ->label('Artykuły w topicu')
            ->helperText('Próg publikacji to minimum 3 powiązane artykuły, które są jednocześnie actively-distributed i indexable.')
            ->multiple()
            ->searchable()
            ->live()
            ->getSearchResultsUsing(function (string $search): array {
                $needle = '%'.mb_strtolower(trim($search)).'%';

                return ContentArticle::query()
                    ->where(function (Builder $query) use ($needle): void {
                        $query
                            ->whereRaw('LOWER(title) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(slug) LIKE ?', [$needle]);
                    })
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (ContentArticle $article): array => [
                        $article->id => static::articleLabel($article),
                    ])
                    ->all();
            })
            ->getOptionLabelsUsing(fn (array $values): array => ContentArticle::query()
                ->whereIn('id', $values)
                ->get()
                ->mapWithKeys(fn (ContentArticle $article): array => [
                    $article->id => static::articleLabel($article),
                ])
                ->all());
    }

    protected static function articleLabel(ContentArticle $article): string
    {
        $status = $article->workflow_status instanceof ContentArticleWorkflowStatus
            ? $article->workflow_status->value
            : (string) $article->workflow_status;

        $signals = [$status];

        if ($article->isActivelyDistributed()) {
            $signals[] = 'active';
        }

        if ($article->isIndexable()) {
            $signals[] = 'indexable';
        }

        return $article->title.' · '.$article->slug.' · '.implode(' / ', $signals);
    }

    protected static function statusLabel(?string $status): string
    {
        return match ($status) {
            ContentTopic::STATUS_DRAFT => 'Draft',
            ContentTopic::STATUS_PUBLISHED => 'Opublikowany',
            ContentTopic::STATUS_ARCHIVED => 'Archiwalny',
            default => '-',
        };
    }

    protected static function corpusHealthLabel(?ContentTopic $record): string
    {
        if (! $record instanceof ContentTopic) {
            return 'Zapisz draft, aby policzyć corpus.';
        }

        $eligible = $record->eligibleCorpusCount();
        $minimum = ContentTopic::PUBLICATION_CORPUS_MINIMUM;

        if ($record->isCorpusBelowBaseline()) {
            return "OSTRZEŻENIE: {$eligible}/{$minimum} eligible — topic pozostaje opublikowany, ale nie kwalifikuje się do promocji.";
        }

        return "{$eligible}/{$minimum} eligible";
    }
}
