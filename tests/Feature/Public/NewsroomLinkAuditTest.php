<?php

use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use App\Models\TrafficSign;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('newsroom.public_enabled', true);

    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');
});

afterEach(function (): void {
    Carbon::setTestNow();
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

test('N5-006 newsroom link audit passes for a healthy public article', function () {
    $category = ContentCategory::factory()->create();
    $author = ContentAuthor::factory()->published()->create();

    ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'slug' => 'zdrowy-link-audit',
    ]);

    $this->artisan('newsroom:audit-links')
        ->expectsOutput('Newsroom link audit passed.')
        ->assertSuccessful();
});

test('N5-006 newsroom link audit reports orphan source breaking and non-public targets', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');

    $category = ContentCategory::factory()->create();
    $author = ContentAuthor::factory()->published()->create();

    $issueArticle = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'slug' => 'problemowy-link-audit',
        'is_breaking' => true,
        'breaking_expires_at' => now()->subMinute(),
    ]);

    ContentArticleSource::factory()
        ->for($issueArticle, 'article')
        ->create([
            'url' => null,
            'is_publicly_cited' => true,
        ]);

    $draftTopic = ContentTopic::factory()->create([
        'slug' => 'draft-topic-link-audit',
    ]);
    $issueArticle->topics()->attach($draftTopic->id);

    $draftSign = TrafficSign::factory()->create([
        'slug' => 'draft-sign-link-audit',
    ]);
    $issueArticle->trafficSigns()->attach($draftSign->id, [
        'relation_type' => 'direct',
        'sort_order' => 0,
    ]);

    $inactiveCategory = ContentCategory::factory()->inactive()->create();
    ContentArticle::factory()->published()->create([
        'category_id' => $inactiveCategory->id,
        'author_id' => $author->id,
        'slug' => 'orphan-link-audit',
    ]);

    $draftFeatured = ContentArticle::factory()->draft()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'slug' => 'draft-featured-link-audit',
    ]);
    ContentTopic::factory()->published()->create([
        'slug' => 'broken-featured-topic-audit',
        'featured_article_id' => $draftFeatured->id,
    ]);

    $this->artisan('newsroom:audit-links')
        ->expectsOutputToContain('Orphan newsroom article without guaranteed crawlable inbound: /aktualnosci/orphan-link-audit')
        ->expectsOutputToContain('Publicly cited source URL is empty for /aktualnosci/problemowy-link-audit')
        ->expectsOutputToContain('Expired breaking state on newsroom article: /aktualnosci/problemowy-link-audit')
        ->expectsOutputToContain('Draft/non-public topic target linked from /aktualnosci/problemowy-link-audit')
        ->expectsOutputToContain('Draft/non-public traffic-sign target linked from /aktualnosci/problemowy-link-audit')
        ->expectsOutputToContain('Broken topic featured-article relation:')
        ->assertFailed();
});

test('N5-006 newsroom link audit is silent behind the public gate', function () {
    config()->set('newsroom.public_enabled', false);

    $category = ContentCategory::factory()->inactive()->create();
    $author = ContentAuthor::factory()->published()->create();

    ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'slug' => 'dark-deployed-link-audit',
        'is_breaking' => true,
        'breaking_expires_at' => now()->subMinute(),
    ]);

    $this->artisan('newsroom:audit-links')
        ->expectsOutput('Newsroom link audit passed.')
        ->assertSuccessful();
});
