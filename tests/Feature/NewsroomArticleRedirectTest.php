<?php

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\Support\ContentArticleSlugService;

test('historical article paths redirect one hop to the current canonical in both route families', function () {
    $service = app(ContentArticleSlugService::class);

    $news = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'pierwszy-adres-http',
    ]);

    $service->changeSlug($news, 'drugi-adres-http');
    $service->changeSlug($news->fresh(), 'trzeci-adres-http');

    $newsCanonical = route('public.news.show', ['articleSlug' => 'trzeci-adres-http']);

    $this->get('/aktualnosci/pierwszy-adres-http?utm_source=legacy')
        ->assertStatus(301)
        ->assertRedirect($newsCanonical);

    $this->get('/aktualnosci/drugi-adres-http')
        ->assertStatus(301)
        ->assertRedirect($newsCanonical);

    $this->get($newsCanonical)->assertOk();

    $guide = ContentArticle::factory()->published()->guide()->create([
        'slug' => 'stary-poradnik-http',
    ]);

    $service->changeSlug($guide, 'aktualny-poradnik-http');

    $guideCanonical = route('public.guides.show', ['articleSlug' => 'aktualny-poradnik-http']);

    $this->get('/poradniki/stary-poradnik-http')
        ->assertStatus(301)
        ->assertRedirect($guideCanonical);

    $this->get($guideCanonical)->assertOk();
});

test('historical redirect fails closed when persisted target is not the current canonical 301', function () {
    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'stary-adres-fail-closed',
    ]);

    app(ContentArticleSlugService::class)->changeSlug($article, 'aktualny-adres-fail-closed');

    $redirect = ContentArticleRedirect::query()
        ->where('from_path', '/aktualnosci/stary-adres-fail-closed')
        ->firstOrFail();

    $redirect->update([
        'to_path' => '/aktualnosci/niekanoniczny-cel',
        'http_status' => 302,
    ]);

    $this->get('/aktualnosci/stary-adres-fail-closed')
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, follow');
});
