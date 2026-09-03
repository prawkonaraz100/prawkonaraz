<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;

class TrafficSignBreadcrumbs
{
    /**
     * @return list<array{label: string, url: string}>
     */
    public function hub(): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Znaki drogowe', 'url' => route('traffic-signs.index')],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function category(TrafficSignCategory $category): array
    {
        return [
            ...$this->hub(),
            ['label' => $category->name, 'url' => route('traffic-signs.categories.show', $category->slug)],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function sign(TrafficSign $sign): array
    {
        return [
            ...$this->category($sign->category),
            ['label' => $sign->publicTitle(), 'url' => route('traffic-signs.show', $sign->slug)],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function author(ContentAuthor $author): array
    {
        return [
            ...$this->organization(),
            ['label' => $author->name, 'url' => route('content-authors.show', $author->slug)],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function organization(): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'O nas', 'url' => route('about.organization')],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function howItWorks(): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Jak to działa', 'url' => route('about.how-it-works')],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function contact(): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Kontakt', 'url' => route('about.contact')],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function methodology(): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Metodologia', 'url' => route('about.methodology')],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function supportingPage(string $title, string $slug): array
    {
        return [
            ...$this->hub(),
            ['label' => $title, 'url' => route('traffic-signs.supporting.show', $slug)],
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
