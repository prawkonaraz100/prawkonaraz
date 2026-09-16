<?php

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\Models\ContentCategory;
use App\Support\ContentArticleSlugService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\PostgresTestCase;

uses(PostgresTestCase::class);

test('slug service path mutation waits on the same PostgreSQL transaction advisory lock', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $category = ContentCategory::factory()->create();
    $path = '/aktualnosci/zablokowana-sciezka';
    $lockKey = 'newsroom:article:path:'.$path;
    $peerName = 'newsroom_slug_peer';
    $peerConfig = config('database.connections.pgsql');

    config()->set("database.connections.{$peerName}", $peerConfig);
    DB::purge($peerName);

    $primary = DB::connection('pgsql');
    $primary->beginTransaction();

    $previousDefault = DB::getDefaultConnection();

    try {
        $primary->select(
            'select pg_advisory_xact_lock(hashtextextended(?, 0))',
            [$lockKey],
        );

        $peer = DB::connection($peerName);
        $peer->statement("set lock_timeout = '250ms'");

        DB::setDefaultConnection($peerName);
        config()->set('database.default', $peerName);

        expect(fn () => app(ContentArticleSlugService::class)->create([
            'type' => ContentArticleType::News->value,
            'category_id' => $category->id,
            'title' => 'Zablokowana ścieżka',
            'slug' => 'zablokowana-sciezka',
        ]))->toThrow(QueryException::class);
    } finally {
        DB::setDefaultConnection($previousDefault);
        config()->set('database.default', $previousDefault);

        if ($primary->transactionLevel() > 0) {
            $primary->rollBack();
        }

        DB::disconnect($peerName);
        DB::purge($peerName);
    }
});

test('published slug history stays one hop on PostgreSQL', function () {
    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'postgres-a',
    ]);
    $service = app(ContentArticleSlugService::class);

    $service->changeSlug($article, 'postgres-b');
    $service->changeSlug($article->fresh(), 'postgres-c');

    $redirects = ContentArticleRedirect::query()
        ->where('article_id', $article->id)
        ->orderBy('from_path')
        ->pluck('to_path', 'from_path')
        ->all();

    expect($redirects)->toBe([
        '/aktualnosci/postgres-a' => '/aktualnosci/postgres-c',
        '/aktualnosci/postgres-b' => '/aktualnosci/postgres-c',
    ]);
});
