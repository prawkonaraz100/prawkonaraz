<?php

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Support\ContentArticlePublishingService;
use App\Support\ContentArticleSlugService;
use App\Support\NewsroomPublicReadCache;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('public Atom feed exposes latest actively distributed news with stable metadata and validators', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');
    config()->set('newsroom.public_enabled', true);
    config()->set('newsroom.feed_items_limit', 2);
    config()->set('content.organization.name', 'PrawkoNaRaz');

    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Redakcja Feed',
        'slug' => 'redakcja-feed',
    ]);

    $newestPublishedAt = now()->subMinutes(20);
    $newestUpdatedAt = now()->subMinutes(5);
    $newest = ContentArticle::factory()->published()->create([
        'author_id' => $author->id,
        'title' => 'Nowy wpis & ważna zmiana',
        'slug' => 'nowy-wpis-feed',
        'lead' => 'Krótki opis & podsumowanie.',
        'first_published_at' => $newestPublishedAt,
        'published_at' => $newestPublishedAt,
        'last_substantive_update_at' => $newestUpdatedAt,
    ]);

    $secondPublishedAt = now()->subHour();
    $second = ContentArticle::factory()->published()->create([
        'author_id' => $author->id,
        'title' => 'Drugi wpis',
        'slug' => 'drugi-wpis-feed',
        'first_published_at' => $secondPublishedAt,
        'published_at' => $secondPublishedAt,
        'last_substantive_update_at' => null,
    ]);

    $oldestPublishedAt = now()->subHours(2);
    $oldest = ContentArticle::factory()->published()->create([
        'title' => 'Najstarszy poza limitem',
        'slug' => 'najstarszy-poza-limitem-feed',
        'first_published_at' => $oldestPublishedAt,
        'published_at' => $oldestPublishedAt,
    ]);

    ContentArticle::factory()->published()->guide()->create([
        'title' => 'Poradnik poza feedem',
        'slug' => 'poradnik-poza-feedem',
    ]);

    ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::Explainer->value,
        'title' => 'Explainer poza feedem',
        'slug' => 'explainer-poza-feedem',
    ]);

    ContentArticle::factory()->needsReview()->create([
        'title' => 'Needs review poza feedem',
        'slug' => 'needs-review-poza-feedem',
    ]);

    ContentArticle::factory()->archived()->create([
        'title' => 'Archiwalny poza feedem',
        'slug' => 'archiwalny-poza-feedem',
    ]);

    $response = $this->get(route('public.news.feed'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeaderMissing('Set-Cookie');

    $xml = $response->getContent();

    expect($xml)
        ->toContain('<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="pl">')
        ->toContain('<title>PrawkoNaRaz — Aktualności</title>')
        ->toContain('<link rel="self" type="application/atom+xml" href="'.route('public.news.feed').'"/>')
        ->toContain('<id>urn:prawkonaraz:content-article:'.$newest->id.'</id>')
        ->toContain('<link rel="alternate" type="text/html" href="'.route('public.news.show', ['articleSlug' => $newest->slug]).'"/>')
        ->toContain('<published>'.$newestPublishedAt->toAtomString().'</published>')
        ->toContain('<updated>'.$newestUpdatedAt->toAtomString().'</updated>')
        ->toContain('<summary type="text">Krótki opis &amp; podsumowanie.</summary>')
        ->toContain('<name>Redakcja Feed</name>')
        ->toContain('<id>urn:prawkonaraz:content-article:'.$second->id.'</id>')
        ->toContain('<published>'.$secondPublishedAt->toAtomString().'</published>')
        ->not->toContain($oldest->slug)
        ->not->toContain('poradnik-poza-feedem')
        ->not->toContain('explainer-poza-feedem')
        ->not->toContain('needs-review-poza-feedem')
        ->not->toContain('archiwalny-poza-feedem');

    expect(substr_count($xml, '<entry>'))->toBe(2);
    expect(strpos($xml, $newest->slug))->toBeLessThan(strpos($xml, $second->slug));

    $etag = $response->headers->get('ETag');
    $lastModified = $response->headers->get('Last-Modified');

    expect($etag)->not->toBeNull()
        ->and($lastModified)->not->toBeNull()
        ->and($response->headers->get('Cache-Control'))->toContain('public');

    $this->withHeader('If-None-Match', $etag)
        ->get(route('public.news.feed'))
        ->assertStatus(304)
        ->assertHeader('ETag', $etag)
        ->assertHeaderMissing('Set-Cookie');

    $this->withHeader('If-Modified-Since', $lastModified)
        ->get(route('public.news.feed'))
        ->assertStatus(304)
        ->assertHeader('ETag', $etag)
        ->assertHeaderMissing('Set-Cookie');
});

test('Atom item id stays stable while canonical link follows slug change', function () {
    Carbon::setTestNow('2026-09-18 12:10:00');
    config()->set('newsroom.public_enabled', true);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Stabilny identyfikator feedu',
        'slug' => 'stary-slug-feedu',
    ]);

    $first = $this->get(route('public.news.feed'))->assertOk()->getContent();
    $stableId = 'urn:prawkonaraz:content-article:'.$article->id;

    expect($first)
        ->toContain('<id>'.$stableId.'</id>')
        ->toContain(route('public.news.show', ['articleSlug' => 'stary-slug-feedu']));

    app(ContentArticleSlugService::class)->changeSlug($article->fresh(), 'nowy-slug-feedu');
    app(NewsroomPublicReadCache::class)->invalidateFeed();

    $second = $this->get(route('public.news.feed'))->assertOk()->getContent();

    expect($second)
        ->toContain('<id>'.$stableId.'</id>')
        ->toContain(route('public.news.show', ['articleSlug' => 'nowy-slug-feedu']))
        ->not->toContain(route('public.news.show', ['articleSlug' => 'stary-slug-feedu']));
    expect(substr_count($second, '<id>'.$stableId.'</id>'))->toBe(1);
});

test('article workflow invalidation refreshes cached Atom feed after archive', function () {
    Carbon::setTestNow('2026-09-18 12:20:00');
    config()->set('newsroom.public_enabled', true);

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Artykuł przed archiwizacją feedu',
        'slug' => 'artykul-przed-archiwizacja-feedu',
    ]);

    $this->get(route('public.news.feed'))
        ->assertOk()
        ->assertSee($article->slug, false);

    app(ContentArticlePublishingService::class)->archive($article->fresh());

    $this->get(route('public.news.feed'))
        ->assertOk()
        ->assertDontSee($article->slug, false);
});

test('public newsroom pages advertise the Atom feed only while the rollout gate is enabled', function () {
    Carbon::setTestNow('2026-09-18 12:30:00');
    config()->set('newsroom.public_enabled', true);

    $article = ContentArticle::factory()->published()->featured()->create([
        'title' => 'Artykuł z discovery feedu',
        'slug' => 'artykul-z-discovery-feedu',
        'editorial_priority' => 100,
    ]);

    $feedUrl = route('public.news.feed');

    $this->get(route('public.news'))
        ->assertOk()
        ->assertSee('type="application/atom+xml"', false)
        ->assertSee('href="'.$feedUrl.'"', false);

    $this->get(route('public.news.show', ['articleSlug' => $article->slug]))
        ->assertOk()
        ->assertSee('type="application/atom+xml"', false)
        ->assertSee('href="'.$feedUrl.'"', false);

    config()->set('newsroom.public_enabled', false);

    $this->get(route('public.news.feed'))->assertNotFound();

    $this->get(route('public.news'))
        ->assertOk()
        ->assertDontSee('application/atom+xml', false);
});
