<?php

use App\Support\NewsroomRouteContract;
use App\Support\NewsroomTaxonomyContract;

test('newsroom category seed contract is deterministic and ordered', function () {
    expect(NewsroomTaxonomyContract::CATEGORY_VERSION)->toBe('v1')
        ->and(NewsroomTaxonomyContract::categories())->toBe([
            [
                'slug' => 'prawo-jazdy',
                'name' => 'Prawo jazdy',
                'position' => 10,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'egzaminy',
                'name' => 'Egzaminy',
                'position' => 20,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'przepisy',
                'name' => 'Przepisy',
                'position' => 30,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'word',
                'name' => 'WORD',
                'position' => 40,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'kierowcy',
                'name' => 'Kierowcy',
                'position' => 50,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'osk',
                'name' => 'OSK',
                'position' => 60,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
        ]);
});

test('newsroom category seed contract has unique route compatible slugs and positions', function () {
    $categories = NewsroomTaxonomyContract::categories();
    $slugs = array_column($categories, 'slug');
    $names = array_column($categories, 'name');
    $positions = array_column($categories, 'position');

    expect($slugs)->toHaveCount(6)
        ->and(array_unique($slugs))->toHaveCount(6)
        ->and(array_unique($names))->toHaveCount(6)
        ->and(array_unique($positions))->toHaveCount(6)
        ->and($positions)->toBe([10, 20, 30, 40, 50, 60]);

    foreach ($slugs as $slug) {
        expect(preg_match('/\\A'.NewsroomRouteContract::SLUG_PATTERN.'\\z/', $slug))->toBe(1);
    }
});

test('newsroom category seo copy stays explicitly unset until editorial copy is approved', function () {
    foreach (NewsroomTaxonomyContract::categories() as $category) {
        expect($category['description'])->toBeNull()
            ->and($category['seo_title'])->toBeNull()
            ->and($category['seo_description'])->toBeNull()
            ->and($category['is_active'])->toBeTrue();
    }
});
