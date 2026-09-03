<?php

namespace App\Support;

use App\Models\LegalArticleTopicCandidate;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegalArticleTopicCandidateSyncService
{
    public const ASSIGNMENT_SOURCE = 'prompt_scan';

    public function __construct(
        protected LegalArticleTopicCandidateCatalog $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(bool $write = false): array
    {
        $definitions = $this->catalog->definitions();
        $questions = Question::query()
            ->with([
                'licenseCategory:id,code',
                'questionTopic:id,key,name',
            ])
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->orderBy('id')
            ->get([
                'id',
                'license_category_id',
                'question_topic_id',
                'external_id',
                'prompt',
            ]);
        $matches = collect();

        foreach ($questions as $question) {
            $context = $this->questionContext($question);

            foreach ($definitions as $definition) {
                $match = $this->matchDefinition($definition, $context);

                if ($match === null) {
                    continue;
                }

                $matches->push([
                    'slug' => $definition['slug'],
                    'question_id' => (int) $question->getKey(),
                    'canonical_external_id' => $context['canonical_external_id'],
                    'external_id' => (string) $question->external_id,
                    'prompt' => (string) $question->prompt,
                    'category_code' => (string) ($question->licenseCategory?->code ?? ''),
                    'question_topic_key' => (string) ($question->questionTopic?->key ?? ''),
                    ...$match,
                ]);
            }
        }

        $summary = $this->summary($definitions, $matches, $questions);

        if ($write) {
            $this->persist($definitions, $matches);
        }

        return $summary;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $definitions
     * @param  Collection<int, array<string, mixed>>  $matches
     */
    protected function persist(Collection $definitions, Collection $matches): void
    {
        DB::transaction(function () use ($definitions, $matches): void {
            $candidateIdsBySlug = collect();

            foreach ($definitions as $definition) {
                $candidate = LegalArticleTopicCandidate::query()->firstOrNew([
                    'slug' => $definition['slug'],
                ]);
                $candidate->fill([
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'level' => $definition['level'],
                    'existing_article_slug' => $definition['existing_article_slug'] ?? null,
                    'sort_order' => $definition['sort_order'],
                    'rule_version' => $this->catalog->version(),
                ]);

                if (! $candidate->exists) {
                    $candidate->status = LegalArticleTopicCandidate::STATUS_CANDIDATE;
                }

                $candidate->save();

                $candidateIdsBySlug->put($definition['slug'], (int) $candidate->getKey());
            }

            $candidateIds = $candidateIdsBySlug->values()->all();

            DB::table('legal_article_topic_candidate_question')
                ->whereIn('legal_article_topic_candidate_id', $candidateIds)
                ->where('assignment_source', self::ASSIGNMENT_SOURCE)
                ->delete();

            $now = now();
            $rows = $matches
                ->map(fn (array $match): array => [
                    'legal_article_topic_candidate_id' => $candidateIdsBySlug->get($match['slug']),
                    'question_id' => $match['question_id'],
                    'canonical_external_id' => $match['canonical_external_id'],
                    'match_type' => $match['match_type'],
                    'matched_by' => $match['matched_by'],
                    'confidence' => $match['confidence'],
                    'assignment_source' => self::ASSIGNMENT_SOURCE,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values();

            $rows
                ->chunk(1000)
                ->each(fn (Collection $chunk) => DB::table(
                    'legal_article_topic_candidate_question',
                )->insertOrIgnore($chunk->all()));
        });
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, string>  $context
     * @return array{match_type:string,matched_by:string,confidence:int}|null
     */
    protected function matchDefinition(array $definition, array $context): ?array
    {
        if ($definition['level'] === LegalArticleTopicCandidate::LEVEL_PILLAR) {
            if ($context['question_topic_key'] !== ($definition['topic_key'] ?? '')) {
                return null;
            }

            return [
                'match_type' => 'question_topic',
                'matched_by' => 'question_topic:'.$context['question_topic_key'],
                'confidence' => 60,
            ];
        }

        $allowedTopicKeys = $definition['topic_keys'] ?? [];

        if (
            $allowedTopicKeys !== []
            && ! in_array($context['question_topic_key'], $allowedTopicKeys, true)
        ) {
            return null;
        }

        $prompt = $context['normalized_prompt'];
        $promptAll = $this->normalizePhrases($definition['prompt_all'] ?? []);
        $promptAny = $this->normalizePhrases($definition['prompt_any'] ?? []);
        $promptAnyContext = $this->normalizePhrases($definition['prompt_any_context'] ?? []);
        $promptNone = $this->normalizePhrases($definition['prompt_none'] ?? []);

        if ($promptAll !== [] && ! $this->containsAll($prompt, $promptAll)) {
            return null;
        }

        if ($promptAny !== [] && ! $this->containsAny($prompt, $promptAny)) {
            return null;
        }

        if ($promptAnyContext !== [] && ! $this->containsAny($prompt, $promptAnyContext)) {
            return null;
        }

        if ($promptNone !== [] && $this->containsAny($prompt, $promptNone)) {
            return null;
        }

        if ($promptAll === [] && $promptAny === []) {
            return null;
        }

        $matchedPhrases = collect([...$promptAll, ...$promptAny, ...$promptAnyContext])
            ->filter(fn (string $phrase): bool => str_contains($prompt, $phrase))
            ->values()
            ->take(4);

        return [
            'match_type' => 'prompt_rule',
            'matched_by' => 'prompt:'.$matchedPhrases->implode('|'),
            'confidence' => $promptAll !== [] || $promptAnyContext !== [] ? 95 : 90,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $definitions
     * @param  Collection<int, array<string, mixed>>  $matches
     * @param  Collection<int, Question>  $questions
     * @return array<string, mixed>
     */
    protected function summary(
        Collection $definitions,
        Collection $matches,
        Collection $questions,
    ): array {
        $canonicalQuestions = $questions
            ->groupBy(fn (Question $question): string => $this->canonicalExternalId($question->external_id));
        $matchesBySlug = $matches->groupBy('slug');
        $topics = $definitions
            ->map(function (array $definition) use ($matchesBySlug): array {
                $topicMatches = $matchesBySlug->get($definition['slug'], collect());
                $canonicalMatches = $topicMatches
                    ->groupBy('canonical_external_id')
                    ->map(function (Collection $questionMatches, string $canonicalExternalId): array {
                        $representative = $questionMatches
                            ->sortBy(fn (array $match): array => [
                                str_contains($match['external_id'], ':') ? 1 : 0,
                                $match['question_id'],
                            ])
                            ->first();

                        return [
                            'external_id' => $canonicalExternalId,
                            'prompt' => $representative['prompt'],
                            'categories' => $questionMatches
                                ->pluck('category_code')
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values()
                                ->all(),
                            'question_topic_keys' => $questionMatches
                                ->pluck('question_topic_key')
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values()
                                ->all(),
                            'confidence' => (int) $questionMatches->max('confidence'),
                            'matched_by' => $questionMatches
                                ->pluck('matched_by')
                                ->filter()
                                ->unique()
                                ->values()
                                ->all(),
                        ];
                    })
                    ->sortKeysUsing('strnatcasecmp')
                    ->values();

                return [
                    'slug' => $definition['slug'],
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'level' => $definition['level'],
                    'existing_article_slug' => $definition['existing_article_slug'] ?? null,
                    'question_rows' => $topicMatches->count(),
                    'canonical_questions' => $canonicalMatches->count(),
                    'categories' => $topicMatches
                        ->pluck('category_code')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'questions' => $canonicalMatches->all(),
                ];
            })
            ->values();
        $focusedCanonicalIds = $topics
            ->where('level', LegalArticleTopicCandidate::LEVEL_FOCUSED)
            ->flatMap(fn (array $topic): array => array_column($topic['questions'], 'external_id'))
            ->unique()
            ->values();
        $pillarQuestionIds = $matches
            ->whereIn(
                'slug',
                $definitions
                    ->where('level', LegalArticleTopicCandidate::LEVEL_PILLAR)
                    ->pluck('slug'),
            )
            ->pluck('question_id')
            ->unique()
            ->flip();
        $questionsWithoutPillar = $questions
            ->reject(fn (Question $question): bool => $pillarQuestionIds->has((int) $question->getKey()));

        return [
            'generated_at' => now()->toIso8601String(),
            'rule_version' => $this->catalog->version(),
            'question_rows_scanned' => $questions->count(),
            'canonical_questions_scanned' => $canonicalQuestions->count(),
            'candidate_topics' => $topics->count(),
            'pillar_topics' => $topics->where('level', LegalArticleTopicCandidate::LEVEL_PILLAR)->count(),
            'focused_topics' => $topics->where('level', LegalArticleTopicCandidate::LEVEL_FOCUSED)->count(),
            'question_rows_without_pillar_topic' => $questionsWithoutPillar->count(),
            'canonical_questions_without_pillar_topic' => $questionsWithoutPillar
                ->map(fn (Question $question): string => $this->canonicalExternalId($question->external_id))
                ->unique()
                ->count(),
            'canonical_questions_with_focused_topic' => $focusedCanonicalIds->count(),
            'focused_coverage_percent' => $canonicalQuestions->isEmpty()
                ? 0
                : round(($focusedCanonicalIds->count() / $canonicalQuestions->count()) * 100, 1),
            'topics' => $topics->all(),
        ];
    }

    /**
     * @return array{canonical_external_id:string,normalized_prompt:string,question_topic_key:string}
     */
    protected function questionContext(Question $question): array
    {
        return [
            'canonical_external_id' => $this->canonicalExternalId($question->external_id),
            'normalized_prompt' => $this->normalize((string) $question->prompt),
            'question_topic_key' => (string) ($question->questionTopic?->key ?? ''),
        ];
    }

    protected function canonicalExternalId(mixed $externalId): string
    {
        $value = trim((string) $externalId);

        if ($value === '' || ! str_contains($value, ':')) {
            return $value;
        }

        $suffix = trim(substr($value, (int) strrpos($value, ':') + 1));

        return $suffix !== '' ? $suffix : $value;
    }

    protected function normalize(string $value): string
    {
        return (string) Str::of($value)
            ->lower()
            ->ascii()
            ->replace(['[green]', '[/green]', '[red]', '[/red]', '**'], ' ')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    /**
     * @param  array<int, string>  $phrases
     * @return list<string>
     */
    protected function normalizePhrases(array $phrases): array
    {
        return collect($phrases)
            ->map(fn (string $phrase): string => $this->normalize($phrase))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $phrases
     */
    protected function containsAny(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $phrases
     */
    protected function containsAll(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (! str_contains($text, $phrase)) {
                return false;
            }
        }

        return true;
    }
}
