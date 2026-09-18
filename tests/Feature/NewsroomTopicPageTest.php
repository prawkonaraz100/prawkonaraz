<?php

use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

function attachTopicArticles(ContentTopic $topic, ContentArticle ...$articles): void
{
    $topic->articles()->syncWithoutDetaching(
        collect($articles)->mapWithKeys(fn (ContentArticle $article): array => [$article->id => []])->all(),
    );
}

test('topic page stays unavailable while the newsroom public gate is disabled', function () {
    config(['newsroom.public_enabled' => false]);

    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Zmiany egzaminacyjne',
        'slug' => 'zmiany-egzaminacyjne',
    ]);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Materiał ukryty przed cutover',
        'slug' => 'material-ukryty-przed-cutover-topic',
    ]);
    attachTopicArticles($topic, $article);

    $this->get(route('public.news.topics.show', ['topicSlug' => $topic->slug]))
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertDontSee('Materiał ukryty przed cutover');
});

test('published topic renders editorial description featured article and eligible mixed-family corpus', function () {
    Carbon::setTestNow('2026-09-18 10:00:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy-topic',
    ]);

    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Egzamin teoretyczny',
        'slug' => 'egzamin-teoretyczny',
        'description' => 'Najważniejsze informacje i praktyczne materiały o egzaminie teoretycznym.',
        'seo_title' => 'Egzamin teoretyczny — aktualności i poradniki',
        'seo_description' => 'Aktualne informacje oraz poradniki dotyczące egzaminu teoretycznego.',
    ]);

    $featured = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Najważniejszy materiał topicu',
        'slug' => 'najwazniejszy-material-topicu',
        'published_at' => now()->subHours(5),
        'first_published_at' => now()->subHours(5),
    ]);

    $newer = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Nowsza aktualność topicu',
        'slug' => 'nowsza-aktualnosc-topicu',
        'published_at' => now()->subHour(),
        'first_published_at' => now()->subHour(),
    ]);

    $guide = ContentArticle::factory()->published()->guide()->create([
        'category_id' => $category->id,
        'title' => 'Poradnik topicu',
        'slug' => 'poradnik-topicu',
        'published_at' => now()->subHours(2),
        'first_published_at' => now()->subHours(2),
    ]);

    $older = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Starsza aktualność topicu',
        'slug' => 'starsza-aktualnosc-topicu',
        'published_at' => now()->subHours(3),
        'first_published_at' => now()->subHours(3),
    ]);

    $noindex = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Noindex nie trafia do topicu',
        'slug' => 'noindex-nie-trafia-do-topicu',
        'robots' => 'noindex,follow',
    ]);

    $archived = ContentArticle::factory()->archived()->create([
        'category_id' => $category->id,
        'title' => 'Archiwalny nie trafia do topicu',
        'slug' => 'archiwalny-nie-trafia-do-topicu',
    ]);

    $needsReview = ContentArticle::factory()->needsReview()->create([
        'category_id' => $category->id,
        'title' => 'Needs review nie trafia do topicu',
        'slug' => 'needs-review-nie-trafia-do-topicu',
    ]);

    $draft = ContentArticle::factory()->draft()->create([
        'category_id' => $category->id,
        'title' => 'Draft nie trafia do topicu',
        'slug' => 'draft-nie-trafia-do-topicu',
    ]);

    attachTopicArticles($topic, $featured, $newer, $guide, $older, $noindex, $archived, $needsReview, $draft);
    $topic->update(['featured_article_id' => $featured->id]);

    $response = $this->get(route('public.news.topics.show', ['topicSlug' => $topic->slug]));

    $response
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertViewIs('newsroom.topic')
        ->assertSee('Egzamin teoretyczny')
        ->assertSee('Najważniejsze informacje i praktyczne materiały o egzaminie teoretycznym.')
        ->assertSee('Wyróżniony materiał')
        ->assertSee('Najważniejszy materiał topicu')
        ->assertSee('href="/poradniki/poradnik-topicu"', false)
        ->assertSeeInOrder([
            'Nowsza aktualność topicu',
            'Poradnik topicu',
            'Starsza aktualność topicu',
        ])
        ->assertDontSee('Noindex nie trafia do topicu')
        ->assertDontSee('Archiwalny nie trafia do topicu')
        ->assertDontSee('Needs review nie trafia do topicu')
        ->assertDontSee('Draft nie trafia do topicu')
        ->assertDontSee('data-article-id="'.$featured->id.'"', false)
        ->assertSee('Egzamin teoretyczny — aktualności i poradniki')
        ->assertSee('Aktualne informacje oraz poradniki dotyczące egzaminu teoretycznego.');

    $response->assertViewHas('articles', fn (mixed $articles): bool => $articles instanceof LengthAwarePaginator
        && $articles->total() === 3
        && $articles->perPage() === 20);

    $response->assertViewHas('structuredData', function (mixed $schema): bool {
        if (! is_array($schema) || ! is_array($schema['@graph'] ?? null)) {
            return false;
        }

        $types = collect($schema['@graph'])->pluck('@type');

        return $types->contains('CollectionPage')
            && $types->contains('BreadcrumbList')
            && $types->contains('ItemList');
    });
});

test('topic pagination excludes featured article and keeps crawlable self canonical pages', function () {
    Carbon::setTestNow('2026-09-18 11:00:00');
    config(['newsroom.public_enabled' => true]);

    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Prawo jazdy 2026',
        'slug' => 'prawo-jazdy-2026',
        'description' => 'Dossier zmian i praktycznych informacji.',
    ]);

    $articles = collect();
    foreach (range(1, 22) as $index) {
        $articles->push(ContentArticle::factory()->published()->create([
            'title' => "Materiał topicu {$index}",
            'slug' => "material-topicu-{$index}",
            'published_at' => now()->subMinutes($index),
            'first_published_at' => now()->subMinutes($index),
        ]));
    }

    attachTopicArticles($topic, ...$articles->all());
    $featured = $articles->last();
    $topic->update(['featured_article_id' => $featured->id]);

    $pageOne = $this->get(route('public.news.topics.show', ['topicSlug' => $topic->slug]));

    $pageOne
        ->assertOk()
        ->assertSee('Wyróżniony materiał')
        ->assertSee($featured->title)
        ->assertSee('?page=2', false)
        ->assertSee('Następna');

    $pageTwoUrl = route('public.news.topics.show', ['topicSlug' => $topic->slug]).'?page=2';
    $pageTwo = $this->get($pageTwoUrl);

    $pageTwo
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$pageTwoUrl.'">', false)
        ->assertSee('Materiał topicu 21')
        ->assertDontSee('Wyróżniony materiał')
        ->assertDontSee($featured->title)
        ->assertSee('Poprzednia');

    $pageTwo->assertViewHas('articles', fn (mixed $paginator): bool => $paginator instanceof LengthAwarePaginator
        && $paginator->currentPage() === 2
        && $paginator->count() === 1
        && $paginator->total() === 21);
});

test('published topic remains public if corpus later falls below publication baseline', function () {
    config(['newsroom.public_enabled' => true]);

    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Temat po spadku corpus',
        'slug' => 'temat-po-spadku-corpus',
        'description' => 'Opis zachowujący wartość redakcyjną po późniejszej zmianie corpus.',
    ]);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Jedyny aktywny materiał',
        'slug' => 'jedyny-aktywny-material-topicu',
    ]);

    attachTopicArticles($topic, $article);

    $this->get(route('public.news.topics.show', ['topicSlug' => $topic->slug]))
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertSee('<meta name="robots" content="index,follow,max-image-preview:large">', false)
        ->assertSee('Jedyny aktywny materiał');
});

test('draft future unknown and invalid topic requests are not public while archived history is gone', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');
    config(['newsroom.public_enabled' => true]);

    $draft = ContentTopic::factory()->create([
        'title' => 'Draft topic',
        'slug' => 'draft-topic',
    ]);

    $future = ContentTopic::factory()->published()->create([
        'title' => 'Future topic',
        'slug' => 'future-topic',
        'published_at' => now()->addHour(),
    ]);

    $archived = ContentTopic::factory()->archived()->create([
        'title' => 'Archiwalny topic',
        'slug' => 'archiwalny-topic',
    ]);

    $this->get(route('public.news.topics.show', ['topicSlug' => $draft->slug]))
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, follow');

    $this->get(route('public.news.topics.show', ['topicSlug' => $future->slug]))
        ->assertNotFound();

    $this->get('/aktualnosci/temat/nie-istnieje')
        ->assertNotFound();

    $this->get(route('public.news.topics.show', ['topicSlug' => $archived->slug]))
        ->assertStatus(410)
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertSee('Temat został zarchiwizowany');

    $this->get(route('public.news.topics.show', ['topicSlug' => $archived->slug]).'?page=0')
        ->assertNotFound();
});

test('topic rejects invalid and out of range pagination', function () {
    config(['newsroom.public_enabled' => true]);

    $topic = ContentTopic::factory()->published()->create([
        'title' => 'Topic paginacji',
        'slug' => 'topic-paginacji',
        'description' => 'Opis topicu paginacji.',
    ]);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Jedyny materiał topicu',
        'slug' => 'jedyny-material-topicu-paginacji',
    ]);

    attachTopicArticles($topic, $article);

    $base = route('public.news.topics.show', ['topicSlug' => $topic->slug]);

    $this->get($base.'?page=0')->assertNotFound();
    $this->get($base.'?page=abc')->assertNotFound();
    $this->get($base.'?page[]=1')->assertNotFound();
    $this->get($base.'?page=2')->assertNotFound();
});
