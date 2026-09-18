<?php

use App\Models\ContentArticle;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('guides hub keeps the pre launch placeholder while the public gate is disabled', function () {
    config(['newsroom.public_enabled' => false]);

    ContentArticle::factory()->published()->guide()->create([
        'title' => 'Ukryty poradnik przed cutover',
        'slug' => 'ukryty-poradnik-przed-cutover',
    ]);

    $this->get(route('public.guides'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertDontSee('Ukryty poradnik przed cutover')
        ->assertDontSee('Praktyczne poradniki');
});

test('guides hub renders only actively distributed guides in deterministic chronology', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');
    config(['newsroom.public_enabled' => true]);

    $older = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Starszy poradnik',
        'slug' => 'starszy-poradnik',
        'published_at' => now()->subHours(3),
        'first_published_at' => now()->subHours(3),
    ]);

    $samePublishedAt = now()->subHour();

    $tieFirst = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Poradnik remis niższe ID',
        'slug' => 'poradnik-remis-nizsze-id',
        'published_at' => $samePublishedAt,
        'first_published_at' => $samePublishedAt,
    ]);

    $tieSecond = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Poradnik remis wyższe ID',
        'slug' => 'poradnik-remis-wyzsze-id',
        'published_at' => $samePublishedAt,
        'first_published_at' => $samePublishedAt,
    ]);

    ContentArticle::factory()->published()->create([
        'title' => 'News nie może wejść do guide huba',
        'slug' => 'news-poza-guide-hubem',
    ]);

    ContentArticle::factory()->archived()->guide()->create([
        'title' => 'Archiwalny guide nie jest aktywnie dystrybuowany',
        'slug' => 'archiwalny-guide-poza-hubem',
    ]);

    ContentArticle::factory()->needsReview()->guide()->create([
        'title' => 'Guide needs review nie jest aktywnie dystrybuowany',
        'slug' => 'guide-needs-review-poza-hubem',
    ]);

    ContentArticle::factory()->draft()->guide()->create([
        'title' => 'Draft guide nie może wyciec',
        'slug' => 'draft-guide-nie-moze-wyciec',
    ]);

    $response = $this->get(route('public.guides'));

    $response
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertViewIs('newsroom.guides')
        ->assertSee('<h1', false)
        ->assertSee('Poradniki')
        ->assertSee('Praktyczne poradniki')
        ->assertSeeInOrder([
            $tieSecond->title,
            $tieFirst->title,
            $older->title,
        ])
        ->assertDontSee('News nie może wejść do guide huba')
        ->assertDontSee('Archiwalny guide nie jest aktywnie dystrybuowany')
        ->assertDontSee('Guide needs review nie jest aktywnie dystrybuowany')
        ->assertDontSee('Draft guide nie może wyciec')
        ->assertSee(route('public.guides.show', ['articleSlug' => $tieSecond->slug], false), false)
        ->assertSee('Poradniki o prawie jazdy i egzaminach');

    $response->assertViewHas('guides', function (mixed $guides): bool {
        return $guides instanceof LengthAwarePaginator
            && $guides->total() === 3
            && $guides->perPage() === 20;
    });

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

test('guides hub pagination is SSR crawlable and self canonical', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');
    config(['newsroom.public_enabled' => true]);

    foreach (range(1, 21) as $index) {
        ContentArticle::factory()->published()->guide()->create([
            'title' => "Poradnik paginacji {$index}",
            'slug' => "poradnik-paginacji-{$index}",
            'published_at' => now()->subMinutes($index),
            'first_published_at' => now()->subMinutes($index),
        ]);
    }

    $pageOne = $this->get(route('public.guides'));

    $pageOne
        ->assertOk()
        ->assertSee('?page=2', false)
        ->assertSee('Następna');

    $pageTwoUrl = route('public.guides').'?page=2';

    $pageTwo = $this->get($pageTwoUrl);

    $pageTwo
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$pageTwoUrl.'">', false)
        ->assertSee('Poradnik paginacji 21')
        ->assertDontSee('Poradnik paginacji 1')
        ->assertSee('Poprzednia');

    $pageTwo->assertViewHas('guides', fn (mixed $guides): bool => $guides instanceof LengthAwarePaginator
        && $guides->currentPage() === 2
        && $guides->count() === 1);
});

test('empty guides hub renders a useful noindex state', function () {
    config(['newsroom.public_enabled' => true]);

    $response = $this->get(route('public.guides'));

    $response
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertViewIs('newsroom.guides')
        ->assertSee('<meta name="robots" content="noindex,follow">', false)
        ->assertSee('Nie ma jeszcze opublikowanych poradników')
        ->assertSee('Przejdź do aktualności');

    $response->assertViewHas('structuredData', function (mixed $schema): bool {
        if (! is_array($schema) || ! is_array($schema['@graph'] ?? null)) {
            return false;
        }

        $types = collect($schema['@graph'])->pluck('@type');

        return $types->contains('CollectionPage')
            && ! $types->contains('ItemList');
    });
});

test('guides hub rejects invalid and out of range pages', function () {
    config(['newsroom.public_enabled' => true]);

    ContentArticle::factory()->published()->guide()->create([
        'title' => 'Jedyny poradnik',
        'slug' => 'jedyny-poradnik',
    ]);

    $this->get(route('public.guides').'?page=0')->assertNotFound();
    $this->get(route('public.guides').'?page=abc')->assertNotFound();
    $this->get(route('public.guides').'?page[]=2')->assertNotFound();
    $this->get(route('public.guides').'?page=2')->assertNotFound();
});
