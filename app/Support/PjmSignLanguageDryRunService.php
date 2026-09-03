<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Finder\Finder;
use Throwable;

class PjmSignLanguageDryRunService
{
    public function __construct(
        private readonly PjmSignLanguageFilenameParser $parser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReport(string $sourcePath, int $sampleLimit = 20): array
    {
        $sourcePath = rtrim($sourcePath, DIRECTORY_SEPARATOR);

        $assets = [];
        $invalidFiles = [];
        $duplicates = [];
        $roleCounts = [
            QuestionSignLanguageAsset::ROLE_QUESTION => 0,
            QuestionSignLanguageAsset::ROLE_ANSWER_A => 0,
            QuestionSignLanguageAsset::ROLE_ANSWER_B => 0,
            QuestionSignLanguageAsset::ROLE_ANSWER_C => 0,
        ];
        $rolesByExternalId = [];
        $seenRoleVariants = [];
        $totalFiles = 0;

        foreach ($this->files($sourcePath) as $file) {
            $totalFiles++;
            $parsed = $this->parser->parse($file->getFilename());

            if ($parsed === null) {
                $invalidFiles[] = $file->getRelativePathname();
                continue;
            }

            $assetKey = $parsed['external_id'].'|'.$parsed['asset_role'].'|'.QuestionSignLanguageAsset::VARIANT_STANDARD;

            if (isset($seenRoleVariants[$assetKey])) {
                $duplicates[] = [
                    'external_id' => $parsed['external_id'],
                    'asset_role' => $parsed['asset_role'],
                    'files' => [$seenRoleVariants[$assetKey], $file->getRelativePathname()],
                ];
            }

            $seenRoleVariants[$assetKey] = $file->getRelativePathname();
            $roleCounts[$parsed['asset_role']]++;
            $rolesByExternalId[$parsed['external_id']][$parsed['asset_role']] = true;

            $assets[] = [
                'external_id' => $parsed['external_id'],
                'asset_role' => $parsed['asset_role'],
                'filename' => $file->getFilename(),
                'relative_path' => $file->getRelativePathname(),
                'extension' => $parsed['extension'],
                'bytes' => $file->getSize(),
            ];
        }

        $externalIds = array_keys($rolesByExternalId);
        sort($externalIds, SORT_NATURAL);

        $questionAssetIds = $this->externalIdsForRole($rolesByExternalId, QuestionSignLanguageAsset::ROLE_QUESTION);
        $databaseReport = $this->databaseReport($externalIds, $questionAssetIds, $sampleLimit);

        return array_merge([
            'source_path' => $sourcePath,
            'dry_run' => true,
            'generated_at' => now()->toIso8601String(),
            'total_files' => $totalFiles,
            'parsed_files' => count($assets),
            'invalid_files_count' => count($invalidFiles),
            'invalid_files_sample' => array_slice($invalidFiles, 0, $sampleLimit),
            'unique_external_ids' => count($externalIds),
            'question_assets' => $roleCounts[QuestionSignLanguageAsset::ROLE_QUESTION],
            'answer_assets' => $roleCounts[QuestionSignLanguageAsset::ROLE_ANSWER_A]
                + $roleCounts[QuestionSignLanguageAsset::ROLE_ANSWER_B]
                + $roleCounts[QuestionSignLanguageAsset::ROLE_ANSWER_C],
            'role_counts' => $roleCounts,
            'question_only_sets' => $this->countQuestionOnlySets($rolesByExternalId),
            'complete_answer_sets' => $this->countCompleteAnswerSets($rolesByExternalId),
            'duplicates_count' => count($duplicates),
            'duplicates_sample' => array_slice($duplicates, 0, $sampleLimit),
        ], $databaseReport);
    }

    private function files(string $sourcePath): Finder
    {
        return Finder::create()
            ->files()
            ->in($sourcePath)
            ->depth('== 0')
            ->sortByName();
    }

    /**
     * @param  array<string, array<string, bool>>  $rolesByExternalId
     * @return array<int, string>
     */
    private function externalIdsForRole(array $rolesByExternalId, string $role): array
    {
        return array_values(array_filter(
            array_keys($rolesByExternalId),
            fn (string $externalId): bool => isset($rolesByExternalId[$externalId][$role]),
        ));
    }

    /**
     * @return array<int, string>
     */
    private function activeDatabaseExternalIds(): array
    {
        return Question::query()
            ->where('is_active', true)
            ->whereNull('delivery_issue')
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->distinct()
            ->pluck('external_id')
            ->map(fn ($externalId): string => (string) $externalId)
            ->all();
    }

    /**
     * @param  array<int, string>  $externalIds
     * @param  array<int, string>  $questionAssetIds
     * @return array<string, mixed>
     */
    private function databaseReport(array $externalIds, array $questionAssetIds, int $sampleLimit): array
    {
        try {
            $activeDatabaseExternalIds = $this->activeDatabaseExternalIds();
            $orphanedExternalIds = array_values(array_diff($externalIds, $activeDatabaseExternalIds));
            $matchedExternalIds = array_values(array_intersect($externalIds, $activeDatabaseExternalIds));

            return [
                'database_available' => true,
                'database_error' => null,
                'active_database_external_ids' => count($activeDatabaseExternalIds),
                'matched_external_ids' => count($matchedExternalIds),
                'orphaned_external_ids' => count($orphanedExternalIds),
                'orphaned_external_ids_sample' => array_slice($orphanedExternalIds, 0, $sampleLimit),
                'category_coverage' => $this->categoryCoverageFromFiles($questionAssetIds),
            ];
        } catch (Throwable $exception) {
            return [
                'database_available' => false,
                'database_error' => $exception->getMessage(),
                'active_database_external_ids' => null,
                'matched_external_ids' => null,
                'orphaned_external_ids' => null,
                'orphaned_external_ids_sample' => [],
                'category_coverage' => [],
            ];
        }
    }

    /**
     * @param  array<string, array<string, bool>>  $rolesByExternalId
     */
    private function countQuestionOnlySets(array $rolesByExternalId): int
    {
        return collect($rolesByExternalId)
            ->filter(fn (array $roles): bool => array_keys($roles) === [QuestionSignLanguageAsset::ROLE_QUESTION])
            ->count();
    }

    /**
     * @param  array<string, array<string, bool>>  $rolesByExternalId
     */
    private function countCompleteAnswerSets(array $rolesByExternalId): int
    {
        return collect($rolesByExternalId)
            ->filter(fn (array $roles): bool => isset(
                $roles[QuestionSignLanguageAsset::ROLE_QUESTION],
                $roles[QuestionSignLanguageAsset::ROLE_ANSWER_A],
                $roles[QuestionSignLanguageAsset::ROLE_ANSWER_B],
                $roles[QuestionSignLanguageAsset::ROLE_ANSWER_C],
            ))
            ->count();
    }

    /**
     * @param  array<int, string>  $questionAssetIds
     * @return array<int, array<string, mixed>>
     */
    private function categoryCoverageFromFiles(array $questionAssetIds): array
    {
        return LicenseCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(function (LicenseCategory $category) use ($questionAssetIds): array {
                $questionQuery = $this->baseQuestionQuery($category);
                $totalQuestions = (clone $questionQuery)->count();
                $coveredQuestions = $questionAssetIds === []
                    ? 0
                    : (clone $questionQuery)->whereIn('external_id', $questionAssetIds)->count();

                return [
                    'code' => $category->code,
                    'name' => $category->name,
                    'total_questions' => $totalQuestions,
                    'pjm_questions' => $coveredQuestions,
                    'missing_questions' => max(0, $totalQuestions - $coveredQuestions),
                    'coverage_percent' => $this->percent($coveredQuestions, $totalQuestions),
                ];
            })
            ->all();
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

    private function percent(int $covered, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($covered / $total) * 100, 2);
    }
}
