<?php

use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\User;
use App\Support\ContentArticleSlugService;
use App\Support\IndexNowUrlCollector;
use App\Support\NewsroomPublicGate;
use App\Support\SeoSitemapBuilder;

test('disabled newsroom public gate prevents dark deployed public discovery while preserving placeholders and private preview', function () {
    config()->set('newsroom.public_enabled', false);

    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Autor tylko Newsroomu',
        'slug' => 'autor-tylko-newsroomu',
    ]);
    $article = ContentArticle::factory()->published()->create([
        'author_id' => $author->id,
        'title' => 'Ukryty dark-deploy artykuł',
        'slug' => 'ukryty-dark-deploy-artykul',
    ]);

    expect(app(NewsroomPublicGate::class)->disabled())->toBeTrue();

    $this->get(route('public.news.show', ['articleSlug' => $article->slug]))
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertDontSee('Ukryty dark-deploy artykuł');

    $guide = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Ukryty dark-deploy poradnik',
        'slug' => 'ukryty-dark-deploy-poradnik',
    ]);

    $this->get(route('public.guides.show', ['articleSlug' => $guide->slug]))
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertDontSee('Ukryty dark-deploy poradnik');

    app(ContentArticleSlugService::class)->changeSlug($article, 'aktualny-dark-deploy-artykul');

    $this->get('/aktualnosci/ukryty-dark-deploy-artykul')
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, follow')
        ->assertDontSee('aktualny-dark-deploy-artykul');

    $this->get(route('public.news'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow');

    $this->get(route('public.guides'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow');

    $this->get(route('public.news.categories.show', ['categorySlug' => $article->category->slug]))
        ->assertNotFound();

    $this->get(route('public.news.topics.show', ['topicSlug' => 'dark-deploy-topic']))
        ->assertNotFound();

    $this->get(route('public.news.feed'))
        ->assertNotFound();

    $this->get(route('content-authors.show', ['authorSlug' => $author->slug]))
        ->assertOk()
        ->assertDontSee('Ukryty dark-deploy artykuł');

    $authorUrl = route('content-authors.show', ['authorSlug' => $author->slug]);
    $articleUrl = route('public.news.show', ['articleSlug' => 'aktualny-dark-deploy-artykul']);

    expect(collect(app(SeoSitemapBuilder::class)->authorUrls())->pluck('loc'))
        ->not->toContain($authorUrl);

    expect(app(IndexNowUrlCollector::class)->publicPageUrls())
        ->not->toContain($authorUrl)
        ->not->toContain($articleUrl);

    $previewArticle = ContentArticle::factory()->draft()->create([
        'title' => 'Prywatny podgląd nadal działa',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.newsroom.articles.preview', $previewArticle))
        ->assertOk()
        ->assertSee('Prywatny podgląd nadal działa');
});

test('enabled newsroom public gate exposes only already implemented eligible public surfaces', function () {
    config()->set('newsroom.public_enabled', true);

    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Publiczny autor Newsroomu',
        'slug' => 'publiczny-autor-newsroomu',
    ]);
    $article = ContentArticle::factory()->published()->create([
        'author_id' => $author->id,
        'title' => 'Publiczny artykuł po cutover',
        'slug' => 'publiczny-artykul-po-cutover',
    ]);

    expect(app(NewsroomPublicGate::class)->enabled())->toBeTrue();

    $this->get(route('public.news.show', ['articleSlug' => $article->slug]))
        ->assertOk()
        ->assertSee('Publiczny artykuł po cutover');

    $this->get(route('content-authors.show', ['authorSlug' => $author->slug]))
        ->assertOk()
        ->assertSee('Publiczny artykuł po cutover');

    $authorUrl = route('content-authors.show', ['authorSlug' => $author->slug]);

    expect(collect(app(SeoSitemapBuilder::class)->authorUrls())->pluck('loc'))
        ->toContain($authorUrl);

    expect(app(IndexNowUrlCollector::class)->publicPageUrls())
        ->toContain($authorUrl);

    app(ContentArticleSlugService::class)->changeSlug($article, 'publiczny-artykul-po-zmianie');

    $this->get('/aktualnosci/publiczny-artykul-po-cutover')
        ->assertStatus(301)
        ->assertRedirect(route('public.news.show', ['articleSlug' => 'publiczny-artykul-po-zmianie']));

    $this->get(route('public.news'))
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertViewIs('newsroom.home');

    $this->get(route('public.news.categories.show', ['categorySlug' => $article->category->slug]))
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertViewIs('newsroom.category')
        ->assertSee('Publiczny artykuł po cutover');

    $guide = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Publiczny poradnik po cutover',
        'slug' => 'publiczny-poradnik-po-cutover',
    ]);

    $this->get(route('public.guides'))
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertViewIs('newsroom.guides')
        ->assertSee('Publiczny poradnik po cutover');

    $this->get(route('public.news.feed'))->assertNotFound();
});
