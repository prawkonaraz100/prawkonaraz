<?php

namespace App\Console\Commands;

use App\Models\ContentImportRun;
use App\Support\LearningExplanationRuleSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class SyncPublicMemoryRulesCommand extends Command
{
    protected $signature = 'questions:sync-public-memory-rules
        {--external-id=* : Limit preview to exact public external_id values}
        {--write : Apply a previously created preview run}
        {--run= : Preview run ID required with --write}
        {--confirm= : Manifest checksum required with --write}
        {--report= : Optional JSON report path}';

    protected $description = 'Preview or safely synchronize short public memory rules into learning explanations';

    public function handle(LearningExplanationRuleSyncService $service): int
    {
        try {
            $write = (bool) $this->option('write');
            $requestedExternalIds = array_values(array_filter(
                array_map(fn (mixed $externalId): string => trim((string) $externalId), (array) $this->option('external-id')),
            ));

            if (! $write) {
                if (filled($this->option('run')) || filled($this->option('confirm'))) {
                    $this->error('Opcje --run oraz --confirm sa dostepne tylko z --write.');

                    return self::INVALID;
                }

                $result = $service->preview($requestedExternalIds);
                $this->writeReport($result['run'], $result['report']);

                $this->info('Tryb: PREVIEW');
                $this->line('Preview run: '.$result['run']->getKey());
                $this->line('Checksum: '.$result['report']['manifest_checksum']);
                $this->line('Grupy gotowe: '.$result['report']['summary']['planned_groups']);
                $this->line('Rekordy pytan gotowe: '.$result['report']['summary']['planned_question_rows']);
                $this->line('Grupy pominiete: '.$result['report']['summary']['skipped_groups']);
                $this->warn('Preview nie zmienia questions.explanation. Zapis wymaga --write, --run i --confirm.');

                return self::SUCCESS;
            }

            if ($requestedExternalIds !== []) {
                $this->error('Przy --write nie podawaj --external-id. Zapis musi uzyc niezmienionego manifestu preview.');

                return self::INVALID;
            }

            $runId = (int) $this->option('run');
            $confirmation = trim((string) $this->option('confirm'));

            if ($runId <= 0 || $confirmation === '') {
                $this->error('Zapis wymaga --run=<preview ID> oraz --confirm=<checksum>.');

                return self::INVALID;
            }

            $result = $service->applyPreview($runId, $confirmation);
            $this->writeReport($result['run'], $result['report']);

            $this->info('Tryb: WRITE');
            $this->line('Run: '.$result['run']->getKey());
            $this->line('Grupy zaktualizowane: '.$result['report']['groups_applied']);
            $this->line('Rekordy pytan zaktualizowane: '.$result['report']['question_rows_applied']);
            $this->line('Grupy pominiete po ponownej kontroli: '.$result['report']['groups_skipped']);

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function writeReport(ContentImportRun $run, array $report): void
    {
        $option = trim((string) $this->option('report'));

        if ($option === '') {
            return;
        }

        $path = str_starts_with($option, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $option) === 1
            ? $option
            : storage_path('app/'.$option);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $run->forceFill(['report_path' => $path])->save();
        $this->line('Raport: '.$path);
    }
}
