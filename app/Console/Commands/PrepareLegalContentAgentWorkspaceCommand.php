<?php

namespace App\Console\Commands;

use App\Models\QuestionLegalReference;
use App\Support\LegalArticleTopicCandidateSyncService;
use App\Support\LegalContentAgentWorkspaceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareLegalContentAgentWorkspaceCommand extends Command
{
    protected $signature = 'legal-content:prepare-agent-workspace
        {slug? : Slug kandydata, dla którego wygenerować dossier}
        {--refresh : Najpierw odśwież kandydatów i relacje z promptów}
        {--directory=resources/legal-content/generated/agent-workspace : Katalog wynikowy}';

    protected $description = 'Generuje kolejkę tematów i gotowy pakiet roboczy dla agenta tworzącego artykuł prawny.';

    public function handle(
        LegalContentAgentWorkspaceService $workspace,
        LegalArticleTopicCandidateSyncService $sync,
    ): int {
        if ($this->option('refresh')) {
            $this->info('Odświeżanie mapy kandydatów...');
            $sync->build(write: true);
        }

        $directory = base_path((string) $this->option('directory'));
        File::ensureDirectoryExists($directory);

        $queue = $workspace->queue();
        $this->writeJson($directory.'/queue.json', $queue);
        File::put($directory.'/QUEUE.md', $this->queueMarkdown($queue));

        $slug = trim((string) $this->argument('slug'));

        if ($slug !== '') {
            $dossier = $workspace->dossier($slug);
            $topicDirectory = $directory.'/topics';
            File::ensureDirectoryExists($topicDirectory);
            $this->writeJson($topicDirectory.'/'.$slug.'.json', $dossier);
            File::put($topicDirectory.'/'.$slug.'.md', $this->dossierMarkdown($dossier));

            $this->table(
                ['Metryka', 'Wartość'],
                [
                    ['Temat', $dossier['candidate']['title']],
                    ['Status', $dossier['workflow']['status']],
                    ['Pytania kanoniczne', $dossier['summary']['canonical_questions']],
                    ['Wiersze pytań', $dossier['summary']['question_rows']],
                    ['Zweryfikowane pytania', $dossier['summary']['canonical_questions_with_verified_reference']],
                    ['Konflikty odpowiedzi', count($dossier['summary']['correct_answer_conflicts'])],
                ],
            );
            $this->info("Zapisano dossier: {$topicDirectory}/{$slug}.md");
        }

        $this->info("Zapisano kolejkę: {$directory}/QUEUE.md");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function writeJson(string $path, array $payload): void
    {
        File::put(
            $path,
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ).PHP_EOL,
        );
    }

    /**
     * @param  array<string, mixed>  $queue
     */
    protected function queueMarkdown(array $queue): string
    {
        $lines = [
            '# Kolejka artykułów prawnych dla agenta',
            '',
            'Ten plik jest krótkim indeksem operacyjnym. Status `ready` oznacza temat niewykorzystany, `published` oznacza temat posiadający artykuł.',
            '',
            '## Podsumowanie',
            '',
            "- Tematy `focused`: `{$queue['summary']['focused_topics']}`.",
            "- Gotowe do opracowania: `{$queue['summary']['ready_topics']}`.",
            "- Wykorzystane klastry kandydackie: `{$queue['summary']['used_candidate_topics']}`.",
            "- Unikalne artykuły wskazane przez klastry: `{$queue['summary']['published_articles']}`.",
            "- Małe klastry do ręcznej oceny: `{$queue['summary']['manual_review_topics']}`.",
            '',
            '## Co zrobić po wyczerpaniu kolejki',
            '',
            'Nie twórz tematów ręcznie i nie skanuj ponownie całej bazy. Otwórz `docs/LEGAL-CONTENT-SCALING-PLAN.md` i przejdź do etapu discovery. Plan określa progi, komendę docelową, format raportu oraz zasady ochrony opublikowanych i odrzuconych decyzji.',
            '',
            'Główny workflow publikacji pozostaje w `docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md`.',
            '',
            '## Następne tematy',
            '',
            '| Temat | Pytania | Kategorie | Komenda dossier |',
            '| --- | ---: | --- | --- |',
        ];

        foreach ($queue['next_topics'] as $topic) {
            $lines[] = sprintf(
                '| %s | %d | %s | `%s` |',
                str_replace('|', '\|', $topic['title']),
                $topic['canonical_questions'],
                implode(', ', $topic['categories']),
                'php artisan legal-content:prepare-agent-workspace '.$topic['slug'],
            );
        }

        $lines[] = '';
        $lines[] = '## Wszystkie tematy';
        $lines[] = '';
        $lines[] = '| Status | Slug | Pytania | Zweryfikowane | Artykuł |';
        $lines[] = '| --- | --- | ---: | ---: | --- |';

        foreach ($queue['topics'] as $topic) {
            $lines[] = sprintf(
                '| `%s` | `%s` | %d | %d | %s |',
                $topic['workflow_status'],
                $topic['slug'],
                $topic['canonical_questions'],
                $topic['verified_canonical_questions'],
                $topic['existing_article_slug'] ? "`{$topic['existing_article_slug']}`" : '-',
            );
        }

        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  array<string, mixed>  $dossier
     */
    protected function dossierMarkdown(array $dossier): string
    {
        $candidate = $dossier['candidate'];
        $summary = $dossier['summary'];
        $lines = [
            "# Dossier tematu: {$candidate['title']}",
            '',
            "- Slug kandydata: `{$candidate['slug']}`.",
            "- Status workflow: `{$dossier['workflow']['status']}`.",
            '- Istniejący artykuł: '.($candidate['existing_article_slug'] ? "`{$candidate['existing_article_slug']}`." : 'brak.'),
            "- Pytania kanoniczne: `{$summary['canonical_questions']}`.",
            "- Wiersze kategorii/źródeł: `{$summary['question_rows']}`.",
            "- Zweryfikowane relacje prawne: `{$summary['canonical_questions_with_verified_reference']}`.",
            '',
            '## Następny krok',
            '',
            $dossier['workflow']['next_action'],
            '',
            'Nie publikuj relacji kandydackich. Do `QuestionLegalReference` trafiają wyłącznie pytania sprawdzone z oficjalnym przepisem.',
            '',
            '## Kontrola jakości',
            '',
            '- Różne prompty dla jednego ID: '.$this->listOrNone($summary['prompt_conflicts']).'.',
            '- Różne zestawy odpowiedzi: '.$this->listOrNone($summary['answer_conflicts']).'.',
            '- Sprzeczne poprawne odpowiedzi: '.$this->listOrNone($summary['correct_answer_conflicts']).'.',
            '',
            '## Pytania',
            '',
        ];

        foreach ($dossier['questions'] as $question) {
            $answer = collect($question['answers'])->first();
            $verifiedReferences = collect($question['legal_references'])
                ->where('status', QuestionLegalReference::STATUS_VERIFIED);
            $lines[] = "### `{$question['external_id']}`";
            $lines[] = '';
            $lines[] = $question['prompt'];
            $lines[] = '';
            $lines[] = '- Kategorie: `'.implode('`, `', $question['categories']).'`.';
            $lines[] = '- Poprawna odpowiedź: `'.($answer['correct_answer'] ?? '-').'` - '.($answer['correct_answer_text'] ?? '-').'.';
            $lines[] = '- Media: '.($question['checks']['has_media'] ? 'tak' : 'nie').'.';
            $lines[] = '- Zweryfikowana relacja prawna: '.($verifiedReferences->isNotEmpty() ? 'tak' : 'nie').'.';

            foreach ($verifiedReferences as $reference) {
                $lines[] = "- `{$reference['legal_unit_label']}` / `{$reference['article_slug']}`: {$reference['public_note']}";
            }

            $lines[] = '';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  array<int, string>  $items
     */
    protected function listOrNone(array $items): string
    {
        return $items === [] ? 'brak' : implode(', ', array_map(
            fn (string $item): string => "`{$item}`",
            $items,
        ));
    }
}
