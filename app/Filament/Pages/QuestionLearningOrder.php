<?php

namespace App\Filament\Pages;

use App\Models\QuestionLearningOrderItem;
use App\Support\QuestionLearningOrderAdminService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class QuestionLearningOrder extends Page
{
    public ?int $categoryId = null;

    public ?int $topicId = null;

    public string $questionScope = 'all';

    public ?int $editableSetId = null;

    public string $search = '';

    /**
     * @var array<int|string, int|string|null>
     */
    public array $positions = [];

    /**
     * @var array<int|string, bool>
     */
    public array $selectedItems = [];

    public int|string|null $groupAnchorItemId = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Zawartość';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Kolejność pytań';

    protected string $view = 'filament.pages.question-learning-order';

    protected static ?string $slug = 'kolejnosc-pytan';

    public static function getNavigationLabel(): string
    {
        return 'Kolejność pytań';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::QueueList;
    }

    public function mount(): void
    {
        $categories = $this->service()->categories();
        $this->categoryId = $categories[0]['id'] ?? null;
        $this->topicId = $this->firstTopicId();
    }

    public function getHeading(): string|Htmlable
    {
        return 'Kolejność pytań';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Ręczne układanie stałej kolejności pytań dla nauki klasycznej i Zen.';
    }

    public function updatedCategoryId(): void
    {
        $this->topicId = $this->firstTopicId();
        $this->resetEditableState();
    }

    public function updatedQuestionScope(): void
    {
        $availableTopicIds = collect($this->service()->topicsForCategoryId($this->categoryId, $this->questionScope))
            ->pluck('id')
            ->all();

        if (! in_array($this->topicId, $availableTopicIds, true)) {
            $this->topicId = $availableTopicIds[0] ?? null;
        }

        $this->resetEditableState();
    }

    public function updatedTopicId(): void
    {
        $this->resetEditableState();
    }

    public function selectEditableSet(int $setId): void
    {
        $this->editableSetId = $setId;
        $this->positions = [];
        $this->resetSelection();
    }

    public function createDraft(): void
    {
        if (! $this->categoryId || ! $this->topicId) {
            return;
        }

        try {
            $set = $this->service()->createDraftForEditing(
                $this->categoryId,
                $this->topicId,
                $this->questionScope,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        $this->editableSetId = (int) $set->getKey();
        $this->positions = $this->positionsForSet((int) $set->getKey());
        $this->resetSelection();

        Notification::make()
            ->title('Draft kolejności został utworzony')
            ->body('Możesz teraz zmienić pozycje i opublikować układ.')
            ->success()
            ->send();
    }

    public function savePositions(int $setId): void
    {
        try {
            $this->service()->savePositions($setId, $this->positions, auth()->user());
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        $this->positions = [];
        $this->resetSelection();

        Notification::make()
            ->title('Pozycje zostały zapisane')
            ->body('Lista została znormalizowana co 10 pozycji.')
            ->success()
            ->send();
    }

    public function appendMissing(int $setId): void
    {
        try {
            $this->service()->appendMissing($setId, auth()->user());
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        $this->positions = [];
        $this->resetSelection();

        Notification::make()
            ->title('Brakujące pytania dopięte')
            ->body('Nowe pytania trafiły na koniec draftu.')
            ->success()
            ->send();
    }

    public function removeStale(int $setId): void
    {
        try {
            $this->service()->removeStale($setId, auth()->user());
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        $this->positions = [];
        $this->resetSelection();

        Notification::make()
            ->title('Stare pozycje usunięte')
            ->body('Draft zawiera tylko pytania z aktualnej puli działu.')
            ->success()
            ->send();
    }

    public function moveItem(int $setId, int $itemId, string $direction): void
    {
        try {
            $this->service()->moveItem($setId, $itemId, $direction, auth()->user());
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        $this->positions = [];
        $this->resetSelection();
    }

    /**
     * @param  array<int, int|string>  $itemIds
     */
    public function selectVisibleItems(array $itemIds): void
    {
        foreach ($itemIds as $itemId) {
            $itemId = (int) $itemId;

            if ($itemId > 0) {
                $this->selectedItems[$itemId] = true;
            }
        }
    }

    public function clearSelectedItems(): void
    {
        $this->resetSelection();
    }

    public function moveSelectedItems(int $setId, string $placement): void
    {
        try {
            $this->service()->moveItems(
                $setId,
                $this->selectedItemIds(),
                $placement,
                $this->groupAnchorItemId ? (int) $this->groupAnchorItemId : null,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        $this->positions = [];
        $this->resetSelection();

        Notification::make()
            ->title('Zaznaczone pytania przeniesione')
            ->body('Grupa została przesunięta i pozycje zostaną znormalizowane co 10.')
            ->success()
            ->send();
    }

    public function deleteDraft(int $setId): void
    {
        try {
            $this->service()->deleteDraft($setId, auth()->user());
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        if ($this->editableSetId === $setId) {
            $this->editableSetId = null;
        }

        $this->positions = [];
        $this->resetSelection();

        Notification::make()
            ->title('Draft został usunięty')
            ->body('Robocza wersja kolejności została skasowana.')
            ->success()
            ->send();
    }

    public function publish(int $setId): void
    {
        try {
            $this->service()->publish($setId, auth()->user());
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception);

            return;
        }

        $this->editableSetId = null;
        $this->positions = [];
        $this->resetSelection();

        Notification::make()
            ->title('Kolejność została opublikowana')
            ->body('Stała kolejność w nauce zacznie używać tego układu dla wybranego działu.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $overview = $this->service()->build(
            $this->categoryId,
            $this->topicId,
            $this->questionScope,
            $this->editableSetId,
        );

        $this->syncPositionInputs($overview);
        $overview = $this->filterOverview($overview);

        return [
            'overview' => $overview,
            'scopeOptions' => [
                'all' => 'Wszystkie',
                'basic' => 'Podstawowe',
                'specialist' => 'Specjalistyczne',
            ],
        ];
    }

    protected function firstTopicId(): ?int
    {
        $topics = $this->service()->topicsForCategoryId($this->categoryId, $this->questionScope);

        return $topics[0]['id'] ?? null;
    }

    protected function resetEditableState(): void
    {
        $this->editableSetId = null;
        $this->positions = [];
        $this->search = '';
        $this->resetSelection();
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return array<string, mixed>
     */
    protected function filterOverview(array $overview): array
    {
        $search = mb_strtolower(trim($this->search));

        if ($search === '') {
            return $overview;
        }

        $overview['rows'] = collect($overview['rows'] ?? [])
            ->filter(function (array $row) use ($search): bool {
                return str_contains(mb_strtolower((string) ($row['prompt'] ?? '')), $search)
                    || str_contains(mb_strtolower((string) ($row['external_id'] ?? '')), $search)
                    || str_contains((string) ($row['question_id'] ?? ''), $search);
            })
            ->values()
            ->all();

        $overview['missing_questions'] = collect($overview['missing_questions'] ?? [])
            ->filter(function (array $row) use ($search): bool {
                return str_contains(mb_strtolower((string) ($row['prompt'] ?? '')), $search)
                    || str_contains(mb_strtolower((string) ($row['external_id'] ?? '')), $search)
                    || str_contains((string) ($row['question_id'] ?? ''), $search);
            })
            ->values()
            ->all();

        return $overview;
    }

    /**
     * @param  array<string, mixed>  $overview
     */
    protected function syncPositionInputs(array $overview): void
    {
        $editableSet = $overview['editable_set'] ?? null;

        if (! is_array($editableSet) || ($editableSet['status'] ?? null) !== 'draft') {
            return;
        }

        if ($this->editableSetId !== (int) $editableSet['id'] || $this->positions === []) {
            $this->editableSetId = (int) $editableSet['id'];
            $this->positions = collect($overview['rows'] ?? [])
                ->filter(fn (array $row): bool => isset($row['item_id']))
                ->mapWithKeys(fn (array $row): array => [(int) $row['item_id'] => (int) $row['position']])
                ->all();
        }
    }

    /**
     * @return array<int, int>
     */
    protected function positionsForSet(int $setId): array
    {
        return QuestionLearningOrderItem::query()
            ->where('question_learning_order_set_id', $setId)
            ->orderBy('position')
            ->pluck('position', 'id')
            ->mapWithKeys(fn (mixed $position, mixed $itemId): array => [(int) $itemId => (int) $position])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected function selectedItemIds(): array
    {
        return collect($this->selectedItems)
            ->filter()
            ->keys()
            ->map(fn (mixed $itemId): int => (int) $itemId)
            ->filter(fn (int $itemId): bool => $itemId > 0)
            ->values()
            ->all();
    }

    protected function resetSelection(): void
    {
        $this->selectedItems = [];
        $this->groupAnchorItemId = null;
    }

    protected function notifyValidation(ValidationException $exception): void
    {
        $message = collect($exception->errors())
            ->flatten()
            ->first() ?? 'Nie udało się wykonać tej operacji.';

        Notification::make()
            ->title('Operacja zatrzymana')
            ->body((string) $message)
            ->danger()
            ->send();
    }

    protected function service(): QuestionLearningOrderAdminService
    {
        return app(QuestionLearningOrderAdminService::class);
    }
}
