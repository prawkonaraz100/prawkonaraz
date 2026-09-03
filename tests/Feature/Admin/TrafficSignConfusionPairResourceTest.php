<?php

use App\Filament\Resources\TrafficSignConfusionPairs\Pages\ListTrafficSignConfusionPairs;
use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignConfusionPair;
use App\Models\User;
use Livewire\Livewire;

test('admin sees traffic sign confusion pair resource columns', function () {
    $admin = User::factory()->admin()->create();
    $author = ContentAuthor::factory()->published()->create();
    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
    ]);
    $baseSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($category, 'category')
        ->create([
            'code' => 'A-7',
            'name' => 'Ustąp pierwszeństwa',
            'slug' => 'a-7-ustap-pierwszenstwa',
        ]);
    $confusingSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($category, 'category')
        ->create([
            'code' => 'B-20',
            'name' => 'STOP',
            'slug' => 'b-20-stop',
        ]);
    $pair = TrafficSignConfusionPair::query()->create([
        'traffic_sign_id' => $baseSign->getKey(),
        'confusing_traffic_sign_id' => $confusingSign->getKey(),
        'source' => TrafficSignConfusionPair::SOURCE_SUPPORTING_PAGE,
        'source_slug' => 'a-7-vs-b-20',
        'strength' => 90,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListTrafficSignConfusionPairs::class)
        ->assertActionExists('syncSupportingPages')
        ->assertTableColumnExists('trafficSign.code')
        ->assertTableColumnExists('confusingTrafficSign.code')
        ->assertTableColumnExists('source')
        ->assertTableColumnStateSet('source', 'Strona porównawcza', $pair)
        ->assertTableActionExists('edit')
        ->assertTableActionExists('view');
});

test('admin can sync supporting page confusion pairs from the panel reminder action', function () {
    $admin = User::factory()->admin()->create();
    $author = ContentAuthor::factory()->published()->create();
    $warningCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
        'sort_order' => 1,
    ]);
    $prohibitionCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki zakazu',
        'slug' => 'znaki-zakazu',
        'sort_order' => 2,
    ]);
    $yieldSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($warningCategory, 'category')
        ->create([
            'code' => 'A-7',
            'name' => 'Ustąp pierwszeństwa',
            'slug' => 'a-7-ustap-pierwszenstwa',
            'intro_definition' => 'Ostrzega o skrzyżowaniu z drogą z pierwszeństwem.',
        ]);
    $stopSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($prohibitionCategory, 'category')
        ->create([
            'code' => 'B-20',
            'name' => 'STOP',
            'slug' => 'b-20-stop',
            'intro_definition' => 'Nakazuje zatrzymanie przed skrzyżowaniem.',
        ]);

    $this->actingAs($admin);

    Livewire::test(ListTrafficSignConfusionPairs::class)
        ->callAction('syncSupportingPages')
        ->assertHasNoActionErrors();

    expect(TrafficSignConfusionPair::query()
        ->where('traffic_sign_id', $yieldSign->getKey())
        ->where('confusing_traffic_sign_id', $stopSign->getKey())
        ->where('source', TrafficSignConfusionPair::SOURCE_SUPPORTING_PAGE)
        ->exists())->toBeTrue();
});
