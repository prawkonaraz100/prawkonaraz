<?php

namespace App\Support;

use App\Models\LegalArticleTopicCandidate;
use Illuminate\Support\Collection;
use RuntimeException;

class LegalArticleTopicCandidateCatalog
{
    public function version(): string
    {
        return (string) ($this->payload()['version'] ?? 'unknown');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function definitions(): Collection
    {
        $payload = $this->payload();
        $sortOrder = 0;

        $pillars = collect($payload['pillars'] ?? [])
            ->map(function (array $definition) use (&$sortOrder): array {
                $sortOrder += 10;

                return [
                    ...$definition,
                    'level' => LegalArticleTopicCandidate::LEVEL_PILLAR,
                    'description' => $definition['description']
                        ?? 'Temat filarowy wynikający z istniejącego działu pytań egzaminacyjnych.',
                    'sort_order' => $sortOrder,
                ];
            });

        $focused = collect($payload['focused'] ?? [])
            ->map(function (array $definition) use (&$sortOrder): array {
                $sortOrder += 10;

                return [
                    ...$definition,
                    'level' => LegalArticleTopicCandidate::LEVEL_FOCUSED,
                    'description' => $definition['description']
                        ?? 'Węższy temat artykułu wykryty na podstawie treści pytań.',
                    'sort_order' => $sortOrder,
                ];
            });

        $definitions = $pillars
            ->concat($focused)
            ->values();
        $duplicateSlugs = $definitions
            ->pluck('slug')
            ->countBy()
            ->filter(fn (int $count): bool => $count > 1)
            ->keys();

        if ($duplicateSlugs->isNotEmpty()) {
            throw new RuntimeException(
                'Powtarzające się slugi kandydatów: '.$duplicateSlugs->implode(', '),
            );
        }

        return $definitions;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        $path = base_path('resources/legal-content/article-topic-candidates.php');

        if (! is_file($path)) {
            throw new RuntimeException("Brak katalogu kandydatów: {$path}");
        }

        $payload = require $path;

        if (! is_array($payload)) {
            throw new RuntimeException('Katalog kandydatów musi zwracać tablicę.');
        }

        return $payload;
    }
}
