<?php

namespace App\Filament\Resources\ContentArticles\Pages\Concerns;

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\User;
use App\Support\ContentArticlePublishingService;
use Closure;
use DateTimeInterface;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

trait InteractsWithContentArticleWorkflowActions
{
    /**
     * @return list<Action>
     */
    protected function contentArticleWorkflowActions(): array
    {
        return [
            Action::make('submitForReview')
                ->label('Wyślij do review')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::Draft)
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->submitForReview($article, $actor),
                    'Artykuł wysłany do review.',
                )),
            Action::make('returnToDraft')
                ->label('Wróć do draftu')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::InReview)
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->returnToDraft($article, $actor),
                    'Artykuł wrócił do draftu.',
                )),
            Action::make('markReviewed')
                ->label('Oznacz jako sprawdzony')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->workflowStatus(), [
                    ContentArticleWorkflowStatus::InReview,
                    ContentArticleWorkflowStatus::NeedsReview,
                    ContentArticleWorkflowStatus::Archived,
                ], true))
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->markReviewed($article, $actor),
                    'Review zakończony.',
                )),
            Action::make('schedule')
                ->label('Zaplanuj publikację')
                ->color('info')
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::InReview
                    && $this->contentArticleRecord()->first_published_at === null)
                ->schema([
                    DateTimePicker::make('scheduled_for')
                        ->label('Data i godzina publikacji')
                        ->timezone('Europe/Warsaw')
                        ->seconds(false)
                        ->required(),
                ])
                ->modalHeading('Zaplanuj pierwszą publikację')
                ->modalDescription('Termin jest prezentowany w strefie Europe/Warsaw. Backend ponownie sprawdzi review i kompletność publikacyjną.')
                ->action(fn (array $data): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->schedule(
                        $article,
                        $this->parseWarsawDateTime($data['scheduled_for'] ?? null),
                        $actor,
                    ),
                    'Publikacja została zaplanowana.',
                )),
            Action::make('publishNow')
                ->label('Opublikuj teraz')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->workflowStatus(), [
                    ContentArticleWorkflowStatus::InReview,
                    ContentArticleWorkflowStatus::NeedsReview,
                ], true))
                ->modalDescription('Publikacja przejdzie przez pełną walidację ContentArticlePublishingService.')
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->publish($article, $actor),
                    'Artykuł został opublikowany.',
                )),
            Action::make('markNeedsReview')
                ->label('Wymaga ponownego review')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::Published)
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->markNeedsReview($article, $actor),
                    'Artykuł oznaczono jako wymagający review.',
                )),
            Action::make('archive')
                ->label('Archiwizuj')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::Published)
                ->modalDescription('Archiwizacja kończy aktywną dystrybucję, ale nie jest wycofaniem URL z historii.')
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->archive($article, $actor),
                    'Artykuł został zarchiwizowany.',
                )),
            Action::make('republish')
                ->label('Opublikuj ponownie')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::Archived)
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->republish($article, $actor),
                    'Artykuł został ponownie opublikowany.',
                )),
            Action::make('withdraw')
                ->label('Wycofaj z publikacji')
                ->color('danger')
                ->visible(fn (): bool => in_array($this->workflowStatus(), [
                    ContentArticleWorkflowStatus::Published,
                    ContentArticleWorkflowStatus::NeedsReview,
                    ContentArticleWorkflowStatus::Archived,
                ], true))
                ->schema([
                    Textarea::make('withdrawal_reason')
                        ->label('Powód wycofania')
                        ->helperText('Pole jest wewnętrzne. Wycofanie ustanawia tombstone do czasu ponownego review i publikacji.')
                        ->rows(4)
                        ->required(),
                ])
                ->modalHeading('Wycofaj artykuł z publikacji')
                ->modalDescription('To akcja wysokiego ryzyka. Publiczny URL zostanie oznaczony domenowo jako withdrawn; HTTP 410 jest wdrażane w etapie publicznego renderera.')
                ->action(fn (array $data): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->withdraw(
                        $article,
                        (string) ($data['withdrawal_reason'] ?? ''),
                        $actor,
                    ),
                    'Artykuł został wycofany z publikacji.',
                )),
            Action::make('restoreToReview')
                ->label('Przywróć do review')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::Withdrawn)
                ->modalDescription('Ta akcja nie przywraca publicznej treści. Tombstone pozostaje do czasu pełnego review i skutecznego publish.')
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->restoreToReview($article, $actor),
                    'Artykuł przywrócono do review.',
                )),
            Action::make('feature')
                ->label(fn (): string => $this->contentArticleRecord()->is_featured
                    ? 'Zmień featured'
                    : 'Ustaw featured')
                ->color('primary')
                ->visible(fn (): bool => in_array($this->workflowStatus(), [
                    ContentArticleWorkflowStatus::Scheduled,
                    ContentArticleWorkflowStatus::Published,
                ], true))
                ->fillForm(fn (): array => [
                    'editorial_priority' => (int) $this->contentArticleRecord()->editorial_priority,
                ])
                ->schema([
                    TextInput::make('editorial_priority')
                        ->label('Priorytet redakcyjny')
                        ->helperText('Wpływa na fallback kompozycji, ale nie ustawia konkretnego placementu strony głównej.')
                        ->numeric()
                        ->rules(['integer'])
                        ->minValue(-32768)
                        ->maxValue(32767)
                        ->required(),
                ])
                ->action(fn (array $data): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->setFeatured(
                        $article,
                        true,
                        (int) ($data['editorial_priority'] ?? 0),
                        $actor,
                    ),
                    'Ustawienie featured zostało zapisane.',
                )),
            Action::make('unfeature')
                ->label('Usuń featured')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => (bool) $this->contentArticleRecord()->is_featured)
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->setFeatured(
                        $article,
                        false,
                        null,
                        $actor,
                    ),
                    'Featured zostało wyłączone.',
                )),
            Action::make('breaking')
                ->label(fn (): string => $this->contentArticleRecord()->is_breaking
                    ? 'Zmień Pilne'
                    : 'Ustaw Pilne')
                ->color('danger')
                ->visible(fn (): bool => $this->workflowStatus() === ContentArticleWorkflowStatus::Published
                    && $this->contentArticleType() === ContentArticleType::News)
                ->fillForm(fn (): array => [
                    'breaking_expires_at' => $this->contentArticleRecord()->breaking_expires_at,
                ])
                ->schema([
                    DateTimePicker::make('breaking_expires_at')
                        ->label('Pilne do')
                        ->helperText('Termin musi być w przyszłości. Nie ustawiamy automatycznego czasu wygaśnięcia bez decyzji redakcyjnej.')
                        ->timezone('Europe/Warsaw')
                        ->seconds(false)
                        ->required(),
                ])
                ->action(fn (array $data): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->enableBreaking(
                        $article,
                        $this->parseWarsawDateTime($data['breaking_expires_at'] ?? null),
                        $actor,
                    ),
                    'Stan Pilne został zapisany.',
                )),
            Action::make('clearBreaking')
                ->label('Wyłącz Pilne')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => (bool) $this->contentArticleRecord()->is_breaking)
                ->action(fn (): mixed => $this->runWorkflowAction(
                    fn (ContentArticlePublishingService $service, ContentArticle $article, ?User $actor): ContentArticle => $service->clearBreaking($article, $actor),
                    'Stan Pilne został wyłączony.',
                )),
        ];
    }

    private function runWorkflowAction(Closure $operation, string $successMessage): mixed
    {
        try {
            $result = $operation(
                app(ContentArticlePublishingService::class),
                $this->contentArticleRecord(),
                $this->workflowActor(),
            );

            $this->contentArticleRecord()->refresh();

            Notification::make()
                ->success()
                ->title($successMessage)
                ->send();

            return $result;
        } catch (DomainException|InvalidArgumentException $exception) {
            Notification::make()
                ->danger()
                ->title('Nie można wykonać akcji.')
                ->body($exception->getMessage())
                ->send();

            return null;
        }
    }

    private function contentArticleRecord(): ContentArticle
    {
        $record = $this->getRecord();

        if (! $record instanceof ContentArticle) {
            throw new RuntimeException('Content article workflow action requires a ContentArticle record.');
        }

        return $record;
    }

    private function workflowStatus(): ContentArticleWorkflowStatus
    {
        $status = $this->contentArticleRecord()->workflow_status;

        if ($status instanceof ContentArticleWorkflowStatus) {
            return $status;
        }

        return ContentArticleWorkflowStatus::from((string) $status);
    }

    private function contentArticleType(): ContentArticleType
    {
        $type = $this->contentArticleRecord()->type;

        if ($type instanceof ContentArticleType) {
            return $type;
        }

        return ContentArticleType::from((string) $type);
    }

    private function workflowActor(): ?User
    {
        $actor = auth()->user();

        return $actor instanceof User ? $actor : null;
    }

    private function parseWarsawDateTime(mixed $value): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value->format(DATE_ATOM));
        }

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('Data i godzina są wymagane.');
        }

        return Carbon::parse($value, 'Europe/Warsaw');
    }
}
