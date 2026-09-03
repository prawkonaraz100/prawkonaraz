<?php

namespace App\Console\Commands;

use App\Support\LegalUnitManifestBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class BuildLegalBasisUnitsManifestCommand extends Command
{
    protected $signature = 'legal-basis:build-units-manifest
        {source : ELI text.html URL or local HTML file}
        {--output= : Output JSON path; defaults to storage/app/legal-units/{act-slug}.json}
        {--act-slug=prawo-o-ruchu-drogowym : Legal act slug}
        {--title= : Legal act title}
        {--short-title= : Legal act short title}
        {--source-url= : Official act source URL}
        {--eli-url= : ELI URL}
        {--isap-url= : ISAP URL}
        {--effective-from=1998-01-01 : Legal act effective date}
        {--last-checked-at= : Source check timestamp}
        {--act-status=needs_review : Status assigned to the legal act}
        {--unit-status=needs_review : Status assigned to generated units}
        {--limit= : Optional unit limit for parser debugging}
        {--json : Output command report as JSON}';

    protected $description = 'Build a legal units manifest from official ELI HTML text.';

    public function handle(LegalUnitManifestBuilder $builder): int
    {
        $options = [
            'act_slug' => (string) $this->option('act-slug'),
            'title' => (string) $this->option('title'),
            'short_title' => (string) $this->option('short-title'),
            'source_url' => (string) $this->option('source-url'),
            'eli_url' => (string) $this->option('eli-url'),
            'isap_url' => (string) $this->option('isap-url'),
            'effective_from' => (string) $this->option('effective-from'),
            'last_checked_at' => (string) $this->option('last-checked-at'),
            'act_status' => (string) $this->option('act-status'),
            'unit_status' => (string) $this->option('unit-status'),
        ];
        $limit = $this->option('limit');

        if (is_numeric($limit)) {
            $options['limit'] = (int) $limit;
        }

        try {
            $report = $builder->build((string) $this->argument('source'), $options);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $outputPath = $this->outputPath($report);
        $this->writeManifest($outputPath, $report['manifest']);

        $commandReport = [
            'source' => $report['source'],
            'output_path' => $outputPath,
            'act_slug' => $report['manifest']['act']['slug'],
            'unit_count' => $report['unit_count'],
            'skipped_replacement_units' => $report['skipped_replacement_units'],
            'skipped_without_parent' => $report['skipped_without_parent'],
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($commandReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Manifest jednostek prawnych');
        $this->line('Zrodlo: '.$commandReport['source']);
        $this->line('Plik: '.$commandReport['output_path']);
        $this->line('Akt: '.$commandReport['act_slug']);
        $this->line('Jednostki w manifeście: '.$commandReport['unit_count']);
        $this->line('Pominiete fragmenty zmian innych aktow: '.$commandReport['skipped_replacement_units']);
        $this->line('Pominiete jednostki bez rodzica: '.$commandReport['skipped_without_parent']);
        $this->newLine();
        $this->line('Nastepny krok preview: php artisan legal-basis:import-units "'.$outputPath.'"');
        $this->line('Zapis po review: php artisan legal-basis:import-units "'.$outputPath.'" --write');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function outputPath(array $report): string
    {
        $output = $this->option('output');

        if (is_string($output) && trim($output) !== '') {
            return trim($output);
        }

        return storage_path('app/legal-units/'.$report['manifest']['act']['slug'].'.json');
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function writeManifest(string $path, array $manifest): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }
}
