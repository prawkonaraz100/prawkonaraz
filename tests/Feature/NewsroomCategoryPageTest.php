<?php

use App\Models\ContentArticle;
use App\Models\ContentCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('category page stays dark deployed while the newsroom public gate is disabled', function () {
    config(['newsroom.public_enabled' => false]);

    $category = ContentCategory::factory()->create([
        'name' => 'Przepisy',
        'slug' => 'przepisy',
    ]);

    ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Materiał ukryty przed cutover',
        'slug' => 'material-ukryty-przed-cutover',
    ]);

    $this->get(route('public.news.categories.show', ['categorySlug' => $category->slug]))
        ->assertNotFound()
        ->assertDontSee('Materiał ukryty przed cutover');
});

test('category page renders only actively distributed newsroom articles in deterministic chronology', function () {
    Carbon::setTestNow('2026-09-18 10:00:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Przepisy',
        'slug' => 'przepisy',
        'description' => 'Zmiany przepisów ważne dla kandydatów i kierowców.',
        'seo_title' => 'Przepisy drogowe — aktualności',
        'seo_description' => 'Aktualne informacje o zmianach przepisów drogowych.',
        'position' => 10,
    ]);

    $samePublishedAt = now()->subHour();

    $older = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Starsza publikacja',
        'slug' => 'starsza-publikacja',
        'published_at' => now()->subHours(3),
        'first_published_at' => now()->subHours(3),
    ]);

    $tieFirst = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Remis niższe ID',
        'slug' => 'remis-nizsze-id',
        'published_at' => $samePublishedAt,
        'first_published_at' => $samePublishedAt,
    ]);

    $tieSecond = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Remis wyższe ID',
        'slug' => 'remis-wyzsze-id',
        'published_at' => $samePublishedAt,
        'first_published_at' => $samePublishedAt,
    ]);

    ContentArticle::factory()->published()->guide()->create([
        'category_id' => $category->id,
        'title' => 'Poradnik nie należy do newsroom category listing',
        'slug' => 'poradnik-poza-listingiem-kategorii',
    ]);

    ContentArticle::factory()->archived()->create([
        'category_id' => $category->id,
        'title' => 'Archiwalny materiał nie jest aktywnie dystrybuowany',
        'slug' => 'archiwalny-material-poza-listingiem',
    ]);

    ContentArticle::factory()->needsReview()->create([
        'category_id' => $category->id,
        'title' => 'Materiał w ponownej weryfikacji nie jest aktywnie dystrybuowany',
        'slug' => 'material-needs-review-poza-listingiem',
    ]);

    ContentArticle::factory()->draft()->create([
        'category_id' => $category->id,
        'title' => 'Draft nie może wyciec',
        'slug' => 'draft-nie-moze-wyciec',
    ]);

    $relatedCategory = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
        'position' => 20,
    ]);

    ContentArticle::factory()->published()->create([
        'category_id' => $relatedCategory->id,
        'title' => 'Materiał powiązanej kategorii',
        'slug' => 'material-powiazanej-kategorii',
    ]);

    $response = $this->get(route('public.news.categories.show', ['categorySlug' => $category->slug]));

    $response
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertViewIs('newsroom.category')
        ->assertSee('<h1', false)
        ->assertSee('Przepisy')
        ->assertSee('Zmiany przepisów ważne dla kandydatów i kierowców.')
        ->assertSeeInOrder([
            $tieSecond->title,
            $tieFirst->title,
            $older->title,
        ])
        ->assertDontSee('Poradnik nie należy do newsroom category listing')
        ->assertDontSee('Archiwalny materiał nie jest aktywnie dystrybuowany')
        ->assertDontSee('Materiał w ponownej weryfikacji nie jest aktywnie dystrybuowany')
        ->assertDontSee('Draft nie może wyciec')
        ->assertSee(route('public.news.categories.show', ['categorySlug' => $relatedCategory->slug]), false)
        ->assertSee('Przepisy drogowe — aktualności')
        ->assertSee('Aktualne informacje o zmianach przepisów drogowych.');

    $response->assertViewHas('articles', function (mixed $articles): bool {
        return $articles instanceof LengthAwarePaginator
            && $articles->total() === 3
            && $articles->perPage() === 20;
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

test('category pagination is SSR crawlable and each result page is self canonical', function () {
    Carbon::setTestNow('2026-09-18 10:00:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
    ]);

    foreach (range(1, 21) as $index) {
        ContentArticle::factory()->published()->create([
            'category_id' => $category->id,
            'title' => "Materiał paginacji {$index}",
            'slug' => "material-paginacji-{$index}",
            'published_at' => now()->subMinutes($index),
            'first_published_at' => now()->subMinutes($index),
        ]);
    }

    $pageOne = $this->get(route('public.news.categories.show', ['categorySlug' => $category->slug]));

    $pageOne
        ->assertOk()
        ->assertSee('?page=2', false)
        ->assertSee('Następna');

    $pageTwoUrl = route('public.news.categories.show', ['categorySlug' => $category->slug]).'?page=2';

    $pageTwo = $this->get($pageTwoUrl);

    $pageTwo
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$pageTwoUrl.'">', false)
        ->assertSee('Materiał paginacji 21')
        ->assertDontSee('Materiał paginacji 1')
        ->assertSee('Poprzednia');

    $pageTwo->assertViewHas('articles', fn (mixed $articles): bool => $articles instanceof LengthAwarePaginator
        && $articles->currentPage() === 2
        && $articles->count() === 1);
});

test('empty active category renders a useful noindex state instead of a fake listing', function () {
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'OSK',
        'slug' => 'osk',
        'description' => null,
        'seo_title' => null,
        'seo_description' => null,
    ]);

    $response = $this->get(route('public.news.categories.show', ['categorySlug' => $category->slug]));

    $response
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertSee('<meta name="robots" content="noindex,follow">', false)
        ->assertSee('Nie ma jeszcze opublikowanych materiałów')
        ->assertSee('Wróć do aktualności')
        ->assertDontSee('Brak kart');

    $response->assertViewHas('structuredData', function (mixed $schema): bool {
        if (! is_array($schema) || ! is_array($schema['@graph'] ?? null)) {
            return false;
        }

        return collect($schema['@graph'])->pluck('@type')->contains('CollectionPage')
            && ! collect($schema['@graph'])->pluck('@type')->contains('ItemList');
    });
});

test('category page rejects inactive unknown invalid and out of range requests', function () {
    config(['newsroom.public_enabled' => true]);

    $inactive = ContentCategory::factory()->inactive()->create([
        'name' => 'Nieaktywna',
        'slug' => 'nieaktywna',
    ]);

    $active = ContentCategory::factory()->create([
        'name' => 'Prawo jazdy',
        'slug' => 'prawo-jazdy',
    ]);

    ContentArticle::factory()->published()->create([
        'category_id' => $active->id,
        'title' => 'Jedyny materiał',
        'slug' => 'jedyny-material',
    ]);

    $this->get(route('public.news.categories.show', ['categorySlug' => $inactive->slug]))
        ->assertNotFound();

    $this->get('/aktualnosci/kategoria/nie-istnieje')
        ->assertNotFound();

    $this->get(route('public.news.categories.show', ['categorySlug' => $active->slug]).'?page=0')
        ->assertNotFound();

    $this->get(route('public.news.categories.show', ['categorySlug' => $active->slug]).'?page=abc')
        ->assertNotFound();

    $this->get(route('public.news.categories.show', ['categorySlug' => $active->slug]).'?page=2')
        ->assertNotFound();
});
