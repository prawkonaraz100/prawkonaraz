<?php

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentTopic;
use Illuminate\Support\Facades\DB;
use Tests\PostgresTestCase;

uses(PostgresTestCase::class);

test('newsroom models and public scopes execute on PostgreSQL', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $published = ContentArticle::factory()->published()->create();
    $noindex = ContentArticle::factory()->published()->noindex()->create();
    $archived = ContentArticle::factory()->archived()->create();
    $withdrawn = ContentArticle::factory()->withdrawn()->create();

    expect($published->type)->toBe(ContentArticleType::News)
        ->and($published->workflow_status)->toBe(ContentArticleWorkflowStatus::Published)
        ->and($published->body_blocks)->toBeArray();

    expect(ContentArticle::query()->publiclyVisible()->pluck('id')->all())
        ->toContain($published->id, $noindex->id, $archived->id)
        ->not->toContain($withdrawn->id);

    expect(ContentArticle::query()->activelyDistributed()->pluck('id')->all())
        ->toContain($published->id, $noindex->id)
        ->not->toContain($archived->id, $withdrawn->id);

    expect(ContentArticle::query()->indexable()->pluck('id')->all())
        ->toContain($published->id, $archived->id)
        ->not->toContain($noindex->id, $withdrawn->id);

    $topic = ContentTopic::factory()->published()->create();
    $corpus = ContentArticle::factory()->published()->count(3)->create();
    $topic->articles()->attach($corpus->pluck('id')->all());

    expect($topic->meetsPublicationCorpusBaseline())->toBeTrue()
        ->and($topic->meetsPublicationRequirements())->toBeTrue();
});
