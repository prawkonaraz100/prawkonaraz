<?php

namespace App\Filament\Resources\TrafficSigns\Tables;

use App\Models\TrafficSign;
use App\Support\TrafficSignContentOpsService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class TrafficSignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po kodzie, nazwie lub slugu')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak znaków drogowych')
            ->emptyStateDescription('Dodaj pierwszy znak, żeby zacząć budować publiczny klaster SEO.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->columns([
                TextColumn::make('code')
                    ->label('Znak')
                    ->searchable(['code', 'name', 'slug'])
                    ->sortable()
                    ->description(fn (TrafficSign $record): string => implode(' · ', array_filter([
                        $record->name,
                        filled($record->slug) ? $record->slug : null,
                    ])))
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Kategoria')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('author.name')
                    ->label('Autor')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('workflow_status')
                    ->label('Workflow')
                    ->state(fn (TrafficSign $record): string => $record->workflowLabel())
                    ->badge()
                    ->color(fn (TrafficSign $record): string => $record->workflowColor())
                    ->description(fn (TrafficSign $record): string => static::workflowDescription($record))
                    ->wrap(),
                TextColumn::make('freshness_status')
                    ->label('Freshness')
                    ->state(fn (TrafficSign $record): string => $record->freshnessStateLabel())
                    ->badge()
                    ->color(fn (TrafficSign $record): string => $record->freshnessStateColor())
                    ->description(fn (TrafficSign $record): string => static::freshnessDescription($record))
                    ->wrap(),
                TextColumn::make('publication_checklist')
                    ->label('Checklista')
                    ->state(fn (TrafficSign $record): string => $record->publicationChecklistCompletionLabel())
                    ->badge()
                    ->color(fn (TrafficSign $record): string => $record->hasCompletePublicationChecklist() ? 'success' : 'warning')
                    ->description(fn (TrafficSign $record): string => static::publicationChecklistDescription($record))
                    ->wrap(),
                TextColumn::make('seo_status')
                    ->label('SEO')
                    ->state(fn (TrafficSign $record): string => static::seoStatus($record))
                    ->badge()
                    ->color(fn (TrafficSign $record): string => static::seoStatus($record) === 'Gotowe' ? 'success' : 'warning')
                    ->description(fn (TrafficSign $record): string => static::seoStatusDescription($record))
                    ->wrap(),
                TextColumn::make('publication_status')
                    ->label('Publikacja')
                    ->state(fn (TrafficSign $record): string => static::publicationStateLabel($record))
                    ->badge()
                    ->color(fn (TrafficSign $record): string => static::publicationStateColor($record))
                    ->description(fn (TrafficSign $record): ?string => $record->published_at?->format('d.m.Y H:i')),
                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Opublikowany'),
                SelectFilter::make('workflow_status')
                    ->label('Workflow')
                    ->options(TrafficSign::workflowOptions()),
                TernaryFilter::make('source_checked_at')
                    ->label('Źródła potwierdzone')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('source_checked_at'),
                        false: fn ($query) => $query->whereNull('source_checked_at'),
                        blank: fn ($query) => $query,
                    ),
                SelectFilter::make('traffic_sign_category_id')
                    ->label('Kategoria')
                    ->relationship('category', 'name'),
                SelectFilter::make('content_author_id')
                    ->label('Autor')
                    ->relationship('author', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Podgląd'),
                EditAction::make()
                    ->label('Edytuj'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_to_review')
                        ->label('Przenieś do review')
                        ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = app(TrafficSignContentOpsService::class)->sendToReview($records);

                            static::sendCountNotification(
                                $updated,
                                'Zaktualizowano workflow do review.',
                                'Wybrane znaki są gotowe na kolejny pass redakcyjny.',
                            );
                        }),
                    BulkAction::make('mark_needs_review')
                        ->label('Oznacz: wymaga przeglądu')
                        ->icon(Heroicon::OutlinedExclamationTriangle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = app(TrafficSignContentOpsService::class)->markNeedsReview($records);

                            static::sendCountNotification(
                                $updated,
                                'Oznaczono rekordy do kolejnego przeglądu.',
                                'Wybrane znaki wróciły na listę rzeczy wymagających review.',
                            );
                        }),
                    BulkAction::make('confirm_sources')
                        ->label('Potwierdź źródła')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = app(TrafficSignContentOpsService::class)->confirmSources($records);

                            static::sendCountNotification(
                                $updated,
                                'Potwierdzono źródła.',
                                'Wybrane znaki mają już zapisany pass źródłowy.',
                            );
                        }),
                    BulkAction::make('schedule_freshness_review')
                        ->label('Ustaw freshness review')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->color('gray')
                        ->schema([
                            DateTimePicker::make('freshness_review_due_at')
                                ->label('Termin kolejnego review')
                                ->seconds(false)
                                ->default(now()->addMonths(6))
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $updated = app(TrafficSignContentOpsService::class)->scheduleFreshnessReview(
                                $records,
                                Carbon::parse($data['freshness_review_due_at']),
                            );

                            static::sendCountNotification(
                                $updated,
                                'Zapisano termin freshness review.',
                                'Wybrane znaki mają już ustawiony kolejny przegląd.',
                            );
                        }),
                    BulkAction::make('publish_ready')
                        ->label('Opublikuj gotowe')
                        ->icon(Heroicon::OutlinedRocketLaunch)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $result = app(TrafficSignContentOpsService::class)->publishReady($records);

                            static::sendPublishNotification($result['updated'], $result['skipped']);
                        }),
                    BulkAction::make('withdraw_publication')
                        ->label('Cofnij publikację')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = app(TrafficSignContentOpsService::class)->withdrawPublication($records);

                            static::sendCountNotification(
                                $updated,
                                'Cofnięto publikację wybranych znaków.',
                                'Rekordy pozostały w panelu i mogą wrócić do review lub kolejnej publikacji.',
                            );
                        }),
                    DeleteBulkAction::make()
                        ->label('Usuń'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    protected static function seoStatus(TrafficSign $record): string
    {
        return static::missingSeoFields($record) === [] ? 'Gotowe' : 'Braki';
    }

    protected static function publicationStateLabel(TrafficSign $record): string
    {
        if (! $record->is_published) {
            return 'Szkic';
        }

        if ($record->published_at === null) {
            return 'Brak daty';
        }

        return $record->published_at->isFuture() ? 'Zaplanowany' : 'Opublikowany';
    }

    protected static function publicationStateColor(TrafficSign $record): string
    {
        return match (static::publicationStateLabel($record)) {
            'Opublikowany' => 'success',
            'Zaplanowany', 'Brak daty' => 'warning',
            default => 'gray',
        };
    }

    protected static function seoStatusDescription(TrafficSign $record): string
    {
        $missing = static::missingSeoFields($record);

        if ($missing === []) {
            return 'Meta i assety podstawowe są uzupełnione.';
        }

        return 'Brakuje: '.implode(', ', $missing);
    }

    protected static function workflowDescription(TrafficSign $record): string
    {
        return implode(' · ', array_filter([
            filled($record->reviewer?->name) ? 'Reviewer: '.$record->reviewer->name : null,
            $record->reviewed_at?->format('d.m.Y H:i') !== null
                ? 'Review: '.$record->reviewed_at?->format('d.m.Y H:i')
                : null,
            'Źródła: '.$record->sourceVerificationLabel(),
        ]));
    }

    protected static function freshnessDescription(TrafficSign $record): string
    {
        if ($record->freshness_review_due_at === null) {
            return 'Brak terminu kolejnego przeglądu.';
        }

        return 'Termin: '.$record->freshness_review_due_at->format('d.m.Y H:i');
    }

    protected static function publicationChecklistDescription(TrafficSign $record): string
    {
        $missing = $record->publicationChecklistMissingLabels();

        if ($missing === []) {
            return 'Rekord ma komplet podstaw do review i publikacji.';
        }

        return 'Braki: '.implode(', ', $missing);
    }

    /**
     * @return list<string>
     */
    protected static function missingSeoFields(TrafficSign $record): array
    {
        $fields = [];

        if (! filled($record->meta_title)) {
            $fields[] = 'meta title';
        }

        if (! filled($record->meta_description)) {
            $fields[] = 'meta description';
        }

        if (! filled($record->image_path)) {
            $fields[] = 'obraz';
        }

        if (! filled($record->og_image_path)) {
            $fields[] = 'OG image';
        }

        return $fields;
    }

    /**
     * @param  list<string>  $skipped
     */
    protected static function sendPublishNotification(int $updated, array $skipped): void
    {
        $notification = Notification::make();

        if ($updated > 0) {
            $notification
                ->success()
                ->title($updated === 1 ? 'Opublikowano 1 znak.' : "Opublikowano {$updated} znaki.");
        } else {
            $notification
                ->warning()
                ->title('Nie opublikowano żadnego znaku.');
        }

        if ($skipped !== []) {
            $preview = implode(', ', array_slice($skipped, 0, 3));
            $suffix = count($skipped) > 3 ? ' i kolejne.' : '.';

            $notification->body("Pominięte przez niepełną checklistę: {$preview}{$suffix}");
        } elseif ($updated > 0) {
            $notification->body('Wszystkie wybrane rekordy spełniały checklistę publikacyjną.');
        }

        $notification->send();
    }

    protected static function sendCountNotification(int $updated, string $emptyTitle, string $body): void
    {
        $notification = Notification::make();

        if ($updated > 0) {
            $notification
                ->success()
                ->title($updated === 1 ? 'Zaktualizowano 1 znak.' : "Zaktualizowano {$updated} znaków.")
                ->body($body);
        } else {
            $notification
                ->warning()
                ->title($emptyTitle);
        }

        $notification->send();
    }
}
