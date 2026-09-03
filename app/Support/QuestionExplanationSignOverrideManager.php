<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationSignOverride;
use App\Models\SharedQuestionExplanationSignOverride;
use App\Models\TrafficSign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionExplanationSignOverrideManager
{
    private const SIGN_CODE_PATTERN = '/^([A-Z]{1,3})-(\d{1,3}[a-zA-Z]?[A-Z]?(?:-[A-Z])?(?:\/\d{1,3}[a-zA-Z]?)?)$/iu';

    public function __construct(
        protected PublicQuestionSignReferenceService $publicQuestionSignReferenceService,
        protected QuestionTextFormatter $questionTextFormatter,
    ) {}

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    public function syncLocal(Question $question, array $rows, ?int $actorId = null): void
    {
        $normalizedRows = $this->normalizeRows($question, $rows);

        DB::transaction(function () use ($question, $normalizedRows, $actorId): void {
            QuestionExplanationSignOverride::query()
                ->where('question_id', $question->getKey())
                ->delete();

            foreach ($normalizedRows as $row) {
                QuestionExplanationSignOverride::query()->create([
                    ...$row,
                    'question_id' => $question->getKey(),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }
        });
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    public function syncShared(Question $question, array $rows, ?int $actorId = null): void
    {
        if (! filled($question->external_id)) {
            $this->syncLocal($question, $rows, $actorId);

            return;
        }

        $normalizedRows = $this->normalizeRows($question, $rows);
        $sharedQuestions = app(SharedQuestionScopeService::class)->questionsForSharedExternalId($question);
        $this->validateRowsForSharedQuestions($normalizedRows, $sharedQuestions, $question);
        $sourceScope = SharedQuestionExplanationSignOverride::sourceScopeFor($question->source);

        DB::transaction(function () use ($question, $normalizedRows, $sourceScope, $actorId): void {
            SharedQuestionExplanationSignOverride::query()
                ->where('external_id', $question->external_id)
                ->where('source_scope', $sourceScope)
                ->delete();

            foreach ($normalizedRows as $row) {
                SharedQuestionExplanationSignOverride::query()->create([
                    ...$row,
                    'external_id' => (string) $question->external_id,
                    'source_scope' => $sourceScope,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }
        });
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return list<array{action:string,detected_code:?string,anchor_text:?string,traffic_sign_id:?int,position:int,is_active:bool}>
     */
    protected function normalizeRows(Question $question, array $rows): array
    {
        $normalizedRows = [];
        $errors = [];
        $detectedCodesInExplanation = collect(
            $this->publicQuestionSignReferenceService->codesFromText($question->explanation),
        )
            ->map(fn (string $code): string => mb_strtoupper($code))
            ->all();

        if (count($rows) > 12) {
            $errors['explanation_sign_overrides'] = 'Możesz zapisać maksymalnie 12 korekt znaków dla jednego wyjaśnienia.';
        }

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $path = "explanation_sign_overrides.{$index}";
            $action = trim((string) ($row['action'] ?? ''));
            $isActive = (bool) ($row['is_active'] ?? true);
            $detectedCode = $this->normalizeSignCode($row['detected_code'] ?? null);
            $anchorText = $this->normalizeAnchorText($row['anchor_text'] ?? null);
            $trafficSignId = isset($row['traffic_sign_id']) && $row['traffic_sign_id'] !== ''
                ? (int) $row['traffic_sign_id']
                : null;

            if ($action === '' && $detectedCode === null && $anchorText === null && $trafficSignId === null) {
                continue;
            }

            if (! in_array($action, [
                QuestionExplanationSignOverride::ACTION_HIDE,
                QuestionExplanationSignOverride::ACTION_REPLACE,
                QuestionExplanationSignOverride::ACTION_ADD,
            ], true)) {
                $errors["{$path}.action"] = 'Wybierz akcję dla korekty znaku.';

                continue;
            }

            if (in_array($action, [QuestionExplanationSignOverride::ACTION_HIDE, QuestionExplanationSignOverride::ACTION_REPLACE], true)) {
                if ($detectedCode === null) {
                    $errors["{$path}.detected_code"] = 'Podaj poprawny kod znaku z treści wyjaśnienia, np. B-20.';

                    continue;
                }

                if (! in_array(mb_strtoupper($detectedCode), $detectedCodesInExplanation, true)) {
                    $errors["{$path}.detected_code"] = 'Ten kod nie występuje w aktualnym wyjaśnieniu.';

                    continue;
                }

                $anchorText = null;
            }

            if ($action === QuestionExplanationSignOverride::ACTION_ADD) {
                $detectedCode = null;

                if ($anchorText === null) {
                    $errors["{$path}.anchor_text"] = 'Podaj fragment wyjaśnienia, po którym ma pojawić się znak.';

                    continue;
                }

                $occurrences = $this->anchorOccurrences((string) $question->explanation, $anchorText);
                if ($occurrences !== 1) {
                    $errors["{$path}.anchor_text"] = $occurrences === 0
                        ? 'Ten fragment nie występuje w aktualnym wyjaśnieniu.'
                        : 'Ten fragment występuje więcej niż raz. Wskaż dłuższy, unikalny fragment.';

                    continue;
                }
            }

            if ($action === QuestionExplanationSignOverride::ACTION_HIDE) {
                $trafficSignId = null;
            } elseif (! $this->isUsableTrafficSign($trafficSignId)) {
                $errors["{$path}.traffic_sign_id"] = 'Wybierz opublikowany znak z dostępną grafiką.';

                continue;
            }

            $normalizedRows[] = [
                'action' => $action,
                'detected_code' => $detectedCode,
                'anchor_text' => $anchorText,
                'traffic_sign_id' => $trafficSignId,
                'position' => count($normalizedRows) + 1,
                'is_active' => $isActive,
            ];
        }

        $duplicateKeys = [];
        foreach ($normalizedRows as $index => $row) {
            $key = $row['action'] === QuestionExplanationSignOverride::ACTION_ADD
                ? 'add:'.mb_strtolower((string) $row['anchor_text'])
                : 'detected:'.mb_strtoupper((string) $row['detected_code']);

            if (isset($duplicateKeys[$key])) {
                $errors["explanation_sign_overrides.{$index}"] = 'Ta korekta jest już na liście.';
            }

            $duplicateKeys[$key] = true;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalizedRows;
    }

    protected function normalizeSignCode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $code = str_replace(' ', '', trim($value));
        if ($code === '' || preg_match(self::SIGN_CODE_PATTERN, $code, $matches) !== 1) {
            return null;
        }

        return mb_strtoupper((string) $matches[1]).'-'.(string) $matches[2];
    }

    protected function normalizeAnchorText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $anchorText = trim($value);

        if ($anchorText === '' || mb_strlen($anchorText) > 255) {
            return null;
        }

        return $anchorText;
    }

    protected function anchorOccurrences(string $explanation, string $anchorText): int
    {
        if ($explanation === '' || $anchorText === '') {
            return 0;
        }

        $html = $this->questionTextFormatter->richHtml($explanation);
        $parts = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return 0;
        }

        return collect($parts)
            ->reject(fn (string $part): bool => $part === '' || str_starts_with($part, '<'))
            ->sum(fn (string $part): int => substr_count(
                mb_strtolower(html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                mb_strtolower($anchorText),
            ));
    }

    protected function isUsableTrafficSign(?int $trafficSignId): bool
    {
        if ($trafficSignId === null) {
            return false;
        }

        $sign = TrafficSign::query()
            ->published()
            ->find($trafficSignId);

        if (! $sign) {
            return false;
        }

        return filled($this->publicQuestionSignReferenceService->cardForTrafficSign($sign)['image_url'] ?? null);
    }

    /**
     * @param  list<array{action:string,detected_code:?string,anchor_text:?string,traffic_sign_id:?int,position:int,is_active:bool}>  $rows
     * @param  iterable<int, Question>  $questions
     */
    protected function validateRowsForSharedQuestions(iterable $rows, iterable $questions, Question $sourceQuestion): void
    {
        $rows = array_values(is_array($rows) ? $rows : iterator_to_array($rows));

        if ($rows === []) {
            return;
        }

        foreach ($questions as $question) {
            if (! $question instanceof Question || $question->is($sourceQuestion)) {
                continue;
            }

            try {
                $this->normalizeRows($question, $rows);
            } catch (ValidationException) {
                throw ValidationException::withMessages([
                    'explanation_sign_overrides' => sprintf(
                        'Korekta wspólna nie pasuje do pytania ID %d. Zapisz ją jako „Tylko to pytanie” albo użyj kodu i fragmentu obecnego w każdej kopii pytania.',
                        $question->getKey(),
                    ),
                ]);
            }
        }
    }
}
