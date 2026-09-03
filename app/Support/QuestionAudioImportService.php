<?php

namespace App\Support;

use App\Models\QuestionAudioAsset;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class QuestionAudioImportService
{
    public function __construct(
        private readonly QuestionAudioExportManifestBuilder $manifestBuilder,
    ) {}

    /**
     * @param  array{
     *     input?: string|null,
     *     disk?: string|null,
     *     prefix?: string|null,
     *     force?: bool,
     *     dry_run?: bool,
     *     sample_limit?: int
     * }  $options
     * @return array<string, mixed>
     */
    public function import(string $manifestPath, array $options = []): array
    {
        if (! File::exists($manifestPath)) {
            throw new RuntimeException("Manifest file does not exist: {$manifestPath}");
        }

        $payload = json_decode((string) File::get($manifestPath), true);

        if (! is_array($payload)) {
            throw new RuntimeException('Manifest has invalid JSON.');
        }

        $records = $this->records($payload);
        $expectedItems = $this->expectedItemsForRecords($records);
        $disk = (string) ($options['disk'] ?? config('media.question_audio_disk', 'media_local'));
        $prefix = trim((string) ($options['prefix'] ?? config('media.question_audio_prefix', 'audio/questions')), '/');
        $inputDirectory = trim((string) ($options['input'] ?? ''));
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $sampleLimit = max(1, (int) ($options['sample_limit'] ?? 20));
        $filesystem = Storage::disk($disk);
        $report = [
            'manifest_path' => $manifestPath,
            'input' => $inputDirectory,
            'disk' => $disk,
            'prefix' => $prefix,
            'force' => $force,
            'dry_run' => $dryRun,
            'records_total' => count($records),
            'valid_records' => 0,
            'imported_assets' => 0,
            'updated_assets' => 0,
            'skipped_existing_assets' => 0,
            'failed_records' => 0,
            'errors_count' => 0,
            'errors_sample' => [],
        ];

        foreach ($records as $record) {
            if (! is_array($record)) {
                $report['errors_count']++;
                $this->pushSample($report['errors_sample'], ['message' => 'Manifest record is not an object.'], $sampleLimit);

                continue;
            }

            try {
                $result = $this->importRecord(
                    $record,
                    $manifestPath,
                    $inputDirectory,
                    $expectedItems,
                    $filesystem,
                    $disk,
                    $prefix,
                    $force,
                    $dryRun,
                );

                $report[$result]++;
            } catch (Throwable $exception) {
                $status = (string) ($record['status'] ?? '');

                if ($status !== QuestionAudioAsset::STATUS_GENERATED) {
                    $report['failed_records']++;
                } else {
                    $report['errors_count']++;
                }

                $this->pushSample($report['errors_sample'], [
                    'asset_key' => $record['asset_key'] ?? null,
                    'external_id' => $record['external_id'] ?? null,
                    'message' => $exception->getMessage(),
                ], $sampleLimit);
            }
        }

        return $report;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, mixed>
     */
    private function records(array $payload): array
    {
        $records = $payload['items'] ?? $payload;

        return is_array($records) ? array_values($records) : [];
    }

    private function importRecord(
        array $record,
        string $manifestPath,
        string $inputDirectory,
        array $expectedItems,
        mixed $filesystem,
        string $disk,
        string $prefix,
        bool $force,
        bool $dryRun,
    ): string {
        if ((string) ($record['status'] ?? '') !== QuestionAudioAsset::STATUS_GENERATED) {
            throw new RuntimeException('Generator record is not generated.');
        }

        $assetKey = (string) ($record['asset_key'] ?? '');

        if ($assetKey === '') {
            throw new RuntimeException('Missing asset_key.');
        }

        $expected = $expectedItems[$assetKey] ?? null;

        if (! is_array($expected)) {
            throw new RuntimeException('Question is not available in current Laravel catalog.');
        }

        if ($assetKey === '' || $assetKey !== (string) ($expected['asset_key'] ?? '')) {
            throw new RuntimeException('asset_key does not match current Laravel manifest.');
        }

        if ((string) ($record['source_text_hash'] ?? '') !== (string) ($expected['source_text_hash'] ?? '')) {
            throw new RuntimeException('source_text_hash does not match current question text.');
        }

        $sourcePath = $this->resolveSourcePath($record, $manifestPath, $inputDirectory);

        if ($sourcePath === null || ! is_file($sourcePath)) {
            throw new RuntimeException('Audio file does not exist.');
        }

        $bytes = filesize($sourcePath);

        if ($bytes === false || $bytes <= 0) {
            throw new RuntimeException('Audio file is empty.');
        }

        $targetPath = (string) ($expected['target_storage_path'] ?? '');

        if ($targetPath === '') {
            $targetPath = trim($prefix, '/').'/'.basename($sourcePath);
        }

        $existing = QuestionAudioAsset::query()->where('asset_key', $assetKey)->first();

        if ($existing instanceof QuestionAudioAsset && ! $force && $existing->isGenerated() && $this->storageFileExists($existing)) {
            return 'skipped_existing_assets';
        }

        if ($dryRun) {
            return 'valid_records';
        }

        $stream = fopen($sourcePath, 'rb');

        if ($stream === false) {
            throw new RuntimeException("Cannot read audio file: {$sourcePath}");
        }

        try {
            $stored = $filesystem->put($targetPath, $stream);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new RuntimeException("Cannot write audio asset to storage: {$targetPath}");
        }

        $asset = QuestionAudioAsset::query()->updateOrCreate(
            ['asset_key' => $assetKey],
            [
                'question_id' => $expected['question_id'] ?? null,
                'external_id' => (string) $expected['external_id'],
                'category_code' => null,
                'content_scope' => (string) $expected['content_scope'],
                'audio_type' => (string) $expected['audio_type'],
                'locale' => (string) $expected['locale'],
                'source_text' => (string) $expected['source_text'],
                'source_text_hash' => (string) $expected['source_text_hash'],
                'storage_disk' => $disk,
                'storage_path' => $targetPath,
                'duration_seconds' => isset($record['duration_seconds']) && is_numeric($record['duration_seconds'])
                    ? round((float) $record['duration_seconds'], 3)
                    : null,
                'encoding_format' => (string) ($record['encoding_format'] ?? config('media.question_audio_encoding_format', 'audio/mpeg')),
                'bytes' => (int) $bytes,
                'checksum_sha256' => hash_file('sha256', $sourcePath) ?: null,
                'voice_provider' => (string) $expected['voice_provider'],
                'voice_id' => (string) $expected['voice_id'],
                'model_id' => (string) $expected['model_id'],
                'generation_version' => (string) $expected['generation_version'],
                'status' => QuestionAudioAsset::STATUS_GENERATED,
                'error_message' => null,
                'generated_at' => now(),
                'metadata' => [
                    'source_path' => $sourcePath,
                    'generator_file_path' => $record['file_path'] ?? null,
                    'category_codes' => $expected['category_codes'] ?? [],
                    'raw_external_ids' => $expected['raw_external_ids'] ?? [],
                ],
            ],
        );

        return $asset->wasRecentlyCreated ? 'imported_assets' : 'updated_assets';
    }

    /**
     * @param  array<int, mixed>  $records
     * @return array<string, array<string, mixed>>
     */
    private function expectedItemsForRecords(array $records): array
    {
        $groups = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $externalId = trim((string) ($record['external_id'] ?? ''));

            if ($externalId === '') {
                continue;
            }

            $options = [
                'question_scope' => (string) ($record['question_scope'] ?? 'all'),
                'types' => [(string) ($record['audio_type'] ?? QuestionAudioAsset::TYPE_QUESTION)],
                'locale' => (string) ($record['locale'] ?? config('media.question_audio_locale', 'pl-PL')),
                'voice_provider' => (string) ($record['voice_provider'] ?? config('media.question_audio_voice_provider', 'elevenlabs')),
                'voice_id' => (string) ($record['voice_id'] ?? config('media.question_audio_voice_id', '')),
                'model_id' => (string) ($record['model_id'] ?? config('media.question_audio_model_id', '')),
                'generation_version' => (string) ($record['generation_version'] ?? config('media.question_audio_generation_version', 'question-v1')),
            ];
            $groupKey = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (! is_string($groupKey)) {
                throw new RuntimeException('Cannot build expected manifest group key.');
            }

            $groups[$groupKey]['options'] = $options;
            $groups[$groupKey]['external_ids'][] = $externalId;
        }

        $expectedItems = [];

        foreach ($groups as $group) {
            $manifest = $this->manifestBuilder->build([
                ...$group['options'],
                'external_ids' => array_values(array_unique($group['external_ids'] ?? [])),
            ]);

            foreach ($manifest['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $assetKey = (string) ($item['asset_key'] ?? '');

                if ($assetKey !== '') {
                    $expectedItems[$assetKey] = $item;
                }
            }
        }

        return $expectedItems;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveSourcePath(array $record, string $manifestPath, string $inputDirectory): ?string
    {
        $filePath = trim((string) ($record['file_path'] ?? ''));
        $basename = $filePath !== '' ? basename(str_replace('\\', '/', $filePath)) : '';
        $externalId = trim((string) ($record['external_id'] ?? ''));
        $safeExternalId = $this->safePathPart($externalId);
        $candidates = [];

        if ($filePath !== '') {
            $normalizedFilePath = $this->normalizePath($filePath);

            if ($this->isAbsolutePath($normalizedFilePath)) {
                $candidates[] = $normalizedFilePath;
            }

            if ($inputDirectory !== '') {
                $normalizedInput = rtrim($this->normalizePath($inputDirectory), DIRECTORY_SEPARATOR);
                $candidates[] = $normalizedInput.DIRECTORY_SEPARATOR.$normalizedFilePath;

                if ($basename !== '') {
                    $candidates[] = $normalizedInput.DIRECTORY_SEPARATOR.$externalId.DIRECTORY_SEPARATOR.$basename;
                    $candidates[] = $normalizedInput.DIRECTORY_SEPARATOR.$safeExternalId.DIRECTORY_SEPARATOR.$basename;
                    $candidates[] = $normalizedInput.DIRECTORY_SEPARATOR.$basename;
                }

                $generatorRoot = dirname(dirname($normalizedInput));
                $candidates[] = $generatorRoot.DIRECTORY_SEPARATOR.$normalizedFilePath;
            }

            $manifestDirectory = dirname($manifestPath);
            $candidates[] = $manifestDirectory.DIRECTORY_SEPARATOR.$normalizedFilePath;
            $candidates[] = dirname($manifestDirectory).DIRECTORY_SEPARATOR.$normalizedFilePath;
        }

        $targetFileName = trim((string) ($record['target_file_name'] ?? ''));

        if ($targetFileName !== '' && $inputDirectory !== '') {
            $normalizedInput = rtrim($this->normalizePath($inputDirectory), DIRECTORY_SEPARATOR);
            $candidates[] = $normalizedInput.DIRECTORY_SEPARATOR.$externalId.DIRECTORY_SEPARATOR.$targetFileName;
            $candidates[] = $normalizedInput.DIRECTORY_SEPARATOR.$safeExternalId.DIRECTORY_SEPARATOR.$targetFileName;
            $candidates[] = $normalizedInput.DIRECTORY_SEPARATOR.$targetFileName;
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', $path) === 1;
    }

    private function safePathPart(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'audio';
        }

        $safe = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $value) ?: 'audio';
        $safe = trim($safe, '.-');

        return $safe !== '' ? $safe : 'audio';
    }

    private function storageFileExists(QuestionAudioAsset $asset): bool
    {
        if (! filled($asset->storage_disk) || ! filled($asset->storage_path)) {
            return false;
        }

        try {
            return Storage::disk((string) $asset->storage_disk)->exists((string) $asset->storage_path);
        } catch (Throwable) {
            return false;
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
