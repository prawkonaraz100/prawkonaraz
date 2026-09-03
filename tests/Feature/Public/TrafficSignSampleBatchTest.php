<?php

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishComplementarySignCatalog;
use App\Support\PolishDirectionSignCatalog;
use App\Support\PolishHorizontalSignCatalog;
use App\Support\PolishInformationalSignCatalog;
use App\Support\PolishMandatorySignCatalog;
use App\Support\PolishPlateSignCatalog;
use App\Support\PolishProhibitionSignCatalog;
use App\Support\PolishRailwaySignCatalog;
use App\Support\PolishSignalSignCatalog;
use App\Support\PolishWarningSignCatalog;
use Database\Seeders\TrafficSignSeoSeeder;

test('traffic sign seo seeder creates an idempotent rollout dataset with media metadata', function () {
    $this->seed(TrafficSignSeoSeeder::class);
    $this->seed(TrafficSignSeoSeeder::class);

    expect(ContentAuthor::query()->count())->toBe(1);
    expect(TrafficSignCategory::query()->count())->toBe(count(expectedTrafficSignCategorySlugs()));
    expect(TrafficSign::query()->count())->toBe(count(expectedTrafficSignSlugs()));
    expect(TrafficSignQueryMapEntry::query()->count())->toBe(array_sum(expectedTrafficSignQueryBatchCounts()));

    $author = ContentAuthor::query()->firstOrFail();
    $categories = TrafficSignCategory::query()->orderBy('sort_order')->get();
    $signs = TrafficSign::query()->with(['author', 'category'])->orderBy('sort_order')->orderBy('slug')->get();

    expect($author->slug)->toBe('katarzyna-wisniewska');
    expect($author->name)->toBe('Katarzyna Wiśniewska');
    expect($author->photo_path)->toBe('images/authors/katarzyna-wisniewska.png');
    expect($author->isPubliclyVisible())->toBeTrue();
    expect($categories->pluck('slug')->all())->toBe(expectedTrafficSignCategorySlugs());
    $actualSignSlugs = $signs->pluck('slug')->all();
    sort($actualSignSlugs);

    $expectedSignSlugs = expectedTrafficSignSlugs();
    sort($expectedSignSlugs);

    expect($actualSignSlugs)->toBe($expectedSignSlugs);

    foreach ($signs as $sign) {
        expect($sign->author->is($author))->toBeTrue();
        expect($sign->category->isPubliclyVisible())->toBeTrue();
        expect($sign->workflow_status)->toBe(TrafficSign::WORKFLOW_PUBLISHED);
        expect(filled($sign->intro_definition))->toBeTrue();
        expect(filled($sign->meaning))->toBeTrue();
        expect(filled($sign->driver_behavior))->toBeTrue();
        expect(filled($sign->legal_summary))->toBeTrue();
        expect(filled($sign->fine_summary))->toBeTrue();
        expect(filled($sign->common_mistakes))->toBeTrue();
        expect(filled($sign->review_notes))->toBeTrue();
        expect(filled($sign->meta_title))->toBeTrue();
        expect(filled($sign->meta_description))->toBeTrue();
        expect(filled($sign->image_alt))->toBeTrue();
        expect($sign->image_width)->toBe(1200);
        expect($sign->image_height)->toBe(1200);
        expect(filled($sign->og_image_alt))->toBeTrue();
        expect($sign->og_image_width)->toBe(1200);
        expect($sign->og_image_height)->toBe(1200);
        expect($sign->og_image_path)->toBe($sign->image_path);
        expect($sign->og_image_alt)->toBe($sign->image_alt);
        expect($sign->source_checked_at)->not->toBeNull();
        expect($sign->freshness_review_due_at)->not->toBeNull();
        expect($sign->freshness_review_due_at?->isFuture())->toBeTrue();
        expect(count($sign->faq_items ?? []))->toBeGreaterThanOrEqual(2);
        expect($sign->isPubliclyVisible())->toBeTrue();
    }

    $missingAssetCodes = $signs
        ->filter(fn (TrafficSign $sign): bool => ! is_file(public_path($sign->image_path))
            || ! is_file(public_path($sign->og_image_path)))
        ->pluck('code')
        ->values()
        ->all();
    sort($missingAssetCodes);

    expect($missingAssetCodes)->toBe([
        'F-1',
        'F-10',
        'F-11',
        'F-5',
        'G-1a',
        'G-1b',
        'G-1c',
        'G-3',
        'G-4',
        'T-1',
        'T-24',
        'T-30',
        'T-6a',
    ]);

    expect(
        $signs
            ->where('traffic_sign_category_id', $categories->firstWhere('slug', 'znaki-ostrzegawcze')?->getKey())
            ->reject(fn (TrafficSign $sign): bool => in_array($sign->slug, ['a-7-ustap-pierwszenstwa', 'a-17-dzieci'], true))
            ->every(fn (TrafficSign $sign): bool => ! str_contains($sign->image_path, 'warning-generic')
                && ! str_contains($sign->og_image_path, 'warning-generic')
                && str_contains($sign->image_path, '/warnings/')
                && str_contains($sign->og_image_path, '/warnings/'))
    )->toBeTrue();

    expect(
        $signs
            ->where('traffic_sign_category_id', $categories->firstWhere('slug', 'znaki-zakazu')?->getKey())
            ->every(fn (TrafficSign $sign): bool => str_contains($sign->image_path, '/prohibitions/')
                && str_contains($sign->og_image_path, '/prohibitions/')
                && str_starts_with(basename($sign->image_path), 'znak-b-'))
    )->toBeTrue();

    expect(
        $signs
            ->where('traffic_sign_category_id', $categories->firstWhere('slug', 'znaki-nakazu')?->getKey())
            ->every(fn (TrafficSign $sign): bool => str_contains($sign->image_path, '/mandatory/')
                && str_contains($sign->og_image_path, '/mandatory/')
                && str_starts_with(basename($sign->image_path), 'znak-c-'))
    )->toBeTrue();

    expect(
        $signs
            ->where('traffic_sign_category_id', $categories->firstWhere('slug', 'znaki-informacyjne')?->getKey())
            ->every(fn (TrafficSign $sign): bool => str_contains($sign->image_path, '/informational/')
                && str_contains($sign->og_image_path, '/informational/')
                && str_starts_with(basename($sign->image_path), 'znak-d-'))
    )->toBeTrue();

    $queryMapEntries = TrafficSignQueryMapEntry::query()
        ->orderBy('primary_query')
        ->get();

    expect($queryMapEntries->pluck('primary_query')->all())->toContain(
        'a-1 a-2 a-3 a-4 różnice',
        'a-1 niebezpieczny zakręt w prawo',
        'a-3 niebezpieczne zakręty pierwszy w prawo',
        'a-5 a-6 a-8 różnice',
        'a-5 skrzyżowanie dróg',
        'a-6a skrzyżowanie z drogą podporządkowaną po obu stronach',
        'a-6b skrzyżowanie z drogą podporządkowaną po prawej stronie',
        'a-6c skrzyżowanie z drogą podporządkowaną po lewej stronie',
        'a-6d a-6e różnice',
        'a-6d wlot drogi jednokierunkowej z prawej strony',
        'a-6e wlot drogi jednokierunkowej z lewej strony',
        'a-7 ustąp pierwszeństwa',
        'a-7 a stop różnice',
        'a-8 skrzyżowanie o ruchu okrężnym',
        'a-9 a-10 różnice',
        'a-9 przejazd kolejowy z zaporami',
        'a-10 przejazd kolejowy bez zapór',
        'a-11 a-11a a-12a a-12b a-12c różnice',
        'a-11 nierówna droga',
        'a-11a próg zwalniający',
        'a-12a zwężenie jezdni dwustronne',
        'a-12b zwężenie jezdni prawostronne',
        'a-12c zwężenie jezdni lewostronne',
        'a-13 a-19 a-21 różnice',
        'a-13 ruchomy most',
        'a-14 a-15 a-20 różnice',
        'a-14 roboty na drodze',
        'a-15 śliska jezdnia',
        'a-16 a-17 a-24 różnice',
        'a-16 przejście dla pieszych',
        'a-18a a-18b różnice',
        'a-18a zwierzęta gospodarskie',
        'a-18b zwierzęta dzikie',
        'a-19 boczny wiatr',
        'a-20 odcinek jezdni o ruchu dwukierunkowym',
        'a-21 tramwaj',
        'a-22 a-23 różnice',
        'a-22 niebezpieczny zjazd',
        'a-23 stromy podjazd',
        'b-21 a b-23 różnice',
        'b-21 zakaz skręcania w lewo',
        'b-33 a b-43 różnice',
        'b-33 ograniczenie prędkości',
        'b-3 b-5 b-7 różnice',
        'b-3 zakaz wjazdu pojazdów silnikowych',
        'b-13 b-13a b-14 różnice',
        'b-13 zakaz wjazdu materiałów wybuchowych',
        'b-15 b-16 b-17 b-18 b-19 różnice',
        'b-18 zakaz masy całkowitej',
        'b-25 b-26 b-27 b-28 różnice',
        'b-26 zakaz wyprzedzania przez samochody ciężarowe',
        'b-29 b-30 różnice',
        'b-29 zakaz używania sygnałów dźwiękowych',
        'b-31 pierwszeństwo dla nadjeżdżających z przeciwka',
        'b-32a kontrola graniczna',
        'b-32b rogatka uszkodzona',
        'b-32c sygnalizacja uszkodzona',
        'b-32d wjazd na prom',
        'b-32e kontrola drogowa',
        'b-39 b-40 różnice',
        'b-39 strefa ograniczonego postoju',
        'b-36 zakaz zatrzymywania się',
        'b-43 strefa ograniczonej prędkości',
        'b-35 zakaz postoju',
        'a-24 rowerzyści',
        'a-25 a-26 a-27 a-28 różnice',
        'a-25 spadające odłamki skalne',
        'a-26 lotnisko',
        'a-27 nabrzeże lub brzeg rzeki',
        'a-28 sypki żwir',
        'a-29 a-30 różnice',
        'a-29 sygnały świetlne',
        'a-30 inne niebezpieczeństwo',
        'a-31 a-32 a-33 a-34 różnice',
        'a-31 niebezpieczne pobocze',
        'a-32 oszronienie jezdni',
        'a-33 zator drogowy',
        'a-34 wypadek drogowy',
        'znaki zakazu',
        'c-1 nakaz jazdy w prawo',
        'c-1 c-2 c-3 c-4 różnice',
        'c-5 c-6 c-7 c-8 różnice',
        'c-9 c-10 c-11 różnice',
        'c-12 ruch okrężny',
        'c-13 c-13a c-13/16 c-13a/16a c-16 c-16a różnice',
        'c-13/16 droga dla rowerów i pieszych',
        'c-14 c-15 różnice',
        'c-18 c-19 różnice',
        'c-19 koniec nakazu używania łańcuchów przeciwślizgowych',
        'znaki nakazu',
        'd-1 droga z pierwszeństwem',
        'd-2 koniec drogi z pierwszeństwem',
        'd-3 droga jednokierunkowa',
        'd-4a droga bez przejazdu',
        'd-4b wjazd na drogę bez przejazdu',
        'd-6 przejście dla pieszych',
        'd-6a przejazd dla rowerzystów',
        'd-17 przystanek tramwajowy',
        'd-18 parking',
        'd-23 stacja paliwowa',
        'd-28 restauracja',
        'd-34 punkt informacji turystycznej',
        'd-1 d-2 różnice',
        'd-4a d-4b różnice',
        'd-6 d-6a różnice',
        'd-18 d-23 d-28 d-34 różnice',
        'znaki informacyjne',
    );

    expect($queryMapEntries->where('rollout_status', TrafficSignQueryMapEntry::STATUS_PUBLISHED)->count())->toBe(array_sum(expectedTrafficSignQueryBatchCounts()));

    foreach (expectedTrafficSignQueryBatchCounts() as $batchLabel => $expectedCount) {
        expect($queryMapEntries->where('batch_label', $batchLabel)->count())->toBe($expectedCount);
    }
    expect($queryMapEntries->firstWhere('primary_query', 'a-1 a-2 a-3 a-4 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-9 a-10 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-11 a-11a a-12a a-12b a-12c różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-13 a-19 a-21 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-6d a-6e różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-14 a-15 a-20 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-18a a-18b różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-22 a-23 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-25 a-26 a-27 a-28 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-29 a-30 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-31 a-32 a-33 a-34 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-7 a stop różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-5 a-6 a-8 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'a-16 a-17 a-24 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-35 zakaz postoju')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-35 zakaz postoju')?->traffic_sign_id)->not->toBeNull();
    expect($queryMapEntries->firstWhere('primary_query', 'b-36 zakaz zatrzymywania się')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-36 zakaz zatrzymywania się')?->traffic_sign_id)->not->toBeNull();
    expect($queryMapEntries->firstWhere('primary_query', 'b-21 a b-23 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-33 a b-43 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-35 a b-36 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-1 a b-2 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-3 b-5 b-7 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-13 b-13a b-14 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-15 b-16 b-17 b-18 b-19 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-25 b-26 b-27 b-28 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-29 b-30 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'b-39 b-40 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'c-1 c-2 c-3 c-4 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'c-5 c-6 c-7 c-8 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'c-9 c-10 c-11 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'c-13 c-13a c-13/16 c-13a/16a c-16 c-16a różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'c-14 c-15 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'c-18 c-19 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'd-1 d-2 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'd-4a d-4b różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'd-6 d-6a różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
    expect($queryMapEntries->firstWhere('primary_query', 'd-18 d-23 d-28 d-34 różnice')?->rollout_status)->toBe(TrafficSignQueryMapEntry::STATUS_PUBLISHED);
});

test('traffic sign seo seeder normalizes renamed prohibition slugs and queries by code', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    TrafficSign::query()->where('code', 'B-11')->update([
        'slug' => 'b-11-zakaz-wjazdu-wozkow-rowerowych',
        'name' => 'Zakaz wjazdu wózków rowerowych',
        'image_path' => 'traffic-signs/signs/prohibitions/znak-b-11-zakaz-wjazdu-wozkow-rowerowych.webp',
        'og_image_path' => 'traffic-signs/signs/prohibitions/znak-b-11-zakaz-wjazdu-wozkow-rowerowych.webp',
    ]);

    TrafficSign::query()->where('code', 'B-12')->update([
        'slug' => 'b-12-zakaz-wjazdu-wozkow-recznych',
        'name' => 'Zakaz wjazdu wózków ręcznych',
        'image_path' => 'traffic-signs/signs/prohibitions/znak-b-12-zakaz-wjazdu-wozkow-recznych.webp',
        'og_image_path' => 'traffic-signs/signs/prohibitions/znak-b-12-zakaz-wjazdu-wozkow-recznych.webp',
    ]);

    TrafficSign::query()->where('code', 'B-32')->update([
        'slug' => 'b-32-stoj-kontrola-celna',
        'name' => 'Stój - kontrola celna',
        'image_path' => 'traffic-signs/signs/prohibitions/znak-b-32-stoj-kontrola-celna.webp',
        'og_image_path' => 'traffic-signs/signs/prohibitions/znak-b-32-stoj-kontrola-celna.webp',
    ]);

    TrafficSignQueryMapEntry::query()->where('primary_query', 'b-11 zakaz wjazdu wozów ręcznych')->update([
        'primary_query' => 'b-11 zakaz wjazdu wózków rowerowych',
        'target_path' => '/znaki-drogowe/b-11-zakaz-wjazdu-wozkow-rowerowych',
    ]);

    TrafficSignQueryMapEntry::query()->where('primary_query', 'b-12 zakaz wjazdu wozów ręcznych z towarem')->update([
        'primary_query' => 'b-12 zakaz wjazdu wózków ręcznych',
        'target_path' => '/znaki-drogowe/b-12-zakaz-wjazdu-wozkow-recznych',
    ]);

    TrafficSignQueryMapEntry::query()->where('primary_query', 'b-32 zatrzymanie i odprawa celna')->update([
        'primary_query' => 'b-32 stój kontrola celna',
        'target_path' => '/znaki-drogowe/b-32-stoj-kontrola-celna',
    ]);

    $this->seed(TrafficSignSeoSeeder::class);

    $b11 = TrafficSign::query()->where('code', 'B-11')->sole();
    $b12 = TrafficSign::query()->where('code', 'B-12')->sole();
    $b32 = TrafficSign::query()->where('code', 'B-32')->sole();

    expect($b11->slug)->toBe('b-11-zakaz-wjazdu-wozow-recznych');
    expect($b11->name)->toBe('Zakaz wjazdu wozów ręcznych');
    expect($b11->image_path)->toBe('traffic-signs/signs/prohibitions/znak-b-11-zakaz-wjazdu-wozow-recznych.webp');

    expect($b12->slug)->toBe('b-12-zakaz-wjazdu-wozow-recznych-z-towarem');
    expect($b12->name)->toBe('Zakaz wjazdu wozów ręcznych z towarem');
    expect($b12->image_path)->toBe('traffic-signs/signs/prohibitions/znak-b-12-zakaz-wjazdu-wozow-recznych-z-towarem.webp');

    expect($b32->slug)->toBe('b-32-zatrzymanie-i-odprawa-celna');
    expect($b32->name)->toBe('Zatrzymanie i odprawa celna');
    expect($b32->image_path)->toBe('traffic-signs/signs/prohibitions/znak-b-32-zatrzymanie-i-odprawa-celna.webp');

    expect(TrafficSignQueryMapEntry::query()->where('primary_query', 'b-11 zakaz wjazdu wózków rowerowych')->exists())->toBeFalse();
    expect(TrafficSignQueryMapEntry::query()->where('primary_query', 'b-12 zakaz wjazdu wózków ręcznych')->exists())->toBeFalse();
    expect(TrafficSignQueryMapEntry::query()->where('primary_query', 'b-32 stój kontrola celna')->exists())->toBeFalse();

    expect(TrafficSignQueryMapEntry::query()->where('primary_query', 'b-11 zakaz wjazdu wozów ręcznych')->exists())->toBeTrue();
    expect(TrafficSignQueryMapEntry::query()->where('primary_query', 'b-12 zakaz wjazdu wozów ręcznych z towarem')->exists())->toBeTrue();
    expect(TrafficSignQueryMapEntry::query()->where('primary_query', 'b-32 zatrzymanie i odprawa celna')->exists())->toBeTrue();

    expect(TrafficSign::query()->count())->toBe(count(expectedTrafficSignSlugs()));
    expect(TrafficSignQueryMapEntry::query()->count())->toBe(array_sum(expectedTrafficSignQueryBatchCounts()));
});

test('seeded first qa batch is publicly reachable across hub category sign and author pages', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $hubResponse = $this->get(route('traffic-signs.index'))
        ->assertOk();

    foreach (expectedTrafficSignCategorySlugs() as $categorySlug) {
        $category = TrafficSignCategory::query()->where('slug', $categorySlug)->firstOrFail();

        $hubResponse->assertSeeText($category->name);
    }

    $expectedFeaturedSigns = TrafficSign::query()
        ->published()
        ->with(['author', 'category'])
        ->whereHas('author', fn ($query) => $query->published())
        ->whereHas('category', fn ($query) => $query->published())
        ->orderBy('updated_at', 'desc')
        ->orderBy('name')
        ->limit(8)
        ->get();

    expect($expectedFeaturedSigns)->toHaveCount(8);

    foreach ($expectedFeaturedSigns as $featuredSign) {
        $hubResponse->assertSeeText($featuredSign->publicTitle());
    }

    $warningCategoryResponse = $this->get(route('traffic-signs.categories.show', 'znaki-ostrzegawcze'))
        ->assertOk()
        ->assertSeeText('Znaki ostrzegawcze')
        ->assertSeeText('Powiązane porównania')
        ->assertSeeText('A-1 do A-4 - jak czytać znaki ostrzegające o zakrętach');

    $expectedPublishedWarnings = TrafficSign::query()
        ->published()
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-ostrzegawcze'))
        ->orderBy('sort_order')
        ->orderBy('slug')
        ->get();

    expect($expectedPublishedWarnings)->toHaveCount(42);

    foreach ($expectedPublishedWarnings as $warningSign) {
        $warningCategoryResponse->assertSeeText($warningSign->publicTitle());
    }

    $prohibitionCategoryResponse = $this->get(route('traffic-signs.categories.show', 'znaki-zakazu'))
        ->assertOk()
        ->assertSeeText('Znaki zakazu');

    $expectedPublishedProhibitions = TrafficSign::query()
        ->published()
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-zakazu'))
        ->orderBy('sort_order')
        ->orderBy('slug')
        ->get();

    expect($expectedPublishedProhibitions)->toHaveCount(51);

    foreach ($expectedPublishedProhibitions as $prohibitionSign) {
        $prohibitionCategoryResponse->assertSeeText($prohibitionSign->publicTitle());
    }

    $mandatoryCategoryResponse = $this->get(route('traffic-signs.categories.show', 'znaki-nakazu'))
        ->assertOk()
        ->assertSeeText('Znaki nakazu')
        ->assertSeeText('Opublikowane strony')
        ->assertSeeText('Powiązane porównania')
        ->assertSeeText('C-1 do C-4 - kiedy znak nakazuje jazdę, a kiedy sam skręt');

    $expectedPublishedMandatorySigns = TrafficSign::query()
        ->published()
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-nakazu'))
        ->orderBy('sort_order')
        ->orderBy('slug')
        ->get();

    expect($expectedPublishedMandatorySigns)->toHaveCount(23);

    foreach ($expectedPublishedMandatorySigns as $mandatorySign) {
        $mandatoryCategoryResponse->assertSeeText($mandatorySign->publicTitle());
    }

    $informationalCategoryResponse = $this->get(route('traffic-signs.categories.show', 'znaki-informacyjne'))
        ->assertOk()
        ->assertSeeText('Znaki informacyjne')
        ->assertSeeText('Powiązane porównania')
        ->assertSeeText('D-1 a D-2 - kiedy droga ma pierwszeństwo, a kiedy ten status się kończy');

    $expectedPublishedInformationalSigns = TrafficSign::query()
        ->published()
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-informacyjne'))
        ->orderBy('sort_order')
        ->orderBy('slug')
        ->get();

    expect($expectedPublishedInformationalSigns)->toHaveCount(count(app(PolishInformationalSignCatalog::class)->all()));

    foreach ($expectedPublishedInformationalSigns as $informationalSign) {
        $informationalCategoryResponse->assertSeeText($informationalSign->publicTitle());
    }

    $this->get(route('traffic-signs.show', 'a-7-ustap-pierwszenstwa'))
        ->assertOk()
        ->assertSeeText('Katarzyna Wiśniewska')
        ->assertSeeText('Podstawa prawna')
        ->assertSeeText('Najczęstsze pytania o ten znak')
        ->assertSee('http://localhost:8000/traffic-signs/signs/warnings/a-7-ustap-pierwszenstwa.webp', false);

    $this->get(route('traffic-signs.supporting.show', 'a-7-vs-b-20'))
        ->assertOk()
        ->assertSeeText('A-7 a B-20 STOP')
        ->assertSeeText('Najkrótsza różnica');

    $this->get(route('traffic-signs.supporting.show', 'a-5-do-a-8'))
        ->assertOk()
        ->assertSeeText('A-5, A-6 i A-8')
        ->assertSeeText('układzie skrzyżowania');

    $this->get(route('traffic-signs.supporting.show', 'a-16-vs-a-17-vs-a-24'))
        ->assertOk()
        ->assertSeeText('A-16, A-17 i A-24')
        ->assertSeeText('uczestnikach ruchu');

    $this->get(route('traffic-signs.supporting.show', 'a-1-do-a-4'))
        ->assertOk()
        ->assertSeeText('A-1 do A-4')
        ->assertSeeText('znaki ostrzegające o zakrętach');

    $this->get(route('traffic-signs.supporting.show', 'a-9-vs-a-10'))
        ->assertOk()
        ->assertSeeText('A-9 a A-10')
        ->assertSeeText('przejazdu kolejowego');

    $this->get(route('traffic-signs.supporting.show', 'a-11-do-a-12c'))
        ->assertOk()
        ->assertSeeText('A-11 do A-12c')
        ->assertSeeText('nawierzchni i przewężeniu');

    $this->get(route('traffic-signs.supporting.show', 'a-6d-vs-a-6e'))
        ->assertOk()
        ->assertSeeText('A-6d a A-6e')
        ->assertSeeText('drogi jednokierunkowej');

    $this->get(route('traffic-signs.supporting.show', 'a-14-a-15-a-20'))
        ->assertOk()
        ->assertSeeText('A-14, A-15 i A-20')
        ->assertSeeText('zmieniają rytm jazdy');

    $this->get(route('traffic-signs.supporting.show', 'a-18a-vs-a-18b'))
        ->assertOk()
        ->assertSeeText('A-18a a A-18b')
        ->assertSeeText('zwierzętach na drodze');

    $this->get(route('traffic-signs.supporting.show', 'a-29-vs-a-30'))
        ->assertOk()
        ->assertSeeText('A-29 a A-30')
        ->assertSeeText('sygnały świetlne');

    $this->get(route('traffic-signs.supporting.show', 'a-13-a-19-a-21'))
        ->assertOk()
        ->assertSeeText('A-13, A-19 i A-21')
        ->assertSeeText('trzy różne źródła zagrożenia');

    $this->get(route('traffic-signs.supporting.show', 'a-22-vs-a-23'))
        ->assertOk()
        ->assertSeeText('A-22 a A-23')
        ->assertSeeText('zjazd od podjazdu');

    $this->get(route('traffic-signs.supporting.show', 'a-25-do-a-28'))
        ->assertOk()
        ->assertSeeText('A-25 do A-28')
        ->assertSeeText('terenem i otoczeniem drogi');

    $this->get(route('traffic-signs.supporting.show', 'a-31-do-a-34'))
        ->assertOk()
        ->assertSeeText('A-31 do A-34')
        ->assertSeeText('stanie odcinka i sytuacji na drodze');

    $this->get(route('traffic-signs.supporting.show', 'b-21-vs-b-23'))
        ->assertOk()
        ->assertSeeText('B-21 a B-23')
        ->assertSeeText('zakaz skrętu w lewo');

    $this->get(route('traffic-signs.supporting.show', 'b-33-vs-b-43'))
        ->assertOk()
        ->assertSeeText('B-33 a B-43')
        ->assertSeeText('limit punktowy');

    $this->get(route('traffic-signs.supporting.show', 'b-35-vs-b-36'))
        ->assertOk()
        ->assertSeeText('B-35 a B-36')
        ->assertSeeText('zakaz postoju i zakaz zatrzymywania się');

    $this->get(route('traffic-signs.supporting.show', 'b-1-vs-b-2'))
        ->assertOk()
        ->assertSeeText('B-1 a B-2')
        ->assertSeeText('zakaz ruchu w obu kierunkach');

    $this->get(route('traffic-signs.supporting.show', 'b-3-vs-b-5-vs-b-7'))
        ->assertOk()
        ->assertSeeText('B-3, B-5 i B-7')
        ->assertSeeText('które pojazdy obejmuje zakaz wjazdu');

    $this->get(route('traffic-signs.supporting.show', 'b-13-vs-b-13a-vs-b-14'))
        ->assertOk()
        ->assertSeeText('B-13, B-13a i B-14')
        ->assertSeeText('materiałów niebezpiecznych');

    $this->get(route('traffic-signs.supporting.show', 'b-15-do-b-19'))
        ->assertOk()
        ->assertSeeText('B-15 do B-19')
        ->assertSeeText('szerokość, wysokość, długość, masa i nacisk osi');

    $this->get(route('traffic-signs.supporting.show', 'b-25-do-b-28'))
        ->assertOk()
        ->assertSeeText('B-25 do B-28')
        ->assertSeeText('zakazy wyprzedzania');

    $this->get(route('traffic-signs.supporting.show', 'b-29-vs-b-30'))
        ->assertOk()
        ->assertSeeText('B-29 a B-30')
        ->assertSeeText('zakazu używania klaksonu');

    $this->get(route('traffic-signs.supporting.show', 'b-39-vs-b-40'))
        ->assertOk()
        ->assertSeeText('B-39 a B-40')
        ->assertSeeText('strefa ograniczonego postoju');

    $this->get(route('traffic-signs.supporting.show', 'c-1-do-c-4'))
        ->assertOk()
        ->assertSeeText('C-1 do C-4')
        ->assertSeeText('nakazuje jazdę');

    $this->get(route('traffic-signs.supporting.show', 'c-5-do-c-8'))
        ->assertOk()
        ->assertSeeText('C-5 do C-8')
        ->assertSeeText('jazda prosto');

    $this->get(route('traffic-signs.supporting.show', 'c-9-do-c-11'))
        ->assertOk()
        ->assertSeeText('C-9 do C-11')
        ->assertSeeText('objazdu przeszkody');

    $this->get(route('traffic-signs.supporting.show', 'c-13-do-c-16a'))
        ->assertOk()
        ->assertSeeText('C-13 do C-16a')
        ->assertSeeText('wydzielony ciąg');

    $this->get(route('traffic-signs.supporting.show', 'c-14-vs-c-15'))
        ->assertOk()
        ->assertSeeText('C-14 a C-15')
        ->assertSeeText('prędkości minimalnej');

    $this->get(route('traffic-signs.supporting.show', 'c-18-vs-c-19'))
        ->assertOk()
        ->assertSeeText('C-18 a C-19')
        ->assertSeeText('łańcuchów');

    $this->get(route('traffic-signs.supporting.show', 'd-1-vs-d-2'))
        ->assertOk()
        ->assertSeeText('D-1 a D-2')
        ->assertSeeText('droga ma pierwszeństwo');

    $this->get(route('traffic-signs.supporting.show', 'd-4a-vs-d-4b'))
        ->assertOk()
        ->assertSeeText('D-4a a D-4b')
        ->assertSeeText('droga bez przejazdu');

    $this->get(route('traffic-signs.supporting.show', 'd-6-vs-d-6a'))
        ->assertOk()
        ->assertSeeText('D-6 a D-6a')
        ->assertSeeText('przejście dla pieszych');

    $this->get(route('traffic-signs.supporting.show', 'd-18-d-23-d-28-d-34'))
        ->assertOk()
        ->assertSeeText('D-18, D-23, D-28 i D-34')
        ->assertSeeText('usługowe');

    $this->get(route('content-authors.show', 'redakcja-brd'))
        ->assertRedirect(route('content-authors.show', 'katarzyna-wisniewska'), 301);

    $authorResponse = $this->get(route('content-authors.show', 'katarzyna-wisniewska'))
        ->assertOk()
        ->assertSeeText('Katarzyna Wiśniewska')
        ->assertSeeText('Specjalistka ds. organizacji ruchu drogowego i BRD')
        ->assertSee('images/authors/katarzyna-wisniewska.png', false);

    $expectedAuthorSigns = TrafficSign::query()
        ->published()
        ->whereHas('author', fn ($query) => $query->where('slug', 'katarzyna-wisniewska'))
        ->orderBy('sort_order')
        ->orderBy('slug')
        ->get();

    expect($expectedAuthorSigns)->toHaveCount(count(expectedTrafficSignSlugs()));

    foreach ($expectedAuthorSigns as $authorSign) {
        $authorResponse->assertSeeText($authorSign->publicTitle());
    }
});

function expectedTrafficSignCategorySlugs(): array
{
    return [
        'znaki-ostrzegawcze',
        'znaki-zakazu',
        'znaki-nakazu',
        'znaki-informacyjne',
        'znaki-kierunku-i-miejscowosci',
        'znaki-uzupelniajace',
        'tabliczki-do-znakow',
        'znaki-przed-przejazdami-kolejowymi',
        'znaki-drogowe-poziome',
        'sygnaly-swietlne',
        'kontrolki-w-samochodzie',
        'osoba-kierujaca-ruchem',
        'znaki-wojskowe',
        'sygnaly-dla-tramwajow',
        'znaki-tramwajowe',
        'urzadzenia-bezpieczenstwa-ruchu',
    ];
}

function expectedTrafficSignSlugs(): array
{
    return collect([
        app(PolishWarningSignCatalog::class)->all(),
        app(PolishProhibitionSignCatalog::class)->all(),
        app(PolishMandatorySignCatalog::class)->all(),
        app(PolishInformationalSignCatalog::class)->all(),
        app(PolishDirectionSignCatalog::class)->all(),
        app(PolishComplementarySignCatalog::class)->all(),
        app(PolishPlateSignCatalog::class)->all(),
        app(PolishRailwaySignCatalog::class)->all(),
        app(PolishHorizontalSignCatalog::class)->all(),
        app(PolishSignalSignCatalog::class)->all(),
    ])
        ->flatten(1)
        ->pluck('slug')
        ->values()
        ->all();
}

function expectedTrafficSignQueryBatchCounts(): array
{
    return [
        'rollout-01' => 3,
        'rollout-02' => 15,
        'rollout-03' => 19,
        'rollout-04' => 22,
        'rollout-05' => 9,
        'rollout-06' => 14,
        'rollout-07' => 13,
        'rollout-08' => 17,
        'rollout-09-mandatory' => 30,
        'rollout-10-informational' => 79,
        'rollout-11-directions' => 45,
        'rollout-12-complementary' => 5,
        'rollout-13-plates' => 5,
        'rollout-14-railway' => 6,
        'rollout-15-horizontal' => 22,
        'rollout-16-signals' => 14,
        'sample-qa-01' => 7,
    ];
}
