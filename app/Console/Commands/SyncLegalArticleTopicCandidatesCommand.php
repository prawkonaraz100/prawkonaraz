<?php

namespace App\Console\Commands;

use App\Models\LegalArticleTopicCandidate;
use App\Support\LegalArticleTopicCandidateSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncLegalArticleTopicCandidatesCommand extends Command
{
    protected $signature = 'legal-content:sync-article-topic-candidates
        {--write : Zapisz kandydatów i relacje w bazie}
        {--report= : Zapisz skrócony raport Markdown}
        {--map= : Zapisz pełną mapę JSON temat -> pytania}';

    protected $description = 'Buduje listę kandydatów na artykuły i przypisuje pytania na podstawie promptów.';

    public function handle(LegalArticleTopicCandidateSyncService $syncService): int
    {
        $result = $syncService->build((bool) $this->option('write'));

        $this->table(
            ['Metryka', 'Wartość'],
            [
                ['Wiersze pytań', $result['question_rows_scanned']],
                ['Pytania kanoniczne', $result['canonical_questions_scanned']],
                ['Tematy filarowe', $result['pillar_topics']],
                ['Tematy węższe', $result['focused_topics']],
                ['Wiersze bez tematu filarowego', $result['question_rows_without_pillar_topic']],
                ['Pytania kanoniczne bez filaru', $result['canonical_questions_without_pillar_topic']],
                ['Kanoniczne pytania w węższych tematach', $result['canonical_questions_with_focused_topic']],
                ['Pokrycie węższymi tematami', $result['focused_coverage_percent'].'%'],
            ],
        );

        $this->table(
            ['Poziom', 'Temat', 'Pytania', 'Kategorie', 'Istniejący artykuł'],
            collect($result['topics'])
                ->sortByDesc('canonical_questions')
                ->map(fn (array $topic): array => [
                    $topic['level'],
                    $topic['title'],
                    $topic['canonical_questions'],
                    implode(', ', $topic['categories']),
                    $topic['existing_article_slug'] ?? '-',
                ])
                ->all(),
        );

        if (filled($this->option('report'))) {
            $path = base_path((string) $this->option('report'));
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $this->markdownReport($result));
            $this->info("Zapisano raport: {$path}");
        }

        if (filled($this->option('map'))) {
            $path = base_path((string) $this->option('map'));
            File::ensureDirectoryExists(dirname($path));
            File::put(
                $path,
                json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            );
            $this->info("Zapisano pełną mapę: {$path}");
        }

        if (! $this->option('write')) {
            $this->warn('Tryb dry-run: baza nie została zmieniona. Użyj --write, aby zapisać relacje.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function markdownReport(array $result): string
    {
        $lines = [
            '# Kandydaci na artykuły na podstawie bazy pytań',
            '',
            'Status: mapa planistyczna, bez automatycznej publikacji podstaw prawnych',
            '',
            '## Zakres skanu',
            '',
            "- Wiersze aktywnych pytań: `{$result['question_rows_scanned']}`.",
            "- Kanoniczne pytania: `{$result['canonical_questions_scanned']}`.",
            "- Tematy filarowe: `{$result['pillar_topics']}`.",
            "- Węższe tematy wykryte z promptów: `{$result['focused_topics']}`.",
            "- Pytania kanoniczne bez tematu filarowego: `{$result['canonical_questions_without_pillar_topic']}`.",
            "- Pytania objęte co najmniej jednym węższym tematem: `{$result['canonical_questions_with_focused_topic']}` ({$result['focused_coverage_percent']}%).",
            '',
            'Każde pytanie jest przypisane do tematu filarowego. Węższe powiązania powstają wyłącznie wtedy, gdy treść promptu zawiera wystarczająco mocny sygnał. Media, odpowiedzi i wyjaśnienia nie są analizowane na tym etapie.',
            '',
            '## Lista tematów',
            '',
            '| Poziom | Temat | Pytania kanoniczne | Kategorie | Istniejący artykuł | Przykładowe ID |',
            '| --- | --- | ---: | --- | --- | --- |',
        ];

        foreach (collect($result['topics'])->sortByDesc('canonical_questions') as $topic) {
            $sampleIds = collect($topic['questions'])
                ->pluck('external_id')
                ->take(12)
                ->map(fn (string $externalId): string => "`{$externalId}`")
                ->implode(', ');
            $lines[] = sprintf(
                '| %s | %s | %d | %s | %s | %s |',
                $topic['level'] === LegalArticleTopicCandidate::LEVEL_PILLAR ? 'filar' : 'węższy',
                str_replace('|', '\|', $topic['title']),
                $topic['canonical_questions'],
                implode(', ', $topic['categories']),
                $topic['existing_article_slug'] ? "`{$topic['existing_article_slug']}`" : '-',
                $sampleIds ?: '-',
            );
        }

        $lines[] = '';
        $lines[] = '## Jak używać mapy';
        $lines[] = '';
        $lines[] = '1. Wybierz węższy temat o wystarczającej liczbie pytań.';
        $lines[] = '2. Otwórz pełną mapę JSON i przejrzyj wszystkie prompty przypisane do tematu.';
        $lines[] = '3. Zweryfikuj podstawę prawną i odrzuć fałszywe dopasowania.';
        $lines[] = '4. Utwórz lub rozbuduj artykuł.';
        $lines[] = '5. Dopiero po weryfikacji przepisów przenieś pytania do `QuestionLegalReference`.';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }
}
