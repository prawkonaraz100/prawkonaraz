<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PjmCoverageService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function categoryCoverage(?array $categoryCodes = null): array
    {
        return LicenseCategory::query()
            ->when($categoryCodes !== null, fn (Builder $query) => $query->whereIn('code', $categoryCodes))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (LicenseCategory $category): array => $this->coverageForCategory($category))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(?array $categoryCodes = null): array
    {
        $categories = $this->categoryCoverage($categoryCodes);
        $totalQuestions = array_sum(array_column($categories, 'total_questions'));
        $pjmQuestions = array_sum(array_column($categories, 'pjm_questions'));

        return [
            'total_questions' => $totalQuestions,
            'pjm_questions' => $pjmQuestions,
            'missing_questions' => max(0, $totalQuestions - $pjmQuestions),
            'coverage_percent' => $this->percent($pjmQuestions, $totalQuestions),
            'categories' => $categories,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function assetRoleCounts(): array
    {
        return QuestionSignLanguageAsset::query()
            ->active()
            ->ready()
            ->select('asset_role', DB::raw('count(*) as aggregate'))
            ->groupBy('asset_role')
            ->pluck('aggregate', 'asset_role')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function coverageForCategory(LicenseCategory $category): array
    {
        $questionQuery = $this->baseQuestionQuery($category);

        $totalQuestions = (clone $questionQuery)->count();
        $pjmQuestions = (clone $questionQuery)
            ->whereExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('question_sign_language_assets as pjm_assets')
                    ->whereColumn('pjm_assets.external_id', 'questions.external_id')
                    ->where('pjm_assets.asset_role', QuestionSignLanguageAsset::ROLE_QUESTION)
                    ->where('pjm_assets.is_active', true)
                    ->whereIn('pjm_assets.processing_status', [
                        QuestionSignLanguageAsset::STATUS_READY,
                        QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
                    ]);
            })
            ->count();

        $roleCounts = $this->assetCountsForQuestions(clone $questionQuery);
        $answerAssets = array_sum(array_intersect_key($roleCounts, array_flip([
            QuestionSignLanguageAsset::ROLE_ANSWER_A,
            QuestionSignLanguageAsset::ROLE_ANSWER_B,
            QuestionSignLanguageAsset::ROLE_ANSWER_C,
        ])));

        return [
            'id' => $category->id,
            'code' => $category->code,
            'name' => $category->name,
            'total_questions' => $totalQuestions,
            'pjm_questions' => $pjmQuestions,
            'missing_questions' => max(0, $totalQuestions - $pjmQuestions),
            'coverage_percent' => $this->percent($pjmQuestions, $totalQuestions),
            'question_assets' => $roleCounts[QuestionSignLanguageAsset::ROLE_QUESTION] ?? 0,
            'answer_assets' => $answerAssets,
            'active_assets' => array_sum($roleCounts),
        ];
    }

    private function baseQuestionQuery(LicenseCategory $category): Builder
    {
        return Question::query()
            ->where('license_category_id', $category->id)
            ->where('is_active', true)
            ->whereNull('delivery_issue')
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '');
    }

    /**
     * @return array<string, int>
     */
    private function assetCountsForQuestions(Builder $questionQuery): array
    {
        return DB::table('question_sign_language_assets')
            ->whereIn('external_id', $questionQuery->select('external_id')->distinct())
            ->where('is_active', true)
            ->whereIn('processing_status', [
                QuestionSignLanguageAsset::STATUS_READY,
                QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
            ])
            ->select('asset_role', DB::raw('count(*) as aggregate'))
            ->groupBy('asset_role')
            ->pluck('aggregate', 'asset_role')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    private function percent(int $covered, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($covered / $total) * 100, 2);
    }
}
