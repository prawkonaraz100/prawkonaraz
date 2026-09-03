<?php

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use Database\Seeders\TrafficSignProhibitionInventorySeeder;

test('prohibition inventory seeder creates a complete official backlog for Polish prohibition signs', function () {
    $this->seed(TrafficSignProhibitionInventorySeeder::class);

    $category = TrafficSignCategory::query()->where('slug', 'znaki-zakazu')->firstOrFail();
    $prohibitionSigns = TrafficSign::query()
        ->where('traffic_sign_category_id', $category->getKey())
        ->orderBy('code')
        ->get();

    expect($prohibitionSigns)->toHaveCount(51);
    expect($prohibitionSigns->pluck('code')->all())->toContain(
        'B-1',
        'B-3a',
        'B-11',
        'B-13a',
        'B-19',
        'B-32',
        'B-32a',
        'B-32b',
        'B-32c',
        'B-32d',
        'B-32e',
        'B-44',
    );

    expect(
        $prohibitionSigns
            ->where('is_published', false)
            ->count()
    )->toBe(0);

    expect(
        $prohibitionSigns
            ->where('is_published', true)
            ->count()
    )->toBe(51);

    $draftSigns = $prohibitionSigns->where('is_published', false);

    expect($draftSigns)->toHaveCount(0);
    expect($prohibitionSigns->every(function (TrafficSign $sign): bool {
        return is_file(public_path($sign->image_path))
            && $sign->image_path === $sign->og_image_path
            && $sign->image_alt === $sign->name
            && $sign->og_image_alt === $sign->name
            && $sign->image_width === 1200
            && $sign->image_height === 1200
            && $sign->og_image_width === 1200
            && $sign->og_image_height === 1200;
    }))->toBeTrue();

    $inventoryEntries = TrafficSignQueryMapEntry::query()
        ->where('batch_label', 'rollout-02-prohibitions')
        ->orderBy('primary_query')
        ->get();

    expect($inventoryEntries)->toHaveCount(0);
});
