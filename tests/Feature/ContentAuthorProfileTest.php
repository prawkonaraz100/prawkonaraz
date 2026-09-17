<?php

use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Support\SeoSitemapBuilder;
use Illuminate\Support\Carbon;

it('integrates indexable newsroom contributions into the existing author profile by lifecycle state', function () {
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Anna Kowalska',
        'slug' => 'anna-kowalska',
    ]);

    $published = ContentArticle::factory()->published()->create([
        'author_id' => $author->id,
        'title' => 'Aktualny artykuł autora',
        'slug' => 'aktualny-artykul-autora',
    ]);
    $needsReview = ContentArticle::factory()->needsReview()->create([
        'author_id' => $author->id,
        'title' => 'Artykuł w trakcie weryfikacji',
        'slug' => 'artykul-w-trakcie-weryfikacji',
    ]);
    $archived = ContentArticle::factory()->archived()->create([
        'author_id' => $author->id,
        'title' => 'Archiwalny artykuł autora',
        'slug' => 'archiwalny-artykul-autora',
    ]);
    ContentArticle::factory()->published()->noindex()->create([
        'author_id' => $author->id,
        'title' => 'Artykuł noindex autora',
        'slug' => 'artykul-noindex-autora',
    ]);
    ContentArticle::factory()->scheduled()->create([
        'author_id' => $author->id,
        'title' => 'Zaplanowany artykuł autora',
        'slug' => 'zaplanowany-artykul-autora',
    ]);
    $inactiveCategory = ContentCategory::factory()->inactive()->create();
    ContentArticle::factory()->published()->create([
        'author_id' => $author->id,
        'category_id' => $inactiveCategory->id,
        'title' => 'Artykuł w nieaktywnej kategorii',
        'slug' => 'artykul-w-nieaktywnej-kategorii',
    ]);

    $response = $this->get(route('content-authors.show', $author->slug));

    $response
        ->assertOk()
        ->assertSee('Aktualne, opublikowane materiały autora')
        ->assertSee('Aktualny artykuł autora')
        ->assertSee('href="/aktualnosci/'.$published->slug.'"', false)
        ->assertSee('W trakcie weryfikacji')
        ->assertSee('Artykuł w trakcie weryfikacji')
        ->assertSee('href="/aktualnosci/'.$needsReview->slug.'"', false)
        ->assertSee('Archiwum')
        ->assertSee('Archiwalny artykuł autora')
        ->assertSee('href="/aktualnosci/'.$archived->slug.'"', false)
        ->assertDontSee('Artykuł noindex autora')
        ->assertDontSee('Zaplanowany artykuł autora')
        ->assertDontSee('Artykuł w nieaktywnej kategorii')
        ->assertSee('"@type":"Person"', false)
        ->assertSee(route('content-authors.show', $author->slug).'#person', false);
});

it('includes published authors in the author sitemap only when they have an indexable public contribution', function () {
    $articleUpdatedAt = Carbon::parse('2026-09-17 12:00:00');
    $articleAuthor = ContentAuthor::factory()->published()->create([
        'name' => 'Autor artykułu',
        'slug' => 'autor-artykulu',
        'updated_at' => Carbon::parse('2026-09-16 12:00:00'),
    ]);
    ContentArticle::factory()->published()->create([
        'author_id' => $articleAuthor->id,
        'title' => 'Publiczny artykuł do sitemap',
        'slug' => 'publiczny-artykul-do-sitemap',
        'updated_at' => $articleUpdatedAt,
    ]);

    $needsReviewAuthor = ContentAuthor::factory()->published()->create([
        'name' => 'Autor weryfikowanego artykułu',
        'slug' => 'autor-weryfikowanego-artykulu',
    ]);
    ContentArticle::factory()->needsReview()->create([
        'author_id' => $needsReviewAuthor->id,
        'title' => 'Publiczny artykuł w ponownej weryfikacji',
        'slug' => 'publiczny-artykul-w-ponownej-weryfikacji',
    ]);

    $noindexAuthor = ContentAuthor::factory()->published()->create([
        'name' => 'Autor tylko noindex',
        'slug' => 'autor-tylko-noindex',
    ]);
    ContentArticle::factory()->published()->noindex()->create([
        'author_id' => $noindexAuthor->id,
    ]);

    $scheduledAuthor = ContentAuthor::factory()->published()->create([
        'name' => 'Autor tylko zaplanowany',
        'slug' => 'autor-tylko-zaplanowany',
    ]);
    ContentArticle::factory()->scheduled()->create([
        'author_id' => $scheduledAuthor->id,
    ]);

    $hiddenAuthor = ContentAuthor::factory()->create([
        'name' => 'Autor niepubliczny',
        'slug' => 'autor-niepubliczny',
    ]);
    ContentArticle::factory()->published()->create([
        'author_id' => $hiddenAuthor->id,
    ]);

    $inactiveCategoryAuthor = ContentAuthor::factory()->published()->create([
        'name' => 'Autor nieaktywnej kategorii',
        'slug' => 'autor-nieaktywnej-kategorii',
    ]);
    $inactiveCategory = ContentCategory::factory()->inactive()->create();
    ContentArticle::factory()->published()->create([
        'author_id' => $inactiveCategoryAuthor->id,
        'category_id' => $inactiveCategory->id,
    ]);

    $rows = collect(app(SeoSitemapBuilder::class)->authorUrls())->keyBy('loc');

    expect($rows)
        ->toHaveKey(route('content-authors.show', $articleAuthor->slug))
        ->toHaveKey(route('content-authors.show', $needsReviewAuthor->slug))
        ->not->toHaveKey(route('content-authors.show', $noindexAuthor->slug))
        ->not->toHaveKey(route('content-authors.show', $scheduledAuthor->slug))
        ->not->toHaveKey(route('content-authors.show', $hiddenAuthor->slug))
        ->not->toHaveKey(route('content-authors.show', $inactiveCategoryAuthor->slug));

    expect($rows->get(route('content-authors.show', $articleAuthor->slug))['lastmod'])
        ->toBe($articleUpdatedAt->toIso8601String());
});
