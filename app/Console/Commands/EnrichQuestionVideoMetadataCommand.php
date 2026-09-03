<?php

namespace App\Console\Commands;

use App\Support\QuestionVideoMetadataEnricher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnrichQuestionVideoMetadataCommand extends Command
{
    protected $signature = 'seo:enrich-question-video-metadata
        {--write : Persist metadata updates; without this option the command only previews changes}
        {--force : Re-probe and overwrite existing metadata fields when ffprobe returns values}
        {--all : Include non-public question media; default is public SEO scope only}
        {--limit= : Maximum media rows to process}
        {--media-id= : Process one question_media row}
        {--question-id= : Process media for one question id}
        {--disk= : Process only media from one storage disk}
        {--probe-timeout=20 : ffprobe timeout per video in seconds}
        {--sample-limit=20 : Maximum sample rows in JSON/text output}
        {--report= : Optional JSON report path}';

    protected $description = 'Probe question videos with ffprobe and fill missing SEO metadata.';

    public function handle(QuestionVideoMetadataEnricher $enricher): int
    {
        $limit = $this->option('limit');
        $mediaId = $this->option('media-id');
        $questionId = $this->option('question-id');
        $disk = $this->option('disk');

        $report = $enricher->enrich([
            'write' => (bool) $this->option('write'),
            'force' => (bool) $this->option('force'),
            'public_only' => ! (bool) $this->option('all'),
            'limit' => is_numeric($limit) ? (int) $limit : null,
            'media_id' => is_numeric($mediaId) ? (int) $mediaId : null,
            'question_id' => is_numeric($questionId) ? (int) $questionId : null,
            'disk' => is_string($disk) && trim($disk) !== '' ? trim($disk) : null,
            'sample_limit' => max(1, (int) $this->option('sample-limit')),
            'probe_timeout' => max(1, (int) $this->option('probe-timeout')),
        ]);

        $mode = (string) ($report['mode'] ?? 'preview');
        $this->line('Tryb: '.($mode === 'write' ? 'WRITE' : 'PREVIEW'));
        $this->line(sprintf(
            'Zakres: %s | force=%s | limit=%s',
            (bool) ($report['public_only'] ?? true) ? 'publiczne pytania' : 'wszystkie media',
            (bool) ($report['force'] ?? false) ? 'yes' : 'no',
            $report['limit'] ?? 'none',
        ));

        $this->line(sprintf(
            'Video metadata: candidates=%d probed=%d updated=%d would_update=%d no_changes=%d',
            (int) ($report['candidate_media'] ?? 0),
            (int) ($report['probed_media'] ?? 0),
            (int) ($report['updated_media'] ?? 0),
            (int) ($report['would_update_media'] ?? 0),
            (int) ($report['skipped_no_changes'] ?? 0),
        ));

        $this->line(sprintf(
            'Skipped: external_url=%d missing_file=%d unconfigured_disk=%d probe_failed=%d errors=%d',
            (int) ($report['skipped_external_url'] ?? 0),
            (int) ($report['skipped_missing_file'] ?? 0),
            (int) ($report['skipped_unconfigured_disk'] ?? 0),
            (int) ($report['probe_failed'] ?? 0),
            (int) ($report['errors_count'] ?? 0),
        ));

        $fieldUpdates = (array) ($report['field_updates'] ?? []);

        if ($fieldUpdates !== []) {
            ksort($fieldUpdates);

            foreach ($fieldUpdates as $field => $count) {
                $this->line(sprintf('[field] %s=%d', (string) $field, (int) $count));
            }
        }

        if ((int) ($report['probe_failed'] ?? 0) > 0 || (int) ($report['errors_count'] ?? 0) > 0) {
            $this->warn('Some videos could not be enriched. Check the JSON report or sample output.');
            $this->printSamples((array) ($report['errors_sample'] ?? []), 'error');
        }

        $reportPath = $this->option('report');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $report);
        }

        if ($mode !== 'write' && (int) ($report['would_update_media'] ?? 0) > 0) {
            $this->info('To byl preview. Uruchom z --write, zeby zapisac metadane.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     */
    protected function printSamples(array $samples, string $level): void
    {
        foreach (array_slice($samples, 0, 5) as $sample) {
            $this->line(sprintf(
                '[%s] media=%s question=%s reason=%s',
                $level,
                (string) ($sample['id'] ?? '?'),
                (string) ($sample['question_id'] ?? '?'),
                (string) ($sample['reason'] ?? 'unknown'),
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function writeReport(string $path, array $report): void
    {
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

        $this->info('Report written: '.$path);
    }
}
