<?php

namespace App\Console\Commands;

use App\Support\QuestionAudioCoverageService;
use Illuminate\Console\Command;

class QuestionAudioCoverageCommand extends Command
{
    protected $signature = 'questions:audio-coverage
        {--type=question : Audio type to report}
        {--question-scope= : Limit coverage to all, basic, or specialist questions}
        {--json : Output JSON}
        {--missing-limit=20 : Number of sample rows per issue bucket}';

    protected $description = 'Show question audio coverage by canonical external_id.';

    public function handle(QuestionAudioCoverageService $coverageService): int
    {
        $summary = $coverageService->summary([
            'type' => (string) $this->option('type'),
            'question_scope' => $this->option('question-scope') ?: null,
            'sample_limit' => (int) $this->option('missing-limit'),
        ]);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $stats = $summary['canonical_questions'];

        $this->info('Audio pytan');
        $this->line('Typ: '.$summary['type']);
        $this->line('Locale: '.$summary['locale']);
        $this->line('Glos: '.$summary['voice_provider'].' / '.$summary['voice_id']);
        $this->line('Model: '.$summary['model_id']);
        $this->line('Wersja generatora: '.$summary['generation_version']);
        $this->newLine();
        $this->line('Kanoniczne pytania: '.$stats['total']);
        $this->line('Gotowe: '.$stats['ready']);
        $this->line('Do zrobienia: '.$stats['missing']);
        $this->line('Do weryfikacji: '.($stats['review_required'] ?? 0));
        $this->line('Bledy: '.$stats['failed']);
        $this->line('Nieaktualny hash: '.$stats['outdated_hash']);
        $this->line('Brakujace pliki: '.$stats['missing_files']);
        $this->line('Wylaczone: '.$stats['disabled']);
        $this->line('Pokrycie: '.$stats['coverage_percent'].'%');

        return self::SUCCESS;
    }
}
