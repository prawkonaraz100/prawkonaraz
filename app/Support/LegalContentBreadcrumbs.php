<?php

namespace App\Support;

use App\Models\LegalContentPage;

class LegalContentBreadcrumbs
{
    /**
     * @return list<array{label: string, url: string}>
     */
    public function hub(): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Przepisy', 'url' => route('public.regulations')],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function page(LegalContentPage $page): array
    {
        return [
            ...$this->hub(),
            ['label' => $page->title, 'url' => route('public.regulations.show', $page->slug)],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function methodology(): array
    {
        return [
            ...$this->hub(),
            ['label' => 'Metodologia podstaw prawnych', 'url' => route('public.regulations.methodology')],
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function toSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'],
                ],
                $items,
                array_keys($items),
            ),
        ];
    }
}
