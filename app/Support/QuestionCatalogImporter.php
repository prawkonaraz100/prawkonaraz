<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use DateTimeInterface;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class QuestionCatalogImporter
{
    /** @var array<string, QuestionCollection> */
    protected array $collectionCache = [];

    /** @var array<string, QuestionModule> */
    protected array $moduleCache = [];

    /** @var array<int, array{module: QuestionModule, sync_mode: string, assignments: array<int, int>}> */
    protected array $pendingModuleAssignments = [];

    public function __construct(
        protected QuestionDeliveryReadinessService $questionDeliveryReadinessService,
        protected QuestionTopicAssigner $questionTopicAssigner,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function import(array $payload, bool $dryRun = false): array
    {
        $this->resetImportState();

        $startedAt = now()->utc();
        $report = $this->initialReport($payload, $startedAt, $dryRun);
        $categories = Arr::get($payload, 'categories', []);

        DB::beginTransaction();

        try {
            $this->questionTopicAssigner->seedTopics();

            foreach ($categories as $categoryIndex => $categoryData) {
                if (! is_array($categoryData)) {
                    $report['categories_total']++;
                    $this->pushError($report, "categories.{$categoryIndex}", 'Category payload must be an object.');

                    continue;
                }

                $report['categories_total']++;

                $validatedCategory = $this->validateCategory($categoryData, "categories.{$categoryIndex}", $report);

                if ($validatedCategory === null) {
                    continue;
                }

                $category = $this->upsertCategory($validatedCategory, $report);

                foreach (($categoryData['questions'] ?? []) as $questionIndex => $questionData) {
                    $report['questions_total']++;

                    if (! is_array($questionData)) {
                        $this->pushError($report, "categories.{$categoryIndex}.questions.{$questionIndex}", 'Question payload must be an object.');

                        continue;
                    }

                    $validatedQuestion = $this->validateQuestion(
                        $questionData,
                        "categories.{$categoryIndex}.questions.{$questionIndex}",
                        $report,
                    );

                    if ($validatedQuestion === null) {
                        continue;
                    }

                    $question = $this->upsertQuestion($category, $validatedQuestion, $report);
                    $this->syncMedia($question, $validatedQuestion['media'] ?? [], $report);
                    $this->queueModuleAssignment($category, $question, $validatedQuestion, $report);
                    $this->questionTopicAssigner->assign($question);
                    $this->questionDeliveryReadinessService->sync($question);
                }
            }

            $this->synchronizePendingModuleAssignments($report);

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $report['completed_at'] = now()->utc()->toIso8601String();
        $report['errors_count'] = count($report['errors']);

        return $report;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function initialReport(array $payload, DateTimeInterface $startedAt, bool $dryRun): array
    {
        $batchId = Arr::get($payload, 'batch_id');

        if (! is_string($batchId) || $batchId === '') {
            $batchId = 'batch-'.now()->utc()->format('Ymd-His');
        }

        return [
            'batch_id' => $batchId,
            'dry_run' => $dryRun,
            'started_at' => $startedAt->toIso8601String(),
            'completed_at' => null,
            'categories_total' => 0,
            'categories_created' => 0,
            'categories_updated' => 0,
            'questions_total' => 0,
            'questions_created' => 0,
            'questions_updated' => 0,
            'collections_total' => 0,
            'collections_created' => 0,
            'collections_updated' => 0,
            'modules_total' => 0,
            'modules_created' => 0,
            'modules_updated' => 0,
            'module_assignments_total' => 0,
            'module_assignments_created' => 0,
            'module_assignments_updated' => 0,
            'module_assignments_unchanged' => 0,
            'module_assignments_deleted' => 0,
            'media_total' => 0,
            'media_created' => 0,
            'media_updated' => 0,
            'media_unchanged' => 0,
            'media_deleted' => 0,
            'errors_count' => 0,
            'errors' => [],
            'updated_records' => [
                'categories' => [],
                'questions' => [],
                'collections' => [],
                'modules' => [],
            ],
            'created_assets' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $categoryData
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>|null
     */
    protected function validateCategory(array $categoryData, string $path, array &$report): ?array
    {
        $validator = Validator::make($categoryData, [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'questions' => ['nullable', 'array'],
        ]);

        return $this->validatedPayload($validator, $path, $report);
    }

    /**
     * @param  array<string, mixed>  $questionData
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>|null
     */
    protected function validateQuestion(array $questionData, string $path, array &$report): ?array
    {
        $validator = Validator::make($questionData, [
            'external_id' => ['nullable', 'string', 'max:255'],
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'in:a,b,c,A,B,C'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'points' => ['nullable', 'integer', 'min:1', 'max:10'],
            'question_type' => ['nullable', 'string', 'in:single_choice,boolean'],
            'is_active' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'media' => ['nullable', 'array'],
            'collection' => ['nullable', 'array'],
            'collection.code' => ['required_with:collection', 'string', 'max:100'],
            'collection.slug' => ['nullable', 'string', 'max:255'],
            'collection.name' => ['required_with:collection', 'string', 'max:255'],
            'collection.description' => ['nullable', 'string'],
            'collection.kind' => ['required_with:collection', 'string', 'max:50'],
            'collection.source' => ['nullable', 'string', 'max:255'],
            'collection.is_active' => ['nullable', 'boolean'],
            'collection.is_public' => ['nullable', 'boolean'],
            'collection.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'collection.metadata' => ['nullable', 'array'],
            'module' => ['nullable', 'array'],
            'module.source_id' => ['nullable', 'string', 'max:100'],
            'module.code' => ['required_with:module', 'string', 'max:100'],
            'module.slug' => ['nullable', 'string', 'max:255'],
            'module.name' => ['required_with:module', 'string', 'max:255'],
            'module.description' => ['nullable', 'string'],
            'module.expected_questions' => ['nullable', 'integer', 'min:0'],
            'module.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'module.is_active' => ['nullable', 'boolean'],
            'module.sync_mode' => ['nullable', 'string', 'in:upsert,replace'],
            'module.metadata' => ['nullable', 'array'],
            'module_position' => ['nullable', 'integer', 'min:1'],
        ]);

        $validator->after(function (ValidatorContract $validator) use ($questionData): void {
            $questionType = (string) ($questionData['question_type'] ?? 'single_choice');
            $correctAnswer = Str::lower((string) ($questionData['correct_answer'] ?? ''));

            if ($questionType === 'boolean' && $correctAnswer === 'c') {
                $validator->errors()->add('correct_answer', 'Boolean questions can only use answers A or B.');
            }

            $hasCollection = is_array($questionData['collection'] ?? null);
            $hasModule = is_array($questionData['module'] ?? null);

            if ($hasCollection !== $hasModule) {
                $validator->errors()->add('module', 'Collection and module must be provided together.');
            }

            if (($hasCollection || $hasModule) && ! isset($questionData['module_position'])) {
                $validator->errors()->add('module_position', 'Module position is required for a module question.');
            }

            if (isset($questionData['module_position']) && ! $hasModule) {
                $validator->errors()->add('module_position', 'Module position requires collection and module data.');
            }
        });

        $validatedQuestion = $this->validatedPayload($validator, $path, $report);

        if ($validatedQuestion === null) {
            return null;
        }

        $validatedMedia = [];

        foreach (($questionData['media'] ?? []) as $mediaIndex => $mediaData) {
            $report['media_total']++;

            if (! is_array($mediaData)) {
                $this->pushError($report, "{$path}.media.{$mediaIndex}", 'Media payload must be an object.');

                return null;
            }

            $validatedMediaItem = $this->validateMedia($mediaData, "{$path}.media.{$mediaIndex}", $report);

            if ($validatedMediaItem === null) {
                return null;
            }

            $validatedMedia[] = $validatedMediaItem;
        }

        $validatedQuestion['correct_answer'] = Str::lower((string) $validatedQuestion['correct_answer']);
        $validatedQuestion['question_type'] = (string) ($validatedQuestion['question_type'] ?? 'single_choice');
        $validatedQuestion['media'] = $validatedMedia;

        return $validatedQuestion;
    }

    /**
     * @param  array<string, mixed>  $mediaData
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>|null
     */
    protected function validateMedia(array $mediaData, string $path, array &$report): ?array
    {
        $validator = Validator::make($mediaData, [
            'kind' => ['nullable', 'string', 'in:image,video'],
            'disk' => ['nullable', 'string', 'max:50'],
            'path' => ['required', 'string', 'max:2048'],
            'poster_path' => ['nullable', 'string', 'max:2048'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'bytes' => ['nullable', 'integer', 'min:1'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'variant' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $validator->after(function (ValidatorContract $validator) use ($mediaData): void {
            $kind = (string) ($mediaData['kind'] ?? 'image');
            $mimeType = $mediaData['mime_type'] ?? null;
            $variant = (string) ($mediaData['variant'] ?? 'full');

            if ($mimeType !== null && ! in_array((string) $mimeType, (array) config("media.allowed_mime_types.{$kind}", []), true)) {
                $validator->errors()->add('mime_type', 'Unsupported MIME type for the selected media kind.');
            }

            if (! in_array($variant, (array) config("media.allowed_variants.{$kind}", []), true)) {
                $validator->errors()->add('variant', 'Unsupported media variant for the selected media kind.');
            }

            $maxBytes = (int) config("media.max_bytes.{$kind}", 0);

            if ($maxBytes > 0 && isset($mediaData['bytes']) && (int) $mediaData['bytes'] > $maxBytes) {
                $validator->errors()->add('bytes', 'Media asset exceeds the configured size limit.');
            }
        });

        $validatedMedia = $this->validatedPayload($validator, $path, $report);

        if ($validatedMedia === null) {
            return null;
        }

        $validatedMedia['kind'] = (string) ($validatedMedia['kind'] ?? 'image');
        $validatedMedia['variant'] = (string) ($validatedMedia['variant'] ?? 'full');
        $validatedMedia['disk'] = (string) ($validatedMedia['disk'] ?? config('media.default_disk'));

        return $validatedMedia;
    }

    /**
     * @param  array<string, mixed>  $categoryData
     * @param  array<string, mixed>  $report
     */
    protected function upsertCategory(array $categoryData, array &$report): LicenseCategory
    {
        $category = LicenseCategory::query()->firstOrNew([
            'code' => (string) $categoryData['code'],
        ]);
        $wasExisting = $category->exists;

        $category->fill([
            'slug' => (string) ($categoryData['slug'] ?? Str::slug((string) $categoryData['code'])),
            'name' => (string) $categoryData['name'],
            'description' => $categoryData['description'] ?? null,
            'is_active' => (bool) ($categoryData['is_active'] ?? true),
            'sort_order' => (int) ($categoryData['sort_order'] ?? 0),
        ]);
        $category->save();

        if ($wasExisting) {
            $report['categories_updated']++;
        } else {
            $report['categories_created']++;
        }

        $report['updated_records']['categories'][] = [
            'code' => $category->code,
            'action' => $wasExisting ? 'updated' : 'created',
        ];

        return $category;
    }

    /**
     * @param  array<string, mixed>  $questionData
     * @param  array<string, mixed>  $report
     */
    protected function upsertQuestion(LicenseCategory $category, array $questionData, array &$report): Question
    {
        $query = Question::query()
            ->where('license_category_id', $category->getKey());

        if (filled($questionData['external_id'] ?? null)) {
            $question = $query->firstOrNew([
                'external_id' => (string) $questionData['external_id'],
            ]);
        } else {
            $question = $query->firstOrNew([
                'prompt' => (string) $questionData['prompt'],
            ]);
        }
        $wasExisting = $question->exists;

        /** @var Question $question */
        $question->fill([
            'license_category_id' => $category->getKey(),
            'external_id' => $questionData['external_id'] ?? null,
            'prompt' => (string) $questionData['prompt'],
            'explanation' => $questionData['explanation'] ?? null,
            'option_a' => (string) ($questionData['option_a'] ?? 'Tak'),
            'option_b' => (string) ($questionData['option_b'] ?? 'Nie'),
            'option_c' => $questionData['option_c'] ?? null,
            'correct_answer' => Str::lower((string) ($questionData['correct_answer'] ?? 'a')),
            'difficulty' => (int) ($questionData['difficulty'] ?? 1),
            'points' => (int) ($questionData['points'] ?? 1),
            'question_type' => (string) ($questionData['question_type'] ?? 'single_choice'),
            'is_active' => (bool) ($questionData['is_active'] ?? true),
            'source' => $questionData['source'] ?? null,
            'published_at' => $questionData['published_at'] ?? now(),
            'metadata' => is_array($questionData['metadata'] ?? null) ? $questionData['metadata'] : null,
        ]);
        $question->save();

        if ($wasExisting) {
            $report['questions_updated']++;
        } else {
            $report['questions_created']++;
        }

        $report['updated_records']['questions'][] = [
            'category_code' => $category->code,
            'external_id' => $question->external_id,
            'prompt' => Str::limit($question->prompt, 80),
            'action' => $wasExisting ? 'updated' : 'created',
        ];

        return $question;
    }

    protected function resetImportState(): void
    {
        $this->collectionCache = [];
        $this->moduleCache = [];
        $this->pendingModuleAssignments = [];
    }

    /**
     * @param  array<string, mixed>  $collectionData
     * @param  array<string, mixed>  $report
     */
    protected function upsertCollection(
        LicenseCategory $category,
        array $collectionData,
        array &$report,
    ): QuestionCollection {
        $cacheKey = $category->getKey().'|'.(string) $collectionData['code'];

        if (isset($this->collectionCache[$cacheKey])) {
            return $this->collectionCache[$cacheKey];
        }

        $collection = QuestionCollection::query()->firstOrNew([
            'code' => (string) $collectionData['code'],
        ]);
        $wasExisting = $collection->exists;

        $collection->fill([
            'license_category_id' => $category->getKey(),
            'slug' => (string) ($collectionData['slug'] ?? Str::slug((string) $collectionData['code'])),
            'name' => (string) $collectionData['name'],
            'description' => $collectionData['description'] ?? null,
            'kind' => (string) $collectionData['kind'],
            'source' => $collectionData['source'] ?? null,
            'is_active' => (bool) ($collectionData['is_active'] ?? true),
            'is_public' => (bool) ($collectionData['is_public'] ?? false),
            'sort_order' => (int) ($collectionData['sort_order'] ?? 0),
            'metadata' => is_array($collectionData['metadata'] ?? null) ? $collectionData['metadata'] : null,
        ]);
        $collection->save();

        $report['collections_total']++;
        $report[$wasExisting ? 'collections_updated' : 'collections_created']++;
        $report['updated_records']['collections'][] = [
            'code' => $collection->code,
            'category_code' => $category->code,
            'action' => $wasExisting ? 'updated' : 'created',
        ];

        return $this->collectionCache[$cacheKey] = $collection;
    }

    /**
     * @param  array<string, mixed>  $moduleData
     * @param  array<string, mixed>  $report
     */
    protected function upsertModule(
        QuestionCollection $collection,
        array $moduleData,
        array &$report,
    ): QuestionModule {
        $cacheKey = $collection->getKey().'|'.(string) $moduleData['code'];

        if (isset($this->moduleCache[$cacheKey])) {
            return $this->moduleCache[$cacheKey];
        }

        $module = QuestionModule::query()->firstOrNew([
            'question_collection_id' => $collection->getKey(),
            'code' => (string) $moduleData['code'],
        ]);
        $wasExisting = $module->exists;

        $module->fill([
            'source_id' => $moduleData['source_id'] ?? null,
            'slug' => (string) ($moduleData['slug'] ?? Str::slug((string) $moduleData['code'])),
            'name' => (string) $moduleData['name'],
            'description' => $moduleData['description'] ?? null,
            'expected_questions' => isset($moduleData['expected_questions'])
                ? (int) $moduleData['expected_questions']
                : null,
            'sort_order' => (int) ($moduleData['sort_order'] ?? 0),
            'is_active' => (bool) ($moduleData['is_active'] ?? true),
            'metadata' => is_array($moduleData['metadata'] ?? null) ? $moduleData['metadata'] : null,
        ]);
        $module->save();

        $report['modules_total']++;
        $report[$wasExisting ? 'modules_updated' : 'modules_created']++;
        $report['updated_records']['modules'][] = [
            'collection_code' => $collection->code,
            'code' => $module->code,
            'action' => $wasExisting ? 'updated' : 'created',
        ];

        return $this->moduleCache[$cacheKey] = $module;
    }

    /**
     * @param  array<string, mixed>  $questionData
     * @param  array<string, mixed>  $report
     */
    protected function queueModuleAssignment(
        LicenseCategory $category,
        Question $question,
        array $questionData,
        array &$report,
    ): void {
        if (! is_array($questionData['collection'] ?? null) || ! is_array($questionData['module'] ?? null)) {
            return;
        }

        $collection = $this->upsertCollection($category, $questionData['collection'], $report);
        $module = $this->upsertModule($collection, $questionData['module'], $report);
        $moduleId = (int) $module->getKey();

        $this->pendingModuleAssignments[$moduleId] ??= [
            'module' => $module,
            'sync_mode' => (string) ($questionData['module']['sync_mode'] ?? 'upsert'),
            'assignments' => [],
        ];
        $this->pendingModuleAssignments[$moduleId]['assignments'][(int) $question->getKey()] =
            (int) $questionData['module_position'];
        $report['module_assignments_total']++;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function synchronizePendingModuleAssignments(array &$report): void
    {
        foreach ($this->pendingModuleAssignments as $moduleId => $pending) {
            $module = $pending['module'];
            $assignments = $pending['assignments'];
            $positions = array_values($assignments);
            $path = 'modules.'.(string) $module->code;

            if (count($positions) !== count(array_unique($positions))) {
                $this->pushError($report, $path, 'Module question positions must be unique.');

                continue;
            }

            if (
                $pending['sync_mode'] === 'replace'
                && $module->expected_questions !== null
                && count($assignments) !== (int) $module->expected_questions
            ) {
                $this->pushError(
                    $report,
                    $path,
                    sprintf(
                        'Module expected %d questions, but the batch contains %d.',
                        (int) $module->expected_questions,
                        count($assignments),
                    ),
                );

                continue;
            }

            $existing = DB::table('question_module_question')
                ->where('question_module_id', $moduleId)
                ->pluck('position', 'question_id')
                ->mapWithKeys(fn ($position, $questionId): array => [(int) $questionId => (int) $position])
                ->all();
            $replace = $pending['sync_mode'] === 'replace' && $report['errors'] === [];
            $desiredQuestionIds = array_keys($assignments);
            $occupiedByUntouchedQuestion = collect($existing)
                ->filter(
                    fn (int $position, int $questionId): bool => ! in_array($questionId, $desiredQuestionIds, true)
                        && in_array($position, $positions, true),
                );

            if (! $replace && $occupiedByUntouchedQuestion->isNotEmpty()) {
                $this->pushError($report, $path, 'A requested module position is already occupied by another question.');

                continue;
            }

            $temporaryOffset = max(
                [1_000_000, ...array_values($existing), ...$positions],
            ) + 1_000_000;
            $rowsToShift = $replace ? array_keys($existing) : $desiredQuestionIds;

            if ($rowsToShift !== []) {
                DB::table('question_module_question')
                    ->where('question_module_id', $moduleId)
                    ->whereIn('question_id', $rowsToShift)
                    ->increment('position', $temporaryOffset);
            }

            foreach ($assignments as $questionId => $position) {
                $now = now();
                $wasExisting = array_key_exists($questionId, $existing);
                $wasChanged = ! $wasExisting || $existing[$questionId] !== $position;

                DB::table('question_module_question')->updateOrInsert(
                    [
                        'question_module_id' => $moduleId,
                        'question_id' => $questionId,
                    ],
                    [
                        'position' => $position,
                        'created_at' => $wasExisting
                            ? DB::raw('created_at')
                            : $now,
                        'updated_at' => $now,
                    ],
                );

                if (! $wasExisting) {
                    $report['module_assignments_created']++;
                } elseif ($wasChanged) {
                    $report['module_assignments_updated']++;
                } else {
                    $report['module_assignments_unchanged']++;
                }
            }

            if ($replace) {
                $staleAssignments = DB::table('question_module_question')
                    ->where('question_module_id', $moduleId);

                if ($desiredQuestionIds !== []) {
                    $staleAssignments->whereNotIn('question_id', $desiredQuestionIds);
                }

                $report['module_assignments_deleted'] += $staleAssignments->delete();
            }
        }
    }

    /**
     * @param  array<int, mixed>  $mediaEntries
     * @param  array<string, mixed>  $report
     */
    protected function syncMedia(Question $question, array $mediaEntries, array &$report): void
    {
        $retainedMediaIds = [];

        foreach ($mediaEntries as $index => $mediaData) {
            $identity = [
                'kind' => (string) ($mediaData['kind'] ?? 'image'),
                'variant' => (string) ($mediaData['variant'] ?? 'full'),
                'sort_order' => (int) ($mediaData['sort_order'] ?? $index),
            ];
            $media = $question->media()->firstOrNew($identity);
            $wasExisting = $media->exists;

            $media->fill([
                'disk' => (string) ($mediaData['disk'] ?? config('media.default_disk')),
                'path' => (string) $mediaData['path'],
                'poster_path' => $mediaData['poster_path'] ?? null,
                'mime_type' => $mediaData['mime_type'] ?? null,
                'bytes' => isset($mediaData['bytes']) ? (int) $mediaData['bytes'] : null,
                'duration_seconds' => isset($mediaData['duration_seconds']) ? (int) $mediaData['duration_seconds'] : null,
                'width' => isset($mediaData['width']) ? (int) $mediaData['width'] : null,
                'height' => isset($mediaData['height']) ? (int) $mediaData['height'] : null,
                'metadata' => is_array($mediaData['metadata'] ?? null) ? $mediaData['metadata'] : null,
            ]);
            $wasChanged = $media->isDirty();
            $media->save();
            $retainedMediaIds[] = $media->getKey();

            if (! $wasExisting) {
                $report['media_created']++;
                $report['created_assets'][] = [
                    'question_external_id' => $question->external_id,
                    'kind' => $identity['kind'],
                    'variant' => $identity['variant'],
                    'path' => (string) $mediaData['path'],
                ];
            } elseif ($wasChanged) {
                $report['media_updated']++;
            } else {
                $report['media_unchanged']++;
            }
        }

        $staleMediaQuery = $question->media();

        if ($retainedMediaIds !== []) {
            $staleMediaQuery->whereNotIn('id', $retainedMediaIds);
        }

        $report['media_deleted'] += $staleMediaQuery->delete();
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>|null
     */
    protected function validatedPayload(ValidatorContract $validator, string $path, array &$report): ?array
    {
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->pushError($report, $path, $message);
            }

            return null;
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function pushError(array &$report, string $path, string $message): void
    {
        $report['errors'][] = [
            'path' => $path,
            'message' => $message,
        ];
    }
}
