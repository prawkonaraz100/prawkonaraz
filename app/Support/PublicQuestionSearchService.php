<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicQuestionSearchService
{
    public function __construct(
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
        protected QuestionTextFormatter $questionTextFormatter,
    ) {}

    public function canonicalRedirectFor(string $query): ?string
    {
        $query = Str::squish(trim($query));

        if ($query === '') {
            return null;
        }

        return $this->publicQuestionCatalogService->findCanonicalUrlByExternalId($query);
    }

    public function search(string $query, ?LicenseCategory $category = null, string $govId = ''): PublicQuestionSearchResult
    {
        $query = Str::squish(trim($query));
        $govId = Str::squish(trim($govId));
        $normalizedQuery = $this->normalizeSearchableText($query);
        $normalizedGovId = $this->normalizeSearchableText($govId);
        $hasSearch = $normalizedQuery !== '' || $normalizedGovId !== '';

        if (! $hasSearch && ! $category instanceof LicenseCategory) {
            return new PublicQuestionSearchResult($query, $normalizedQuery, collect());
        }

        $matches = $this->candidateQuestions($category)
            ->map(function (Question $question) use ($normalizedQuery, $normalizedGovId, $hasSearch): ?array {
                $score = $hasSearch
                    ? $this->scoreQuestion($question, $normalizedQuery, $normalizedGovId)
                    : 1;

                if ($score <= 0) {
                    return null;
                }

                return [
                    'question' => $question,
                    'score' => $score,
                    'exact_prompt' => $normalizedQuery !== ''
                        && $this->normalizeSearchableText((string) $question->prompt) === $normalizedQuery,
                ];
            })
            ->filter()
            ->values();

        $items = $matches
            ->groupBy(fn (array $match): string => (string) $match['question']->external_id)
            ->map(fn (Collection $group): array => $this->buildGroupedItem($group, $category))
            ->sort(function (array $left, array $right): int {
                $scoreComparison = ((int) ($right['match_score'] ?? 0)) <=> ((int) ($left['match_score'] ?? 0));

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return strnatcasecmp(
                    (string) ($left['external_id'] ?? ''),
                    (string) ($right['external_id'] ?? ''),
                );
            })
            ->values();

        return new PublicQuestionSearchResult(
            $query,
            $normalizedQuery,
            $items,
            $this->exactPromptRedirectUrl($items),
        );
    }

    public function normalizeSearchableText(string $value): string
    {
        $plainText = $this->questionTextFormatter->plainText($value);
        $ascii = Str::of($plainText)
            ->lower()
            ->ascii()
            ->value();

        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @return Collection<int, Question>
     */
    protected function candidateQuestions(?LicenseCategory $category): Collection
    {
        return $this->publicQuestionCatalogService
            ->publicQuestionsQuery()
            ->when(
                $category instanceof LicenseCategory,
                fn ($query) => $query->where('license_category_id', $category->getKey()),
            )
            ->with(['licenseCategory:id,code,slug,name,sort_order'])
            ->withCount('media')
            ->get();
    }

    protected function scoreQuestion(Question $question, string $normalizedQuery, string $normalizedGovId): int
    {
        $rawExternalId = $this->normalizeSearchableText((string) $question->external_id);
        $displayExternalId = $this->normalizeSearchableText(
            $this->publicQuestionCatalogService->displayExternalId($question->external_id),
        );

        if ($normalizedGovId !== '') {
            return $rawExternalId === $normalizedGovId || $displayExternalId === $normalizedGovId
                ? 1000
                : 0;
        }

        if ($normalizedQuery === '') {
            return 0;
        }

        if ($rawExternalId === $normalizedQuery || $displayExternalId === $normalizedQuery) {
            return 950;
        }

        if (str_contains($rawExternalId, $normalizedQuery) || str_contains($displayExternalId, $normalizedQuery)) {
            return 850;
        }

        $prompt = $this->normalizeSearchableText((string) $question->prompt);

        if ($prompt === $normalizedQuery) {
            return 800;
        }

        if (str_contains($prompt, $normalizedQuery)) {
            return 700;
        }

        return $this->containsAllTokens($prompt, $normalizedQuery) ? 500 : 0;
    }

    /**
     * @param  Collection<int, array{question: Question, score: int, exact_prompt: bool}>  $matches
     * @return array<string, mixed>
     */
    protected function buildGroupedItem(Collection $matches, ?LicenseCategory $category): array
    {
        /** @var Collection<int, Question> $questions */
        $questions = $matches
            ->pluck('question')
            ->values();

        $representative = $this->publicQuestionCatalogService->representativeQuestionForGroup($questions);
        $representative->loadMissing(['media' => fn ($query) => $query->orderBy('sort_order')]);

        $categories = $this->publicQuestionCatalogService->categoriesForQuestions($questions);
        $item = $this->publicQuestionCatalogService->buildQuestionListItem(
            $representative,
            $category instanceof LicenseCategory ? $category : null,
        );

        $item['category_codes'] = $categories
            ->pluck('code')
            ->filter()
            ->values()
            ->all();
        $item['category_count'] = count($item['category_codes']);
        $item['match_score'] = (int) $matches->max('score');
        $item['is_exact_prompt_match'] = $matches->contains(fn (array $match): bool => (bool) $match['exact_prompt']);

        return $item;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    protected function exactPromptRedirectUrl(Collection $items): ?string
    {
        $exactItems = $items
            ->filter(fn (array $item): bool => (bool) ($item['is_exact_prompt_match'] ?? false))
            ->values();

        if ($exactItems->count() !== 1) {
            return null;
        }

        $url = $exactItems->first()['canonical_url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    protected function containsAllTokens(string $haystack, string $needle): bool
    {
        $tokens = preg_split('/\s+/', $needle) ?: [];
        $tokens = array_values(array_filter($tokens, fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return false;
        }

        foreach ($tokens as $token) {
            if (! str_contains($haystack, $token)) {
                return false;
            }
        }

        return true;
    }
}
