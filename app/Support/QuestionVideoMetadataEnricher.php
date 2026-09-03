<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class QuestionVideoMetadataEnricher
{
    public function __construct(
        protected VideoMetadataProbe $probe,
    ) {}

    /**
     * @param  array{
     *     write?: bool,
     *     force?: bool,
     *     public_only?: bool,
     *     limit?: int|null,
     *     media_id?: int|null,
     *     question_id?: int|null,
     *     disk?: string|null,
     *     sample_limit?: int,
     *     probe_timeout?: int
     * }  $options
     * @return array<string, mixed>
     */
    public function enrich(array $options = []): array
    {
        $write = (bool) ($options['write'] ?? false);
        $force = (bool) ($options['force'] ?? false);
        $publicOnly = (bool) ($options['public_only'] ?? true);
        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : null;
        $sampleLimit = max(1, (int) ($options['sample_limit'] ?? 20));
        $probeTimeout = max(1, (int) ($options['probe_timeout'] ?? 20));

        $report = [
            'mode' => $write ? 'write' : 'preview',
            'generated_at' => now()->toIso8601String(),
            'public_only' => $publicOnly,
            'force' => $force,
            'limit' => $limit,
            'candidate_media' => 0,
            'probed_media' => 0,
            'updated_media' => 0,
            'would_update_media' => 0,
            'skipped_no_changes' => 0,
            'skipped_external_url' => 0,
            'skipped_missing_file' => 0,
            'skipped_unconfigured_disk' => 0,
            'probe_failed' => 0,
            'errors_count' => 0,
            'field_updates' => [],
            'updated_sample' => [],
            'skipped_sample' => [],
            'errors_sample' => [],
        ];

        $query = $this->query($publicOnly, $force);

        if (isset($options['media_id'])) {
            $query->whereKey((int) $options['media_id']);
        }

        if (isset($options['question_id'])) {
            $query->where('question_id', (int) $options['question_id']);
        }

        if (filled($options['disk'] ?? null)) {
            $query->where('disk', (string) $options['disk']);
        }

        foreach ($query->lazyById() as $media) {
            if ($limit !== null && $report['candidate_media'] >= $limit) {
                break;
            }

            $report['candidate_media']++;
            $this->enrichMedia($media, $report, $write, $force, $sampleLimit, $probeTimeout);
        }

        return $report;
    }

    /**
     * @return Builder<QuestionMedia>
     */
    protected function query(bool $publicOnly, bool $force): Builder
    {
        $query = QuestionMedia::query()
            ->where('kind', 'video')
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->orderBy('id');

        if (! $force) {
            $query->where(function (Builder $query): void {
                $query
                    ->whereNull('duration_seconds')
                    ->orWhere('duration_seconds', '<=', 0)
                    ->orWhereNull('width')
                    ->orWhere('width', '<=', 0)
                    ->orWhereNull('height')
                    ->orWhere('height', '<=', 0)
                    ->orWhereNull('bytes')
                    ->orWhere('bytes', '<=', 0)
                    ->orWhereNull('mime_type')
                    ->orWhere('mime_type', '');
            });
        }

        if ($publicOnly) {
            $query->whereHas('question', fn (Builder $query) => $this->applyPublicQuestionScope($query));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function enrichMedia(QuestionMedia $media, array &$report, bool $write, bool $force, int $sampleLimit, int $probeTimeout): void
    {
        $context = $this->context($media);
        $path = trim((string) $media->path);

        if ($this->isExternalUrl($path)) {
            $report['skipped_external_url']++;
            $this->pushSample($report['skipped_sample'], $context + ['reason' => 'external_url'], $sampleLimit);

            return;
        }

        $disk = trim((string) $media->disk);

        if ($disk === '' || ! is_array(config("filesystems.disks.{$disk}"))) {
            $report['skipped_unconfigured_disk']++;
            $this->pushSample($report['skipped_sample'], $context + ['reason' => 'unconfigured_disk'], $sampleLimit);

            return;
        }

        try {
            $filesystem = Storage::disk($disk);

            if (! $filesystem->exists($path)) {
                $report['skipped_missing_file']++;
                $this->pushSample($report['skipped_sample'], $context + ['reason' => 'missing_file'], $sampleLimit);

                return;
            }

            $bytes = $this->fileSize($disk, $path);
            $tempFiles = [];
            $sourcePath = $this->probeSourcePath($disk, $path, $tempFiles);

            try {
                $probeResult = $this->probe->probe($sourcePath, $probeTimeout);
            } finally {
                $this->deleteTempFiles($tempFiles);
            }

            $report['probed_media']++;

            if (
                filled($probeResult['error'] ?? null)
                || (
                    ($probeResult['duration_seconds'] ?? null) === null
                    && ($probeResult['width'] ?? null) === null
                    && ($probeResult['height'] ?? null) === null
                )
            ) {
                $report['probe_failed']++;
                $this->pushSample($report['errors_sample'], $context + [
                    'reason' => 'probe_failed',
                    'error' => $probeResult['error'] ?? 'ffprobe did not return video metadata.',
                ], $sampleLimit);

                return;
            }

            [$updates, $fields] = $this->updatesFor($media, $probeResult, $bytes, $force);

            if ($updates === []) {
                $report['skipped_no_changes']++;

                return;
            }

            foreach ($fields as $field) {
                $report['field_updates'][$field] = (int) ($report['field_updates'][$field] ?? 0) + 1;
            }

            if ($write) {
                $media->forceFill($updates)->save();
                $report['updated_media']++;
            } else {
                $report['would_update_media']++;
            }

            $this->pushSample($report['updated_sample'], $context + [
                'fields' => $fields,
                'duration_seconds' => $updates['duration_seconds'] ?? $media->duration_seconds,
                'width' => $updates['width'] ?? $media->width,
                'height' => $updates['height'] ?? $media->height,
                'bytes' => $updates['bytes'] ?? $media->bytes,
            ], $sampleLimit);
        } catch (Throwable $exception) {
            $report['errors_count']++;
            $this->pushSample($report['errors_sample'], $context + [
                'reason' => 'exception',
                'error' => $exception->getMessage(),
            ], $sampleLimit);
        }
    }

    /**
     * @param  Builder<Question>  $query
     * @return Builder<Question>
     */
    protected function applyPublicQuestionScope(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereHas('licenseCategory', fn (Builder $categoryQuery) => $categoryQuery->where('is_active', true));
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    protected function updatesFor(QuestionMedia $media, array $probeResult, ?int $bytes, bool $force): array
    {
        $updates = [];
        $fields = [];

        if (($force || $this->missingPositiveInteger($media->duration_seconds)) && isset($probeResult['duration_seconds'])) {
            $updates['duration_seconds'] = (int) $probeResult['duration_seconds'];
            $fields[] = 'duration_seconds';
        }

        if (($force || $this->missingPositiveInteger($media->width)) && isset($probeResult['width'])) {
            $updates['width'] = (int) $probeResult['width'];
            $fields[] = 'width';
        }

        if (($force || $this->missingPositiveInteger($media->height)) && isset($probeResult['height'])) {
            $updates['height'] = (int) $probeResult['height'];
            $fields[] = 'height';
        }

        if (($force || $this->missingPositiveInteger($media->bytes)) && $bytes !== null) {
            $updates['bytes'] = $bytes;
            $fields[] = 'bytes';
        }

        if (($force || blank($media->mime_type)) && $this->pathLooksLikeMp4((string) $media->path)) {
            $updates['mime_type'] = 'video/mp4';
            $fields[] = 'mime_type';
        }

        if ($updates !== []) {
            $metadata = is_array($media->metadata) ? $media->metadata : [];
            $metadata['video_metadata_enrichment'] = [
                'source' => $probeResult['source'] ?? 'unknown',
                'enriched_at' => now()->toIso8601String(),
                'fields' => $fields,
            ];
            $updates['metadata'] = $metadata;
        }

        return [$updates, $fields];
    }

    protected function probeSourcePath(string $disk, string $path, array &$tempFiles): string
    {
        $diskConfig = (array) config("filesystems.disks.{$disk}", []);

        if (($diskConfig['driver'] ?? null) === 'local') {
            return Storage::disk($disk)->path($path);
        }

        $stream = Storage::disk($disk)->readStream($path);

        if ($stream === false) {
            throw new \RuntimeException("Cannot read video stream from {$disk}:{$path}");
        }

        $directory = storage_path('app/tmp/question-video-metadata');
        File::ensureDirectoryExists($directory);

        $tempPath = tempnam($directory, 'probe-');

        if ($tempPath === false) {
            throw new \RuntimeException('Cannot create temporary file for video metadata probe.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension !== '') {
            $targetPath = $tempPath.'.'.$extension;
            File::move($tempPath, $targetPath);
            $tempPath = $targetPath;
        }

        $target = fopen($tempPath, 'wb');

        if ($target === false) {
            fclose($stream);
            File::delete($tempPath);

            throw new \RuntimeException('Cannot open temporary file for video metadata probe.');
        }

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($stream);
            fclose($target);
        }

        $tempFiles[] = $tempPath;

        return $tempPath;
    }

    protected function fileSize(string $disk, string $path): ?int
    {
        try {
            $size = Storage::disk($disk)->size($path);

            return $size > 0 ? (int) $size : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $tempFiles
     */
    protected function deleteTempFiles(array $tempFiles): void
    {
        foreach ($tempFiles as $tempFile) {
            File::delete($tempFile);
        }
    }

    protected function missingPositiveInteger(mixed $value): bool
    {
        return $value === null || (int) $value <= 0;
    }

    protected function pathLooksLikeMp4(string $path): bool
    {
        return str_ends_with(strtolower((string) parse_url($path, PHP_URL_PATH)), '.mp4');
    }

    protected function isExternalUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    /**
     * @return array<string, mixed>
     */
    protected function context(QuestionMedia $media): array
    {
        return [
            'id' => $media->getKey(),
            'question_id' => $media->question_id,
            'disk' => $media->disk,
            'path' => $media->path,
        ];
    }

    /**
     * @param  array<int, mixed>  $sample
     */
    protected function pushSample(array &$sample, mixed $value, int $limit): void
    {
        if (count($sample) >= $limit) {
            return;
        }

        $sample[] = $value;
    }
}
