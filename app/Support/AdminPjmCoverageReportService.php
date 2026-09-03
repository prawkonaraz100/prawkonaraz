<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminPjmCoverageReportService
{
    public function __construct(
        private readonly PjmCoverageService $coverageService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?string $externalId = null, int $sampleLimit = 20): array
    {
        $sampleLimit = max(1, $sampleLimit);
        $coverage = $this->coverageService->summary();
        $roleCounts = $this->coverageService->assetRoleCounts();
        $orphanedQuery = $this->orphanedAssetQuery();
        $problemQuery = $this->processingProblemAssetQuery();

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_questions' => (int) $coverage['total_questions'],
                'pjm_questions' => (int) $coverage['pjm_questions'],
                'missing_questions' => (int) $coverage['missing_questions'],
                'coverage_percent' => (float) $coverage['coverage_percent'],
                'total_assets' => QuestionSignLanguageAsset::query()->count(),
                'active_assets' => QuestionSignLanguageAsset::query()->where('is_active', true)->count(),
                'question_assets' => $roleCounts[QuestionSignLanguageAsset::ROLE_QUESTION] ?? 0,
                'answer_assets' => array_sum(array_intersect_key($roleCounts, array_flip([
                    QuestionSignLanguageAsset::ROLE_ANSWER_A,
                    QuestionSignLanguageAsset::ROLE_ANSWER_B,
                    QuestionSignLanguageAsset::ROLE_ANSWER_C,
                ]))),
                'review_required_assets' => QuestionSignLanguageAsset::query()
                    ->where('review_required', true)
                    ->count(),
                'processing_problem_assets' => (clone $problemQuery)->count(),
                'orphaned_assets' => (clone $orphanedQuery)->count(),
                'total_bytes' => (int) QuestionSignLanguageAsset::query()->sum('bytes'),
                'total_bytes_human' => $this->humanBytes((int) QuestionSignLanguageAsset::query()->sum('bytes')),
            ],
            'categories' => $coverage['categories'],
            'asset_roles' => $this->roleRows($roleCounts),
            'processing_statuses' => $this->processingStatusRows(),
            'review_required_sample' => $this->assetSample(
                QuestionSignLanguageAsset::query()->where('review_required', true),
                $sampleLimit,
            ),
            'processing_problem_sample' => $this->assetSample($problemQuery, $sampleLimit),
            'orphaned_asset_sample' => $this->assetSample($orphanedQuery, $sampleLimit),
            'missing_question_sample' => $this->missingQuestionSample($sampleLimit),
            'external_id_lookup' => $this->externalIdLookup($externalId),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'w+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, ['section', 'code_or_id', 'label', 'total', 'pjm', 'missing', 'coverage_percent', 'status']);

        foreach ($report['categories'] as $category) {
            fputcsv($handle, [
                'category',
                $category['code'],
                $category['name'],
                $category['total_questions'],
                $category['pjm_questions'],
                $category['missing_questions'],
                $category['coverage_percent'],
                '',
            ]);
        }

        foreach ($report['processing_statuses'] as $status) {
            fputcsv($handle, [
                'processing_status',
                $status['status'],
                $status['label'],
                $status['count'],
                '',
                '',
                '',
                $status['tone'],
            ]);
        }

        foreach ($report['orphaned_asset_sample'] as $asset) {
            fputcsv($handle, [
                'orphaned_asset_sample',
                $asset['external_id'],
                $asset['role'],
                '',
                '',
                '',
                '',
                $asset['processing_status'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    private function orphanedAssetQuery(): Builder
    {
        return QuestionSignLanguageAsset::query()
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('questions')
                    ->whereColumn('questions.external_id', 'question_sign_language_assets.external_id')
                    ->where('questions.is_active', true)
                    ->whereNull('questions.delivery_issue');
            });
    }

    private function processingProblemAssetQuery(): Builder
    {
        return QuestionSignLanguageAsset::query()
            ->where(function (Builder $query): void {
                $query
                    ->where('is_active', false)
                    ->orWhereNotIn('processing_status', [
                        QuestionSignLanguageAsset::STATUS_READY,
                        QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
                    ]);
            });
    }

    /**
     * @param  array<string, int>  $roleCounts
     * @return array<int, array<string, mixed>>
     */
    private function roleRows(array $roleCounts): array
    {
        return collect([
            QuestionSignLanguageAsset::ROLE_QUESTION => 'Film pytania',
            QuestionSignLanguageAsset::ROLE_ANSWER_A => 'Odpowiedź A',
            QuestionSignLanguageAsset::ROLE_ANSWER_B => 'Odpowiedź B',
            QuestionSignLanguageAsset::ROLE_ANSWER_C => 'Odpowiedź C',
        ])->map(fn (string $label, string $role): array => [
            'role' => $role,
            'label' => $label,
            'count' => $roleCounts[$role] ?? 0,
        ])->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function processingStatusRows(): array
    {
        $counts = QuestionSignLanguageAsset::query()
            ->selectRaw('processing_status, count(*) as aggregate')
            ->groupBy('processing_status')
            ->orderBy('processing_status')
            ->pluck('aggregate', 'processing_status')
            ->map(fn ($count): int => (int) $count);

        return $counts
            ->map(fn (int $count, string $status): array => [
                'status' => $status,
                'label' => $this->processingStatusLabel($status),
                'count' => $count,
                'tone' => $this->processingStatusTone($status),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assetSample(Builder $query, int $limit): array
    {
        return (clone $query)
            ->orderBy('external_id')
            ->orderBy('asset_role')
            ->limit($limit)
            ->get([
                'id',
                'external_id',
                'asset_role',
                'disk',
                'path',
                'source_filename',
                'bytes',
                'processing_status',
                'review_required',
                'is_active',
            ])
            ->map(fn (QuestionSignLanguageAsset $asset): array => $this->assetPayload($asset))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function missingQuestionSample(int $limit): array
    {
        return Question::query()
            ->join('license_categories', 'license_categories.id', '=', 'questions.license_category_id')
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->whereNotNull('questions.external_id')
            ->where('questions.external_id', '!=', '')
            ->whereNotExists(function ($query): void {
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
            ->orderBy('license_categories.sort_order')
            ->orderBy('questions.external_id')
            ->limit($limit)
            ->get([
                'questions.id',
                'questions.external_id',
                'questions.prompt',
                'license_categories.code as category_code',
            ])
            ->map(fn (Question $question): array => [
                'question_id' => $question->getKey(),
                'external_id' => $question->external_id,
                'category_code' => $question->category_code,
                'prompt' => str((string) $question->prompt)->limit(120)->value(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function externalIdLookup(?string $externalId): ?array
    {
        $externalId = trim((string) $externalId);

        if ($externalId === '') {
            return null;
        }

        $questions = Question::query()
            ->with('licenseCategory:id,code,name')
            ->where('external_id', $externalId)
            ->orderBy('license_category_id')
            ->get(['id', 'external_id', 'license_category_id', 'prompt', 'is_active', 'delivery_issue'])
            ->map(fn (Question $question): array => [
                'id' => $question->getKey(),
                'category_code' => $question->licenseCategory?->code,
                'category_name' => $question->licenseCategory?->name,
                'is_active' => (bool) $question->is_active,
                'delivery_issue' => $question->delivery_issue,
                'prompt' => str((string) $question->prompt)->limit(180)->value(),
            ])
            ->all();

        $assets = QuestionSignLanguageAsset::query()
            ->where('external_id', $externalId)
            ->orderBy('asset_role')
            ->get()
            ->map(fn (QuestionSignLanguageAsset $asset): array => $this->assetPayload($asset))
            ->all();

        return [
            'external_id' => $externalId,
            'question_count' => count($questions),
            'asset_count' => count($assets),
            'has_question_asset' => collect($assets)->contains(
                fn (array $asset): bool => $asset['role'] === QuestionSignLanguageAsset::ROLE_QUESTION,
            ),
            'questions' => $questions,
            'assets' => $assets,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assetPayload(QuestionSignLanguageAsset $asset): array
    {
        return [
            'id' => $asset->getKey(),
            'external_id' => $asset->external_id,
            'role' => $asset->asset_role,
            'disk' => $asset->disk,
            'path' => $asset->path,
            'source_filename' => $asset->source_filename,
            'bytes' => $asset->bytes,
            'bytes_human' => $this->humanBytes((int) $asset->bytes),
            'processing_status' => $asset->processing_status,
            'review_required' => (bool) $asset->review_required,
            'is_active' => (bool) $asset->is_active,
            'url' => $this->assetUrl($asset),
        ];
    }

    private function assetUrl(QuestionSignLanguageAsset $asset): ?string
    {
        try {
            return Storage::disk($asset->disk)->url($asset->path);
        } catch (Throwable) {
            return null;
        }
    }

    private function processingStatusLabel(string $status): string
    {
        return match ($status) {
            QuestionSignLanguageAsset::STATUS_READY => 'Gotowe',
            QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED => 'Do ręcznej kontroli',
            QuestionSignLanguageAsset::STATUS_DISABLED => 'Wyłączone',
            default => $status,
        };
    }

    private function processingStatusTone(string $status): string
    {
        return match ($status) {
            QuestionSignLanguageAsset::STATUS_READY => 'success',
            QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED => 'warning',
            default => 'danger',
        };
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KiB', 'MiB', 'GiB'];
        $size = (float) $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $unitIndex === 0 ? 0 : 2, ',', ' ').' '.$units[$unitIndex];
    }
}
