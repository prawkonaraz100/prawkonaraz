<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class QuestionVideoSeoDescriptionImporter
{
    protected const MAX_DESCRIPTION_CHARS = 2048;

    /**
     * @param  array{
     *     write?: bool,
     *     overwrite?: bool,
     *     include_drafts?: bool,
     *     public_only?: bool,
     *     limit?: int|null,
     *     sample_limit?: int
     * }  $options
     * @return array<string, mixed>
     */
    public function import(string $path, array $options = []): array
    {
        $write = (bool) ($options['write'] ?? false);
        $overwrite = (bool) ($options['overwrite'] ?? false);
        $includeDrafts = (bool) ($options['include_drafts'] ?? false);
        $publicOnly = (bool) ($options['public_only'] ?? true);
        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : null;
        $sampleLimit = max(1, (int) ($options['sample_limit'] ?? 20));
        $rows = $this->readRows($path);

        $report = [
            'mode' => $write ? 'write' : 'preview',
            'generated_at' => now()->toIso8601String(),
            'path' => $path,
            'target_table' => 'question_media',
            'target_column' => 'seo_video_description',
            'public_only' => $publicOnly,
            'overwrite' => $overwrite,
            'include_drafts' => $includeDrafts,
            'input_rows' => count($rows),
            'processed_rows' => 0,
            'accepted_rows' => 0,
            'skipped_status' => 0,
            'invalid_rows' => 0,
            'unmatched_rows' => 0,
            'matched_media' => 0,
            'updated_media' => 0,
            'would_update_media' => 0,
            'unchanged_media' => 0,
            'skipped_existing_media' => 0,
            'invalid_sample' => [],
            'unmatched_sample' => [],
            'updated_sample' => [],
            'skipped_existing_sample' => [],
        ];

        foreach ($rows as $row) {
            if ($limit !== null && $report['processed_rows'] >= $limit) {
                break;
            }

            $report['processed_rows']++;
            $this->importRow($row, $report, $write, $overwrite, $includeDrafts, $publicOnly, $sampleLimit);
        }

        return $report;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $report
     */
    protected function importRow(
        array $row,
        array &$report,
        bool $write,
        bool $overwrite,
        bool $includeDrafts,
        bool $publicOnly,
        int $sampleLimit,
    ): void {
        $externalId = trim((string) ($row['external_id'] ?? ''));
        $status = Str::lower(trim((string) ($row['editorial_status'] ?? '')));
        $description = Str::squish((string) ($row['proposed_video_description'] ?? ''));
        $context = [
            'external_id' => $externalId,
            'editorial_status' => $status,
        ];

        if ($externalId === '') {
            $report['invalid_rows']++;
            $this->pushSample($report['invalid_sample'], $context + ['reason' => 'missing_external_id'], $sampleLimit);

            return;
        }

        if (! $includeDrafts && $status !== 'accepted') {
            $report['skipped_status']++;

            return;
        }

        $qualityError = $this->qualityError($description);

        if ($qualityError !== null) {
            $report['invalid_rows']++;
            $this->pushSample($report['invalid_sample'], $context + ['reason' => $qualityError], $sampleLimit);

            return;
        }

        $report['accepted_rows']++;
        $mediaRows = $this->mediaQuery($externalId, $publicOnly)->get();

        if ($mediaRows->isEmpty()) {
            $report['unmatched_rows']++;
            $this->pushSample($report['unmatched_sample'], $context + ['reason' => 'no_matching_video_media'], $sampleLimit);

            return;
        }

        foreach ($mediaRows as $media) {
            $report['matched_media']++;
            $existing = Str::squish((string) $media->seo_video_description);
            $mediaContext = $context + [
                'media_id' => $media->getKey(),
                'question_id' => $media->question_id,
            ];

            if ($existing === $description) {
                $report['unchanged_media']++;

                continue;
            }

            if ($existing !== '' && ! $overwrite) {
                $report['skipped_existing_media']++;
                $this->pushSample($report['skipped_existing_sample'], $mediaContext + ['reason' => 'existing_description'], $sampleLimit);

                continue;
            }

            if ($write) {
                $media->forceFill([
                    'seo_video_description' => $description,
                ])->save();

                $report['updated_media']++;
            } else {
                $report['would_update_media']++;
            }

            $this->pushSample($report['updated_sample'], $mediaContext, $sampleLimit);
        }
    }

    /**
     * @return Builder<QuestionMedia>
     */
    protected function mediaQuery(string $externalId, bool $publicOnly): Builder
    {
        $query = QuestionMedia::query()
            ->where('kind', 'video')
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->where(function (Builder $query): void {
                $query
                    ->where('variant', 'full')
                    ->orWhereNull('variant')
                    ->orWhere('variant', '');
            })
            ->whereHas('question', function (Builder $query) use ($externalId, $publicOnly): void {
                $query->where('external_id', $externalId);

                if ($publicOnly) {
                    $this->applyPublicQuestionScope($query);
                }
            })
            ->orderBy('question_id')
            ->orderBy('sort_order')
            ->orderBy('id');

        return $query;
    }

    /**
     * @param  Builder<Question>  $query
     */
    protected function applyPublicQuestionScope(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereHas('licenseCategory', fn (Builder $categoryQuery) => $categoryQuery->where('is_active', true));
    }

    protected function qualityError(string $description): ?string
    {
        if ($description === '') {
            return 'missing_description';
        }

        if (mb_strlen($description) > self::MAX_DESCRIPTION_CHARS) {
            return 'description_too_long';
        }

        if (preg_match('/^(tak|nie|czy|film do pytania|klip wideo|wideo powiązane|nagranie przypisane)\b/iu', $description) === 1) {
            return 'bad_description_start';
        }

        if (preg_match('/temat sceny|ocena, czy|sytuację egzaminacyjną na drodze|właściwa decyzja kierowcy|film do pytania/iu', $description) === 1) {
            return 'template_phrase';
        }

        if (preg_match('/\b(powinien|trzeba|obowiązek|musisz|należy|wolno ci|jest dozwolone|masz prawo|ustąpić pierwszeństwa|tak|nie)\b/iu', $description) === 1) {
            return 'direct_answer_term';
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function readRows(string $path): array
    {
        if (! File::exists($path)) {
            throw new InvalidArgumentException('Input file does not exist: '.$path);
        }

        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => $this->readJsonRows($path),
            'csv' => $this->readCsvRows($path),
            default => throw new InvalidArgumentException('Unsupported input format. Use CSV or JSON.'),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function readJsonRows(string $path): array
    {
        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('JSON input must contain an array of rows.');
        }

        return collect($decoded)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('Cannot open CSV file: '.$path);
        }

        try {
            $header = fgetcsv($handle);

            if (! is_array($header)) {
                return [];
            }

            $header = array_map(
                fn (mixed $value): string => ltrim((string) $value, "\xEF\xBB\xBF"),
                $header,
            );

            $rows = [];

            while (($values = fgetcsv($handle)) !== false) {
                $row = [];

                foreach ($header as $index => $column) {
                    if ($column === '') {
                        continue;
                    }

                    $row[$column] = $values[$index] ?? null;
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     */
    protected function pushSample(array &$samples, array $sample, int $limit): void
    {
        if (count($samples) >= $limit) {
            return;
        }

        $samples[] = $sample;
    }
}
