<?php

namespace App\Console\Commands;

use App\Support\LegalBasisCoverageService;
use Illuminate\Console\Command;

class LegalBasisCoverageCommand extends Command
{
    protected $signature = 'legal-basis:coverage
        {--sample-limit=10 : Number of sample rows per issue bucket}
        {--json : Output JSON for monitoring jobs}';

    protected $description = 'Show legal basis coverage for public question pages.';

    public function handle(LegalBasisCoverageService $coverageService): int
    {
        $sampleLimit = max(0, (int) $this->option('sample-limit'));
        $summary = $coverageService->summary($sampleLimit);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $questionRows = $summary['question_rows'];
        $canonicalQuestions = $summary['canonical_questions'];
        $legalCatalog = $summary['legal_catalog'];
        $questionReferences = $summary['question_legal_references'];

        $this->info('Raport podstaw prawnych');
        $this->line('Publiczne rekordy pytan: '.$questionRows['total']);
        $this->line('Publiczne pytania kanoniczne: '.$canonicalQuestions['total']);
        $this->line(sprintf(
            'Zweryfikowana podstawa prawna: %d/%d (%s%%)',
            $canonicalQuestions['with_verified_reference'],
            $canonicalQuestions['total'],
            $canonicalQuestions['verified_coverage_percent'],
        ));
        $this->line('Brak zweryfikowanej podstawy: '.$canonicalQuestions['missing_verified_reference']);
        $this->line('Z tematem opcjonalnym do uzupelnienia: '.$canonicalQuestions['with_verified_reference_without_topic']);
        $this->line('Z opublikowanym artykulem: '.$canonicalQuestions['with_verified_reference_with_article']);
        $this->line('Bez opublikowanego artykulu: '.$canonicalQuestions['with_verified_reference_without_article']);
        $this->line('Bez publicznej notatki: '.$canonicalQuestions['with_verified_reference_without_public_note']);
        $this->line('Wskazanie na poziomie samego artykulu: '.$canonicalQuestions['with_article_level_verified_reference']);

        $this->newLine();
        $this->line(sprintf(
            'Katalog przepisow: akty %d/%d zweryfikowane, jednostki %d/%d zweryfikowane, hierarchia %d z canonical_path / %d z rodzicem, tematy %d/%d opublikowane, artykuly %d/%d opublikowane',
            $legalCatalog['verified_legal_acts'],
            $legalCatalog['legal_acts_total'],
            $legalCatalog['verified_legal_units'],
            $legalCatalog['legal_units_total'],
            $legalCatalog['legal_units_with_canonical_path'],
            $legalCatalog['legal_units_with_parent'],
            $legalCatalog['published_legal_topics'],
            $legalCatalog['legal_topics_total'],
            $legalCatalog['published_legal_content_pages'],
            $legalCatalog['legal_content_pages_total'],
        ));
        $this->line('Relacje prawne dla publicznych pytan: '.$questionReferences['total_for_public_questions']);

        if ($questionReferences['status_counts'] !== []) {
            $this->newLine();
            $this->table(
                ['Status relacji', 'Liczba'],
                collect($questionReferences['status_counts'])
                    ->map(fn (int $count, string $status): array => [$status, $count])
                    ->values()
                    ->all(),
            );
        }

        if ($summary['samples']['missing_verified_reference'] !== []) {
            $this->newLine();
            $this->line('Pierwsze pytania bez zweryfikowanej podstawy:');

            foreach ($summary['samples']['missing_verified_reference'] as $sample) {
                $this->line('- '.$sample['external_id'].' (kategorie: '.implode(', ', $sample['category_codes']).')');
            }
        }

        return self::SUCCESS;
    }
}
