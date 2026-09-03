<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserTopicCompletionRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudyTopicCompletionRecordService
{
    public const RECORD_STATE_NONE = 'none';

    public const RECORD_STATE_FIRST = 'first_record';

    public const RECORD_STATE_IMPROVED = 'improved_record';

    /**
     * @return array<string, mixed>
     */
    public function syncFromCompletedSession(StudySession $studySession): array
    {
        $qualification = $this->completionCandidate($studySession);

        if (! $qualification['qualified']) {
            return [
                'qualified' => false,
                'record_state' => self::RECORD_STATE_NONE,
            ];
        }

        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $existingResult = is_array($payload['topic_completion_record'] ?? null)
            ? $payload['topic_completion_record']
            : null;

        if ((int) ($existingResult['study_session_id'] ?? 0) === (int) $studySession->getKey()) {
            return $existingResult;
        }

        return DB::transaction(function () use ($qualification, $studySession): array {
            $record = UserTopicCompletionRecord::query()
                ->where('user_id', $studySession->user_id)
                ->where('license_category_id', $studySession->license_category_id)
                ->where('question_topic_id', $qualification['question_topic_id'])
                ->where('question_scope', $qualification['question_scope'])
                ->lockForUpdate()
                ->first();

            if ($record && (int) $record->last_study_session_id === (int) $studySession->getKey()) {
                $result = $this->resultFromRecord($studySession, $record, self::RECORD_STATE_NONE);
                $this->storeSessionResult($studySession, $result);

                return $result;
            }

            $previousBestDuration = $record?->best_duration_seconds;
            $isPerfect = (int) $studySession->correct_answers_count === (int) $studySession->total_questions_count;
            $recordState = self::RECORD_STATE_NONE;
            $savedDurationSeconds = null;

            if ($isPerfect && $previousBestDuration === null) {
                $recordState = self::RECORD_STATE_FIRST;
            } elseif ($isPerfect && $qualification['duration_seconds'] < (int) $previousBestDuration) {
                $recordState = self::RECORD_STATE_IMPROVED;
                $savedDurationSeconds = (int) $previousBestDuration - (int) $qualification['duration_seconds'];
            }

            if (! $record) {
                $record = new UserTopicCompletionRecord([
                    'user_id' => $studySession->user_id,
                    'license_category_id' => $studySession->license_category_id,
                    'question_topic_id' => $qualification['question_topic_id'],
                    'question_scope' => $qualification['question_scope'],
                ]);
            }

            $record->forceFill([
                'last_study_session_id' => $studySession->getKey(),
                'last_duration_seconds' => $qualification['duration_seconds'],
                'last_score_percent' => $studySession->score_percent,
                'last_completed_at' => $studySession->completed_at,
                'last_ui_shell' => $qualification['ui_shell'],
                'last_questions_count' => $studySession->total_questions_count,
                'last_question_ids_hash' => $qualification['question_ids_hash'],
                'completion_count' => (int) $record->completion_count + 1,
                'perfect_completion_count' => (int) $record->perfect_completion_count + ($isPerfect ? 1 : 0),
                'first_completed_at' => $record->first_completed_at ?? $studySession->completed_at,
            ]);

            if (in_array($recordState, [self::RECORD_STATE_FIRST, self::RECORD_STATE_IMPROVED], true)) {
                $record->forceFill([
                    'best_study_session_id' => $studySession->getKey(),
                    'best_duration_seconds' => $qualification['duration_seconds'],
                    'best_completed_at' => $studySession->completed_at,
                    'best_ui_shell' => $qualification['ui_shell'],
                    'best_questions_count' => $studySession->total_questions_count,
                    'best_question_ids_hash' => $qualification['question_ids_hash'],
                ]);
            }

            $record->save();

            $result = $this->resultFromRecord(
                $studySession,
                $record,
                $recordState,
                $savedDurationSeconds,
                $previousBestDuration !== null ? (int) $previousBestDuration : null,
            );
            $this->storeSessionResult($studySession, $result);

            return $result;
        }, 3);
    }

    /**
     * @param  Collection<int, array{label:string,options:array<int, array{id:int,key:string,label:string,questions_count:int,counts:array<string,int>}>}>  $topicGroups
     * @return array<string, mixed>|null
     */
    public function overviewForSession(StudySession $studySession, User $user, Collection $topicGroups): ?array
    {
        if (
            $studySession->mode !== StudySessionManager::MODE_LEARN
            || ! $studySession->license_category_id
            || ! $studySession->licenseCategory
        ) {
            return null;
        }

        $topicOptions = $topicGroups
            ->flatMap(fn (array $group): array => collect($group['options'] ?? [])
                ->map(fn (array $option): array => [
                    ...$option,
                    'bucket_label' => (string) ($group['label'] ?? ''),
                ])
                ->all())
            ->values();

        if ($topicOptions->isEmpty()) {
            return null;
        }

        $questionScope = $this->normalizedQuestionScope(
            data_get($studySession->payload, 'filters.question_scope', 'all'),
        );
        $topicIds = $topicOptions
            ->pluck('id')
            ->map(fn (mixed $topicId): int => (int) $topicId)
            ->filter()
            ->values();

        $questionCounts = $this->questionCountsForScope(
            $studySession->licenseCategory,
            $topicIds,
            $questionScope,
        );
        $records = UserTopicCompletionRecord::query()
            ->where('user_id', $user->getKey())
            ->where('license_category_id', $studySession->license_category_id)
            ->where('question_scope', $questionScope)
            ->whereIn('question_topic_id', $topicIds->all())
            ->get()
            ->keyBy('question_topic_id');
        $allScopeRecords = $questionScope === 'all'
            ? $records
            : UserTopicCompletionRecord::query()
                ->where('user_id', $user->getKey())
                ->where('license_category_id', $studySession->license_category_id)
                ->where('question_scope', 'all')
                ->whereIn('question_topic_id', $topicIds->all())
                ->get()
                ->keyBy('question_topic_id');
        $currentTopicPoolSnapshots = $this->currentTopicPoolSnapshots(
            $studySession->licenseCategory,
            $topicIds,
        );
        $currentTopicId = $this->topicIdFromSession($studySession);
        $sessionResult = $this->sessionResult($studySession);

        $items = $topicOptions
            ->map(function (array $topic) use ($currentTopicId, $questionCounts, $records, $sessionResult): ?array {
                $topicId = (int) ($topic['id'] ?? 0);
                $questionsCount = (int) ($questionCounts->get($topicId) ?? 0);

                if ($topicId <= 0 || $questionsCount <= 0) {
                    return null;
                }

                /** @var UserTopicCompletionRecord|null $record */
                $record = $records->get($topicId);
                $isCurrentTopic = $currentTopicId !== null && $topicId === $currentTopicId;
                $recordState = $isCurrentTopic
                    ? (string) ($sessionResult['record_state'] ?? self::RECORD_STATE_NONE)
                    : self::RECORD_STATE_NONE;

                return [
                    'topic_id' => $topicId,
                    'key' => (string) ($topic['key'] ?? ''),
                    'label' => (string) ($topic['label'] ?? ''),
                    'bucket_label' => (string) ($topic['bucket_label'] ?? ''),
                    'questions_count' => $questionsCount,
                    'is_current_topic' => $isCurrentTopic,
                    'last_duration_seconds' => $record?->last_duration_seconds,
                    'last_score_percent' => $record?->last_score_percent !== null
                        ? (float) $record->last_score_percent
                        : null,
                    'best_duration_seconds' => $record?->best_duration_seconds,
                    'completion_count' => $record?->completion_count ?? 0,
                    'perfect_completion_count' => $record?->perfect_completion_count ?? 0,
                    'record_state' => $recordState,
                    'saved_duration_seconds' => $isCurrentTopic
                        ? ($sessionResult['saved_duration_seconds'] ?? null)
                        : null,
                    'previous_best_duration_seconds' => $isCurrentTopic
                        ? ($sessionResult['previous_best_duration_seconds'] ?? null)
                        : null,
                ];
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        $masteredTopicIds = $topicOptions
            ->filter(function (array $topic) use ($allScopeRecords, $currentTopicPoolSnapshots): bool {
                $topicId = (int) ($topic['id'] ?? 0);

                if ($topicId <= 0) {
                    return false;
                }

                /** @var UserTopicCompletionRecord|null $record */
                $record = $allScopeRecords->get($topicId);
                $currentPool = $currentTopicPoolSnapshots->get($topicId);

                return $record !== null
                    && $record->best_duration_seconds !== null
                    && is_array($currentPool)
                    && (int) $record->best_questions_count === (int) ($currentPool['questions_count'] ?? 0)
                    && hash_equals(
                        (string) ($currentPool['question_ids_hash'] ?? ''),
                        (string) $record->best_question_ids_hash,
                    );
            })
            ->pluck('id')
            ->map(fn (mixed $topicId): int => (int) $topicId)
            ->values();
        $currentTopicIndex = $currentTopicId !== null
            ? $topicOptions->search(fn (array $topic): bool => (int) ($topic['id'] ?? 0) === $currentTopicId)
            : false;
        $currentPosition = $currentTopicIndex === false ? null : (int) $currentTopicIndex + 1;

        return [
            'category_id' => (int) $studySession->license_category_id,
            'question_scope' => $questionScope,
            'current_topic_id' => $currentTopicId,
            'items' => $items->all(),
            'learning_path' => [
                'total_topics' => $topicOptions->count(),
                'mastered_topics' => $masteredTopicIds->count(),
                'mastered_topic_ids' => $masteredTopicIds->all(),
                'current_position' => $currentPosition,
                'remaining_after_current' => $currentPosition !== null
                    ? max($topicOptions->count() - $currentPosition, 0)
                    : null,
            ],
        ];
    }

    /**
     * @return array{qualified:bool,question_topic_id?:int,question_scope?:string,duration_seconds?:int,question_ids_hash?:string,ui_shell?:string|null}
     */
    protected function completionCandidate(StudySession $studySession): array
    {
        if ($this->isQuestionModuleSession($studySession)) {
            return ['qualified' => false];
        }

        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
        $topicId = isset($filters['question_topic_id']) && $filters['question_topic_id']
            ? (int) $filters['question_topic_id']
            : null;

        if (
            $studySession->status !== 'completed'
            || $studySession->mode !== StudySessionManager::MODE_LEARN
            || ! $studySession->license_category_id
            || ! $topicId
            || ! $studySession->started_at
            || ! $studySession->completed_at
            || (int) $studySession->total_questions_count <= 0
        ) {
            return ['qualified' => false];
        }

        $answeredCount = $studySession->relationLoaded('answers')
            ? $studySession->answers->count()
            : $studySession->answers()->count();

        if ($answeredCount < (int) $studySession->total_questions_count) {
            return ['qualified' => false];
        }

        $questionScope = $this->normalizedQuestionScope($filters['question_scope'] ?? 'all');

        if (! $this->coversCurrentFullTopicPool($studySession, $topicId, $questionScope)) {
            return ['qualified' => false];
        }

        return [
            'qualified' => true,
            'question_topic_id' => $topicId,
            'question_scope' => $questionScope,
            'duration_seconds' => max((int) round($studySession->started_at->diffInSeconds($studySession->completed_at)), 0),
            'question_ids_hash' => $this->questionIdsHash($studySession),
            'ui_shell' => $this->normalizedUiShell($payload['ui_shell'] ?? null),
        ];
    }

    protected function isQuestionModuleSession(StudySession $studySession): bool
    {
        return $studySession->question_collection_id !== null
            || $studySession->question_module_id !== null
            || data_get($studySession->payload, 'context.type') === 'question_module';
    }

    protected function storeSessionResult(StudySession $studySession, array $result): void
    {
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $payload['topic_completion_record'] = [
            ...$result,
            'synced_at' => now()->toIso8601String(),
        ];

        $studySession->forceFill(['payload' => $payload])->save();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function sessionResult(StudySession $studySession): ?array
    {
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $result = is_array($payload['topic_completion_record'] ?? null)
            ? $payload['topic_completion_record']
            : null;

        if ((int) ($result['study_session_id'] ?? 0) !== (int) $studySession->getKey()) {
            return null;
        }

        return $result;
    }

    protected function resultFromRecord(
        StudySession $studySession,
        UserTopicCompletionRecord $record,
        string $recordState,
        ?int $savedDurationSeconds = null,
        ?int $previousBestDurationSeconds = null,
    ): array {
        return [
            'qualified' => true,
            'record_id' => $record->getKey(),
            'study_session_id' => $studySession->getKey(),
            'question_topic_id' => (int) $record->question_topic_id,
            'question_scope' => (string) $record->question_scope,
            'duration_seconds' => $record->last_duration_seconds,
            'record_state' => $recordState,
            'saved_duration_seconds' => $savedDurationSeconds,
            'previous_best_duration_seconds' => $previousBestDurationSeconds,
            'best_duration_seconds' => $record->best_duration_seconds,
        ];
    }

    /**
     * @param  Collection<int, int>  $topicIds
     * @return Collection<int, int>
     */
    protected function questionCountsForScope(LicenseCategory $category, Collection $topicIds, string $questionScope): Collection
    {
        if ($topicIds->isEmpty()) {
            return collect();
        }

        $query = Question::query()
            ->selectRaw('question_topic_id, count(*) as aggregate')
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereIn('question_topic_id', $topicIds->all())
            ->groupBy('question_topic_id');

        $this->applyQuestionScopeFilter($query, $questionScope);

        return $query
            ->pluck('aggregate', 'question_topic_id')
            ->mapWithKeys(fn (mixed $count, mixed $topicId): array => [(int) $topicId => (int) $count]);
    }

    /**
     * @param  Collection<int, int>  $topicIds
     * @return Collection<int, array{questions_count:int,question_ids_hash:string}>
     */
    protected function currentTopicPoolSnapshots(LicenseCategory $category, Collection $topicIds): Collection
    {
        if ($topicIds->isEmpty()) {
            return collect();
        }

        return Question::query()
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereIn('question_topic_id', $topicIds->all())
            ->get(['id', 'question_topic_id'])
            ->groupBy('question_topic_id')
            ->mapWithKeys(function (Collection $questions, int|string $topicId): array {
                $questionIds = $questions
                    ->pluck('id')
                    ->map(fn (mixed $questionId): int => (int) $questionId)
                    ->sort()
                    ->values();

                return [
                    (int) $topicId => [
                        'questions_count' => $questionIds->count(),
                        'question_ids_hash' => hash('sha256', $questionIds->implode(',')),
                    ],
                ];
            });
    }

    protected function applyQuestionScopeFilter(Builder $query, string $questionScope): void
    {
        if ($questionScope === 'specialist') {
            $query->where('metadata->structure_scope', 'SPECJALISTYCZNY');

            return;
        }

        if ($questionScope === 'basic') {
            $query->where(function (Builder $scopeQuery): void {
                $scopeQuery
                    ->whereNull('metadata->structure_scope')
                    ->orWhere('metadata->structure_scope', '!=', 'SPECJALISTYCZNY');
            });
        }
    }

    protected function coversCurrentFullTopicPool(
        StudySession $studySession,
        int $topicId,
        string $questionScope,
    ): bool {
        $sessionQuestionIds = $studySession->questionIds()
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->filter()
            ->sort()
            ->values();

        if ($sessionQuestionIds->isEmpty()) {
            return false;
        }

        $query = Question::query()
            ->where('license_category_id', $studySession->license_category_id)
            ->where('is_active', true)
            ->readyForDelivery()
            ->where('question_topic_id', $topicId);

        $this->applyQuestionScopeFilter($query, $questionScope);

        $topicQuestionIds = $query
            ->pluck('id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->sort()
            ->values();

        if ($topicQuestionIds->isEmpty() || $topicQuestionIds->count() !== $sessionQuestionIds->count()) {
            return false;
        }

        return $topicQuestionIds->all() === $sessionQuestionIds->all();
    }

    protected function questionIdsHash(StudySession $studySession): string
    {
        return hash(
            'sha256',
            $studySession->questionIds()
                ->sort()
                ->values()
                ->implode(','),
        );
    }

    protected function topicIdFromSession(StudySession $studySession): ?int
    {
        $topicId = data_get($studySession->payload, 'filters.question_topic_id');

        return $topicId ? (int) $topicId : null;
    }

    protected function normalizedQuestionScope(mixed $questionScope): string
    {
        $questionScope = (string) $questionScope;

        return in_array($questionScope, ['all', 'basic', 'specialist'], true)
            ? $questionScope
            : 'all';
    }

    protected function normalizedUiShell(mixed $uiShell): ?string
    {
        $uiShell = strtolower(trim((string) $uiShell));

        return in_array($uiShell, [StudySessionManager::UI_SHELL_EXAM_LIKE, StudySessionManager::UI_SHELL_ZEN], true)
            ? $uiShell
            : null;
    }
}
