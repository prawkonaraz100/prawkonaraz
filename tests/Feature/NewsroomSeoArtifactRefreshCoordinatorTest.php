<?php

use App\Events\ContentArticlePublicReadChanged;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Events\ContentHomePlacementChanged;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;
use App\Support\SeoSitemapAuditor;
use App\Support\SeoSitemapGenerator;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;

beforeEach(function () {
    config()->set('newsroom.seo_artifact_cache_store', 'array');
    config()->set('newsroom.seo_artifact_refresh_lock_seconds', 60);
    Cache::store('array')->flush();
});

it('tracks dirty versions and clears only an unchanged version', function () {
    $coordinator = app(NewsroomSeoArtifactRefreshCoordinator::class);

    expect($coordinator->currentVersion())->toBe(0)
        ->and($coordinator->cleanVersion())->toBe(0)
        ->and($coordinator->isDirty())->toBeFalse();

    expect($coordinator->markDirty())->toBe(1)
        ->and($coordinator->isDirty())->toBeTrue()
        ->and($coordinator->markCleanIfUnchanged(1))->toBeTrue()
        ->and($coordinator->isDirty())->toBeFalse();

    expect($coordinator->markDirty())->toBe(2)
        ->and($coordinator->markDirty())->toBe(3)
        ->and($coordinator->markCleanIfUnchanged(2))->toBeFalse()
        ->and($coordinator->cleanVersion())->toBe(1)
        ->and($coordinator->isDirty())->toBeTrue();
});

it('marks SEO artifacts dirty from public newsroom lifecycle events', function () {
    $coordinator = app(NewsroomSeoArtifactRefreshCoordinator::class);

    ContentArticleWorkflowTransitioned::dispatch(
        10,
        'content_article.published',
        'review',
        'published',
        'user',
    );
    ContentArticlePublicReadChanged::dispatch(10, 'content_article.public_updated');
    ContentHomePlacementChanged::dispatch(20, 'content_home_placement.updated');

    expect($coordinator->currentVersion())->toBe(3)
        ->and($coordinator->isDirty())->toBeTrue();
});

it('skips sitemap generation when the artifact version is already clean', function () {
    $this->mock(SeoSitemapGenerator::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('generate');
    });
    $this->mock(SeoSitemapAuditor::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('audit');
    });

    $this->artisan('newsroom:refresh-seo-artifacts-if-dirty')
        ->expectsOutput('Newsroom SEO artifacts are already clean.')
        ->assertSuccessful();
});

it('marks the generated version clean after a successful refresh', function () {
    $coordinator = app(NewsroomSeoArtifactRefreshCoordinator::class);
    $version = $coordinator->markDirty();

    $this->mock(SeoSitemapGenerator::class, function (MockInterface $mock) {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn([[
                'path' => 'sitemap.xml',
                'urls' => 1,
                'bytes' => 128,
            ]]);
    });
    $this->mock(SeoSitemapAuditor::class, function (MockInterface $mock) {
        $mock->shouldReceive('audit')
            ->once()
            ->andReturn([]);
    });

    $this->artisan('newsroom:refresh-seo-artifacts-if-dirty')
        ->expectsOutput('sitemap.xml urls=1 bytes=128')
        ->expectsOutput(sprintf('Newsroom SEO artifacts refreshed for version %d.', $version))
        ->assertSuccessful();

    expect($coordinator->cleanVersion())->toBe($version)
        ->and($coordinator->isDirty())->toBeFalse();
});

it('retains dirty state when the sitemap audit fails', function () {
    $coordinator = app(NewsroomSeoArtifactRefreshCoordinator::class);
    $coordinator->markDirty();

    $this->mock(SeoSitemapGenerator::class, function (MockInterface $mock) {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn([]);
    });
    $this->mock(SeoSitemapAuditor::class, function (MockInterface $mock) {
        $mock->shouldReceive('audit')
            ->once()
            ->andReturn(['audit failed']);
    });

    $this->artisan('newsroom:refresh-seo-artifacts-if-dirty')
        ->expectsOutput('audit failed')
        ->assertFailed();

    expect($coordinator->cleanVersion())->toBe(0)
        ->and($coordinator->isDirty())->toBeTrue();
});

it('retains dirty state when a newer version appears during generation', function () {
    $coordinator = app(NewsroomSeoArtifactRefreshCoordinator::class);
    $coordinator->markDirty();

    $this->mock(SeoSitemapGenerator::class, function (MockInterface $mock) use ($coordinator) {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturnUsing(function () use ($coordinator): array {
                $coordinator->markDirty();

                return [];
            });
    });
    $this->mock(SeoSitemapAuditor::class, function (MockInterface $mock) {
        $mock->shouldReceive('audit')
            ->once()
            ->andReturn([]);
    });

    $this->artisan('newsroom:refresh-seo-artifacts-if-dirty')
        ->expectsOutput('Newsroom SEO artifacts changed during refresh; dirty state was retained for the next pass.')
        ->assertSuccessful();

    expect($coordinator->currentVersion())->toBe(2)
        ->and($coordinator->cleanVersion())->toBe(0)
        ->and($coordinator->isDirty())->toBeTrue();
});
