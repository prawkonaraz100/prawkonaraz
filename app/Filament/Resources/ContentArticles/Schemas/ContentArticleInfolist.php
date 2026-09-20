<?php

namespace App\Filament\Resources\ContentArticles\Schemas;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Artykuł')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            TextEntry::make('title')
                                ->label('Tytuł')
                                ->columnSpanFull(),
                            TextEntry::make('slug')
                                ->label('Slug'),
                            TextEntry::make('type')
                                ->label('Typ')
                                ->formatStateUsing(fn (mixed $state): string => static::typeLabel($state)),
                            TextEntry::make('category.name')
                                ->label('Kategoria'),
                            TextEntry::make('author.name')
                                ->label('Autor')
                                ->placeholder('-'),
                            TextEntry::make('reviewer.name')
                                ->label('Reviewer')
                                ->placeholder('-'),
                            TextEntry::make('lead')
                                ->label('Lead')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Stan')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            TextEntry::make('workflow_status')
                                ->label('Workflow')
                                ->formatStateUsing(fn (mixed $state): string => static::workflowLabel($state))
                                ->badge(),
                            IconEntry::make('is_featured')
                                ->label('Featured')
                                ->boolean(),
                            IconEntry::make('is_breaking')
                                ->label('Breaking')
                                ->boolean(),
                            TextEntry::make('published_at')
                                ->label('Publikacja')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('scheduled_for')
                                ->label('Zaplanowano')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('-'),
                            TextEntry::make('updated_at')
                                ->label('Aktualizacja')
                                ->dateTime('d.m.Y H:i'),
                        ]),
                ]),
                Section::make('Freshness')
                    ->description('Backoffice status ponownej weryfikacji. Overdue nie zmienia automatycznie workflow ani dystrybucji.')
                    ->schema([
                        TextEntry::make('freshness_status_display')
                            ->label('Status freshness')
                            ->state(fn ($record): string => $record?->freshnessStatus() ?? 'not_scheduled')
                            ->formatStateUsing(fn (mixed $state): string => static::freshnessStatusLabel($state))
                            ->badge()
                            ->color(fn (mixed $state): string => static::freshnessStatusColor($state)),
                        TextEntry::make('source_checked_at')
                            ->label('Źródła sprawdzone')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('freshness_review_due_at')
                            ->label('Review freshness do')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('reviewed_at')
                            ->label('Ostatnie review')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('last_substantive_update_at')
                            ->label('Ostatnia istotna aktualizacja')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('public_state_changed_at')
                            ->label('Ostatnia zmiana public state')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Pochodzenie i kontekst')
                    ->schema([
                        TextEntry::make('origin_type')
                            ->label('Pochodzenie')
                            ->formatStateUsing(fn (mixed $state): string => static::originLabel($state)),
                        TextEntry::make('regulatory_status')
                            ->label('Status regulacyjny')
                            ->formatStateUsing(fn (mixed $state): string => static::regulatoryLabel($state)),
                        TextEntry::make('effective_from')
                            ->label('Obowiązuje od')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                        TextEntry::make('change_summary')
                            ->label('Co się zmienia')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('applies_to')
                            ->label('Kogo dotyczy')
                            ->placeholder('-'),
                        TextEntry::make('exam_impact')
                            ->label('Wpływ na egzamin')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Media / art direction')
                    ->schema([
                        TextEntry::make('hero_image_path')
                            ->label('Hero path')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('hero_image_alt')
                            ->label('Hero alt')
                            ->placeholder('-'),
                        TextEntry::make('hero_dimensions_display')
                            ->label('Hero wymiary')
                            ->state(fn ($record): string => static::dimensionsLabel(
                                $record?->hero_image_width,
                                $record?->hero_image_height,
                            )),
                        TextEntry::make('hero_focal_display')
                            ->label('Focal point')
                            ->state(fn ($record): string => static::focalLabel(
                                $record?->hero_focal_x,
                                $record?->hero_focal_y,
                            )),
                        TextEntry::make('og_image_path')
                            ->label('OG path')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('og_image_alt')
                            ->label('OG alt')
                            ->placeholder('-'),
                        TextEntry::make('og_dimensions_display')
                            ->label('OG wymiary')
                            ->state(fn ($record): string => static::dimensionsLabel(
                                $record?->og_image_width,
                                $record?->og_image_height,
                            )),
                        TextEntry::make('image_credit')
                            ->label('Credit')
                            ->placeholder('-'),
                        TextEntry::make('image_license_note')
                            ->label('Notatka licencyjna (backoffice)')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Notatka wewnętrzna')
                    ->schema([
                        TextEntry::make('editorial_note')
                            ->label('Notatka redakcyjna')
                            ->placeholder('-'),
                    ]),
            ]);
    }

    protected static function originLabel(mixed $state): string
    {
        $value = $state instanceof ContentArticleOriginType ? $state->value : (string) $state;

        return match ($value) {
            'original' => 'Oryginalny materiał',
            'compiled' => 'Opracowanie wielu źródeł',
            'official_source' => 'Źródło oficjalne',
            'data_analysis' => 'Analiza danych',
            'licensed_agency' => 'Agencyjny/licencjonowany',
            default => $value,
        };
    }

    protected static function regulatoryLabel(mixed $state): string
    {
        $value = $state instanceof ContentArticleRegulatoryStatus ? $state->value : (string) $state;

        return match ($value) {
            'not_applicable' => 'Nie dotyczy',
            'proposal' => 'Projekt',
            'consultation' => 'Konsultacje',
            'official_announcement' => 'Oficjalna zapowiedź',
            'adopted_future' => 'Przyjęte — przyszłe',
            'in_force' => 'Obowiązuje',
            default => $value,
        };
    }

    protected static function dimensionsLabel(mixed $width, mixed $height): string
    {
        return (int) $width > 0 && (int) $height > 0
            ? ((int) $width).' × '.((int) $height).' px'
            : '-';
    }

    protected static function focalLabel(mixed $x, mixed $y): string
    {
        if ($x === null || $y === null) {
            return 'Środek (fallback)';
        }

        return number_format((float) $x, 2, '.', '')
            .' / '
            .number_format((float) $y, 2, '.', '');
    }

    protected static function typeLabel(mixed $state): string
    {
        $value = $state instanceof ContentArticleType ? $state->value : (string) $state;

        return match ($value) {
            'news' => 'News',
            'guide' => 'Poradnik',
            'explainer' => 'Explainer',
            'analysis' => 'Analiza',
            'report' => 'Raport',
            default => $value,
        };
    }

    protected static function freshnessStatusLabel(mixed $state): string
    {
        return match ((string) $state) {
            'fresh' => 'Fresh',
            'overdue' => 'Overdue',
            default => 'Not scheduled',
        };
    }

    protected static function freshnessStatusColor(mixed $state): string
    {
        return match ((string) $state) {
            'fresh' => 'success',
            'overdue' => 'danger',
            default => 'gray',
        };
    }

    protected static function workflowLabel(mixed $state): string
    {
        $value = $state instanceof \BackedEnum ? (string) $state->value : (string) $state;

        return match ($value) {
            'draft' => 'Draft',
            'in_review' => 'W review',
            'scheduled' => 'Zaplanowany',
            'published' => 'Opublikowany',
            'needs_review' => 'Wymaga review',
            'archived' => 'Archiwalny',
            'withdrawn' => 'Wycofany',
            default => $value,
        };
    }
}
