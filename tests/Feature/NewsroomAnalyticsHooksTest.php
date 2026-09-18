<?php

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Support\NewsroomBodyContract;

test('public newsroom article exposes privacy safe analytics hooks', function () {
    config(['newsroom.public_enabled' => true]);

    $related = ContentArticle::factory()->published()->create([
        'title' => 'Powiązany materiał analityczny',
        'slug' => 'powiazany-material-analityczny',
    ]);

    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'title' => 'Materiał z kontraktem analitycznym',
        'slug' => 'material-z-kontraktem-analitycznym',
        'body_blocks' => NewsroomBodyContract::normalize([
            [
                'type' => NewsroomBodyContract::BLOCK_RELATED_ARTICLE,
                'data' => ['article_id' => $related->id],
            ],
            [
                'type' => NewsroomBodyContract::BLOCK_PRODUCT_CTA,
                'data' => ['kind' => 'test'],
            ],
        ]),
    ]);

    ContentArticleSource::factory()->for($article, 'article')->create([
        'title' => 'Publiczne źródło analityczne',
        'url' => 'https://example.test/source-without-pii',
        'is_publicly_cited' => true,
    ]);

    $response = $this->get(route('public.news.show', ['articleSlug' => $article->slug]));

    $response
        ->assertOk()
        ->assertSee('data-newsroom-analytics-article', false)
        ->assertSee('data-article-id="'.$article->id.'"', false)
        ->assertSee('data-article-type="news"', false)
        ->assertSee('data-category-slug="'.$article->category->slug.'"', false)
        ->assertSee('data-newsroom-analytics-event="newsroom_category_click"', false)
        ->assertSee('data-newsroom-analytics-event="newsroom_related_article_click"', false)
        ->assertSee('data-newsroom-analytics-event="newsroom_source_click"', false)
        ->assertSee('data-newsroom-analytics-event="newsroom_product_cta_click"', false)
        ->assertDontSee('data-newsroom-analytics-title=', false)
        ->assertDontSee('data-newsroom-analytics-author=', false)
        ->assertDontSee('data-newsroom-analytics-body=', false);
});

test('product bridge template uses stable newsroom analytics event names without content fields', function () {
    $template = file_get_contents(resource_path('views/newsroom/product-bridge-block.blade.php'));

    expect($template)
        ->toContain('newsroom_related_question_click')
        ->toContain('newsroom_related_legal_click')
        ->toContain('newsroom_related_sign_click')
        ->toContain('newsroom_product_cta_click')
        ->not->toContain('data-newsroom-analytics-title')
        ->not->toContain('data-newsroom-analytics-author')
        ->not->toContain('data-newsroom-analytics-body');
});
