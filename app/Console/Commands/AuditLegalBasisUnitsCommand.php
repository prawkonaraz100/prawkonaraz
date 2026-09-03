<?php

namespace App\Console\Commands;

use App\Support\LegalUnitAuditService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class AuditLegalBasisUnitsCommand extends Command
{
    protected $signature = 'legal-basis:audit-units
        {act_slug : Legal act slug, for example prawo-o-ruchu-drogowym}
        {--source= : Official ELI text.html URL or local HTML file; inferred from unit source_url when omitted}
        {--source-url= : Base official text.html URL used when --source points to a local file}
        {--write : Promote passing needs_review units to verified and record source checks}
        {--include-verified : Audit verified units too; they are not changed}
        {--sample-limit=10 : Number of samples to show}
        {--json : Output JSON for automation}';

    protected $description = 'Audit imported legal units against official ELI HTML and optionally promote clean units to verified.';

    public function handle(LegalUnitAuditService $auditService): int
    {
        try {
            $report = $auditService->audit(
                (string) $this->argument('act_slug'),
                $this->nullableOption('source'),
                $this->nullableOption('source-url'),
                (bool) $this->option('write'),
                (bool) $this->option('include-verified'),
                max(0, (int) $this->option('sample-limit')),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Audyt katalogu przepisow');
        $this->line('Tryb: '.(($report['mode'] ?? 'preview') === 'write' ? 'WRITE' : 'PREVIEW'));
        $this->line('Akt: '.$report['act_slug']);
        $this->line('Zrodlo: '.$report['source']);
        $this->line('Bazowy source_url: '.$report['source_url']);
        $this->line('Jednostki w zrodle: '.$report['manifest_units']);
        $this->line('Jednostki w bazie: '.$report['database_units']);
        $this->line('Audytowane jednostki: '.$report['audited_units']);
        $this->line('Przechodza kontrole: '.$report['passed_units']);
        $this->line('Nie przechodza kontroli: '.$report['failed_units']);
        $this->line('Juz zweryfikowane i poprawne: '.$report['already_verified_passed_units']);
        $this->line('Promowane do verified: '.$report['promoted_units']);

        if ($report['failures_by_reason'] !== []) {
            $this->newLine();
            $this->line('Powody odrzucenia:');

            foreach ($report['failures_by_reason'] as $reason => $count) {
                $this->line(sprintf('- %s: %d', $reason, $count));
            }
        }

        if ($report['failure_sample'] !== []) {
            $this->newLine();
            $this->line('Przyklady do recznego review:');

            foreach ($report['failure_sample'] as $sample) {
                $this->line(sprintf(
                    '- #%s %s %s [%s]',
                    (string) ($sample['id'] ?? '-'),
                    (string) ($sample['canonical_path'] ?? '-'),
                    (string) ($sample['label'] ?? '-'),
                    implode(', ', $sample['issues'] ?? []),
                ));
            }
        }

        if ($report['promoted_sample'] !== []) {
            $this->newLine();
            $this->line('Przyklady promowanych jednostek:');

            foreach ($report['promoted_sample'] as $sample) {
                $this->line(sprintf(
                    '- #%s %s %s',
                    (string) ($sample['id'] ?? '-'),
                    (string) ($sample['canonical_path'] ?? '-'),
                    (string) ($sample['label'] ?? '-'),
                ));
            }
        }

        if (($report['mode'] ?? 'preview') !== 'write') {
            $this->newLine();
            $this->info('To byl preview. Uruchom z --write, zeby promowac jednostki przechodzace kontrole.');
        }

        return self::SUCCESS;
    }

    protected function nullableOption(string $name): ?string
    {
        $value = $this->option($name);

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
