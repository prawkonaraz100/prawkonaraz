<?php

namespace App\Console\Commands;

use App\Support\LegalUnitManifestImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportLegalBasisUnitsCommand extends Command
{
    protected $signature = 'legal-basis:import-units
        {path : JSON manifest with legal act metadata and units}
        {--write : Persist changes; without this option the command only previews changes}
        {--sample-limit=10 : Number of invalid samples to show}
        {--json : Output JSON for automation}';

    protected $description = 'Import hierarchical legal units from a reviewed manifest.';

    public function handle(LegalUnitManifestImporter $importer): int
    {
        try {
            $report = $importer->import(
                (string) $this->argument('path'),
                (bool) $this->option('write'),
                max(0, (int) $this->option('sample-limit')),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return (int) ($report['invalid_units'] ?? 0) > 0
                ? self::FAILURE
                : self::SUCCESS;
        }

        $this->info('Import katalogu przepisow');
        $this->line('Tryb: '.(($report['mode'] ?? 'preview') === 'write' ? 'WRITE' : 'PREVIEW'));
        $this->line('Manifest: '.$report['manifest_path']);
        $this->line('Akt: '.$report['act']['slug']);

        if (($report['mode'] ?? 'preview') === 'write') {
            $this->line('Akt utworzony: '.($report['act']['created'] ? 'tak' : 'nie'));
            $this->line('Akt zaktualizowany: '.($report['act']['updated'] ? 'tak' : 'nie'));
            $this->line(sprintf(
                'Jednostki: input=%d created=%d updated=%d unchanged=%d invalid=%d',
                $report['input_units'],
                $report['created_units'],
                $report['updated_units'],
                $report['unchanged_units'],
                $report['invalid_units'],
            ));
        } else {
            $this->line('Akt do utworzenia: '.($report['act']['would_create'] ? 'tak' : 'nie'));
            $this->line('Akt do aktualizacji: '.($report['act']['would_update'] ? 'tak' : 'nie'));
            $this->line(sprintf(
                'Jednostki: input=%d would_create=%d would_update=%d unchanged=%d invalid=%d',
                $report['input_units'],
                $report['would_create_units'],
                $report['would_update_units'],
                $report['unchanged_units'],
                $report['invalid_units'],
            ));
        }

        foreach ($report['invalid_sample'] as $sample) {
            $this->line(sprintf(
                '[invalid] %s %s',
                (string) ($sample['canonical_path'] ?? '-'),
                (string) ($sample['reason'] ?? 'unknown'),
            ));
        }

        if ((int) ($report['invalid_units'] ?? 0) > 0) {
            $this->error('Manifest ma bledy. Popraw go przed zapisem.');

            return self::FAILURE;
        }

        if (($report['mode'] ?? 'preview') !== 'write') {
            $this->info('To byl preview. Uruchom z --write, zeby zapisac katalog.');
        }

        return self::SUCCESS;
    }
}
