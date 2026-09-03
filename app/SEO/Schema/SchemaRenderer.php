<?php

namespace App\SEO\Schema;

class SchemaRenderer
{
    /**
     * @param  iterable<array<string, mixed>|null>  $nodes
     * @return array{@context: string, @graph: list<array<string, mixed>>}
     */
    public function graph(iterable $nodes): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => (new SchemaGraph)->addMany($nodes)->nodes(),
        ];
    }
}
