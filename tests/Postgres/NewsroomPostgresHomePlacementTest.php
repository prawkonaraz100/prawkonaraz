<?php

use App\Models\ContentArticle;
use App\Models\ContentHomePlacement;
use App\Support\NewsroomHomePlacementService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\PostgresTestCase;

uses(PostgresTestCase::class);

test('home placement writer waits on the same PostgreSQL transaction advisory lock for an empty tuple', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $article = ContentArticle::factory()->published()->create();
    $lockKey = NewsroomHomePlacementService::lockKey(
        ContentHomePlacement::SURFACE_NEWSROOM_HOME,
        ContentHomePlacement::SLOT_SECONDARY,
        null,
        0,
    );

    $peerName = 'newsroom_home_placement_peer';
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

        expect(fn () => app(NewsroomHomePlacementService::class)->create([
            'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
            'position' => 0,
            'article_id' => $article->id,
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
