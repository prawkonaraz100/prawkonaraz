<?php

namespace App\Support;

use App\Models\ContentImportRun;

class ContentImportRunRecorder
{
    /**
     * @param  array<string, mixed>  $report
     */
    public function record(
        string $kind,
        array $report,
        ?string $reportPath = null,
        ?string $sourcePath = null,
        ?string $outputPath = null,
    ): ContentImportRun {
        return ContentImportRun::query()->create([
            'kind' => $kind,
            'identifier' => $this->identifier($report),
            'status' => $this->status($report),
            'dry_run' => (bool) ($report['dry_run'] ?? false),
            'source_path' => $sourcePath ?? $this->sourcePath($report),
            'output_path' => $outputPath ?? $this->outputPath($report),
            'report_path' => $reportPath,
            'rows_total' => $this->rowsTotal($report),
            'questions_total' => (int) ($report['questions_total'] ?? $report['question_rows_total'] ?? 0),
            'media_total' => $this->mediaTotal($report),
            'asset_plan_total' => (int) ($report['asset_plan_total'] ?? 0),
            'uploaded_assets_total' => (int) ($report['uploaded_assets_total'] ?? 0),
            'errors_count' => (int) ($report['errors_count'] ?? 0),
            'warnings_count' => is_array($report['warnings'] ?? null)
                ? count($report['warnings'])
                : (int) ($report['warnings_total'] ?? 0),
            'summary' => $this->summary($report),
            'started_at' => $report['started_at'] ?? null,
            'completed_at' => $report['completed_at'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    protected function summary(array $report): array
    {
        $chunkOverview = array_values(array_map(
            static fn (array $chunk): array => array_filter([
                'number' => $chunk['number'] ?? null,
                'status' => $chunk['status'] ?? null,
                'source_offset' => $chunk['source_offset'] ?? null,
                'source_limit' => $chunk['source_limit'] ?? null,
                'rows_total' => $chunk['rows_total'] ?? null,
                'rows_prepared' => $chunk['rows_prepared'] ?? null,
                'questions_total' => $chunk['questions_total'] ?? $chunk['question_rows_total'] ?? null,
                'errors_count' => $chunk['errors_count'] ?? null,
            ], static fn (mixed $value): bool => $value !== null),
            array_filter(
                is_array($report['chunks'] ?? null) ? $report['chunks'] : [],
                static fn (mixed $chunk): bool => is_array($chunk),
            ),
        ));

        return array_filter([
            'selected_categories' => $report['selected_categories'] ?? null,
            'stopped_reason' => $report['stopped_reason'] ?? null,
            'source_offset' => $report['source_offset'] ?? null,
            'source_limit' => $report['source_limit'] ?? null,
            'source_rows_considered' => $report['source_rows_considered'] ?? $report['source_rows_considered_total'] ?? null,
            'rows_prepared' => $report['rows_prepared'] ?? $report['rows_prepared_total'] ?? null,
            'rows_skipped_by_category' => $report['rows_skipped_by_category'] ?? $report['rows_skipped_by_category_total'] ?? null,
            'rows_skipped_not_ready' => $report['rows_skipped_not_ready'] ?? $report['rows_skipped_not_ready_total'] ?? null,
            'images_materialized' => $report['images_materialized'] ?? $report['images_materialized_total'] ?? null,
            'thumbs_materialized' => $report['thumbs_materialized'] ?? $report['thumbs_materialized_total'] ?? null,
            'videos_materialized' => $report['videos_materialized'] ?? $report['videos_materialized_total'] ?? null,
            'posters_materialized' => $report['posters_materialized'] ?? $report['posters_materialized_total'] ?? null,
            'chunks_skipped' => $report['chunks_skipped'] ?? null,
            'missing_main_media_total' => $report['main_media_missing_total'] ?? null,
            'missing_pjm_media_total' => $report['pjm_media_missing_total'] ?? null,
            'chunks' => $chunkOverview !== [] ? $chunkOverview : null,
            'warnings' => array_slice(
                is_array($report['warnings'] ?? null) ? $report['warnings'] : [],
                0,
                10,
            ),
            'errors' => array_slice(
                is_array($report['errors'] ?? null) ? $report['errors'] : [],
                0,
                10,
            ),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function identifier(array $report): ?string
    {
        $identifier = $report['series_id'] ?? $report['batch_id'] ?? null;

        if (! is_string($identifier) || $identifier === '') {
            return null;
        }

        return $identifier;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function status(array $report): string
    {
        $status = $report['status'] ?? null;

        if (is_string($status) && $status !== '') {
            return $status;
        }

        return ((int) ($report['errors_count'] ?? 0) > 0) ? 'failed' : 'ok';
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function sourcePath(array $report): ?string
    {
        $value = $report['xlsx_path']
            ?? $report['manifest_path']
            ?? $report['series_directory']
            ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function outputPath(array $report): ?string
    {
        $value = $report['output_directory']
            ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function rowsTotal(array $report): int
    {
        return (int) (
            $report['rows_total']
            ?? $report['rows_prepared_total']
            ?? $report['source_rows_considered_total']
            ?? 0
        );
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function mediaTotal(array $report): int
    {
        if (isset($report['media_total'])) {
            return (int) $report['media_total'];
        }

        return (int) (
            ($report['images_materialized'] ?? $report['images_materialized_total'] ?? 0)
            + ($report['thumbs_materialized'] ?? $report['thumbs_materialized_total'] ?? 0)
            + ($report['videos_materialized'] ?? $report['videos_materialized_total'] ?? 0)
            + ($report['posters_materialized'] ?? $report['posters_materialized_total'] ?? 0)
        );
    }
}
