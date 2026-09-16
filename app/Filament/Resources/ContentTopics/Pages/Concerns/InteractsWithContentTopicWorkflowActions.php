<?php

namespace App\Filament\Resources\ContentTopics\Pages\Concerns;

use App\Models\ContentTopic;
use App\Models\User;
use App\Support\ContentTopicPublishingService;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait InteractsWithContentTopicWorkflowActions
{
    /**
     * @return list<Action>
     */
    protected function contentTopicWorkflowActions(): array
    {
        return [
            Action::make('publishTopic')
                ->label('Opublikuj topic')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->topicStatus() === ContentTopic::STATUS_DRAFT)
                ->modalDescription('Zapisz najpierw bieżące zmiany formularza. Publikacja ponownie sprawdzi opis, corpus i featured article.')
                ->action(fn (): mixed => $this->runTopicWorkflowAction(
                    fn (ContentTopicPublishingService $service, ContentTopic $topic, ?User $actor): ContentTopic => $service->publish($topic, $actor),
                    'Topic został opublikowany.',
                )),
            Action::make('archiveTopic')
                ->label('Archiwizuj topic')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->topicStatus() === ContentTopic::STATUS_PUBLISHED)
                ->modalDescription('Archiwizacja kończy kwalifikację do promocji. Publiczne HTTP 410 pozostaje częścią późniejszego publicznego controllera N4.')
                ->action(fn (): mixed => $this->runTopicWorkflowAction(
                    fn (ContentTopicPublishingService $service, ContentTopic $topic, ?User $actor): ContentTopic => $service->archive($topic, $actor),
                    'Topic został zarchiwizowany.',
                )),
            Action::make('republishTopic')
                ->label('Opublikuj ponownie')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->topicStatus() === ContentTopic::STATUS_ARCHIVED)
                ->modalDescription('Ponowna publikacja wymaga ponownie pełnego baseline corpus i poprawnego featured article.')
                ->action(fn (): mixed => $this->runTopicWorkflowAction(
                    fn (ContentTopicPublishingService $service, ContentTopic $topic, ?User $actor): ContentTopic => $service->republish($topic, $actor),
                    'Topic został ponownie opublikowany.',
                )),
        ];
    }

    private function runTopicWorkflowAction(\Closure $operation, string $successMessage): mixed
    {
        try {
            $result = $operation(
                app(ContentTopicPublishingService::class),
                $this->contentTopicRecord(),
                $this->topicWorkflowActor(),
            );

            $this->record = $result;
            $this->refreshContentTopicEditStateAfterWorkflowAction();

            Notification::make()
                ->success()
                ->title($successMessage)
                ->send();

            return $result;
        } catch (DomainException|ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Nie można wykonać akcji.')
                ->body($exception->getMessage())
                ->persistent()
                ->send();

            return null;
        }
    }

    protected function refreshContentTopicEditStateAfterWorkflowAction(): void
    {
        // Edit pages override this hook to refill the persisted state.
    }

    private function contentTopicRecord(): ContentTopic
    {
        $record = $this->getRecord();

        if (! $record instanceof ContentTopic) {
            throw new RuntimeException('Content topic workflow action requires a ContentTopic record.');
        }

        return $record;
    }

    private function topicStatus(): string
    {
        return (string) $this->contentTopicRecord()->status;
    }

    private function topicWorkflowActor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
