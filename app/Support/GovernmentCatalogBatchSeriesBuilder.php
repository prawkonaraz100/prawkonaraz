<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GovernmentCatalogBatchSeriesBuilder
{
    public function __construct(
        protected GovernmentCatalogBatchBuilder $batchBuilder,
    ) {}

    /**
     * @param  array<int, string>  $mediaSources
     * @param  array<int, string>  $categoryFilter
     * @return array<string, mixed>
     */
    public function build(
        string $xlsxPath,
        array $mediaSources,
        string $outputDirectory,
        bool $includeVerification = false,
        bool $materializeMedia = false,
        array $categoryFilter = [],
        bool $readyOnly = false,
        int $chunkSize = 100,
        int $sourceOffset = 0,
        ?int $maxChunks = null,
        bool $skipExisting = false,
        bool $allowMissingMedia = false,
    ): array {
        $chunkSize = max($chunkSize, 1);
        $sourceOffset = max($sourceOffset, 0);
        $outputDirectory = $this->normalizeAbsolutePath($outputDirectory);

        File::ensureDirectoryExists($outputDirectory);

        $summary = [
            'status' => 'ok',
            'series_id' => 'gov-pl-series-'.now()->utc()->format('Ymd-His'),
            'xlsx_path' => $this->normalizeAbsolutePath($xlsxPath),
            'output_directory' => $outputDirectory,
            'chunk_size' => $chunkSize,
            'source_offset_start' => $sourceOffset,
            'max_chunks' => $maxChunks,
            'include_verification' => $includeVerification,
            'materialize_media' => $materializeMedia,
            'ready_only' => $readyOnly,
            'skip_existing' => $skipExisting,
            'allow_missing_media' => $allowMissingMedia,
            'selected_categories' => $categoryFilter,
            'chunks_total' => 0,
            'chunks_reused' => 0,
            'chunks_built' => 0,
            'source_rows_considered_total' => 0,
            'rows_prepared_total' => 0,
            'question_rows_total' => 0,
            'images_materialized_total' => 0,
            'thumbs_materialized_total' => 0,
            'videos_materialized_total' => 0,
            'posters_materialized_total' => 0,
            'rows_skipped_by_category_total' => 0,
            'rows_skipped_not_ready_total' => 0,
            'warnings_total' => 0,
            'errors_total' => 0,
            'chunks' => [],
            'stopped_reason' => null,
        ];

        $currentOffset = $sourceOffset;

        while (true) {
            if ($maxChunks !== null && $summary['chunks_total'] >= $maxChunks) {
                $summary['stopped_reason'] = 'max_chunks_reached';

                break;
            }

            $chunkNumber = $summary['chunks_total'] + 1;
            $chunkDirectory = sprintf(
                '%s/chunk-%04d',
                rtrim(str_replace('\\', '/', $outputDirectory), '/'),
                $chunkNumber,
            );

            if ($skipExisting) {
                $existingReport = $this->existingChunkReport($chunkDirectory, $currentOffset);

                if ($existingReport !== null) {
                    $this->appendChunkToSummary(
                        summary: $summary,
                        report: $existingReport,
                        chunkNumber: $chunkNumber,
                        chunkDirectory: $chunkDirectory,
                        reused: true,
                    );

                    $sourceRowsConsidered = (int) ($existingReport['source_rows_considered'] ?? 0);

                    if ($sourceRowsConsidered === 0) {
                        $summary['stopped_reason'] = $summary['chunks_total'] === 1
                            ? 'no_source_rows'
                            : 'source_exhausted';

                        break;
                    }

                    $currentOffset += $sourceRowsConsidered;

                    if ($sourceRowsConsidered < $chunkSize) {
                        $summary['stopped_reason'] = 'source_exhausted';

                        break;
                    }

                    continue;
                }
            }

            $report = $this->batchBuilder->build(
                xlsxPath: $xlsxPath,
                mediaSources: $mediaSources,
                outputDirectory: $chunkDirectory,
                includeVerification: $includeVerification,
                materializeMedia: $materializeMedia,
                categoryFilter: $categoryFilter,
                readyOnly: $readyOnly,
                sourceOffset: $currentOffset,
                sourceLimit: $chunkSize,
                allowMissingMedia: $allowMissingMedia,
            );

            File::put(
                $chunkDirectory.'/report.json',
                json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            );

            $this->appendChunkToSummary(
                summary: $summary,
                report: $report,
                chunkNumber: $chunkNumber,
                chunkDirectory: $chunkDirectory,
                reused: false,
            );

            $sourceRowsConsidered = (int) ($report['source_rows_considered'] ?? 0);

            if ((string) ($report['status'] ?? 'ok') === 'failed') {
                $summary['status'] = 'failed';
                $summary['stopped_reason'] = 'chunk_failed';

                break;
            }

            if ($sourceRowsConsidered === 0) {
                $summary['stopped_reason'] = $summary['chunks_total'] === 1
                    ? 'no_source_rows'
                    : 'source_exhausted';

                break;
            }

            $currentOffset += $sourceRowsConsidered;

            if ($sourceRowsConsidered < $chunkSize) {
                $summary['stopped_reason'] = 'source_exhausted';

                break;
            }
        }

        if ($summary['stopped_reason'] === null) {
            $summary['stopped_reason'] = 'completed';
        }

        File::put(
            $outputDirectory.'/series-report.json',
            json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        return $summary;
    }

    protected function normalizeAbsolutePath(string $path): string
    {
        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $report
     */
    protected function appendChunkToSummary(
        array &$summary,
        array $report,
        int $chunkNumber,
        string $chunkDirectory,
        bool $reused,
    ): void {
        $summary['chunks_total']++;
        $summary['chunks_reused'] += $reused ? 1 : 0;
        $summary['chunks_built'] += $reused ? 0 : 1;
        $summary['source_rows_considered_total'] += (int) ($report['source_rows_considered'] ?? 0);
        $summary['rows_prepared_total'] += (int) ($report['rows_prepared'] ?? 0);
        $summary['question_rows_total'] += (int) ($report['question_rows_total'] ?? 0);
        $summary['images_materialized_total'] += (int) ($report['images_materialized'] ?? 0);
        $summary['thumbs_materialized_total'] += (int) ($report['thumbs_materialized'] ?? 0);
        $summary['videos_materialized_total'] += (int) ($report['videos_materialized'] ?? 0);
        $summary['posters_materialized_total'] += (int) ($report['posters_materialized'] ?? 0);
        $summary['rows_skipped_by_category_total'] += (int) ($report['rows_skipped_by_category'] ?? 0);
        $summary['rows_skipped_not_ready_total'] += (int) ($report['rows_skipped_not_ready'] ?? 0);
        $summary['warnings_total'] += count((array) ($report['warnings'] ?? []));
        $summary['errors_total'] += (int) ($report['errors_count'] ?? 0);
        $summary['chunks'][] = [
            'number' => $chunkNumber,
            'directory' => $chunkDirectory,
            'status' => $reused ? 'reused' : (string) ($report['status'] ?? 'ok'),
            'reused' => $reused,
            'source_offset' => (int) ($report['source_offset'] ?? 0),
            'source_limit' => $report['source_limit'] ?? null,
            'source_rows_considered' => (int) ($report['source_rows_considered'] ?? 0),
            'rows_prepared' => (int) ($report['rows_prepared'] ?? 0),
            'question_rows_total' => (int) ($report['question_rows_total'] ?? 0),
            'images_materialized' => (int) ($report['images_materialized'] ?? 0),
            'thumbs_materialized' => (int) ($report['thumbs_materialized'] ?? 0),
            'videos_materialized' => (int) ($report['videos_materialized'] ?? 0),
            'errors_count' => (int) ($report['errors_count'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function existingChunkReport(string $chunkDirectory, int $expectedOffset): ?array
    {
        $reportPath = $chunkDirectory.'/report.json';

        if (! is_file($reportPath)) {
            return null;
        }

        $report = json_decode((string) File::get($reportPath), true);

        if (! is_array($report)) {
            return null;
        }

        if ((string) ($report['status'] ?? 'failed') !== 'ok') {
            return null;
        }

        if ((int) ($report['source_offset'] ?? -1) !== $expectedOffset) {
            return null;
        }

        if (! is_file($chunkDirectory.'/manifest.json') || ! is_file($chunkDirectory.'/questions.csv')) {
            return null;
        }

        return $report;
    }
}
