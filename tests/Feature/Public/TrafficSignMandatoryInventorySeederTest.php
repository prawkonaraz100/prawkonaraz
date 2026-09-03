<?php

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishMandatorySignCatalog;
use Database\Seeders\TrafficSignMandatoryInventorySeeder;

test('mandatory inventory seeder creates a complete official published set for Polish mandatory signs', function () {
    $this->seed(TrafficSignMandatoryInventorySeeder::class);

    $category = TrafficSignCategory::query()->where('slug', 'znaki-nakazu')->firstOrFail();
    $mandatorySigns = TrafficSign::query()
        ->where('traffic_sign_category_id', $category->getKey())
        ->orderBy('code')
        ->get();

    expect($mandatorySigns)->toHaveCount(23);
    expect($mandatorySigns->pluck('code')->all())->toContain(
        'C-1',
        'C-12',
        'C-13',
        'C-13/16',
        'C-13a/16a',
        'C-19',
    );

    $expectedPublishedCodes = collect(app(PolishMandatorySignCatalog::class)->all())
        ->pluck('code')
        ->all();
    sort($expectedPublishedCodes);

    expect(
        $mandatorySigns
            ->where('is_published', true)
            ->pluck('code')
            ->sort()
            ->values()
            ->all()
    )->toBe($expectedPublishedCodes);

    expect(
        $mandatorySigns
            ->where('is_published', false)
            ->count()
    )->toBe(0);

    expect($mandatorySigns->every(function (TrafficSign $sign): bool {
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
        ->where('batch_label', 'rollout-09-mandatory-inventory')
        ->orderBy('primary_query')
        ->get();

    expect($inventoryEntries)->toHaveCount(0);
});
