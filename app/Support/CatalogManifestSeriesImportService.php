<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CatalogManifestSeriesImportService
{
    public function __construct(
        protected CatalogManifestImportService $catalogManifestImportService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(
        string $path,
        bool $dryRun = false,
        ?int $fromChunk = null,
        ?int $toChunk = null,
        bool $continueOnError = false,
        ?string $resumeReportPath = null,
    ): array {
        $seriesDirectory = $this->resolveSeriesDirectory($path);
        $chunks = $this->discoverChunks($seriesDirectory, $fromChunk, $toChunk);
        $successfulManifestPaths = $this->successfulManifestPathsFromReport($resumeReportPath);
        $summary = $this->baseSummary(
            seriesDirectory: $seriesDirectory,
            dryRun: $dryRun,
            fromChunk: $fromChunk,
            toChunk: $toChunk,
            continueOnError: $continueOnError,
            chunksDiscovered: count($chunks),
            resumeReportPath: $resumeReportPath !== null
                ? $this->normalizeAbsolutePath($resumeReportPath)
                : null,
        );

        if ($chunks === []) {
            $summary['status'] = 'failed';
            $summary['stopped_reason'] = 'no_chunks_selected';
            $summary['errors'][] = [
                'path' => $seriesDirectory,
                'message' => 'Nie znaleziono zadnych chunkow z manifest.json do importu.',
            ];
            $summary['errors_count'] = 1;
            $summary['completed_at'] = now()->utc()->toIso8601String();

            return $summary;
        }

        foreach ($chunks as $chunk) {
            if (in_array($chunk['manifest_path'], $successfulManifestPaths, true)) {
                $summary['chunks'][] = [
                    'number' => $chunk['number'],
                    'directory' => $chunk['directory'],
                    'manifest_path' => $chunk['manifest_path'],
                    'status' => 'skipped',
                    'reason' => 'already_successful',
                    'rows_total' => 0,
                    'questions_total' => 0,
                    'media_total' => 0,
                    'asset_plan_total' => 0,
                    'uploaded_assets_total' => 0,
                    'errors_count' => 0,
                    'error' => null,
                ];
                $summary['chunks_skipped']++;

                continue;
            }

            $chunkEntry = [
                'number' => $chunk['number'],
                'directory' => $chunk['directory'],
                'manifest_path' => $chunk['manifest_path'],
                'status' => 'ok',
                'rows_total' => 0,
                'questions_total' => 0,
                'media_total' => 0,
                'asset_plan_total' => 0,
                'uploaded_assets_total' => 0,
                'errors_count' => 0,
                'error' => null,
            ];

            try {
                $report = $this->catalogManifestImportService->import($chunk['manifest_path'], $dryRun);
                $chunkEntry = array_merge($chunkEntry, [
                    'status' => ((int) ($report['errors_count'] ?? 0) > 0) ? 'failed' : 'ok',
                    'rows_total' => (int) ($report['rows_total'] ?? 0),
                    'questions_total' => (int) ($report['questions_total'] ?? 0),
                    'media_total' => (int) ($report['media_total'] ?? 0),
                    'asset_plan_total' => (int) ($report['asset_plan_total'] ?? 0),
                    'uploaded_assets_total' => (int) ($report['uploaded_assets_total'] ?? 0),
                    'errors_count' => (int) ($report['errors_count'] ?? 0),
                    'error_samples' => $this->errorSamples($report),
                ]);
            } catch (Throwable $throwable) {
                $chunkEntry['status'] = 'failed';
                $chunkEntry['errors_count'] = 1;
                $chunkEntry['error'] = $throwable->getMessage();
            }

            $summary['chunks'][] = $chunkEntry;
            $summary['chunks_selected']++;
            $summary['rows_total'] += (int) $chunkEntry['rows_total'];
            $summary['questions_total'] += (int) $chunkEntry['questions_total'];
            $summary['media_total'] += (int) $chunkEntry['media_total'];
            $summary['asset_plan_total'] += (int) $chunkEntry['asset_plan_total'];
            $summary['uploaded_assets_total'] += (int) $chunkEntry['uploaded_assets_total'];

            if ($chunkEntry['status'] === 'ok') {
                $summary['chunks_succeeded']++;

                continue;
            }

            $summary['status'] = 'failed';
            $summary['chunks_failed']++;
            $summary['errors'][] = [
                'path' => $chunk['manifest_path'],
                'message' => $chunkEntry['error'] ?? sprintf(
                    'Chunk %04d zawiera bledy importu (%d).',
                    $chunk['number'],
                    (int) $chunkEntry['errors_count'],
                ),
            ];

            if (! $continueOnError) {
                $summary['stopped_reason'] = 'chunk_failed';
                break;
            }
        }

        if ($summary['stopped_reason'] === null) {
            $summary['stopped_reason'] = $summary['status'] === 'ok'
                ? 'completed'
                : 'completed_with_errors';
        }

        $summary['errors_count'] = count($summary['errors']);
        $summary['completed_at'] = now()->utc()->toIso8601String();

        return $summary;
    }

    /**
     * @return array<int, array{number:int,directory:string,manifest_path:string}>
     */
    protected function discoverChunks(string $seriesDirectory, ?int $fromChunk, ?int $toChunk): array
    {
        $directories = collect(File::directories($seriesDirectory))
            ->filter(static fn (string $directory): bool => is_file($directory.DIRECTORY_SEPARATOR.'manifest.json'))
            ->map(function (string $directory): ?array {
                $name = basename($directory);

                if (! preg_match('/chunk-(\d+)/', $name, $matches)) {
                    return null;
                }

                return [
                    'number' => (int) $matches[1],
                    'directory' => $directory,
                    'manifest_path' => $directory.DIRECTORY_SEPARATOR.'manifest.json',
                ];
            })
            ->filter()
            ->sortBy('number')
            ->values();

        if ($fromChunk !== null) {
            $directories = $directories->filter(
                static fn (array $chunk): bool => $chunk['number'] >= $fromChunk,
            )->values();
        }

        if ($toChunk !== null) {
            $directories = $directories->filter(
                static fn (array $chunk): bool => $chunk['number'] <= $toChunk,
            )->values();
        }

        /** @var array<int, array{number:int,directory:string,manifest_path:string}> $result */
        $result = $directories->all();

        return $result;
    }

    /**
     * @return array<int, string>
     */
    protected function successfulManifestPathsFromReport(?string $resumeReportPath): array
    {
        if ($resumeReportPath === null || trim($resumeReportPath) === '') {
            return [];
        }

        $resolvedPath = $this->normalizeAbsolutePath($resumeReportPath);

        if (! is_file($resolvedPath)) {
            throw new RuntimeException(sprintf('Nie znaleziono raportu wznowienia: %s', $resolvedPath));
        }

        $report = json_decode((string) File::get($resolvedPath), true);

        if (! is_array($report)) {
            throw new RuntimeException('Raport wznowienia ma niepoprawny format JSON.');
        }

        return collect(is_array($report['chunks'] ?? null) ? $report['chunks'] : [])
            ->filter(static fn (mixed $chunk): bool => is_array($chunk))
            ->filter(static fn (array $chunk): bool => (string) ($chunk['status'] ?? '') === 'ok')
            ->map(static fn (array $chunk): ?string => isset($chunk['manifest_path']) && is_string($chunk['manifest_path'])
                ? $chunk['manifest_path']
                : null)
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveSeriesDirectory(string $path): string
    {
        $resolved = $this->normalizeAbsolutePath($path);

        if (is_file($resolved)) {
            if (basename($resolved) !== 'series-report.json') {
                throw new RuntimeException('Sciezka serii musi wskazywac katalog chunkow albo series-report.json.');
            }

            return dirname($resolved);
        }

        if (! is_dir($resolved)) {
            throw new RuntimeException(sprintf('Nie znaleziono katalogu serii: %s', $resolved));
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseSummary(
        string $seriesDirectory,
        bool $dryRun,
        ?int $fromChunk,
        ?int $toChunk,
        bool $continueOnError,
        int $chunksDiscovered,
        ?string $resumeReportPath,
    ): array {
        return [
            'series_id' => 'manifest-series-'.now()->utc()->format('Ymd-His'),
            'series_directory' => $seriesDirectory,
            'dry_run' => $dryRun,
            'from_chunk' => $fromChunk,
            'to_chunk' => $toChunk,
            'continue_on_error' => $continueOnError,
            'resume_report_path' => $resumeReportPath,
            'started_at' => now()->utc()->toIso8601String(),
            'completed_at' => null,
            'status' => 'ok',
            'chunks_discovered' => $chunksDiscovered,
            'chunks_selected' => 0,
            'chunks_succeeded' => 0,
            'chunks_failed' => 0,
            'chunks_skipped' => 0,
            'rows_total' => 0,
            'questions_total' => 0,
            'media_total' => 0,
            'asset_plan_total' => 0,
            'uploaded_assets_total' => 0,
            'errors_count' => 0,
            'errors' => [],
            'chunks' => [],
            'stopped_reason' => null,
        ];
    }

    protected function normalizeAbsolutePath(string $path): string
    {
        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, string>>
     */
    protected function errorSamples(array $report): array
    {
        $errors = is_array($report['errors'] ?? null) ? $report['errors'] : [];

        return array_values(array_map(
            static fn (array $error): array => array_filter([
                'path' => isset($error['path']) ? (string) $error['path'] : null,
                'message' => isset($error['message']) ? (string) $error['message'] : null,
            ], static fn (?string $value): bool => $value !== null && $value !== ''),
            array_slice(array_values(array_filter(
                $errors,
                static fn (mixed $error): bool => is_array($error),
            )), 0, 5),
        ));
    }
}
