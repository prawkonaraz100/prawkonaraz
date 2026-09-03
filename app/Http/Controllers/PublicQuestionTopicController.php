<?php

namespace App\Http\Controllers;

use App\Models\QuestionSeoTopic;
use App\Support\PublicQuestionBreadcrumbs;
use App\Support\PublicQuestionCatalogService;
use App\Support\PublicQuestionSeoService;
use Illuminate\View\View;

class PublicQuestionTopicController extends Controller
{
    public function __invoke(
        string $topicSlug,
        PublicQuestionCatalogService $catalog,
        PublicQuestionSeoService $seo,
        PublicQuestionBreadcrumbs $breadcrumbsService,
    ): View {
        $topic = QuestionSeoTopic::query()
            ->indexable()
            ->where('slug', $topicSlug)
            ->with(['parent', 'adjacentTopics' => fn ($query) => $query->indexable()->orderByPivot('display_order')])
            ->first();

        abort_unless($topic instanceof QuestionSeoTopic, 404);

        $explanations = $topic->explanations()
            ->published()
            ->orderBy('external_id')
            ->paginate(20);
        $canonicalQuestions = $catalog->canonicalQuestionsByExternalIds($explanations->pluck('external_id'));
        $questions = $explanations->through(function ($explanation) use ($catalog, $canonicalQuestions): ?array {
            $question = $canonicalQuestions->get((string) $explanation->external_id);

            return $question === null ? null : $catalog->buildQuestionListItem($question);
        });
        $breadcrumbs = $breadcrumbsService->topic($topic);
        $canonicalUrl = route('public.questions.topics.show', $topic->slug);
        $itemList = collect($questions->items())
            ->filter()
            ->values()
            ->map(fn (array $question, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1 + (($questions->currentPage() - 1) * $questions->perPage()),
                'url' => $question['canonical_url'],
                'name' => $question['prompt_plain'],
            ])
            ->all();

        return view('questions-database.topic', [
            'topic' => $topic,
            'questions' => $questions,
            'meta' => $seo->topic($topic, $questions),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'CollectionPage',
                        '@id' => $canonicalUrl.'#webpage',
                        'url' => $canonicalUrl,
                        'name' => $topic->label,
                        'description' => $topic->description,
                    ],
                    [
                        '@type' => 'BreadcrumbList',
                        '@id' => $canonicalUrl.'#breadcrumb',
                        'itemListElement' => collect($breadcrumbs)->map(fn (array $item, int $index): array => [
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'name' => $item['label'],
                            'item' => $item['url'],
                        ])->all(),
                    ],
                    [
                        '@type' => 'ItemList',
                        '@id' => $canonicalUrl.'#questions',
                        'numberOfItems' => $questions->total(),
                        'itemListElement' => $itemList,
                    ],
                ],
            ],
        ]);
    }
}
