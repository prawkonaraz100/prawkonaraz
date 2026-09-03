<?php

namespace App\Filament\Resources\Questions\Pages\Concerns;

use App\Models\Question;
use App\Models\QuestionExplanationSignOverride;
use App\Models\SharedQuestionExplanationSignOverride;
use App\Support\AuditLogService;
use App\Support\QuestionExplanationSignOverrideManager;
use App\Support\QuestionExplanationSignOverrideResolver;
use App\Support\SharedQuestionScopeService;
use Illuminate\Support\Facades\Auth;

trait InteractsWithQuestionExplanationSignOverrides
{
    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $questionExplanationSignOverridesData = null;

    protected string $questionExplanationSignOverridesApplyScope = 'single';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractQuestionExplanationSignOverrideData(array $data): array
    {
        $this->questionExplanationSignOverridesData = is_array($data['explanation_sign_overrides'] ?? null)
            ? array_values(array_filter($data['explanation_sign_overrides'], 'is_array'))
            : null;
        $this->questionExplanationSignOverridesApplyScope = in_array(
            $data['explanation_sign_overrides_apply_scope'] ?? 'single',
            ['single', 'shared_external_id'],
            true,
        )
            ? (string) $data['explanation_sign_overrides_apply_scope']
            : 'single';

        unset($data['explanation_sign_overrides']);
        unset($data['explanation_sign_overrides_apply_scope']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillQuestionExplanationSignOverrideData(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Question) {
            return $data;
        }

        $editable = app(QuestionExplanationSignOverrideResolver::class)->editableForQuestion($record);

        $data['explanation_sign_overrides'] = $editable['overrides']
            ->map(fn (QuestionExplanationSignOverride|SharedQuestionExplanationSignOverride $override): array => [
                'action' => $override->action,
                'detected_code' => $override->detected_code,
                'anchor_text' => $override->anchor_text,
                'traffic_sign_id' => $override->traffic_sign_id,
                'is_active' => $override->is_active,
            ])
            ->values()
            ->all();
        $data['explanation_sign_overrides_apply_scope'] = $editable['scope'];

        return $data;
    }

    protected function syncQuestionExplanationSignOverrides(): void
    {
        if ($this->questionExplanationSignOverridesData === null) {
            return;
        }

        $record = $this->getRecord();

        if (! $record instanceof Question) {
            return;
        }

        $applyScope = $this->resolveQuestionExplanationSignOverridesApplyScope($record);
        $rows = $this->questionExplanationSignOverridesData;
        $actor = Auth::user();
        $actorId = $actor?->getKey();

        if ($applyScope === 'shared_external_id') {
            $questions = app(SharedQuestionScopeService::class)->questionsForSharedExternalId($record);

            app(QuestionExplanationSignOverrideManager::class)->syncShared($record, $rows, $actorId);

            app(AuditLogService::class)->record(
                'admin.question.explanation_sign_overrides_updated',
                'question',
                $record->getKey(),
                $actor,
                [
                    'source' => 'question_edit_form',
                    'question_id' => $record->getKey(),
                    'apply_scope' => $applyScope,
                    'shared_external_id' => $record->external_id,
                    'source_scope' => SharedQuestionExplanationSignOverride::sourceScopeFor($record->source),
                    'affected_question_ids' => $questions->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all(),
                    'override_count' => count($rows),
                ],
            );

            return;
        }

        app(QuestionExplanationSignOverrideManager::class)->syncLocal($record, $rows, $actorId);

        app(AuditLogService::class)->record(
            'admin.question.explanation_sign_overrides_updated',
            'question',
            $record->getKey(),
            $actor,
            [
                'source' => 'question_edit_form',
                'question_id' => $record->getKey(),
                'apply_scope' => 'single',
                'shared_external_id' => $record->external_id,
                'affected_question_ids' => [$record->getKey()],
                'override_count' => count($rows),
            ],
        );
    }

    protected function resolveQuestionExplanationSignOverridesApplyScope(Question $question): string
    {
        if (! filled($question->external_id)) {
            return 'single';
        }

        return $this->questionExplanationSignOverridesApplyScope === 'shared_external_id'
            ? 'shared_external_id'
            : 'single';
    }
}
