<?php

use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Support\NewsroomHomeCompositionService;
use App\Support\NewsroomHomeReadModelService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    Carbon::setTestNow();
});

function newsroomHomeReadModelContainsObject(mixed $value): bool
{
    if (is_object($value)) {
        return true;
    }

    if (! is_array($value)) {
        return false;
    }

    foreach ($value as $item) {
        if (newsroomHomeReadModelContainsObject($item)) {
            return true;
        }
    }

    return false;
}

test('public newsroom home read model is rollout gated and cacheable as scalar arrays', function () {
    Carbon::setTestNow('2026-09-18 08:00:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy',
        'position' => 10,
    ]);

    $article = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'title' => 'Nowe zasady egzaminu',
        'slug' => 'nowe-zasady-egzaminu',
        'lead' => 'Najważniejsze informacje dla kandydatów.',
        'hero_image_path' => 'https://cdn.example.test/newsroom/egzamin.jpg',
        'hero_image_alt' => 'Plac egzaminacyjny',
        'hero_image_width' => 1600,
        'hero_image_height' => 900,
        'hero_focal_x' => 0.25,
        'hero_focal_y' => 0.75,
    ]);

    $readModel = app(NewsroomHomeReadModelService::class)->build();

    expect($readModel)->not->toBeNull()
        ->and($readModel['lead']['id'])->toBe($article->id)
        ->and($readModel['lead']['type'])->toBe('news')
        ->and($readModel['lead']['url'])->toBe('/aktualnosci/nowe-zasady-egzaminu')
        ->and($readModel['lead']['category'])->toMatchArray([
            'id' => $category->id,
            'name' => 'Egzaminy',
            'slug' => 'egzaminy',
        ])
        ->and($readModel['lead']['author']['url'])->toStartWith('/autorzy/')
        ->and($readModel['lead']['hero'])->toMatchArray([
            'url' => 'https://cdn.example.test/newsroom/egzamin.jpg',
            'alt' => 'Plac egzaminacyjny',
            'width' => 1600,
            'height' => 900,
            'object_position' => '25.00% 75.00%',
        ])
        ->and(newsroomHomeReadModelContainsObject($readModel))->toBeFalse()
        ->and(json_encode($readModel, JSON_THROW_ON_ERROR))->toBeString();

    config(['newsroom.public_enabled' => false]);

    expect(app(NewsroomHomeReadModelService::class)->build())->toBeNull();
});

test('newsroom home composition query budget does not grow with active category count', function () {
    Carbon::setTestNow('2026-09-18 08:00:00');

    $createCategoryWithArticle = function (int $position): void {
        $category = ContentCategory::factory()->create([
            'position' => $position,
        ]);

        ContentArticle::factory()->published()->create([
            'category_id' => $category->id,
            'editorial_priority' => 100 - $position,
            'first_published_at' => now()->subMinutes($position + 1),
            'published_at' => now()->subMinutes($position + 1),
        ]);
    };

    $createCategoryWithArticle(1);

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    app(NewsroomHomeCompositionService::class)->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 1,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
        includeManualPlacements: false,
    );

    $singleCategoryQueries = count($queries);

    foreach (range(2, 8) as $position) {
        $createCategoryWithArticle($position);
    }

    $queries = [];

    app(NewsroomHomeCompositionService::class)->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 1,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
        includeManualPlacements: false,
    );

    $manyCategoryQueries = count($queries);

    expect($singleCategoryQueries)->toBeGreaterThan(0)
        ->and($singleCategoryQueries)->toBeLessThanOrEqual(12)
        ->and($manyCategoryQueries)->toBe($singleCategoryQueries);
});
