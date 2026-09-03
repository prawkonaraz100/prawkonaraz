<?php

namespace App\Console\Commands;

use App\Support\QuestionVideoSeoDescriptionImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class ImportQuestionVideoDescriptionsCommand extends Command
{
    protected $signature = 'seo:import-question-video-descriptions
        {path : CSV/JSON export with external_id, editorial_status and proposed_video_description}
        {--write : Persist updates; without this option the command only previews changes}
        {--overwrite : Replace existing non-empty SEO descriptions}
        {--include-drafts : Import rows not marked accepted}
        {--all : Include non-public question media; default is public SEO scope only}
        {--limit= : Maximum input rows to process}
        {--sample-limit=20 : Maximum sample rows in JSON/text output}
        {--report= : Optional JSON report path}';

    protected $description = 'Import editorial video descriptions into question_media.seo_video_description for video sitemaps.';

    public function handle(QuestionVideoSeoDescriptionImporter $importer): int
    {
        $path = (string) $this->argument('path');
        $limit = $this->option('limit');

        try {
            $report = $importer->import($path, [
                'write' => (bool) $this->option('write'),
                'overwrite' => (bool) $this->option('overwrite'),
                'include_drafts' => (bool) $this->option('include-drafts'),
                'public_only' => ! (bool) $this->option('all'),
                'limit' => is_numeric($limit) ? (int) $limit : null,
                'sample_limit' => max(1, (int) $this->option('sample-limit')),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $mode = (string) ($report['mode'] ?? 'preview');
        $this->line('Tryb: '.($mode === 'write' ? 'WRITE' : 'PREVIEW'));
        $this->line('Bezpiecznik: aktualizowane pole to tylko question_media.seo_video_description.');
        $this->line('Pola pytania/odpowiedzi/explanation nie sa modyfikowane.');
        $this->line(sprintf(
            'Zakres: %s | overwrite=%s | include_drafts=%s',
            (bool) ($report['public_only'] ?? true) ? 'publiczne media pytan' : 'wszystkie media pytan',
            (bool) ($report['overwrite'] ?? false) ? 'yes' : 'no',
            (bool) ($report['include_drafts'] ?? false) ? 'yes' : 'no',
        ));

        $this->line(sprintf(
            'Rows: input=%d processed=%d accepted=%d skipped_status=%d invalid=%d unmatched=%d',
            (int) ($report['input_rows'] ?? 0),
            (int) ($report['processed_rows'] ?? 0),
            (int) ($report['accepted_rows'] ?? 0),
            (int) ($report['skipped_status'] ?? 0),
            (int) ($report['invalid_rows'] ?? 0),
            (int) ($report['unmatched_rows'] ?? 0),
        ));

        $this->line(sprintf(
            'Media: matched=%d updated=%d would_update=%d unchanged=%d skipped_existing=%d',
            (int) ($report['matched_media'] ?? 0),
            (int) ($report['updated_media'] ?? 0),
            (int) ($report['would_update_media'] ?? 0),
            (int) ($report['unchanged_media'] ?? 0),
            (int) ($report['skipped_existing_media'] ?? 0),
        ));

        $this->printSamples((array) ($report['invalid_sample'] ?? []), 'invalid');
        $this->printSamples((array) ($report['unmatched_sample'] ?? []), 'unmatched');
        $this->printSamples((array) ($report['skipped_existing_sample'] ?? []), 'skipped-existing');

        $reportPath = $this->option('report');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $report);
        }

        if ((int) ($report['invalid_rows'] ?? 0) > 0) {
            $this->error('Import zatrzymany logicznie: plik zawiera niepoprawne opisy. Popraw wsad przed zapisem.');

            return self::FAILURE;
        }

        if ($mode !== 'write' && (int) ($report['would_update_media'] ?? 0) > 0) {
            $this->info('To byl preview. Uruchom z --write, zeby zapisac opisy SEO w mediach.');
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
                '[%s] external_id=%s media=%s reason=%s',
                $level,
                (string) ($sample['external_id'] ?? '?'),
                (string) ($sample['media_id'] ?? '-'),
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
