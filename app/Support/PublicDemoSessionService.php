<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublicDemoSessionService
{
    protected const SESSION_KEY = 'public_demo.player_demo';

    public function __construct(
        protected PublicDemoQuestionSetService $questionSetService,
        protected PublicDemoQuestionPayloadBuilder $questionPayloadBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function state(Request $request): array
    {
        $state = $request->session()->get(self::SESSION_KEY);
        $questionIds = $this->questionSetService->questionIds();

        if (! is_array($state) || $this->stateNeedsReset($state, $questionIds)) {
            $state = $this->freshState($questionIds);
            $request->session()->put(self::SESSION_KEY, $state);
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function restart(Request $request): array
    {
        $state = $this->freshState($this->questionSetService->questionIds());
        $request->session()->put(self::SESSION_KEY, $state);

        return $state;
    }

    /**
     * Build a read-only state for the public locked preview.
     *
     * @return array<string, mixed>
     */
    public function previewState(): array
    {
        return $this->freshState($this->questionSetService->questionIds());
    }

    public function currentQuestion(Request $request): ?Question
    {
        $state = $this->state($request);

        if ((bool) ($state['completed'] ?? false)) {
            return null;
        }

        $questionId = $this->currentQuestionId($state);

        return $questionId ? $this->findDemoQuestion($questionId) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function recordAnswer(
        Request $request,
        int $questionId,
        string $selectedAnswer,
        ?int $responseTimeMs = null,
    ): array {
        $state = $this->state($request);

        if ((bool) ($state['completed'] ?? false)) {
            throw new HttpException(409, 'Demo zostalo juz ukonczone.');
        }

        $currentQuestionId = $this->currentQuestionId($state);

        if ($currentQuestionId !== $questionId) {
            throw new HttpException(409, 'To pytanie nie jest juz aktywne w tej sesji demo.');
        }

        $question = $this->findDemoQuestion($questionId);

        if (! $question instanceof Question) {
            throw new HttpException(404, 'Nie znaleziono pytania demo.');
        }

        $normalizedAnswer = Str::lower($selectedAnswer);
        $correctAnswer = Str::lower((string) $question->correct_answer);
        $isCorrect = $normalizedAnswer === $correctAnswer;
        $answers = Arr::get($state, 'answers', []);
        $answers[(string) $questionId] = [
            'question_id' => $questionId,
            'selected_answer' => Str::upper($normalizedAnswer),
            'answer_kind' => 'choice',
            'is_correct' => $isCorrect,
            'response_time_ms' => $responseTimeMs,
            'answered_at' => now()->toIso8601String(),
        ];

        $questionIds = $this->questionIdsFromState($state);
        $nextIndex = ((int) ($state['current_index'] ?? 0)) + 1;
        $completed = $nextIndex >= count($questionIds);

        $state['answers'] = $answers;
        $state['current_index'] = $completed ? max(count($questionIds) - 1, 0) : $nextIndex;
        $state['completed'] = $completed;
        $state['completed_at'] = $completed ? now()->toIso8601String() : null;

        $request->session()->put(self::SESSION_KEY, $state);

        $nextQuestion = $completed ? null : $this->findDemoQuestion($questionIds[$nextIndex] ?? null);

        $revealPayload = $this->questionPayloadBuilder->forQuestion($question, includeReveal: true);

        return [
            'answer' => [
                'question_id' => $question->getKey(),
                'selected_answer' => Str::upper($normalizedAnswer),
                'answer_kind' => 'choice',
                'selected_answer_text' => $this->questionPayloadBuilder->optionText($question, $normalizedAnswer),
                'is_correct' => $isCorrect,
                'response_time_ms' => $responseTimeMs,
                'correct_answer' => Str::upper($correctAnswer),
                'correct_answer_text' => $this->questionPayloadBuilder->optionText($question, $correctAnswer),
                'explanation' => $question->explanation,
                'explanation_asset' => $revealPayload['explanation_asset'],
                'explanation_annotations' => $revealPayload['explanation_annotations'],
            ],
            'state' => $state,
            'next_question' => $nextQuestion instanceof Question
                ? $this->questionPayloadBuilder->forQuestion(
                    $nextQuestion,
                    includeReveal: true,
                    publicExplanationUrl: $this->questionPayloadBuilder->publicExplanationUrlFor($nextQuestion),
                )
                : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $submittedAnswers
     * @return array<string, mixed>
     */
    public function complete(Request $request, array $submittedAnswers = []): array
    {
        $state = $this->state($request);

        if ($submittedAnswers !== []) {
            $state['answers'] = $this->normalizeSubmittedAnswers($state, $submittedAnswers);
        }

        $state['completed'] = true;
        $state['current_index'] = max(count($this->questionIdsFromState($state)) - 1, 0);
        $state['completed_at'] = now()->toIso8601String();

        $request->session()->put(self::SESSION_KEY, $state);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(array $state): array
    {
        $questionIds = $this->questionIdsFromState($state);
        $answers = collect(Arr::get($state, 'answers', []));
        $answered = $answers->count();
        $correct = $answers
            ->filter(fn (mixed $answer): bool => (bool) data_get($answer, 'is_correct'))
            ->count();
        $total = count($questionIds);

        return [
            'label' => (string) config('public_demo.player_demo.label', 'player-demo-2026'),
            'category_code' => $this->questionSetService->categoryCode(),
            'position' => $total === 0 || (bool) ($state['completed'] ?? false)
                ? $total
                : min(((int) ($state['current_index'] ?? 0)) + 1, $total),
            'answered' => $answered,
            'correct' => $correct,
            'remaining' => max($total - $answered, 0),
            'total' => $total,
            'planned_total' => $this->questionSetService->questionLimit(),
            'score_percent' => $answered > 0 ? round(($correct / $answered) * 100, 1) : null,
            'completed' => (bool) ($state['completed'] ?? false),
            'started_at' => $state['started_at'] ?? null,
            'completed_at' => $state['completed_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, array<string, mixed>>
     */
    public function results(array $state): array
    {
        $questionIds = $this->questionIdsFromState($state);
        $answers = collect(Arr::get($state, 'answers', []))
            ->keyBy(fn (mixed $answer, mixed $questionId): string => (string) data_get($answer, 'question_id', $questionId));

        if ($answers->isEmpty()) {
            return [];
        }

        $questions = Question::query()
            ->with(['media', 'questionTopic', 'referenceExplanationAsset', 'explanationAnnotations'])
            ->whereIn('id', $answers->keys()->map(fn (mixed $id): int => (int) $id)->all())
            ->get()
            ->keyBy('id');

        return collect($questionIds)
            ->filter(fn (int $questionId): bool => $answers->has((string) $questionId))
            ->map(function (int $questionId, int $position) use ($answers, $questions): ?array {
                $question = $questions->get($questionId);

                if (! $question instanceof Question) {
                    return null;
                }

                $answer = $answers->get((string) $questionId);
                $questionPayload = $this->questionPayloadBuilder->forQuestion($question, includeReveal: true);
                $selectedAnswer = data_get($answer, 'selected_answer');
                $correctAnswer = data_get($answer, 'correct_answer', $questionPayload['correct_answer']);

                return [
                    'id' => $question->getKey(),
                    'external_id' => $question->external_id,
                    'shared_explanation_has_conflict' => false,
                    'sequence_number' => $position + 1,
                    'prompt' => $question->prompt,
                    'structure_scope' => $questionPayload['structure_scope'],
                    'points' => $question->points,
                    'explanation' => $questionPayload['explanation'],
                    'correct_answer' => $correctAnswer ? Str::upper((string) $correctAnswer) : null,
                    'correct_answer_text' => $this->questionPayloadBuilder->optionText($question, (string) $correctAnswer),
                    'selected_answer' => $selectedAnswer ? Str::upper((string) $selectedAnswer) : null,
                    'answer_kind' => data_get($answer, 'answer_kind', 'choice'),
                    'selected_answer_text' => $this->questionPayloadBuilder->optionText($question, (string) $selectedAnswer),
                    'is_correct' => data_get($answer, 'is_correct'),
                    'response_time_ms' => data_get($answer, 'response_time_ms'),
                    'topic' => $questionPayload['topic'],
                    'media' => $questionPayload['media'],
                    'sign_language_assets' => [],
                    'explanation_asset' => $questionPayload['explanation_asset'],
                    'explanation_annotations' => $questionPayload['explanation_annotations'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array<string, mixed>>  $submittedAnswers
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeSubmittedAnswers(array $state, array $submittedAnswers): array
    {
        $questionIds = $this->questionIdsFromState($state);
        $allowedQuestionIds = array_flip($questionIds);
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->get(['id', 'correct_answer'])
            ->keyBy('id');

        return collect($submittedAnswers)
            ->map(function (array $answer) use ($allowedQuestionIds, $questions): ?array {
                $questionId = (int) ($answer['question_id'] ?? 0);

                if (! isset($allowedQuestionIds[$questionId])) {
                    return null;
                }

                $question = $questions->get($questionId);

                if (! $question instanceof Question) {
                    return null;
                }

                $selectedAnswer = Str::lower((string) ($answer['selected_answer'] ?? ''));

                if (! in_array($selectedAnswer, ['a', 'b', 'c'], true)) {
                    return null;
                }

                $correctAnswer = Str::lower((string) $question->correct_answer);

                return [
                    'question_id' => $questionId,
                    'selected_answer' => Str::upper($selectedAnswer),
                    'answer_kind' => 'choice',
                    'is_correct' => $selectedAnswer === $correctAnswer,
                    'response_time_ms' => isset($answer['response_time_ms'])
                        ? (int) $answer['response_time_ms']
                        : null,
                    'answered_at' => now()->toIso8601String(),
                ];
            })
            ->filter()
            ->keyBy(fn (array $answer): string => (string) $answer['question_id'])
            ->all();
    }

    /**
     * @param  list<int>  $questionIds
     * @return array<string, mixed>
     */
    protected function freshState(array $questionIds): array
    {
        return [
            'question_ids' => $questionIds,
            'current_index' => 0,
            'answers' => [],
            'completed' => false,
            'started_at' => now()->toIso8601String(),
            'completed_at' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<int>  $questionIds
     */
    protected function stateNeedsReset(array $state, array $questionIds): bool
    {
        return $this->questionIdsFromState($state) !== $questionIds;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<int>
     */
    protected function questionIdsFromState(array $state): array
    {
        return collect($state['question_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function currentQuestionId(array $state): ?int
    {
        $questionIds = $this->questionIdsFromState($state);
        $currentIndex = (int) ($state['current_index'] ?? 0);

        return $questionIds[$currentIndex] ?? null;
    }

    protected function findDemoQuestion(?int $questionId): ?Question
    {
        if (! $questionId) {
            return null;
        }

        $questionIds = $this->questionSetService->questionIds();

        if (! in_array($questionId, $questionIds, true)) {
            return null;
        }

        return Question::query()
            ->with(['media', 'questionTopic', 'referenceExplanationAsset', 'explanationAnnotations'])
            ->whereKey($questionId)
            ->first();
    }
}
