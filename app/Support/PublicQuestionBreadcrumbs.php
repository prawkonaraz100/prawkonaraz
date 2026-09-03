<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\QuestionSeoTopic;

class PublicQuestionBreadcrumbs
{
    /**
     * @return list<array{label: string, url: string}>
     */
    public function hub(): array
    {
        return [
            ['label' => 'Strona główna', 'url' => route('home')],
            ['label' => 'Oficjalna baza pytań', 'url' => route('public.questions.hub')],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function category(LicenseCategory $category): array
    {
        return [
            ...$this->hub(),
            ['label' => 'Kategoria '.$category->code, 'url' => route('public.questions.category', $category->slug)],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function question(?LicenseCategory $category, string $questionLabel, string $questionUrl): array
    {
        $items = $this->hub();

        if ($category instanceof LicenseCategory) {
            $items[] = [
                'label' => 'Kategoria '.$category->code,
                'url' => route('public.questions.category', $category->slug),
            ];
        }

        $items[] = [
            'label' => $questionLabel,
            'url' => $questionUrl,
        ];

        return $items;
    }

    /** @return list<array{label: string, url: string}> */
    public function topic(QuestionSeoTopic $topic): array
    {
        return [
            ...$this->hub(),
            ['label' => 'Tematy', 'url' => route('public.questions.hub').'#tematy'],
            ['label' => $topic->label, 'url' => route('public.questions.topics.show', $topic->slug)],
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
