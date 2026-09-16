<?php

namespace App\Filament\Resources\ContentArticles\Pages;

use App\Enums\ContentArticleType;
use App\Filament\Resources\ContentArticles\ContentArticleResource;
use App\Filament\Resources\ContentArticles\Pages\Concerns\InteractsWithContentArticleWorkflowActions;
use App\Models\ContentArticle;
use App\Models\User;
use App\Support\ContentArticleEditToken;
use App\Support\ContentArticlePublishingService;
use App\Support\ContentArticleSlugService;
use App\Support\NewsroomArticleRelationsEditorAdapter;
use App\Support\NewsroomArticleSourcesEditorAdapter;
use App\Support\NewsroomBodyEditorAdapter;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Events\RecordSaved;
use Filament\Resources\Events\RecordUpdated;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class EditContentArticle extends EditRecord
{
    use InteractsWithContentArticleWorkflowActions;

    protected static string $resource = ContentArticleResource::class;

    public bool $publicUpdateMode = false;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = NewsroomBodyEditorAdapter::hydrateArticleData($data);

        if ($this->record instanceof ContentArticle) {
            $data = NewsroomArticleRelationsEditorAdapter::hydrateArticleData($data, $this->record);
            $data['_edit_token'] = app(ContentArticleEditToken::class)->make($this->record);
        }

        return $data;
    }

    public function isPublicUpdateMode(): bool
    {
        return $this->publicUpdateMode
            && $this->record instanceof ContentArticle
            && $this->record->isPubliclyVisible();
    }

    public function beginPublicUpdate(): void
    {
        if (! ($this->record instanceof ContentArticle) || ! $this->record->isPubliclyVisible()) {
            return;
        }

        $this->publicUpdateMode = true;

        Notification::make()
            ->warning()
            ->title('Tryb Apply public update jest aktywny.')
            ->body('Publiczne pola są odblokowane. Zmiana stanie się publiczna natychmiast dopiero po użyciu Apply public update.')
            ->send();
    }

    public function cancelPublicUpdate(): void
    {
        $this->publicUpdateMode = false;

        if ($this->record instanceof ContentArticle) {
            $this->record = $this->record->refresh();
            $this->fillForm();
            $this->rememberData();
        }
    }

    public function applyPublicUpdate(): void
    {
        if (! ($this->record instanceof ContentArticle) || ! $this->record->isPubliclyVisible()) {
            return;
        }

        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');
            $this->form->validate();
            $this->callHook('afterValidate');
            $this->callHook('beforeSave');

            $payload = $this->validatedEditorPayload();

            $loadedToken = trim((string) ($payload['_edit_token'] ?? ''));
            $actor = auth()->user();
            $actor = $actor instanceof User ? $actor : null;

            $updated = app(ContentArticlePublishingService::class)->applyPublicUpdate(
                $this->record,
                $payload,
                $loadedToken,
                $actor,
            );

            $this->record = $updated;
            $this->publicUpdateMode = false;
            $this->fillForm();
            $this->callHook('afterSave');
            Event::dispatch(RecordUpdated::class, [
                'record' => $this->record,
                'data' => $payload,
                'page' => $this,
            ]);
            Event::dispatch(RecordSaved::class, [
                'record' => $this->record,
                'data' => $payload,
                'page' => $this,
            ]);
            $this->rememberData();

            Notification::make()
                ->success()
                ->title('Zmiany publiczne zostały zastosowane.')
                ->send();
        } catch (DomainException|InvalidArgumentException $exception) {
            Notification::make()
                ->danger()
                ->title('Nie zapisano zmian publicznych.')
                ->body($exception->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        if ($this->isPublicUpdateMode()) {
            $this->applyPublicUpdate();

            return;
        }

        $this->authorizeAccess();

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');
            $this->form->validate();
            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($this->validatedEditorPayload());

            $this->callHook('beforeSave');
            $this->record = $this->handleRecordUpdate($this->getRecord(), $data);
            $this->callHook('afterSave');

            Event::dispatch(RecordUpdated::class, [
                'record' => $this->record,
                'data' => $data,
                'page' => $this,
            ]);
            Event::dispatch(RecordSaved::class, [
                'record' => $this->record,
                'data' => $data,
                'page' => $this,
            ]);
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();
        $this->rememberData();

        if ($shouldSendSavedNotification) {
            $this->getSavedNotification()?->send();
        }

        if ($shouldRedirect && ($redirectUrl = $this->getRedirectUrl())) {
            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof ContentArticle) {
            return parent::handleRecordUpdate($record, $data);
        }

        $loadedToken = trim((string) ($data['_edit_token'] ?? ''));
        unset($data['_edit_token']);

        if ($record->isPubliclyVisible()) {
            return DB::transaction(function () use ($record, $data, $loadedToken): ContentArticle {
                $locked = ContentArticle::query()
                    ->whereKey($record->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertFreshToken($locked, $loadedToken);

                $locked->forceFill([
                    'editorial_note' => $data['editorial_note'] ?? $locked->editorial_note,
                ])->save();

                return $locked->refresh();
            });
        }

        try {
            $sourcePayload = NewsroomArticleSourcesEditorAdapter::extractArticleData($data);
            $data = $sourcePayload['article_data'];
            $sources = $sourcePayload['sources'];
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'data.sources' => $exception->getMessage(),
            ]);
        }

        try {
            $data = NewsroomBodyEditorAdapter::normalizeArticleData(
                $data,
                (int) ($record->body_schema_version ?? 1),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'data.body_blocks' => $exception->getMessage(),
            ]);
        }

        $relationPayload = NewsroomArticleRelationsEditorAdapter::extractArticleData($data);
        $data = $relationPayload['article_data'];
        $relations = $relationPayload['relations'];

        $actor = auth()->user();
        $actor = $actor instanceof User ? $actor : null;

        return DB::transaction(function () use (
            $record,
            $data,
            $relations,
            $sources,
            $actor,
            $loadedToken,
        ): ContentArticle {
            $locked = ContentArticle::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertFreshToken($locked, $loadedToken);

            $requestedType = $data['type'] ?? $locked->type;
            $requestedType = $requestedType instanceof ContentArticleType
                ? $requestedType->value
                : (string) $requestedType;

            $requestedSlug = trim((string) ($data['slug'] ?? $locked->slug));

            unset($data['type'], $data['slug'], $data['sources']);

            $locked->fill($data);
            $locked->save();

            $service = app(ContentArticleSlugService::class);
            $currentType = $locked->type instanceof ContentArticleType
                ? $locked->type->value
                : (string) $locked->type;

            if ($requestedType !== $currentType) {
                $locked = $service->changeType($locked, $requestedType, $actor);
            }

            if ($requestedSlug !== '' && $requestedSlug !== (string) $locked->slug) {
                $locked = $service->changeSlug($locked, $requestedSlug, $actor);
            }

            $locked = NewsroomArticleSourcesEditorAdapter::sync($locked, $sources);

            return NewsroomArticleRelationsEditorAdapter::sync($locked, $relations);
        });
    }

    protected function afterSave(): void
    {
        if (! ($this->record instanceof ContentArticle)) {
            return;
        }

        $this->data['_edit_token'] = app(ContentArticleEditToken::class)->make($this->record->refresh());
    }

    /**
     * @return list<Action>
     */
    protected function getFormActions(): array
    {
        if (! $this->isPublicUpdateMode()) {
            return parent::getFormActions();
        }

        return [
            Action::make('applyPublicUpdate')
                ->label('Apply public update')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Zastosować zmianę publiczną?')
                ->modalDescription('Backend ponownie sprawdzi cały aktualny payload i token edycji. Po zatwierdzeniu zmiana stanie się publiczna natychmiast.')
                ->action(fn () => $this->applyPublicUpdate()),
            Action::make('cancelPublicUpdate')
                ->label('Anuluj edycję publiczną')
                ->color('gray')
                ->action(fn () => $this->cancelPublicUpdate()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('beginPublicUpdate')
                ->label('Apply public update')
                ->color('warning')
                ->visible(fn (): bool => $this->record instanceof ContentArticle
                    && $this->record->isPubliclyVisible()
                    && ! $this->isPublicUpdateMode())
                ->action(fn () => $this->beginPublicUpdate()),
            ...$this->contentArticleWorkflowActions(),
        ];
    }

    /**
     * Build an allowlisted editor payload without triggering Filament relationship persistence.
     *
     * @return array<string, mixed>
     */
    private function validatedEditorPayload(): array
    {
        $snapshot = $this->form->getStateSnapshot();
        $rawState = $this->form->getRawState();

        if ($rawState instanceof Arrayable) {
            $rawState = $rawState->toArray();
        }

        if (is_array($rawState) && array_key_exists('sources', $rawState)) {
            $snapshot['sources'] = $rawState['sources'];
        }

        return $snapshot;
    }

    private function assertFreshToken(ContentArticle $article, string $loadedToken): void
    {
        try {
            app(ContentArticlePublishingService::class)->assertFreshEditToken($article, $loadedToken);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'data.title' => $exception->getMessage(),
            ]);
        }
    }
}
