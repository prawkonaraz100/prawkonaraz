<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionAnswerDailyStat;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PublicQuestionAnswerStatsService
{
    /**
     * @param  list<array<string, mixed>>  $options
     * @param  iterable<Question>  $relatedQuestions
     * @return array<string, mixed>
     */
    public function forQuestion(Question $question, array $options, iterable $relatedQuestions = []): array
    {
        if ($question->question_type !== 'boolean') {
            return $this->unavailable('unsupported_type');
        }

        $visibleOptionKeys = collect($options)
            ->pluck('key')
            ->map(fn (mixed $key): string => (string) $key)
            ->filter(fn (string $key): bool => in_array($key, ['a', 'b'], true))
            ->values();

        if ($visibleOptionKeys->count() !== 2) {
            return $this->unavailable('unsupported_options');
        }

        $questionIds = $this->compatibleQuestionIds($question, $relatedQuestions);
        $latestStatsDate = QuestionAnswerDailyStat::query()
            ->whereIn('question_id', $questionIds)
            ->whereIn('selected_answer', $visibleOptionKeys->all())
            ->max('stats_date');

        if (! $latestStatsDate) {
            return $this->unavailable('no_data');
        }

        $windowDays = max((int) config('study.question_analytics_window_days', 365), 1);
        $minSample = max((int) config('study.public_question_answer_stats_min_sample', 30), 1);
        $cacheKey = sprintf(
            'public-question-answer-stats:v2:%d:%s:%s:%d:%d',
            $question->getKey(),
            sha1(implode(',', $questionIds)),
            Carbon::parse((string) $latestStatsDate)->toDateString(),
            $windowDays,
            $minSample,
        );

        if (app()->environment('testing')) {
            return $this->buildPayload($question, $options, $questionIds, (string) $latestStatsDate, $windowDays, $minSample);
        }

        return Cache::remember(
            $cacheKey,
            now()->addHours(max((int) config('study.public_question_answer_stats_cache_hours', 30), 1)),
            fn (): array => $this->buildPayload($question, $options, $questionIds, (string) $latestStatsDate, $windowDays, $minSample),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @param  list<int>  $questionIds
     * @return array<string, mixed>
     */
    protected function buildPayload(
        Question $question,
        array $options,
        array $questionIds,
        string $latestStatsDate,
        int $windowDays,
        int $minSample,
    ): array {
        $latestDate = Carbon::parse($latestStatsDate)->startOfDay();
        $windowStart = $latestDate->copy()->subDays($windowDays - 1)->startOfDay();
        $visibleOptions = collect($options)
            ->filter(fn (array $option): bool => in_array((string) ($option['key'] ?? ''), ['a', 'b'], true))
            ->values();
        $visibleOptionKeys = $visibleOptions
            ->pluck('key')
            ->map(fn (mixed $key): string => (string) $key)
            ->all();
        $answerCounts = QuestionAnswerDailyStat::query()
            ->whereIn('question_id', $questionIds)
            ->whereIn('selected_answer', $visibleOptionKeys)
            ->where('stats_date', '>=', $windowStart)
            ->where('stats_date', '<=', $latestDate)
            ->selectRaw('selected_answer, SUM(answers_count) as answers_count, SUM(users_count) as user_days_count')
            ->groupBy('selected_answer')
            ->get()
            ->keyBy('selected_answer');
        $totalAnswers = (int) $answerCounts->sum(fn (QuestionAnswerDailyStat $row): int => (int) $row->answers_count);

        if ($totalAnswers < $minSample) {
            return [
                ...$this->unavailable('low_sample'),
                'sample_count' => $totalAnswers,
                'min_sample' => $minSample,
            ];
        }

        $items = $visibleOptions
            ->map(function (array $option) use ($answerCounts): array {
                $key = (string) ($option['key'] ?? '');
                $count = (int) ($answerCounts->get($key)?->answers_count ?? 0);

                return [
                    'key' => $key,
                    'label' => (string) ($option['label'] ?? strtoupper($key)),
                    'count' => $count,
                    'percent' => 0,
                    'is_correct' => (bool) ($option['is_correct'] ?? false),
                ];
            })
            ->values();
        $items = $this->withStablePercentages($items, $totalAnswers);
        $correctItem = $items->first(fn (array $item): bool => (bool) $item['is_correct']);
        $correctCount = is_array($correctItem) ? (int) $correctItem['count'] : 0;
        $incorrectPercent = $totalAnswers > 0 ? round((($totalAnswers - $correctCount) / $totalAnswers) * 100, 1) : 0.0;
        $difficulty = $this->difficultyForIncorrectPercent($incorrectPercent);

        return [
            'available' => true,
            'reason' => null,
            'question_type' => 'boolean',
            'sample_count' => $totalAnswers,
            'user_days_count' => (int) $answerCounts->sum(fn (QuestionAnswerDailyStat $row): int => (int) ($row->user_days_count ?? 0)),
            'question_ids' => $questionIds,
            'min_sample' => $minSample,
            'window_days' => $windowDays,
            'window_label' => "Ostatnie {$windowDays} dni",
            'updated_at' => $latestDate,
            'updated_label' => 'Aktualizacja danych: '.$latestDate->format('d.m.Y'),
            'difficulty_label' => $difficulty['label'],
            'difficulty_tone' => $difficulty['tone'],
            'incorrect_percent' => $incorrectPercent,
            'items' => $items->all(),
        ];
    }

    /**
     * @param  iterable<Question>  $relatedQuestions
     * @return list<int>
     */
    protected function compatibleQuestionIds(Question $question, iterable $relatedQuestions): array
    {
        $ids = collect([$question])
            ->merge($relatedQuestions)
            ->filter(fn (mixed $candidate): bool => $candidate instanceof Question)
            ->filter(fn (Question $candidate): bool => $this->isCompatibleQuestion($question, $candidate))
            ->map(fn (Question $candidate): int => (int) $candidate->getKey())
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $ids !== [] ? $ids : [(int) $question->getKey()];
    }

    protected function isCompatibleQuestion(Question $question, Question $candidate): bool
    {
        return (string) $candidate->question_type === (string) $question->question_type
            && $this->normalizedAnswer($candidate->correct_answer) === $this->normalizedAnswer($question->correct_answer)
            && $this->normalizedOption($candidate->option_a) === $this->normalizedOption($question->option_a)
            && $this->normalizedOption($candidate->option_b) === $this->normalizedOption($question->option_b);
    }

    protected function normalizedAnswer(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    protected function normalizedOption(mixed $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function withStablePercentages(Collection $items, int $totalAnswers): Collection
    {
        if ($totalAnswers <= 0 || $items->isEmpty()) {
            return $items;
        }

        $remainingPercent = 100;

        return $items
            ->values()
            ->map(function (array $item, int $index) use ($items, $totalAnswers, &$remainingPercent): array {
                if ($index === $items->count() - 1) {
                    $item['percent'] = max($remainingPercent, 0);

                    return $item;
                }

                $percent = (int) round(((int) $item['count'] / $totalAnswers) * 100);
                $item['percent'] = min(max($percent, 0), $remainingPercent);
                $remainingPercent -= (int) $item['percent'];

                return $item;
            });
    }

    /**
     * @return array{label:string,tone:string}
     */
    protected function difficultyForIncorrectPercent(float $incorrectPercent): array
    {
        if ($incorrectPercent >= 40.0) {
            return ['label' => 'Wysoki', 'tone' => 'high'];
        }

        if ($incorrectPercent >= 20.0) {
            return ['label' => 'Średni', 'tone' => 'medium'];
        }

        return ['label' => 'Niski', 'tone' => 'low'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function unavailable(string $reason): array
    {
        return [
            'available' => false,
            'reason' => $reason,
            'items' => [],
        ];
    }
}
