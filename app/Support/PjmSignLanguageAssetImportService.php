<?php

namespace App\Support;

use App\Models\QuestionSignLanguageAsset;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Throwable;

class PjmSignLanguageAssetImportService
{
    public function __construct(
        private readonly PjmSignLanguageFilenameParser $parser,
    ) {}

    /**
     * @param  array{
     *     disk?: string,
     *     prefix?: string,
     *     variant?: string,
     *     processing_profile?: string|null,
     *     limit?: int|null,
     *     force?: bool,
     *     review_report?: string|null,
     *     sample_limit?: int
     * }  $options
     * @return array<string, mixed>
     */
    public function import(string $sourcePath, array $options = []): array
    {
        $sourcePath = rtrim($sourcePath, DIRECTORY_SEPARATOR);
        $disk = (string) ($options['disk'] ?? config('media.pjm_sign_language_disk', 'media_local'));
        $prefix = trim((string) ($options['prefix'] ?? config('media.pjm_sign_language_prefix', 'pjm/sign-language')), '/');
        $variant = (string) ($options['variant'] ?? QuestionSignLanguageAsset::VARIANT_STANDARD);
        $processingProfile = $options['processing_profile'] ?? 'safe-center-crop-80';
        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : null;
        $force = (bool) ($options['force'] ?? false);
        $sampleLimit = max(1, (int) ($options['sample_limit'] ?? 20));
        $reviewKeys = $this->reviewKeys((string) ($options['review_report'] ?? ''));
        $filesystem = Storage::disk($disk);

        $report = [
            'source_path' => $sourcePath,
            'dry_run' => false,
            'generated_at' => now()->toIso8601String(),
            'disk' => $disk,
            'prefix' => $prefix,
            'variant' => $variant,
            'processing_profile' => $processingProfile,
            'limit' => $limit,
            'force' => $force,
            'total_files_seen' => 0,
            'parsed_files' => 0,
            'invalid_files_count' => 0,
            'invalid_files_sample' => [],
            'imported_assets' => 0,
            'updated_assets' => 0,
            'skipped_existing_assets' => 0,
            'linked_existing_files' => 0,
            'review_required_assets' => 0,
            'errors_count' => 0,
            'errors_sample' => [],
        ];

        foreach ($this->files($sourcePath) as $file) {
            $report['total_files_seen']++;
            $parsed = $this->parser->parse($file->getFilename());

            if ($parsed === null) {
                $report['invalid_files_count']++;
                $this->pushSample($report['invalid_files_sample'], $file->getRelativePathname(), $sampleLimit);
                continue;
            }

            if ($limit !== null && $report['parsed_files'] >= $limit) {
                break;
            }

            $report['parsed_files']++;

            try {
                $result = $this->importFile(
                    $file->getPathname(),
                    $file->getFilename(),
                    $parsed,
                    $filesystem,
                    $disk,
                    $prefix,
                    $variant,
                    $processingProfile,
                    $force,
                    $reviewKeys,
                );

                $report[$result['status']]++;

                if ($result['review_required']) {
                    $report['review_required_assets']++;
                }
            } catch (Throwable $exception) {
                $report['errors_count']++;
                $this->pushSample($report['errors_sample'], [
                    'file' => $file->getRelativePathname(),
                    'error' => $exception->getMessage(),
                ], $sampleLimit);
            }
        }

        return $report;
    }

    /**
     * @param  array{external_id: string, asset_role: string, extension: string}  $parsed
     * @param  array<string, bool>  $reviewKeys
     * @return array{status: string, review_required: bool}
     */
    private function importFile(
        string $sourcePath,
        string $filename,
        array $parsed,
        mixed $filesystem,
        string $disk,
        string $prefix,
        string $variant,
        ?string $processingProfile,
        bool $force,
        array $reviewKeys,
    ): array {
        $targetPath = $this->targetPath($prefix, $variant, $filename);
        $existing = QuestionSignLanguageAsset::query()
            ->where('external_id', $parsed['external_id'])
            ->where('asset_role', $parsed['asset_role'])
            ->where('variant', $variant)
            ->first();

        if ($existing !== null && ! $force) {
            return [
                'status' => 'skipped_existing_assets',
                'review_required' => (bool) $existing->review_required,
            ];
        }

        $copied = false;

        if (! $filesystem->exists($targetPath) || $force) {
            $stream = fopen($sourcePath, 'rb');

            if ($stream === false) {
                throw new \RuntimeException("Cannot read source file: {$sourcePath}");
            }

            try {
                $stored = $filesystem->put($targetPath, $stream);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw new \RuntimeException("Cannot write PJM asset to storage: {$targetPath}");
            }

            $copied = true;
        }

        $reviewRequired = isset($reviewKeys[$parsed['external_id'].'|'.$parsed['asset_role']]);
        $status = $reviewRequired
            ? QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED
            : QuestionSignLanguageAsset::STATUS_READY;
        $videoMetadata = $this->videoMetadata($sourcePath);

        try {
            $asset = QuestionSignLanguageAsset::query()->updateOrCreate(
                [
                    'external_id' => $parsed['external_id'],
                    'asset_role' => $parsed['asset_role'],
                    'variant' => $variant,
                ],
                [
                    'disk' => $disk,
                    'path' => $targetPath,
                    'source_filename' => $filename,
                    'source_path' => $sourcePath,
                    'mime_type' => 'video/mp4',
                    'bytes' => filesize($sourcePath) ?: null,
                    'duration_seconds' => $videoMetadata['duration_seconds'],
                    'width' => $videoMetadata['width'],
                    'height' => $videoMetadata['height'],
                    'processing_profile' => $processingProfile,
                    'processing_status' => $status,
                    'is_active' => true,
                    'review_required' => $reviewRequired,
                    'checksum_sha256' => hash_file('sha256', $sourcePath) ?: null,
                    'metadata' => [
                        'source_extension' => $parsed['extension'],
                        'video_metadata_source' => $videoMetadata['source'],
                    ],
                ],
            );
        } catch (Throwable $exception) {
            if ($copied) {
                $filesystem->delete($targetPath);
            }

            throw $exception;
        }

        if (! $copied && $existing === null) {
            return [
                'status' => 'linked_existing_files',
                'review_required' => $reviewRequired,
            ];
        }

        return [
            'status' => $asset->wasRecentlyCreated ? 'imported_assets' : 'updated_assets',
            'review_required' => $reviewRequired,
        ];
    }

    private function targetPath(string $prefix, string $variant, string $filename): string
    {
        return trim($prefix, '/').'/'.trim($variant, '/').'/'.$filename;
    }

    /**
     * @return array{duration_seconds: float|null, width: int|null, height: int|null, source: string|null}
     */
    private function videoMetadata(string $sourcePath): array
    {
        $empty = [
            'duration_seconds' => null,
            'width' => null,
            'height' => null,
            'source' => null,
        ];

        $binary = (string) config('media.ffprobe_binary', 'ffprobe');

        if ($binary === '') {
            return $empty;
        }

        try {
            $process = new Process([
                $binary,
                '-v',
                'error',
                '-select_streams',
                'v:0',
                '-show_entries',
                'stream=width,height,duration',
                '-of',
                'json',
                $sourcePath,
            ]);
            $process->setTimeout(15);
            $process->run();

            if (! $process->isSuccessful()) {
                return $empty;
            }

            $payload = json_decode($process->getOutput(), true);

            if (! is_array($payload) || ! is_array($payload['streams'][0] ?? null)) {
                return $empty;
            }

            $stream = $payload['streams'][0];

            return [
                'duration_seconds' => isset($stream['duration']) ? round((float) $stream['duration'], 3) : null,
                'width' => isset($stream['width']) ? (int) $stream['width'] : null,
                'height' => isset($stream['height']) ? (int) $stream['height'] : null,
                'source' => 'ffprobe',
            ];
        } catch (Throwable) {
            return $empty;
        }
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
     * @return array<string, bool>
     */
    private function reviewKeys(string $reviewReportPath): array
    {
        if ($reviewReportPath === '' || ! is_file($reviewReportPath)) {
            return [];
        }

        $handle = fopen($reviewReportPath, 'rb');

        if ($handle === false) {
            return [];
        }

        try {
            $headers = fgetcsv($handle);

            if (! is_array($headers)) {
                return [];
            }

            $headers = array_map(fn ($header): string => (string) $header, $headers);
            $keys = [];

            while (($row = fgetcsv($handle)) !== false) {
                $record = array_combine($headers, $row);

                if (! is_array($record)) {
                    continue;
                }

                $externalId = (string) ($record['external_id'] ?? '');
                $assetRole = (string) ($record['asset_role'] ?? '');

                if ($externalId === '' || $assetRole === '') {
                    continue;
                }

                $keys[$externalId.'|'.$assetRole] = true;
            }

            return $keys;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, mixed>  $sample
     */
    private function pushSample(array &$sample, mixed $value, int $limit): void
    {
        if (count($sample) >= $limit) {
            return;
        }

        $sample[] = $value;
    }
}
