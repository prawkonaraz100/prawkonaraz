<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionLearningOrderItem;
use App\Models\QuestionLearningOrderSet;
use App\Models\QuestionTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionLearningOrderAdminService
{
    public function __construct(
        protected QuestionLearningOrderService $questionLearningOrderService,
        protected QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        protected QuestionTopicLabelResolver $questionTopicLabelResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?int $categoryId, ?int $topicId, string $questionScope = 'all', ?int $editableSetId = null): array
    {
        $questionScope = $this->normalizeScope($questionScope);
        $categories = $this->categories();
        $selectedCategory = $categoryId ? LicenseCategory::query()->find($categoryId) : null;
        $topics = $selectedCategory ? $this->topicsForCategory($selectedCategory, $questionScope) : collect();
        $selectedTopic = $topicId ? $topics->firstWhere('id', $topicId) : null;

        if (! $selectedCategory || ! $selectedTopic) {
            return [
                'categories' => $categories,
                'topics' => $topics->values()->all(),
                'selected_category' => $selectedCategory,
                'selected_topic' => null,
                'question_scope' => $questionScope,
                'active_set' => null,
                'draft_sets' => [],
                'editable_set' => null,
                'rows' => [],
                'missing_questions' => [],
                'stale_count' => 0,
                'missing_count' => 0,
                'current_pool_count' => 0,
            ];
        }

        /** @var QuestionTopic $topic */
        $topic = QuestionTopic::query()->findOrFail((int) $selectedTopic['id']);
        $currentQuestionIds = $this->currentQuestionIds($selectedCategory, $topic, $questionScope);
        $activeSet = $this->activeSet($selectedCategory, $topic, $questionScope);
        $draftSets = $this->draftSets($selectedCategory, $topic, $questionScope);
        $editableSet = $editableSetId
            ? QuestionLearningOrderSet::query()->whereKey($editableSetId)->with('items')->first()
            : $draftSets->first();

        if ($editableSet && ! $this->setMatchesSelection($editableSet, $selectedCategory, $topic, $questionScope)) {
            $editableSet = null;
        }

        $displaySet = $editableSet ?? $activeSet;
        $diagnostics = $displaySet
            ? $this->setDiagnostics($displaySet, $currentQuestionIds)
            : [
                'rows' => [],
                'missing_questions' => [],
                'stale_count' => 0,
                'missing_count' => 0,
            ];

        return [
            'categories' => $categories,
            'topics' => $topics->values()->all(),
            'selected_category' => $selectedCategory,
            'selected_topic' => $selectedTopic,
            'question_scope' => $questionScope,
            'active_set' => $activeSet ? $this->setSummary($activeSet) : null,
            'draft_sets' => $draftSets->map(fn (QuestionLearningOrderSet $set): array => $this->setSummary($set))->values()->all(),
            'editable_set' => $displaySet ? $this->setSummary($displaySet) : null,
            'rows' => $diagnostics['rows'],
            'missing_questions' => $diagnostics['missing_questions'],
            'stale_count' => $diagnostics['stale_count'],
            'missing_count' => $diagnostics['missing_count'],
            'current_pool_count' => count($currentQuestionIds),
        ];
    }

    /**
     * @return array<int, array{id:int,code:string,name:string}>
     */
    public function categories(): array
    {
        return LicenseCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (LicenseCategory $category): array => [
                'id' => (int) $category->getKey(),
                'code' => (string) $category->code,
                'name' => (string) $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,key:string,name:string,technical_name:string,questions_count:int}>
     */
    public function topicsForCategoryId(?int $categoryId, string $questionScope = 'all'): array
    {
        $category = $categoryId ? LicenseCategory::query()->find($categoryId) : null;

        return $category
            ? $this->topicsForCategory($category, $questionScope)->values()->all()
            : [];
    }

    public function createDraftFromFallback(int $categoryId, int $topicId, string $questionScope, User $admin): QuestionLearningOrderSet
    {
        $category = LicenseCategory::query()->findOrFail($categoryId);
        $topic = QuestionTopic::query()->findOrFail($topicId);
        $questionScope = $this->normalizeScope($questionScope);
        $questionIds = $this->currentQuestionIds($category, $topic, $questionScope);

        if ($questionIds === []) {
            throw ValidationException::withMessages([
                'question_topic_id' => 'Ten dział nie ma aktywnych pytań w wybranym zakresie.',
            ]);
        }

        return DB::transaction(function () use ($admin, $category, $questionIds, $questionScope, $topic): QuestionLearningOrderSet {
            $version = ((int) QuestionLearningOrderSet::query()
                ->where('license_category_id', $category->getKey())
                ->where('question_topic_id', $topic->getKey())
                ->where('question_scope', $questionScope)
                ->max('version')) + 1;

            $set = QuestionLearningOrderSet::query()->create([
                'license_category_id' => $category->getKey(),
                'question_topic_id' => $topic->getKey(),
                'question_scope' => $questionScope,
                'status' => QuestionLearningOrderSet::STATUS_DRAFT,
                'version' => $version,
                'created_by' => $admin->getKey(),
                'updated_by' => $admin->getKey(),
            ]);

            $this->insertItems($set, $questionIds);

            return $set;
        });
    }

    public function createDraftForEditing(int $categoryId, int $topicId, string $questionScope, User $admin): QuestionLearningOrderSet
    {
        $category = LicenseCategory::query()->findOrFail($categoryId);
        $topic = QuestionTopic::query()->findOrFail($topicId);
        $questionScope = $this->normalizeScope($questionScope);
        $activeSet = $this->activeSet($category, $topic, $questionScope);
        $questionIds = $activeSet
            ? $activeSet->items
                ->sortBy('position')
                ->pluck('question_id')
                ->map(fn (mixed $questionId): int => (int) $questionId)
                ->values()
                ->all()
            : $this->currentQuestionIds($category, $topic, $questionScope);

        if ($questionIds === []) {
            throw ValidationException::withMessages([
                'question_topic_id' => 'Ten dział nie ma aktywnych pytań w wybranym zakresie.',
            ]);
        }

        return DB::transaction(function () use ($admin, $category, $questionIds, $questionScope, $topic): QuestionLearningOrderSet {
            $version = ((int) QuestionLearningOrderSet::query()
                ->where('license_category_id', $category->getKey())
                ->where('question_topic_id', $topic->getKey())
                ->where('question_scope', $questionScope)
                ->max('version')) + 1;

            $set = QuestionLearningOrderSet::query()->create([
                'license_category_id' => $category->getKey(),
                'question_topic_id' => $topic->getKey(),
                'question_scope' => $questionScope,
                'status' => QuestionLearningOrderSet::STATUS_DRAFT,
                'version' => $version,
                'created_by' => $admin->getKey(),
                'updated_by' => $admin->getKey(),
            ]);

            $this->insertItems($set, $questionIds);

            return $set;
        });
    }

    public function deleteDraft(int $setId, User $admin): void
    {
        $set = QuestionLearningOrderSet::query()->findOrFail($setId);

        if ($set->status !== QuestionLearningOrderSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'set' => 'Usuwać można tylko drafty. Opublikowane układy zostają jako historia wersji.',
            ]);
        }

        $set->delete();
    }

    /**
     * @param  array<int|string, mixed>  $positions
     */
    public function savePositions(int $setId, array $positions, User $admin): void
    {
        $set = QuestionLearningOrderSet::query()->findOrFail($setId);

        if ($set->status !== QuestionLearningOrderSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'set' => 'Pozycje można edytować tylko w drafcie.',
            ]);
        }

        $items = $set->items()->get()->keyBy('id');
        $normalized = [];

        foreach ($positions as $itemId => $position) {
            $itemId = (int) $itemId;

            if (! $items->has($itemId)) {
                continue;
            }

            $position = max((int) $position, 1);

            if (isset($normalized[$position])) {
                throw ValidationException::withMessages([
                    'positions' => 'Pozycje nie mogą się powtarzać.',
                ]);
            }

            $normalized[$position] = $itemId;
        }

        if (count($normalized) !== $items->count()) {
            throw ValidationException::withMessages([
                'positions' => 'Brakuje pozycji dla części pytań.',
            ]);
        }

        ksort($normalized);

        DB::transaction(function () use ($admin, $items, $normalized, $set): void {
            $temporaryBase = ((int) $items->max('position')) + 1000000;

            foreach (array_values($normalized) as $index => $itemId) {
                /** @var QuestionLearningOrderItem $item */
                $item = $items->get($itemId);
                $item->forceFill(['position' => $temporaryBase + $index])->save();
            }

            foreach (array_values($normalized) as $index => $itemId) {
                /** @var QuestionLearningOrderItem $item */
                $item = $items->get($itemId);
                $item->forceFill(['position' => ($index + 1) * 10])->save();
            }

            $set->forceFill(['updated_by' => $admin->getKey()])->save();
        });
    }

    public function appendMissing(int $setId, User $admin): void
    {
        $set = QuestionLearningOrderSet::query()->with('items')->findOrFail($setId);

        if ($set->status !== QuestionLearningOrderSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'set' => 'Brakujące pytania można dopinać tylko do draftu.',
            ]);
        }

        $category = $set->licenseCategory()->firstOrFail();
        $topic = $set->questionTopic()->firstOrFail();
        $currentIds = $this->currentQuestionIds($category, $topic, (string) $set->question_scope);
        $existing = $set->items->pluck('question_id')->map(fn (mixed $id): int => (int) $id)->all();
        $existingLookup = array_fill_keys($existing, true);
        $missing = array_values(array_filter($currentIds, fn (int $id): bool => ! isset($existingLookup[$id])));

        if ($missing === []) {
            return;
        }

        $start = ((int) $set->items->max('position')) + 10;

        DB::transaction(function () use ($admin, $missing, $set, $start): void {
            $this->insertItems($set, $missing, $start);
            $set->forceFill(['updated_by' => $admin->getKey()])->save();
        });
    }

    public function removeStale(int $setId, User $admin): void
    {
        $set = QuestionLearningOrderSet::query()->with('items')->findOrFail($setId);

        if ($set->status !== QuestionLearningOrderSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'set' => 'Stare pozycje można usuwać tylko z draftu.',
            ]);
        }

        $category = $set->licenseCategory()->firstOrFail();
        $topic = $set->questionTopic()->firstOrFail();
        $currentLookup = array_fill_keys($this->currentQuestionIds($category, $topic, (string) $set->question_scope), true);
        $staleIds = $set->items
            ->filter(fn (QuestionLearningOrderItem $item): bool => ! isset($currentLookup[(int) $item->question_id]))
            ->pluck('id')
            ->all();

        if ($staleIds === []) {
            return;
        }

        DB::transaction(function () use ($admin, $set, $staleIds): void {
            QuestionLearningOrderItem::query()->whereIn('id', $staleIds)->delete();
            $set->forceFill(['updated_by' => $admin->getKey()])->save();
        });
    }

    public function moveItem(int $setId, int $itemId, string $direction, User $admin): void
    {
        $set = QuestionLearningOrderSet::query()->with('items')->findOrFail($setId);

        if ($set->status !== QuestionLearningOrderSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'set' => 'Pytania można przesuwać tylko w drafcie.',
            ]);
        }

        $items = $set->items->sortBy('position')->values();
        $currentIndex = $items->search(fn (QuestionLearningOrderItem $item): bool => (int) $item->getKey() === $itemId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = match ($direction) {
            'top' => 0,
            'bottom' => $items->count() - 1,
            'up' => max((int) $currentIndex - 1, 0),
            'down' => min((int) $currentIndex + 1, $items->count() - 1),
            default => (int) $currentIndex,
        };

        if ($targetIndex === (int) $currentIndex) {
            return;
        }

        $moving = $items->pull((int) $currentIndex);
        $items->splice($targetIndex, 0, [$moving]);

        DB::transaction(function () use ($admin, $items, $set): void {
            $temporaryBase = ((int) $items->max('position')) + 1000000;

            $items->values()->each(function (QuestionLearningOrderItem $item, int $index) use ($temporaryBase): void {
                $item->forceFill(['position' => $temporaryBase + $index])->save();
            });

            $items->values()->each(function (QuestionLearningOrderItem $item, int $index): void {
                $item->forceFill(['position' => ($index + 1) * 10])->save();
            });

            $set->forceFill(['updated_by' => $admin->getKey()])->save();
        });
    }

    /**
     * @param  array<int, int>  $itemIds
     */
    public function moveItems(int $setId, array $itemIds, string $placement, ?int $anchorItemId, User $admin): void
    {
        $set = QuestionLearningOrderSet::query()->with('items')->findOrFail($setId);

        if ($set->status !== QuestionLearningOrderSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'set' => 'Pytania można grupować tylko w drafcie.',
            ]);
        }

        $selectedLookup = collect($itemIds)
            ->map(fn (mixed $itemId): int => (int) $itemId)
            ->filter(fn (int $itemId): bool => $itemId > 0)
            ->unique()
            ->flip();

        if ($selectedLookup->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Zaznacz przynajmniej jedno pytanie do przeniesienia.',
            ]);
        }

        $items = $set->items->sortBy('position')->values();
        $moving = $items
            ->filter(fn (QuestionLearningOrderItem $item): bool => $selectedLookup->has((int) $item->getKey()))
            ->values();

        if ($moving->isEmpty()) {
            return;
        }

        if (in_array($placement, ['before', 'after'], true)) {
            if (! $anchorItemId || $selectedLookup->has($anchorItemId)) {
                throw ValidationException::withMessages([
                    'anchor' => 'Wybierz pytanie spoza zaznaczonej grupy jako miejsce wstawienia.',
                ]);
            }
        }

        $remaining = $items
            ->reject(fn (QuestionLearningOrderItem $item): bool => $selectedLookup->has((int) $item->getKey()))
            ->values();

        $targetIndex = match ($placement) {
            'top' => 0,
            'bottom' => $remaining->count(),
            'before', 'after' => $this->anchorInsertionIndex($remaining, (int) $anchorItemId, $placement),
            default => null,
        };

        if ($targetIndex === null) {
            throw ValidationException::withMessages([
                'placement' => 'Nieznany kierunek przenoszenia grupy.',
            ]);
        }

        $ordered = $remaining
            ->slice(0, $targetIndex)
            ->concat($moving)
            ->concat($remaining->slice($targetIndex))
            ->values();

        DB::transaction(function () use ($admin, $ordered, $set): void {
            $temporaryBase = ((int) $ordered->max('position')) + 1000000;

            $ordered->each(function (QuestionLearningOrderItem $item, int $index) use ($temporaryBase): void {
                $item->forceFill(['position' => $temporaryBase + $index])->save();
            });

            $ordered->each(function (QuestionLearningOrderItem $item, int $index): void {
                $item->forceFill(['position' => ($index + 1) * 10])->save();
            });

            $set->forceFill(['updated_by' => $admin->getKey()])->save();
        });
    }

    public function publish(int $setId, User $admin): void
    {
        $set = QuestionLearningOrderSet::query()->with('items')->findOrFail($setId);
        $category = $set->licenseCategory()->firstOrFail();
        $topic = $set->questionTopic()->firstOrFail();
        $currentIds = $this->currentQuestionIds($category, $topic, (string) $set->question_scope);
        $diagnostics = $this->setDiagnostics($set, $currentIds);

        if ($diagnostics['stale_count'] > 0 || $diagnostics['missing_count'] > 0) {
            throw ValidationException::withMessages([
                'set' => 'Przed publikacją usuń stare pozycje i dopnij wszystkie brakujące pytania.',
            ]);
        }

        try {
            DB::transaction(function () use ($admin, $set): void {
                QuestionLearningOrderSet::query()
                    ->where('license_category_id', $set->license_category_id)
                    ->where('question_topic_id', $set->question_topic_id)
                    ->where('question_scope', $set->question_scope)
                    ->where('status', QuestionLearningOrderSet::STATUS_ACTIVE)
                    ->lockForUpdate()
                    ->get()
                    ->each(fn (QuestionLearningOrderSet $activeSet) => $activeSet->forceFill([
                        'status' => QuestionLearningOrderSet::STATUS_ARCHIVED,
                        'active_marker' => null,
                        'updated_by' => $admin->getKey(),
                    ])->save());

                $set->forceFill([
                    'status' => QuestionLearningOrderSet::STATUS_ACTIVE,
                    'active_marker' => QuestionLearningOrderSet::ACTIVE_MARKER,
                    'updated_by' => $admin->getKey(),
                    'published_at' => now(),
                ])->save();
            });
        } catch (QueryException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'set' => 'Ktoś opublikował kolejny set przed Tobą. Odśwież widok i spróbuj ponownie.',
            ]);
        }
    }

    /**
     * @return Collection<int, array{id:int,key:string,name:string,technical_name:string,questions_count:int}>
     */
    protected function topicsForCategory(LicenseCategory $category, string $questionScope): Collection
    {
        $query = Question::query()
            ->selectRaw('question_topic_id, count(*) as aggregate')
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('question_topic_id')
            ->groupBy('question_topic_id');

        $this->applyQuestionScopeFilter($query, $questionScope);

        $counts = $query
            ->pluck('aggregate', 'question_topic_id')
            ->mapWithKeys(fn (mixed $count, mixed $topicId): array => [(int) $topicId => (int) $count]);

        if ($counts->isEmpty()) {
            return collect();
        }

        return QuestionTopic::query()
            ->whereIn('id', $counts->keys()->all())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'key', 'name'])
            ->pipe(function (Collection $topics) use ($category, $counts): Collection {
                $labels = $this->questionTopicLabelResolver->labelsForCategory($category, $topics);

                return $topics->map(fn (QuestionTopic $topic): array => [
                    'id' => (int) $topic->getKey(),
                    'key' => (string) $topic->key,
                    'name' => (string) $labels->get((int) $topic->getKey()),
                    'technical_name' => (string) $topic->name,
                    'questions_count' => (int) ($counts->get((int) $topic->getKey()) ?? 0),
                ]);
            });
    }

    /**
     * @return array<int, int>
     */
    protected function currentQuestionIds(LicenseCategory $category, QuestionTopic $topic, string $questionScope): array
    {
        return $this->questionLearningOrderService->fallbackQuestionIds(
            $this->questionLearningOrderService->baseQuestionQuery(
                $category,
                (int) $topic->getKey(),
                $this->normalizeScope($questionScope),
            ),
        );
    }

    protected function activeSet(LicenseCategory $category, QuestionTopic $topic, string $questionScope): ?QuestionLearningOrderSet
    {
        return QuestionLearningOrderSet::query()
            ->where('license_category_id', $category->getKey())
            ->where('question_topic_id', $topic->getKey())
            ->where('question_scope', $this->normalizeScope($questionScope))
            ->where('status', QuestionLearningOrderSet::STATUS_ACTIVE)
            ->where('active_marker', QuestionLearningOrderSet::ACTIVE_MARKER)
            ->with('items')
            ->first();
    }

    /**
     * @param  Collection<int, QuestionLearningOrderItem>  $remaining
     */
    protected function anchorInsertionIndex(Collection $remaining, int $anchorItemId, string $placement): int
    {
        $anchorIndex = $remaining->search(
            fn (QuestionLearningOrderItem $item): bool => (int) $item->getKey() === $anchorItemId,
        );

        if ($anchorIndex === false) {
            throw ValidationException::withMessages([
                'anchor' => 'Nie znaleziono pytania, przy którym grupa ma zostać wstawiona.',
            ]);
        }

        return $placement === 'after'
            ? ((int) $anchorIndex) + 1
            : (int) $anchorIndex;
    }

    /**
     * @return Collection<int, QuestionLearningOrderSet>
     */
    protected function draftSets(LicenseCategory $category, QuestionTopic $topic, string $questionScope): Collection
    {
        return QuestionLearningOrderSet::query()
            ->where('license_category_id', $category->getKey())
            ->where('question_topic_id', $topic->getKey())
            ->where('question_scope', $this->normalizeScope($questionScope))
            ->where('status', QuestionLearningOrderSet::STATUS_DRAFT)
            ->with('items')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<int, int>  $currentQuestionIds
     * @return array{rows:array<int,array<string,mixed>>,missing_questions:array<int,array<string,mixed>>,stale_count:int,missing_count:int}
     */
    protected function setDiagnostics(QuestionLearningOrderSet $set, array $currentQuestionIds): array
    {
        $currentLookup = array_fill_keys($currentQuestionIds, true);
        $itemQuestionIds = $set->items
            ->pluck('question_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $itemQuestionLookup = array_fill_keys($itemQuestionIds, true);
        $questions = $this->questionsById(array_values(array_unique(array_merge($itemQuestionIds, $currentQuestionIds))));
        $rows = [];
        $staleCount = 0;

        foreach ($set->items as $item) {
            $questionId = (int) $item->question_id;
            $question = $questions->get($questionId);
            $isStale = ! isset($currentLookup[$questionId]);

            if ($isStale) {
                $staleCount++;
            }

            $rows[] = [
                'item_id' => (int) $item->getKey(),
                'question_id' => $questionId,
                'position' => (int) $item->position,
                'external_id' => $question?->external_id,
                'prompt' => $this->cleanPrompt($question?->prompt ?? 'Pytanie usunięte z bazy'),
                'raw_prompt' => $question?->prompt ?? 'Pytanie usunięte z bazy',
                'difficulty' => $question?->difficulty,
                'media_preview' => $this->questionMediaPreview($question),
                'scope' => $this->questionScopeLabel($question),
                'state' => $isStale ? 'stale' : 'ok',
            ];
        }

        $missingIds = array_values(array_filter(
            $currentQuestionIds,
            fn (int $questionId): bool => ! isset($itemQuestionLookup[$questionId]),
        ));

        return [
            'rows' => $rows,
            'missing_questions' => $this->questionRows($missingIds, $questions),
            'stale_count' => $staleCount,
            'missing_count' => count($missingIds),
        ];
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return array<int, array<string, mixed>>
     */
    protected function questionRows(array $questionIds, ?Collection $questions = null): array
    {
        $questions ??= $this->questionsById($questionIds);

        return collect($questionIds)
            ->map(function (int $questionId) use ($questions): ?array {
                $question = $questions->get($questionId);

                if (! $question) {
                    return null;
                }

                return [
                    'question_id' => $questionId,
                    'external_id' => $question->external_id,
                    'prompt' => $this->cleanPrompt((string) $question->prompt),
                    'raw_prompt' => $question->prompt,
                    'difficulty' => $question->difficulty,
                    'media_preview' => $this->questionMediaPreview($question),
                    'scope' => $this->questionScopeLabel($question),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return Collection<int, Question>
     */
    protected function questionsById(array $questionIds): Collection
    {
        if ($questionIds === []) {
            return collect();
        }

        return Question::query()
            ->with('media')
            ->whereIn('id', $questionIds)
            ->get(['id', 'external_id', 'prompt', 'difficulty', 'metadata'])
            ->keyBy('id');
    }

    /**
     * @return array{kind:string,thumbnail_url:string|null,full_url:string|null,label:string}|null
     */
    protected function questionMediaPreview(?Question $question): ?array
    {
        if (! $question) {
            return null;
        }

        $payload = $this->questionMediaPayloadBuilder->forCatalog($question->media);
        $primary = $payload[0] ?? null;

        if (! is_array($primary)) {
            return null;
        }

        $kind = (string) ($primary['kind'] ?? 'image');
        $thumbnailUrl = $kind === 'video'
            ? ($primary['poster_url'] ?? null)
            : ($primary['thumb_url'] ?? $primary['url'] ?? $primary['poster_url'] ?? null);
        $fullUrl = $kind === 'video'
            ? ($primary['url'] ?? null)
            : ($primary['full_url'] ?? $primary['url'] ?? null);

        return [
            'kind' => $kind,
            'thumbnail_url' => is_string($thumbnailUrl) ? $thumbnailUrl : null,
            'full_url' => is_string($fullUrl) ? $fullUrl : null,
            'label' => $kind === 'video' ? 'Wideo' : 'Grafika',
        ];
    }

    /**
     * @param  array<int, int>  $questionIds
     */
    protected function insertItems(QuestionLearningOrderSet $set, array $questionIds, int $startPosition = 10): void
    {
        $now = now();
        $position = $startPosition;

        $rows = collect($questionIds)
            ->map(function (int $questionId) use (&$position, $now, $set): array {
                $row = [
                    'question_learning_order_set_id' => $set->getKey(),
                    'question_id' => $questionId,
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $position += 10;

                return $row;
            })
            ->all();

        QuestionLearningOrderItem::query()->insert($rows);
    }

    /**
     * @return array<string, mixed>
     */
    protected function setSummary(QuestionLearningOrderSet $set): array
    {
        return [
            'id' => (int) $set->getKey(),
            'status' => (string) $set->status,
            'version' => (int) $set->version,
            'items_count' => $set->relationLoaded('items') ? $set->items->count() : $set->items()->count(),
            'published_at' => $set->published_at?->toDateTimeString(),
            'updated_at' => $set->updated_at?->toDateTimeString(),
        ];
    }

    protected function setMatchesSelection(
        QuestionLearningOrderSet $set,
        LicenseCategory $category,
        QuestionTopic $topic,
        string $questionScope,
    ): bool {
        return (int) $set->license_category_id === (int) $category->getKey()
            && (int) $set->question_topic_id === (int) $topic->getKey()
            && (string) $set->question_scope === $this->normalizeScope($questionScope);
    }

    protected function normalizeScope(string $questionScope): string
    {
        return in_array($questionScope, ['all', 'basic', 'specialist'], true)
            ? $questionScope
            : 'all';
    }

    protected function questionScopeLabel(?Question $question): string
    {
        if (! $question) {
            return '-';
        }

        return strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')) === 'SPECJALISTYCZNY'
            ? 'Specjalistyczne'
            : 'Podstawowe';
    }

    protected function cleanPrompt(string $prompt): string
    {
        $prompt = preg_replace('/\*\*(.*?)\*\*/u', '$1', $prompt) ?? $prompt;
        $prompt = str_replace(
            ['[green]', '[/green]', '[red]', '[/red]'],
            '',
            $prompt,
        );

        return trim($prompt);
    }

    protected function applyQuestionScopeFilter(Builder $query, string $questionScope): void
    {
        if ($questionScope === 'specialist') {
            $query->where('metadata->structure_scope', 'SPECJALISTYCZNY');

            return;
        }

        if ($questionScope === 'basic') {
            $query->where(function ($scopeQuery): void {
                $scopeQuery
                    ->whereNull('metadata->structure_scope')
                    ->orWhere('metadata->structure_scope', '!=', 'SPECJALISTYCZNY');
            });
        }
    }
}
