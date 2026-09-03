<?php

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishWarningSignCatalog;
use Database\Seeders\TrafficSignWarningInventorySeeder;

test('warning inventory seeder creates a complete official backlog for Polish warning signs', function () {
    $this->seed(TrafficSignWarningInventorySeeder::class);

    $category = TrafficSignCategory::query()->where('slug', 'znaki-ostrzegawcze')->firstOrFail();
    $warningSigns = TrafficSign::query()
        ->where('traffic_sign_category_id', $category->getKey())
        ->orderBy('code')
        ->get();

    expect($warningSigns)->toHaveCount(42);
    expect($warningSigns->pluck('code')->all())->toContain(
        'A-1',
        'A-6a',
        'A-11a',
        'A-18b',
        'A-30',
        'A-34',
    );

    $expectedPublishedCodes = collect(app(PolishWarningSignCatalog::class)->all())
        ->pluck('code')
        ->all();
    sort($expectedPublishedCodes);

    expect(
        $warningSigns
            ->where('is_published', true)
            ->pluck('code')
            ->sort()
            ->values()
            ->all()
    )->toBe($expectedPublishedCodes);

    $backlogSigns = $warningSigns->where('is_published', false);

    expect($backlogSigns)->toHaveCount(0);
    expect($backlogSigns->every(function (TrafficSign $sign): bool {
        return $sign->workflow_status === TrafficSign::WORKFLOW_IN_REVIEW
            && filled($sign->intro_definition)
            && filled($sign->meaning)
            && filled($sign->placement)
            && filled($sign->driver_behavior)
            && filled($sign->common_mistakes)
            && filled($sign->meta_title)
            && filled($sign->meta_description)
            && is_file(public_path($sign->image_path))
            && is_file(public_path($sign->og_image_path));
    }))->toBeTrue();

    expect($warningSigns->every(function (TrafficSign $sign): bool {
        return str_ends_with($sign->image_path, '.webp')
            && $sign->image_path === $sign->og_image_path
            && $sign->image_width === 1200
            && $sign->image_height === 1200
            && $sign->og_image_width === 1200
            && $sign->og_image_height === 1200;
    }))->toBeTrue();

    $inventoryEntries = TrafficSignQueryMapEntry::query()
        ->where('batch_label', 'rollout-07-warnings-inventory')
        ->orderBy('primary_query')
        ->get();

    expect($inventoryEntries)->toHaveCount(0);
    expect($inventoryEntries->every(fn (TrafficSignQueryMapEntry $entry): bool => $entry->target_type === TrafficSignQueryMapEntry::TARGET_SIGN))->toBeTrue();
});
